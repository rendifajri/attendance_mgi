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
        $attendance = Attendance::orderBy("employee_id")->orderBy("checkin", 'desc')->get();
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
        $where = [
            "employee_id" => Auth()->user()->employee->id,
            "shift" => Auth()->user()->employee->shift,
            "checkout" => null
        ];
        $attendance = Attendance::where($where)->orderBy("checkin", 'desc')->first();
        if($attendance == null)
            throw new \ModelNotFoundException("Attendance not found.");
        else {
            $where = [
                "day" => date("N", strtotime($attendance_check->checkin)),
                "shift" => $attendance->shift,
            ];
            $work_hour_check = WorkHour::where($where)->first();
            $attendance->employee = $attendance->employee;
            $attendance->checkout_warning = $attendance->employee;
            $res = [
                "status" => "success",
                "message" => "Get Attendance success",
                "response" => $attendance
            ];
        }

        return response($res);
    }
    public function temp(Request $request)
    {
        $datenp = date("N") + 1;
        if($datenp == 8)
            $datenp = 1;

        $where = [
            "day" => $datenp,
            "shift" => Auth()->user()->employee->shift,
        ];
        $work_hour = WorkHour::where($where)->first();
        $diff = abs((strtotime(date('Y-m-d '.$work_hour->start)) - strtotime(date('Y-m-d '.$work_hour->end)))/3600);
        //if(date('Y-m-d H:i:s') > )
        $data = [
            "date" => date('Y-m-d H:i:s'),
            "daten" => date('N'),
            "datenp" => $datenp,
            "diff" => $diff,
            "shift" => Auth()->user()->employee->shift,
            "work_hour" => $work_hour,
        ];
        $res = [
            "status" => "success",
            "message" => "Update Attendance success",
            "response" => $data
        ];
        return response($res);
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
        $distance_fmt = number_format($distance,0,',','.');
        Log::channel('daily')->info("DISTANCE RESULT ".str_pad($distance_fmt, 6, " ", STR_PAD_LEFT).", FROM $config->office_lat, $config->office_lon TO $request->lat, $request->lon");
        $where = [
            "employee_id" => Auth()->user()->employee->id,
            //"shift" => Auth()->user()->employee->shift,
        ];
        $attendance_check = Attendance::where($where)->orderBy("checkin", 'desc')->first();
        $do_next_checkin = true;
        if($attendance_check != null){
            if($attendance_check->checkout != null)
                throw new \ModelNotFoundException("You're already checked out.");
            else
                $do_next_checkin = false;
        }
        if($distance > $config->max_distance){
            throw \ValidationException::withMessages([
                "lat" => "Jarak kantor dan lokasi anda lebih dari 50 M, yaitu ".$distance_fmt." M.",
                "lon" => "Jarak kantor dan lokasi anda lebih dari 50 M, yaitu ".$distance_fmt." M."
            ]);
        }
        if($do_next_checkin){//|| $force_checkin == 1){
            $attendance = Attendance::create([
                "employee_id" => Auth()->user()->employee->id,
                "shift" => Auth()->user()->employee->shift,
                "checkin" => date('Y-m-d H:i:s'),
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
            $attendance_check->update([
                "checkout" => date('Y-m-d H:i:s'),
                "lat" => $request->lat,
                "lon" => $request->lon
            ]);
            $res = [
                "status" => "success",
                "message" => "Attendance check out success",
                "response" => $attendance_check
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
