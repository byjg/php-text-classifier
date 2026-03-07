<?php

namespace Test\Llm;

use ByJG\TextClassifier\BinaryClassifier;
use ByJG\TextClassifier\ConfigBinaryClassifier;
use ByJG\TextClassifier\Degenerator\ConfigDegenerator;
use ByJG\TextClassifier\Degenerator\StandardDegenerator;
use ByJG\TextClassifier\Lexer\ConfigLexer;
use ByJG\TextClassifier\Lexer\StandardLexer;
use ByJG\TextClassifier\Llm\ConfigLlm;
use ByJG\TextClassifier\Llm\LlmInterface;
use ByJG\TextClassifier\Storage\Dba;
use PHPUnit\Framework\TestCase;

class BinaryClassifierLlmTest extends TestCase
{
    private string $path = '/tmp/llm_b8_test.db';
    private BinaryClassifier $classifier;

    protected function setUp(): void
    {
        if (file_exists($this->path)) {
            unlink($this->path);
        }

        $lexer       = new StandardLexer(new ConfigLexer());
        $degenerator = new StandardDegenerator(new ConfigDegenerator());
        $storage     = new Dba($this->path, $degenerator);
        $storage->createDatabase();

        $this->classifier = new BinaryClassifier(new ConfigBinaryClassifier(), $storage, $lexer);
    }

    protected function tearDown(): void
    {
        if (file_exists($this->path)) {
            unlink($this->path);
        }
    }

    public function testUncertainScoreEscalatesToLlm(): void
    {
        $llm = $this->createMock(LlmInterface::class);
        $llm->expects($this->once())
            ->method('classify')
            ->willReturn('spam');

        // Build a fresh classifier with LLM injected
        $lexer       = new StandardLexer(new ConfigLexer());
        $degenerator = new StandardDegenerator(new ConfigDegenerator());
        $storage     = new Dba($this->path, $degenerator);

        $classifier = new BinaryClassifier(new ConfigBinaryClassifier(), $storage, $lexer, $llm);

        // Untrained classifier returns 0.5, which is in the uncertain range
        $result = $classifier->classify('buy cheap pills now');

        $this->assertInstanceOf(\ByJG\TextClassifier\ClassificationResult::class, $result);
        $this->assertTrue($result->escalated);
        $this->assertEquals('spam', $result->llmDecision);
        $this->assertGreaterThan(0.5, $result->score);
    }

    public function testCertainScoreSkipsLlm(): void
    {
        // Train strongly so the score is outside [0.35, 0.65]
        for ($i = 0; $i < 5; $i++) {
            $this->classifier->learn('buy cheap pills now', BinaryClassifier::SPAM);
        }

        $llm = $this->createMock(LlmInterface::class);
        $llm->expects($this->never())->method('classify');

        $lexer       = new StandardLexer(new ConfigLexer());
        $degenerator = new StandardDegenerator(new ConfigDegenerator());
        $storage     = new Dba($this->path, $degenerator);

        $classifier = new BinaryClassifier(new ConfigBinaryClassifier(), $storage, $lexer, $llm);
        $result     = $classifier->classify('buy cheap pills now');

        $this->assertInstanceOf(\ByJG\TextClassifier\ClassificationResult::class, $result);
        $this->assertFalse($result->escalated);
        $this->assertNull($result->llmDecision);
        $this->assertGreaterThan(0.65, $result->score);
    }

    public function testAutoLearnFalseCallsLlmButDoesNotLearn(): void
    {
        $llm = $this->createMock(LlmInterface::class);
        $llm->expects($this->once())
            ->method('classify')
            ->willReturn('ham');

        $lexer       = new StandardLexer(new ConfigLexer());
        $degenerator = new StandardDegenerator(new ConfigDegenerator());
        $storage     = new Dba($this->path, $degenerator);
        $config      = (new ConfigLlm())->setAutoLearn(false);

        $classifier = new BinaryClassifier(new ConfigBinaryClassifier(), $storage, $lexer, $llm, $config);

        // With autoLearn=false, no learn() call → score stays at 0.5
        $result = $classifier->classify('some ambiguous text');

        $this->assertInstanceOf(\ByJG\TextClassifier\ClassificationResult::class, $result);
        $this->assertTrue($result->escalated);
        $this->assertEquals('ham', $result->llmDecision);
        $this->assertEquals(0.5, $result->score);
    }
}
