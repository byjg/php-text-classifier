---
sidebar_position: 4
---

# Storage: GDBM (DBA)

`ByJG\TextClassifier\Storage\Dba` persists the word list to a GDBM file using PHP's `dba_*` extension. It is a fast, embedded key-value store with no external server dependency.

## Requirements

The `ext-dba` PHP extension must be installed and the `gdbm` handler must be available:

```bash
# Ubuntu / Debian
sudo apt-get install php-dba

# Verify gdbm is available
php -r "print_r(dba_handlers(true));"
```

## Setup

```php
use ByJG\TextClassifier\Degenerator\ConfigDegenerator;
use ByJG\TextClassifier\Degenerator\StandardDegenerator;
use ByJG\TextClassifier\Storage\Dba;

$storage = new Dba(
    '/var/data/wordlist.db',
    new StandardDegenerator(new ConfigDegenerator())
);

// Run once to create the database file and seed internal variables
$storage->createDatabase();
```

## createDatabase()

Creates a new gdbm file at the specified path using `dba_open($path, 'c', 'gdbm')` and inserts the two required internal variables:

| key | value |
|---|---|
| `tc*dbversion` | `3` |
| `tc*texts` | `0 0` (ham count, spam count) |

**Call this once on a new, empty path.** Calling it on an existing file will fail because the file already exists and is not empty.

## File path

Pass an absolute filesystem path:

```php
$storage = new Dba('/absolute/path/to/wordlist.db', $degenerator);
```

The `.db` extension is conventional but not required.

## Data format

Each token is stored as a key-value pair:

```
key:   "some_word"
value: "count_ham count_spam"    e.g. "12 5"
```

Internal variables use a `b8*` prefix to avoid collisions with real tokens.

## When to use this backend

- Single-process applications where no external database is available
- Embedded deployments where minimising dependencies is important
- High read throughput with infrequent writes

## Limitations

- Not safe for concurrent writes from multiple processes
- Not suitable for distributed or shared-access setups
- File size grows unboundedly as tokens accumulate; there is no built-in compaction

## Related

- [Storage: RDBMS](storage-rdbms.md)
- [Storage backends comparison](../../concepts/storage-backends.md)
