<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Models\Attendance;
use App\Models\Config;
use App\Models\WorkHour;

class AttendanceController extends Controller
{
    public function index()
    {
        $attendance = Attendance::orderBy("employee_id")->orderBy("checkin", "desc")->get();
        foreach($attendance as $val){
            $val->employee = $val->employee;
        }
        $res = [
            "status" => "success",
            "message" => "Get Attendance list success",
            "response" => $attendance
        ];

        return response($res);
    }
    public function detail($id)
    {
        $attendance = Attendance::find($id);
        if($attendance == null)
            throw new \ModelNotFoundException("Attendance not found.");
        else {
            $attendance->employee = $attendance->employee;
            $res = [
                "status" => "success",
                "message" => "Get Attendance success",
                "response" => $attendance
            ];
        }

        return response($res);
    }
    public function userInfo()
    {
        $time_check = $this->timeCheck()["response"];
        $where = [
            "employee_id" => Auth()->user()->employee->id,
            "shift" => Auth()->user()->employee->shift,
        ];
        $attendance = Attendance::where($where)->where("checkin", ">", date("Y-m-d H:i:s", strtotime("-16 hours")))->orderBy("checkin", "desc")->first();
        $message = "";
        if($attendance == null && $time_check["remark"] == "before_work_hour")
            $message = "Anda belum checkin. Silahkan checkin.";
        else if($attendance == null && $time_check["remark"] == "after_work_hour")
            $message = "Hari ini anda tidak checkin. Silahkan checkout.";
        else if($attendance == null && $time_check["remark"] == "work_hour")
            $message = "Anda terlambat. Silahkan checkin.";
        else if($attendance->checkout != null)
            $message = "Anda sudah checkout.";
        else if($attendance->checkout == null && $time_check["remark"] == "before_work_hour") 
            $message = "Anda telah melewati waktu checkout. Silahkan checkin.";
        else if($attendance->checkout == null && $time_check["remark"] == "after_work_hour") 
            $message = "Anda belum checkout. Silahkan checkout.";
        else if($attendance->checkout == null && $time_check["remark"] == "work_hour") 
            $message = "Belum waktu checkout.";
        $res = [
            "status" => "success",
            "message" => $message,
            "response" => [
                "attendance" => $attendance,
                "time_check" => $time_check
            ]
        ];

        return response($res);
    }
    public function timeCheck()
    {
        $where_day = [
            date("N", strtotime("-1 day")),
            date("N"),
            date("N", strtotime("1 day"))
        ];
        $work_hour = WorkHour::whereIn("day", $where_day)->where("shift", Auth()->user()->employee->shift)->get();
        $first_work_hour = null;
        if(Auth()->user()->employee->shift != 1){
            $first_where = [
                "shift" => 1,
            ];
            $first_work_hour = WorkHour::whereIn("day", $where_day)->where("shift", 1)->get();
        }
        $user_hour = null;
        foreach($work_hour as $val){
            $date_start = date("Y-m-d {$val->start}");
            $date_end = date("Y-m-d {$val->end}");
            if($date_start > $date_end)
                $date_end = date("Y-m-d H:i:s", strtotime("{$date_end} 1 day"));
            $str_add = "0 day";
            if(strtotime("now") > strtotime($date_end))
                $str_add = "1 day";
            $date_start = date("Y-m-d H:i:s", strtotime("{$date_start} {$str_add}"));
            $date_end = date("Y-m-d H:i:s", strtotime("{$date_end} {$str_add}"));
            $diff = (strtotime($date_end) - strtotime($date_start))/3600;
            $bef = date("Y-m-d H:i:s", strtotime("{$date_start} -{$diff} hours"));
            //$bef2 = "{$date_start} -{$diff} hours";
            if(Auth()->user()->employee->shift != 1){
                $first_val = $first_work_hour->where("day", $val->day)->first();
                $first_date_start = date("Y-m-d {$first_val->start}");
                if($date_start < $first_date_start)
                    $bef = date("Y-m-d H:i:s", strtotime("{$bef} 1 day"));
            }
            //echo $date_start." ".date("Y-m-d H:i:s")." && ".$date_end." ".date("Y-m-d H:i:s")."\n";
            if($date_start <= date("Y-m-d H:i:s") && $date_end >= date("Y-m-d H:i:s")){
                $user_hour = [
                    //"bef" => $bef,
                    //"bef2" => $bef2,
                    "remark" => "work_hour",
                    "work_hour" => $val
                ];
                break;
            }
            else if(date("Y-m-d H:i:s") >= $bef){
                $user_hour = [
                    //"bef" => $bef,
                    //"bef2" => $bef2,
                    "remark" => "before_work_hour",
                    "work_hour" => $val
                ];
                break;
            }
            else{//else if(date("Y-m-d H:i:s") >= $date_end){
                $user_hour = [
                    //"bef" => strtotime("now"),
                    //"bef2" => strtotime($val->end),
                    "remark" => "after_work_hour",
                    "work_hour" => $val
                ];
            }
        }
        $res = [
            "status" => $user_hour != null ? "success" : "not_found",
            "message" => $user_hour != null ? "Success" : "Work Hour is empty",
            "response" => $user_hour
        ];
        return $res;
        //return response($res);
    }
    public function tap(Request $request)
    {
        $valid_arr = [
            //"force_checkin" => "required|integer|between:0,1",
            "lat" => "required|between:-90,90",
            "lon" => "required|between:-180,180"
        ];
        $valid = Validator::make($request->all(), $valid_arr);
        if ($valid->fails())
            throw new \ValidationException($valid);
        $config = Config::first();
        $distance = $this->vincentyGreatCircleDistance($config->office_lat, $config->office_lon, $request->lat, $request->lon);// / 1000;
        $distance_fmt = number_format($distance,0,",",".");
        Log::channel("daily")->info("DISTANCE RESULT ".str_pad($distance_fmt, 6, " ", STR_PAD_LEFT).", FROM $config->office_lat, $config->office_lon TO $request->lat, $request->lon");
        $where = [
            "employee_id" => Auth()->user()->employee->id,
            "shift" => Auth()->user()->employee->shift,
        ];
        $attendance_check = Attendance::where($where)->orderBy("checkin", "desc")->first();
        $do_next_checkin = true;
        // if($attendance_check != null){
        //     if($attendance_check->checkout != null)
        //         throw new \ModelNotFoundException("You're already checked out.");
        //     else{
        //         // $attendance_check->checkin
        //         $do_next_checkin = false;
        //     }
        // }
        if($distance > $config->max_distance){
            throw \ValidationException::withMessages([
                "lat" => "Jarak kantor dan lokasi anda lebih dari 50 M, yaitu {$distance_fmt} M.",
                "lon" => "Jarak kantor dan lokasi anda lebih dari 50 M, yaitu {$distance_fmt} M."
            ]);
        }
        // if($do_next_checkin){//|| $force_checkin == 1){
        //     $attendance = Attendance::create([
        //         "employee_id" => Auth()->user()->employee->id,
        //         "shift" => Auth()->user()->employee->shift,
        //         "checkin" => date("Y-m-d H:i:s"),
        //         "checkout" => null,
        //         "lat" => $request->lat,
        //         "lon" => $request->lon
        //     ]);
        //     $res = [
        //         "status" => "success",
        //         "message" => "Attendance check in success",
        //         "response" => $attendance
        //     ];
        // }
        // else{
        //     $attendance_check->update([
        //         "checkout" => date("Y-m-d H:i:s"),
        //         "lat" => $request->lat,
        //         "lon" => $request->lon
        //     ]);
        //     $res = [
        //         "status" => "success",
        //         "message" => "Attendance check out success",
        //         "response" => $attendance_check
        //     ];
        // }
        $res = $this->timeCheck();
        return response($res);
    }
    public function delete($id)
    {
        $attendance = Attendance::find($id);
        if($attendance == null)
            throw new \ModelNotFoundException("Attendance not found.");
        else {
            $attendance->delete();
            $res = [
                "status" => "success",
                "message" => "Delete Attendance success",
                "response" => $attendance
            ];
        }

        return response($res);
    }
    private function vincentyGreatCircleDistance($latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo, $earthRadius = 6371000)
    {
        $latFrom = deg2rad($latitudeFrom);
        $lonFrom = deg2rad($longitudeFrom);
        $latTo = deg2rad($latitudeTo);
        $lonTo = deg2rad($longitudeTo);

        $lonDelta = $lonTo - $lonFrom;
        $a = pow(cos($latTo) * sin($lonDelta), 2) +
            pow(cos($latFrom) * sin($latTo) - sin($latFrom) * cos($latTo) * cos($lonDelta), 2);
        $b = sin($latFrom) * sin($latTo) + cos($latFrom) * cos($latTo) * cos($lonDelta);
        $angle = atan2(sqrt($a), $b);

        return $angle * $earthRadius;
    }
}
