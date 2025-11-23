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
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

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
