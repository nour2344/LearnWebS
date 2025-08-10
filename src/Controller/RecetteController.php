<?php

namespace App\Controller;
use Knp\Component\Pager\PaginatorInterface;
use App\Entity\Recette;
use App\Form\RecetteType;
use App\Repository\RecetteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Enum\SourceRecette;


#[Route('/recette')]
class RecetteController extends AbstractController
{
    #[Route('/', name: 'recette_index', methods: ['GET'])]
    public function index(
        Request $request,
        RecetteRepository $repo,
        PaginatorInterface $paginator
    ): Response {
        $source = $request->query->get('source'); // enum backing value or ''

        $query = $repo->searchQuery($source);

        $recettes = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );

        // Build choices for the select (value + label taken from enum)
        $sourceChoices = array_map(
            fn($case) => ['value' => $case->value, 'label' => $case->label()],
            SourceRecette::cases()
        );

        if ($request->isXmlHttpRequest()) {
            return $this->render('recette/_rows.html.twig', [
                'recettes' => $recettes,
            ]);
        }

        return $this->render('recette/index.html.twig', [
            'recettes'       => $recettes,
            'sourceChoices'  => $sourceChoices,
            'currentSource'  => $source,
        ]);
    }

    #[Route('/new', name: 'recette_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $recette = new Recette();
        $form = $this->createForm(RecetteType::class, $recette);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($recette);
            $entityManager->flush();

            return $this->redirectToRoute('recette_index');
        }

        return $this->render('recette/new.html.twig', [
            'recette' => $recette,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'recette_show', methods: ['GET'])]
    public function show(Recette $recette): Response
    {
        return $this->render('recette/show.html.twig', [
            'recette' => $recette,
        ]);
    }

    #[Route('/{id}/edit', name: 'recette_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Recette $recette, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(RecetteType::class, $recette);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('recette_index');
        }

        return $this->render('recette/edit.html.twig', [
            'recette' => $recette,
            'form' => $form,
        ]);
    }

    #[Route('/recette/{id}', name: 'recette_delete', methods: ['POST'])]
public function delete(Request $request, Recette $recette, EntityManagerInterface $entityManager): Response
{
    if ($this->isCsrfTokenValid('delete'.$recette->getId(), $request->request->get('_token'))) {
        $entityManager->remove($recette);
        $entityManager->flush();
    }

    return $this->redirectToRoute('recette_index');
}


#[Route('/{id}/print', name: 'recette_show', methods: ['GET'])]
public function print(Recette $recette): Response
{
    return $this->render('recette/print.html.twig', [
        'recette' => $recette,
    ]);
}


}
