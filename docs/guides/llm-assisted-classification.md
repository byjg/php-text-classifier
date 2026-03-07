---
sidebar_position: 10
---

# LLM-Assisted Classification

When a statistical classifier is uncertain, you can escalate to an LLM for a definitive decision. The LLM result is optionally fed back as training data (active learning), so the statistical model improves over time.

The LLM is injected directly into `BinaryClassifier` and `NaiveBayes` via their constructors — no wrapper classes needed.

---

## Spam filter with LLM fallback

```php
use ByJG\TextClassifier\BinaryClassifier;
use ByJG\TextClassifier\ClassificationResult;
use ByJG\TextClassifier\ConfigBinaryClassifier;
use ByJG\TextClassifier\Degenerator\ConfigDegenerator;
use ByJG\TextClassifier\Degenerator\StandardDegenerator;
use ByJG\TextClassifier\Lexer\ConfigLexer;
use ByJG\TextClassifier\Lexer\StandardLexer;
use ByJG\TextClassifier\Llm\ConfigLlm;
use ByJG\TextClassifier\Llm\OpenAiLlmClient;
use ByJG\TextClassifier\Storage\Rdbms;
use ByJG\Util\Uri;

// 1. Build the OpenAI/Ollama client
$openai = OpenAI::client(getenv('OPENAI_API_KEY'));
$llm    = new OpenAiLlmClient($openai, 'gpt-4o-mini');

// 2. Configure escalation thresholds (optional — these are the defaults)
$config = (new ConfigLlm())
    ->setLowerBound(0.35)    // escalate when score >= 0.35 …
    ->setUpperBound(0.65)    // … and score <= 0.65
    ->setAutoLearn(true);    // feed LLM decision back as training data

// 3. Build the classifier with LLM injected
$lexer       = new StandardLexer(new ConfigLexer());
$degenerator = new StandardDegenerator(new ConfigDegenerator());
$storage     = new Rdbms(new Uri('sqlite:///tmp/spam.db'), $degenerator);
$storage->createDatabase();

$classifier = new BinaryClassifier(
    new ConfigBinaryClassifier(),
    $storage,
    $lexer,
    llm: $llm,
    configLlm: $config,
);

// 4. Classify — LLM escalation happens internally when the score is uncertain
$result = $classifier->classify('You have won a free prize! Click here now.');

if ($result instanceof ClassificationResult) {
    echo $result->choice;             // 'spam' or 'ham'
    echo $result->score;              // final score
    echo $result->escalated ? 'LLM was consulted' : 'stat model was sufficient';
}
```

When the statistical score lands in the uncertain zone `[lowerBound, upperBound]`, the LLM is asked to decide. With `autoLearn=true`, the decision is fed back via `learn()` and the classifier re-scores the text with the updated model — `score` in the result reflects this final re-scored value. `statScores` always holds the original statistical score before any LLM involvement.

---

## Multi-class classifier with LLM fallback

```php
use ByJG\TextClassifier\Lexer\ConfigLexer;
use ByJG\TextClassifier\Lexer\StandardLexer;
use ByJG\TextClassifier\Llm\ConfigLlm;
use ByJG\TextClassifier\Llm\OpenAiLlmClient;
use ByJG\TextClassifier\NaiveBayes\NaiveBayes;
use ByJG\TextClassifier\NaiveBayes\Storage\Memory;

// 1. Build the OpenAI/Ollama client
$openai = OpenAI::client(getenv('OPENAI_API_KEY'));
$llm    = new OpenAiLlmClient($openai, 'gpt-4o-mini');

$config = (new ConfigLlm())
    ->setMinConfidence(0.65)   // escalate when top score < 0.65
    ->setMinMargin(0.15)       // escalate when top − second < 0.15
    ->setAutoLearn(true);

// 2. Build NaiveBayes with LLM injected
$storage = new Memory();
$nb = new NaiveBayes(
    $storage,
    new StandardLexer(new ConfigLexer()),
    llm: $llm,
    configLlm: $config,
);

// 3. Train some examples
$nb->train('PHP is a server-side programming language', 'tech');
$nb->train('The cat sat on the mat', 'animals');

// 4. Classify — LLM escalation happens internally when confidence is low
$result = $nb->classify('Python machine learning algorithms');

echo $result->choice;      // 'tech'
echo $result->score;       // final score after any LLM retraining
echo $result->escalated;   // true/false
```

Escalation triggers when any of:
- `classify()` returns `null` (no categories trained yet)
- The top score is below `minConfidence`
- The gap between the top and second score is below `minMargin`

---

## Reading the ClassificationResult

Both classifiers return a `ClassificationResult` with the same fields:

| Field | Type | Description |
|---|---|---|
| `choice` | `string` | Final winning category |
| `score` | `float` | Final score of the winning category |
| `scores` | `array<string, float>` | All final scores, sorted descending |
| `statScores` | `array<string, float>` | Raw statistical scores before LLM escalation |
| `llmDecision` | `string\|null` | What the LLM chose, or `null` if not consulted |
| `escalated` | `bool` | `true` when the LLM was invoked |

```php
$result = $nb->classify($text);
if ($result === null) {
    // no categories trained yet
}

printf(
    "Choice: %s (%.0f%%)  stat: %.0f%%  LLM: %s\n",
    $result->choice,
    $result->score * 100,
    array_values($result->statScores)[0] * 100,
    $result->escalated ? $result->llmDecision : '-',
);
```

---

## Bringing your own LLM

Implement `LlmInterface` to connect any LLM:

```php
use ByJG\TextClassifier\Llm\LlmInterface;

class MyCustomLlm implements LlmInterface
{
    public function classify(string $text, array $categories): string
    {
        // $categories is the full list of allowed labels
        // return exactly one value from $categories
    }
}
```

The single `classify()` method is used for both binary (categories = `['spam', 'ham']`) and multi-class scenarios.

---

## See also

- [LLM active learning concept](../concepts/llm-active-learning.md)
- [ConfigLlm reference](../reference/config-llm.md)
- [ClassificationResult in the spam-filter guide](spam-filter/classifying.md)
- [ClassificationResult in the multi-class guide](multi-class/classifying.md)
