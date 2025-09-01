<?php
namespace App\Entity;

use App\Repository\ChatLogRepository;
use App\Entity\User;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ChatLogRepository::class)]
#[ORM\Table(name: 'chat_log')]
#[ORM\Index(columns: ['session_id'])]
#[ORM\Index(columns: ['created_at'])]
class ChatLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    // a logical thread for a parent’s session
    #[ORM\Column(name: 'session_id', type: 'string', length: 64)]
    private string $sessionId;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL', nullable: true)]
    private ?User $user = null;

    // free text asked by the parent
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $message = null;

    // assistant’s answer
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $reply = null;

    // suggestions shown as follow-ups
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $suggestions = null;

    // optional feedback (note/texte)
    #[ORM\Column(type: 'smallint', nullable: true)]
    private ?int $rating = null;         // 1..5 or null

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $feedback = null;

    // misc (ip, userAgent…)
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $meta = null;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    // --- getters/setters omitted for brevity; generate them with your IDE ---
    // Quick ones:
    public function getId(): ?int { return $this->id; }
    public function getSessionId(): string { return $this->sessionId; }
    public function setSessionId(string $v): self { $this->sessionId = $v; return $this; }
    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $u): self { $this->user = $u; return $this; }
    public function getMessage(): ?string { return $this->message; }
    public function setMessage(?string $m): self { $this->message = $m; return $this; }
    public function getReply(): ?string { return $this->reply; }
    public function setReply(?string $r): self { $this->reply = $r; return $this; }
    public function getSuggestions(): ?array { return $this->suggestions; }
    public function setSuggestions(?array $s): self { $this->suggestions = $s; return $this; }
    public function getRating(): ?int { return $this->rating; }
    public function setRating(?int $r): self { $this->rating = $r; return $this; }
    public function getFeedback(): ?string { return $this->feedback; }
    public function setFeedback(?string $f): self { $this->feedback = $f; return $this; }
    public function getMeta(): ?array { return $this->meta; }
    public function setMeta(?array $m): self { $this->meta = $m; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}
