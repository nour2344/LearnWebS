<?php

namespace App\Controller;
use Knp\Component\Pager\PaginatorInterface;
use App\Enum\CategorieDepense;

use App\Entity\Depense;
use App\Form\DepenseType;
use App\Repository\DepenseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/depense')]
class DepenseController extends AbstractController
{
    #[Route('/', name: 'depense_index', methods: ['GET'])]
    public function index(
        Request $request,
        DepenseRepository $repo,
        PaginatorInterface $paginator
    ): Response {
        $categorie = $request->query->get('categorie'); // enum backing value or ''

        $query = $repo->searchQuery($categorie);

        $depenses = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );

        // For the select options
        $categorieChoices = array_map(
            fn($case) => ['value' => $case->value, 'label' => $case->label()],
            CategorieDepense::cases()
        );

        if ($request->isXmlHttpRequest()) {
            return $this->render('depense/_rows.html.twig', [
                'depenses' => $depenses,
            ]);
        }

        return $this->render('depense/index.html.twig', [
            'depenses'         => $depenses,
            'categorieChoices' => $categorieChoices,
            'currentCategorie' => $categorie,
        ]);
    }

    #[Route('/new', name: 'depense_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $depense = new Depense();
        $form = $this->createForm(DepenseType::class, $depense);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($depense);
            $em->flush();

            return $this->redirectToRoute('depense_index');
        }

        return $this->render('depense/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'depense_show', methods: ['GET'])]
    public function show(Depense $depense): Response
    {
        return $this->render('depense/show.html.twig', [
            'depense' => $depense,
        ]);
    }

    #[Route('/{id}/edit', name: 'depense_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Depense $depense, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(DepenseType::class, $depense);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            return $this->redirectToRoute('depense_index');
        }

        return $this->render('depense/edit.html.twig', [
            'form' => $form,
            'depense' => $depense,
        ]);
    }

    #[Route('/depense/{id}', name: 'depense_delete', methods: ['POST'])]
public function delete(Request $request, Depense $depense, EntityManagerInterface $entityManager): Response
{
    if ($this->isCsrfTokenValid('delete'.$depense->getId(), $request->request->get('_token'))) {
        $entityManager->remove($depense);
        $entityManager->flush();
    }

    return $this->redirectToRoute('depense_index');
}

}
