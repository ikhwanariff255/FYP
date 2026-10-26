<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    use Notifiable;

    protected $table = 'user';
    protected $primaryKey = 'user_id';

    protected $fillable = [
        'username',
        'email',
        'password',
        'role',
        'device_id',
        'telegram_chat_id',
        'telegram_link_token',
    ];

    protected $hidden = [
        'password',
        'telegram_link_token',
    ];

    // 1. Accessor supaya Filament boleh baca $user->name daripada 'username'
    public function getNameAttribute(): string
    {
        return $this->username ?? '';
    }

    // 2. Kawalan Akses Panel Admin Filament
    public function canAccessPanel(Panel $panel): bool
{
    // Izinkan Administrator, Admin, dan Customer untuk masuk ke panel
    return in_array($this->role, ['Administrator', 'Admin', 'Customer']);
}

    // 3. Peranan: pentadbir nampak semua; pelanggan (penternak) hanya kolam sendiri
    public function isAdmin(): bool
    {
        return in_array($this->role, ['Administrator', 'Admin']);
    }

    public function visiblePonds()
    {
        return PondDevice::query()
            ->unless($this->isAdmin(), fn ($q) => $q->where(fn ($q) => $q
                ->where('user_id', $this->user_id)
                ->when($this->device_id, fn ($q) => $q->orWhere('device_id', $this->device_id))));
    }

    // Hubungan ke PondDevice
    public function device()
    {
        return $this->belongsTo(PondDevice::class, 'device_id', 'device_id');
    }
}