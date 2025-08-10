<?php

namespace App\Twig;

use App\Repository\SalaireRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class LatePaymentsExtension extends AbstractExtension
{
    public function __construct(private SalaireRepository $repo) {}

    public function getFunctions(): array
    {
        // use it in Twig as: late_payments()
        return [new TwigFunction('late_payments', [$this, 'buildLatePayments'])];
    }

    /**
     * Returns an array of small DTOs for the bell.
     * Each item: ['displayName' => string, 'mois' => string|null, 'dateP' => ?\DateTimeInterface, 'daysLate' => ?int]
     */
    public function buildLatePayments(): array
    {
        $soon   = $this->repo->findDueWithinDays(7); // unpaid, within next 7 days
        $over   = $this->repo->findOverdue();        // unpaid, past due

        // $soon and $over can be entities or arrays; normalize to a flat array for Twig.
        $normalize = function ($rows, string $type) {
            $out = [];
            foreach ($rows as $r) {
                // If you returned entity Salaire:
                if (is_object($r) && method_exists($r, 'getPersonnel')) {
                    $pers = $r->getPersonnel();
                    $display = $pers ? trim(($pers->getNomP() ?? '').' '.($pers->getPrenomP() ?? '')) : '—';
                    $out[] = [
                        'type'        => $type,
                        'displayName' => $display,
                        'mois'        => method_exists($r, 'getMois') ? $r->getMois() : null,
                        'dateP'       => method_exists($r, 'getDateP') ? $r->getDateP() : null,
                        'daysLate'    => property_exists($r, 'daysLate') ? $r->daysLate : (method_exists($r, 'getDaysLate') ? $r->getDaysLate() : null),
                    ];
                }
                // If your repo returns arrays:
                elseif (is_array($r)) {
                    $display = $r['displayName'] ??
                               ($r['personnel'] instanceof \App\Entity\Personnel
                                  ? trim(($r['personnel']->getNomP() ?? '').' '.($r['personnel']->getPrenomP() ?? ''))
                                  : (is_string($r['personnel'] ?? null) ? $r['personnel'] : '—'));
                    $out[] = [
                        'type'        => $type,
                        'displayName' => $display,
                        'mois'        => $r['mois']   ?? null,
                        'dateP'       => $r['dateP']  ?? null,
                        'daysLate'    => $r['daysLate'] ?? null,
                    ];
                }
            }
            return $out;
        };

        return array_merge(
            $normalize($soon, 'soon'),
            $normalize($over, 'overdue')
        );
    }
}
