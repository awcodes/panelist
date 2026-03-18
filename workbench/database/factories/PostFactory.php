<?php

declare(strict_types=1);

namespace Workbench\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Workbench\App\Models\Post;

/**
 * @template TModel of \Workbench\App\Models\Post
 *
 * @extends Factory<TModel>
 */
class PostFactory extends Factory
{
    /**
     * @var class-string<TModel>
     */
    protected $model = Post::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'content' => fake()->paragraphs(3, true),
            'is_published' => fake()->boolean(70),
        ];
    }
}
