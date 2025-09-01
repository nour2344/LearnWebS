<?php
namespace App\Controller;

use App\Entity\Etudiant;
use App\Entity\ParentProfile;
use App\Entity\User;
use App\Service\StudentFicheBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_PARENT')]
final class ParentController extends AbstractController
{
    #[Route('/parent', name: 'parent_home')]
    public function home(): Response
    {
        return $this->render('parent/home.html.twig');
    }

    #[Route('/parent/fiche/{id<\d+>}', name: 'parent_fiche')]
    public function fiche(
        Etudiant $etudiant,
        StudentFicheBuilder $builder
    ): Response {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('Utilisateur non authentifié.');
        }

        /** @var ParentProfile|null $pp */
        $pp = method_exists($user, 'getParentProfile') ? $user->getParentProfile() : null;
        if (!$pp) {
            throw $this->createAccessDeniedException('Aucun profil parent.');
        }

        // The child must belong to this parent
        if (!$pp->getChildren()->contains($etudiant)) {
            throw $this->createAccessDeniedException('Cet élève n’est pas associé à votre compte.');
        }

        $data = $builder->build($etudiant); // months, monthLabels, paidMap, periodLabel

        return $this->render('etudiant/fiche.html.twig', array_merge($data, [
            'etudiant'  => $etudiant,
            'backRoute' => 'parent_home', // Retour button goes back to parent space
        ]));
    }
}
