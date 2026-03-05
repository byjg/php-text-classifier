---
sidebar_position: 1
---

# Training the Spam Filter

Training teaches b8 which texts are spam and which are ham. The filter learns by counting how often tokens appear in each category, weighted by the total number of trained texts per category.

## Basic training

```php
$b8->learn($text, B8::SPAM);  // mark as spam
$b8->learn($text, B8::HAM);   // mark as ham
```

Both constants are defined on the `B8` class:

```php
B8::SPAM  // = 'spam'
B8::HAM   // = 'ham'
```

## Unlearning

Remove a previously trained text from the model:

```php
$b8->unlearn($text, B8::SPAM);
$b8->unlearn($text, B8::HAM);
```

Use this to correct mistakes or to remove old training data that no longer reflects current patterns.

## Correcting a misclassification

The recommended correction workflow:

```php
// Text was learned as ham but should have been spam
$b8->unlearn($text, B8::HAM);
$b8->learn($text, B8::SPAM);
```

Always unlearn before re-learning with a different label. Skipping the unlearn step adds weight to both categories, which degrades accuracy.

## Batch training

There is no bulk training API — call `learn()` in a loop:

```php
$spamTexts = ['...', '...', '...'];
foreach ($spamTexts as $text) {
    $b8->learn($text, B8::SPAM);
}
```

## Training data quality

| Principle | Guidance |
|---|---|
| Balance | Aim for a similar number of spam and ham samples |
| Diversity | Varied text produces better generalisation than repetitive phrases |
| Relevance | Train on texts representative of what you expect to classify |
| Maintenance | Periodically untrain outdated samples to keep the model current |

## Training persistence

Training is persisted immediately to the storage backend. There is no separate "flush" or "commit" step. Rdbms storage writes to the database on every `learn()` call. DBA storage opens, writes, and closes the file per operation.

## Error codes

`learn()` and `unlearn()` return `null` on success or one of:

| Constant | Meaning |
|---|---|
| `B8::TRAINER_TEXT_MISSING` | `$text` was `null` |
| `B8::TRAINER_CATEGORY_MISSING` | `$category` was `null` |
| `B8::TRAINER_CATEGORY_FAIL` | `$category` was not `B8::SPAM` or `B8::HAM` |

See [Error Codes reference](../../reference/error-codes.md) for the full list.
