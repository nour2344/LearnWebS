<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class RootController extends AbstractController
{
    #[Route('/', name: 'root', methods: ['GET'])]
    public function root(): Response
    {
        return $this->redirectToRoute('app_landing'); // your existing /landing route
    }
}
