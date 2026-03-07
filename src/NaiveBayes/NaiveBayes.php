<?php

namespace ByJG\TextClassifier\NaiveBayes;

use ByJG\TextClassifier\ClassificationResult;
use ByJG\TextClassifier\Lexer\LexerInterface;
use ByJG\TextClassifier\Llm\ConfigLlm;
use ByJG\TextClassifier\Llm\LlmInterface;
use ByJG\TextClassifier\NaiveBayes\Storage\StorageInterface;

class NaiveBayes
{
    public function __construct(
        private StorageInterface $storage,
        private LexerInterface $lexer,
        private ConfigNaiveBayes $config = new ConfigNaiveBayes(),
        private ?LlmInterface $llm = null,
        private ?ConfigLlm $configLlm = null,
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
     * Classify a text and return a ClassificationResult, or null when the storage has no categories.
     * If an LlmInterface was provided, escalates to it when the statistical confidence is insufficient.
     */
    public function classify(string $text): ?ClassificationResult
    {
        $statScores = $this->statisticalClassify($text);
        $scores     = $statScores;

        $llmDecision = null;
        $escalated   = false;

        if ($this->llm !== null) {
            $config = $this->configLlm ?? new ConfigLlm();
            $vals   = array_values($statScores);
            $top    = $vals[0] ?? 0.0;
            $second = $vals[1] ?? 0.0;

            $shouldEscalate = empty($statScores)
                || $top < $config->getMinConfidence()
                || ($top - $second) < $config->getMinMargin();

            if ($shouldEscalate) {
                $categories = $this->storage->getCategories();
                if (!empty($categories)) {
                    $llmDecision = $this->llm->classify($text, $categories);
                    $escalated   = true;
                    if ($config->isAutoLearn()) {
                        $this->train($text, $llmDecision);
                        $scores = $this->statisticalClassify($text);
                    }
                }
            }
        }

        if (empty($scores)) {
            return null;
        }

        return new ClassificationResult(
            choice:      array_key_first($scores),
            score:       (float) reset($scores),
            scores:      $scores,
            statScores:  $statScores,
            llmDecision: $llmDecision,
            escalated:   $escalated,
        );
    }

    /**
     * @return array<string, float>
     */
    private function statisticalClassify(string $text): array
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
