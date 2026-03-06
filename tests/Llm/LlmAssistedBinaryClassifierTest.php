<?php

namespace Test\Llm;

use ByJG\TextClassifier\BinaryClassifier;
use ByJG\TextClassifier\ConfigBinaryClassifier;
use ByJG\TextClassifier\Degenerator\ConfigDegenerator;
use ByJG\TextClassifier\Degenerator\StandardDegenerator;
use ByJG\TextClassifier\Lexer\ConfigLexer;
use ByJG\TextClassifier\Lexer\StandardLexer;
use ByJG\TextClassifier\Llm\ConfigLlm;
use ByJG\TextClassifier\Llm\LlmAssistedBinaryClassifier;
use ByJG\TextClassifier\Llm\LlmInterface;
use ByJG\TextClassifier\Storage\Dba;
use PHPUnit\Framework\TestCase;

class LlmAssistedBinaryClassifierTest extends TestCase
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

        $assisted = new LlmAssistedBinaryClassifier($this->classifier, $llm);

        // Untrained classifier returns 0.5, which is in the uncertain range
        $result = $assisted->classify('buy cheap pills now');

        $this->assertIsFloat($result);
        // After LLM decided spam and autoLearn re-trained, score should shift toward spam
        $this->assertGreaterThan(0.5, $result);
    }

    public function testCertainScoreSkipsLlm(): void
    {
        // Train strongly so the score is outside [0.35, 0.65]
        for ($i = 0; $i < 5; $i++) {
            $this->classifier->learn('buy cheap pills now', BinaryClassifier::SPAM);
        }

        $llm = $this->createMock(LlmInterface::class);
        $llm->expects($this->never())->method('classify');

        $assisted = new LlmAssistedBinaryClassifier($this->classifier, $llm);
        $result   = $assisted->classify('buy cheap pills now');

        $this->assertIsFloat($result);
        $this->assertGreaterThan(0.65, $result);
    }

    public function testAutoLearnFalseCallsLlmButDoesNotLearn(): void
    {
        $llm = $this->createMock(LlmInterface::class);
        $llm->expects($this->once())
            ->method('classify')
            ->willReturn('ham');

        $config   = (new ConfigLlm())->setAutoLearn(false);
        $assisted = new LlmAssistedBinaryClassifier($this->classifier, $llm, $config);

        // Uncertain score triggers LLM
        $result = $assisted->classify('some ambiguous text');

        // With autoLearn=false, no learn() was called so score remains 0.5
        $this->assertEquals(0.5, $result);
    }
}
