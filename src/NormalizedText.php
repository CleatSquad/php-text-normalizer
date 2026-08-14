<?php

declare(strict_types=1);

namespace CleatSquad\TextNormalizer;

/**
 * Value object representing the result of a normalization operation,
 * holding the original text, normalized text, and metadata.
 */
final readonly class NormalizedText
{
    public function __construct(
        public string $original,
        public string $normalized,
        public string $profileName = 'default',
    ) {
    }

    public function isEmpty(): bool
    {
        return $this->normalized === '';
    }

    public function length(): int
    {
        return mb_strlen($this->normalized, 'UTF-8');
    }

    public function originalLength(): int
    {
        return mb_strlen($this->original, 'UTF-8');
    }

    public function wasModified(): bool
    {
        return $this->original !== $this->normalized;
    }

    public function __toString(): string
    {
        return $this->normalized;
    }
}
