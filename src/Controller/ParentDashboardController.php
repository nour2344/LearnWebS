<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Attribute\IsGranted;   // <-- this one
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[IsGranted('ROLE_PARENT')]
class ParentDashboardController extends AbstractController
{
    #[Route('/parent', name: 'parent_home')]
    public function index(): Response
    {
        return $this->render('parent/home.html.twig');
    }
}
