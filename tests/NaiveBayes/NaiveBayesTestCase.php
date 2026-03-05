<?php

namespace Test\NaiveBayes;

use B8\Lexer\ConfigLexer;
use B8\Lexer\StandardLexer;
use B8\NaiveBayes\NaiveBayes;
use B8\NaiveBayes\Storage\StorageInterface;
use PHPUnit\Framework\TestCase;

abstract class NaiveBayesTestCase extends TestCase
{
    protected NaiveBayes $nb;

    protected StorageInterface $storage;

    protected function buildNaiveBayes(): void
    {
        $lexer    = new StandardLexer(new ConfigLexer());
        $this->nb = new NaiveBayes($this->storage, $lexer);
    }

    public function testTrainAndClassify(): void
    {
        // Unknown text before any training
        $result = $this->nb->classify('hello world');
        $this->assertEmpty($result);

        // Train two categories
        $this->nb->train('The cat sat on the mat', 'animals');
        $this->nb->train('The dog ran in the park', 'animals');
        $this->nb->train('PHP is a programming language', 'tech');
        $this->nb->train('Python is used for machine learning', 'tech');

        // Animals text should score highest for animals
        $result = $this->nb->classify('the cat and the dog');
        $this->assertArrayHasKey('animals', $result);
        $this->assertArrayHasKey('tech', $result);
        $this->assertEquals('animals', array_key_first($result));

        // Tech text should score highest for tech
        $result = $this->nb->classify('programming language learning');
        $this->assertEquals('tech', array_key_first($result));
    }

    public function testUntrain(): void
    {
        $this->nb->train('The cat sat on the mat', 'animals');
        $this->nb->train('The cat sat on the mat', 'animals');
        $this->nb->train('PHP is a programming language', 'tech');

        $this->nb->untrain('The cat sat on the mat', 'animals');

        $result = $this->nb->classify('cat mat');
        // After untraining one, animals still has one training sample so it should still win
        $this->assertEquals('animals', array_key_first($result));

        // Untrain the second animals sample — category should disappear
        $this->nb->untrain('The cat sat on the mat', 'animals');
        $result = $this->nb->classify('cat mat');
        $this->assertArrayNotHasKey('animals', $result);
    }

    public function testMultipleCategories(): void
    {
        $this->nb->train("L'Italie a été gouvernée par Mario Monti président du conseil", 'fr');
        $this->nb->train("Il en faut peu pour passer du statut de renégate dans la politique française", 'fr');
        $this->nb->train("El ex presidente sudafricano Nelson Mandela hospitalizado en Pretoria", 'es');
        $this->nb->train("Guerras continuas y problemas llevaron a un estado de disminución del imperio español", 'es');
        $this->nb->train("Un importante punto de inflexión en la historia de la ciencia filosófica primitiva", 'es');
        $this->nb->train("AI researchers debate whether machines should have emotions or not", 'en');
        $this->nb->train("Scientific problems and the need to understand the human brain through research", 'en');

        $result = $this->nb->classify('ciencia filosófica primitiva');
        $this->assertEquals('es', array_key_first($result));

        $result = $this->nb->classify('scientific problems researchers');
        $this->assertEquals('en', array_key_first($result));

        $result = $this->nb->classify('Italie gouvernée Monti');
        $this->assertEquals('fr', array_key_first($result));
    }
}
