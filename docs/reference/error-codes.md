---
sidebar_position: 8
---

# Error Codes

The BinaryClassifier engine returns string error constants instead of throwing exceptions when it receives invalid input. Always check return values before using them numerically.

## Pattern

```php
$result = $b8->classify($text);

if (!is_float($result)) {
    // $result is an error code string
    handleError($result);
    return;
}

// Safe to use as float
if ($result > 0.8) { /* spam */ }
```

## B8 classify() error codes

| Constant | String value | Trigger condition |
|---|---|---|
| `BinaryClassifier::CLASSIFYER_TEXT_MISSING` | `'CLASSIFYER_TEXT_MISSING'` | `$text` is `null` |
| `StandardLexer::LEXER_TEXT_NOT_STRING` | `'LEXER_TEXT_NOT_STRING'` | `$text` is not a string |
| `StandardLexer::LEXER_TEXT_EMPTY` | `'LEXER_TEXT_EMPTY'` | `$text` is an empty string |

## B8 learn() / unlearn() error codes

| Constant | String value | Trigger condition |
|---|---|---|
| `BinaryClassifier::TRAINER_TEXT_MISSING` | `'TRAINER_TEXT_MISSING'` | `$text` is `null` |
| `BinaryClassifier::TRAINER_CATEGORY_MISSING` | `'TRAINER_CATEGORY_MISSING'` | `$category` is `null` |
| `BinaryClassifier::TRAINER_CATEGORY_FAIL` | `'TRAINER_CATEGORY_FAIL'` | `$category` is not `BinaryClassifier::SPAM` or `BinaryClassifier::HAM` |
| `StandardLexer::LEXER_TEXT_NOT_STRING` | `'LEXER_TEXT_NOT_STRING'` | `$text` is not a string |
| `StandardLexer::LEXER_TEXT_EMPTY` | `'LEXER_TEXT_EMPTY'` | `$text` is an empty string |

`learn()` and `unlearn()` return `null` on success.

## NaiveBayes error handling

`NaiveBayes::classify()`, `train()`, and `untrain()` do not return error codes. They are void methods (train/untrain) or return an empty array (classify). Invalid input is handled silently:

- Non-string or empty text → lexer returns no tokens → no-op or empty result
- No trained categories → `classify()` returns `[]`

## Lexer error codes

These originate in `StandardLexer` and are propagated by `B8`:

| Constant | Class | Value |
|---|---|---|
| `LEXER_TEXT_NOT_STRING` | `ByJG\TextClassifier\Lexer\StandardLexer` | `'LEXER_TEXT_NOT_STRING'` |
| `LEXER_TEXT_EMPTY` | `ByJG\TextClassifier\Lexer\StandardLexer` | `'LEXER_TEXT_EMPTY'` |

If you implement a custom `LexerInterface`, you may return any string error code from `getTokens()`. BinaryClassifier will propagate it as-is.

## Defensive usage example

```php
function classifyMessage(BinaryClassifier $b8, mixed $input): ?float
{
    if (!is_string($input) || $input === '') {
        return null;
    }

    $score = $b8->classify($input);

    return is_float($score) ? $score : null;
}
```
