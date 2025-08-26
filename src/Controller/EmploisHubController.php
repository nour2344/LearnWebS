<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EmploisHubController extends AbstractController
{
    #[Route('/admin/emplois', name: 'emplois_index', methods: ['GET'])]
    public function index(): Response
    {
        // Redirect to the new listing you prefer
        return $this->redirectToRoute('emplois_grille_index');
        // or: return $this->redirectToRoute('emplois_upload_index');
    }
}
