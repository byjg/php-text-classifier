<?php

namespace ByJG\TextClassifier\NaiveBayes;

class ConfigNaiveBayes
{
    public function __construct(
        private float $robS = 1.0,
        private float $robX = 0.5
    ) {}

    public function getRobS(): float
    {
        return $this->robS;
    }

    public function getRobX(): float
    {
        return $this->robX;
    }
}
