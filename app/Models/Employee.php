<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $table = "employee";
    protected $fillable = ['user_id', 'department_id', 'nik', 'name', 'shift'];
    
    public function department(){
        return $this->belongsTo(Department::class);
    }
    public function user(){
        return $this->hasOne(User::class);
    }
    public function attendance(){
        return $this->hasMany(Attendance::class);
    }
}
