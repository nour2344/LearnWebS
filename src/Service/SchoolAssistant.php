<?php
declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Entity\ParentProfile;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Lightweight assistant that answers common parent questions.
 * Optionally rewrites answers with an LLM via AiRewriter (when configured).
 */
final class SchoolAssistant
{
    public function __construct(
        private SchoolFaqProvider $faq,
        private UrlGeneratorInterface $urlGen,
        private ?AiRewriter $rewriter = null, // optional, autowired if present
    ) {}

    /**
     * @return array{0:string,1:string[]} [replyHtml, suggestions]
     */
    public function answer(string $message, ?User $user = null): array
    {
        $msg = mb_strtolower(trim($message));
        $reply = null;
        $suggestions = [];

        // ---- 1) Intents rapides vers vos routes parent ---------------------
        if ($this->contains($msg, ['emploi du temps', 'edt', 'horaire', 'planning'])) {
            $childId = $this->guessChildId($user);
            $url = $this->urlGen->generate(
                'parent_emplois_index',
                $childId ? ['child' => $childId] : [],
                UrlGeneratorInterface::ABSOLUTE_URL
            );
            $reply = 'Voici l’<a href="' . $url . '" target="_blank" rel="noopener">emploi du temps</a>.';
            $suggestions = $this->sug();
        } elseif ($this->contains($msg, ['bulletin', 'notes', 'resultats', 'résultats'])) {
            $url = $this->urlGen->generate('parent_bulletins', [], UrlGeneratorInterface::ABSOLUTE_URL);
            $reply = 'Vous pouvez consulter les <a href="' . $url . '" target="_blank" rel="noopener">bulletins ici</a>.';
            $suggestions = $this->sug();
        } elseif ($this->contains($msg, ['fiche', 'paiement', 'mensualité', 'mensualites', 'paiements'])) {
            $childId = $this->guessChildId($user);
            if ($childId) {
                $url = $this->urlGen->generate(
                    'parent_fiche',
                    ['id' => $childId],
                    UrlGeneratorInterface::ABSOLUTE_URL
                );
                $reply = 'La <a href="' . $url . '" target="_blank" rel="noopener">fiche de votre enfant</a> récapitule les paiements.';
                $suggestions = $this->sug();
            }
        }

        // ---- 2) FAQ statique (horaires, contacts, absences, cantine…) ------
        if ($reply === null) {
            if ($answer = $this->faq->lookup($msg)) {
                $reply = $answer;
                $suggestions = $this->sug();
            }
        }

        // ---- 3) Fallback poli ------------------------------------------------
        if ($reply === null) {
            $reply = "Je n’ai pas trouvé d’information précise. Vous pouvez me demander : "
                . "<em>horaires</em>, <em>paiement</em>, <em>absence</em>, "
                . "<em>bulletins</em>, <em>emploi du temps</em>…";
            $suggestions = ['Quels sont les horaires ?', 'Comment déclarer une absence ?', 'Où payer les frais ?'];
        }

        // ---- 4) (Optionnel) Réécriture LLM pour plus de clarté -------------
        if ($this->rewriter && $this->rewriter->isEnabled()) {
            try {
                $reply = $this->rewriter->rewrite($reply, $message, [
                    'userId' => $user?->getId(),
                ]);
            } catch (\Throwable $e) {
                // en cas d’erreur LLM, on garde la réponse originale
            }
        }

        return [$reply, $suggestions];
    }

    private function contains(string $haystack, array $needles): bool
    {
        foreach ($needles as $n) {
            if (str_contains($haystack, mb_strtolower($n))) {
                return true;
            }
        }
        return false;
    }

    private function guessChildId(?User $user): ?int
    {
        if (!$user || !method_exists($user, 'getParentProfile')) {
            return null;
        }
        /** @var ParentProfile|null $pp */
        $pp = $user->getParentProfile();
        if (!$pp || count($pp->getChildren()) === 0) {
            return null;
        }
        return $pp->getChildren()->first()?->getId();
    }

    /** @return string[] */
    private function sug(): array
    {
        return ['Horaires de l’école', 'Procédure d’absence', 'Contacts & adresse'];
    }
}
