<?php

namespace ByJG\TextClassifier\NaiveBayes\Storage;

class Memory implements StorageInterface
{
    /** @var array<string, int> category => doc_count */
    private array $docs = [];

    /** @var array<string, array<string, int>> token => [category => count] */
    private array $tokens = [];

    #[\Override]
    public function getCategories(): array
    {
        return array_keys($this->docs);
    }

    #[\Override]
    public function getDocCount(string $category): int
    {
        return $this->docs[$category] ?? 0;
    }

    #[\Override]
    public function getTotalDocCount(): int
    {
        return array_sum($this->docs);
    }

    #[\Override]
    public function incrementDocCount(string $category): void
    {
        $this->docs[$category] = ($this->docs[$category] ?? 0) + 1;
    }

    #[\Override]
    public function decrementDocCount(string $category): void
    {
        $current = $this->docs[$category] ?? 0;
        if ($current <= 1) {
            unset($this->docs[$category]);
        } else {
            $this->docs[$category] = $current - 1;
        }
    }

    #[\Override]
    public function getTokenCount(string $token, string $category): int
    {
        return $this->tokens[$token][$category] ?? 0;
    }

    #[\Override]
    public function getTotalTokenCount(string $token): int
    {
        return isset($this->tokens[$token]) ? array_sum($this->tokens[$token]) : 0;
    }

    #[\Override]
    public function getTokenCounts(array $tokens): array
    {
        $result = [];
        foreach ($tokens as $token) {
            $result[$token] = $this->tokens[$token] ?? [];
        }
        return $result;
    }

    #[\Override]
    public function incrementToken(string $token, string $category, int $count = 1): void
    {
        $this->tokens[$token][$category] = ($this->tokens[$token][$category] ?? 0) + $count;
    }

    #[\Override]
    public function decrementToken(string $token, string $category, int $count = 1): void
    {
        if (!isset($this->tokens[$token][$category])) {
            return;
        }

        $new = $this->tokens[$token][$category] - $count;
        if ($new <= 0) {
            unset($this->tokens[$token][$category]);
            if (empty($this->tokens[$token])) {
                unset($this->tokens[$token]);
            }
        } else {
            $this->tokens[$token][$category] = $new;
        }
    }

    public function save(string $file): void
    {
        file_put_contents($file, json_encode([
            'docs'   => $this->docs,
            'tokens' => $this->tokens,
        ], JSON_THROW_ON_ERROR));
    }

    public function load(string $file): void
    {
        if (!file_exists($file)) {
            throw new \InvalidArgumentException("File not found: $file");
        }
        /** @var array{docs: array<string, int>, tokens: array<string, array<string, int>>} $data */
        $data = json_decode((string)file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
        $this->docs   = $data['docs'];
        $this->tokens = $data['tokens'];
    }
}
