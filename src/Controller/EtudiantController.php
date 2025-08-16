<?php

namespace App\Controller;
use Knp\Component\Pager\PaginatorInterface;

use App\Entity\Etudiant;
use App\Form\EtudiantType;
use App\Repository\EtudiantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Repository\PaiementRepository;
use DateInterval;
use DatePeriod;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Query\Parameter;



#[Route('/etudiant')]
class EtudiantController extends AbstractController
{
    #[Route('/', name: 'etudiant_index', methods: ['GET'])]
    public function index(
        Request $request,
        PaginatorInterface $paginator,
        EtudiantRepository $repo
    ): Response {
        $nom = $request->query->get('nom');      // search by nom
        $prenom = $request->query->get('prenom');   // search by prenom
        $classe = $request->query->get('classe');   // search by classe
        $sort = $request->query->get('sort', 'nom');
        $direction = strtoupper($request->query->get('direction', 'ASC')) === 'DESC' ? 'DESC' : 'ASC';

        // Build QB like your recompense code
        $qb = $repo->createQueryBuilder('e');

        if ($nom) {
            $qb->andWhere('e.nom LIKE :nom')->setParameter('nom', "%$nom%");
        }
        if ($prenom) {
            $qb->andWhere('e.prenom LIKE :prenom')->setParameter('prenom', "%$prenom%");
        }
        if ($classe) {
            $qb->andWhere('e.classe LIKE :classe')->setParameter('classe', "%$classe%");
        }

        $allowedSort = ['nom', 'prenom', 'classe', 'dateN', 'dateInscription'];
        if (in_array($sort, $allowedSort, true)) {
            $qb->orderBy("e.$sort", $direction);
        } else {
            $qb->orderBy('e.nom', 'ASC');
        }

        $pagination = $paginator->paginate(
            $qb->getQuery(),
            $request->query->getInt('page', 1),
            10
        );

        // If AJAX: return JSON rows (like your recompense example)
        if ($request->isXmlHttpRequest()) {
            $today = new \DateTimeImmutable('today');
            $monthStart = new \DateTimeImmutable('first day of this month 00:00:00');
            $monthEnd = new \DateTimeImmutable('last day of this month 23:59:59');

            $rows = [];
            foreach ($pagination as $e) {
                $paidThisMonth = $e->getDernierPaiement() && $e->getDernierPaiement() >= $monthStart;

                $status = [
                    'paidThisMonth' => $paidThisMonth,
                    'overdueDays' => null,
                    'dueInDays' => null,
                ];
                if (!$paidThisMonth) {
                    if ($today > $monthEnd) {
                        $status['overdueDays'] = (int) ceil(($today->getTimestamp() - $monthEnd->getTimestamp()) / 86400);
                    } else {
                        $status['dueInDays'] = (int) ceil(($monthEnd->getTimestamp() - $today->getTimestamp()) / 86400);
                    }
                }

                $rows[] = [
                    'id' => $e->getId(),
                    'nom' => $e->getNom(),
                    'prenom' => $e->getPrenom(),
                    'classe' => $e->getClasse(),
                    'dateN' => $e->getDateN()?->format('d/m/Y'),
                    'numTel' => $e->getNumTel(),
                    'dateInscription' => $e->getDateInscription()?->format('d/m/Y'),
                    'status' => $status,
                ];
            }

            return $this->json([
                'rows' => $rows,
                'page' => $pagination->getCurrentPageNumber(),
                'pages' => ceil($pagination->getTotalItemCount() / $pagination->getItemNumberPerPage()),
                'total' => $pagination->getTotalItemCount(),
                'per_page' => $pagination->getItemNumberPerPage(),
            ]);
        }

        // Normal (non-AJAX) render
        return $this->render('etudiant/index.html.twig', [
            'pagination' => $pagination,
            // (keep your banners if you want; omitted here for brevity)
        ]);
    }
    #[Route('/new', name: 'etudiant_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $etudiant = new Etudiant();
        $form = $this->createForm(EtudiantType::class, $etudiant);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($etudiant);
            $entityManager->flush();

