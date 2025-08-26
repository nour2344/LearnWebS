<?php

namespace App\Controller;
use Symfony\Component\Security\Http\Attribute\IsGranted;

use App\Entity\Etudiant;
use App\Form\EtudiantType;
use App\Repository\EtudiantRepository;
use App\Repository\PaiementRepository;
use DateInterval;
use DatePeriod;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Parameter;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;

#[Route('/etudiant')]
class EtudiantController extends AbstractController
{
    #[Route('/', name: 'etudiant_index', methods: ['GET'])]
    public function index(
        Request $request,
        PaginatorInterface $paginator,
        EtudiantRepository $repo
    ): Response {
        // Filters
        $nom       = $request->query->get('nom');
        $prenom    = $request->query->get('prenom');
        $classe    = $request->query->get('classe');

        // Sorting
        $sort      = $request->query->get('sort', 'nom');
        $direction = \strtoupper($request->query->get('direction', 'ASC')) === 'DESC' ? 'DESC' : 'ASC';

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

        // Allow sorting by new optional fields too
        $allowedSort = ['nom', 'prenom', 'classe', 'dateN', 'dateInscription', 'nomPere', 'nomMere', 'numTel2'];
        if (\in_array($sort, $allowedSort, true)) {
            $qb->orderBy("e.$sort", $direction);
        } else {
            $qb->orderBy('e.nom', 'ASC');
        }

        $pagination = $paginator->paginate(
            $qb->getQuery(),
            $request->query->getInt('page', 1),
            10
        );

        // AJAX (JSON) listing like your recompense module
        if ($request->isXmlHttpRequest()) {
            $today      = new DateTimeImmutable('today');
            $monthStart = new DateTimeImmutable('first day of this month 00:00:00');
            $monthEnd   = new DateTimeImmutable('last day of this month 23:59:59');

            $rows = [];
            foreach ($pagination as $e) {
                /** @var Etudiant $e */
                $paidThisMonth = $e->getDernierPaiement() && $e->getDernierPaiement() >= $monthStart;

                $status = [
                    'paidThisMonth' => $paidThisMonth,
                    'overdueDays'   => null,
                    'dueInDays'     => null,
                ];
                if (!$paidThisMonth) {
                    if ($today > $monthEnd) {
                        $status['overdueDays'] = (int) \ceil(($today->getTimestamp() - $monthEnd->getTimestamp()) / 86400);
                    } else {
                        $status['dueInDays'] = (int) \ceil(($monthEnd->getTimestamp() - $today->getTimestamp()) / 86400);
                    }
                }
// inside the $rows[] = [...] block in the AJAX part of index():
$rows[] = [
    'id'              => $e->getId(),
    'nom'             => $e->getNom(),
    'prenom'          => $e->getPrenom(),
    'classe'          => $e->getClasse(),
    'dateN'           => $e->getDateN()?->format('d/m/Y'),
    'dateInscription' => $e->getDateInscription()?->format('d/m/Y'),
    'numTel'          => $e->getNumTel(),
    'numTel2'         => $e->getNumTel2(),
    'nomPere'         => $e->getNomPere(),
    'nomMere'         => $e->getNomMere(),
    'bulletinsVisibles'     => $e->isBulletinsVisibles(), // <—
    'csrf_toggle_bulletins' => $this->container->get('security.csrf.token_manager')
                                 ->getToken('toggle_bulletins_' . $e->getId())->getValue(), // <—
    'csrf_toggle_paiement'  => $this->container->get('security.csrf.token_manager')
                                 ->getToken('toggle_etudiant_' . $e->getId())->getValue(), // <—
    'status' => $status,
];
            }

            return $this->json([
                'rows'     => $rows,
                'page'     => $pagination->getCurrentPageNumber(),
                'pages'    => (int) \ceil($pagination->getTotalItemCount() / $pagination->getItemNumberPerPage()),
                'total'    => $pagination->getTotalItemCount(),
                'per_page' => $pagination->getItemNumberPerPage(),
            ]);
        }

        return $this->render('etudiant/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route('/new', name: 'etudiant_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $etudiant = new Etudiant();
        $form = $this->createForm(EtudiantType::class, $etudiant);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($etudiant);
            $em->flush();

            return $this->redirectToRoute('etudiant_index');
        }

        return $this->render('etudiant/new.html.twig', [
            'etudiant' => $etudiant,
            'form'     => $form,
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
    public function edit(Request $request, Etudiant $etudiant, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(EtudiantType::class, $etudiant);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            return $this->redirectToRoute('etudiant_index');
        }

        return $this->render('etudiant/edit.html.twig', [
            'etudiant' => $etudiant,
            'form'     => $form,
        ]);
    }

    #[Route('/{id}', name: 'etudiant_delete', methods: ['POST'])]
    public function delete(Request $request, Etudiant $etudiant, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $etudiant->getId(), $request->request->get('_token'))) {
            $em->remove($etudiant);
            $em->flush();
        }
        return $this->redirectToRoute('etudiant_index');
    }

