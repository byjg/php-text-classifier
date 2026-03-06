---
sidebar_position: 6
---

# ConfigNaiveBayes

`B8\NaiveBayes\ConfigNaiveBayes` controls the smoothing behaviour of the `NaiveBayes` classifier. Parameters are set via constructor.

## Constructor

```php
new ConfigNaiveBayes(float $robS = 1.0, float $robX = 0.5)
```

## Parameters

| Parameter | Constructor arg | Default | Type | Description |
|---|---|---|---|---|
| `robS` | First positional | `1.0` | `float` | Robinson smoothing weight. Controls how strongly rare tokens are pulled toward the neutral prior `robX`. |
| `robX` | Second positional | `0.5` | `float` | Neutral prior probability. The assumed score for a token/category pair with no training data. |

## Usage

```php
use ByJG\TextClassifier\NaiveBayes\ConfigNaiveBayes;
use ByJG\TextClassifier\NaiveBayes\NaiveBayes;

// Default config
$nb = new NaiveBayes($storage, $lexer);

// Custom smoothing
$nb = new NaiveBayes($storage, $lexer, new ConfigNaiveBayes(robS: 2.0, robX: 0.5));
```

## Tuning guidance

### `robS`

Higher values make the classifier more conservative — tokens with few observations are pulled strongly toward `0.5` and have less influence on the final score. Lower values give rare tokens more influence, which can cause instability with small training sets.

| `robS` value | Behaviour |
|---|---|
| `0.1`–`0.5` | Aggressive — rare tokens matter more |
| `1.0` (default) | Balanced |
| `2.0`–`5.0` | Conservative — rare tokens matter less |

### `robX`

The neutral prior. `0.5` means "no information" — a token never seen in any category contributes nothing to any category's score. Changing this biases the classifier.

## Getters

| Method | Returns |
|---|---|
| `getRobS()` | `float` |
| `getRobX()` | `float` |

## Comparison with ConfigBinaryClassifier

`ConfigNaiveBayes` has different defaults than `ConfigBinaryClassifier` because the algorithms differ:

| Parameter | ConfigBinaryClassifier default | ConfigNaiveBayes default |
|---|---|---|
| `robS` | `0.3` | `1.0` |
| `robX` | `0.5` | `0.5` |

NaiveBayes uses a higher `robS` because the one-vs-rest approach with multiple categories is more sensitive to sparse data than the binary Fisher test used by B8.
