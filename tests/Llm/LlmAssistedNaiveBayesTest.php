<?php

namespace Test\Llm;

use ByJG\TextClassifier\Lexer\ConfigLexer;
use ByJG\TextClassifier\Lexer\StandardLexer;
use ByJG\TextClassifier\Llm\ConfigLlm;
use ByJG\TextClassifier\Llm\LlmAssistedNaiveBayes;
use ByJG\TextClassifier\Llm\LlmInterface;
use ByJG\TextClassifier\NaiveBayes\NaiveBayes;
use ByJG\TextClassifier\NaiveBayes\Storage\Memory;
use PHPUnit\Framework\TestCase;

class LlmAssistedNaiveBayesTest extends TestCase
{
    private Memory $storage;
    private NaiveBayes $nb;

    protected function setUp(): void
    {
        $this->storage = new Memory();
        $this->nb      = new NaiveBayes($this->storage, new StandardLexer(new ConfigLexer()));
    }

    public function testLowConfidenceEscalatesToLlm(): void
    {
        // Train with very limited data so scores are close
        $this->nb->train('The cat sat on the mat', 'animals');
        $this->nb->train('PHP is a programming language', 'tech');

        $llm = $this->createMock(LlmInterface::class);
        $llm->expects($this->once())
            ->method('classify')
            ->willReturn('tech');

        // Use tight confidence threshold so the result always escalates
        $config   = (new ConfigLlm())->setMinConfidence(0.99)->setMinMargin(0.99);
        $assisted = new LlmAssistedNaiveBayes($this->nb, $llm, $config);

        $result = $assisted->classify('programming language');

        // After LLM decided 'tech' and autoLearn trained it, tech should dominate
        $this->assertNotEmpty($result);
        $this->assertEquals('tech', array_key_first($result));
    }

    public function testHighConfidenceSkipsLlm(): void
    {
        // Train heavily so one category dominates with high confidence
        for ($i = 0; $i < 5; $i++) {
            $this->nb->train('The cat sat on the mat', 'animals');
        }
        $this->nb->train('PHP is a programming language', 'tech');

        $llm = $this->createMock(LlmInterface::class);
        $llm->expects($this->never())->method('classify');

        $config   = (new ConfigLlm())->setMinConfidence(0.5)->setMinMargin(0.05);
        $assisted = new LlmAssistedNaiveBayes($this->nb, $llm, $config);

        $result = $assisted->classify('cat mat');

        $this->assertEquals('animals', array_key_first($result));
    }

    public function testEmptyResultEscalatesToLlm(): void
    {
        // With a single category, NaiveBayes.classify() returns [] (inversedDocCount === 0).
        // The LLM is consulted and returns a NEW category; after autoLearn trains it,
        // storage now has 2 categories and re-classification returns a non-empty result.
        $freshStorage = new Memory();
        $freshNb      = new NaiveBayes($freshStorage, new StandardLexer(new ConfigLexer()));

        $freshNb->train('The cat sat on the mat', 'animals');

        $llm = $this->createMock(LlmInterface::class);
        $llm->expects($this->once())
            ->method('classify')
            ->willReturn('tech'); // introduces a second category

        $config   = new ConfigLlm(); // defaults: escalate when scores empty
        $assisted = new LlmAssistedNaiveBayes($freshNb, $llm, $config);

        // classify() returns [] (single category → inversedDocCount=0).
        // After LLM returns 'tech' and autoLearn trains it, two categories exist
        // and re-classification produces a real result.
        $result = $assisted->classify('PHP is a programming language');

        $this->assertNotEmpty($result);
    }

    public function testAutoLearnFalseCallsLlmButDoesNotTrain(): void
    {
        $this->nb->train('The cat sat on the mat', 'animals');
        $this->nb->train('PHP is a programming language', 'tech');

        $llm = $this->createMock(LlmInterface::class);
        $llm->expects($this->once())
            ->method('classify')
            ->willReturn('tech');

        $config   = (new ConfigLlm())->setMinConfidence(0.99)->setMinMargin(0.99)->setAutoLearn(false);
        $assisted = new LlmAssistedNaiveBayes($this->nb, $llm, $config);

        $scoresBefore = $this->nb->classify('some new text');
        $assisted->classify('some new text');
        $scoresAfter  = $this->nb->classify('some new text');

        // Storage unchanged because autoLearn=false
        $this->assertEquals($scoresBefore, $scoresAfter);
    }
}
