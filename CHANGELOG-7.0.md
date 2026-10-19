# Changelog - Version 7.0

> **Status: in development.** This document tracks changes landing on the `7.0` branch.
> Nothing here is released yet, and the contents may still change.

## Breaking Changes

- None.

## Bug Fixes

### `StandardLexer`: the text `"0"` was treated as empty

`getTokens()` checked for empty input with `empty($text)`, which is also true for the string `"0"`,
so classifying or training on `"0"` returned `LEXER_TEXT_EMPTY` instead of tokenizing it. Only the
empty string `''` is now rejected. Whitespace-only text is unchanged: it was never considered empty
and still yields no tokens.

## Requirements

- PHP 8.3, 8.4, 8.5 and 8.6 are now supported: `"php": ">=8.3 <8.7"`.
  The previous `<8.6` upper bound excluded PHP 8.6, since `<8.6` is exclusive.

### ByJG dependencies

- `byjg/llm-api-objects` is now `^7.0`.
- `byjg/micro-orm` is now `^7.0`.
- `byjg/migration` is now `^7.0`.

While 7.0 is unreleased these resolve to `7.0.x-dev` from each component's
`7.0` branch, via `minimum-stability: dev` with `prefer-stable: true`.

## Toolchain

- PHPUnit updated to `^12.5`.
- Psalm is installed as `psalm/phar` instead of `vimeo/psalm`.

  `vimeo/psalm` lists the PHP versions it supports and no published release includes
  8.6, so as a dev dependency it made `composer install` fail on the 8.6 build job
  before any test ran. `psalm/phar` requires only `php ^8.2` and bundles its own
  dependencies, so it installs on every PHP version in the matrix and cannot conflict
  with the project's. Psalm itself still refuses to *run* on 8.6, which is why the
  Psalm job uses 8.5. `composer psalm` runs it.

- PHPUnit 13 is deliberately **not** used. It requires PHP `>=8.4.1`, breaking the
  8.3 floor.

## Continuous Integration

- The build matrix now includes PHP 8.6.
- The Psalm job runs on PHP 8.5.

## Housekeeping

- `phpunit.xml.dist` renamed to `phpunit.xml`.
- The license is now stated consistently as LGPL 2.1: `composer.json` declares
  `LGPL-2.1-only` and `LICENSE` holds the LGPL text (it was in `COPYING`, next to an MIT
  `LICENSE` file). The library derives from the b8 filter by Tobias Leupold, and the source
  files have carried the LGPL 2.1 notice all along.
- `homepage` in `composer.json` points to this repository.
