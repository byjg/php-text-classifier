<?php

namespace ByJG\TextClassifier\Llm;

use ByJG\LlmApiObjects\Enum\Role;
use ByJG\LlmApiObjects\Model\Chat;
use ByJG\LlmApiObjects\Model\Message;
use OpenAI\Client;
use UnexpectedValueException;

class OpenAiLlmClient implements LlmInterface
{
    public function __construct(
        private Client $client,
        private string $model = 'gpt-4o-mini'
    ) {}

    /**
     * @param string[] $categories
     */
    #[\Override]
    public function classify(string $text, array $categories): string
    {
        $list   = implode(', ', $categories);
        $system = "Classify the following text into exactly one of these categories: $list. Reply ONLY with the category name, nothing else.";

        $chat = new Chat(
            model: $this->model,
            messages: [new Message(role: Role::user, message: $text)],
            system: $system
        );

        $response = $this->client->chat()->create($chat->toApi());
        $result   = trim($response->choices[0]->message->content ?? '');

        if (!in_array($result, $categories, true)) {
            throw new UnexpectedValueException("LLM returned unexpected category: '$result'. Expected one of: $list.");
        }

        return $result;
    }
}
