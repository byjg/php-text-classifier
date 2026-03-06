---
sidebar_position: 5
---

# ConfigDegenerator

`ByJG\TextClassifier\Degenerator\ConfigDegenerator` controls the word degeneration behaviour used by the BinaryClassifier spam filter. All setters return `$this` for fluent chaining.

## Parameters

| Parameter | Setter | Default | Type | Description |
|---|---|---|---|---|
| `multibyte` | `setMultibyte(bool)` | `false` | `bool` | Use multibyte string functions (`mb_strtolower`, `mb_strtoupper`, `mb_substr`) instead of their ASCII counterparts. Required for non-ASCII text. |
| `encoding` | `setEncoding(string)` | `'UTF-8'` | `string` | Character encoding for multibyte operations. Only used when `multibyte = true`. |

## Usage

### ASCII text (default)

```php
use ByJG\TextClassifier\Degenerator\ConfigDegenerator;
use ByJG\TextClassifier\Degenerator\StandardDegenerator;

$degenerator = new StandardDegenerator(new ConfigDegenerator());
```

### Multibyte / Unicode text

```php
$degenerator = new StandardDegenerator(
    (new ConfigDegenerator())
        ->setMultibyte(true)
        ->setEncoding('UTF-8')
);
```

## When to enable multibyte

Enable if your training texts or classified texts contain:

- Accented characters (é, ü, ñ, ö, ...)
- Cyrillic, Greek, Arabic, Hebrew, or other non-Latin scripts
- CJK characters (Chinese, Japanese, Korean)
- Any character outside the ASCII range (`0x00`–`0x7F`)

Without multibyte mode, `strtolower('Ñoño')` does not produce `ñoño`, which means degenerated variants will be incorrect and case-folding will fail silently for non-ASCII input.

## Getters

| Method | Returns |
|---|---|
| `isMultibyte()` | `bool` |
| `getEncoding()` | `string` |

## Related

- [Degenerator concepts](../concepts/degenerator.md)
