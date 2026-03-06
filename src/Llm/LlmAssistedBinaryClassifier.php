<?php

namespace ByJG\TextClassifier\Llm;

use ByJG\TextClassifier\BinaryClassifier;

class LlmAssistedBinaryClassifier
{
    public function __construct(
        private BinaryClassifier $classifier,
        private LlmInterface $llm,
        private ConfigLlm $config = new ConfigLlm()
    ) {}

    /**
     * Classify text, escalating to LLM when the score is uncertain.
     *
     * @return float|string Returns the spam probability (0–1) or a BinaryClassifier error constant.
     */
    public function classify(string $text): float|string
    {
        $score = $this->classifier->classify($text);

        if (
            is_float($score)
            && $score >= $this->config->getLowerBound()
            && $score <= $this->config->getUpperBound()
        ) {
            $decision = $this->llm->classify($text, [BinaryClassifier::SPAM, BinaryClassifier::HAM]);

            if ($this->config->isAutoLearn()) {
                $this->classifier->learn($text, $decision);
                $score = $this->classifier->classify($text);
            }
        }

        return $score;
    }

}
