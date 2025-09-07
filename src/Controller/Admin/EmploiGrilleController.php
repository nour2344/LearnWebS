<?php
namespace App\Controller\Admin;

use App\Entity\EmploiTemps;
use App\Form\EmploiGrilleType;
use App\Repository\EmploiTempsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/emplois-grille')]
class EmploiGrilleController extends AbstractController
{
    #[Route('/', name: 'emplois_grille_index', methods: ['GET'])]
    public function index(EmploiTempsRepository $repo): Response
    {
        $rows = $repo->findBy(['mode' => 'manual'], ['classe' => 'ASC', 'effectiveFrom' => 'DESC']);
        return $this->render('emplois_grille/index.html.twig', [
            'emplois' => $rows,
        ]);
    }

    // NEW: returns only the <tr> rows for the table (used by AJAX)
    #[Route('/search', name: 'emplois_grille_search', methods: ['GET'])]
    public function search(Request $r, EmploiTempsRepository $repo): Response
    {
        $q = trim((string) $r->query->get('q', ''));
        $rows = $repo->searchManual($q);

        // Return the rendered rows fragment (no layout)
        return $this->render('emplois_grille/_rows.html.twig', [
            'emplois' => $rows,
        ]);
    }

    #[Route('/new', name: 'emplois_grille_new', methods: ['GET','POST'])]
    public function new(Request $r, EntityManagerInterface $em): Response
    {
        $e = (new EmploiTemps())->setMode('manual');
        $form = $this->createForm(EmploiGrilleType::class, $e);
        $form->handleRequest($r);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($e);
            $em->flush();
            $this->addFlash('success', 'Grille créée. Ajoutez les créneaux.');
            return $this->redirectToRoute('emplois_lignes_index', ['emploi' => $e->getId()]);
        }

        return $this->render('emplois_grille/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'emplois_grille_edit', methods: ['GET','POST'])]
    public function edit(EmploiTemps $e, Request $r, EntityManagerInterface $em): Response
    {
        if ($e->getMode() !== 'manual') {
            throw $this->createNotFoundException();
        }
        $form = $this->createForm(EmploiGrilleType::class, $e);
        $form->handleRequest($r);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Grille mise à jour.');
            return $this->redirectToRoute('emplois_grille_index');
        }

        return $this->render('emplois_grille/edit.html.twig', [
            'form' => $form->createView(),
            'emploi' => $e,
        ]);
    }

    #[Route('/{id}', name: 'emplois_grille_delete', methods: ['POST'])]
    public function delete(EmploiTemps $e, Request $r, EntityManagerInterface $em): Response
    {
        if ($e->getMode() !== 'manual') {
            throw $this->createNotFoundException();
        }
        if ($this->isCsrfTokenValid('del_grille_' . $e->getId(), $r->request->get('_token'))) {
            $em->remove($e);
            $em->flush();
            $this->addFlash('success', 'Supprimée.');
        }
        return $this->redirectToRoute('emplois_grille_index');
    }
}
