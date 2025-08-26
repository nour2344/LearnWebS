<?php
namespace App\Controller\Admin;

use App\Entity\EmploiLigne;
use App\Entity\EmploiTemps;
use App\Form\EmploiLigneType;
use App\Repository\EmploiLigneRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Dompdf\Dompdf;
use Dompdf\Options;

#[Route('/admin/emplois-grille/{emploi}/lignes')]
class EmploiLigneController extends AbstractController
{
    #[Route('/', name:'emplois_lignes_index', methods:['GET'])]
    public function index(EmploiTemps $emploi, EmploiLigneRepository $repo): Response {
        $rows = $repo->findBy(['emploi'=>$emploi], ['day'=>'ASC','startAt'=>'ASC']);
        return $this->render('emplois_lignes/index.html.twig', ['emploi'=>$emploi,'lignes'=>$rows]);
    }

#[Route('/new', name: 'emplois_lignes_new', methods: ['GET','POST'])]
public function new(EmploiTemps $emploi, Request $r, EntityManagerInterface $em): Response
{
    $ligne = (new EmploiLigne())->setEmploi($emploi);

    // Preselect & lock day from query (?day=Mon)
    $dayParam  = (string) $r->query->get('day');
    $daysLabel = [
        'Mon' => 'Lundi', 'Tue' => 'Mardi', 'Wed' => 'Mercredi',
        'Thu' => 'Jeudi', 'Fri' => 'Vendredi', 'Sat' => 'Samedi', 'Sun' => 'Dimanche',
    ];
    $lockedDay = array_key_exists($dayParam, $daysLabel);

    if ($lockedDay) {
        $ligne->setDay($dayParam);
    }

    $form = $this->createForm(EmploiLigneType::class, $ligne);
    $form->handleRequest($r);

    if ($form->isSubmitted() && $form->isValid()) {
        // Enforce locked day server-side too (prevents tampering)
        if ($lockedDay) {
            $ligne->setDay($dayParam);
        }

        $em->persist($ligne);
        $em->flush();

        $this->addFlash('success', 'Créneau ajouté.');
        return $this->redirectToRoute('emplois_lignes_index', ['emploi' => $emploi->getId()]);
    }

    return $this->render('emplois_lignes/new.html.twig', [
        'form'      => $form->createView(),
        'emploi'    => $emploi,
        'lockedDay' => $lockedDay,
        'dayCode'   => $dayParam,
        'dayLabel'  => $lockedDay ? $daysLabel[$dayParam] : null,
    ]);
}


    #[Route('/{id}/edit', name: 'emplois_lignes_edit', methods: ['GET','POST'])]
public function edit(EmploiTemps $emploi, EmploiLigne $ligne, Request $r, EntityManagerInterface $em): Response
{
    if ($ligne->getEmploi()->getId() !== $emploi->getId()) {
        throw $this->createNotFoundException();
    }

    // Keep original day to lock it
    $originalDay = $ligne->getDay();

    $form = $this->createForm(EmploiLigneType::class, $ligne);
    $form->handleRequest($r);

    if ($form->isSubmitted() && $form->isValid()) {
        // Server-side lock: always restore the original day
        $ligne->setDay($originalDay);

        $em->flush();
        $this->addFlash('success', 'Créneau mis à jour.');
        return $this->redirectToRoute('emplois_lignes_index', ['emploi' => $emploi->getId()]);
    }

    $days = [
        'Mon'=>'Lundi','Tue'=>'Mardi','Wed'=>'Mercredi','Thu'=>'Jeudi',
        'Fri'=>'Vendredi','Sat'=>'Samedi','Sun'=>'Dimanche'
    ];

    return $this->render('emplois_lignes/edit.html.twig', [
        'form'       => $form->createView(),
        'emploi'     => $emploi,
        'lockedDay'  => true,
        'dayCode'    => $originalDay,
        'dayLabel'   => $days[$originalDay] ?? $originalDay,
    ]);
}


    #[Route('/{id}', name:'emplois_lignes_delete', methods:['POST'])]
    public function delete(EmploiTemps $emploi, EmploiLigne $ligne, Request $r, EntityManagerInterface $em): Response {
        if ($ligne->getEmploi()->getId() !== $emploi->getId()) throw $this->createNotFoundException();
        if ($this->isCsrfTokenValid('del_ligne_'.$ligne->getId(), $r->request->get('_token'))) { $em->remove($ligne); $em->flush(); }
        return $this->redirectToRoute('emplois_lignes_index',['emploi'=>$emploi->getId()]);
    }

    #[Route('/pdf', name: 'emplois_pdf', methods: ['GET'])]
    public function pdf(EmploiTemps $emploi, EmploiLigneRepository $repo): Response
    {
        // 1) If this timetable was uploaded, just redirect to the stored file
        if ($emploi->getMode() === 'upload' && $emploi->getFileName()) {
            // file is under /public/uploads/timetables
            return $this->redirect('/uploads/timetables/'.$emploi->getFileName());
        }

        // 2) Manual mode -> build a PDF
        $lignes = $repo->findBy(['emploi' => $emploi], ['day' => 'ASC', 'startAt' => 'ASC']);

        $html = $this->renderView('emplois_lignes/pdf.html.twig', [
            'emploi' => $emploi,
            'lignes' => $lignes,
            'days' => [
                'Mon' => 'Lundi', 'Tue' => 'Mardi', 'Wed' => 'Mercredi', 'Thu' => 'Jeudi',
                'Fri' => 'Vendredi', 'Sat' => 'Samedi', 'Sun' => 'Dimanche',
            ],
        ]);

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');   // Unicode friendly
        $options->setIsRemoteEnabled(true);            // allow images/css if needed

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = sprintf('emploi_%s_%s.pdf',
            preg_replace('/\W+/', '_', $emploi->getClasse() ?? 'classe'),
            $emploi->getEffectiveFrom()?->format('Ymd') ?? 'date'
        );

        return new Response(
            $dompdf->output(),
            200,
            [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ]
        );
    }

}
