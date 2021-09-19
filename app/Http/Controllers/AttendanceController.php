<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Models\Attendance;
use App\Models\Config;
use App\Models\WorkHour;
use App\Models\User;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $query = Attendance::query();
        $page = $request->query('page');
        $limit = $request->query('limit');
        if(Auth()->user()->role == "User"){
            $query->where('employee_id', Auth()->user()->employee->id);
        }
        if($page != null && $limit != null){
            $page--;
            $attendance_pg = ceil($query->count() / $limit);
            $query->offset(($page * $limit));
            $query->limit($limit);
        }
        $attendance = $query->orderBy("employee_id")->orderBy("checkin", "desc")->get();
        foreach($attendance as $val){
            $val->employee = $val->employee;
        }
        $res = [
            "status" => "success",
            "message" => "Get Attendance list success",
            "response" => $attendance,
            "response_total_page" => $attendance_pg
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
        }
        else if($attendance_check == null && $time_check["remark"] == "work_hour"){
            $message = "You're late";
            $action = "Check In";
        }
        else if($attendance_check->checkout != null){
            $message = "You already checked out";
            $action = "";
        }
        else if($time_check["date_period"] != $attendance_check->date && $attendance_check->checkout == null && $time_check["remark"] == "before_work_hour"){// && $attendance_check->checkin < date("Y-m-d H:i:s", strtotime("-{$diff_2} hours"))){
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
        $work_hour = WorkHour::whereIn("day", $where_day)->where("shift", Auth()->user()->employee->shift)->orderByRaw("field(day, ".implode(",", $where_day).")")->get();
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
        $i = -1;
        foreach($work_hour as $val){
            $diff_day = $i++;
            // $diff_day   = $val->day - date("N");
            $date_start = date("Y-m-d {$val->start}", strtotime("{$diff_day} days"));
            $date_end   = date("Y-m-d {$val->end}", strtotime("{$diff_day} days"));
            if($date_start > $date_end)
                $date_end = date("Y-m-d H:i:s", strtotime("{$date_end} 1 day"));
            $date_period = date("Y-m-d", strtotime("{$date_start}"));

            $diff = (strtotime($date_end) - strtotime($date_start))/3600;
            if(Auth()->user()->employee->shift != 1){//note: cek jika bukan shift 1, maka perlu dicek apakah start di shift tersebut, kurang dari start shift 1, maka date period masuknya di hari kemarinnya.
                $first_val = $first_work_hour->where("day", $val->day)->first();
                $date_period = date("Y-m-d", strtotime("{$date_start} -1 day"));//tunggu pas mau checkin/before_work_hour
                if(strtotime($val->start) < strtotime($first_val->start)){
                    //note: diatas berlaku jika waktu sekarang adalah before untuk shift 3(shift yang kurang start nya kurang dari shift 1), jika before, maka start/end ditambah 1 hari(besoknya), date periodnya hari ini
                    $date_start = date("Y-m-d H:i:s", strtotime("{$date_start} 1 day"));
                    $date_end   = date("Y-m-d H:i:s", strtotime("{$date_end} 1 day"));
                    $date_period = date("Y-m-d", strtotime("{$date_start} -1 day"));//tunggu pas mau checkin/before_work_hour
                    $bef = date("Y-m-d H:i:s", strtotime("{$date_start} -{$diff} hours"));
                    if($bef > date("Y-m-d H:i:s") || $date_end < date("Y-m-d H:i:s")){
                        //note: dalam if ini, dicek apakah waktu sekarang bukan before, maka barulah date periodnya dibuat hari kemarin
                        $date_start = date("Y-m-d H:i:s", strtotime("{$date_start} -1 day"));
                        $date_end   = date("Y-m-d H:i:s", strtotime("{$date_end} -1 day"));
                        $date_period = date("Y-m-d", strtotime("{$date_start} -1 day"));//tunggu pas mau checkin/before_work_hour
                    }
                }
            }
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
            ]);
        }
        $config = Config::first();
        $distance = $this->vincentyGreatCircleDistance($config->office_lat, $config->office_lon, $request->lat, $request->lon);// / 1000;
        $distance_fmt = number_format($distance, 0, ",", ".");
        Log::channel("daily")->info("DISTANCE RESULT ".str_pad($distance_fmt, 6, " ", STR_PAD_LEFT).", FROM $config->office_lat, $config->office_lon TO $request->lat, $request->lon");
        if($distance > $config->max_distance){
            throw \ValidationException::withMessages([
                "lat" => "Jarak kantor dan lokasi anda lebih dari 50 M, yaitu {$distance_fmt} M.",
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
            /*$attendance->update([
                "checkout" => date("Y-m-d H:i:s"),
                "lat" => $request->lat,
                "lon" => $request->lon
            ]);*/
            if($user_info["response"]["attendance"] != null){
                $user_info["response"]["attendance"]->update([
                    "checkout" => date("Y-m-d H:i:s"),
                    "lat" => $request->lat,
                    "lon" => $request->lon
                ]);
                $res = [
                    "status" => "success",
                    "message" => "Attendance check out success",
                    "response" => $user_info["response"]["attendance"]
                ];
            }
            else{
            $attendance = Attendance::create([
                "employee_id" => Auth()->user()->employee->id,
                "shift" => Auth()->user()->employee->shift,
                "date" => $user_info["response"]["time_check"]["date_period"],
                "checkin" => null,
                "checkout" => date("Y-m-d H:i:s"),
                "lat" => $request->lat,
                "lon" => $request->lon
            ]);
            $res = [
                "status" => "success",
                "message" => "Attendance check out success",
                "response" => $attendance
            ];
            }
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
    public function export(Request $request)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue("A1", "NIK");
        $sheet->setCellValue("B1", "Nama");
        $sheet->setCellValue("C1", "Checkin");
        $sheet->setCellValue("D1", "Checkout");
        $sheet->setCellValue("E1", "WH Start");
        $sheet->setCellValue("F1", "WH End");
        $sheet->setCellValue("G1", "Jam Kerja");
        $sheet->setCellValue("H1", "Kur Sebelum");
        $sheet->setCellValue("I1", "Leb Sebelum");
        $sheet->setCellValue("J1", "Kur Setelah");
        $sheet->setCellValue("K1", "Leb Setelah");
        $first_work_hour = WorkHour::where("shift", 1)->get();
        $user = User::where("api_token", $request->query("token"))->first();
        if($user != null){
            $query = Attendance::query();
            if($user->role == "User"){
                $query->where("employee_id", $user->employee->id);
            }
            $cell = 1;
            $query->leftJoin("work_hour", function($join){
                $join->on("work_hour.shift", "=", "attendance.shift")->on("work_hour.day", "=", \DB::Raw("
                    (
                        CASE WHEN (DAYOFWEEK(attendance.date)-1 = 0)
                            THEN 7
                            ELSE DAYOFWEEK(attendance.date)-1 
                        END
                    )
                "));
            });//->select("start", "end") ;
            $attendance = $query->orderBy("employee_id")->orderBy("date", "desc")->get();
            foreach($attendance as $val){
                $cell++;
                $wh_start = $val->date." ".$val->start;
                $wh_end = $val->date." ".$val->end;
                if($wh_start > $wh_end){
                    echo "abc\n";
                    $wh_end = date("Y-m-d H:i:s", strtotime("{$wh_end} 1 day"));
                }
                $first_val = $first_work_hour->where("day", $val->day)->first();
                if(strtotime($val->start) < strtotime($first_val->start)){
                    $wh_start = date("Y-m-d H:i:s", strtotime("{$wh_start} 1 day"));
                    $wh_end = date("Y-m-d H:i:s", strtotime("{$wh_end} 1 day"));
                }
                $wh_diff = (strtotime($wh_end) - strtotime($wh_start))/3600;
                if($val->checkin == null || $val->checkout == null){
                    $jam     = 0;
                    $kur_seb = 0;
                    $leb_seb = 0;
                    $kur_set = $wh_diff;
                    $leb_set = 0;
                }
                if($val->checkin < $wh_start){
                    $kur_seb = 0;
                    $leb_seb = (strtotime($wh_start) - strtotime($val->checkin))/3600;
                }
                else{
                    $kur_seb = (strtotime($val->checkin) - strtotime($wh_start))/3600;
                    $leb_seb = 0;
                }

                if($val->checkout > $wh_end){
                    $kur_set = 0;
                    $leb_set = (strtotime($val->checkout) - strtotime($wh_end))/3600;
                }
                else{
                    $kur_set = (strtotime($wh_end) - strtotime($val->checkout))/3600;
                    $leb_set = 0;
                }

                if($val->checkin < $wh_start && $val->checkout > $wh_end){
                    $jam = $wh_diff;
                }
                else if($val->checkin > $wh_start && $val->checkout > $wh_end){
                    $jam = (strtotime($wh_end) - strtotime($val->checkin))/3600;
                }
                else if($val->checkin < $wh_start && $val->checkout < $wh_end){
                    $jam = (strtotime($val->checkout) - strtotime($wh_start))/3600;
                }
                else{
                    $jam = (strtotime($val->checkout) - strtotime($val->checkin))/3600;
                }
                // if($val->checkin > $wh_start && $val->checkout > $wh_end){
                //     $jam     = (strtotime($val->checkout) - strtotime($val->checkin))/3600;
                //     $kur_seb = 0;
                //     $leb_seb = (strtotime($wh_start) - strtotime($val->checkin))/3600;
                //     $kur_set = 0;
                //     $leb_set = (strtotime($val->checkout) - strtotime($wh_end))/3600;
                // }
                $jam     = number_format($jam, 2, ",", ".");
                $kur_seb = number_format($kur_seb, 2, ",", ".");
                $leb_seb = number_format($leb_seb, 2, ",", ".");
                $kur_set = number_format($kur_set, 2, ",", ".");
                $leb_set = number_format($leb_set, 2, ",", ".");
                //echo $val->checkin." ".$val->checkout." ".$jam." ".$kur_seb." ".$leb_seb." ".$kur_set." ".$leb_set."\n";
                $sheet->setCellValue("A".$cell, $val->employee->nik);
                $sheet->setCellValue("B".$cell, $val->employee->name);
                $sheet->setCellValue("C".$cell, $val->checkin);
                $sheet->setCellValue("D".$cell, $val->checkout);
                $sheet->setCellValue("E".$cell, $wh_start);
                $sheet->setCellValue("F".$cell, $wh_end);
                $sheet->setCellValue("G".$cell, $jam);
                $sheet->setCellValue("H".$cell, $kur_seb);
                $sheet->setCellValue("I".$cell, $leb_seb);
                $sheet->setCellValue("J".$cell, $kur_set);
                $sheet->setCellValue("K".$cell, $leb_set);
            }
        }
        foreach (range("A","K") as $col) {
            if($col == "E" || $col == "F")
                $sheet->getColumnDimension($col)->setWidth(0);
            else
                $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        $sheet->getStyle("A1:K".$cell)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        $writer = new Xlsx($spreadsheet);
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="attendance-export-'.date('Y-m-d').'.xlsx"');
        $writer->save('php://output');
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
