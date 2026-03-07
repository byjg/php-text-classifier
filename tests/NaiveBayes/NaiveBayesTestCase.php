<?php

namespace Test\NaiveBayes;

use ByJG\TextClassifier\Lexer\ConfigLexer;
use ByJG\TextClassifier\Lexer\StandardLexer;
use ByJG\TextClassifier\NaiveBayes\NaiveBayes;
use ByJG\TextClassifier\NaiveBayes\Storage\StorageInterface;
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
        $this->assertNull($result);

        // Train two categories
        $this->nb->train('The cat sat on the mat', 'animals');
        $this->nb->train('The dog ran in the park', 'animals');
        $this->nb->train('PHP is a programming language', 'tech');
        $this->nb->train('Python is used for machine learning', 'tech');

        // Animals text should score highest for animals
        $result = $this->nb->classify('the cat and the dog');
        $this->assertArrayHasKey('animals', $result->scores);
        $this->assertArrayHasKey('tech', $result->scores);
        $this->assertEquals('animals', $result->choice);

        // Tech text should score highest for tech
        $result = $this->nb->classify('programming language learning');
        $this->assertEquals('tech', $result->choice);
    }

    public function testUntrain(): void
    {
        $this->nb->train('The cat sat on the mat', 'animals');
        $this->nb->train('The cat sat on the mat', 'animals');
        $this->nb->train('PHP is a programming language', 'tech');

        $this->nb->untrain('The cat sat on the mat', 'animals');

        $result = $this->nb->classify('cat mat');
        // After untraining one, animals still has one training sample so it should still win
        $this->assertEquals('animals', $result->choice);

        // Untrain the second animals sample — category should disappear
        $this->nb->untrain('The cat sat on the mat', 'animals');
        $result = $this->nb->classify('cat mat');
        $this->assertArrayNotHasKey('animals', $result?->scores ?? []);
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
        $this->assertEquals('es', $result->choice);

        $result = $this->nb->classify('scientific problems researchers');
        $this->assertEquals('en', $result->choice);

        $result = $this->nb->classify('Italie gouvernée Monti');
        $this->assertEquals('fr', $result->choice);
    }
}
