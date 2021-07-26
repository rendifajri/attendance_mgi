<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Config;

class ConfigController extends Controller
{
    public function index()
    {
        $config = Config::orderBy("id")->first();
        $res = [
            "status" => "success",
            "message" => "Get Config list success",
            "response" => $config
        ];

        return response($res);
    }
    public function update(Request $request)
    {
        $valid_arr = [
            "max_distance" => "required|integer",
            "office_lat" => "required|between:-90,90",
            "office_lon" => "required|between:-180,180"
        ];
        $valid = Validator::make($request->all(), $valid_arr);
        if ($valid->fails())
            throw new \ValidationException($valid);

        $config = Config::orderBy("id")->first();
        if($config == null){
            $config = Config::create([
                "office_lat" => $request->office_lat,
                "office_lon" => $request->office_lon
            ]);
        }
        else{
            $config->update([
                "office_lat" => $request->office_lat,
                "office_lon" => $request->office_lon
            ]);
            $config_delete = Config::where('id', '!=', $config->id)->orWhereNull('id')->delete();
            $res = [
                "status" => "success",
                "message" => "Update Config success",
                "response" => $config
            ];
        }
        return response($res);
    }
}
