---
sidebar_position: 7
---

# Database Schema

Both the BinaryClassifier spam filter and NaiveBayes RDBMS backends use the same database, managed by `byjg/migration`. Migrations are located in `db/migrations/`.

## Migration files

| File | Version | Description |
|---|---|---|
| `db/migrations/up/00001.sql` | 1 | Creates `tc_wordlist` and seeds internal variables |
| `db/migrations/up/00002.sql` | 2 | Creates `nb_internals` and `nb_wordlist` |
| `db/migrations/down/00001.sql` | — | Drops `tc_wordlist` |
| `db/migrations/down/00002.sql` | — | Drops `nb_wordlist` and `nb_internals` |

## Tables

### `tc_wordlist` — BinaryClassifier spam filter tokens

```sql
CREATE TABLE tc_wordlist (
    token      VARCHAR(255) NOT NULL,
    count_ham  INTEGER DEFAULT NULL,
    count_spam INTEGER DEFAULT NULL,
    PRIMARY KEY (token)
);
```

| Column | Type | Description |
|---|---|---|
| `token` | `VARCHAR(255)` | The word or URI fragment |
| `count_ham` | `INTEGER` | Times this token appeared in ham training texts |
| `count_spam` | `INTEGER` | Times this token appeared in spam training texts |

**Internal rows** (seeded by migration):

| token | count_ham | count_spam | Purpose |
|---|---|---|---|
| `tc*dbversion` | `3` | `NULL` | Schema version check |
| `tc*texts` | `0` | `0` | Total ham / spam text counts |

### `nb_internals` — NaiveBayes document counts

```sql
CREATE TABLE nb_internals (
    category  VARCHAR(255) NOT NULL,
    doc_count INTEGER NOT NULL DEFAULT 0,
    PRIMARY KEY (category)
);
```

| Column | Type | Description |
|---|---|---|
| `category` | `VARCHAR(255)` | Category name (user-defined) |
| `doc_count` | `INTEGER` | Number of texts trained under this category |

Rows are created on first `train()` for a category and deleted when all samples are untrained.

### `nb_wordlist` — NaiveBayes token counts

```sql
CREATE TABLE nb_wordlist (
    token     VARCHAR(255) NOT NULL,
    category  VARCHAR(255) NOT NULL,
    count     INTEGER NOT NULL DEFAULT 0,
    PRIMARY KEY (token, category)
);
```

| Column | Type | Description |
|---|---|---|
| `token` | `VARCHAR(255)` | The word token |
| `category` | `VARCHAR(255)` | Category name |
| `count` | `INTEGER` | Cumulative occurrence count of this token in this category |

## Running migrations

Migrations are applied automatically by `createDatabase()`:

```php
// BinaryClassifier spam filter
$storage = new \ByJG\TextClassifier\Storage\Rdbms($uri, $degenerator);
$storage->createDatabase();

// NaiveBayes
$storage = new \ByJG\TextClassifier\NaiveBayes\Storage\Rdbms($uri);
$storage->createDatabase();
```

Both methods call `Migration::reset()`, which:
1. Drops the migration version table (if it exists)
2. Runs all `up` scripts in numerical order

**Warning:** `createDatabase()` is destructive. Do not call it on a database that already contains training data.

## Shared database

Both engines can coexist in the same database. Their tables are independent. Run `createDatabase()` once from either implementation — the migration applies all scripts regardless of which class calls it.
