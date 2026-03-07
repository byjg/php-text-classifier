---
sidebar_position: 2
---

# Classifying Text

`classify()` returns a `ClassificationResult` object, or `null` when no categories have been trained yet.

```php
$result = $nb->classify(string $text): ?ClassificationResult
```

## Return value

```php
$result = $nb->classify('programming language Python');

$result->choice;  // 'tech'
$result->score;   // 0.94
$result->scores;  // ['tech' => 0.94, 'politics' => 0.51, 'animals' => 0.48]
```

## ClassificationResult fields

| Field | Type | Description |
|---|---|---|
| `choice` | `string` | Winning category name |
| `score` | `float` | Score of the winning category `0.0`–`1.0` (final, after any LLM retraining) |
| `scores` | `array<string, float>` | All final category scores, sorted descending |
| `statScores` | `array<string, float>` | Raw statistical scores before any LLM escalation |
| `llmDecision` | `string\|null` | LLM's label if it was consulted, otherwise `null` |
| `escalated` | `bool` | `true` when the LLM was invoked |

## Getting the top category

```php
$result = $nb->classify($text);
echo $result?->choice;  // null-safe when no categories trained yet
```

## Confidence threshold

Scores close to `0.5` mean the classifier is uncertain. A score near `1.0` means strong evidence for that category:

```php
$result = $nb->classify($text);
if ($result === null) {
    echo "No categories trained yet";
} elseif ($result->score >= 0.8) {
    echo "Confident: {$result->choice}";
} elseif ($result->score >= 0.6) {
    echo "Likely: {$result->choice}";
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
| No trained categories | Returns `null` |
| Text with no known tokens | Category scores stay near `0.5`; order is unpredictable |
| Only one category trained | Returns `null` (one-vs-rest requires at least 2 categories with docs) |
| Empty or non-string input | Returns `null` silently (lexer returns no tokens) |

## Related

- [Training guide](training.md)
- [How NaiveBayes works](../../concepts/how-naive-bayes-works.md)
- [NaiveBayes API reference](../../reference/naive-bayes.md)
- [LLM-Assisted Classification](../../guides/llm-assisted-classification.md)
