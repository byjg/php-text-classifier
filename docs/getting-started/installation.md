---
sidebar_position: 1
---

# Installation

## Requirements

| Requirement | Version |
|---|---|
| PHP | `>=8.3 <8.6` |
| Composer | any |
| `ext-dba` | Only for BerkeleyDB storage |

## Install via Composer

```bash
composer require byjg/text-classifier
```

## Storage backend requirements

b8 supports multiple storage backends. Choose based on your use case:

| Backend | Engine | Extra requirement |
|---|---|---|
| `Storage\Rdbms` | BinaryClassifier (spam filter) | None — uses `byjg/micro-orm` |
| `Storage\Dba` | BinaryClassifier (spam filter) | `ext-dba` PHP extension |
| `NaiveBayes\Storage\Rdbms` | NaiveBayes | None |
| `NaiveBayes\Storage\Memory` | NaiveBayes | None |

### Enable ext-dba (BerkeleyDB only)

```bash
# Ubuntu / Debian
sudo apt-get install php-dba

# Verify
php -m | grep dba
```

## Verify installation

```php
<?php
require 'vendor/autoload.php';

use ByJG\TextClassifier\BinaryClassifier;
use ByJG\TextClassifier\ConfigBinaryClassifier;
use ByJG\TextClassifier\Lexer\StandardLexer;
use ByJG\TextClassifier\Lexer\ConfigLexer;
use ByJG\TextClassifier\Degenerator\StandardDegenerator;
use ByJG\TextClassifier\Degenerator\ConfigDegenerator;
use ByJG\TextClassifier\Storage\Rdbms;
use ByJG\Util\Uri;

$storage = new Rdbms(
    new Uri('sqlite:///tmp/b8_verify.db'),
    new StandardDegenerator(new ConfigDegenerator())
);
$storage->createDatabase();

$b8 = new BinaryClassifier(new ConfigBinaryClassifier(), $storage, new StandardLexer(new ConfigLexer()));

echo $b8->classify('hello world'); // prints 0.5 (no training yet)
```

## Next steps

- [Quick start: spam filter](quick-start-spam-filter.md)
- [Quick start: multi-class classifier](quick-start-multi-class.md)
