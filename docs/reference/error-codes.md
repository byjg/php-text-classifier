---
sidebar_position: 8
---

# Error Codes

The BinaryClassifier engine returns string error constants instead of throwing exceptions when it receives invalid input. Always check return values before using them numerically.

## Pattern

```php
use ByJG\TextClassifier\ClassificationResult;

$result = $classifier->classify($text);

if (!($result instanceof ClassificationResult)) {
    // $result is an error code string
    handleError($result);
    return;
}

if ($result->score > 0.8) { /* spam */ }
```

## BinaryClassifier classify() error codes

| Constant | String value | Trigger condition |
|---|---|---|
| `BinaryClassifier::CLASSIFYER_TEXT_MISSING` | `'CLASSIFYER_TEXT_MISSING'` | `$text` is `null` |
| `StandardLexer::LEXER_TEXT_NOT_STRING` | `'LEXER_TEXT_NOT_STRING'` | `$text` is not a string |
| `StandardLexer::LEXER_TEXT_EMPTY` | `'LEXER_TEXT_EMPTY'` | `$text` is an empty string |

## BinaryClassifier learn() / unlearn() error codes

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
- No trained categories → `classify()` returns `null`

## Lexer error codes

These originate in `StandardLexer` and are propagated by `BinaryClassifier`:

| Constant | Class | Value |
|---|---|---|
| `LEXER_TEXT_NOT_STRING` | `ByJG\TextClassifier\Lexer\StandardLexer` | `'LEXER_TEXT_NOT_STRING'` |
| `LEXER_TEXT_EMPTY` | `ByJG\TextClassifier\Lexer\StandardLexer` | `'LEXER_TEXT_EMPTY'` |

If you implement a custom `LexerInterface`, you may return any string error code from `getTokens()`. BinaryClassifier will propagate it as-is.

## Defensive usage example

```php
function classifyMessage(BinaryClassifier $classifier, mixed $input): ?float
{
    if (!is_string($input) || $input === '') {
        return null;
    }

    $result = $classifier->classify($input);

    return ($result instanceof ClassificationResult) ? $result->score : null;
}
```
