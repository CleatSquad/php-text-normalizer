<?php

declare(strict_types=1);

namespace CleatSquad\TextNormalizer;

/**
 * The script-specific folding rules a normalizer applies on top of Unicode.
 * Injected rather than hardcoded, so adding a script needs no fork.
 */
final readonly class NormalizerProfile
{
    /**
     * @param array<string, string> $characterMap folded character => replacement
     * @param list<string> $strippedPatterns PCRE patterns removed before folding
     * @param list<string> $foldedMarkPatterns PCRE patterns of combining marks
     *        removed after canonical decomposition. Scoped per script, so a
     *        Latin-only profile leaves Arabic harakat where they are.
     */
    public function __construct(
        public array $characterMap = [],
        public array $strippedPatterns = [],
        public array $foldedMarkPatterns = [],
    ) {
    }

    /**
     * Only the Latin letters Unicode decomposition cannot reach. An accented
     * letter decomposes into letter + combining mark and is handled by NFD;
     * these are distinct letters carrying no mark, so nothing decomposes and
     * a table is the only way.
     */
    public static function latin(): self
    {
        return new self([
            'æ' => 'ae', 'œ' => 'oe', 'ß' => 'ss',
            'ø' => 'o', 'ł' => 'l', 'đ' => 'd', 'ð' => 'd', 'þ' => 'th',
            'ħ' => 'h', 'ı' => 'i', 'ŋ' => 'n', 'ŧ' => 't', 'ƶ' => 'z',
        ], foldedMarkPatterns: [
            // Combining Diacritical Marks, and the Latin Extended Additional
            // ones below-dot letters decompose into.
            '/[\x{0300}-\x{036F}\x{1AB0}-\x{1AFF}\x{1DC0}-\x{1DFF}\x{20D0}-\x{20F0}]/u',
        ]);
    }

    /**
     * Orthographic equivalences: the same word typed on an Arabic, Persian or
     * Urdu keyboard produces different code points that Unicode considers
     * distinct letters, so no normalization form unifies them. Harakat are not
     * listed — they are combining marks, and NFD strips them.
     */
    public static function arabic(): self
    {
        return new self(
            characterMap: [
                // Alif variants, incl. the Alif Wasla decomposition misses.
                'ٱ' => 'ا',
                // Ta Marbuta folded to Ha, as search indexes conventionally do.
                'ة' => 'ه',
                // Alef Maksura and Farsi/Urdu Yeh folded to Arabic Yeh.
                'ى' => 'ي', 'ی' => 'ي', 'ې' => 'ي', 'ۍ' => 'ي',
                // Keheh (Persian/Urdu Kaf) folded to Arabic Kaf.
                'ک' => 'ك', 'ګ' => 'ك',
                // Heh Goal / Heh Doachashmee folded to Heh.
                'ہ' => 'ه', 'ھ' => 'ه',
                // Arabic-Indic and Extended Arabic-Indic digits.
                '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
                '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
                '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
                '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            ],
            strippedPatterns: [
                // Tatweel/Kashida: decorative elongation, never lexical.
                '/\x{0640}/u',
                // Zero-width joiner and non-joiner: invisible, and Persian
                // sprinkles them inside single words.
                '/[\x{200C}\x{200D}]/u',
            ],
            foldedMarkPatterns: [
                // Harakat/tashkeel, plus the hamza and maddah marks that
                // decomposition peels off Alif and Yeh variants.
                '/[\x{064B}-\x{0655}\x{0670}]/u',
            ],
        );
    }

    /** Every profile shipped with the package. */
    public static function all(): self
    {
        return self::arabic()->merge(self::latin());
    }

    public function merge(self $other): self
    {
        return new self(
            [...$this->characterMap, ...$other->characterMap],
            [...$this->strippedPatterns, ...$other->strippedPatterns],
            [...$this->foldedMarkPatterns, ...$other->foldedMarkPatterns],
        );
    }
}
