---
sidebar_position: 2
---

# Classifying Text

`classify()` returns a `ClassificationResult` object on success, or a string error code on failure.

```php
use ByJG\TextClassifier\ClassificationResult;

$result = $classifier->classify($text);

if (!($result instanceof ClassificationResult)) {
    // handle error code string
}

echo $result->choice;  // 'spam' or 'ham'
echo $result->score;   // float 0.0–1.0 (spam probability)
```

## ClassificationResult fields

| Field | Type | Description |
|---|---|---|
| `choice` | `string` | `'spam'` or `'ham'` |
| `score` | `float` | Spam probability `0.0`–`1.0` (final, after any LLM retraining) |
| `scores` | `array<string, float>` | `['spam' => …, 'ham' => …]` final scores |
| `statScores` | `array<string, float>` | Raw statistical scores before any LLM escalation |
| `llmDecision` | `string\|null` | LLM's label if it was consulted, otherwise `null` |
| `escalated` | `bool` | `true` when the LLM was invoked |

## Interpreting the score

| Score range | Interpretation |
|---|---|
| `0.0 – 0.2` | Strong ham |
| `0.2 – 0.4` | Likely ham |
| `0.4 – 0.6` | Uncertain |
| `0.6 – 0.8` | Likely spam |
| `0.8 – 1.0` | Strong spam |

`0.5` means the filter has no opinion — either no training data exists, or the tokens in the text are equally distributed between ham and spam.

## Choosing a threshold

```php
use ByJG\TextClassifier\ClassificationResult;

$result = $classifier->classify($text);
if (!($result instanceof ClassificationResult)) {
    // handle error
}

// Aggressive spam filtering (minimise false negatives)
if ($result->score > 0.7) {
    rejectMessage();
}

// Conservative (minimise false positives)
if ($result->score > 0.9) {
    rejectMessage();
} elseif ($result->score > 0.7) {
    quarantineMessage();
}
```

## Relevance filtering

BinaryClassifier does not use all tokens in the text. It selects the most "relevant" tokens — those whose spam probability deviates most from `0.5`. The number of tokens considered is controlled by `ConfigBinaryClassifier::setUseRelevant()` (default: `15`) and the minimum deviation threshold `ConfigBinaryClassifier::setMinDev()` (default: `0.2`).

Tokens that appear multiple times in the text contribute proportionally — a token appearing three times counts three times in the probability calculation.

## Degeneration fallback

When a token is not found in the database, BinaryClassifier tries degenerated variants (case variants, punctuation stripped). If none are found, it uses the neutral value `robX` (default: `0.5`). This prevents unknown words from skewing the score.

## Error codes

`classify()` returns a string error code instead of a `ClassificationResult` when something goes wrong:

| Constant | Meaning |
|---|---|
| `BinaryClassifier::CLASSIFYER_TEXT_MISSING` | `$text` was `null` |
| `StandardLexer::LEXER_TEXT_NOT_STRING` | `$text` is not a string |
| `StandardLexer::LEXER_TEXT_EMPTY` | `$text` is an empty string |

## Related

- [How B8 works](../../concepts/how-binary-classifier-works.md) — the Robinson-Fisher algorithm explained
- [ConfigBinaryClassifier reference](../../reference/config-binary-classifier.md) — all tuning parameters
- [Error codes reference](../../reference/error-codes.md)
- [LLM-Assisted Classification](../../guides/llm-assisted-classification.md)
