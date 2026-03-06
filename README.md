# b8 — Bayesian Text Classifier

[![Sponsor](https://img.shields.io/badge/Sponsor-%23ea4aaa?logo=githubsponsors&logoColor=white&labelColor=0d1117)](https://github.com/sponsors/byjg)
[![Build Status](https://github.com/byjg/text-classifier/actions/workflows/phpunit.yml/badge.svg?branch=master)](https://github.com/byjg/text-classifier/actions/workflows/phpunit.yml)
[![Opensource ByJG](https://img.shields.io/badge/opensource-byjg-success.svg)](http://opensource.byjg.com)
[![GitHub source](https://img.shields.io/badge/Github-source-informational?logo=github)](https://github.com/byjg/text-classifier/)
[![GitHub license](https://img.shields.io/github/license/byjg/text-classifier.svg)](https://opensource.byjg.com/opensource/licensing.html)
[![GitHub release](https://img.shields.io/github/release/byjg/text-classifier.svg)](https://github.com/byjg/text-classifier/releases/)

A PHP library for statistical text classification. Provides two independent engines:

- **B8** — Binary Robinson-Fisher Bayesian filter. Classifies text as spam or ham, returning a probability score between `0.0` (ham) and `1.0` (spam). Designed for high-accuracy two-class filtering with word degeneration support.
- **NaiveBayes** — Multi-class Naive Bayes classifier. Classifies text into any number of user-defined categories, returning a ranked score map. Suitable for language detection, topic tagging, content routing, and similar tasks.

Both engines share the same tokenization pipeline (`StandardLexer`, `StandardDegenerator`) and support pluggable storage backends (in-memory, SQLite, MySQL, PostgreSQL, BerkeleyDB).

## Installation

```bash
composer require byjg/text-classifier
```

Requires PHP `>=8.3`. The BerkeleyDB storage backend additionally requires `ext-dba`.

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

$b8 = new BinaryClassifier(new ConfigBinaryClassifier(), $storage, new StandardLexer(new ConfigLexer()));

$b8->learn('Buy cheap pills now!!!', BinaryClassifier::SPAM);
$b8->learn('Meeting at 3pm in the conference room', BinaryClassifier::HAM);

$score = $b8->classify('buy pills online cheap');
// $score is close to 1.0 (spam)
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

$scores = $nb->classify('programming language');
// ['tech' => 0.93, 'animals' => 0.07]
```

## Documentation

| Section | Description |
|---|---|
| [Getting Started](docs/getting-started/installation.md) | Installation, requirements, first working example |
| [Guides: Spam Filter](docs/guides/spam-filter/training.md) | Training, classifying, choosing storage |
| [Guides: Multi-class](docs/guides/multi-class/training.md) | Training categories, classifying, persistence |
| [Concepts](docs/concepts/how-b8-works.md) | How the algorithms work, architecture overview |
| [Reference](docs/reference/b8.md) | Full API, configuration parameters, error codes |

## Acknowledgements

This library is inspired by the original **b8** spam filter written by [Tobias Leupold](mailto:tobias.leupold@web.de). The core algorithm, Robinson-Fisher probability model, token degeneration approach, and the `b8*` internal variable convention all originate from his work. This project modernises the codebase for PHP 8.3+, replaces the storage layer with `byjg/micro-orm` and `byjg/migration`, and adds a multi-class NaiveBayes engine built on the same tokenisation pipeline.

## Dependencies

```mermaid
flowchart TD
    byjg/text-classifier --> byjg/micro-orm
    byjg/text-classifier --> byjg/migration
```
