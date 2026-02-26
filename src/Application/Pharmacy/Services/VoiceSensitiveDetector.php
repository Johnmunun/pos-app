<?php

namespace Src\Application\Pharmacy\Services;

/**
 * Détecte si un texte ne doit pas être lu à voix haute (données sensibles).
 * Utilisé pour désactiver le TTS sur réponses contenant montants élevés, RH, patients, etc.
 */
class VoiceSensitiveDetector
{
    /** Montant au-delà duquel on considère la donnée sensible (ex. salaires, gros CA). */
    private const AMOUNT_THRESHOLD = 100_000;

    /** Mots-clés indiquant des données sensibles (RH, patients, financier détaillé). */
    private const SENSITIVE_KEYWORDS = [
        'salaire', 'salaires', 'rémunération', 'paie', 'bulletin',
        'patient', 'patients', 'dossier médical', 'diagnostic',
        'rh', 'ressources humaines', 'contrat', 'licenciement',
        'données personnelles', 'rgpd', 'cnil',
        'compte bancaire', 'rib', 'iban', 'coordonnées bancaires',
    ];

    public function isSensitive(string $text): bool
    {
        $clean = mb_strtolower($text);
        foreach (self::SENSITIVE_KEYWORDS as $keyword) {
            if (mb_strpos($clean, $keyword) !== false) {
                return true;
            }
        }
        if ($this->containsHighAmounts($text)) {
            return true;
        }
        return false;
    }

    private function containsHighAmounts(string $text): bool
    {
        if (preg_match_all('/\d[\d\s.,]*\s*(?:CDF|XAF|USD|EUR|FCFA|€|\$)/ui', $text, $matches)) {
            foreach ($matches[0] as $match) {
                $num = (float) preg_replace('/[\s,]/', '', preg_replace('/\s*(?:CDF|XAF|USD|EUR|FCFA|€|\$).*$/u', '', $match));
                if ($num >= self::AMOUNT_THRESHOLD) {
                    return true;
                }
            }
        }
        return false;
    }
}
