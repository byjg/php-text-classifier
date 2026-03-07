---
sidebar_position: 2
---

# Quick Start: Spam Filter

This guide walks through a minimal working spam filter using the `BinaryClassifier` engine with SQLite storage.

## 1. Set up storage

```php
use ByJG\TextClassifier\Degenerator\ConfigDegenerator;
use ByJG\TextClassifier\Degenerator\StandardDegenerator;
use ByJG\TextClassifier\Storage\Rdbms;
use ByJG\Util\Uri;

$degenerator = new StandardDegenerator(new ConfigDegenerator());
$storage = new Rdbms(new Uri('sqlite:///var/data/spam.db'), $degenerator);

// Call once to create the database tables
$storage->createDatabase();
```

## 2. Build the classifier

```php
use ByJG\TextClassifier\BinaryClassifier;
use ByJG\TextClassifier\ConfigBinaryClassifier;
use ByJG\TextClassifier\Lexer\ConfigLexer;
use ByJG\TextClassifier\Lexer\StandardLexer;

$classifier = new BinaryClassifier(
    new ConfigBinaryClassifier(),
    $storage,
    new StandardLexer(new ConfigLexer())
);
```

## 3. Train with known texts

```php
// Mark texts as spam
$classifier->learn('Buy cheap pills now! Limited offer!!!', BinaryClassifier::SPAM);
$classifier->learn('You have won a prize. Click here to claim.', BinaryClassifier::SPAM);
$classifier->learn('Earn money fast working from home', BinaryClassifier::SPAM);

// Mark texts as ham
$classifier->learn('Meeting rescheduled to Tuesday at 2pm', BinaryClassifier::HAM);
$classifier->learn('Please review the attached pull request', BinaryClassifier::HAM);
$classifier->learn('Your order has been shipped', BinaryClassifier::HAM);
```

## 4. Classify new text

```php
use ByJG\TextClassifier\ClassificationResult;

$result = $classifier->classify('win money fast click now');

if (!($result instanceof ClassificationResult)) {
    // handle error
}

if ($result->score > 0.8) {
    echo "Spam ({$result->score})";
} elseif ($result->score < 0.2) {
    echo "Ham ({$result->score})";
} else {
    echo "Unsure ({$result->score})";
}
```

## 5. Correct a mistake

If the filter classified something wrong, unlearn it and re-learn with the correct label:

```php
// Previously trained as ham, but it was actually spam
$classifier->unlearn('Your invoice is attached', BinaryClassifier::HAM);
$classifier->learn('Your invoice is attached', BinaryClassifier::SPAM);
```

## What happens on first classify

Before any training, `classify()` returns a result with `score = 0.5` — the classifier has no opinion. The more you train it, the more accurate it becomes. The quality of training data matters more than the quantity.

## Next steps

- [Training guide](../guides/spam-filter/training.md) — best practices, batch training, unlearn
- [Classifying guide](../guides/spam-filter/classifying.md) — interpreting scores, thresholds
- [LLM-assisted classification](../guides/llm-assisted-classification.md) — automatic fallback with active learning
- [Storage: SQLite / MySQL / PostgreSQL](../guides/spam-filter/storage-rdbms.md)
- [Storage: BerkeleyDB](../guides/spam-filter/storage-dba.md)
