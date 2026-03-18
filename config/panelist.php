<?php

declare(strict_types=1);

// config for Awcodes/Panelist
return [

    /*
    |--------------------------------------------------------------------------
    | Auto Register Resources
    |--------------------------------------------------------------------------
    | When enabled, all resources that use the HasPanelist trait will
    | automatically have their index page replaced with the panelist layout.
    | This is opt-in and disabled by default for safety.
    */
    'auto_register_resources' => false,

    /*
    |--------------------------------------------------------------------------
    | Sidebar Width
    |--------------------------------------------------------------------------
    | The default width (in pixels) of the sidebar panel.
    | This value is used as a fallback when no persisted width is found
    | in localStorage.
    */
    'sidebar_width' => 320,

    /*
    |--------------------------------------------------------------------------
    | Enable Keyboard Shortcuts
    |--------------------------------------------------------------------------
    | When enabled, keyboard shortcuts are available for navigating records,
    | opening records, saving, creating, and cancelling.
    */
    'enable_keyboard_shortcuts' => true,

];
