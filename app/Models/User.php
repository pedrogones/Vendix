<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Filament\Panel\Concerns\HasUserMenu;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements HasAvatar, FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'archive_id',
        'custom_fields',
        'avatar_url'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function client()
    {
        return $this->hasOne(Client::class);
    }

    public function productFavorites(): HasMany
    {
        return $this->hasMany(ProductFavorite::class);
    }

    public function favoriteProducts(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_favorites')
            ->withTimestamps();
    }
//    public function avatar()
//    {
//        return $this->belongsTo(Archive::class, 'archive_id');
//    }

    public function getFilamentAvatarUrl(): ?string
    {
        if (! $this->avatar_url) {
            return null;
        }

        return asset('storage/' . $this->avatar_url);
    }
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->hasRole('Admin');
    }
//    public function setAvatarAttribute($value): void
//    {
//        $this->attributes['avatar'] = $value ? 'avatars/' . $value : null;
//    }
//
//    public function getAvatarUrlAttribute(): ?string
//    {
//        return $this->avatar ? asset('storage/' . $this->avatar) : null;
//    }
//}
}
