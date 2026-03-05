---
sidebar_position: 2
---

# NaiveBayes Class

`B8\NaiveBayes\NaiveBayes` is the multi-class Naive Bayes text classifier.

## Constructor

```php
new NaiveBayes(
    StorageInterface $storage,
    LexerInterface   $lexer,
    ConfigNaiveBayes $config = new ConfigNaiveBayes()
)
```

| Parameter | Type | Description |
|---|---|---|
| `$storage` | `B8\NaiveBayes\Storage\StorageInterface` | Persistence backend |
| `$lexer` | `B8\Lexer\LexerInterface` | Tokeniser |
| `$config` | `ConfigNaiveBayes` | Smoothing parameters (optional) |

`$config` uses PHP 8.1+ constructor promotion default — if omitted, a default `ConfigNaiveBayes` is created automatically.

## Methods

### train()

```php
public function train(string $text, string $category): void
```

Trains the classifier with `$text` as an example of `$category`. The category is created automatically if it does not exist.

- Increments the document count for `$category` by 1
- Increments the token count for each unique token in `$text`, scaled by its occurrence count

### untrain()

```php
public function untrain(string $text, string $category): void
```

Reverses a previous `train()` call. Decrements document and token counts. If a category's document count reaches zero, it is removed from storage.

### classify()

```php
public function classify(string $text): array<string, float>
```

Classifies `$text` and returns an array of `category => score` pairs, sorted by score descending.

| Return value | Meaning |
|---|---|
| Non-empty array | `['category' => 0.94, ...]` sorted descending |
| Empty array `[]` | No categories trained yet, or lexer returned no tokens |

Scores are floats between `0.0` and `1.0`. They are not probabilities that sum to `1.0` — each category is scored independently in a one-vs-rest fashion.

```php
$scores = $nb->classify('machine learning algorithms');
// ['tech' => 0.91, 'science' => 0.67, 'politics' => 0.49]

$topCategory = array_key_first($scores);  // 'tech'
$topScore    = $scores[$topCategory];     // 0.91
```

## Usage example

```php
use B8\Lexer\ConfigLexer;
use B8\Lexer\StandardLexer;
use B8\NaiveBayes\NaiveBayes;
use B8\NaiveBayes\Storage\Memory;

$nb = new NaiveBayes(
    new Memory(),
    new StandardLexer(new ConfigLexer())
);

$nb->train('PHP is a programming language', 'tech');
$nb->train('The dog ran in the park', 'animals');

$scores = $nb->classify('programming language');
// ['tech' => ..., 'animals' => ...]
```

## Related

- [ConfigNaiveBayes reference](config-naive-bayes.md)
- [How NaiveBayes works](../concepts/how-naive-bayes-works.md)
- [Training guide](../guides/multi-class/training.md)
- [Classifying guide](../guides/multi-class/classifying.md)
