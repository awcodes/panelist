---
title: Customizing the editor
description: Extend Panelist's record editor to change how records are filled, validated and saved.
---

# Customizing the editor

The editor on the right of the layout is a Livewire component, `Awcodes\Panelist\Livewire\RecordEditor`. It fills the resource's form, saves new and existing records, and deletes them. To customize that behavior, extend the component and point your Panelist page at the subclass.

## Creating a custom editor

Extend `RecordEditor` and override the hooks you need:

```php
use Awcodes\Panelist\Livewire\RecordEditor;

class PostEditor extends RecordEditor
{
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();

        return $data;
    }

    protected function afterSave(): void
    {
        // ...
    }
}
```

Then set the page's `$editor` property to your class:

```php
class ManagePosts extends PanelistPage
{
    protected static string $resource = PostResource::class;

    protected static string $editor = PostEditor::class;
}
```

Panelist registers the editor with Livewire automatically, so there is nothing else to configure. The class must extend `RecordEditor`.

## Available hooks

The editor supports the same hooks as Filament's create and edit pages:

| Purpose | Methods |
|---|---|
| Mutate form data | `mutateFormDataBeforeFill()`, `mutateFormDataBeforeSave()`, `mutateFormDataBeforeCreate()` |
| Replace persistence | `handleRecordCreation()`, `handleRecordUpdate()` |
| Lifecycle hooks | `beforeFill()`, `afterFill()`, `beforeValidate()`, `afterValidate()`, `beforeSave()`, `afterSave()`, `beforeCreate()`, `afterCreate()`, `beforeDelete()`, `afterDelete()` |

`mutateFormDataBeforeSave()` applies when an existing record is saved, and `mutateFormDataBeforeCreate()` when a new record is created. The `beforeSave()` and `afterSave()` hooks run for existing records, and `beforeCreate()` and `afterCreate()` for new ones.

To stop a save from a hook, throw `Filament\Support\Exceptions\Halt`.

## What the editor handles for you

Whether or not you customize it, the editor:

- checks the resource's `canCreate()`, `canEdit()` and `canDelete()` authorization before creating, saving or deleting;
- only loads records returned by the resource's `getEloquentQuery()`, so tenancy and other query scoping apply;
- saves relationship fields, such as a multiple `Select` using `relationship()`, for new and existing records;
- wraps saves in a database transaction when the panel enables `databaseTransactions()`.
