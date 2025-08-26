<?php
namespace App\Controller\Admin;

use App\Entity\EmploiTemps;
use App\Form\EmploiUploadType;
use App\Repository\EmploiTempsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/emplois-upload')]
class EmploiUploadController extends AbstractController
{
    #[Route('/', name:'emplois_upload_index', methods:['GET'])]
    public function index(EmploiTempsRepository $repo): Response {
        $rows = $repo->findBy(['mode'=>'upload'], ['classe'=>'ASC','effectiveFrom'=>'DESC']);
        return $this->render('emplois_upload/index.html.twig', ['emplois'=>$rows]);
    }

    #[Route('/new', name:'emplois_upload_new', methods:['GET','POST'])]
    public function new(Request $r, EntityManagerInterface $em): Response {
        $e = (new EmploiTemps())->setMode('upload');
        $form = $this->createForm(EmploiUploadType::class, $e);
        $form->handleRequest($r);
        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('upload')->getData();
            $name = bin2hex(random_bytes(8)).'.'.$file->guessExtension();
            $dir  = $this->getParameter('kernel.project_dir').'/public/uploads/timetables';
            try { $file->move($dir, $name); } catch (FileException $ex) {
                $this->addFlash('danger','Upload: '.$ex->getMessage()); return $this->redirectToRoute('emplois_upload_new');
            }
            $e->setFileName($name);
            $em->persist($e); $em->flush();
            $this->addFlash('success','Emploi (fichier) créé.');
            return $this->redirectToRoute('emplois_upload_index');
        }
        return $this->render('emplois_upload/new.html.twig', ['form'=>$form->createView()]);
    }

    #[Route('/{id}/edit', name:'emplois_upload_edit', methods:['GET','POST'])]
    public function edit(EmploiTemps $e, Request $r, EntityManagerInterface $em): Response {
        if ($e->getMode()!=='upload') throw $this->createNotFoundException();
        $old = $e->getFileName();
        $form = $this->createForm(EmploiUploadType::class, $e, ['upload_required'=>false]);
        $form->handleRequest($r);
        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('upload')->getData();
            if ($file) {
                $dir  = $this->getParameter('kernel.project_dir').'/public/uploads/timetables';
                $name = bin2hex(random_bytes(8)).'.'.$file->guessExtension();
                try { $file->move($dir, $name); if ($old && is_file($dir.'/'.$old)) @unlink($dir.'/'.$old); }
                catch (\Throwable $ex) { $this->addFlash('danger','Upload: '.$ex->getMessage()); return $this->redirectToRoute('emplois_upload_edit',['id'=>$e->getId()]); }
                $e->setFileName($name);
            }
            $em->flush(); $this->addFlash('success','Mis à jour.'); return $this->redirectToRoute('emplois_upload_index');
        }
        return $this->render('emplois_upload/edit.html.twig', ['form'=>$form->createView(),'emploi'=>$e]);
    }

    #[Route('/{id}', name:'emplois_upload_delete', methods:['POST'])]
    public function delete(EmploiTemps $e, Request $r, EntityManagerInterface $em): Response {
        if ($e->getMode()!=='upload') throw $this->createNotFoundException();
        if ($this->isCsrfTokenValid('del_upload_'.$e->getId(), $r->request->get('_token'))) {
            $dir = $this->getParameter('kernel.project_dir').'/public/uploads/timetables';
            if ($e->getFileName() && is_file($dir.'/'.$e->getFileName())) @unlink($dir.'/'.$e->getFileName());
            $em->remove($e); $em->flush(); $this->addFlash('success','Supprimé.');
        }
        return $this->redirectToRoute('emplois_upload_index');
    }
}
