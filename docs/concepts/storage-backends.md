---
sidebar_position: 5
---

# Storage Backends

Both the B8 spam filter and NaiveBayes classifier use pluggable storage backends. This page compares all available options.

## B8 (spam filter) backends

| Backend | Class | Persistence | External dependency |
|---|---|---|---|
| RDBMS | `B8\Storage\Rdbms` | Database | `byjg/micro-orm` (bundled) |
| BerkeleyDB | `B8\Storage\Dba` | File | `ext-dba` PHP extension |

## NaiveBayes backends

| Backend | Class | Persistence | External dependency |
|---|---|---|---|
| Memory | `B8\NaiveBayes\Storage\Memory` | Optional (JSON file) | None |
| RDBMS | `B8\NaiveBayes\Storage\Rdbms` | Database | `byjg/anydataset-db` (bundled) |

## Feature comparison

| Feature | B8 Rdbms | B8 Dba | NB Memory | NB Rdbms |
|---|---|---|---|---|
| Persistent by default | Yes | Yes | No (opt-in via `save()`) | Yes |
| Multiple process safe | Yes | No | No | Yes |
| External server required | Optional | No | No | Optional |
| SQLite support | Yes | — | — | Yes |
| MySQL support | Yes | — | — | Yes |
| PostgreSQL support | Yes | — | — | Yes |
| `createDatabase()` | Yes | Yes | Not needed | Yes |
| Schema migrations | Yes | No | No | Yes |

## Choosing a backend

### Use `B8\Storage\Rdbms` when:
- You have an existing relational database
- Training data needs to survive process restarts
- Multiple processes or servers share the same filter
- You want SQL-level inspection of token data

### Use `B8\Storage\Dba` when:
- No database server is available
- You want a simple, self-contained file
- Single-process only

### Use `NaiveBayes\Storage\Memory` when:
- Prototyping or testing
- Model is trained once and loaded per process
- Low-overhead inference without database connections

### Use `NaiveBayes\Storage\Rdbms` when:
- Model is updated from multiple processes
- You need durable, consistent storage
- Sharing the database with B8

## Implementing a custom storage backend

### For B8

Implement `B8\Storage\StorageInterface`. The key methods are:

```php
public function storageOpen(): void;
public function storageClose(): void;
public function storageRetrieve(array|string $tokens): array; // returns Word[]
public function storagePut(Word $word): void;
public function storageUpdate(Word $word): void;
public function storageDel(string $token): void;
```

Extend `B8\Storage\Base` to inherit the `getInternals()`, `getTokens()`, and `processText()` implementations.

### For NaiveBayes

Implement `B8\NaiveBayes\Storage\StorageInterface` directly:

```php
public function getCategories(): array;
public function getDocCount(string $category): int;
public function getTotalDocCount(): int;
public function incrementDocCount(string $category): void;
public function decrementDocCount(string $category): void;
public function getTokenCount(string $token, string $category): int;
public function getTotalTokenCount(string $token): int;
public function getTokenCounts(array $tokens): array;
public function incrementToken(string $token, string $category, int $count = 1): void;
public function decrementToken(string $token, string $category, int $count = 1): void;
```
