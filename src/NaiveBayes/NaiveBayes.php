<?php

namespace ByJG\TextClassifier\NaiveBayes;

use ByJG\TextClassifier\Lexer\LexerInterface;
use ByJG\TextClassifier\NaiveBayes\Storage\StorageInterface;

class NaiveBayes
{
    public function __construct(
        private StorageInterface $storage,
        private LexerInterface $lexer,
        private ConfigNaiveBayes $config = new ConfigNaiveBayes()
    ) {}

    /**
     * Train the classifier with a text belonging to a given category.
     */
    public function train(string $text, string $category): void
    {
        $tokens = $this->lexer->getTokens($text);
        if (!is_array($tokens)) {
            return;
        }

        $this->storage->incrementDocCount($category);
        foreach ($tokens as $token => $count) {
            $this->storage->incrementToken((string)$token, $category, $count);
        }
    }

    /**
     * Undo training for a text that was previously trained under the given category.
     */
    public function untrain(string $text, string $category): void
    {
        $tokens = $this->lexer->getTokens($text);
        if (!is_array($tokens)) {
            return;
        }

        $this->storage->decrementDocCount($category);
        foreach ($tokens as $token => $count) {
            $this->storage->decrementToken((string)$token, $category, $count);
        }
    }

    /**
     * @return string[]
     */
    public function getCategories(): array
    {
        return $this->storage->getCategories();
    }

    /**
     * Classify a text and return an array of category => score (0.0–1.0), sorted by score descending.
     *
     * @return array<string, float>
     */
    public function classify(string $text): array
    {
        $tokens = $this->lexer->getTokens($text);
        if (!is_array($tokens)) {
            return [];
        }

        $categories = $this->storage->getCategories();
        if (empty($categories)) {
            return [];
        }

        $totalDocCount = $this->storage->getTotalDocCount();
        $tokenCounts   = $this->storage->getTokenCounts(array_keys($tokens));

        $scores = [];

        foreach ($categories as $category) {
            $docCount         = $this->storage->getDocCount($category);
            $inversedDocCount = $totalDocCount - $docCount;

            if ($inversedDocCount === 0) {
                continue;
            }

            $logSum = 0.0;

            foreach (array_keys($tokens) as $token) {
                $categoryMap     = $tokenCounts[$token] ?? [];
                $totalTokenCount = array_sum($categoryMap);

                if ($totalTokenCount === 0) {
                    continue;
                }

                $tokenCount         = $categoryMap[$category] ?? 0;
                $inversedTokenCount = $totalTokenCount - $tokenCount;

                $tokenProbPos = $docCount > 0 ? $tokenCount / $docCount : 0.0;
                $tokenProbNeg = $inversedDocCount > 0 ? $inversedTokenCount / $inversedDocCount : 0.0;

                $sum = (float)$tokenProbPos + (float)$tokenProbNeg;
                if ($sum == 0.0) {
                    continue;
                }

                $probability = (float)$tokenProbPos / $sum;

                // Robinson smoothing — same formula used by b8 binary classifier
                $probability = (
                    ($this->config->getRobS() * $this->config->getRobX()) +
                    ((float)$totalTokenCount * $probability)
                ) / ($this->config->getRobS() + (float)$totalTokenCount);

                $probability = max(0.01, min(0.99, $probability));

                $logSum += log(1.0 - $probability) - log($probability);
            }

            $scores[$category] = 1.0 / (1.0 + exp($logSum));
        }

        arsort($scores, SORT_NUMERIC);

        return $scores;
    }
}
