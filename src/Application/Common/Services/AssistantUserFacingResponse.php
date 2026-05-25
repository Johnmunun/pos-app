<?php

namespace Src\Application\Common\Services;

/**
 * Nettoie et formate les réponses assistant visibles par l'utilisateur (jamais de chemins URL).
 */
final class AssistantUserFacingResponse
{
    /**
     * @param  array{answer?: string|null, navigation?: array|null}  $result
     * @return array{answer?: string|null, navigation?: array|null}
     */
    public static function finalize(array $result): array
    {
        if (isset($result['answer']) && is_string($result['answer'])) {
            $result['answer'] = self::sanitizeText($result['answer']);
        }
        if (! empty($result['navigation']) && is_array($result['navigation'])) {
            $result['navigation'] = self::enrichNavigation($result['navigation']);
        }

        return $result;
    }

    public static function sanitizeText(string $text): string
    {
        $text = trim($text);
        if ($text === '') {
            return $text;
        }

        // Parenthèses contenant un chemin interne : "Rapports (/pharmacy/reports)"
        $text = preg_replace('/\s*\(\s*\/[\w\-\/]+\s*\)/u', '', $text) ?? $text;
        // Chemins internes isolés
        $text = preg_replace(
            '/\s*\/(?:pharmacy|commerce|hardware|finance|settings|admin|ecommerce|global-commerce)[\w\-\/]*/u',
            '',
            $text
        ) ?? $text;
        // Liens markdown vers chemins internes
        $text = preg_replace('/\[[^\]]+\]\(\/[^)]+\)/u', '', $text) ?? $text;
        // JSON navigation accidentellement affiché en texte
        if (preg_match('/^\s*\{\s*"type"\s*:\s*"navigation"/u', $text)) {
            return 'Utilisez le bouton proposé pour accéder à la page demandée.';
        }

        $text = preg_replace("/[ \t]+\n/u", "\n", $text) ?? $text;
        $text = preg_replace("/\n{3,}/u", "\n\n", $text) ?? $text;

        return trim($text);
    }

    /**
     * @param  array{type?: string, label?: string, route?: string, method?: string, message?: string}  $navigation
     * @return array{type: string, label: string, route: string, method: string, message: string}
     */
    public static function enrichNavigation(array $navigation): array
    {
        $label = trim((string) ($navigation['label'] ?? 'Ouvrir la page'));

        return [
            'type' => $navigation['type'] ?? 'navigation',
            'label' => $label,
            'route' => $navigation['route'] ?? '',
            'method' => $navigation['method'] ?? 'GET',
            'message' => trim((string) ($navigation['message'] ?? '')) !== ''
                ? trim((string) $navigation['message'])
                : self::navigationGuidanceMessage($label),
        ];
    }

    public static function navigationGuidanceMessage(string $label): string
    {
        $label = trim($label) !== '' ? $label : 'cette section';

        return sprintf(
            '« %s » est accessible depuis le menu latéral de l\'application. Cliquez sur le bouton ci-dessous pour y accéder directement.',
            $label
        );
    }

    /**
     * Liste des modules accessibles sans chemins URL.
     *
     * @param  array<int, array{name?: string, label?: string}>  $nav
     */
    public static function formatAccessibleModulesList(array $nav, int $limit = 8, string $moduleHint = ''): string
    {
        if ($nav === []) {
            return 'Je ne trouve pas de section correspondante dans votre accès actuel.';
        }

        $lines = [];
        foreach (array_slice($nav, 0, $limit) as $item) {
            $name = trim((string) ($item['label'] ?? $item['name'] ?? ''));
            if ($name !== '') {
                $lines[] = '• '.$name;
            }
        }

        $prefix = $moduleHint !== ''
            ? "Voici les sections {$moduleHint} auxquelles vous avez accès :\n\n"
            : "Voici les sections auxquelles vous avez accès :\n\n";

        return $prefix
            .implode("\n", $lines)
            ."\n\nPosez une question précise, par exemple : « Où sont les rapports ? » ou « Où gérer les produits ? », et je vous guiderai vers la bonne page.";
    }
}
