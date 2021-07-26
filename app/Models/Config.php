<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Config extends Model
{
    protected $table = "config";
    protected $fillable = ['max_distance', 'office_lat', 'office_lon'];

}
