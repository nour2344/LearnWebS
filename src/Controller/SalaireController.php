<?php

namespace App\Controller;
use Knp\Component\Pager\PaginatorInterface;
use App\Entity\Salaire;
use Symfony\Component\HttpFoundation\RedirectResponse;
use App\Form\SalaireType;
use App\Repository\SalaireRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/salaire')]
class SalaireController extends AbstractController
{
#[Route('/', name: 'salaire_index', methods: ['GET'])]
public function index(
    Request $request,
    SalaireRepository $repo,
    PaginatorInterface $paginator
): Response {
    $mois      = $request->query->get('mois');
    $personnel = $request->query->get('personnel');
    $statut    = $request->query->get('statut'); // "paye" | "non" | ""

    // Search query
    $query = $repo->searchQuery(
        mois: $mois,
        personnel: $personnel,
        statut: $statut
    );

    $salaires = $paginator->paginate(
        $query,
        $request->query->getInt('page', 1),
        10
    );

    // Notifications
    $dueSoon  = $repo->findDueWithinDays(7); // unpaid, due in next 7 days
    $overdue  = $repo->findOverdue();        // unpaid, past due

    // Combine & format notifications
   $latePayments = array_merge($dueSoon, $overdue);

foreach ($latePayments as $p) {
    $personnel = $p->getPersonnel();

    if ($personnel) {
        $nom    = method_exists($personnel, 'getNomP') ? $personnel->getNomP() : '';
        $prenom = method_exists($personnel, 'getPrenomP') ? $personnel->getPrenomP() : '';
        $p->displayName = trim($nom . ' ' . $prenom);
    } else {
        $p->displayName = '—';
    }
}


    if ($request->isXmlHttpRequest()) {
        return $this->render('salaire/_rows.html.twig', [
            'salaires' => $salaires,
        ]);
    }

    return $this->render('salaire/index.html.twig', [
        'salaires'     => $salaires,
        'latePayments' => $latePayments,
    ]);
}

    #[Route('/new', name: 'salaire_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $salaire = new Salaire();
        $form = $this->createForm(SalaireType::class, $salaire);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($salaire);
            $entityManager->flush();

            return $this->redirectToRoute('salaire_index');
        }

        return $this->render('salaire/new.html.twig', [
            'salaire' => $salaire,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'salaire_show', methods: ['GET'])]
    public function show(Salaire $salaire): Response
    {
        return $this->render('salaire/show.html.twig', [
            'salaire' => $salaire,
        ]);
    }

    #[Route('/{id}/edit', name: 'salaire_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Salaire $salaire, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(SalaireType::class, $salaire);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('salaire_index');
        }

        return $this->render('salaire/edit.html.twig', [
            'salaire' => $salaire,
            'form' => $form,
        ]);
    }

    #[Route('/salaire/{id}', name: 'salaire_delete', methods: ['POST'])]
public function delete(Request $request, Salaire $salaire, EntityManagerInterface $entityManager): Response
{
    if ($this->isCsrfTokenValid('delete'.$salaire->getId(), $request->request->get('_token'))) {
        $entityManager->remove($salaire);
        $entityManager->flush();
    }

    return $this->redirectToRoute('salaire_index');
}

#[Route('/{id}/toggle', name: 'salaire_toggle', methods: ['POST'])]
#[IsGranted('PUBLIC_ACCESS')]   // <- allow without login
public function toggle(
    Request $request,
    Salaire $salaire,
    EntityManagerInterface $em
): RedirectResponse {
    // $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY'); // remove this line

    if ($this->isCsrfTokenValid('toggle'.$salaire->getId(), $request->request->get('_token'))) {
        $salaire->setEstPaye(!$salaire->isEstPaye());
        $em->flush();
        $this->addFlash('success', $salaire->isEstPaye() ? 'Salaire marqué payé.' : 'Salaire marqué non payé.');
    } else {
        $this->addFlash('danger', 'Token CSRF invalide.');
    }

    return $this->redirect($request->headers->get('referer') ?? $this->generateUrl('salaire_index'));
}

}
