<?php

namespace App\Controller;

use App\Entity\Bulletin;
use App\Entity\Etudiant;
use App\Form\BulletinType;
use App\Repository\BulletinRepository;
use App\Repository\EtudiantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class BulletinController extends AbstractController
{
    #[Route('/etudiant/{id}/bulletin', name: 'bulletin_by_etudiant', requirements: ['id' => '\d+'])]
    public function index(int $id, EtudiantRepository $etudiants, BulletinRepository $bulletinsRepo): Response
    {
        $etudiant = $etudiants->find($id);
        if (!$etudiant) {
            throw $this->createNotFoundException("Étudiant #$id introuvable.");
        }

        $bulletins = $bulletinsRepo->findBy(['etudiant' => $etudiant]);

        return $this->render('bulletin/index.html.twig', [
            'etudiant'  => $etudiant,
            'bulletins' => $bulletins,
        ]);
    }

    #[Route('/bulletin/etudiant/{id}/ajouter', name: 'bulletin_ajouter', requirements: ['id' => '\d+'])]
    public function ajouter(Request $request, int $id, EtudiantRepository $etudiants, EntityManagerInterface $em): Response
    {
        $etudiant = $etudiants->find($id);
        if (!$etudiant) {
            throw $this->createNotFoundException("Étudiant #$id introuvable.");
        }

        $bulletin = new Bulletin();
        $bulletin->setEtudiant($etudiant);

        $form = $this->createForm(BulletinType::class, $bulletin);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($bulletin);
            $em->flush();
            return $this->redirectToRoute('bulletin_by_etudiant', ['id' => $etudiant->getId()]);
        }

        return $this->render('bulletin/ajouter.html.twig', [
            'etudiant' => $etudiant,
            'form'     => $form->createView(),
        ]);
    }

    #[Route('/bulletin/etudiant/{id}/print', name: 'bulletin_imprimer', requirements: ['id' => '\d+'])]
    public function imprimer(int $id, BulletinRepository $bulletinRepo, EtudiantRepository $etudiantRepo): Response
    {
        $etudiant = $etudiantRepo->find($id);
        if (!$etudiant) {
            throw $this->createNotFoundException("Étudiant #$id introuvable.");
        }

        // IMPORTANT: filtrer par l’entité (association), pas par id brut
        $bulletins = $bulletinRepo->findBy(['etudiant' => $etudiant]);

        return $this->render('bulletin/print.html.twig', [
            'etudiant'  => $etudiant,
            'bulletins' => $bulletins,
        ]);
    }

    #[Route('/bulletin/{id}/edit', name: 'bulletin_edit', requirements: ['id' => '\d+'])]
    public function edit(Request $request, Bulletin $bulletin, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(BulletinType::class, $bulletin);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $etudiantId = $bulletin->getEtudiant()?->getId();
            return $this->redirectToRoute('bulletin_by_etudiant', ['id' => $etudiantId]);
        }

        return $this->render('bulletin/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    public function show(Bulletin $bulletin): Response
{
    $etudiant = $bulletin->getEtudiant();

    if (!$etudiant->isBulletinsVisibles()) {
        throw $this->createAccessDeniedException('Bulletins non disponibles pour le moment.');
    }

    return $this->render('bulletin/show.html.twig', [
        'bulletin' => $bulletin,
    ]);
}

}
