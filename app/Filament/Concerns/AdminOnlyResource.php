<?php

namespace App\Filament\Concerns;

use Illuminate\Database\Eloquent\Model;

/**
 * Sumber (resource) teknikal: hanya pentadbir boleh lihat dan ubah.
 * Penternak (Customer) tidak nampak menu ini langsung.
 */
trait AdminOnlyResource
{
    protected static function isAdmin(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }

    public static function canViewAny(): bool
    {
        return static::isAdmin();
    }

    public static function canCreate(): bool
    {
        return static::isAdmin();
    }

    public static function canView(Model $record): bool
    {
        return static::isAdmin();
    }

    public static function canEdit(Model $record): bool
    {
        return static::isAdmin();
    }

    public static function canDelete(Model $record): bool
    {
        return static::isAdmin();
    }

    public static function canDeleteAny(): bool
    {
        return static::isAdmin();
    }
}
