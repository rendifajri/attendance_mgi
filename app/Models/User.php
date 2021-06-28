<?php

namespace App\Models;

//use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    //use HasFactory;
    protected $table = "user";
    protected $fillable = ['username', 'name', 'password', 'role', 'api_token'];

    public function employee(){
        return $this->hasOne(employee::class);
    }
}
