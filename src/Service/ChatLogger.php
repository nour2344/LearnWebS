<?php
namespace App\Service;

use App\Entity\ChatLog;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

class ChatLogger
{
    public function __construct(private EntityManagerInterface $em) {}

    /** ensure a persistent session id for the chat */
    public function ensureSessionId(Request $request): string
    {
        $session = $request->getSession();
        $sid = $session->get('chat_session_id');
        if (!$sid) {
            $sid = bin2hex(random_bytes(8));
            $session->set('chat_session_id', $sid);
        }
        return $sid;
    }

    public function log(string $sessionId, ?User $user, string $message, string $reply, array $suggestions = [], array $meta = []): ChatLog
    {
        $log = (new ChatLog())
            ->setSessionId($sessionId)
            ->setUser($user)
            ->setMessage($message)
            ->setReply($reply)
            ->setSuggestions($suggestions)
            ->setMeta($meta ?: null);

        $this->em->persist($log);
        $this->em->flush();

        return $log;
    }

    public function feedback(int $logId, ?int $rating, ?string $feedback): ?ChatLog
    {
        /** @var ChatLog|null $log */
        $log = $this->em->getRepository(ChatLog::class)->find($logId);
        if (!$log) { return null; }
        $log->setRating($rating)->setFeedback($feedback);
        $this->em->flush();
        return $log;
    }
}
