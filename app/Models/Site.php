<?php

namespace App\Models;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Site extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'server_id',
        'user_id',
        'domain',
        'container_name',
        'status',
        'database_name',
        'database_user',
        'database_password',
        'admin_username',
        'admin_password',
        'admin_email',
        'ssl',
        'firewall_rules',
        'last_deployed_at',
    ];
    
    protected $casts = [
        'ssl' => 'array',
        'firewall_rules' => 'array',
        'last_deployed_at' => 'datetime',
    ];
    
    public function server()
    {
        return $this->belongsTo(Server::class);
    }
    
    public function setDatabasePasswordAttribute($value)
    {
        $this->attributes['database_password'] = Crypt::encrypt($value);
    }
    
    public function getDatabasePasswordAttribute($value)
    {
        return Crypt::decrypt($value);
    }
    
    public function setAdminPasswordAttribute($value)
    {
        $this->attributes['admin_password'] = Crypt::encrypt($value);
    }
    
    public function getAdminPasswordAttribute($value)
    {
        return Crypt::decrypt($value);
    }
}
