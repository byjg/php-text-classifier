<?php

namespace Test\Storage;

use ByJG\TextClassifier\Degenerator\ConfigDegenerator;
use ByJG\TextClassifier\Degenerator\StandardDegenerator;
use ByJG\TextClassifier\Storage\Rdbms;

class RdbmsTest extends BaseTestCase
{
    protected function setUp(): void
    {
        $this->path = "/tmp/sqlite.db";
        $this->tearDown();

        $degenerator = new StandardDegenerator(
            (new ConfigDegenerator())
                ->setMultibyte(true)
        );

        $uri = new \ByJG\Util\Uri("sqlite://" . $this->path);
        $this->storage = new Rdbms(
            $uri,
            $degenerator
        );
        $this->storage->createDatabase();
    }
}
