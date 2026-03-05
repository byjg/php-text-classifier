---
sidebar_position: 2
---

# Classifying Text

`classify()` returns an associative array of `category => score` pairs, sorted by score descending.

```php
$scores = $nb->classify(string $text): array<string, float>
```

## Return value

```php
$scores = $nb->classify('programming language Python');
// [
//   'tech'     => 0.94,
//   'politics' => 0.51,
//   'animals'  => 0.48,
// ]
```

- Keys are category names, in descending score order
- Scores are floats between `0.0` and `1.0`
- An empty array `[]` means no categories have been trained yet

## Getting the top category

```php
$topCategory = array_key_first($nb->classify($text));
```

## Confidence threshold

Scores close to `0.5` mean the classifier is uncertain. A score near `1.0` means strong evidence for that category:

```php
$scores = $nb->classify($text);
$top    = array_key_first($scores);
$score  = $scores[$top] ?? 0.0;

if ($score >= 0.8) {
    echo "Confident: $top";
} elseif ($score >= 0.6) {
    echo "Likely: $top";
} else {
    echo "Uncertain — consider retraining";
}
```

## One-vs-rest scoring

Each category is scored independently using a one-vs-rest approach: the classifier asks "how likely is this text to belong to category X versus all other categories combined?" This means scores across categories do not sum to `1.0`.

## Categories with no overlap

If a token appears only in one category, it becomes a strong signal for that category. Tokens shared across many categories carry less discriminative weight.

## Edge cases

| Situation | Result |
|---|---|
| No trained categories | Returns `[]` |
| Text with no known tokens | Category scores stay near `0.5`; order is unpredictable |
| Only one category trained | That category is skipped (one-vs-rest requires at least 2 categories with docs) |
| Empty or non-string input | Returns `[]` silently (lexer returns no tokens) |

## Related

- [Training guide](training.md)
- [How NaiveBayes works](../../concepts/how-naive-bayes-works.md)
- [NaiveBayes API reference](../../reference/naive-bayes.md)
