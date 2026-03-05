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
composer require byjg/b8
```

## Storage backend requirements

b8 supports multiple storage backends. Choose based on your use case:

| Backend | Engine | Extra requirement |
|---|---|---|
| `Storage\Rdbms` | B8 (spam filter) | None — uses `byjg/micro-orm` |
| `Storage\Dba` | B8 (spam filter) | `ext-dba` PHP extension |
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

use B8\B8;
use B8\ConfigB8;
use B8\Lexer\StandardLexer;
use B8\Lexer\ConfigLexer;
use B8\Degenerator\StandardDegenerator;
use B8\Degenerator\ConfigDegenerator;
use B8\Storage\Rdbms;
use ByJG\Util\Uri;

$storage = new Rdbms(
    new Uri('sqlite:///tmp/b8_verify.db'),
    new StandardDegenerator(new ConfigDegenerator())
);
$storage->createDatabase();

$b8 = new B8(new ConfigB8(), $storage, new StandardLexer(new ConfigLexer()));

echo $b8->classify('hello world'); // prints 0.5 (no training yet)
```

## Next steps

- [Quick start: spam filter](quick-start-spam-filter.md)
- [Quick start: multi-class classifier](quick-start-multi-class.md)
