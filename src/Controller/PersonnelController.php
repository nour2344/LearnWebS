<?php

namespace App\Controller;
use Knp\Component\Pager\PaginatorInterface;

use App\Entity\Personnel;
use App\Form\PersonnelType;
use App\Repository\PersonnelRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/personnel')]
class PersonnelController extends AbstractController
{
    #[Route('/', name: 'personnel_index', methods: ['GET'])]
    public function index(
        Request $request,
        PersonnelRepository $repo,
        PaginatorInterface $paginator
    ): Response {
        $q    = $request->query->get('q');     // nom/prénom
        $role = $request->query->get('role');  // enum value string

        $query = $repo->searchQuery($q, $role);

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );

        // For AJAX requests, just return the rows HTML
        if ($request->isXmlHttpRequest()) {
            return $this->render('personnel/_rows.html.twig', [
                'personnels' => $pagination,
            ]);
        }

        return $this->render('personnel/index.html.twig', [
            'personnels' => $pagination,
        ]);
    }


    #[Route('/new', name: 'personnel_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $personnel = new Personnel();
        $form = $this->createForm(PersonnelType::class, $personnel);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($personnel);
            $entityManager->flush();

            return $this->redirectToRoute('personnel_index');
        }

        return $this->render('personnel/new.html.twig', [
            'personnel' => $personnel,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'personnel_show', methods: ['GET'])]
    public function show(Personnel $personnel): Response
    {
        return $this->render('personnel/show.html.twig', [
            'personnel' => $personnel,
        ]);
    }

    #[Route('/{id}/edit', name: 'personnel_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Personnel $personnel, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(PersonnelType::class, $personnel);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('personnel_index');
        }

        return $this->render('personnel/edit.html.twig', [
            'personnel' => $personnel,
            'form' => $form,
        ]);
    }

    #[Route('/personnel/{id}', name: 'personnel_delete', methods: ['POST'])]
public function delete(Request $request, Personnel $personnel, EntityManagerInterface $entityManager): Response
{
    if ($this->isCsrfTokenValid('delete'.$personnel->getId(), $request->request->get('_token'))) {
        $entityManager->remove($personnel);
        $entityManager->flush();
    }

    return $this->redirectToRoute('personnel_index');
}
}
