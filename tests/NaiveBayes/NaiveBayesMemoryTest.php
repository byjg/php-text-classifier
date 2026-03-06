<?php

namespace Test\NaiveBayes;

use ByJG\TextClassifier\NaiveBayes\Storage\Memory;

class NaiveBayesMemoryTest extends NaiveBayesTestCase
{
    protected function setUp(): void
    {
        $this->storage = new Memory();
        $this->buildNaiveBayes();
    }

    public function testSaveAndLoad(): void
    {
        $this->nb->train('The cat sat on the mat', 'animals');
        $this->nb->train('PHP is a programming language', 'tech');

        $file = sys_get_temp_dir() . '/nb_test.json';

        /** @var Memory $storage */
        $storage = $this->storage;
        $storage->save($file);

        $storage2 = new Memory();
        $storage2->load($file);

        $result = $this->nb->classify('cat mat');
        $this->assertEquals('animals', array_key_first($result));

        unlink($file);
    }
}
