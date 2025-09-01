<?php
namespace App\Service;

use App\Entity\Etudiant;
use DateInterval;
use DatePeriod;
use DateTimeImmutable;

class StudentFicheBuilder
{
    public function build(Etudiant $etudiant, ?\DateTimeInterface $start = null, ?\DateTimeInterface $end = null): array
    {
        $start = $start ? DateTimeImmutable::createFromInterface($start) : new DateTimeImmutable('first day of September this year');
        $end   = $end   ? DateTimeImmutable::createFromInterface($end)   : new DateTimeImmutable('last day of June next year');

        // Months sequence
        $period = new DatePeriod($start->modify('first day of this month'), new DateInterval('P1M'), $end->modify('first day of next month'));
        $months = iterator_to_array($period);

        // Example month labels (01..12 -> Janvier..Décembre)
        $monthLabels = [
            '01'=>'Janvier','02'=>'Février','03'=>'Mars','04'=>'Avril','05'=>'Mai','06'=>'Juin',
            '07'=>'Juillet','08'=>'Août','09'=>'Septembre','10'=>'Octobre','11'=>'Novembre','12'=>'Décembre'
        ];

        // TODO: Replace with your real query that fetches paid months for this student
        // Expected format: keys "Y-m" (e.g., "2025-10")
        $paidMap = $this->loadPaidMonthsMap($etudiant, $start, $end);

        $periodLabel = sprintf('%s – %s', $start->format('d/m/Y'), $end->format('d/m/Y'));

        return compact('months','monthLabels','paidMap','periodLabel');
    }

    private function loadPaidMonthsMap(Etudiant $etudiant, \DateTimeInterface $start, \DateTimeInterface $end): array
    {
        // Implement your repository call here.
        // For now return an empty array to mark all as "Non payé".
        return [];
    }
}