            return $this->redirectToRoute('etudiant_index');
        }

        return $this->render('etudiant/new.html.twig', [
            'etudiant' => $etudiant,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'etudiant_show', methods: ['GET'])]
    public function show(Etudiant $etudiant): Response
    {
        return $this->render('etudiant/show.html.twig', [
            'etudiant' => $etudiant,
        ]);
    }

    #[Route('/{id}/edit', name: 'etudiant_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Etudiant $etudiant, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(EtudiantType::class, $etudiant);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('etudiant_index');
        }

        return $this->render('etudiant/edit.html.twig', [
            'etudiant' => $etudiant,
            'form' => $form,
        ]);
    }

    #[Route('/etudiant/{id}', name: 'etudiant_delete', methods: ['POST'])]
    public function delete(Request $request, Etudiant $etudiant, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $etudiant->getId(), $request->request->get('_token'))) {
            $entityManager->remove($etudiant);
            $entityManager->flush();
        }

        return $this->redirectToRoute('etudiant_index');
    }

    #[Route('/{id}/toggle-paiement', name: 'etudiant_toggle_paiement', methods: ['POST'])]
    #[IsGranted('PUBLIC_ACCESS')]
    public function togglePaiement(
        Request $request,
        Etudiant $etudiant,
        EntityManagerInterface $em
    ): RedirectResponse {
        if ($this->isCsrfTokenValid('toggle_etudiant_' . $etudiant->getId(), $request->request->get('_token'))) {
            $monthStart = new \DateTimeImmutable('first day of this month 00:00:00');
            $paidThisMonth = $etudiant->getDernierPaiement() && $etudiant->getDernierPaiement() >= $monthStart;

            if ($paidThisMonth) {
                // un-toggle = retirer le paiement du mois (on annule)
                $etudiant->setDernierPaiement(null);
            } else {
                // toggle = payé maintenant
                $etudiant->setDernierPaiement(new \DateTime());
            }
            $em->flush();
        }

        $referer = $request->headers->get('referer') ?? $this->generateUrl('etudiant_index');
        return $this->redirect($referer);
    }

    #[Route('/etudiant/{id}/fiche', name: 'etudiant_fiche', methods: ['GET'])]
    public function fiche(Etudiant $etudiant, PaiementRepository $paiementRepo): Response
    {
        // 1) Début = 1er jour du mois d’inscription (00:00)
        $ins   = $etudiant->getDateInscription() ?? new \DateTime();
        $start = new DateTimeImmutable($ins->format('Y-m-01 00:00:00'));

        // 2) Fin = dernier jour du mois courant (23:59:59)
        $end   = new DateTimeImmutable('last day of this month 23:59:59');

        // 3) Liste des mois (inclusifs)
        $months = [];
        $period = new DatePeriod(
            $start,
            new DateInterval('P1M'),
            (new DateTimeImmutable($end->format('Y-m-01')))->add(new DateInterval('P1M'))
        );
        foreach ($period as $d) {
            $months[] = $d; // premier jour de chaque mois
        }

        // 4) Paiements enregistrés dans la période
        $pays = $paiementRepo->createQueryBuilder('p')
    ->andWhere('p.etudiant = :e')
    ->andWhere('p.dateP BETWEEN :a AND :b')
    ->setParameters(new ArrayCollection([
        new Parameter('e', $etudiant),
        new Parameter('a', $start),
        new Parameter('b', $end),
    ]))
    ->getQuery()
    ->getResult();


        // 5) Index des mois payés : "Y-m"
        $paidMap = [];
        foreach ($pays as $p) {
            $d = $p->getDateP();
            if ($d) {
                $paidMap[$d->format('Y-m')] = true;
            }
        }

        // ➕ Prendre aussi en compte dernierPaiement (toggle) comme payé
        $dp = $etudiant->getDernierPaiement();
        if ($dp !== null) {
            if ($dp >= $start && $dp <= $end) {   // <-- correction : utiliser $start / $end
                $paidMap[$dp->format('Y-m')] = true;
            }
        }

        // 6) Libellés FR des mois (sans intl)
        $monthLabels = [
            '01' => 'Janvier',   '02' => 'Février',  '03' => 'Mars',
            '04' => 'Avril',     '05' => 'Mai',      '06' => 'Juin',
            '07' => 'Juillet',   '08' => 'Août',     '09' => 'Septembre',
            '10' => 'Octobre',   '11' => 'Novembre', '12' => 'Décembre',
        ];

        $periodLabel = sprintf('Du %s au %s', $start->format('d/m/Y'), $end->format('d/m/Y'));

        return $this->render('etudiant/fiche.html.twig', [
            'etudiant'    => $etudiant,
            'months'      => $months,
            'paidMap'     => $paidMap,
            'monthLabels' => $monthLabels,
            'periodLabel' => $periodLabel,
        ]);
    }

    #[Route('/etudiant/{id}/toggle-bulletins', name: 'etudiant_toggle_bulletins')]
public function toggleBulletins(Etudiant $etudiant, EntityManagerInterface $em): Response
{
    $etudiant->setBulletinsVisibles(!$etudiant->isBulletinsVisibles());
    $em->flush();

    $this->addFlash('success', 'Visibilité des bulletins mise à jour !');

    return $this->redirectToRoute('etudiant_index');
}

}