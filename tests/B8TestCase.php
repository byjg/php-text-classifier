<?php

namespace Test;

use ByJG\TextClassifier\BinaryClassifier;
use PHPUnit\Framework\TestCase;

abstract class B8TestCase extends TestCase
{
    /**
     * @var BinaryClassifier
     */
    protected $b8 = null;

    protected $path;

    protected function tearDown(): void
    {
        if (file_exists($this->path)) {
            unlink($this->path);
        }
    }

    public function testLearnAndClassify()
    {
        $result = $this->b8->classify("this is a bad text");
        $this->assertEquals(0.5, $result->score);

        $this->b8->learn("this is a bad text", BinaryClassifier::SPAM);

        $expected = 0.88461538;
        $result = $this->b8->classify("talking bad");
        $this->assertGreaterThanOrEqual($expected, $result->score);
        $this->assertLessThanOrEqual($expected + 0.01, $result->score);

        $this->b8->learn("john is a good person", BinaryClassifier::HAM);

        $expected = 0.11538461;
        $result = $this->b8->classify("talking about john");
        $this->assertGreaterThanOrEqual($expected, $result->score);
        $this->assertLessThanOrEqual($expected + 0.01, $result->score);

        $expected = 0.41649054;
        $result = $this->b8->classify("talking bad person john");
        $this->assertGreaterThanOrEqual($expected, $result->score);
        $this->assertLessThanOrEqual($expected + 0.01, $result->score);
    }

}
