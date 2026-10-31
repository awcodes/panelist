<?php

declare(strict_types=1);

namespace Awcodes\Panelist\Tests\Fixtures;

use Workbench\App\Models\User;

class PostPolicy
{
    /**
     * Policy abilities the current test denies; everything else is allowed.
     *
     * @var array<string>
     */
    public static array $denied = [];

    public function viewAny(User $user): bool
    {
        return $this->allows('viewAny');
    }

    public function view(User $user): bool
    {
        return $this->allows('view');
    }

    public function create(User $user): bool
    {
        return $this->allows('create');
    }

    public function update(User $user): bool
    {
        return $this->allows('update');
    }

    public function delete(User $user): bool
    {
        return $this->allows('delete');
    }

    public function deleteAny(User $user): bool
    {
        return $this->allows('deleteAny');
    }

    protected function allows(string $ability): bool
    {
        return ! in_array($ability, static::$denied, true);
    }
}
