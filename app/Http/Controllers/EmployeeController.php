<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Employee;
use App\Models\User;

class EmployeeController extends Controller
{
    public function index()
    {
        $employee = Employee::orderBy("nik")->get();
        $res = [
            "status" => "success",
            "message" => "Get Employee list success",
            "response" => $employee 
        ];

        return response($res);
    }
    public function detail($id)
    {
        $employee = Employee::find($id);
        if($employee == null)
            throw new \ModelNotFoundException("Employee not found.");
        else {
            $res = [
                "status" => "success",
                "message" => "Get Employee success",
                "response" => $employee 
            ];
        }

        return response($res);
    }
    public function create(Request $request)
    {
       // try{
        //insert user dulu
        $valid_arr = [
            "department_id" => "required|exists:App\Models\Department,id",
            "nik" => "required|unique:App\Models\Employee,nik",
            "password" => "required",
            "name" => "required"
        ];
        $valid = Validator::make($request->all(), $valid_arr);
        if ($valid->fails())
            throw new \ValidationException($valid);
        $user_check = User::where("username", $request->nik)->first();
        if($user_check != null)
            throw \ValidationException::withMessages(['username' => "The username has already been taken."]);
        else{
            $user = User::create([
                "username" => $request->nik,
                "name" => $request->name,
                "password" => \Hash::make($request->password),
                "role" => "User",
                "api_token" => md5($request->nik)
            ]);
            $employee = Employee::create([
                "user_id" => $user->id,
                "department_id" => $request->department_id,
                "nik" => $request->nik,
                "name" => $request->name
            ]);
            $res = [
                "status" => "success",
                "message" => "Create Employee success",
                "response" => $employee 
            ];
            return response($res);
        }
        // } catch (\Throwable $e) {
        //     //DB::rollback();
        //     throw $e;
        // }
    }
    public function update(Request $request, $id)
    {
        $valid_arr = [
            "department_id" => "required|exists:App\Models\Department,id",
            "nik" => "required|unique:App\Models\Employee,nik,{$id},id",
            "name" => "required"
        ];
        $valid = Validator::make($request->all(), $valid_arr);
        if ($valid->fails())
            throw new \ValidationException($valid);

        $employee = Employee::find($id);
        $user = User::find($employee->user_id);
        $user_check = User::where("username", $request->nik)->first();
        if($employee == null || $user == null)
            throw new \ModelNotFoundException("Employee not found.");
        else if($user_check != null && $user_check->id != $user->id)
            throw \ValidationException::withMessages(['username' => "The username has already been taken."]);
        else{
            $data_user = [
                "username" => $request->nik,
                "name" => $request->name
            ];
            if($request->password != '')
                $data_user["password"] = \Hash::make($request->password);
            $user->update($data_user);

            $employee ->update([
                "department_id" => $request->department_id,
                "nik" => $request->nik,
                "name" => $request->name
            ]);
            $res = [
                "status" => "success",
                "message" => "Update Employee success",
                "response" => $employee 
            ];
        }
        return response($res);
    }
    public function delete($id)
    {
        $employee = Employee::find($id);
        if($employee == null)
            throw new \ModelNotFoundException("Employee not found.");
        else {
            $employee ->delete();
            $res = [
                "status" => "success",
                "message" => "Delete Employee success",
                "response" => $employee 
            ];
        }

        return response($res);
    }
}
