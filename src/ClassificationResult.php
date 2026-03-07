<?php

namespace ByJG\TextClassifier;

class ClassificationResult
{
    /**
     * @param string               $choice      Final winning category (e.g. 'spam', 'tech').
     * @param float                $score       Final score of the winning category (0.0–1.0).
     * @param array<string, float> $scores      All final category scores, sorted descending.
     * @param array<string, float> $statScores  Raw statistical scores before any LLM escalation.
     * @param string|null          $llmDecision The category the LLM chose, or null if not escalated.
     * @param bool                 $escalated   True when the LLM was consulted for this classification.
     */
    public function __construct(
        public readonly string  $choice,
        public readonly float   $score,
        public readonly array   $scores,
        public readonly array   $statScores,
        public readonly ?string $llmDecision = null,
        public readonly bool    $escalated   = false,
    ) {}
}
