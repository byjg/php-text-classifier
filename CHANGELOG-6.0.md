# Changelog — 6.0

## Breaking changes

### Package and namespace renamed

The package has been renamed from `byjg/b8` to `byjg/text-classifier` and all namespaces have changed accordingly.

| Before | After |
|---|---|
| `B8\` | `ByJG\TextClassifier\` |
| `B8\Lexer\` | `ByJG\TextClassifier\Lexer\` |
| `B8\Degenerator\` | `ByJG\TextClassifier\Degenerator\` |
| `B8\Storage\` | `ByJG\TextClassifier\Storage\` |
| `B8\NaiveBayes\` | `ByJG\TextClassifier\NaiveBayes\` |
| `B8\NaiveBayes\Storage\` | `ByJG\TextClassifier\NaiveBayes\Storage\` |

### Classes renamed

| Before | After |
|---|---|
| `B8` | `BinaryClassifier` |
| `ConfigB8` | `ConfigBinaryClassifier` |

### `classify()` return type changed

Both classifiers now return a `ClassificationResult` object instead of a plain scalar or array. `BinaryClassifier::classify()` no longer returns a raw `float`; `NaiveBayes::classify()` no longer returns `array<string, float>`.

**BinaryClassifier — before:**
```php
$score = $b8->classify($text);  // float|string
if (is_float($score) && $score > 0.8) { /* spam */ }
```

**BinaryClassifier — after:**
```php
use ByJG\TextClassifier\ClassificationResult;

$result = $b8->classify($text);  // ClassificationResult|string
if ($result instanceof ClassificationResult && $result->score > 0.8) { /* spam */ }
```

**NaiveBayes — before:**
```php
$scores = $nb->classify($text);  // array<string, float>
$top    = array_key_first($scores);
```

**NaiveBayes — after:**
```php
$result = $nb->classify($text);  // ?ClassificationResult
echo $result->choice;   // winning category
echo $result->score;    // its score
```

`NaiveBayes::classify()` returns `null` instead of `[]` when no categories are trained yet.

### PHP version requirement

Minimum PHP version raised from `8.1` to `8.3`.

### DBA storage handler changed

The `Dba` storage backend switched from `db4` (BerkeleyDB) to `gdbm`. Existing `db4` database files are not compatible — recreate them with `createDatabase()`.

---

## New features

### `ClassificationResult` DTO

A new read-only value object returned by both classifiers:

| Field | Type | Description |
|---|---|---|
| `choice` | `string` | Winning category (`'spam'`, `'tech'`, …) |
| `score` | `float` | Final score of the winning category `0.0`–`1.0` |
| `scores` | `array<string, float>` | All final scores, sorted descending |
| `statScores` | `array<string, float>` | Raw statistical scores before any LLM escalation |
| `llmDecision` | `string\|null` | What the LLM chose, or `null` if not consulted |
| `escalated` | `bool` | `true` when the LLM was invoked |

### LLM-assisted classification with active learning

An LLM can now be injected directly into `BinaryClassifier` and `NaiveBayes` via their constructors. When the statistical model is uncertain, `classify()` escalates to the LLM internally. With `autoLearn=true` (default), the LLM decision is fed back as training data and the model is re-scored — no wrapper classes needed.

```php
use ByJG\TextClassifier\Llm\ConfigLlm;
use ByJG\TextClassifier\Llm\OpenAiLlmClient;

$llm    = new OpenAiLlmClient($openai, 'gpt-4o-mini');
$config = (new ConfigLlm())
    ->setMinConfidence(0.65)
    ->setMinMargin(0.15)
    ->setAutoLearn(true);

$nb = new NaiveBayes($storage, $lexer, llm: $llm, configLlm: $config);

$result = $nb->classify($text);
// $result->escalated    — true if LLM was called
// $result->llmDecision  — what the LLM decided
// $result->statScores   — score before LLM involvement
```

### New classes

| Class | Description |
|---|---|
| `ByJG\TextClassifier\ClassificationResult` | Read-only result DTO returned by both classifiers |
| `ByJG\TextClassifier\Llm\LlmInterface` | Single-method interface: `classify(string $text, array $categories): string` |
| `ByJG\TextClassifier\Llm\OpenAiLlmClient` | OpenAI-compatible LLM client (works with Ollama too) |
| `ByJG\TextClassifier\Llm\ConfigLlm` | Fluent config for LLM escalation thresholds and `autoLearn` |

### NaiveBayes multi-class engine

`ByJG\TextClassifier\NaiveBayes\NaiveBayes` is a new multi-class Naive Bayes classifier that supports any number of user-defined categories, sharing the same lexer pipeline as `BinaryClassifier`. Backed by in-memory (`Memory`) or SQL (`Rdbms`) storage.

### `NaiveBayes::getCategories()`

New public method returning the list of categories currently present in storage.

---

## New dependencies

| Package | Version |
|---|---|
| `byjg/llm-api-objects` | `^6.0` |
| `openai-php/client` | `^0.19` |
