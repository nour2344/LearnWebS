<?php

namespace App\Controller;
use Knp\Component\Pager\PaginatorInterface;

use App\Entity\Paiement;
use App\Form\PaiementType;
use App\Repository\PaiementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;

#[Route('/paiement')]
class PaiementController extends AbstractController
{
    #[Route('/', name: 'paiement_index', methods: ['GET'])]
    public function index(Request $request, PaiementRepository $repo, PaginatorInterface $paginator): Response
    {
        // search by student's name (nom/prenom)
        $q = $request->query->get('q');

        // build filtered query
        $query = $repo->searchQuery($q, null, null, null, null, null);

        // paginate
        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );

        // ⬇️ AJAX -> return only table rows HTML
        if ($request->isXmlHttpRequest()) {
            return $this->render('paiement/_rows.html.twig', [
                'paiements' => $pagination,
            ]);
        }

        // normal full page
        return $this->render('paiement/index.html.twig', [
            'paiements' => $pagination,
        ]);
    }


    #[Route('/new', name: 'paiement_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $paiement = new Paiement();
        $form = $this->createForm(PaiementType::class, $paiement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($paiement);
            $entityManager->flush();

            return $this->redirectToRoute('paiement_index');
        }

        return $this->render('paiement/new.html.twig', [
            'paiement' => $paiement,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'paiement_show', methods: ['GET'])]
    public function show(Paiement $paiement): Response
    {
        return $this->render('paiement/show.html.twig', [
            'paiement' => $paiement,
        ]);
    }

    #[Route('/{id}/edit', name: 'paiement_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Paiement $paiement, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(PaiementType::class, $paiement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('paiement_index');
        }

        return $this->render('paiement/edit.html.twig', [
            'paiement' => $paiement,
            'form' => $form,
        ]);
    }

   #[Route('/paiement/{id}', name: 'paiement_delete', methods: ['POST'])]
public function delete(Request $request, Paiement $paiement, EntityManagerInterface $entityManager): Response
{
    if ($this->isCsrfTokenValid('delete'.$paiement->getId(), $request->request->get('_token'))) {
        $entityManager->remove($paiement);
        $entityManager->flush();
    }

    return $this->redirectToRoute('paiement_index');
}
}
