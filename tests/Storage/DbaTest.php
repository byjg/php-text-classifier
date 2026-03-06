<?php

namespace Test\Storage;

use ByJG\TextClassifier\Degenerator\ConfigDegenerator;
use ByJG\TextClassifier\Degenerator\StandardDegenerator;
use ByJG\TextClassifier\Storage\Dba;

class DbaTest extends BaseTestCase
{
    protected function setUp(): void
    {
        $this->path = "/tmp/wordlist.db";
        $this->tearDown();

        $degenerator = new StandardDegenerator(
            (new ConfigDegenerator())
                ->setMultibyte(true)
        );

        $this->storage = new Dba(
            $this->path,
            $degenerator
        );
        $this->storage->createDatabase();
    }
}
