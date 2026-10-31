<?php

declare(strict_types=1);

namespace Workbench\Database\Seeders;

use Carbon\CarbonImmutable;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Workbench\App\Models\Post;
use Workbench\App\Models\Tag;
use Workbench\Database\Factories\UserFactory;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Fixed posts, oldest first, so the Workbench and its Focus screenshots look the same on every build.
     *
     * @var list<array{title: string, content: string, published: bool, tags: list<string>}>
     */
    protected const POSTS = [
        ['title' => 'Welcome to the new blog', 'content' => 'We moved the blog to a new home. Every post from the old site came along, and the archive is now searchable.', 'published' => true, 'tags' => ['Announcements']],
        ['title' => 'How we plan a release', 'content' => 'Each release starts as a short written proposal. It names the problem, the smallest change that solves it, and how we will know it worked.', 'published' => true, 'tags' => ['Engineering']],
        ['title' => 'Designing for keyboard users', 'content' => 'Every action in the app can now be reached from the keyboard. This post covers the shortcuts we chose and the ones we left out on purpose.', 'published' => true, 'tags' => ['Design', 'Guides']],
        ['title' => 'Release notes: version 2.4', 'content' => 'Version 2.4 adds saved filters, faster exports and a reworked settings page. Upgrading takes a single command.', 'published' => true, 'tags' => ['Releases']],
        ['title' => 'A tour of the settings page', 'content' => 'Settings are now grouped by what you are trying to do rather than by where the data lives. Here is what moved and why.', 'published' => true, 'tags' => ['Guides']],
        ['title' => 'Why we write tests first', 'content' => 'Writing the test first forces us to decide what done looks like before we start. It is slower on day one and faster every day after.', 'published' => false, 'tags' => ['Engineering']],
        ['title' => 'Our accessibility checklist', 'content' => 'We check contrast, focus order, labels and motion on every new screen. The full checklist is below, with the tools we use for each item.', 'published' => true, 'tags' => ['Design', 'Guides']],
        ['title' => 'Release notes: version 2.5', 'content' => 'Version 2.5 brings bulk editing, a new import wizard and dozens of small fixes reported by the community.', 'published' => true, 'tags' => ['Releases']],
        ['title' => 'Meet the support team', 'content' => 'Five people answer every question that reaches us. We asked each of them what they wish more people knew about the product.', 'published' => true, 'tags' => ['Announcements']],
        ['title' => 'Migrating your data safely', 'content' => 'Before you migrate, take a backup and run the import in preview mode. This guide walks through each step and what to check afterwards.', 'published' => false, 'tags' => ['Guides', 'Engineering']],
        ['title' => 'Notes from our design review', 'content' => 'This month we reviewed the onboarding flow. The biggest change: new accounts now start with an example project instead of an empty screen.', 'published' => true, 'tags' => ['Design']],
        ['title' => 'Release notes: version 3.0', 'content' => 'Version 3.0 is our largest release yet, with a new editor, real-time collaboration and a redesigned dashboard.', 'published' => true, 'tags' => ['Releases', 'Announcements']],
    ];

    public function run(): void
    {
        UserFactory::new()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $tags = collect(['Announcements', 'Design', 'Engineering', 'Guides', 'Releases'])
            ->mapWithKeys(fn (string $name): array => [$name => Tag::query()->create(['name' => $name])]);

        $publishedAt = CarbonImmutable::parse('2026-01-05 09:00:00');

        foreach (self::POSTS as $data) {
            $post = Post::query()->forceCreate([
                'title' => $data['title'],
                'content' => $data['content'],
                'is_published' => $data['published'],
                'created_at' => $publishedAt,
                'updated_at' => $publishedAt,
            ]);

            $post->tags()->attach($tags->only($data['tags'])->map->getKey()->values());

            $publishedAt = $publishedAt->addWeek();
        }
    }
}
