---
sidebar_position: 4
---

# Storage: RDBMS (NaiveBayes)

`B8\NaiveBayes\Storage\Rdbms` persists NaiveBayes training data to a relational database using direct SQL via `byjg/anydataset-db`. Supports SQLite, MySQL, and PostgreSQL.

## Setup

### SQLite

```php
use B8\NaiveBayes\Storage\Rdbms;
use ByJG\Util\Uri;

$storage = new Rdbms(new Uri('sqlite:///var/data/classifier.db'));

// Run once to create the schema
$storage->createDatabase();
```

### MySQL

```php
$storage = new Rdbms(new Uri('mysql://user:password@localhost/mydb'));
$storage->createDatabase();
```

### PostgreSQL

```php
$storage = new Rdbms(new Uri('pgsql://user:password@localhost/mydb'));
$storage->createDatabase();
```

## Use with NaiveBayes

```php
use B8\Lexer\ConfigLexer;
use B8\Lexer\StandardLexer;
use B8\NaiveBayes\NaiveBayes;

$nb = new NaiveBayes($storage, new StandardLexer(new ConfigLexer()));

$nb->train('Python is a programming language', 'tech');
$scores = $nb->classify('machine learning');
```

## createDatabase()

Runs `byjg/migration` against the `db/` directory, applying the `00002.sql` migration which creates:

```sql
CREATE TABLE nb_internals (
    category  VARCHAR(255) NOT NULL,
    doc_count INTEGER NOT NULL DEFAULT 0,
    PRIMARY KEY (category)
);

CREATE TABLE nb_wordlist (
    token     VARCHAR(255) NOT NULL,
    category  VARCHAR(255) NOT NULL,
    count     INTEGER NOT NULL DEFAULT 0,
    PRIMARY KEY (token, category)
);
```

**Note:** `createDatabase()` also creates the `b8_wordlist` table (migration `00001`) because the migration runs all scripts in order. This is harmless — the B8 spam filter tables are simply unused by `NaiveBayes`.

## Sharing a database with B8

If you use both the spam filter and the multi-class classifier in the same application, they can share the same database. Both engines use different tables (`b8_wordlist` vs `nb_wordlist`/`nb_internals`) and do not interfere.

```php
$uri = new Uri('sqlite:///var/data/shared.db');

// Run createDatabase() from either Rdbms implementation — both apply the full migration set
$storage = new \B8\NaiveBayes\Storage\Rdbms($uri);
$storage->createDatabase();

$spamStorage = new \B8\Storage\Rdbms($uri, $degenerator);
// No createDatabase() needed — already applied above
```

## Related

- [Storage: Memory](storage-memory.md)
- [Storage backends comparison](../../concepts/storage-backends.md)
- [Database schema reference](../../reference/database-schema.md)
