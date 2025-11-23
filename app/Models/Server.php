<?php

namespace App\Models;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Server extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'name',
        'ip_address',
        'ssh_port',
        'ssh_user',
        'ssh_password',
        'ssh_key',
        'connection_type',
        'status',
        'last_error',
        'last_connected_at',
    ];

    protected $casts = [
        'last_connected_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $hidden = [
        'ssh_password',
        'ssh_key',
    ];

    // Encrypt SSH password
    public function setSshPasswordAttribute($value)
    {
        if ($value) {
            $this->attributes['ssh_password'] = Crypt::encryptString($value);
        }
    }

    // Decrypt SSH password
    public function getSshPasswordAttribute($value)
    {
        if ($value) {
            try {
                return Crypt::decryptString($value);
            } catch (\Exception $e) {
                return null;
            }
        }
        return null;
    }

    // Encrypt SSH key
    public function setSshKeyAttribute($value)
    {
        if ($value) {
            $this->attributes['ssh_key'] = Crypt::encryptString($value);
        }
    }

    // Decrypt SSH key
    public function getSshKeyAttribute($value)
    {
        if ($value) {
            try {
                return Crypt::decryptString($value);
            } catch (\Exception $e) {
                return null;
            }
        }
        return null;
    }

    // Relationships
    public function wordPressSites()
    {
        return $this->hasMany(WordPressSite::class);
    }

    // Helper methods
    public function isLocal(): bool
    {
        return in_array($this->ip_address, ['localhost', '127.0.0.1', '::1']);
    }

    public function getConnectionString(): string
    {
        return "{$this->ssh_user}@{$this->ip_address}";
    }

    protected static function boot(){
        parent::boot();
        static::creating(function ($model) {
            $model->user_id = Auth::id();
        });
    }
}
