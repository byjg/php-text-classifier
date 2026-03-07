<?php


namespace ByJG\TextClassifier\Degenerator;


interface DegeneratorInterface
{
    function degenerate(array $words);

    function getDegenerates($token);
}