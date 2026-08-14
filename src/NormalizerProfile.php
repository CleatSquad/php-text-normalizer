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
        public string $name = 'custom',
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
        return new self(
            characterMap: [
                'æ' => 'ae', 'œ' => 'oe', 'ß' => 'ss',
                'ø' => 'o', 'ł' => 'l', 'đ' => 'd', 'ð' => 'd', 'þ' => 'th',
                'ħ' => 'h', 'ı' => 'i', 'ŋ' => 'n', 'ŧ' => 't', 'ƶ' => 'z',
            ],
            foldedMarkPatterns: [
                // Combining Diacritical Marks, and the Latin Extended Additional
                // ones below-dot letters decompose into.
                '/[\x{0300}-\x{036F}\x{1AB0}-\x{1AFF}\x{1DC0}-\x{1DFF}\x{20D0}-\x{20F0}]/u',
            ],
            name: 'latin'
        );
    }

    /**
     * Orthographic equivalences: the same word typed on an Arabic, Persian or
     * Urdu keyboard produces different code points that Unicode considers
     * distinct letters, so no normalization form unifies them. Harakat are not
     * listed — they are combining marks, and NFD strips them.
     *
     * @param bool $searchEquivalences If true (default), folds search conventions like ة -> ه.
     *                                 If false, strict Unicode normalization preserves ة.
     */
    public static function arabic(bool $searchEquivalences = true): self
    {
        $map = [
            // Alif variants, incl. the Alif Wasla decomposition misses.
            'ٱ' => 'ا',
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
        ];

        if ($searchEquivalences) {
            // Ta Marbuta folded to Ha for search index equivalence conventions.
            $map['ة'] = 'ه';
        }

        return new self(
            characterMap: $map,
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
            name: $searchEquivalences ? 'arabic_search' : 'arabic_strict'
        );
    }

    /**
     * Cyrillic folding rules for non-decomposable letters like Yo (ё -> е),
     * Ukrainian/Belarusian letters (і, ї -> i).
     */
    public static function cyrillic(): self
    {
        return new self(
            characterMap: [
                'ё' => 'е', 'Ё' => 'е',
                'і' => 'i', 'І' => 'i',
                'ї' => 'i', 'Ї' => 'i',
                'ў' => 'у', 'Ў' => 'у',
                'ґ' => 'г', 'Ґ' => 'г',
            ],
            foldedMarkPatterns: [
                '/[\x{0300}-\x{036F}]/u',
            ],
            name: 'cyrillic'
        );
    }

    /**
     * Greek folding rules: final sigma (ς -> σ), symbol equivalences.
     * Accent/tonos folding is handled via NFD canonical decomposition.
     */
    public static function greek(): self
    {
        return new self(
            characterMap: [
                'ς' => 'σ',
            ],
            foldedMarkPatterns: [
                '/[\x{0300}-\x{036F}\x{1FE0}-\x{1FEF}]/u',
            ],
            name: 'greek'
        );
    }

    /** Every profile shipped with the package. */
    public static function all(bool $searchEquivalences = true): self
    {
        return self::arabic($searchEquivalences)
            ->merge(self::latin())
            ->merge(self::cyrillic())
            ->merge(self::greek());
    }

    public function merge(self $other): self
    {
        return new self(
            [...$this->characterMap, ...$other->characterMap],
            [...$this->strippedPatterns, ...$other->strippedPatterns],
            [...$this->foldedMarkPatterns, ...$other->foldedMarkPatterns],
            name: $this->name . '_' . $other->name,
        );
    }
}