    // TOGGLE Paiement
#[Route('/{id}/toggle-paiement', name: 'etudiant_toggle_paiement', methods: ['POST'], requirements: ['id' => '\d+'])]
public function togglePaiement(Request $request, Etudiant $etudiant, EntityManagerInterface $em)
{
    if ($this->isCsrfTokenValid('toggle_etudiant_' . $etudiant->getId(), $request->request->get('_token'))) {
        $monthStart    = new \DateTimeImmutable('first day of this month 00:00:00');
        $paidThisMonth = $etudiant->getDernierPaiement() && $etudiant->getDernierPaiement() >= $monthStart;
        $etudiant->setDernierPaiement($paidThisMonth ? null : new \DateTime());
        $em->flush();
    }

    // If called via AJAX, don’t redirect
    if ($request->isXmlHttpRequest()) {
        return new JsonResponse(['ok' => true]);
    }

    $referer = $request->headers->get('referer') ?? $this->generateUrl('etudiant_index');
    return $this->redirect($referer);
}

// TOGGLE Bulletins
#[Route('/{id}/toggle-bulletins', name: 'etudiant_toggle_bulletins', methods: ['POST'], requirements: ['id' => '\d+'])]
public function toggleBulletins(Request $request, Etudiant $etudiant, EntityManagerInterface $em): Response
{
    // Optional CSRF check if you want:
    // if (!$this->isCsrfTokenValid('toggle_bulletins_' . $etudiant->getId(), $request->request->get('_token'))) {
    //     return new JsonResponse(['ok' => false], 400);
    // }

    $etudiant->setBulletinsVisibles(!$etudiant->isBulletinsVisibles());
    $em->flush();

    if ($request->isXmlHttpRequest()) {
        return new JsonResponse(['ok' => true, 'visible' => $etudiant->isBulletinsVisibles()]);
    }

    $this->addFlash('success', 'Visibilité des bulletins mise à jour !');
    return $this->redirectToRoute('etudiant_index');
}

    #[Route('/{id}/fiche', name: 'etudiant_fiche', methods: ['GET'])]
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
        if ($dp !== null && $dp >= $start && $dp <= $end) {
            $paidMap[$dp->format('Y-m')] = true;
        }

        // 6) Libellés FR des mois (sans intl)
        $monthLabels = [
            '01' => 'Janvier',   '02' => 'Février',  '03' => 'Mars',
            '04' => 'Avril',     '05' => 'Mai',      '06' => 'Juin',
            '07' => 'Juillet',   '08' => 'Août',     '09' => 'Septembre',
            '10' => 'Octobre',   '11' => 'Novembre', '12' => 'Décembre',
        ];

        $periodLabel = \sprintf('Du %s au %s', $start->format('d/m/Y'), $end->format('d/m/Y'));

