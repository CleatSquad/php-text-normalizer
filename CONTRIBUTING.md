# Contributing

Contributions are welcome — bug reports, documentation, and code alike.

## Getting started

```bash
git clone https://github.com/CleatSquad/php-text-normalizer.git
cd php-text-normalizer
composer install
```

## Before opening a pull request

```bash
composer test      # PHPUnit
composer analyse   # PHPStan, max level, must report no error
```

## Guidelines

- Follow PSR-12. Match the style of the surrounding code.
- Every behaviour change needs a test. Bug fixes need a test that fails before
  the fix.
- Keep the public API small. A new public method is a long-term commitment.
- Runtime dependencies are kept to the Unicode polyfill only. Pull
  requests adding another need to make a strong case.
- Update the README when public behaviour changes, and add a CHANGELOG entry.

## Backward compatibility

Anything under `src/` that is `public` is part of the public API and follows
[Semantic Versioning](https://semver.org). Breaking it requires a major
release, so prefer additive changes.

## Commit messages

Short imperative subject line, explaining what changes and why.
