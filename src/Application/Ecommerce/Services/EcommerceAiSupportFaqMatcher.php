<?php

namespace Src\Application\Ecommerce\Services;

/**
 * Correspondance simple question client ↔ FAQ marchand.
 */
final class EcommerceAiSupportFaqMatcher
{
    public const MAX_ITEMS = 10;

    /**
     * @return list<array{question: string, answer: string}>
     */
    public static function normalizeList(mixed $input): array
    {
        if (!is_array($input)) {
            return [];
        }
        $out = [];
        foreach (array_slice($input, 0, self::MAX_ITEMS) as $row) {
            if (!is_array($row)) {
                continue;
            }
            $question = trim((string) ($row['question'] ?? ''));
            $answer = trim((string) ($row['answer'] ?? ''));
            if ($question === '' || $answer === '') {
                continue;
            }
            $out[] = [
                'question' => mb_substr($question, 0, 200),
                'answer' => mb_substr($answer, 0, 800),
            ];
        }

        return $out;
    }

    /**
     * @param list<array{question: string, answer: string}> $faqs
     */
    public function matchAnswer(string $userMessage, array $faqs): ?string
    {
        $message = mb_strtolower(trim($userMessage));
        if ($message === '' || $faqs === []) {
            return null;
        }

        $bestAnswer = null;
        $bestScore = 0.0;

        foreach ($faqs as $faq) {
            $question = mb_strtolower(trim((string) ($faq['question'] ?? '')));
            $answer = trim((string) ($faq['answer'] ?? ''));
            if ($question === '' || $answer === '') {
                continue;
            }

            if (str_contains($message, $question) || str_contains($question, $message)) {
                return $answer;
            }

            $score = $this->tokenOverlapScore($message, $question);
            similar_text($message, $question, $similarPercent);
            $score = max($score, (float) $similarPercent);

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestAnswer = $answer;
            }
        }

        return $bestScore >= 42.0 ? $bestAnswer : null;
    }

    private function tokenOverlapScore(string $a, string $b): float
    {
        $tokensA = $this->significantTokens($a);
        $tokensB = $this->significantTokens($b);
        if ($tokensA === [] || $tokensB === []) {
            return 0.0;
        }
        $common = count(array_intersect($tokensA, $tokensB));
        $denom = max(count($tokensA), count($tokensB));

        return ($common / $denom) * 100;
    }

    /** @return list<string> */
    private function significantTokens(string $text): array
    {
        $parts = preg_split('/\s+/u', mb_strtolower($text)) ?: [];
        $stop = ['le', 'la', 'les', 'un', 'une', 'des', 'de', 'du', 'et', 'ou', 'à', 'a', 'en', 'pour', 'sur', 'est', 'ce', 'cette', 'mon', 'ma', 'mes', 'je', 'vous', 'nous', 'quel', 'quelle', 'comment'];

        return array_values(array_filter($parts, fn ($t) => mb_strlen($t) >= 3 && !in_array($t, $stop, true)));
    }
}
