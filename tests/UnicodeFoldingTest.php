<?php

declare(strict_types=1);

namespace CleatSquad\TextNormalizer\Tests;

use CleatSquad\TextNormalizer\NormalizerProfile;
use CleatSquad\TextNormalizer\TextNormalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Diacritics are folded by canonical decomposition, not by a hand-written
 * table. These cases are the ones a table always misses.
 */
final class UnicodeFoldingTest extends TestCase
{
    private TextNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new TextNormalizer();
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function decomposableWords(): array
    {
        return [
            'french' => ['Crème Brûlée', 'creme brulee'],
            'czech caron' => ['Košice', 'kosice'],
            'esperanto circumflex' => ['Ĝangalo', 'gangalo'],
            'transliteration macron and dot' => ['Ṣāliḥ', 'salih'],
            'vietnamese stacked marks' => ['Đà Nẵng', 'da nang'],
            'turkish breve' => ['Çağrı', 'cagri'],
            'scandinavian ring' => ['Ångström', 'angstrom'],
            'polish stroke' => ['Łódź', 'lodz'],
        ];
    }

    #[DataProvider('decomposableWords')]
    public function testDiacriticsAreFolded(string $input, string $expected): void
    {
        self::assertSame($expected, $this->normalizer->normalize($input));
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function undecomposableLetters(): array
    {
        return [
            'ae ligature' => ['Æther', 'aether'],
            'oe ligature' => ['Œuvre', 'oeuvre'],
            'sharp s' => ['Straße', 'strasse'],
            'o slash' => ['Tromsø', 'tromso'],
            'd stroke' => ['Đà', 'da'],
            'thorn' => ['Þór', 'thor'],
        ];
    }

    /** These carry no combining mark, so decomposition cannot reach them. */
    #[DataProvider('undecomposableLetters')]
    public function testLettersWithoutMarksAreMapped(string $input, string $expected): void
    {
        self::assertSame($expected, $this->normalizer->normalize($input));
    }

    public function testPrecomposedAndDecomposedInputAgree(): void
    {
        $precomposed = \Normalizer::normalize('café', \Normalizer::FORM_C);
        $decomposed = \Normalizer::normalize('café', \Normalizer::FORM_D);

        self::assertIsString($precomposed);
        self::assertIsString($decomposed);
        self::assertNotSame($precomposed, $decomposed, 'The fixture must actually differ in bytes.');

        self::assertSame(
            $this->normalizer->normalize($precomposed),
            $this->normalizer->normalize($decomposed)
        );
    }

    public function testAnEmptyProfileFoldsNothing(): void
    {
        $bare = new TextNormalizer(new NormalizerProfile());

        self::assertSame('météo à rabat', $bare->normalize('Météo, à RABAT !'));
    }

    public function testNormalizingIsIdempotent(): void
    {
        $once = $this->normalizer->normalize('Quelle est la MÉTÉO à Rabat ?');

        self::assertSame($once, $this->normalizer->normalize($once));
    }
}
