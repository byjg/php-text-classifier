<?php

namespace B8\NaiveBayes\Storage;

use ByJG\AnyDataset\Db\DatabaseExecutor;
use ByJG\AnyDataset\Db\Factory;
use ByJG\DbMigration\Database\MySqlDatabase;
use ByJG\DbMigration\Database\PgsqlDatabase;
use ByJG\DbMigration\Database\SqliteDatabase;
use ByJG\DbMigration\Migration;
use ByJG\Util\Uri;

class Rdbms implements StorageInterface
{
    private DatabaseExecutor $db;
    private Uri $uri;

    public function __construct(Uri|string $uri)
    {
        $this->uri = $uri instanceof Uri ? $uri : new Uri($uri);
        $driver    = Factory::getDbRelationalInstance((string)$this->uri);
        $this->db  = new DatabaseExecutor($driver);
    }

    /**
     * Creates the required database tables using migrations.
     * Call this once to set up a new database.
     */
    public function createDatabase(): void
    {
        Migration::registerDatabase(SqliteDatabase::class);
        Migration::registerDatabase(MySqlDatabase::class);
        Migration::registerDatabase(PgsqlDatabase::class);

        $migration = new Migration($this->uri, __DIR__ . '/../../../db');
        $migration->reset();

        // Reinitialize after reset — migration may recreate the underlying database file
        $driver   = Factory::getDbRelationalInstance((string)$this->uri);
        $this->db = new DatabaseExecutor($driver);
    }

    #[\Override]
    public function getCategories(): array
    {
        $iterator   = $this->db->getIterator('SELECT category FROM nb_internals');
        $categories = [];
        foreach ($iterator as $row) {
            $categories[] = (string)$row->get('category');
        }
        return $categories;
    }

    #[\Override]
    public function getDocCount(string $category): int
    {
        $value = $this->db->getScalar(
            'SELECT doc_count FROM nb_internals WHERE category = :category',
            ['category' => $category]
        );
        return $value === false ? 0 : (int)$value;
    }

    #[\Override]
    public function getTotalDocCount(): int
    {
        $value = $this->db->getScalar('SELECT SUM(doc_count) FROM nb_internals');
        return $value === false ? 0 : (int)$value;
    }

    #[\Override]
    public function incrementDocCount(string $category): void
    {
        $existing = $this->db->getScalar(
            'SELECT doc_count FROM nb_internals WHERE category = :category',
            ['category' => $category]
        );

        if ($existing === false) {
            $this->db->execute(
                'INSERT INTO nb_internals (category, doc_count) VALUES (:category, 1)',
                ['category' => $category]
            );
        } else {
            $this->db->execute(
                'UPDATE nb_internals SET doc_count = doc_count + 1 WHERE category = :category',
                ['category' => $category]
            );
        }
    }

    #[\Override]
    public function decrementDocCount(string $category): void
    {
        $current = $this->getDocCount($category);

        if ($current <= 1) {
            $this->db->execute(
                'DELETE FROM nb_internals WHERE category = :category',
                ['category' => $category]
            );
        } else {
            $this->db->execute(
                'UPDATE nb_internals SET doc_count = doc_count - 1 WHERE category = :category',
                ['category' => $category]
            );
        }
    }

    #[\Override]
    public function getTokenCount(string $token, string $category): int
    {
        $value = $this->db->getScalar(
            'SELECT count FROM nb_wordlist WHERE token = :token AND category = :category',
            ['token' => $token, 'category' => $category]
        );
        return $value === false ? 0 : (int)$value;
    }

    #[\Override]
    public function getTotalTokenCount(string $token): int
    {
        $value = $this->db->getScalar(
            'SELECT SUM(count) FROM nb_wordlist WHERE token = :token',
            ['token' => $token]
        );
        return $value === false ? 0 : (int)$value;
    }

    #[\Override]
    public function getTokenCounts(array $tokens): array
    {
        if (empty($tokens)) {
            return [];
        }

        $placeholders = implode(',', array_map(fn(int $i) => ":t$i", array_keys($tokens)));
        $params       = [];
        foreach (array_values($tokens) as $i => $token) {
            $params["t$i"] = $token;
        }

        $iterator = $this->db->getIterator(
            "SELECT token, category, count FROM nb_wordlist WHERE token IN ($placeholders)",
            $params
        );

        $result = array_fill_keys($tokens, []);
        foreach ($iterator as $row) {
            $t          = (string)$row->get('token');
            $c          = (string)$row->get('category');
            $result[$t][$c] = (int)$row->get('count');
        }
        return $result;
    }

    #[\Override]
    public function incrementToken(string $token, string $category, int $count = 1): void
    {
        $existing = $this->db->getScalar(
            'SELECT count FROM nb_wordlist WHERE token = :token AND category = :category',
            ['token' => $token, 'category' => $category]
        );

        if ($existing === false) {
            $this->db->execute(
                'INSERT INTO nb_wordlist (token, category, count) VALUES (:token, :category, :count)',
                ['token' => $token, 'category' => $category, 'count' => $count]
            );
        } else {
            $this->db->execute(
                'UPDATE nb_wordlist SET count = count + :count WHERE token = :token AND category = :category',
                ['token' => $token, 'category' => $category, 'count' => $count]
            );
        }
    }

    #[\Override]
    public function decrementToken(string $token, string $category, int $count = 1): void
    {
        $current = $this->getTokenCount($token, $category);

        if ($current <= $count) {
            $this->db->execute(
                'DELETE FROM nb_wordlist WHERE token = :token AND category = :category',
                ['token' => $token, 'category' => $category]
            );
        } else {
            $this->db->execute(
                'UPDATE nb_wordlist SET count = count - :count WHERE token = :token AND category = :category',
                ['token' => $token, 'category' => $category, 'count' => $count]
            );
        }
    }
}
