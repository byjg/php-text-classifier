---
sidebar_position: 3
---

# Storage: Memory

`ByJG\TextClassifier\NaiveBayes\Storage\Memory` keeps all data in PHP arrays. It is the simplest storage option — no setup, no dependencies, no files. Data is lost when the process ends unless you explicitly save it.

## Setup

```php
use ByJG\TextClassifier\NaiveBayes\Storage\Memory;

$storage = new Memory();
```

No constructor arguments, no `createDatabase()` call required.

## Persisting to disk

Save the trained model to a JSON file:

```php
$storage->save('/var/data/model.json');
```

Load it back in a subsequent request:

```php
$storage = new Memory();
$storage->load('/var/data/model.json');
```

The JSON file contains two keys:

```json
{
  "docs":   { "tech": 5, "animals": 3 },
  "tokens": { "programming": { "tech": 4, "animals": 0 } }
}
```

## Typical lifecycle

```php
// --- Training phase (run once, or periodically) ---
$storage = new Memory();
$nb = new NaiveBayes($storage, $lexer);

$nb->train('PHP is a programming language', 'tech');
$nb->train('The cat sat on the mat', 'animals');
// ... more training ...

$storage->save('/var/data/model.json');

// --- Classification phase (every request) ---
$storage = new Memory();
$storage->load('/var/data/model.json');
$nb = new NaiveBayes($storage, $lexer);

$result = $nb->classify($incomingText);
```

## Concurrent access

`Memory` storage has no file locking. If multiple processes load and save the same file, use an external lock or switch to [RDBMS storage](storage-rdbms.md).

## When to use this backend

- Prototyping and experimentation
- Single-request classification where the model is loaded once per process
- Small models that fit comfortably in memory
- Scripts, CLI tools, and batch jobs

## When to use RDBMS instead

- Model is updated frequently from multiple processes
- Training data is too large to hold in memory
- Durability and ACID guarantees are required

## Related

- [Storage: RDBMS](storage-rdbms.md)
- [Storage backends comparison](../../concepts/storage-backends.md)
