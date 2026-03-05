---
sidebar_position: 2
---

# Classifying Text

`classify()` returns a float between `0.0` and `1.0` representing the probability that the text is spam.

```php
$score = $b8->classify($text);
```

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

The default range for "uncertain" is `0.4`–`0.6`. Adjust based on your use case:

```php
$score = $b8->classify($text);

// Aggressive spam filtering (minimise false negatives)
if ($score > 0.7) {
    rejectMessage();
}

// Conservative (minimise false positives)
if ($score > 0.9) {
    rejectMessage();
} elseif ($score > 0.7) {
    quarantineMessage();
}
```

## Relevance filtering

b8 does not use all tokens in the text. It selects the most "relevant" tokens — those whose spam probability deviates most from `0.5`. The number of tokens considered is controlled by `ConfigB8::setUseRelevant()` (default: `15`) and the minimum deviation threshold `ConfigB8::setMinDev()` (default: `0.2`).

Tokens that appear multiple times in the text contribute proportionally — a token appearing three times counts three times in the probability calculation.

## Degeneration fallback

When a token is not found in the database, b8 tries degenerated variants (case variants, punctuation stripped). If none are found, it uses the neutral value `robX` (default: `0.5`). This prevents unknown words from skewing the score.

## Error codes

`classify()` returns a string error code instead of a float when something goes wrong:

| Constant | Meaning |
|---|---|
| `B8::CLASSIFYER_TEXT_MISSING` | `$text` was `null` |
| `StandardLexer::LEXER_TEXT_NOT_STRING` | `$text` is not a string |
| `StandardLexer::LEXER_TEXT_EMPTY` | `$text` is an empty string |

Always check that the return value is a `float` before using it:

```php
$score = $b8->classify($text);
if (!is_float($score)) {
    // handle error
}
```

## Related

- [How B8 works](../../concepts/how-b8-works.md) — the Robinson-Fisher algorithm explained
- [ConfigB8 reference](../../reference/config-b8.md) — all tuning parameters
- [Error codes reference](../../reference/error-codes.md)
