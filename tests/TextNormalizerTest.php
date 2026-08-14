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
}
