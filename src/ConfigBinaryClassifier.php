<?php

namespace ByJG\TextClassifier;

class ConfigBinaryClassifier
{
    private $use_relevant = 15;
    private $min_dev = 0.2;
    private $rob_s = 0.3;
    private $rob_x = 0.5;

    /**
     * @return int
     */
    public function getUseRelevant()
    {
        return $this->use_relevant;
    }

    /**
     * @param int $use_relevant
     * @return ConfigBinaryClassifier
     */
    public function setUseRelevant($use_relevant)
    {
        $this->use_relevant = (int) $use_relevant;
        return $this;
    }

    /**
     * @return float
     */
    public function getMinDev()
    {
        return $this->min_dev;
    }

    /**
     * @param float $min_dev
     * @return ConfigBinaryClassifier
     */
    public function setMinDev($min_dev)
    {
        $this->min_dev = $min_dev;
        return $this;
    }

    /**
     * @return float
     */
    public function getRobS()
    {
        return $this->rob_s;
    }

    /**
     * @param float $rob_s
     * @return ConfigBinaryClassifier
     */
    public function setRobS($rob_s)
    {
        $this->rob_s = (float) $rob_s;
        return $this;
    }

    /**
     * @return float
     */
    public function getRobX()
    {
        return $this->rob_x;
    }

    /**
     * @param float $rob_x
     * @return ConfigBinaryClassifier
     */
    public function setRobX($rob_x)
    {
        $this->rob_x = (float) $rob_x;
        return $this;
    }
}
