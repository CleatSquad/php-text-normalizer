<?php

declare(strict_types=1);

namespace CleatSquad\TextNormalizer\Tests;

use CleatSquad\TextNormalizer\NormalizerProfile;
use CleatSquad\TextNormalizer\TextNormalizer;
use PHPUnit\Framework\TestCase;

final class TextNormalizerTest extends TestCase
{
    private TextNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new TextNormalizer();
    }

    public function testNormalizeLowercaseMultibyte(): void
    {
        self::assertSame('quelle est la meteo a rabat', $this->normalizer->normalize('Quelle est la MÉTÉO à Rabat ?'));
    }

    public function testNormalizeAccents(): void
    {
        self::assertSame('meteo a rabat', $this->normalizer->normalize('  Météo   à   RABAT !!! '));
        self::assertSame('ca va ou', $this->normalizer->normalize('ça va où ?'));
        self::assertSame('un eleve de l ecole', $this->normalizer->normalize('un élève de l\'école'));
    }

    public function testNormalizePunctuation(): void
    {
        self::assertSame('hello world', $this->normalizer->normalize('hello, world!'));
        self::assertSame('a b c', $this->normalizer->normalize('a;b:c.'));
    }

    public function testNormalizeMultipleSpacesAndTrim(): void
    {
        self::assertSame('a b c', $this->normalizer->normalize('  a   b   c  '));
    }

    public function testTokenize(): void
    {
        self::assertSame(['quelle', 'est', 'la', 'meteo', 'a', 'rabat'], $this->normalizer->tokenize('Quelle est la MÉTÉO à Rabat ?'));
        self::assertSame([], $this->normalizer->tokenize('   !!!  '));
    }

    public function testEmptyAndWhitespaceOnlyInput(): void
    {
        self::assertSame('', $this->normalizer->normalize(''));
        self::assertSame('', $this->normalizer->normalize('   '));
        self::assertSame('', $this->normalizer->normalize('!!! ??? ---'));
        self::assertSame([], $this->normalizer->tokenize(''));
        self::assertSame([], $this->normalizer->tokenize('!!!'));
    }

    public function testNormalizationIsIdempotent(): void
    {
        $once = $this->normalizer->normalize('Quelle est la MÉTÉO à Rabat ?');

        self::assertSame($once, $this->normalizer->normalize($once));
    }

    public function testALatinOnlyProfileLeavesArabicDiacriticsAlone(): void
    {
        $latin = new TextNormalizer(NormalizerProfile::latin());

        self::assertSame('مَدْرَسَة', $latin->normalize('مَدْرَسَة'));
        self::assertSame('مدرسه', $this->normalizer->normalize('مَدْرَسَة'));
    }

    public function testAnEmptyProfileOnlyCollapsesPunctuation(): void
    {
        $bare = new TextNormalizer(new NormalizerProfile());

        self::assertSame('météo à rabat', $bare->normalize('Météo, à RABAT !'));
    }

    public function testDigitsAndUnderscoresSurviveAsSeparatorsOrContent(): void
    {
        self::assertSame('invoice 42', $this->normalizer->normalize('invoice_42'));
        self::assertSame(['token', '2024'], $this->normalizer->tokenize('token-2024'));
    }

    /**
     * Property test: Idempotence across multiple diverse sample strings.
     */
    public function testPropertyIdempotence(): void
    {
        $samples = [
            'Quelle est la MÉTÉO à Rabat ?',
            'مَدْرَسَةٌ كَبِيرَةٌ',
            'Košice, Ṣāliḥ & Đà Nẵng!',
            '  Multiple   Spaces   &   Punctuation...  ',
            'emoji 🚀 test 123',
            'علي vs علی',
        ];

        foreach ($samples as $sample) {
            $normalizedOnce = $this->normalizer->normalize($sample);
            $normalizedTwice = $this->normalizer->normalize($normalizedOnce);

            self::assertSame(
                $normalizedOnce,
                $normalizedTwice,
                sprintf("Failed idempotence for sample: '%s'", $sample)
            );
        }
    }

    /**
     * Negative collision test: Distinct words should NOT collide.
     */
    public function testNegativeCollisions(): void
    {
        // Hamza (ء) preservation should prevent collapse of distinct words
        self::assertNotSame(
            $this->normalizer->normalize('ماء'),
            $this->normalizer->normalize('ما')
        );

        // Distinct Latin words with different base letters must remain distinct
        self::assertNotSame(
            $this->normalizer->normalize('cote'),
            $this->normalizer->normalize('code')
        );
    }

    /**
     * Edge case test: Malformed UTF-8 and mixed scripts.
     */
    public function testUnicodeEdgeCases(): void
    {
        // Malformed UTF-8 sequence should not throw or return empty string silently
        $malformedUtf8 = "\x80\xA0\xA1";
        self::assertSame($malformedUtf8, $this->normalizer->normalize($malformedUtf8));

        // Mixed scripts (Arabic + Latin + Emojis). The emoji is a separator
        // like any other non-letter, but the variation selector trailing it
        // (U+FE0F) is a combining mark, and marks this profile does not claim
        // are kept on purpose.
        self::assertSame(
            "meteo a rabat الطقس في الرباط 2024 \u{FE0F}",
            $this->normalizer->normalize('Météo à Rabat - الطَّقْسُ فِي الرِّبَاط! 2024 🌤️')
        );
    }
}
