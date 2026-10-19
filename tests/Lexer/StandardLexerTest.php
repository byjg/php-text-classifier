<?php

namespace Tests\Lexer;

use ByJG\TextClassifier\Lexer\ConfigLexer;
use ByJG\TextClassifier\Lexer\StandardLexer;
use PHPUnit\Framework\TestCase;

class StandardLexerTest extends TestCase
{
    private function lexer(): StandardLexer
    {
        return new StandardLexer((new ConfigLexer())->setMinSize(1)->setAllowNumbers(true));
    }

    public function testEmptyStringIsEmpty(): void
    {
        $this->assertSame(StandardLexer::LEXER_TEXT_EMPTY, $this->lexer()->getTokens(''));
    }

    /**
     * empty() treats "0" as empty, so the text "0" used to be rejected with LEXER_TEXT_EMPTY.
     */
    public function testZeroIsTokenized(): void
    {
        $this->assertSame(['0' => 1], $this->lexer()->getTokens('0'));
    }

    /**
     * Whitespace-only text is not empty: it is lexed and yields no tokens.
     */
    public function testWhitespaceOnlyIsNotEmpty(): void
    {
        $this->assertSame(['tc*no_tokens' => 1], $this->lexer()->getTokens('   '));
    }
}
