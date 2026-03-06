<?php

namespace Test;

use ByJG\TextClassifier\BinaryClassifier;
use ByJG\TextClassifier\ConfigBinaryClassifier;
use ByJG\TextClassifier\Degenerator\ConfigDegenerator;
use ByJG\TextClassifier\Degenerator\StandardDegenerator;
use ByJG\TextClassifier\Lexer\ConfigLexer;
use ByJG\TextClassifier\Lexer\StandardLexer;
use ByJG\TextClassifier\Storage\Rdbms;
use ByJG\Util\Uri;

require_once 'B8TestCase.php';


class B8RdbmsTest extends B8TestCase
{
    protected function setUp(): void
    {
        $this->path = "/tmp/sqlite.db";
        $this->tearDown();

        $lexer = new StandardLexer(
            (new ConfigLexer())
                ->setOldGetHtml(false)
                ->setGetHtml(true)
        );

        $degenerator = new StandardDegenerator(
            (new ConfigDegenerator())
                ->setMultibyte(true)
        );

        $uri = new Uri("sqlite://" . $this->path);
        $storage = new Rdbms(
            $uri,
            $degenerator
        );
        $storage->createDatabase();

        $this->b8 = new BinaryClassifier(new ConfigBinaryClassifier(), $storage, $lexer);
    }

}
