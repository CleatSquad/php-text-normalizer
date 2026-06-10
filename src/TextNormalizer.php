<?php

declare(strict_types=1);

namespace CleatSquad\TextNormalizer;

use Normalizer;

/**
 * Folds text to a comparable form: lowercase, diacritics removed,
 * punctuation collapsed to single spaces.
 */
final readonly class TextNormalizer
{
    private NormalizerProfile $profile;

    public function __construct(?NormalizerProfile $profile = null)
    {
        $this->profile = $profile ?? NormalizerProfile::all();
    }

    /**
     * Diacritics are removed by Unicode canonical decomposition rather than by
     * a handwritten table: NFD reaches every decomposable letter in every
     * script, where a table only ever covers the ones someone remembered.
     * The profile handles what decomposition cannot — letters that carry no
     * mark to strip, and orthographic equivalences Unicode keeps distinct.
     */
    public function normalize(string $text): string
    {
        // Folding has no meaning for input that is not UTF-8: mb_strtolower
        // replaces every malformed byte with '?', which the separator pass
        // below then erases, turning the whole string into ''. Returning the
        // input untouched keeps a broken encoding visible to the caller.
        if (!mb_check_encoding($text, 'UTF-8')) {
            return $text;
        }

        $text = mb_strtolower($text, 'UTF-8');

        foreach ($this->profile->strippedPatterns as $pattern) {
            $text = preg_replace($pattern, '', $text) ?? $text;
        }

        $text = strtr($text, $this->profile->characterMap);

        if ($this->profile->foldedMarkPatterns !== []) {
            // Decompose first, then drop the marks: a diacritic must not
            // survive as the replacement of the character it sat on. Only the
            // marks this profile claims are removed, so a Latin profile leaves
            // Arabic harakat untouched. Recomposing keeps the output in NFC.
            $text = self::decompose($text, Normalizer::FORM_D);

            foreach ($this->profile->foldedMarkPatterns as $pattern) {
                $text = preg_replace($pattern, '', $text) ?? $text;
            }

            $text = self::decompose($text, Normalizer::FORM_C);
        }

        // Anything left that is neither letter, digit nor combining mark is a
        // separator. Marks survive on purpose: one this profile did not claim
        // belongs to its letter, and is not a word boundary.
        $text = preg_replace('/[^\p{L}\p{N}\p{M}]+/u', ' ', $text) ?? $text;

        return trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
    }

    /**
     * Normalization fails on malformed UTF-8, where it returns false rather
     * than throwing. The input is then left as it is: unfoldable, but never
     * silently replaced by an empty string.
     */
    private static function decompose(string $text, int $form): string
    {
        $normalized = Normalizer::normalize($text, $form);

        return is_string($normalized) ? $normalized : $text;
    }

    /** @return list<string> */
    public function tokenize(string $text): array
    {
        $normalized = $this->normalize($text);

        if ($normalized === '') {
            return [];
        }

        return explode(' ', $normalized);
    }
}
