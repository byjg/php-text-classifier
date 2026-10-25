---
sidebar_key: text-classifier
sidebar_category: text-content
tags: [php, text-classification, ai]
---

# Bayesian Text Classifier

Classify text in PHP (spam or not, topic, language) with Bayesian filters that ask an LLM when they are unsure and learn from its answer. Two engines:

[![Sponsor](https://img.shields.io/badge/Sponsor-%23ea4aaa?logo=githubsponsors&logoColor=white&labelColor=0d1117)](https://github.com/sponsors/byjg)
[![Build Status](https://github.com/byjg/php-text-classifier/actions/workflows/phpunit.yml/badge.svg?branch=master)](https://github.com/byjg/php-text-classifier/actions/workflows/phpunit.yml)
[![Opensource ByJG](https://img.shields.io/badge/opensource-byjg-success.svg)](https://opensource.byjg.com)
[![Install MCP Server](https://img.shields.io/badge/Install-MCP_Server-8A2BE2?logo=modelcontextprotocol&logoColor=white)](https://opensource.byjg.com/docs/ai/mcpserver-byjg-docs/)
[![GitHub source](https://img.shields.io/badge/Github-source-informational?logo=github)](https://github.com/byjg/php-text-classifier/)
[![GitHub license](https://img.shields.io/github/license/byjg/php-text-classifier.svg)](https://opensource.byjg.com/license/)
[![GitHub release](https://img.shields.io/github/release/byjg/php-text-classifier.svg)](https://github.com/byjg/php-text-classifier/releases/)

- **BinaryClassifier** — Binary Robinson-Fisher Bayesian filter. Classifies text as spam or ham. Designed for high-accuracy two-class filtering with word degeneration support.
- **NaiveBayes** — Multi-class Naive Bayes classifier. Classifies text into any number of user-defined categories. Suitable for language detection, topic tagging, content routing, and similar tasks.

Both engines return a `ClassificationResult` with the winning category, confidence score, and all category scores. Both support optional LLM injection for automatic escalation when the statistical model is uncertain — the LLM decision is fed back as training data, improving the model over time (active learning).

Both engines share the same tokenisation pipeline (`StandardLexer`, `StandardDegenerator`) and support pluggable storage backends (in-memory, SQLite, MySQL, PostgreSQL, GDBM).

## Why

An LLM classifies text well, but calling one for every message is slow and costs money. A
Bayesian filter is fast and free, but it is uncertain on the messages it has not seen yet.

Text Classifier uses both. The statistical model answers the clear cases; only when its score
falls in the uncertain range does it ask the LLM, and the LLM's answer is learned as training
data:

```php
$classifier = new BinaryClassifier(
    new ConfigBinaryClassifier(), $storage, $lexer,
    llm: new OpenAiLlmClient($openai, 'gpt-4o-mini'),
    configLlm: (new ConfigLlm())->setLowerBound(0.35)->setUpperBound(0.65)->setAutoLearn(true),
);
```

Over time the model handles more on its own and the LLM is called less (active learning).
The training data lives where you want it: memory, SQLite, MySQL, PostgreSQL or GDBM.

## Installation

```bash
composer require byjg/text-classifier
```

Requires PHP `>=8.3`. The GDBM storage backend additionally requires `ext-dba`.

## Quick Example

**Spam filter:**

```php
use ByJG\TextClassifier\BinaryClassifier;
use ByJG\TextClassifier\ConfigBinaryClassifier;
use ByJG\TextClassifier\Lexer\StandardLexer;
use ByJG\TextClassifier\Lexer\ConfigLexer;
use ByJG\TextClassifier\Degenerator\StandardDegenerator;
use ByJG\TextClassifier\Degenerator\ConfigDegenerator;
use ByJG\TextClassifier\Storage\Rdbms;
use ByJG\Util\Uri;

$storage = new Rdbms(new Uri('sqlite:///tmp/spam.db'), new StandardDegenerator(new ConfigDegenerator()));
$storage->createDatabase();

$classifier = new BinaryClassifier(new ConfigBinaryClassifier(), $storage, new StandardLexer(new ConfigLexer()));

$classifier->learn('Buy cheap pills now!!!', BinaryClassifier::SPAM);
$classifier->learn('Meeting at 3pm in the conference room', BinaryClassifier::HAM);

$result = $classifier->classify('buy pills online cheap');
// $result->choice === 'spam'
// $result->score  is close to 1.0
```

**Multi-class classifier:**

```php
use ByJG\TextClassifier\NaiveBayes\NaiveBayes;
use ByJG\TextClassifier\NaiveBayes\Storage\Memory;
use ByJG\TextClassifier\Lexer\StandardLexer;
use ByJG\TextClassifier\Lexer\ConfigLexer;

$nb = new NaiveBayes(new Memory(), new StandardLexer(new ConfigLexer()));

$nb->train('PHP is a programming language', 'tech');
$nb->train('The cat sat on the mat', 'animals');

$result = $nb->classify('programming language');
// $result->choice          === 'tech'
// $result->score           === 0.93
// $result->scores          === ['tech' => 0.93, 'animals' => 0.07]
```

## Documentation

| Section | Description |
|---|---|
| [Getting Started](docs/getting-started/installation.md) | Installation, requirements, first working example |
| [Guides: Spam Filter](docs/guides/spam-filter/training.md) | Training, classifying, choosing storage |
| [Guides: Multi-class](docs/guides/multi-class/training.md) | Training categories, classifying, persistence |
| [Guide: LLM-Assisted Classification](docs/guides/llm-assisted-classification.md) | Automatic LLM fallback and active learning |
| [Concepts](docs/concepts/how-binary-classifier-works.md) | How the algorithms work, architecture overview |
| [Reference](docs/reference/binary-classifier.md) | Full API, configuration parameters, error codes |

## Acknowledgements

This library is inspired by the original **b8** spam filter written by [Tobias Leupold](mailto:tobias.leupold@web.de). The core algorithm, Robinson-Fisher probability model, token degeneration approach, and the `tc*` internal variable convention all originate from his work. This project modernises the codebase for PHP 8.3+, replaces the storage layer with `byjg/micro-orm` and `byjg/migration`, and adds a multi-class NaiveBayes engine built on the same tokenisation pipeline.

## Running Tests

```bash
vendor/bin/phpunit
```

----
[Open source ByJG](https://opensource.byjg.com)
