<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $table = "attendance";
    protected $fillable = ['employee_id', 'shift', 'checkin', 'checkout', 'lat', 'lon'];
    
    public function employee(){
        return $this->belongsTo(employee::class);
    }
    // Log::channel('daily')->info($res);
// function distance($lat1, $lon1, $lat2, $lon2, $unit) {

//   $theta = $lon1 - $lon2;
//   $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
//   $dist = acos($dist);
//   $dist = rad2deg($dist);
//   $miles = $dist * 60 * 1.1515;
//   $unit = strtoupper($unit);

//   if ($unit == "K") {
//     return ($miles * 1.609344);
//   } else if ($unit == "N") {
//       return ($miles * 0.8684);
//     } else {
//         return $miles;
//       }
// }

// echo distance(32.9697, -96.80322, 29.46786, -98.53506, "M") . " Miles<br>";
// echo distance(32.9697, -96.80322, 29.46786, -98.53506, "K") . " Kilometers<br>";
// echo distance(32.9697, -96.80322, 29.46786, -98.53506, "N") . " Nautical Miles<br>";
}
