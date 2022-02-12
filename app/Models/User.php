<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Role;
use App\Models\User;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'role_id',
        'firstname',
        'lastname',
        'email',
        'password',
        'gender',
        'phone'
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
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function isAdmin()
    {
        if($this->role_id === 2)
        { 
            return true; 
        } 
        else 
        { 
            return false; 
        }
    }

    public function hasRole($role)
    {
        $res = Role::where('name', $role)->first();
        $test = User::where('role_id', $res->id)
                    ->where('id', $this->id)->first();         
        if($test){
            return true;
        }else{
            return false;
        }
    }


    public function role()
    {
        return $this->belongsTo('App\Models\Role');
    }
    public function posts()
    {
        return $this->hasMany('App\Models\Post');
    }

    public function scopeAuthors($query)
    {
        return $query->where('role_id', 1);
    }


    public function sendPasswordResetNotification($token)
    {
        $web_url = env("WEB_URL", "http://localhost:8000");
        $url = $web_url.'/password-reset?token=' . $token .'&state=change';

        $this->notify(new ResetPasswordNotification($url));
    }
}
