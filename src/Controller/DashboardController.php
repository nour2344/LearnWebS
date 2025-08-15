<?php

namespace App\Controller;

use App\Repository\DepenseRepository;
use App\Repository\EtudiantRepository;
use App\Repository\PaiementRepository;
use App\Repository\RecetteRepository;
use App\Repository\SalaireRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard', methods: ['GET'])]
public function index(
    EtudiantRepository $etudiantRepo,
    PaiementRepository $paiementRepo,
    SalaireRepository $salaireRepo,
    DepenseRepository $depenseRepo,
    RecetteRepository $recetteRepo
): Response {
    // Periods
    $startMonth = (new \DateTime('first day of this month midnight'));
    $endMonth   = (new \DateTime('first day of next month midnight'));

    // Students
    $totalStudents = $etudiantRepo->countAll();
    $studentsPaid  = $paiementRepo->countDistinctStudentsPaid();
    $percentPaid   = $totalStudents > 0 ? round(($studentsPaid / $totalStudents) * 100, 1) : 0.0;

    // Money aggregates
    $paymentsTotal = $paiementRepo->sumAll();
    $paymentsMonth = $paiementRepo->sumBetween($startMonth, $endMonth);

    $salariesTotal = $salaireRepo->sumAll();
    $salariesMonth = $salaireRepo->sumBetween($startMonth, $endMonth);
    $personnelPaid = $salaireRepo->countDistinctPersonnelPaid();

    $expensesTotal = $depenseRepo->sumAll();
    $expensesMonth = $depenseRepo->sumBetween($startMonth, $endMonth);
    $topExpenses   = $depenseRepo->sumByCategoryTop(5);

    $revenueTotal  = $recetteRepo->sumAll();
    $revenueMonth  = $recetteRepo->sumBetween($startMonth, $endMonth);

    $netTotal = $revenueTotal - $expensesTotal;
    $netMonth = $revenueMonth - $expensesMonth;

    // ---- Monthly series (last 6 months, including current) ----
    $monthsLabels  = [];
    $revenueSeries = [];
    $expenseSeries = [];
    $netSeries     = [];

    $cursor = (new \DateTime('first day of this month'))->modify('-5 months'); // 6 points
    for ($i = 0; $i < 6; $i++) {
        $start = (clone $cursor)->setTime(0, 0, 0);
        $end   = (clone $cursor)->modify('first day of next month')->setTime(0, 0, 0);

        $monthsLabels[]  = $cursor->format('M Y');        // e.g., "Aug 2025"
        $rev             = (float) $recetteRepo->sumBetween($start, $end);
        $exp             = (float) $depenseRepo->sumBetween($start, $end);
        $revenueSeries[] = $rev;
        $expenseSeries[] = $exp;
        $netSeries[]     = $rev - $exp;

        $cursor->modify('+1 month');
    }

    return $this->render('dashboard/index.html.twig', [
        'period' => [
            'startMonth' => $startMonth,
            'endMonth'   => $endMonth,
        ],
        'students' => [
            'total'       => $totalStudents,
            'paid'        => $studentsPaid,
            'percentPaid' => $percentPaid,
        ],
        'payments' => [
            'total' => $paymentsTotal,
            'month' => $paymentsMonth,
        ],
        'salaries' => [
            'total'         => $salariesTotal,
            'month'         => $salariesMonth,
            'personnelPaid' => $personnelPaid,
        ],
        'expenses' => [
            'total'   => $expensesTotal,
            'month'   => $expensesMonth,
            'topCats' => $topExpenses, // reuse the same array
        ],
        'revenue' => [
            'total' => $revenueTotal,
            'month' => $revenueMonth,
        ],
        'net' => [
            'total' => $netTotal,
            'month' => $netMonth,
        ],
        'chart' => [
            'labels'   => $monthsLabels,
            'revenue'  => $revenueSeries,
            'expenses' => $expenseSeries,
            'net'      => $netSeries,
            'topCats'  => $topExpenses,
        ],
    ]);
}


}
