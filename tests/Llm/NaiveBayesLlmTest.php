<?php

namespace Test\Llm;

use ByJG\TextClassifier\Lexer\ConfigLexer;
use ByJG\TextClassifier\Lexer\StandardLexer;
use ByJG\TextClassifier\Llm\ConfigLlm;
use ByJG\TextClassifier\Llm\LlmInterface;
use ByJG\TextClassifier\NaiveBayes\NaiveBayes;
use ByJG\TextClassifier\NaiveBayes\Storage\Memory;
use PHPUnit\Framework\TestCase;

class NaiveBayesLlmTest extends TestCase
{
    private function makeNb(?LlmInterface $llm = null, ?ConfigLlm $config = null): NaiveBayes
    {
        return new NaiveBayes(
            new Memory(),
            new StandardLexer(new ConfigLexer()),
            llm: $llm,
            configLlm: $config,
        );
    }

    public function testLowConfidenceEscalatesToLlm(): void
    {
        $llm = $this->createMock(LlmInterface::class);
        $llm->expects($this->once())
            ->method('classify')
            ->willReturn('tech');

        $nb = $this->makeNb($llm, (new ConfigLlm())->setMinConfidence(0.99)->setMinMargin(0.99));
        $nb->train('The cat sat on the mat', 'animals');
        $nb->train('PHP is a programming language', 'tech');

        $result = $this->makeNb($llm, (new ConfigLlm())->setMinConfidence(0.99)->setMinMargin(0.99));

        // Rebuild with same mock to use once() expectation properly
        $storage = new Memory();
        $nb2 = new NaiveBayes(
            $storage,
            new StandardLexer(new ConfigLexer()),
            llm: $llm,
            configLlm: (new ConfigLlm())->setMinConfidence(0.99)->setMinMargin(0.99),
        );
        $nb2->train('The cat sat on the mat', 'animals');
        $nb2->train('PHP is a programming language', 'tech');

        $result = $nb2->classify('programming language');

        $this->assertNotNull($result);
        $this->assertTrue($result->escalated);
        $this->assertEquals('tech', $result->llmDecision);
        $this->assertEquals('tech', $result->choice);
    }

    public function testHighConfidenceSkipsLlm(): void
    {
        $llm = $this->createMock(LlmInterface::class);
        $llm->expects($this->never())->method('classify');

        $storage = new Memory();
        $nb = new NaiveBayes(
            $storage,
            new StandardLexer(new ConfigLexer()),
            llm: $llm,
            configLlm: (new ConfigLlm())->setMinConfidence(0.5)->setMinMargin(0.05),
        );

        for ($i = 0; $i < 5; $i++) {
            $nb->train('The cat sat on the mat', 'animals');
        }
        $nb->train('PHP is a programming language', 'tech');

        $result = $nb->classify('cat mat');

        $this->assertEquals('animals', $result->choice);
        $this->assertFalse($result->escalated);
    }

    public function testEmptyResultEscalatesToLlm(): void
    {
        $llm = $this->createMock(LlmInterface::class);
        $llm->expects($this->once())
            ->method('classify')
            ->willReturn('tech');

        $storage = new Memory();
        $nb = new NaiveBayes(
            $storage,
            new StandardLexer(new ConfigLexer()),
            llm: $llm,
        );

        // Single category → inversedDocCount=0 → classify() returns []
        // LLM introduces 'tech' as second category, making re-classification possible
        $nb->train('The cat sat on the mat', 'animals');

        $result = $nb->classify('PHP is a programming language');

        $this->assertNotNull($result);
        $this->assertTrue($result->escalated);
        $this->assertEquals('tech', $result->llmDecision);
    }

    public function testAutoLearnFalseCallsLlmButDoesNotTrain(): void
    {
        $llm = $this->createMock(LlmInterface::class);
        $llm->expects($this->once())
            ->method('classify')
            ->willReturn('tech');

        $storage = new Memory();
        $nb = new NaiveBayes(
            $storage,
            new StandardLexer(new ConfigLexer()),
            llm: $llm,
            configLlm: (new ConfigLlm())->setMinConfidence(0.99)->setMinMargin(0.99)->setAutoLearn(false),
        );
        $nb->train('The cat sat on the mat', 'animals');
        $nb->train('PHP is a programming language', 'tech');

        $before = $storage->getCategories();
        $nb->classify('some new text');
        $after = $storage->getCategories();

        // autoLearn=false → no new training → storage unchanged
        $this->assertEquals($before, $after);
    }
}
