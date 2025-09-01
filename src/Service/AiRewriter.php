<?php
namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class AiRewriter
{
    public function __construct(
        private HttpClientInterface $http,
        private string $provider = 'openai',
        private ?string $openAiApiKey = null,
        private string $model = 'gpt-4o-mini',
    ) {}

    public function isEnabled(): bool
    {
        return $this->provider === 'openai' && !empty($this->openAiApiKey);
    }

    /**
     * Rewrite a draft answer to be short, friendly, and in French.
     * Falls back to the original draft if LLM is disabled or errors.
     */
    public function rewrite(string $draft, string $userMessage, array $context = []): string
    {
        if (!$this->isEnabled()) {
            return $draft;
        }

        try {
            $system = "Tu es un assistant pour des parents d'élèves. " .
                      "Améliore et clarifie la réponse ci-dessous sans inventer d'informations. " .
                      "Réponds en français, 2–5 phrases maximum, et propose des étapes pratiques si utile.";

            $messages = [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => "Question du parent : ".$userMessage],
                ['role' => 'assistant', 'content' => "Réponse de base à réécrire : ".$draft],
            ];

            $resp = $this->http->request('POST', 'https://api.openai.com/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer '.$this->openAiApiKey,
                    'Content-Type'  => 'application/json',
                ],
                'json' => [
                    'model' => $this->model,
                    'temperature' => 0.2,
                    'messages' => $messages,
                ],
                'timeout' => 20,
            ])->toArray(false);

            if (!empty($resp['choices'][0]['message']['content'])) {
                return trim((string)$resp['choices'][0]['message']['content']);
            }
        } catch (\Throwable $e) {
            // swallow errors: fall back to original draft
        }
        return $draft;
    }
}
