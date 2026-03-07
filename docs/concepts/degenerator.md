---
sidebar_position: 4
---

# Degenerator

The degenerator is used only by the `BinaryClassifier` spam filter. It generates "degenerated" variants of a token so that the filter can fall back to related forms when an exact token is not found in training data.

## Purpose

If the classifier encounters `HELLO!` but only `hello` is in the training database, the degenerator allows `hello`'s stored probability to be used in place of `HELLO!`. Without degeneration, every unseen capitalisation or punctuation variant would return the neutral score `robX = 0.5`.

## Variants generated

For each token, `StandardDegenerator` generates:

| Variant | Example (input: `Hello!`) |
|---|---|
| Lowercase | `hello!` |
| Uppercase | `HELLO!` |
| Title case | `Hello!` |
| Single punctuation (if multiple) | `Hello!` → `Hello!` (already single) |
| Without trailing punctuation | `Hello!` → `Hello` |
| Trailing dots stripped iteratively | `word...` → `word..` → `word.` → `word` |

Duplicates (variants identical to the original token) are excluded.

## How variants are selected during classification

`BinaryClassifier` tries all degenerated variants against the storage. If one or more are found, the one with probability furthest from `0.5` is used. This selects the most "informative" variant:

```php
// Pseudocode of selection logic in BinaryClassifier::_getProbability()
$rating = 0.5; // default
foreach ($degenerates as $variant => $count) {
    $candidate = calcProbability($count, $ham_texts, $spam_texts);
    if (abs(0.5 - $candidate) > abs(0.5 - $rating)) {
        $rating = $candidate;
    }
}
```

## Multibyte support

By default, `StandardDegenerator` uses `strtolower()` / `strtoupper()` which only handles ASCII. For non-ASCII text (accented characters, Cyrillic, CJK), enable multibyte mode:

```php
use ByJG\TextClassifier\Degenerator\ConfigDegenerator;
use ByJG\TextClassifier\Degenerator\StandardDegenerator;

$degenerator = new StandardDegenerator(
    (new ConfigDegenerator())
        ->setMultibyte(true)
        ->setEncoding('UTF-8')
);
```

With multibyte enabled, `mb_strtolower()`, `mb_strtoupper()`, and `mb_substr()` are used instead.

## Caching

`StandardDegenerator` caches degenerated variants in memory during the lifetime of the object. If the same token is processed multiple times, the degeneration is only computed once.

## NaiveBayes does not use degeneration

The `NaiveBayes` engine does not accept a degenerator. Unknown tokens in NaiveBayes are simply skipped — they contribute no signal to the classification score. If degeneration-like fallback behaviour is important for your use case, use the BinaryClassifier engine instead.

## ConfigDegenerator reference

| Method | Default | Purpose |
|---|---|---|
| `setMultibyte(bool)` | `false` | Use multibyte string functions |
| `setEncoding(string)` | `'UTF-8'` | Encoding for multibyte operations |

See [ConfigDegenerator reference](../reference/config-degenerator.md).
