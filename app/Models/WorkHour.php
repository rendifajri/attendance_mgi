<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkHour extends Model
{
    protected $table = "work_hour";
    protected $fillable = ['shift', 'day', 'start', 'end'];

}
