<?php

namespace Test;

use B8\B8;
use B8\ConfigB8;
use B8\Degenerator\ConfigDegenerator;
use B8\Degenerator\StandardDegenerator;
use B8\Factory;
use B8\Lexer\ConfigLexer;
use B8\Lexer\StandardLexer;
use B8\Storage\Rdbms;
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

        $this->b8 = new B8(new ConfigB8(), $storage, $lexer);
    }

}