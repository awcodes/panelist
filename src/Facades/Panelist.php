<?php

declare(strict_types=1);

namespace Awcodes\Panelist\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Awcodes\Panelist\Panelist
 */
class Panelist extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Awcodes\Panelist\Panelist::class;
    }
}
