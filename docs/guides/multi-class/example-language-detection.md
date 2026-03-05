---
sidebar_position: 5
---

# Example: Language Detection

This example shows how to use the `NaiveBayes` classifier for automatic language detection. The same approach applies to any multi-class text routing problem.

## Setup

```php
use B8\Lexer\ConfigLexer;
use B8\Lexer\StandardLexer;
use B8\NaiveBayes\NaiveBayes;
use B8\NaiveBayes\Storage\Memory;

$storage = new Memory();
$nb = new NaiveBayes(
    $storage,
    new StandardLexer(
        (new ConfigLexer())
            ->setMinSize(2)   // allow short words like "el", "la", "in"
    )
);
```

Lowering `minSize` is important for language detection because many language-specific short words (articles, prepositions) are strong discriminators.

## Training

```php
// French
$nb->train("L'Italie a été gouvernée par Mario Monti président du conseil", 'fr');
$nb->train("Il en faut peu pour passer du statut de renégate dans la politique française", 'fr');

// Spanish
$nb->train("El ex presidente sudafricano Nelson Mandela hospitalizado en Pretoria", 'es');
$nb->train("Guerras continuas y problemas llevaron a un estado de disminución del imperio", 'es');

// English
$nb->train("AI researchers debate whether machines should have emotions or not", 'en');
$nb->train("Scientific problems and the need to understand the human brain through research", 'en');
```

## Classifying

```php
$result = $nb->classify('ciencia filosófica primitiva');
echo array_key_first($result); // 'es'

$result = $nb->classify('scientific problems researchers');
echo array_key_first($result); // 'en'

$result = $nb->classify('Italie gouvernée Monti');
echo array_key_first($result); // 'fr'
```

## Persist the trained model

```php
$storage->save('/var/data/lang-model.json');

// In subsequent requests:
$storage = new Memory();
$storage->load('/var/data/lang-model.json');
$nb = new NaiveBayes($storage, new StandardLexer(new ConfigLexer()));
```

## Adding more languages

Simply train more categories:

```php
$nb->train("Die Bundesregierung hat neue Maßnahmen angekündigt", 'de');
$nb->train("Il governo italiano ha approvato la nuova legge", 'it');
```

No code changes are needed — categories are dynamic.

## Tips for better accuracy

| Tip | Reason |
|---|---|
| Lower `minSize` to 2 | Short function words are language-specific signals |
| Disable `allowNumbers` (already default) | Numbers don't carry language information |
| Train on domain-relevant text | A model trained on news text works best on news |
| Use at least 5–10 samples per language | A single sample is not enough for reliable discrimination |
| Longer training sentences are better | More tokens = more signal per sample |

## Applying to other domains

The same pattern works for:

- Topic classification (sports, tech, politics, finance)
- Sentiment routing (positive, negative, neutral)
- Support ticket triage (billing, technical, general)
- Email intent detection (order, complaint, inquiry)

The only change is the category names and training data.
