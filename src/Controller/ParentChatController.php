<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\ChatLogger;
use App\Service\SchoolAssistant;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_PARENT')]
final class ParentChatController extends AbstractController
{
    #[Route('/api/parent/chat', name: 'api_parent_chat', methods: ['POST'])]
    public function chat(
        Request $request,
        SchoolAssistant $assistant,
        ChatLogger $logger
    ): JsonResponse {
        // Parse JSON safely
        try {
            $data = json_decode($request->getContent() ?? '[]', true, 512, JSON_THROW_ON_ERROR) ?: [];
        } catch (\Throwable $e) {
            return $this->json(['reply' => 'Requête invalide (JSON).', 'suggestions' => []], 400);
        }

        $message = trim((string)($data['message'] ?? ''));
        if ($message === '') {
            return $this->json(['reply' => 'Message vide.', 'suggestions' => []], 400);
        }

        // Get reply from assistant
        $user = $this->getUser();
        [$reply, $suggestions] = $assistant->answer(message: $message, user: $user);

        // Log conversation
        $sid  = $logger->ensureSessionId($request);
        $meta = [
            'ip' => $request->getClientIp(),
            'ua' => substr((string) $request->headers->get('User-Agent'), 0, 255),
        ];
        $log = $logger->log($sid, $user, $message, $reply, $suggestions, $meta);

        return $this->json([
            'reply'       => $reply,
            'suggestions' => $suggestions,
            'logId'       => $log->getId(),
        ]);
    }

    #[Route('/api/parent/chat/feedback', name: 'api_parent_chat_feedback', methods: ['POST'])]
    public function feedback(Request $request, ChatLogger $logger): JsonResponse
    {
        try {
            $data = json_decode($request->getContent() ?? '[]', true, 512, JSON_THROW_ON_ERROR) ?: [];
        } catch (\Throwable $e) {
            return $this->json(['ok' => false, 'error' => 'Requête invalide (JSON).'], 400);
        }

        $id     = (int)($data['logId'] ?? 0);
        $rating = isset($data['rating']) ? (int) $data['rating'] : null; // 1..5 or null
        $text   = isset($data['feedback']) ? trim((string) $data['feedback']) : null;

        if ($id <= 0) {
            return $this->json(['ok' => false, 'error' => 'Identifiant manquant'], 400);
        }

        $log = $logger->feedback($id, $rating, $text);
        if (!$log) {
            return $this->json(['ok' => false, 'error' => 'Log introuvable'], 404);
        }

        return $this->json(['ok' => true]);
    }
}
