---
sidebar_position: 2
---

# NaiveBayes Class

`ByJG\TextClassifier\NaiveBayes\NaiveBayes` is the multi-class Naive Bayes text classifier.

## Constructor

```php
new NaiveBayes(
    StorageInterface  $storage,
    LexerInterface    $lexer,
    ConfigNaiveBayes  $config    = new ConfigNaiveBayes(),
    ?LlmInterface     $llm       = null,
    ?ConfigLlm        $configLlm = null,
)
```

| Parameter | Type | Description |
|---|---|---|
| `$storage` | `ByJG\TextClassifier\NaiveBayes\Storage\StorageInterface` | Persistence backend |
| `$lexer` | `ByJG\TextClassifier\Lexer\LexerInterface` | Tokeniser |
| `$config` | `ConfigNaiveBayes` | Smoothing parameters (optional) |
| `$llm` | `LlmInterface\|null` | Optional LLM for low-confidence escalation |
| `$configLlm` | `ConfigLlm\|null` | LLM escalation thresholds (defaults apply when `null`) |

## Methods

### train()

```php
public function train(string $text, string $category): void
```

Trains the classifier with `$text` as an example of `$category`. The category is created automatically if it does not exist.

- Increments the document count for `$category` by 1
- Increments the token count for each unique token in `$text`, scaled by its occurrence count

### untrain()

```php
public function untrain(string $text, string $category): void
```

Reverses a previous `train()` call. Decrements document and token counts. If a category's document count reaches zero, it is removed from storage.

### getCategories()

```php
public function getCategories(): array<string>
```

Returns the list of categories currently present in storage.

### classify()

```php
public function classify(string $text): ?ClassificationResult
```

Classifies `$text` and returns a `ClassificationResult`, or `null` when no categories have been trained yet.

| Return value | Meaning |
|---|---|
| `ClassificationResult` | Classification succeeded |
| `null` | No categories trained, or only one category exists (one-vs-rest requires ≥ 2) |

```php
$result = $nb->classify('machine learning algorithms');

echo $result->choice;   // 'tech'
echo $result->score;    // 0.91
```

When an LLM is injected and `autoLearn=true`, the model is retrained on the LLM decision before returning. `statScores` always reflects the raw score before any LLM involvement.

## ClassificationResult fields

| Field | Type | Description |
|---|---|---|
| `choice` | `string` | Winning category name |
| `score` | `float` | Final score of the winning category `0.0`–`1.0` |
| `scores` | `array<string, float>` | All final scores, sorted descending |
| `statScores` | `array<string, float>` | Raw statistical scores before any LLM escalation |
| `llmDecision` | `string\|null` | LLM's label if consulted, otherwise `null` |
| `escalated` | `bool` | `true` when the LLM was invoked |

## Usage example

```php
use ByJG\TextClassifier\Lexer\ConfigLexer;
use ByJG\TextClassifier\Lexer\StandardLexer;
use ByJG\TextClassifier\NaiveBayes\NaiveBayes;
use ByJG\TextClassifier\NaiveBayes\Storage\Memory;

$nb = new NaiveBayes(
    new Memory(),
    new StandardLexer(new ConfigLexer())
);

$nb->train('PHP is a programming language', 'tech');
$nb->train('The dog ran in the park', 'animals');

$result = $nb->classify('programming language');
echo $result->choice;  // 'tech'
echo $result->score;   // e.g. 0.87
```

## Related

- [ConfigNaiveBayes reference](config-naive-bayes.md)
- [ConfigLlm reference](config-llm.md)
- [How NaiveBayes works](../concepts/how-naive-bayes-works.md)
- [Training guide](../guides/multi-class/training.md)
- [Classifying guide](../guides/multi-class/classifying.md)
- [LLM-Assisted Classification](../guides/llm-assisted-classification.md)
