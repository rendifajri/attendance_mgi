<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Models\Config;
use App\Models\Attendance;

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
    public function tap(Request $request)
    {
        $valid_arr = [
            "lat" => "required|between:-90,90",
            "lon" => "required|between:-180,180"
        ];
        $valid = Validator::make($request->all(), $valid_arr);
        if ($valid->fails())
            throw new \ValidationException($valid);
        $config = Config::first();
        $distance = $this->vincentyGreatCircleDistance($config->office_lat, $config->office_lon, $request->lat, $request->lon);// / 1000;
        $distance_fmt = number_format($distance,0,',','.');
        Log::channel('daily')->info("DISTANCE RESULT ".str_pad($distance_fmt, 10, " ", STR_PAD_LEFT).", FROM $config->office_lat, $config->office_lon TO $request->lat, $request->lon");
        $where = [
            "employee_id" => Auth()->user()->employee->id,
            //"shift" => Auth()->user()->employee->shift,
        ];
        $attendance_check = Attendance::where($where)->whereDate("checkin", date('Y-m-d'))->first();
        //dd($attendance_check);
        if($attendance_check == null){
            if($distance > 50){//lebih dari 0.05 Kilometer atau 50 Meter
                throw \ValidationException::withMessages([
                    "lat" => "Jarak kantor dan lokasi anda lebih dari 50 M, yaitu ".$distance_fmt." M.",
                    "lon" => "Jarak kantor dan lokasi anda lebih dari 50 M, yaitu ".$distance_fmt." M."
                ]);
            }
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
            //echo($attendance_check->checkout);
            if($attendance_check->checkout != null)
                throw new \ModelNotFoundException("You're already checked out.");
            if($distance > 50){//lebih dari 0.05 Kilometer atau 50 Meter
                throw \ValidationException::withMessages([
                    "lat" => "Jarak kantor dan lokasi anda lebih dari 50 M, yaitu ".$distance_fmt." M.",
                    "lon" => "Jarak kantor dan lokasi anda lebih dari 50 M, yaitu ".$distance_fmt." M."
                ]);
            }
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
    public function update(Request $request, $id)
    {
        $valid_arr = [
            "name" => "required|unique:App\Models\Attendance,name,{$id},id"
        ];
        $valid = Validator::make($request->all(), $valid_arr);
        if ($valid->fails())
            throw new \ValidationException($valid);

        $attendance = Attendance::find($id);
        if($attendance == null)
            throw new \ModelNotFoundException("Attendance not found.");
        else{
            $attendance->update([
                "name" => $request->name
            ]);
            $res = [
                "status" => "success",
                "message" => "Update Attendance success",
                "response" => $attendance
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
