<?php

namespace Test\NaiveBayes;

use B8\NaiveBayes\Storage\Rdbms;
use ByJG\Util\Uri;

class NaiveBayesRdbmsTest extends NaiveBayesTestCase
{
    protected string $path;

    protected function setUp(): void
    {
        $this->path = '/tmp/nb_sqlite_test.db';
        $this->tearDown();

        $uri           = new Uri('sqlite://' . $this->path);
        $this->storage = new Rdbms($uri);
        $this->storage->createDatabase();

        $this->buildNaiveBayes();
    }

    protected function tearDown(): void
    {
        if (file_exists($this->path)) {
            unlink($this->path);
        }
    }
}
