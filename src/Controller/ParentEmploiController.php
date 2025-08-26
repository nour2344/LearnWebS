<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\EmploiLigneRepository;
use App\Repository\EmploiTempsRepository;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/parent/emplois')]
class ParentEmploiController extends AbstractController
{
    #[Route('', name: 'parent_emplois_index', methods: ['GET'])]
    public function index(
        Request $request,
        EmploiTempsRepository $tRepo,
        EmploiLigneRepository $lRepo
    ): Response {
        /** @var User|null $user */
        $user = $this->getUser();

        // must be a concrete User and must have a ParentProfile
        if (!$user instanceof User || null === $user->getParentProfile()) {
            $this->addFlash('warning', 'Profil parent introuvable.');
            return $this->redirectToRoute('parent_home');
        }

        $children = $user->getParentProfile()->getChildren();
        if (\count($children) === 0) {
            $this->addFlash('info', 'Aucun enfant associé.');
            return $this->redirectToRoute('parent_home');
        }

        // choose child via ?child=ID, else first
        $childId = $request->query->get('child');
        $child = null;
        if ($childId) {
            foreach ($children as $c) {
                if ((string) $c->getId() === (string) $childId) {
                    $child = $c;
                    break;
                }
            }
        }
        if (!$child) {
            $child = $children->first(); // safe: collection not empty (checked above)
        }

        $classe = method_exists($child, 'getClasse') ? $child->getClasse() : null;
        if (!$classe) {
            $this->addFlash('info', "La classe de l'élève n'est pas définie.");
            return $this->redirectToRoute('parent_home');
        }

        // 1) try current (effectiveFrom <= today)
        $emploi   = $tRepo->findLatestForClass($classe);
        $upcoming = false;

        // 2) if none, try upcoming (requires repo to support the 2nd param)
        if (!$emploi) {
            try {
                $emploi   = $tRepo->findLatestForClass($classe, true);
                $upcoming = (bool) $emploi;
            } catch (\ArgumentCountError) {
                // repository not upgraded to support $includeFuture; leave as null
            }
        }

        $lignes = [];
        if ($emploi) {
            $lignes = $lRepo->findBy(
                ['emploi' => $emploi],
                ['day' => 'ASC', 'startAt' => 'ASC']
            );
        }

        $days = [
            'Mon' => 'Lundi',
            'Tue' => 'Mardi',
            'Wed' => 'Mercredi',
            'Thu' => 'Jeudi',
            'Fri' => 'Vendredi',
            'Sat' => 'Samedi',
            'Sun' => 'Dimanche',
        ];

        return $this->render('parent/emplois.html.twig', [
            'child'    => $child,
            'emploi'   => $emploi,
            'lignes'   => $lignes,
            'days'     => $days,
            'upcoming' => $upcoming,
        ]);
    }

    #[Route('/pdf', name: 'parent_emplois_pdf', methods: ['GET'])]
    public function pdf(
        Request $request,
        EmploiTempsRepository $tRepo,
        EmploiLigneRepository $lRepo
    ): Response {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user instanceof User || null === $user->getParentProfile()) {
            throw $this->createAccessDeniedException();
        }

        $children = $user->getParentProfile()->getChildren();
        if (\count($children) === 0) {
            throw $this->createNotFoundException('Aucun enfant associé.');
        }

        // choose child via ?child=ID, else first
        $childId = $request->query->get('child');
        $child = null;
        if ($childId) {
            foreach ($children as $c) {
                if ((string) $c->getId() === (string) $childId) {
                    $child = $c;
                    break;
                }
            }
        }
        if (!$child) {
            $child = $children->first();
        }

        $classe = method_exists($child, 'getClasse') ? $child->getClasse() : null;
        if (!$classe) {
            throw $this->createNotFoundException("La classe de l'élève n'est pas définie.");
        }

        // current first, then upcoming (if repo supports it)
        $emploi = $tRepo->findLatestForClass($classe);
        if (!$emploi) {
            try {
                $emploi = $tRepo->findLatestForClass($classe, true);
            } catch (\ArgumentCountError) {
                // repo not updated; keep null
            }
        }

        if (!$emploi) {
            $this->addFlash('info', "Aucun emploi du temps publié pour la classe {$classe}.");
            return $this->redirectToRoute('parent_emplois_index');
        }

        $lignes = $lRepo->findBy(
            ['emploi' => $emploi],
            ['day' => 'ASC', 'startAt' => 'ASC']
        );

        $days = [
            'Mon' => 'Lundi',
            'Tue' => 'Mardi',
            'Wed' => 'Mercredi',
            'Thu' => 'Jeudi',
            'Fri' => 'Vendredi',
            'Sat' => 'Samedi',
            'Sun' => 'Dimanche',
        ];

        $html = $this->renderView('emplois_lignes/pdf.html.twig', [
            'emploi' => $emploi,
            'lignes' => $lignes,
            'days'   => $days,
        ]);

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->setIsRemoteEnabled(true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = sprintf(
            'emploi_%s_%s.pdf',
            $emploi->getClasse(),
            $emploi->getEffectiveFrom()->format('Ymd')
        );

        return new Response($dompdf->output(), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }
}
