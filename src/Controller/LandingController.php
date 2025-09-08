<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class LandingController extends AbstractController
{
    #[Route('/', name: 'app_landing', methods: ['GET'])]
    public function index(): Response
    {
        // Optional: read school name from DB/service and pass it to Twig
        return $this->render('landing/index.html.twig', [
            'school_name' => null, // or string from DB
        ]);
    }

    #[Route('/guide/parents', name: 'parent_portal_guide')]
    public function parentGuide(): Response
    {
        return $this->render('guides/parents.html.twig');
    }

    #[Route('/guide/admin', name: 'admin_portal_guide')]
    public function adminGuide(): Response
    {
        return $this->render('guides/admin.html.twig');
    }

    // placeholders for spaces (replace with your real controllers)
    #[Route('/parent', name: 'parent_portal')]
    public function parentPortal(): Response { return new Response('Parent portal'); }

    #[Route('/admin', name: 'admin_portal')]
    public function adminPortal(): Response { return new Response('Admin portal'); }

    #[Route('/adbox/a-propos', name: 'adbox_about')]
    public function aboutAdbox(): Response { return $this->render('adbox/about.html.twig'); }

    #[Route('/adbox/contact', name: 'adbox_contact')]
    public function contactAdbox(): Response { return $this->render('adbox/contact.html.twig'); }
}
