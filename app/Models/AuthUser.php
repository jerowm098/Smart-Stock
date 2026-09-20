<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class AuthUser extends Authenticatable
{
    use Notifiable;

    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
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

    /**
     * Convert Supabase user data to Eloquent-compatible format.
     */
    public static function fromSupabase(array $userData): static
    {
        $user = new static();
        
        // Set all attributes directly (bypassing casts for password)
        foreach ($userData as $key => $value) {
            if ($key !== 'password') {
                $user->{$key} = $value;
            }
        }
        
        // Set password raw to avoid hashing on retrieval
        $user->setRawAttribute('password', $userData['password'] ?? null);
        
        return $user;
    }
}
