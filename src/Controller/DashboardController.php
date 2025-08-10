<?php

namespace App\Controller;
use Knp\Component\Pager\PaginatorInterface;
use App\Repository\PaiementRepository;
use App\Repository\RecetteRepository;
use App\Repository\DepenseRepository;



use App\Entity\Depense;
use App\Form\DepenseType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
#[Route('/dashboard')]
class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(
        PaiementRepository $paiementRepository,
        RecetteRepository $recetteRepository,
        DepenseRepository $depenseRepository
    ): Response {
        $today = new \DateTimeImmutable('today');
        $monthStart = new \DateTimeImmutable('first day of this month');
        $yearStart = new \DateTimeImmutable('first day of January');

        // Madakhil (recettes)
        $dailyIncome = $recetteRepository->sumByDate($today);
        $monthlyIncome = $recetteRepository->sumFromDate($monthStart);
        $yearlyIncome = $recetteRepository->sumFromDate($yearStart);
        $totalIncome = $recetteRepository->sumAll();

        // Masarif (dépenses)
        $dailyExpense = $depenseRepository->sumByDate($today);
        $monthlyExpense = $depenseRepository->sumFromDate($monthStart);
        $yearlyExpense = $depenseRepository->sumFromDate($yearStart);
        $totalExpense = $depenseRepository->sumAll();

        // Da5l safi
        $netDaily = $dailyIncome - $dailyExpense;
        $netMonthly = $monthlyIncome - $monthlyExpense;
        $netYearly = $yearlyIncome - $yearlyExpense;
        $netTotal = $totalIncome - $totalExpense;

        return $this->render('dashboard/index.html.twig', [
            'dailyIncome' => $dailyIncome,
            'monthlyIncome' => $monthlyIncome,
            'yearlyIncome' => $yearlyIncome,
            'totalIncome' => $totalIncome,

            'dailyExpense' => $dailyExpense,
            'monthlyExpense' => $monthlyExpense,
            'yearlyExpense' => $yearlyExpense,
            'totalExpense' => $totalExpense,

            'netDaily' => $netDaily,
            'netMonthly' => $netMonthly,
            'netYearly' => $netYearly,
            'netTotal' => $netTotal,
        ]);
    }
}