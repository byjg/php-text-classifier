---
sidebar_position: 2
---

# Quick Start: Spam Filter

This guide walks through a minimal working spam filter using the `B8` engine with SQLite storage.

## 1. Set up storage

```php
use B8\Degenerator\ConfigDegenerator;
use B8\Degenerator\StandardDegenerator;
use B8\Storage\Rdbms;
use ByJG\Util\Uri;

$degenerator = new StandardDegenerator(new ConfigDegenerator());
$storage = new Rdbms(new Uri('sqlite:///var/data/spam.db'), $degenerator);

// Call once to create the database tables
$storage->createDatabase();
```

## 2. Build the classifier

```php
use B8\B8;
use B8\ConfigB8;
use B8\Lexer\ConfigLexer;
use B8\Lexer\StandardLexer;

$b8 = new B8(
    new ConfigB8(),
    $storage,
    new StandardLexer(new ConfigLexer())
);
```

## 3. Train with known texts

```php
// Mark texts as spam
$b8->learn('Buy cheap pills now! Limited offer!!!', B8::SPAM);
$b8->learn('You have won a prize. Click here to claim.', B8::SPAM);
$b8->learn('Earn money fast working from home', B8::SPAM);

// Mark texts as ham
$b8->learn('Meeting rescheduled to Tuesday at 2pm', B8::HAM);
$b8->learn('Please review the attached pull request', B8::HAM);
$b8->learn('Your order has been shipped', B8::HAM);
```

## 4. Classify new text

```php
$score = $b8->classify('win money fast click now');
// Returns a float between 0.0 (ham) and 1.0 (spam)

if ($score > 0.8) {
    echo "Spam ($score)";
} elseif ($score < 0.2) {
    echo "Ham ($score)";
} else {
    echo "Unsure ($score)";
}
```

## 5. Correct a mistake

If the filter classified something wrong, unlearn it and re-learn with the correct label:

```php
// Previously trained as ham, but it was actually spam
$b8->unlearn('Your invoice is attached', B8::HAM);
$b8->learn('Your invoice is attached', B8::SPAM);
```

## What happens on first classify

Before any training, `classify()` returns `0.5` — the filter has no opinion. The more you train it, the more accurate it becomes. The quality of training data matters more than the quantity.

## Next steps

- [Training guide](../guides/spam-filter/training.md) — best practices, batch training, unlearn
- [Classifying guide](../guides/spam-filter/classifying.md) — interpreting scores, thresholds
- [Storage: SQLite / MySQL / PostgreSQL](../guides/spam-filter/storage-rdbms.md)
- [Storage: BerkeleyDB](../guides/spam-filter/storage-dba.md)
