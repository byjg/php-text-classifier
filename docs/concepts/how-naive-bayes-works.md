---
sidebar_position: 2
---

# How NaiveBayes Works

The `NaiveBayes` engine performs multi-class text classification using a one-vs-rest Naive Bayes approach with Robinson smoothing.

## Overview

```
Text input
    │
    ▼
StandardLexer ──── Extract tokens (word → count map)
    │
    ▼
Batch token lookup from storage (all tokens × all categories)
    │
    ▼
For each category:
    │
    ├── Compute per-token probability (one-vs-rest)
    │
    ├── Apply Robinson smoothing
    │
    ├── Sum log-odds
    │
    └── Convert to score via sigmoid
    │
    ▼
Sort categories by score descending → return array<string, float>
```

## One-vs-rest framing

Each category is scored independently. For category C, every other category is treated as "not C". This lets the classifier handle any number of categories without retraining when new categories are added.

## Per-token probability

For a given token and category C:

```
token_prob_pos = count(token in C)         / doc_count(C)
token_prob_neg = count(token in not-C)     / doc_count(not-C)

where:
  doc_count(not-C) = total_doc_count - doc_count(C)
  count(token in not-C) = total_count(token) - count(token in C)

ratio = token_prob_pos / (token_prob_pos + token_prob_neg)
```

## Robinson smoothing

The raw ratio is smoothed to prevent rare tokens from producing extreme probabilities:

```
smoothed = (robS × robX + total_count(token) × ratio) / (robS + total_count(token))

where:
  robS = smoothing weight (default: 1.0)
  robX = neutral prior (default: 0.5)
```

The smoothed value is clamped to `[0.01, 0.99]`.

## Log-sum and sigmoid

Per-token probabilities are combined using log-odds summation, which avoids floating-point underflow from multiplying many small probabilities:

```
log_sum += log(1 - p) - log(p)   for each token
```

The final score is produced by the sigmoid (logistic) function:

```
score = 1 / (1 + exp(log_sum))
```

A `log_sum` of 0 produces a score of `0.5`. Large positive values (many weak ham signals) produce scores near `0.0`. Large negative values (many spam-like signals relative to other categories) produce scores near `1.0`.

## Tokens with no total count

If a token has never been seen in any category, it contributes no signal and is skipped entirely. This differs from `BinaryClassifier`'s degeneration fallback — NaiveBayes does not use word variants.

## Categories with zero inverse doc count

If a category contains all the trained documents (i.e. `doc_count(not-C) = 0`), that category is skipped. This prevents division by zero and typically indicates insufficient training data diversity.

## Differences from BinaryClassifier

| Aspect | BinaryClassifier | NaiveBayes |
|---|---|---|
| Classes | Binary (spam/ham) | N arbitrary categories |
| Unknown tokens | Degeneration fallback | Skipped |
| Score combination | Robinson-Fisher geometric mean | Log-sum + sigmoid |
| Token relevance filtering | Top N by deviation from 0.5 | All tokens used |
| Smoothing defaults | `robS=0.3`, `robX=0.5` | `robS=1.0`, `robX=0.5` |

## Parameters

| Parameter | Default | Effect |
|---|---|---|
| `robS` | `1.0` | Smoothing weight; higher values make the model more conservative |
| `robX` | `0.5` | Prior for unknown categories; `0.5` = neutral |

See [ConfigNaiveBayes reference](../reference/config-naive-bayes.md).
