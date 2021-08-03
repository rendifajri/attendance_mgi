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
            "date" => $time_check["date_period"]
        ];
        //$diff_2 = $time_check["diff"] * 2;
        //$attendance_check = Attendance::where($where)->where("checkin", ">", date("Y-m-d H:i:s", strtotime("-{$diff_2} hours")))->orderBy("checkin", "desc")->first();
        $attendance_check = Attendance::where($where)->orderBy("checkin", "desc")->first();
        $message = "";
        $action = "";
        if($attendance_check == null && $time_check["remark"] == "before_work_hour"){
            $message = "You haven't check in.";
            $action = "Check In";
        }
        else if($attendance_check == null && $time_check["remark"] == "after_work_hour"){
            $message = "You didn't check in.";
            $action = "Check Out";
            //$time_check["date_period"] = date("Y-m-d", strtotime("{$time_check["date_period"]} -1 day"));
        }
        else if($attendance_check == null && $time_check["remark"] == "work_hour"){
            $message = "You're late";
            $action = "Check In";
        }
        else if($attendance_check->checkout != null){
            $message = "You already checked out";
            $action = "";
        }
        else if($attendance_check->checkout == null && $time_check["remark"] == "before_work_hour"){// && $attendance_check->checkin < date("Y-m-d H:i:s", strtotime("-{$diff_2} hours"))){
            $message = "You missed yesterday checkout time.";
            $action = "Check In";
        }
        else if($attendance_check->checkout == null && $time_check["remark"] == "after_work_hour"){// && $attendance_check->checkin < date("Y-m-d H:i:s", strtotime("-{$diff_2} hours"))){
            $message = "You haven't check out.";
            $action = "Check Out";
        }
        else{// else if($attendance_check->checkout == null && $time_check["remark"] == "work_hour"){
            $message = "It's still work hour.";
            $action = "Check Out";
        }
        $res = [
            "status" => "success",
            "message" => $message,
            "response" => [
                "action" => $action,
                "attendance" => $attendance_check,
                "time_check" => $time_check
            ]
        ];

        return $res;
    }
    public function timeCheck()
    {
        $where_day = [
            date("N", strtotime("-1 day")),
            date("N"),
            //date("N", strtotime("1 day"))
        ];
        $work_hour = WorkHour::whereIn("day", $where_day)->where("shift", Auth()->user()->employee->shift)->orderBy('day')->get();
        if($work_hour == null)
            throw new \ModelNotFoundException("Work Hour is empty.");
        $first_work_hour = null;
        if(Auth()->user()->employee->shift != 1){
            $first_where = [
                "shift" => 1,
            ];
            $first_work_hour = WorkHour::whereIn("day", $where_day)->where("shift", 1)->get();
        }
        $user_hour = null;
        foreach($work_hour as $val){
            $diff_day   = $val->day - date("N");
            $date_start = date("Y-m-d {$val->start}", strtotime("{$diff_day} days"));
            $date_end   = date("Y-m-d {$val->end}", strtotime("{$diff_day} days"));
            if($date_start > $date_end)
                $date_end = date("Y-m-d H:i:s", strtotime("{$date_end} 1 day"));
            $date_period = date("Y-m-d", strtotime("{$date_start}"));

            if(Auth()->user()->employee->shift != 1){
                $first_val = $first_work_hour->where("day", $val->day)->first();
                if(strtotime($val->start) < strtotime($first_val->start)){
                    $date_start = date("Y-m-d {$val->start}", strtotime("{$date_start} 1 day"));
                    $date_end   = date("Y-m-d H:i:s", strtotime("{$date_end} 1 day"));
                }
            }
            $diff = (strtotime($date_end) - strtotime($date_start))/3600;
            $bef = date("Y-m-d H:i:s", strtotime("{$date_start} -{$diff} hours"));
            //$bef2 = "{$date_start} -{$diff} hours";
            //echo $date_start."|".$date_end."\n";
            //echo $date_end."|".date("Y-m-d H:i:s")."\n";

            $user_hour = [
                "date_period" => $date_period,
                "diff" => $diff,
            ];
            if($date_start <= date("Y-m-d H:i:s") && $date_end >= date("Y-m-d H:i:s")){
                $user_hour["remark"] = "work_hour";
                $user_hour["work_hour"] = $val;
                break;
            }
            else if($bef <= date("Y-m-d H:i:s") && $date_end >= date("Y-m-d H:i:s")){
                $user_hour["remark"] = "before_work_hour";
                $user_hour["work_hour"] = $val;
                break;
            }
            // else if($date_end <= date("Y-m-d H:i:s") && date("Y-m-d", strtotime($date_end)) == date("Y-m-d")){
            //     $user_hour["remark"] = "after_work_hour";
            //     $user_hour["work_hour"] = $val;
            //     break;
            // }
            else{//else if(date("Y-m-d H:i:s") >= $date_end){
                $user_hour["remark"] = "after_work_hour";
                $user_hour["work_hour"] = $val;
            }
        }
        $res = [
            "status" => "success",
            "message" => "Success",
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
        $user_info = $this->userInfo();
        if($user_info["response"]["action"] == ""){
            throw \ValidationException::withMessages([
                "lat" => $user_info["message"],
                "lon" => $user_info["message"]
            ]);
        }
        $config = Config::first();
        $distance = $this->vincentyGreatCircleDistance($config->office_lat, $config->office_lon, $request->lat, $request->lon);// / 1000;
        $distance_fmt = number_format($distance,0,",",".");
        Log::channel("daily")->info("DISTANCE RESULT ".str_pad($distance_fmt, 6, " ", STR_PAD_LEFT).", FROM $config->office_lat, $config->office_lon TO $request->lat, $request->lon");
        if($distance > $config->max_distance){
            throw \ValidationException::withMessages([
                "lat" => "Jarak kantor dan lokasi anda lebih dari 50 M, yaitu {$distance_fmt} M.",
                "lon" => "Jarak kantor dan lokasi anda lebih dari 50 M, yaitu {$distance_fmt} M."
            ]);
        }
        if($user_info["response"]["action"] == "Check In"){
            $attendance = Attendance::create([
                "employee_id" => Auth()->user()->employee->id,
                "shift" => Auth()->user()->employee->shift,
                "date" => $user_info["response"]["time_check"]["date_period"],
                "checkin" => date("Y-m-d H:i:s"),
                "checkout" => null,
                "lat" => $request->lat,
                "lon" => $request->lon
            ]);
            $res = [
                "status" => "success",
                "message" => "Attendance check in success",
                "response" => $attendance
            ];
        }
        else{
            $user_info["response"]["attendance"]->update([
                "checkout" => date("Y-m-d H:i:s"),
                "lat" => $request->lat,
                "lon" => $request->lon
            ]);
            /*$attendance->update([
                "checkout" => date("Y-m-d H:i:s"),
                "lat" => $request->lat,
                "lon" => $request->lon
            ]);*/
            $res = [
                "status" => "success",
                "message" => "Attendance check out success",
                "response" => $user_info["response"]["attendance"]
            ];
        }
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
