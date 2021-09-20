<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $table = "attendance";
    protected $fillable = ['employee_id', 'date', 'shift', 'checkin', 'checkout', 'lat', 'lon'];
    
    public function employee(){
        return $this->belongsTo(Employee::class);
    }
}