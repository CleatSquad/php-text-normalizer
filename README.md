# PHP Text Normalizer

[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.2-777bb4.svg)](composer.json)

Folds text to a comparable form for **search and deduplication**: lowercase,
diacritics removed, punctuation collapsed — while keeping the text **in its own
script**.

```php
$normalizer->normalize('Crème Brûlée');   // "creme brulee"
$normalizer->normalize('مَدْرَسَة');          // "مدرسه"  — still Arabic
```

## Is this what you need?

Most PHP libraries in this space produce an **ASCII slug for URLs**. This one
produces a **comparison key in the original script**. Pick accordingly:

| You want | Use |
|---|---|
| A URL slug (`crème` → `creme`) | [`cocur/slugify`](https://github.com/cocur/slugify) or `symfony/string` |
| Transliteration into Latin (`مدرسة` → `madrasa`) | `ext-intl` `Transliterator` |
| A comparison key that stays Arabic (`مَدْرَسَة` → `مدرسه`) | **this package** |

The distinction matters for search. Transliterating Arabic to Latin collapses
unrelated roots onto the same consonant skeleton and produces a key you cannot
display, highlight, or feed back into an Arabic index.

## Installation

```bash
composer require cleatsquad/php-text-normalizer
```

PHP 8.2+. Uses `ext-intl` when present, and falls back to
`symfony/polyfill-intl-normalizer` otherwise.

## Usage

```php
use CleatSquad\TextNormalizer\TextNormalizer;

$normalizer = new TextNormalizer();

$normalizer->normalize('Quelle est la MÉTÉO à Rabat ?');
// "quelle est la meteo a rabat"

$normalizer->tokenize('token-2024');
// ['token', '2024']
```

### Script profiles

```php
use CleatSquad\TextNormalizer\NormalizerProfile;

new TextNormalizer(NormalizerProfile::latin());   // Latin only
new TextNormalizer(NormalizerProfile::arabic());  // Arabic only
new TextNormalizer(NormalizerProfile::all());     // default
new TextNormalizer(new NormalizerProfile());      // punctuation only, folds nothing
```

Profiles are scoped: a Latin profile leaves Arabic harakat exactly where they
are. Compose your own, or extend a shipped one:

```php
$profile = NormalizerProfile::latin()->merge(
    new NormalizerProfile(characterMap: ['ĳ' => 'ij'])
);
```

## What it folds

**Diacritics — by Unicode canonical decomposition, not a table.** `Košice`,
`Ṣāliḥ`, `Đà Nẵng`, `Ĝangalo` all fold correctly, in every script, because NFD
reaches every decomposable letter. A hand-written table only ever covers the
ones someone remembered.

**Letters that carry no mark — by table**, since decomposition cannot reach
them: `æ œ ß ø ł đ ð þ ħ ı ŋ ŧ ƶ`.

**Arabic orthographic equivalences — by table**, because Unicode considers them
distinct letters and no normalization form unifies them:

| Fold | Why |
|---|---|
| `أ إ آ ٱ` → `ا` | Alif variants |
| `ة` → `ه` | Ta Marbuta, as search indexes conventionally do |
| `ى ی ې ۍ` → `ي` | Alef Maksura, and Persian/Urdu/Pashto Yeh |
| `ک ګ` → `ك` | Keheh (Persian/Urdu Kaf) |
| `ہ ھ` → `ه` | Heh Goal, Heh Doachashmee |
| `٠-٩` and `۰-۹` → `0-9` | Arabic-Indic and Extended Arabic-Indic digits |
| tatweel `ـ` removed | decorative elongation, never lexical |
| ZWNJ/ZWJ removed | invisible, and Persian puts them inside words |

Without these, `علي` typed on an Arabic keyboard and `علی` typed on a Persian
one are two different strings, and your index answers nothing.

## Design notes

**Idempotent.** Normalizing an already-normalized string returns it unchanged.

**Output stays in NFC**, so it is safe to store and compare byte-wise.

**Combining marks a profile does not claim are preserved**, attached to their
letter rather than treated as word boundaries.

**No clock, no I/O, no configuration files.** One object, two methods.

## Testing

```bash
composer install
composer test      # PHPUnit
composer analyse   # PHPStan, max level
```

## License

MIT. See [LICENSE](LICENSE).
