<?php
namespace App\Service;

use App\Entity\User;
use App\Entity\ParentProfile;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class SchoolAssistant
{
    public function __construct(
        private SchoolFaqProvider $faq,
        private UrlGeneratorInterface $urlGen,
    ) {}

    /**
     * @return array{0:string,1:string[]} [replyHtml, suggestions]
     */
    public function answer(string $message, ?User $user): array
    {
        $msg = mb_strtolower(trim($message));

        // 1) Intents rapides vers vos routes parent
        if ($this->contains($msg, ['emploi du temps','edt','horaire','planning'])) {
            $childId = $this->guessChildId($user);
            $url = $this->urlGen->generate('parent_emplois_index', $childId ? ['child'=>$childId] : [], UrlGeneratorInterface::ABSOLUTE_URL);
            return ["Voici l’<a href=\"{$url}\" target=\"_blank\">emploi du temps</a>.", $this->sug()];
        }
        if ($this->contains($msg, ['bulletin','notes','resultats','résultats'])) {
            $url = $this->urlGen->generate('parent_bulletins', [], UrlGeneratorInterface::ABSOLUTE_URL);
            return ["Vous pouvez consulter les <a href=\"{$url}\" target=\"_blank\">bulletins ici</a>.", $this->sug()];
        }
        if ($this->contains($msg, ['fiche','paiement','mensualité','mensualites','paiements'])) {
            $childId = $this->guessChildId($user);
            if ($childId) {
                $url = $this->urlGen->generate('parent_fiche', ['id'=>$childId], UrlGeneratorInterface::ABSOLUTE_URL);
                return ["La <a href=\"{$url}\" target=\"_blank\">fiche de votre enfant</a> récapitule les paiements.", $this->sug()];
            }
        }

        // 2) FAQ statique (horaires, contacts, absences, cantine…)
        if ($answer = $this->faq->lookup($msg)) {
            return [$answer, $this->sug()];
        }

        // 3) Fallback
        return [
            "Je n’ai pas trouvé d’information précise. Vous pouvez me demander : <em>horaires</em>, <em>paiement</em>, <em>absence</em>, <em>bulletins</em>, <em>emploi du temps</em>…",
            ['Quels sont les horaires ?', 'Comment déclarer une absence ?', 'Où payer les frais ?']
        ];
    }

    private function contains(string $haystack, array $needles): bool
    {
        foreach ($needles as $n) {
            if (str_contains($haystack, mb_strtolower($n))) return true;
        }
        return false;
    }

    private function guessChildId(?User $user): ?int
    {
        if (!$user || !method_exists($user, 'getParentProfile')) return null;
        /** @var ParentProfile|null $pp */
        $pp = $user->getParentProfile();
        if (!$pp || count($pp->getChildren()) === 0) return null;
        return $pp->getChildren()->first()?->getId();
    }

    /** @return string[] */
    private function sug(): array
    {
        return ['Horaires de l’école', 'Procédure d’absence', 'Contacts & adresse'];
    }
}
