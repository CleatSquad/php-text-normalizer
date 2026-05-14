<?php

declare(strict_types=1);

namespace CleatSquad\TextNormalizer\Tests;

use CleatSquad\TextNormalizer\NormalizerProfile;
use CleatSquad\TextNormalizer\TextNormalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Orthographic equivalences Unicode keeps distinct: the same word typed on an
 * Arabic, Persian or Urdu keyboard yields different code points, and no
 * normalization form unifies them. Without these, a search index answers
 * nothing to a query that differs only by keyboard layout.
 */
final class ArabicOrthographyTest extends TestCase
{
    private TextNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new TextNormalizer();
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function equivalentSpellings(): array
    {
        return [
            'harakat vs bare' => ['مَدْرَسَة', 'مدرسة'],
            'alif hamza above' => ['أحمد', 'احمد'],
            'alif hamza below' => ['إبراهيم', 'ابراهيم'],
            'alif madda' => ['آمن', 'امن'],
            'ta marbuta vs ha' => ['مدرسة', 'مدرسه'],
            'alef maksura vs yeh' => ['علي', 'على'],
            'farsi yeh vs arabic yeh' => ['علي', 'علی'],
            'keheh vs arabic kaf' => ['كتاب', 'کتاب'],
            'heh goal vs heh' => ['كتابه', 'كتابہ'],
            'tatweel elongation' => ['مدرســـة', 'مدرسة'],
            'zero width non joiner' => ['می‌رود', 'میرود'],
            'arabic-indic digits' => ['غرفة ٢٠٥', 'غرفة 205'],
            'extended arabic-indic digits' => ['غرفة ۲۰۵', 'غرفة 205'],
        ];
    }

    #[DataProvider('equivalentSpellings')]
    public function testEquivalentSpellingsFoldToTheSameForm(string $a, string $b): void
    {
        self::assertSame($this->normalizer->normalize($a), $this->normalizer->normalize($b));
    }

    public function testNormalizedArabicStaysInArabicScript(): void
    {
        $normalized = $this->normalizer->normalize('مَدْرَسَة');

        self::assertMatchesRegularExpression('/^[\p{Arabic}\s]+$/u', $normalized);
        self::assertSame('مدرسه', $normalized);
    }

    public function testDistinctWordsStayDistinct(): void
    {
        self::assertNotSame(
            $this->normalizer->normalize('كتاب'),
            $this->normalizer->normalize('كتب')
        );
    }

    public function testALatinOnlyProfileLeavesArabicMarksInPlace(): void
    {
        $latin = new TextNormalizer(NormalizerProfile::latin());

        self::assertSame('مَدْرَسَة', $latin->normalize('مَدْرَسَة'));
    }

    public function testAnArabicOnlyProfileLeavesLatinAccentsInPlace(): void
    {
        $arabic = new TextNormalizer(NormalizerProfile::arabic());

        self::assertSame('météo', $arabic->normalize('Météo'));
    }
}
