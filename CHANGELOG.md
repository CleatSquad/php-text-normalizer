# Changelog

All notable changes to `cleatsquad/php-text-normalizer` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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

[0.1.0]: https://github.com/CleatSquad/php-text-normalizer/releases/tag/v0.1.0
