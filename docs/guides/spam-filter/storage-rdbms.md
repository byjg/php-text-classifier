---
sidebar_position: 3
---

# Storage: SQLite / MySQL / PostgreSQL

`B8\Storage\Rdbms` persists the word list to a relational database using `byjg/micro-orm`. It supports SQLite, MySQL, and PostgreSQL via a URI connection string.

## Setup

### SQLite

```php
use B8\Degenerator\ConfigDegenerator;
use B8\Degenerator\StandardDegenerator;
use B8\Storage\Rdbms;
use ByJG\Util\Uri;

$storage = new Rdbms(
    new Uri('sqlite:///var/data/spam.db'),
    new StandardDegenerator(new ConfigDegenerator())
);

// Run once to create the schema
$storage->createDatabase();
```

### MySQL

```php
$storage = new Rdbms(
    new Uri('mysql://user:password@localhost/mydb'),
    new StandardDegenerator(new ConfigDegenerator())
);
$storage->createDatabase();
```

### PostgreSQL

```php
$storage = new Rdbms(
    new Uri('pgsql://user:password@localhost/mydb'),
    new StandardDegenerator(new ConfigDegenerator())
);
$storage->createDatabase();
```

## createDatabase()

`createDatabase()` uses `byjg/migration` to apply the schema migrations located in the `db/` directory of this package. It calls `reset()` internally, which means:

- Drops and recreates the migration version table
- Runs all `up` migration scripts from scratch

**Call this once when setting up a new database.** Do not call it on a database that already contains training data — it will wipe the existing data.

## URI format

```
scheme://[user[:password]@]host[:port]/database
```

| Scheme | Driver |
|---|---|
| `sqlite://` | SQLite (path after `//` is the file path) |
| `mysql://` | MySQL / MariaDB |
| `pgsql://` | PostgreSQL |

## Database table

The `b8_wordlist` table stores one row per unique token:

```sql
CREATE TABLE b8_wordlist (
    token      VARCHAR(255) NOT NULL,
    count_ham  INTEGER DEFAULT NULL,
    count_spam INTEGER DEFAULT NULL,
    PRIMARY KEY (token)
);
```

Two internal rows are seeded automatically:

| token | purpose |
|---|---|
| `b8*dbversion` | Schema version (value: `3`) |
| `b8*texts` | Total ham/spam text count |

## When to use this backend

- Production deployments requiring durability
- Shared access across multiple processes or servers
- Large training sets where memory footprint matters
- When you already have a relational database in your infrastructure

## Related

- [Storage: BerkeleyDB](storage-dba.md)
- [Database schema reference](../../reference/database-schema.md)
