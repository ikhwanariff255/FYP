<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName; // <-- Tambah kontrak ini
use Filament\Panel;

class User extends Authenticatable implements FilamentUser, HasName // <-- Implement HasName
{
    use HasFactory, Notifiable;

    protected $table = 'user';          
    protected $primaryKey = 'user_id';  
    public $timestamps = false;         

    protected $fillable = [
        'username',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
    ];

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    // TAMBAH KOD INI SUPAYA FILAMENT BACA 'username' SEBAGAI NAMA PAPARAN
    public function getFilamentName(): string
    {
        return $this->username;
    }
}