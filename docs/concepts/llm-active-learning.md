---
sidebar_position: 6
---

# LLM Active Learning

## The problem with cold-start classifiers

A statistical classifier starts with no knowledge. Until it has been trained on enough examples, it will produce uncertain scores near `0.5` (binary) or near-equal probabilities across categories (multi-class). During this cold-start period, the classifier is not reliable enough to act on alone.

## What active learning does

Active learning closes the loop between classification and training:

1. The statistical classifier scores the text.
2. If the score is **uncertain**, a more capable oracle (here, an LLM) is asked for a definitive label.
3. That label is fed back to the classifier as a new training example.
4. The classifier re-scores the text with the updated model.

Over time, the classifier accumulates real labelled data and needs the LLM fallback less and less. The LLM pays for itself by bootstrapping a model that eventually runs entirely offline.

## Uncertainty detection

### BinaryClassifier (spam/ham)

A score is uncertain when it falls in a configurable dead zone around `0.5`:

```
uncertain = score >= lowerBound AND score <= upperBound
```

Default: `lowerBound = 0.35`, `upperBound = 0.65`.

Scores below `0.35` are confidently ham; scores above `0.65` are confidently spam.

### NaiveBayes (multi-class)

Uncertainty is detected on two axes:

```
uncertain = empty(scores)
         OR max(scores) < minConfidence
         OR (scores[0] - scores[1]) < minMargin
```

Default: `minConfidence = 0.65`, `minMargin = 0.15`.

- **Empty result** — no categories have been trained yet, or all docs are in one category (no cross-category comparison is possible).
- **Low confidence** — the winning category has a weak score.
- **Low margin** — two categories are nearly tied; the classifier is not sure which one is better.

## The feedback loop

```
          text
           │
           ▼
   ┌───────────────┐
   │  Statistical  │  certain → return score
   │  Classifier   │
   └───────┬───────┘
           │ uncertain
           ▼
   ┌───────────────┐
   │     LLM       │ → label ('spam'/'ham' or category name)
   └───────┬───────┘
           │
    autoLearn = true?
           │ yes
           ▼
   classifier.learn(text, label)
           │
           ▼
   classifier.classify(text)  ← return updated score
```

With `autoLearn = false`, the LLM decides but no training happens. The score returned is the original uncertain score.

## Cost considerations

Every LLM call costs tokens. To minimise cost:

- Widen the certain zone (lower `lowerBound`, raise `upperBound` for binary; raise `minConfidence` and `minMargin` for multi-class) — fewer escalations, lower LLM cost, but the statistical model improves more slowly.
- Use a cheap model (e.g. `gpt-4o-mini`) for simple spam/ham or single-word category decisions.
- Persist the training data (`Memory::save()`, or use `Rdbms` backed by a real database) so learning survives restarts.
