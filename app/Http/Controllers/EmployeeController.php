<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader;
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
        $user = User::find($employee->user_id);
        if($employee == null || $user == null)
            throw new \ModelNotFoundException("Employee not found.");
        else {
            $employee->delete();
            $user->delete();
            $res = [
                "status" => "success",
                "message" => "Delete Employee success",
                "response" => $employee 
            ];
        }

        return response($res);
    }
    public function import(Request $request)
    {
        try {
            \DB::beginTransaction();
            set_time_limit(0);
            $file = $request->file;
            $file_type = IOFactory::identify($file);

            if($file_type == 'Csv')
                $reader = new Reader\Csv();
            else if($file_type == 'Xlsx')
                $reader = new Reader\Xlsx();
            else if($file_type == 'Xls')
                $reader = new Reader\Xls();
            else
                throw \ValidationException::withMessages(['file' => "File invalid."]);
            
            $spreadsheet = $reader->load($file);
            $results = $spreadsheet->getActiveSheet()->toArray();
            $headers = $results[0];
            $rows = array_splice($results, 1);

            //echo "<pre>";
            //var_dump($rows);
            //echo "</pre>";
            $employee_arr = [];
            foreach ($rows as $row) {
                //$row[1] = nik
                $request = [
                    "department_id" => $row[3],
                    "nik" => $row[1],
                    "password" => $row[4],
                    "name" => $row[2],
                ];
                $valid_arr = [
                    "department_id" => "required|exists:App\Models\Department,id",
                    "nik" => "required|unique:App\Models\Employee,nik",
                    "password" => "required",
                    "name" => "required"
                ];
                $valid = Validator::make($request, $valid_arr);
                if ($valid->fails())
                    throw new \ValidationException($valid);
                $user_check = User::where("username", $request['nik'])->first();
                if($user_check != null)
                    throw \ValidationException::withMessages(['username' => "The username has already been taken."]);
                else{
                    $user = User::create([
                        "username" => $request['nik'],
                        "name" => $request['name'],
                        "password" => \Hash::make($request['password']),
                        "role" => "User",
                        "api_token" => md5($request['nik'])
                    ]);
                    $employee = Employee::create([
                        "user_id" => $user->id,
                        "department_id" => $request['department_id'],
                        "nik" => $request['nik'],
                        "name" => $request['name']
                    ]);
                    array_push($employee_arr, $employee);
                }
            }
            $res = [
                "status" => "success",
                "message" => "Import Employee success",
                "response" => $employee_arr 
            ];

            \DB::commit();
            return response($res);
        } catch (\Throwable $e) {
            \DB::rollback();
            throw $e;
        }
    }
}
