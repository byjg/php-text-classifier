---
sidebar_position: 1
---

# How B8 Works

B8 is a Robinson-Fisher Bayesian spam filter. It classifies text as spam or ham by computing a single probability score `[0.0, 1.0]` using a combination of token-level probabilities.

## Overview

```
Text input
    │
    ▼
StandardLexer ──────────────────── Extract tokens
    │                               (words, URIs, HTML tags)
    ▼
Token lookup in storage
    │
    ├── Token found → use stored counts
    └── Token not found → try degenerated variants
                          └── Not found → use neutral score (robX = 0.5)
    │
    ▼
Per-token spamminess (Robinson smoothing)
    │
    ▼
Select top N most "relevant" tokens
(those deviating most from 0.5)
    │
    ▼
Robinson-Fisher combined score
    │
    ▼
Final score [0.0 = ham, 1.0 = spam]
```

## Token probability (Robinson smoothing)

For each token, the basic probability is:

```
basic_probability = relative_spam / (relative_ham + relative_spam)

where:
  relative_ham  = count_ham  / total_ham_texts
  relative_spam = count_spam / total_spam_texts
```

This is then smoothed using the Robinson formula to avoid extreme values for rare tokens:

```
smoothed = (robS × robX + total_seen × basic_probability) / (robS + total_seen)

where:
  robS = smoothing weight (default: 0.3)
  robX = neutral probability for unknown tokens (default: 0.5)
  total_seen = count_ham + count_spam for this token
```

A token seen only once gets a probability close to `robX = 0.5` (uncertain). A token seen many times converges toward its true spam rate.

## Relevance selection

Not all tokens are used. B8 selects the `use_relevant` (default: `15`) tokens whose smoothed probability deviates most from `0.5` (i.e. `abs(0.5 - probability) > min_dev`). This focuses the calculation on the most discriminating words and discards common words that appear equally in spam and ham.

Tokens that appear multiple times in the input text are counted multiple times in the relevance list.

## Combined score (geometric mean)

The final score uses Gary Robinson's geometric mean approach:

```
hamminess  = 1 - (1 - p1) × (1 - p2) × ... × (1 - pN)  ^(1/N)
spamminess = 1 - p1 × p2 × ... × pN                      ^(1/N)

combined = (hamminess - spamminess) / (hamminess + spamminess)
score    = (1 + combined) / 2
```

This produces a value between `0.0` and `1.0`. A value of `0.5` means the filter genuinely cannot distinguish spam from ham with the current training data — it is not a midpoint between two equal scores.

## Degeneration

When a token is not found in the database, `StandardDegenerator` generates variants:

- Lowercase: `HELLO` → `hello`
- Uppercase: `hello` → `HELLO`
- Title case: `hello` → `Hello`
- Without trailing punctuation: `hello!` → `hello`, `hello!!` → `hello!` → `hello`
- Trailing dots stripped iteratively: `hello...` → `hello..` → `hello.` → `hello`

The variant with the probability furthest from `0.5` is used. This allows the filter to use training data about `hello` when classifying `HELLO!` — even if `HELLO!` was never seen in training.

## Learning

When `learn($text, $category)` is called:
1. The text is tokenised
2. The total text count for the category is incremented (`b8*texts`)
3. Each token's count for that category is incremented

When `unlearn($text, $category)` is called, the same steps are reversed.

## Parameters that affect the algorithm

| Parameter | Effect |
|---|---|
| `robS` | Smoothing strength — higher values push unknown tokens closer to `robX` |
| `robX` | Prior probability for unknown tokens (default `0.5` = neutral) |
| `use_relevant` | Maximum tokens considered per classification |
| `min_dev` | Minimum deviation from `0.5` for a token to be considered |

See [ConfigB8 reference](../reference/config-b8.md).
