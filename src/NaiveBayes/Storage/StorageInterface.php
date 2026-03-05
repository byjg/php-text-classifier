<?php

namespace B8\NaiveBayes\Storage;

interface StorageInterface
{
    /**
     * @return string[]
     */
    public function getCategories(): array;

    public function getDocCount(string $category): int;

    public function getTotalDocCount(): int;

    public function incrementDocCount(string $category): void;

    public function decrementDocCount(string $category): void;

    public function getTokenCount(string $token, string $category): int;

    public function getTotalTokenCount(string $token): int;

    /**
     * @param string[] $tokens
     * @return array<string, array<string, int>>  token => [category => count]
     */
    public function getTokenCounts(array $tokens): array;

    public function incrementToken(string $token, string $category, int $count = 1): void;

    public function decrementToken(string $token, string $category, int $count = 1): void;
}
