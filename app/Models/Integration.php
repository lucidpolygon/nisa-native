<?php

namespace App\Models;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Integration extends Model
{
    /** @use HasFactory<\Database\Factories\IntegrationFactory> */
    use HasFactory, SoftDeletes;

    public const TYPES = [
        'airtable', 'mysql', 'smtp', 'oauth2'
    ];
    
    protected $fillable = [
        'user_id', 'title', 'type', 'slug',
        'encrypted_value', 'active', 'last_verified_at',
    ];

    protected $casts = [
        'active' => 'boolean',
        'last_verified_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($integration) {
            $integration->slug = $integration->slug ?: self::generateSlug($integration);
        });
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where('slug', $value)
            ->where('user_id', Auth::id())
            ->firstOrFail();
    }

    protected static function generateSlug($integration)
    {
        $base = str($integration->title)->slug('-');
        $slug = $base;
        $n = 1;

        while (
            static::withTrashed()->where('user_id', $integration->user_id)
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = "{$base}-{$n}";
            $n++;
        }

        return $slug;
    }

    public function setEncryptedValueAttribute($value)
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = is_array($decoded) ? $decoded : [$value];
        }
        $this->attributes['encrypted_value'] = Crypt::encryptString(json_encode($value));
    }

    public function getEncryptedValueAttribute($value)
    {
        if (!$value) return [];
        return json_decode(Crypt::decryptString($value ?? ''), true);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
