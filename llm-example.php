<?php

/**
 * LLM-assisted bootstrap example
 *
 * Phase 1 — Bootstrap: use Ollama (qwen3.5:3b) to label 20 phrases and train
 *           the NaiveBayes statistical model.
 * Phase 2 — Production: classify new phrases using the statistical model only,
 *           with no further LLM calls.
 */

require_once __DIR__ . '/vendor/autoload.php';

use ByJG\TextClassifier\Lexer\ConfigLexer;
use ByJG\TextClassifier\Lexer\StandardLexer;
use ByJG\TextClassifier\Llm\OpenAiLlmClient;
use ByJG\TextClassifier\NaiveBayes\NaiveBayes;
use ByJG\TextClassifier\NaiveBayes\Storage\Memory;

// ---------------------------------------------------------------------------
// Ollama client — OpenAI-compatible endpoint on localhost
// ---------------------------------------------------------------------------

$openai = OpenAI::factory()
    ->withBaseUri('http://localhost:11434/v1')
    ->withApiKey('ollama')          // Ollama ignores this but the client requires it
    ->make();

$llm = new OpenAiLlmClient($openai, 'qwen3.5:9b');

// ---------------------------------------------------------------------------
// Statistical model
// ---------------------------------------------------------------------------

$storage = new Memory();
$nb      = new NaiveBayes($storage, new StandardLexer(new ConfigLexer()));

// ---------------------------------------------------------------------------
// Phase 1 — Bootstrap: label 20 phrases with the LLM, train the model
// ---------------------------------------------------------------------------

$categories = ['tech', 'animals', 'sports', 'cooking'];

$phrases = [
    'PHP is a widely used server-side scripting language for web development',
    'The golden retriever is one of the most popular dog breeds in the world',
    'The football team scored three goals in the last ten minutes of the match',
    'Sauté the onions in olive oil over medium heat until they are translucent',
    'Machine learning models require large datasets to produce accurate results',
    'Tigers are the largest wild cats and are native to Asia',
    'The tennis player served an ace to win the championship point',
    'Knead the bread dough for at least ten minutes to develop the gluten',
    'Kubernetes orchestrates containerised applications across clusters of machines',
    'Dolphins are highly intelligent marine mammals that communicate with clicks',
    'The marathon runner finished the race in just under two hours and thirty minutes',
    'Roast the garlic cloves in the oven until they are soft and golden brown',
    'A microservice architecture splits an application into small independent services',
    'Penguins are flightless birds that live primarily in the Southern Hemisphere',
    'The basketball player broke the all-time scoring record during last night\'s game',
    'Simmer the tomato sauce on low heat for forty minutes to deepen the flavour',
    'TypeScript adds static typing to JavaScript and compiles to plain JavaScript',
    'The African elephant is the largest land animal on Earth',
    'Cyclists in the Tour de France climb thousands of metres of mountain stages',
    'Season the steak generously with salt and pepper before searing in a hot pan',
];

echo "=== Phase 1: Bootstrap — LLM labels phrases and trains the statistical model ===\n\n";

foreach ($phrases as $text) {
    $category = $llm->classify($text, $categories);
    $nb->train($text, $category);
    echo sprintf("  [%-8s] %s\n", $category, mb_strimwidth($text, 0, 70, '…'));
}

echo "\nTraining complete. " . count($phrases) . " phrases labelled by LLM and fed into the statistical model.\n";

// ---------------------------------------------------------------------------
// Phase 2 — Production: statistical model only, no LLM calls
// ---------------------------------------------------------------------------

$testPhrases = [
    'Pandas eat bamboo shoots and live in the mountains of China',
    'Docker containers package applications with all their dependencies',
    'The sprinter set a new world record in the hundred metre dash',
    'Deglaze the pan with white wine after browning the chicken',
    'A relational database stores data in tables with rows and columns',
];

echo "\n=== Phase 2: Production — statistical model only (no LLM) ===\n\n";

foreach ($testPhrases as $text) {
    $scores = $nb->classify($text);
    $top    = array_key_first($scores);
    $score  = reset($scores);

    echo sprintf(
        "  [%-8s %.0f%%] %s\n",
        $top,
        $score * 100,
        mb_strimwidth($text, 0, 70, '…')
    );
}
