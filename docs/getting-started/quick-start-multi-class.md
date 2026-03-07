---
sidebar_position: 3
---

# Quick Start: Multi-class Classifier

This guide walks through a minimal working multi-class classifier using the `NaiveBayes` engine with in-memory storage.

## 1. Build the classifier

```php
use ByJG\TextClassifier\Lexer\ConfigLexer;
use ByJG\TextClassifier\Lexer\StandardLexer;
use ByJG\TextClassifier\NaiveBayes\NaiveBayes;
use ByJG\TextClassifier\NaiveBayes\Storage\Memory;

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
$result = $nb->classify('programming language learning algorithms');

echo $result->choice;  // 'tech'
echo $result->score;   // e.g. 0.94

// All scores, sorted descending
// $result->scores => ['tech' => 0.94, 'animals' => 0.53, 'politics' => 0.48]
```

`classify()` returns `null` when no categories have been trained yet.

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

## Result explained

`classify()` returns a `ClassificationResult` with `choice` (winning category), `score` (its confidence, `0.0`–`1.0`), and `scores` (all categories sorted descending). Scores are Robinson-smoothed values — a higher score means stronger evidence. Returns `null` when no categories have been trained yet.

## Next steps

- [Training guide](../guides/multi-class/training.md) — adding and removing samples, category lifecycle
- [Classifying guide](../guides/multi-class/classifying.md) — interpreting scores, confidence thresholds
- [LLM-assisted classification](../guides/llm-assisted-classification.md) — automatic fallback with active learning
- [Persistent SQL storage](../guides/multi-class/storage-rdbms.md)
- [Example: language detection](../guides/multi-class/example-language-detection.md)
