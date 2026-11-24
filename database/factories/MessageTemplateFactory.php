<?php

namespace Database\Factories;

use App\Enums\MessageChannel;
use App\Models\MessageTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MessageTemplate>
 */
class MessageTemplateFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => 'test.'.fake()->unique()->word(),
            'channel' => MessageChannel::Mail,
            'name' => fake()->words(3, true),
            'subject' => 'Hello {customer_first_name}',
            'body' => 'Hi {customer_first_name}, your reference is {reference}.',
            'is_active' => true,
        ];
    }
}
