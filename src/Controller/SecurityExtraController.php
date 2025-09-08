<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class SecurityExtraController extends AbstractController
{
    #[Route('/login/parent-id', name: 'parent_id_login', methods: ['POST'])]
    public function parentIdSink(): Response
    {
        // Authenticator handles it. If we reached here, just bounce back.
        return $this->redirectToRoute('app_login', ['show_parent' => 1]);
    }
}
