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
| A URL slug guaranteed ASCII, whatever the input script | [`cocur/slugify`](https://github.com/cocur/slugify) or `symfony/string` |
| Transliteration into Latin (`مدرسة` → `madrasa`) | `ext-intl` `Transliterator` |
| A comparison key that stays Arabic (`مَدْرَسَة` → `مدرسه`) | **this package** |
| A slug in the original script (`crème` → `creme`, `مَدْرَسَة` → `مدرسه`) | **this package**, `slugify()` |

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

### Slugs and stable hashes

```php
$normalizer->slugify("L'adresse email de l'utilisateur");
// "l-adresse-email-de-l-utilisateur"

$normalizer->slugify('Météo à Rabat', '_');
// "meteo_a_rabat"

$normalizer->hash("L'adresse email");
$normalizer->hash('L’ADRESSE   email.');
// identical — same normalization, same digest
```

`slugify()` joins the tokens `tokenize()` already produces, so a slug never
disagrees with the rest of the package about where a word ends. Text that
normalizes to nothing yields an empty string.

`hash()` digests the slug (`sha256` by default, any `hash()` algorithm on
request). Two strings this package considers equivalent — casing, typographic
apostrophes, collapsed spacing, trailing punctuation — always produce the same
digest, which is what makes it usable as a deduplication or lookup key. It is
not a password hash and carries no salt: it is a fingerprint of meaning, not a
secret.

### Analyzing text with metadata

```php
$result = $normalizer->analyze('  Météo   à   RABAT !!! ');

$result->normalized;    // "meteo a rabat"
$result->original;      // "  Météo   à   RABAT !!! "
$result->wasModified(); // true
$result->length();      // 13
$result->profileName;   // "arabic_search_latin"
```

### Script profiles

```php
use CleatSquad\TextNormalizer\NormalizerProfile;

new TextNormalizer(NormalizerProfile::latin());                    // Latin only
new TextNormalizer(NormalizerProfile::arabic());                   // Arabic search mode (ة -> ه)
new TextNormalizer(NormalizerProfile::arabic(searchEquivalences: false)); // Arabic strict mode (preserves ة)
new TextNormalizer(NormalizerProfile::cyrillic());                 // Cyrillic (ё -> е, і/ї -> i)
new TextNormalizer(NormalizerProfile::greek());                    // Greek (ς -> σ, tonos stripped)
new TextNormalizer(NormalizerProfile::all());                      // default (all scripts)
new TextNormalizer(new NormalizerProfile());                       // punctuation only, folds nothing
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

**Output stays in NFC** whenever valid UTF-8 Unicode normalization succeeds, making it safe to store and compare byte-wise. Malformed UTF-8 inputs are returned untouched rather than converted to an empty string.

**Combining marks a profile does not claim are preserved**, attached to their
letter rather than treated as word boundaries.

**No clock, no I/O, no configuration files.** One object, and every method
answers from `normalize()` — `tokenize()` splits it, `slugify()` rejoins it,
`hash()` digests it, `analyze()` describes it. Nothing folds text a second way.

## Testing

```bash
composer install
composer test      # PHPUnit
composer analyse   # PHPStan, max level
```

## License

MIT. See [LICENSE](LICENSE).
