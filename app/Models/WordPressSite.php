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
    use HasFactory;

    protected $table = 'wordpress_sites';

    protected $fillable = [
        'server_id',
        'is_remote',
        'site_name',
        'domain',
        'port',
        'container_name',
        'db_name',
        'db_user',
        'db_password',
        'db_root_password',
        'status',
        'error_message',
        'admin_email',
        'admin_user',
        'admin_password',
    ];

    protected $casts = [
        'is_remote' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Encrypt database passwords
    public function setDbPasswordAttribute($value)
    {
        if ($value) {
            $this->attributes['db_password'] = Crypt::encryptString($value);
        }
    }

    public function getDbPasswordAttribute($value)
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

    public function setDbRootPasswordAttribute($value)
    {
        if ($value) {
            $this->attributes['db_root_password'] = Crypt::encryptString($value);
        }
    }

    public function getDbRootPasswordAttribute($value)
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

    public function setAdminPasswordAttribute($value)
    {
        if ($value) {
            $this->attributes['admin_password'] = Crypt::encryptString($value);
        }
    }

    public function getAdminPasswordAttribute($value)
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
    public function server()
    {
        return $this->belongsTo(Server::class);
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
