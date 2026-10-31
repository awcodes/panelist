<?php

declare(strict_types=1);

use Awcodes\Focus\Card;
use Awcodes\Focus\Enums\Size;
use Awcodes\Focus\Screenshot;
use Awcodes\Focus\ScreenshotSuite;

/*
 * Documentation screenshots for Panelist, generated with awcodes/focus from the Workbench (run `composer build`
 * first). The Workbench seeds a fixed set of posts, newest first, so each build renders the same list.
 *
 * Records are opened from the keyboard rather than by key, so the screenshots do not depend on database ids:
 * `j` highlights the first row and Enter opens it.
 */

// The awcodes card templates frame each screenshot at 1400x816, showing slot 1 dark and slot 2 light.
$card = [1400, 816];

$openFirstPost = fn (Screenshot $screenshot): Screenshot => $screenshot
    ->visit('/admin/posts')
    ->waitFor('.panelist-sidebar [wire\:key*=".table.records."]')
    ->press('j')
    ->press('Enter')
    ->waitFor('.panelist-selected-record');

return ScreenshotSuite::make()
    ->padding(16)
    ->screenshots([
        // The full layout: the list on the left and the newest post open in the editor.
        $openFirstPost(Screenshot::make('layout'))
            ->focus('.panelist-layout'),

        // Keyboard navigation: the first post is open and the focus ring has moved two rows down.
        $openFirstPost(Screenshot::make('keyboard-navigation'))
            ->press('j')
            ->press('j')
            ->waitFor('.panelist-focused-record')
            ->focus('.panelist-sidebar'),

        // Create mode, opened with the `c` shortcut.
        Screenshot::make('create')
            ->visit('/admin/posts')
            ->waitFor('.panelist-sidebar [wire\:key*=".table.records."]')
            ->press('c')
            ->waitFor('.panelist-editor input[type="text"]')
            ->focus('.panelist-layout'),

        // Card source: the whole page with a post open, captured in both themes for the two card slots.
        $openFirstPost(Screenshot::make('card-layout'))
            ->viewportSize(...$card)
            ->viewport(),
    ])
    ->cardTemplates('https://github.com/awcodes/focus-templates/tree/v2.1.0/dist')
    ->cards([
        // Open Graph and the GitHub social preview share one 2400x1260 template; GitHub crops 30px top and bottom.
        Card::make('social')
            ->template('two-up-wide')
            ->title('Panelist')
            ->screenshots(['card-layout', 'card-layout'])
            ->sizes([Size::OpenGraph, Size::GitHubSocial]),

        // The Filament plugin directory's 2560x1440 thumbnail.
        Card::make('thumbnail')
            ->template('two-up')
            ->title('Panelist')
            ->screenshots(['card-layout', 'card-layout'])
            ->sizes([Size::Filament]),

        // Unbranded 16:9 image for aw.codes, which adds its own heading: the same screenshots, no text or logo.
        Card::make('plain')
            ->template('two-up-plain')
            ->screenshots(['card-layout', 'card-layout'])
            ->sizes([[2560, 1440]])
            ->scale(1),
    ]);
