<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class AdminController extends AbstractController
{
    #[Route('/admin', name: 'app_admin')]
public function index(): Response
{
    // If you still want to protect it:
    $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

    // Always go to your real home/dashboard
    return $this->redirectToRoute('home'); // or 'app_dashboard'
}

}
