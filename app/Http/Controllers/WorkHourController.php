<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\WorkHour;

class WorkHourController extends Controller
{
    public function index()
    {
        $work_hour = WorkHour::orderBy("shift")->orderBy("day")->get();
        $res = [
            "status" => "success",
            "message" => "Get Work Hour list success",
            "response" => $work_hour
        ];

        return response($res);
    }
    public function detail($id)
    {
        $work_hour = WorkHour::find($id);
        if($work_hour == null)
            throw new \ModelNotFoundException("Work Hour not found.");
        else {
            $res = [
                "status" => "success",
                "message" => "Get Work Hour success",
                "response" => $work_hour
            ];
        }

        return response($res);
    }
    public function create(Request $request)
    {
        $valid_arr = [
            "shift" => "required|integer|min:1|max:3",
            "day" => "required|integer|min:1|max:7",
            "start" => "required|date_format:H:i:s",
            "end" => "required|date_format:H:i:s",
        ];
        $valid = Validator::make($request->all(), $valid_arr);
        if ($valid->fails())
            throw new \ValidationException($valid);
        $where = [
            "shift" => $request->shift,
            "day" => $request->day,
        ];
        $work_hour_check = WorkHour::where($where)->first();
        if($work_hour_check != null){
            throw \ValidationException::withMessages([
                "shift" => "The shift has already been taken.",
                "day" => "The day has already been taken."
            ]);
        }

        $work_hour = WorkHour::create([
            "shift" => $request->shift,
            "day" => $request->day,
            "start" => $request->start,
            "end" => $request->end,
        ]);
        $res = [
            "status" => "success",
            "message" => "Create Work Hour success",
            "response" => $work_hour
        ];
        return response($res);
    }
    public function update(Request $request, $id)
    {
        $valid_arr = [
            "shift" => "required|integer|min:1|max:3",
            "day" => "required|integer|min:1|max:7",
            "start" => "required|date_format:H:i:s",
            "end" => "required|date_format:H:i:s",
        ];
        $valid = Validator::make($request->all(), $valid_arr);
        if ($valid->fails())
            throw new \ValidationException($valid);
        $where = [
            "shift" => $request->shift,
            "day" => $request->day,
        ];
        $work_hour_check = WorkHour::where($where)->first();
        if($work_hour_check != null && $work_hour_check->id != $id){
            throw \ValidationException::withMessages([
                "shift" => "The shift has already been taken.",
                "day" => "The day has already been taken."
            ]);
        }

        $work_hour = WorkHour::find($id);
        if($work_hour == null)
            throw new \ModelNotFoundException("Work Hour not found.");
        else{
            $work_hour->update([
                "shift" => $request->shift,
                "day" => $request->day,
                "start" => $request->start,
                "end" => $request->end,
            ]);
            $res = [
                "status" => "success",
                "message" => "Update Work Hour success",
                "response" => $work_hour
            ];
        }
        return response($res);
    }
    public function delete($id)
    {
        $work_hour = WorkHour::find($id);
        if($work_hour == null)
            throw new \ModelNotFoundException("Work Hour not found.");
        else {
            $work_hour->delete();
            $res = [
                "status" => "success",
                "message" => "Delete Work Hour success",
                "response" => $work_hour
            ];
        }

        return response($res);
    }
}
