---
sidebar_position: 7
---

# ConfigLlm

`ByJG\TextClassifier\Llm\ConfigLlm` controls when the LLM is consulted and whether its decision is fed back as training data. All setters return `$this` for fluent chaining.

## Parameters

| Parameter | Setter | Default | Applies to | Description |
|---|---|---|---|---|
| `lowerBound` | `setLowerBound(float)` | `0.35` | `BinaryClassifier` | Escalate when score ≥ this value… |
| `upperBound` | `setUpperBound(float)` | `0.65` | `BinaryClassifier` | …and score ≤ this value. Scores outside `[lowerBound, upperBound]` are considered certain. |
| `minConfidence` | `setMinConfidence(float)` | `0.65` | `NaiveBayes` | Escalate when the top category score is below this threshold. |
| `minMargin` | `setMinMargin(float)` | `0.15` | `NaiveBayes` | Escalate when the gap between the top and second category score is below this threshold. |
| `autoLearn` | `setAutoLearn(bool)` | `true` | Both | When `true`, the LLM decision is fed back to the classifier as training data and the text is re-classified. |

## Usage

```php
use ByJG\TextClassifier\Llm\ConfigLlm;

// Wider uncertainty zone → more LLM calls, faster learning
$config = (new ConfigLlm())
    ->setLowerBound(0.40)
    ->setUpperBound(0.60);

// Stricter NaiveBayes thresholds → escalate only when very uncertain
$config = (new ConfigLlm())
    ->setMinConfidence(0.80)
    ->setMinMargin(0.20);

// Disable active learning (LLM decides but classifier is not updated)
$config = (new ConfigLlm())
    ->setAutoLearn(false);
```

## Getters

| Method | Returns |
|---|---|
| `getLowerBound()` | `float` |
| `getUpperBound()` | `float` |
| `getMinConfidence()` | `float` |
| `getMinMargin()` | `float` |
| `isAutoLearn()` | `bool` |

## Tuning guidance

### Binary classifier (`lowerBound` / `upperBound`)

The classifier score represents spam probability. A score of `0.5` means "completely uncertain". The dead zone `[lowerBound, upperBound]` is where the statistical model lacks confidence:

- Narrowing the zone (e.g. `[0.45, 0.55]`) → fewer LLM calls, but you miss borderline cases.
- Widening the zone (e.g. `[0.30, 0.70]`) → more LLM calls, faster model improvement.

### Multi-class classifier (`minConfidence` / `minMargin`)

- Raise `minConfidence` to require a stronger winning signal before trusting the classifier.
- Raise `minMargin` to require a bigger lead over the second-best category.
- Both thresholds are independent — either one triggers escalation.

### `autoLearn`

Set to `false` when you want the LLM to act as a fallback but not modify the training data (e.g. read-only mode or when the LLM is unreliable).

## Related

- [LLM-Assisted Classification guide](../guides/llm-assisted-classification.md)
- [LLM Active Learning concept](../concepts/llm-active-learning.md)
