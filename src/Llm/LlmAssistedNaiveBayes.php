<?php

namespace ByJG\TextClassifier\Llm;

use ByJG\TextClassifier\NaiveBayes\NaiveBayes;

class LlmAssistedNaiveBayes
{
    public function __construct(
        private NaiveBayes $nb,
        private LlmInterface $llm,
        private ConfigLlm $config = new ConfigLlm()
    ) {}

    /**
     * Classify text, escalating to LLM when confidence is insufficient.
     *
     * @return array<string, float>
     */
    public function classify(string $text): array
    {
        $scores = $this->nb->classify($text);

        $vals   = array_values($scores);
        $top    = $vals[0] ?? 0.0;
        $second = $vals[1] ?? 0.0;

        $shouldEscalate = empty($scores)
            || $top < $this->config->getMinConfidence()
            || ($top - $second) < $this->config->getMinMargin();

        if ($shouldEscalate) {
            $categories = $this->nb->getCategories();

            if (empty($categories)) {
                return $scores;
            }

            $decision = $this->llm->classify($text, $categories);

            if ($this->config->isAutoLearn()) {
                $this->nb->train($text, $decision);
                $scores = $this->nb->classify($text);
            }
        }

        return $scores;
    }

}
