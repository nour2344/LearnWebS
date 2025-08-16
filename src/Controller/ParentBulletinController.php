<?php
namespace App\Controller;

use App\Entity\Etudiant;
use App\Entity\Bulletin;
use App\Repository\BulletinRepository;
use App\Repository\ParentProfileRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\SecurityBundle\Security;
use Dompdf\Dompdf;
use Dompdf\Options;

#[IsGranted('ROLE_PARENT')]
#[Route('/parent')]
class ParentBulletinController extends AbstractController
{
    #[Route('/bulletins', name: 'parent_bulletins', methods: ['GET'])]
    public function list(ParentProfileRepository $parents, Security $security): Response
    {
        $profile  = $parents->findOneBy(['user' => $security->getUser()]);
        $children = $profile ? $profile->getChildren() : [];

        return $this->render('parent/bulletins.html.twig', [
            'children' => $children,
        ]);
    }

    // ⬇️ THIS is the route the link should hit
    #[Route(
        '/bulletins/etudiant/{id}',
        name: 'parent_child_bulletins',
        requirements: ['id' => '\d+'],
        methods: ['GET']
    )]
    public function childBulletins(
        Etudiant $etudiant,
        BulletinRepository $bulletins,
        ParentProfileRepository $parents,
        Security $security
    ): Response {
        $profile = $parents->findOneBy(['user' => $security->getUser()]);
        if (!$profile || !$profile->getChildren()->contains($etudiant)) {
            throw $this->createAccessDeniedException('Cet élève ne vous appartient pas.');
        }

        $items = $bulletins->findBy(['etudiant' => $etudiant], ['id' => 'DESC']);

        return $this->render('parent/child_bulletins.html.twig', [
            'etudiant'  => $etudiant,
            'bulletins' => $items,
            'visible'   => true, // ou votre logique de visibilité
        ]);
    }

    #[Route('/bulletins/{id}', name: 'parent_bulletin_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Bulletin $bulletin, ParentProfileRepository $parents, Security $security): Response
    {
        $profile = $parents->findOneBy(['user' => $security->getUser()]);
        if (!$profile || !$profile->getChildren()->contains($bulletin->getEtudiant())) {
            throw $this->createAccessDeniedException('Ce bulletin ne vous appartient pas.');
        }

        return $this->render('parent/bulletin_show.html.twig', [
            'bulletin' => $bulletin,
        ]);
    }

    #[Route('/parent/bulletins/etudiant/{id}/pdf', name: 'parent_bulletins_pdf')]
    public function generatePdf(Etudiant $etudiant, BulletinRepository $bulletinRepository): Response
    {
        $bulletins = $bulletinRepository->findBy(['etudiant' => $etudiant]);

        // Config Dompdf
        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $dompdf = new Dompdf($options);

        // Générer le HTML à partir d’un Twig
        $html = $this->renderView('parent/bulletins_pdf.html.twig', [
            'etudiant' => $etudiant,
            'bulletins' => $bulletins,
        ]);

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // Retourner le PDF en téléchargement
        return new Response(
            $dompdf->output(),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="bulletins_'.$etudiant->getId().'.pdf"',
            ]
        );
    }
}
