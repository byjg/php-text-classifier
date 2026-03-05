---
sidebar_position: 3
---

# Quick Start: Multi-class Classifier

This guide walks through a minimal working multi-class classifier using the `NaiveBayes` engine with in-memory storage.

## 1. Build the classifier

```php
use B8\Lexer\ConfigLexer;
use B8\Lexer\StandardLexer;
use B8\NaiveBayes\NaiveBayes;
use B8\NaiveBayes\Storage\Memory;

$nb = new NaiveBayes(
    new Memory(),
    new StandardLexer(new ConfigLexer())
);
```

## 2. Train with examples

Category names are arbitrary strings you define:

```php
$nb->train('PHP is a server-side programming language', 'tech');
$nb->train('Python is used for machine learning and data science', 'tech');
$nb->train('The cat sat on the mat and looked at the dog', 'animals');
$nb->train('Birds fly south during winter migration', 'animals');
$nb->train('The election results were announced on Tuesday', 'politics');
$nb->train('The new tax policy will affect small businesses', 'politics');
```

## 3. Classify text

```php
$scores = $nb->classify('programming language learning algorithms');

// Returns an array sorted by score descending:
// ['tech' => 0.94, 'animals' => 0.53, 'politics' => 0.48]

$topCategory = array_key_first($scores);
echo "Best match: $topCategory"; // tech
```

## 4. Persist to disk (Memory storage)

```php
// Save the trained model
$storage = new Memory();
// ... train ...
$storage->save('/var/data/model.json');

// Load it back later
$storage2 = new Memory();
$storage2->load('/var/data/model.json');
$nb2 = new NaiveBayes($storage2, new StandardLexer(new ConfigLexer()));
```

## 5. Undo a training sample

```php
$nb->untrain('PHP is a server-side programming language', 'tech');
```

## Scores explained

`classify()` returns `array<string, float>` sorted by score descending. Scores are not raw probabilities — they are Robinson-smoothed values between `0.0` and `1.0`. A higher score means stronger evidence that the text belongs to that category. An empty array means no categories have been trained yet.

## Next steps

- [Training guide](../guides/multi-class/training.md) — adding and removing samples, category lifecycle
- [Classifying guide](../guides/multi-class/classifying.md) — interpreting scores, confidence thresholds
- [Persistent SQL storage](../guides/multi-class/storage-rdbms.md)
- [Example: language detection](../guides/multi-class/example-language-detection.md)
