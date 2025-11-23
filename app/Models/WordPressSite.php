<?php

namespace App\Models;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Attributes\Boot;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WordPressSite extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'word_press_sites';

    protected $fillable = [
        'name',
        'user_id',
        'domain',
        'container_name',
        'server_ip',
        'server_port',
        'server_username',
        'server_password',
        'server_ssh_key',
        'wp_version',
        'container_port',
        'db_name',
        'db_user',
        'db_password',
        'db_host',
        'status',
        'status_message',
        'last_checked_at',
        'ssl_enabled',
        'docker_config',
    ];

    protected $casts = [
        'docker_config' => 'array',
        'last_checked_at' => 'datetime',
        'ssl_enabled' => 'boolean',
        'server_port' => 'integer',
        'container_port' => 'integer',
    ];

    // Encrypted attributes
    protected $encryptable = [
        'server_ip',
        'server_username',
        'server_password',
        'server_ssh_key',
        'db_name',
        'db_user',
        'db_password',
    ];

    // Automatically encrypt on set
    public function setAttribute($key, $value)
    {
        if (in_array($key, $this->encryptable) && !is_null($value) && $value !== '') {
            $value = Crypt::encryptString($value);
        }
        return parent::setAttribute($key, $value);
    }

    // Automatically decrypt on get
    public function getAttribute($key)
    {
        $value = parent::getAttribute($key);
        
        if (in_array($key, $this->encryptable) && !is_null($value) && $value !== '') {
            try {
                return Crypt::decryptString($value);
            } catch (\Exception $e) {
                return $value;
            }
        }
        
        return $value;
    }

    // Accessor for safe display (mask sensitive data)
    public function getMaskedServerIpAttribute(): string
    {
        $ip = $this->server_ip;
        $parts = explode('.', $ip);
        if (count($parts) === 4) {
            return $parts[0] . '.' . $parts[1] . '.***.' . $parts[3];
        }
        return '***';
    }

    // Check if site is operational
    public function isRunning(): bool
    {
        return $this->status === 'running';
    }

    public function isStopped(): bool
    {
        return $this->status === 'stopped';
    }

    public function isDeploying(): bool
    {
        return $this->status === 'deploying';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    // Get site URL
    public function getUrlAttribute(): string
    {
        $protocol = $this->ssl_enabled ? 'https' : 'http';
        return "{$protocol}://{$this->domain}";
    }

    // user id auto creating by auth id
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected static function boot(){
        parent::boot();
        static::creating(function ($model) {
            $model->user_id = Auth::id();
        });
    }


}
