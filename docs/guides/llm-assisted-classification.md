---
sidebar_position: 10
---

# LLM-Assisted Classification

When a statistical classifier is uncertain, you can escalate to an LLM for a definitive decision. The LLM result is optionally fed back as training data (active learning), so the statistical model improves over time.

Two wrapper classes are provided:

| Wrapper | Wraps |
|---|---|
| `LlmAssistedBinaryClassifier` | `BinaryClassifier` (spam/ham) |
| `LlmAssistedNaiveBayes` | `NaiveBayes` (multi-class) |

---

## Spam filter with LLM fallback

```php
use ByJG\TextClassifier\BinaryClassifier;
use ByJG\TextClassifier\ConfigBinaryClassifier;
use ByJG\TextClassifier\Degenerator\ConfigDegenerator;
use ByJG\TextClassifier\Degenerator\StandardDegenerator;
use ByJG\TextClassifier\Lexer\ConfigLexer;
use ByJG\TextClassifier\Lexer\StandardLexer;
use ByJG\TextClassifier\Llm\ConfigLlm;
use ByJG\TextClassifier\Llm\LlmAssistedBinaryClassifier;
use ByJG\TextClassifier\Llm\OpenAiLlmClient;
use ByJG\TextClassifier\Storage\Rdbms;
use ByJG\Util\Uri;

// 1. Build the statistical classifier
$lexer       = new StandardLexer(new ConfigLexer());
$degenerator = new StandardDegenerator(new ConfigDegenerator());
$storage     = new Rdbms(new Uri('sqlite:///tmp/spam.db'), $degenerator);
$storage->createDatabase();

$classifier = new BinaryClassifier(new ConfigBinaryClassifier(), $storage, $lexer);

// 2. Build the OpenAI client
$openai = OpenAI::client(getenv('OPENAI_API_KEY'));
$llm    = new OpenAiLlmClient($openai, 'gpt-4o-mini');

// 3. Configure escalation thresholds (optional — these are the defaults)
$config = (new ConfigLlm())
    ->setLowerBound(0.35)    // escalate when score >= 0.35 ...
    ->setUpperBound(0.65)    // ... and score <= 0.65
    ->setAutoLearn(true);    // feed LLM decision back as training data

// 4. Wrap
$assisted = new LlmAssistedBinaryClassifier($classifier, $llm, $config);

// 5. Classify
$score = $assisted->classify('You have won a free prize! Click here now.');
if (is_float($score)) {
    echo $score > 0.8 ? 'spam' : 'ham';
}
```

When the statistical score lands in the uncertain zone `[0.35, 0.65]`, the LLM is asked to decide. With `autoLearn=true`, the decision is fed back to the classifier via `learn()` and the final score from the updated model is returned.

---

## Multi-class classifier with LLM fallback

```php
use ByJG\TextClassifier\Lexer\ConfigLexer;
use ByJG\TextClassifier\Lexer\StandardLexer;
use ByJG\TextClassifier\Llm\ConfigLlm;
use ByJG\TextClassifier\Llm\LlmAssistedNaiveBayes;
use ByJG\TextClassifier\Llm\OpenAiLlmClient;
use ByJG\TextClassifier\NaiveBayes\NaiveBayes;
use ByJG\TextClassifier\NaiveBayes\Storage\Memory;

// 1. Build the NaiveBayes classifier
$storage = new Memory();
$nb      = new NaiveBayes($storage, new StandardLexer(new ConfigLexer()));

// Train some examples
$nb->train('PHP is a server-side programming language', 'tech');
$nb->train('The cat sat on the mat', 'animals');

// 2. Wrap with LLM assistance
$openai   = OpenAI::client(getenv('OPENAI_API_KEY'));
$llm      = new OpenAiLlmClient($openai, 'gpt-4o-mini');
$config   = (new ConfigLlm())
    ->setMinConfidence(0.65)   // escalate when top score < 0.65
    ->setMinMargin(0.15)       // escalate when top - second < 0.15
    ->setAutoLearn(true);

$assisted = new LlmAssistedNaiveBayes($nb, $llm, $config);

// 3. Classify
$scores = $assisted->classify('Python machine learning algorithms');
echo array_key_first($scores); // 'tech'
```

Escalation triggers when any of:
- The classifier returns an empty result (no categories trained yet)
- The top score is below `minConfidence`
- The gap between top and second score is below `minMargin`

---

## Bringing your own LLM

Implement `LlmInterface` to connect any LLM:

```php
use ByJG\TextClassifier\Llm\LlmInterface;

class MyCustomLlm implements LlmInterface
{
    public function decideSpamHam(string $text): string
    {
        // call your LLM, return 'spam' or 'ham'
    }

    public function decideCategory(string $text, array $categories): string
    {
        // call your LLM, return one value from $categories
    }
}
```

---

## Training and untraining

The wrappers only handle `classify()`. To train or untrain, call the underlying classifier directly:

```php
$classifier->learn('Buy cheap pills now!', BinaryClassifier::SPAM);
$classifier->unlearn('Buy cheap pills now!', BinaryClassifier::SPAM);

$nb->train('Python is a language', 'tech');
$nb->untrain('Python is a language', 'tech');
```

---

## See also

- [LLM active learning concept](../concepts/llm-active-learning.md)
- [ConfigLlm reference](../reference/config-llm.md)
