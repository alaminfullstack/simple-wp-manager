<?php

namespace App\Models;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Server extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'user_id',
        'name',
        'ip_address',
        'port',
        'username',
        'private_key',
        'password',
        'path',
        'active',
    ];
    
    protected $casts = [
        'active' => 'boolean',
    ];
    
    public function sites()
    {
        return $this->hasMany(Site::class);
    }
    
    public function setPrivateKeyAttribute($value)
    {
        $this->attributes['private_key'] = $value ? Crypt::encrypt($value) : null;
    }
    
    public function getPrivateKeyAttribute($value)
    {
        return $value ? Crypt::decrypt($value) : null;
    }
    
    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = $value ? Crypt::encrypt($value) : null;
    }
    
    public function getPasswordAttribute($value)
    {
        return $value ? Crypt::decrypt($value) : null;
    }
}
