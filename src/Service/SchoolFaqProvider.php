<?php
namespace App\Service;

use Symfony\Component\Yaml\Yaml;

final class SchoolFaqProvider
{
    /** @var array<int,array{q:string, a:string, kw:string[]}> */
    private array $items = [];

    public function __construct(string $faqPath = __DIR__ . '/../../config/school_faq.yaml')
    {
        if (is_file($faqPath)) {
            $data = Yaml::parseFile($faqPath);
            foreach (($data['faq'] ?? []) as $row) {
                $this->items[] = [
                    'q' => (string)($row['q'] ?? ''),
                    'a' => (string)($row['a'] ?? ''),
                    'kw'=> array_map('strval', $row['kw'] ?? []),
                ];
            }
        }
    }

    public function lookup(string $message): ?string
    {
        $msg = mb_strtolower($message);
        // 1) match by keyword
        foreach ($this->items as $it) {
            foreach ($it['kw'] as $k) {
                if (str_contains($msg, mb_strtolower($k))) return $it['a'];
            }
        }
        // 2) very light similarity (optional)
        $best = null; $bestScore = 0;
        foreach ($this->items as $it) {
            similar_text($msg, mb_strtolower($it['q']), $score);
            if ($score > $bestScore) { $bestScore = $score; $best = $it['a']; }
        }
        return $bestScore >= 40 ? $best : null; // threshold
    }
}
