<?php

namespace ByJG\TextClassifier\Llm;

class ConfigLlm
{
    private float $lowerBound    = 0.35;
    private float $upperBound    = 0.65;
    private float $minConfidence = 0.65;
    private float $minMargin     = 0.15;
    private bool  $autoLearn     = true;

    public function getLowerBound(): float
    {
        return $this->lowerBound;
    }

    public function setLowerBound(float $lowerBound): static
    {
        $this->lowerBound = $lowerBound;
        return $this;
    }

    public function getUpperBound(): float
    {
        return $this->upperBound;
    }

    public function setUpperBound(float $upperBound): static
    {
        $this->upperBound = $upperBound;
        return $this;
    }

    public function getMinConfidence(): float
    {
        return $this->minConfidence;
    }

    public function setMinConfidence(float $minConfidence): static
    {
        $this->minConfidence = $minConfidence;
        return $this;
    }

    public function getMinMargin(): float
    {
        return $this->minMargin;
    }

    public function setMinMargin(float $minMargin): static
    {
        $this->minMargin = $minMargin;
        return $this;
    }

    public function isAutoLearn(): bool
    {
        return $this->autoLearn;
    }

    public function setAutoLearn(bool $autoLearn): static
    {
        $this->autoLearn = $autoLearn;
        return $this;
    }
}
