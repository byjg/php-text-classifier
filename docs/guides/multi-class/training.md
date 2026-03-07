---
sidebar_position: 1
---

# Training the Multi-class Classifier

Training teaches the `NaiveBayes` engine which text belongs to which category. Categories are created automatically the first time you train a sample under a new category name.

## Basic training

```php
$nb->train(string $text, string $category): void
```

Category names are arbitrary strings — use whatever makes sense for your domain:

```php
$nb->train('PHP is a web programming language', 'tech');
$nb->train('The stock market fell today', 'finance');
$nb->train('The election results were announced', 'politics');
```

## Untraining

Remove a previously trained sample from the model:

```php
$nb->untrain(string $text, string $category): void
```

```php
$nb->untrain('PHP is a web programming language', 'tech');
```

Token counts are decremented proportionally. If a token count reaches zero, the token is removed. If the document count for a category reaches zero, the category is removed.

## Category lifecycle

| Operation | Effect |
|---|---|
| First `train()` for a new category name | Category is created automatically |
| All samples untrained from a category | Category disappears from `classify()` results |
| `classify()` with no trained categories | Returns empty array `[]` |

## Correcting a mistake

```php
// Trained under wrong category
$nb->untrain($text, 'tech');
$nb->train($text, 'science');
```

## Training data guidelines

| Principle | Guidance |
|---|---|
| Balance | Similar sample counts across all categories reduces bias |
| Diversity | Varied phrasing generalises better than repeated phrases |
| Minimum | At least a few samples per category before classifying |
| Text length | Longer texts provide more signal; very short texts (1-2 words) are less reliable |

## Token counting

The lexer extracts tokens from the text (see [Lexer concepts](../../concepts/lexer.md)). Each unique token is counted. A word that appears three times in one training text contributes `count = 3` for that token, not `count = 1`. This means frequently repeated words carry more weight.

## Related

- [Classifying text](classifying.md)
- [How NaiveBayes works](../../concepts/how-naive-bayes-works.md)
- [Lexer concepts](../../concepts/lexer.md)
