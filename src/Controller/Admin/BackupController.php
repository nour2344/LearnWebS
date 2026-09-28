<?php

namespace App\Controller\Admin;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Process\Process;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/backup')]
class BackupController extends AbstractController
{
    private string $projectDir;

    public function __construct(
        private ManagerRegistry $doctrine,
        ParameterBagInterface $bag
    ) {
        $this->projectDir = $bag->get('kernel.project_dir');
    }

    #[Route('', name: 'admin_backup', methods: ['GET'])]
    public function index(): Response
    {
        $conn   = $this->doctrine->getConnection();
        $params = $conn->getParams();
        $driver = $params['driver'] ?? 'unknown';

        $dbInfo = [
            'driver' => $driver,
            'dbname' => $params['dbname'] ?? null,
            'host'   => $params['host'] ?? null,
            'port'   => $params['port'] ?? null,
            'user'   => $params['user'] ?? null,
            'path'   => $params['path'] ?? null, // SQLite
        ];

        // For SQLite show file size
        $sqliteSize = null;
        if ($driver === 'pdo_sqlite') {
            $path = $this->resolveSqlitePath($dbInfo['path']);
            if (is_file($path)) {
                $sqliteSize = filesize($path);
            }
        }

        return $this->render('admin/backup/index.html.twig', [
            'db'         => $dbInfo,
            'sqliteSize' => $sqliteSize,
        ]);
    }

    #[Route('/dump', name: 'admin_backup_dump', methods: ['POST'])]
    public function dump(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('backup_dump', (string)$request->request->get('_token'))) {
            $this->addFlash('danger', 'CSRF invalide.');
            return $this->redirectToRoute('admin_backup');
        }

        $conn   = $this->doctrine->getConnection();
        $params = $conn->getParams();
        $driver = $params['driver'] ?? 'unknown';

