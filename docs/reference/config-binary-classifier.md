---
sidebar_position: 3
---

# ConfigBinaryClassifier

`ByJG\TextClassifier\ConfigBinaryClassifier` controls the classification algorithm of the BinaryClassifier spam filter. All setters return `$this` for fluent chaining.

## Parameters

| Parameter | Setter | Default | Type | Description |
|---|---|---|---|---|
| `use_relevant` | `setUseRelevant(int)` | `15` | `int` | Maximum number of tokens used during classification. Only the tokens deviating most from `0.5` are used. |
| `min_dev` | `setMinDev(float)` | `0.2` | `float` | Minimum deviation from `0.5` required for a token to be considered relevant. Tokens with `abs(0.5 - p) < min_dev` are ignored. |
| `rob_s` | `setRobS(float)` | `0.3` | `float` | Robinson smoothing weight. Higher values give more weight to the neutral prior when token data is scarce. |
| `rob_x` | `setRobX(float)` | `0.5` | `float` | Robinson neutral prior. The assumed spam probability for a token never seen before. `0.5` = completely neutral. |

## Usage

```php
use ByJG\TextClassifier\ConfigBinaryClassifier;

$config = (new ConfigBinaryClassifier())
    ->setUseRelevant(20)
    ->setMinDev(0.1)
    ->setRobS(0.3)
    ->setRobX(0.5);
```

## Tuning guidance

### `use_relevant`

Increasing this value allows more tokens to contribute to the score, which can improve accuracy on long texts. Decreasing it makes the filter focus only on the strongest signals. Values between `10` and `25` are typical.

### `min_dev`

Lowering this threshold includes more borderline tokens. Setting it to `0` uses all tokens, including those the filter is uncertain about. Raising it makes the filter rely only on very clear spam or ham signals.

### `rob_s` and `rob_x`

These control how the filter handles tokens with little training data:

- `rob_s` higher → rare tokens are pulled more strongly toward `rob_x` (less influence from sparse data)
- `rob_x = 0.5` → no bias for unknown tokens (recommended)
- `rob_x > 0.5` → bias toward spam for unknown tokens (aggressive)
- `rob_x < 0.5` → bias toward ham for unknown tokens (permissive)

## Getters

| Method | Returns |
|---|---|
| `getUseRelevant()` | `int` |
| `getMinDev()` | `float` |
| `getRobS()` | `float` |
| `getRobX()` | `float` |
