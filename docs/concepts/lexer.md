---
sidebar_position: 3
---

# Lexer

The lexer is responsible for tokenising input text into a map of `token => count` pairs. Both the BinaryClassifier spam filter and the NaiveBayes classifier use the same lexer pipeline.

## StandardLexer pipeline

`StandardLexer::getTokens(string $text)` processes text in this order:

1. **HTML entity decode** — converts `&amp;`, `&lt;`, etc. to their original characters
2. **URI extraction** (if `get_uris = true`) — finds domain-like patterns, adds them as tokens, and removes them from the remaining text before the raw split
3. **HTML tag extraction** — adds tag tokens like `<b>`, `<a...>` (old or new mode, see below)
4. **BBCode extraction** (if `get_bbcode = true`) — adds `[b]`, `[url=...]` tokens
5. **Raw split** — splits on whitespace and punctuation, adds plain word tokens

### URI extraction

Patterns matching `word.word` are extracted as URI tokens. The full URI is added as a token (e.g. `example.com`), then the URI is also raw-split into its component words. The URI is removed from the text so it is not double-counted.

### HTML tag handling

Two HTML modes exist and can be combined:

| Config | Mode | Behaviour |
|---|---|---|
| `old_get_html = true` (default) | Legacy | Tags are tokenised but **not removed** from the text. Tags with attributes become `<tagname...>`. |
| `get_html = true` | Modern | Tags are tokenised **and removed** from the text. Tags with attributes become `<tagname...>` (attribute summarised). |

Use `old_get_html = false` and `get_html = true` together for the recommended HTML mode.

### Raw split

Splits the remaining text on the pattern:

```
/[\s,\.\/"\:;\|<>\-_\[\]{}\+=\)\(\*\&\^%]+/
```

Each resulting fragment is validated and, if valid, added to the token list.

## Token validation

A token is valid if:

- Its length is between `min_size` (default: `3`) and `max_size` (default: `30`)
- It does not start with `b8*` (reserved for internal variables)
- It is not a pure number, unless `allow_numbers = true`

## Token counting

Tokens are counted by occurrence. If the word "cheap" appears three times in the text, `$tokens['cheap'] = 3`. This count is used in both training and classification — the token's contribution scales with its frequency.

## Special token: `b8*no_tokens`

If the entire tokenisation process produces no valid tokens, a single synthetic token `b8*no_tokens` with count `1` is returned. This prevents an empty result that would cause divide-by-zero issues downstream.

## ConfigLexer quick reference

| Method | Default | Purpose |
|---|---|---|
| `setMinSize(int)` | `3` | Minimum token length |
| `setMaxSize(int)` | `30` | Maximum token length |
| `setAllowNumbers(bool)` | `false` | Allow pure numeric tokens |
| `setGetUris(bool)` | `true` | Extract URI-like patterns |
| `setOldGetHtml(bool)` | `true` | Use legacy HTML tag tokenisation |
| `setGetHtml(bool)` | `false` | Use modern HTML tag tokenisation |
| `setGetBbcode(bool)` | `false` | Extract BBCode tags |

See [ConfigLexer reference](../reference/config-lexer.md).

## Error codes

`getTokens()` returns a string error code (not an array) when input is invalid:

| Constant | Meaning |
|---|---|
| `StandardLexer::LEXER_TEXT_NOT_STRING` | Input is not a string |
| `StandardLexer::LEXER_TEXT_EMPTY` | Input is an empty string |

Both `BinaryClassifier::classify()` and `BinaryClassifier::learn()` propagate these codes as their own return value.
