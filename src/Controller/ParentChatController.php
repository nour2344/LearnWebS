<?php
namespace App\Controller;

use App\Service\SchoolAssistant;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;   // ✅ keep this
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_PARENT')]
final class ParentChatController extends AbstractController
{
    #[Route('/api/parent/chat', name: 'api_parent_chat', methods: ['POST'])]
    public function chat(Request $request, SchoolAssistant $assistant): JsonResponse
    {
        $data = json_decode($request->getContent() ?? '[]', true) ?: [];
        $message = trim((string)($data['message'] ?? ''));
        if ($message === '') {
            return $this->json(['reply' => "Message vide.", 'suggestions' => []], 400);
        }

        $user = $this->getUser(); // for child links/intents
        [$reply, $suggestions] = $assistant->answer($message, $user);

        return $this->json([
            'reply' => $reply,
            'suggestions' => $suggestions,
        ]);
    }
}
