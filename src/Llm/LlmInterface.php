<?php

namespace ByJG\TextClassifier\Llm;

interface LlmInterface
{
    /**
     * Classify text into one of the given categories.
     * Returns exactly one value from $categories.
     *
     * @param string[] $categories
     */
    public function classify(string $text, array $categories): string;
}
