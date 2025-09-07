<?php
// src/Controller/EtudiantSuggestController.php

namespace App\Controller;

use App\Entity\Etudiant;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

final class EtudiantSuggestController extends AbstractController
{
    /**
     * Public JSON endpoint used by the login page autocomplete.
     * NOTE: Path is under /etudiant/api/... so it does NOT collide with routes like /etudiant/{id}
     * and it matches the PUBLIC_ACCESS rule you added in security.yaml.
     */
    #[Route('/etudiant/api/etudiants/suggest', name: 'etudiant_suggest', methods: ['GET'])]
    public function suggest(Request $req, EntityManagerInterface $em): JsonResponse
    {
        $classe = trim((string) $req->query->get('classe', ''));
        $q      = trim((string) $req->query->get('q', ''));

        if ($classe === '' || mb_strlen($q) < 2) {
            return $this->json([]);
        }

        $qb = $em->getRepository(Etudiant::class)->createQueryBuilder('e');
        $qb->select('e.id AS id', 'e.nom AS nom', 'e.prenom AS prenom')
            ->where('e.classe = :classe')
            ->andWhere(
                $qb->expr()->orX(
                    'LOWER(CONCAT(e.nom, \' \', e.prenom)) LIKE :kw',
                    'LOWER(CONCAT(e.prenom, \' \', e.nom)) LIKE :kw'
                )
            )
            ->setParameter('classe', $classe)
            ->setParameter('kw', '%'.mb_strtolower($q).'%')
            ->orderBy('e.nom', 'ASC')
            ->setMaxResults(15);

        return $this->json($qb->getQuery()->getArrayResult());
    }

    /**
     * Optional: list all students for a class (also public JSON).
     */
    #[Route('/etudiant/api/etudiants/by-classe', name: 'etudiant_by_classe', methods: ['GET'])]
    public function byClasse(Request $req, EntityManagerInterface $em): JsonResponse
    {
        $classe = trim((string) $req->query->get('classe', ''));
        if ($classe === '') {
            return $this->json([]);
        }

        $rows = $em->getRepository(Etudiant::class)
            ->createQueryBuilder('e')
            ->select('e.id AS id', 'e.nom AS nom', 'e.prenom AS prenom')
            ->where('e.classe = :classe')
            ->setParameter('classe', $classe)
            ->orderBy('e.nom', 'ASC')
            ->getQuery()
            ->getArrayResult();

        return $this->json($rows);
    }
}
