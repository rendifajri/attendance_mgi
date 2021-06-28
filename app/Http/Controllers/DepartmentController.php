<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Department;

class DepartmentController extends Controller
{
    public function index()
    {
        $department = Department::orderBy("name")->get();
        $res = [
            "status" => "success",
            "message" => "Get Department list success",
            "response" => $department
        ];

        return response($res);
    }
    public function detail($id)
    {
        $department = Department::find($id);
        if($department == null)
            throw new \ModelNotFoundException("Department not found.");
        else {
            $res = [
                "status" => "success",
                "message" => "Get Department success",
                "response" => $department
            ];
        }

        return response($res);
    }
    public function create(Request $request)
    {
        $valid_arr = [
            "name" => "required|unique:App\Models\Department,name"
        ];
        $valid = Validator::make($request->all(), $valid_arr);
        if ($valid->fails())
            throw new \ValidationException($valid);

        $department = Department::create([
            "name" => $request->name
        ]);
        $res = [
            "status" => "success",
            "message" => "Create Department success",
            "response" => $department
        ];
        return response($res);
    }
    public function update(Request $request, $id)
    {
        $valid_arr = [
            "name" => "required|unique:App\Models\Department,name,{$id},id"
        ];
        $valid = Validator::make($request->all(), $valid_arr);
        if ($valid->fails())
            throw new \ValidationException($valid);

        $department = Department::find($id);
        if($department == null)
            throw new \ModelNotFoundException("Department not found.");
        else{
            $department->update([
                "name" => $request->name
            ]);
            $res = [
                "status" => "success",
                "message" => "Update Department success",
                "response" => $department
            ];
        }
        return response($res);
    }
    public function delete($id)
    {
        $department = Department::find($id);
        if($department == null)
            throw new \ModelNotFoundException("Department not found.");
        else {
            $department->delete();
            $res = [
                "status" => "success",
                "message" => "Delete Department success",
                "response" => $department
            ];
        }

        return response($res);
    }
}
