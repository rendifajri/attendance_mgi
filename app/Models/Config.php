<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Config extends Model
{
    protected $table = "config";
    protected $fillable = ['office_lat', 'office_lon'];
    // 'office_lat' : required|between:-90,90
    // 'office_lon' : required|between:-180,180

}
