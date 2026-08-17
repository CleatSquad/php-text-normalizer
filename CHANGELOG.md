# Changelog

All notable changes to `cleatsquad/php-text-normalizer` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.0] - 2026-08-17

Both additions were already being written by hand on top of `tokenize()`, which
is how two call sites end up with different answers for the same string.

### Added

- `TextNormalizer::slugify(string $text, string $separator = '-')`: joins the
  tokens `tokenize()` already produces, so a slug can never disagree with the
  rest of the package about where a word ends. Text that normalizes to nothing
  yields an empty string. It is not an ASCII slugger — non-Latin input stays in
  its own script, folded but not transliterated, which is the same distinction
  the README already draws against `cocur/slugify`.
- `TextNormalizer::hash(string $text, string $algo = 'sha256')`: digests the
  slug. Two strings this package considers equivalent — casing, typographic
  apostrophes, collapsed spacing, trailing punctuation — always produce the
  same digest, which is what makes it usable as a deduplication or lookup key.
  Unsalted and deterministic by design: a fingerprint of meaning, not a secret,
  and never a password hash.

No behaviour changed. `normalize()`, `tokenize()` and `analyze()` are untouched.

## [1.0.0] - 2026-08-14

`NormalizerProfile` has been used by every feature added since `0.1.0` without
needing a change to its shape, so the extension point is now considered settled
and the package leaves `0.x`.

### Added

- `NormalizerProfile::cyrillic()`: Yo (`ё`), the Ukrainian and Belarusian `і`,
  `ї`, `ў` and `ґ` — letters no normalization form unifies.
- `NormalizerProfile::greek()`: final sigma (`ς → σ`), with tonos stripped by
  canonical decomposition.
- `NormalizerProfile::arabic(searchEquivalences: false)`, which keeps Ta Marbuta
  intact. Folding `ة → ه` is a search-index convention rather than a Unicode
  equivalence, and callers comparing strictly had no way to opt out of it.
- `TextNormalizer::analyze()`, returning a `NormalizedText` value object: the
  original and normalized strings, the profile name, and `isEmpty()`,
  `length()`, `originalLength()`, `wasModified()` and `__toString()`.
- Profiles carry a `name`, composed on `merge()` (`arabic_search_latin`).
- `tests/fixtures/normalization_samples.json`, so a sample can be added to the
  suite without touching PHP.

### Fixed

- Malformed UTF-8 is returned untouched instead of collapsing to an empty
  string. The guard only covered `Normalizer::normalize` and was never reached:
  `mb_strtolower` runs first and turns every bad byte into `?`, which the
  separator pass then erases.

### Changed

- `all()` composes four profiles instead of two, so Cyrillic and Greek text now
  folds where it previously passed through. Its name becomes
  `arabic_search_latin_cyrillic_greek`.
- `NormalizerProfile::__construct()` takes a trailing `name` argument, and
  `arabic()` and `all()` take `searchEquivalences`. Both default to the previous
  behaviour, so existing calls are unaffected.

## [0.1.0] - 2026-08-14

Initial release. Published as `0.x` on purpose: `NormalizerProfile` is an
extension point no one has used yet, and the folding tables will keep growing.

### Added

- `TextNormalizer` with `normalize()` and `tokenize()`.
- `NormalizerProfile` with `latin()`, `arabic()`, `all()` and `merge()`, so a
  script can be added without forking the package.
- Diacritic folding by Unicode canonical decomposition (NFD), covering every
  decomposable letter in every script rather than a fixed list.
- Table folding for letters decomposition cannot reach: `æ œ ß ø ł đ ð þ ħ ı ŋ ŧ ƶ`.
- Arabic orthographic equivalences that no Unicode normalization form unifies:
  Alif variants, Ta Marbuta, Alef Maksura and Persian/Urdu/Pashto Yeh, Keheh,
  Heh Goal and Heh Doachashmee, Arabic-Indic and Extended Arabic-Indic digits,
  tatweel removal, and zero-width joiner/non-joiner removal.
- Per-script mark folding: a Latin profile leaves Arabic harakat in place, and
  an Arabic profile leaves Latin accents in place.

### Notes

- Output is returned in NFC and normalizing is idempotent.
- Normalized Arabic stays in Arabic script; this package is not a transliterator
  and not a slug generator. See the README for when to use something else.

[1.0.0]: https://github.com/CleatSquad/php-text-normalizer/releases/tag/v1.0.0
[0.1.0]: https://github.com/CleatSquad/php-text-normalizer/releases/tag/v0.1.0