        if ($driver === 'pdo_sqlite') {
            // Copy the SQLite file and download it
            $path = $this->resolveSqlitePath($params['path'] ?? null);
            if (!is_file($path)) {
                $this->addFlash('danger', 'Fichier SQLite introuvable.');
                return $this->redirectToRoute('admin_backup');
            }
            $ts      = date('Ymd_His');
            $tmp     = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "sqlite_backup_$ts.db";
            if (!@copy($path, $tmp)) {
                $this->addFlash('danger', 'Impossible de copier la base SQLite.');
                return $this->redirectToRoute('admin_backup');
            }
            $resp = new BinaryFileResponse($tmp);
            $resp->setContentDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                "backup_sqlite_$ts.db"
            );
            $resp->deleteFileAfterSend(true);
            return $resp;
        }

        if ($driver === 'pdo_mysql') {
            // Use mysqldump to create a .sql file
            $host = $params['host'] ?? '127.0.0.1';
            $port = $params['port'] ?? null;
            $user = $params['user'] ?? '';
            $pass = $params['password'] ?? '';
            $db   = $params['dbname'] ?? '';

            $mysqldump = $_SERVER['MYSQLDUMP_PATH'] ?? getenv('MYSQLDUMP_PATH') ?? 'mysqldump';

            $ts  = date('Ymd_His');
            $tmp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "mysql_backup_$ts.sql";

            $cmd = [$mysqldump, "--host=$host", "--user=$user"];
            if ($port) { $cmd[] = "--port=$port"; }
            if ($pass !== '') { $cmd[] = "--password=$pass"; }
            $cmd[] = $db;
            $cmd[] = "--result-file=$tmp";

            $process = new Process($cmd);
            $process->setTimeout(180);
            $process->run();

            if (!$process->isSuccessful() || !is_file($tmp)) {
                $this->addFlash('danger', 'Échec du mysqldump. Assurez-vous que mysqldump est dans le PATH (ou définissez MYSQLDUMP_PATH).');
                return $this->redirectToRoute('admin_backup');
            }

            $resp = new BinaryFileResponse($tmp);
            $resp->setContentDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                "backup_mysql_$ts.sql"
            );
            $resp->deleteFileAfterSend(true);
            return $resp;
        }

        $this->addFlash('danger', 'Driver de base non géré pour la sauvegarde.');
        return $this->redirectToRoute('admin_backup');
    }

    #[Route('/restore', name: 'admin_backup_restore', methods: ['POST'])]
    public function restore(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('backup_restore', (string)$request->request->get('_token'))) {
            $this->addFlash('danger', 'CSRF invalide.');
            return $this->redirectToRoute('admin_backup');
        }

        // Ask user to type RESTORE to confirm
        if (trim((string)$request->request->get('confirm')) !== 'RESTORE') {
            $this->addFlash('danger', 'Tapez exactement RESTORE pour confirmer.');
            return $this->redirectToRoute('admin_backup');
        }

        /** @var UploadedFile|null $file */
        $file = $request->files->get('backup_file');
        if (!$file || !$file->isValid()) {
            $this->addFlash('danger', 'Aucun fichier valide.');
            return $this->redirectToRoute('admin_backup');
        }

        $conn   = $this->doctrine->getConnection();
        $params = $conn->getParams();
        $driver = $params['driver'] ?? 'unknown';

        if ($driver === 'pdo_sqlite') {
            // Replace the SQLite file
            $dest = $this->resolveSqlitePath($params['path'] ?? null);
            if (!$dest) {
                $this->addFlash('danger', 'Chemin SQLite introuvable.');
                return $this->redirectToRoute('admin_backup');
            }

            // Close connection to release file lock
            if (method_exists($conn, 'close')) {
                $conn->close();
            }

            // Move uploaded as new DB (overwrite)
            try {
                // Optional: keep previous as .bak
                if (is_file($dest)) {
                    @copy($dest, $dest . '.bak.' . date('Ymd_His'));
                }
                // Move to a temp path in same dir then rename (safer on Windows)
                $tmpTarget = $dest . '.restoring';
                @unlink($tmpTarget);
                $file->move(dirname($dest), basename($tmpTarget));
                @rename($tmpTarget, $dest);
            } catch (\Throwable $e) {
                $this->addFlash('danger', 'Restauration SQLite échouée: ' . $e->getMessage());
                return $this->redirectToRoute('admin_backup');
            }

            $this->addFlash('success', 'Base SQLite restaurée avec succès.');
            return $this->redirectToRoute('admin_backup');
        }

        if ($driver === 'pdo_mysql') {
            // Import the .sql file via mysql client
            $host = $params['host'] ?? '127.0.0.1';
            $port = $params['port'] ?? null;
            $user = $params['user'] ?? '';
            $pass = $params['password'] ?? '';
            $db   = $params['dbname'] ?? '';

            $mysql = $_SERVER['MYSQL_PATH'] ?? getenv('MYSQL_PATH') ?? 'mysql';

            $cmd = [$mysql, "--host=$host", "--user=$user"];
            if ($port) { $cmd[] = "--port=$port"; }
            if ($pass !== '') { $cmd[] = "--password=$pass"; }
            $cmd[] = $db;

            $process = new Process($cmd);
            $process->setTimeout(300);
            $process->setInput(file_get_contents($file->getPathname()));
            $process->run();

            if (!$process->isSuccessful()) {
                $this->addFlash('danger', 'Échec de la restauration MySQL. Assurez-vous que "mysql" est dans le PATH (ou définissez MYSQL_PATH).');
                return $this->redirectToRoute('admin_backup');
            }

            $this->addFlash('success', 'Base MySQL restaurée avec succès.');
            return $this->redirectToRoute('admin_backup');
        }

        $this->addFlash('danger', 'Driver de base non géré pour la restauration.');
        return $this->redirectToRoute('admin_backup');
    }

    private function resolveSqlitePath(?string $path): string
    {
        if (!$path) {
            // default to var/data.db if not provided
            return $this->projectDir . '/var/data.db';
        }
        if (str_starts_with($path, '/') || preg_match('~^[A-Za-z]:\\\\~', $path)) {
            return $path; // absolute
        }
        // relative → project dir
        return $this->projectDir . '/' . ltrim($path, '/\\');
    }
}