        return $this->render('etudiant/fiche.html.twig', [
            'etudiant'    => $etudiant,
            'months'      => $months,
            'paidMap'     => $paidMap,
            'monthLabels' => $monthLabels,
            'periodLabel' => $periodLabel,
        ]);
    }

   
    #[Route('/bulk-bulletins', name: 'etudiant_bulk_bulletins', methods: ['POST'])]
public function bulkBulletins(
    Request $request,
    EtudiantRepository $repo,
    EntityManagerInterface $em
): JsonResponse {
    // CSRF
    $token = (string)$request->request->get('_token', '');
    if (!$this->isCsrfTokenValid('bulk_bulletins', $token)) {
        return new JsonResponse(['ok' => false, 'error' => 'csrf'], 400);
    }

    // visible=1 => Afficher, 0 => Masquer
    $visible = $request->request->get('visible', null);
    if ($visible === null || !in_array((string)$visible, ['0','1'], true)) {
        return new JsonResponse(['ok' => false, 'error' => 'param'], 400);
    }
    $visibleBool = $visible === '1';

    // take the same filters as your AJAX search
    $nom    = trim((string)$request->request->get('nom', ''));
    $prenom = trim((string)$request->request->get('prenom', ''));
    $classe = trim((string)$request->request->get('classe', ''));

    $qb = $repo->createQueryBuilder('e');
    if ($nom !== '')    { $qb->andWhere('e.nom LIKE :nom')->setParameter('nom', "%$nom%"); }
    if ($prenom !== '') { $qb->andWhere('e.prenom LIKE :prenom')->setParameter('prenom', "%$prenom%"); }
    if ($classe !== '') { $qb->andWhere('e.classe LIKE :classe')->setParameter('classe', "%$classe%"); }

    $students = $qb->getQuery()->getResult();

    $count = 0;
    foreach ($students as $e) {
        /** @var \App\Entity\Etudiant $e */
        if ($e->isBulletinsVisibles() !== $visibleBool) {
            $e->setBulletinsVisibles($visibleBool);
            $count++;
        }
    }
    if ($count > 0) { $em->flush(); }

    return new JsonResponse(['ok' => true, 'updated' => $count]);
}

#[Route('/api/etudiants/suggest', name: 'etudiant_suggest', methods: ['GET'])]
#[IsGranted('PUBLIC_ACCESS')]

public function suggest(Request $request, EntityManagerInterface $em): JsonResponse
{
    $classe = trim((string) $request->query->get('classe', ''));
    $q      = trim((string) $request->query->get('q', ''));

    if ($classe === '' || mb_strlen($q) < 2) {
        return new JsonResponse([], 200);
    }

    $qb = $em->createQueryBuilder()
        ->select('e.id, e.nom, e.prenom, e.classe')
        ->from(Etudiant::class, 'e')
        ->where('LOWER(e.classe) = LOWER(:classe) OR LOWER(e.classe) LIKE LOWER(:classeLike)')
        ->andWhere('(LOWER(e.nom) LIKE LOWER(:q) OR LOWER(e.prenom) LIKE LOWER(:q))')
        ->setParameter('classe', $classe)
        ->setParameter('classeLike', '%'.$classe.'%')
        ->setParameter('q', $q.'%')
        ->orderBy('e.nom', 'ASC')
        ->setMaxResults(20);

    return new JsonResponse($qb->getQuery()->getArrayResult(), 200);
}
#[Route('/api/etudiants/by-classe', name: 'etudiant_by_classe', methods: ['GET'])]
#[IsGranted('PUBLIC_ACCESS')]

public function apiByClasse(Request $request, EtudiantRepository $repo): JsonResponse
{
    $classe = trim((string) $request->query->get('classe', ''));
    if ($classe === '') {
        return $this->json([]);
    }

    $rows = $repo->createQueryBuilder('e')
        ->select('e.id, e.nom, e.prenom')
        ->where('e.classe = :c')->setParameter('c', $classe)
        ->orderBy('e.nom', 'ASC')
        ->addOrderBy('e.prenom', 'ASC')
        ->getQuery()->getArrayResult();

    return $this->json($rows);
}
}
