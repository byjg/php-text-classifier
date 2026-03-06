---
sidebar_position: 4
---

# ConfigLexer

`B8\Lexer\ConfigLexer` controls tokenisation behaviour. Used by both `B8` and `NaiveBayes`. All setters return `$this` for fluent chaining.

## Parameters

| Parameter | Setter | Default | Type | Description |
|---|---|---|---|---|
| `min_size` | `setMinSize(int)` | `3` | `int` | Minimum token length. Tokens shorter than this are discarded. |
| `max_size` | `setMaxSize(int)` | `30` | `int` | Maximum token length. Tokens longer than this are discarded. |
| `allow_numbers` | `setAllowNumbers(bool)` | `false` | `bool` | Whether to keep pure numeric tokens (e.g. `12345`). |
| `get_uris` | `setGetUris(bool)` | `true` | `bool` | Extract URI-like patterns (e.g. `example.com`, `cdn.host.net`). |
| `old_get_html` | `setOldGetHtml(bool)` | `true` | `bool` | Tokenise HTML tags without removing them from the text. Legacy mode. |
| `get_html` | `setGetHtml(bool)` | `false` | `bool` | Tokenise HTML tags and remove them from the remaining text. Modern mode. |
| `get_bbcode` | `setGetBbcode(bool)` | `false` | `bool` | Extract BBCode tags (e.g. `[b]`, `[url=...]`). |

## Usage

```php
use ByJG\TextClassifier\Lexer\ConfigLexer;

$config = (new ConfigLexer())
    ->setMinSize(3)
    ->setMaxSize(30)
    ->setAllowNumbers(false)
    ->setGetUris(true)
    ->setOldGetHtml(false)
    ->setGetHtml(true)
    ->setGetBbcode(false);
```

## HTML mode selection

| Use case | `old_get_html` | `get_html` |
|---|---|---|
| Default (legacy) | `true` | `false` |
| Recommended for HTML email | `false` | `true` |
| Ignore all HTML | `false` | `false` |
| Both modes active (not recommended) | `true` | `true` |

In "modern" mode (`get_html = true`), HTML tags are extracted as tokens (e.g. `<b>`, `<a...>`) and removed from the text before the raw split, preventing partial tag text from appearing as word tokens.

## Language detection tip

For language detection or any use case where short words carry discriminative value, lower `min_size`:

```php
(new ConfigLexer())->setMinSize(2)
```

This allows articles and prepositions like `el`, `la`, `de`, `in` to be included as tokens.

## Getters

| Method | Returns |
|---|---|
| `getMinSize()` | `int` |
| `getMaxSize()` | `int` |
| `isAllowNumbers()` | `bool` |
| `isGetUris()` | `bool` |
| `isOldGetHtml()` | `bool` |
| `isGetHtml()` | `bool` |
| `isGetBbcode()` | `bool` |
