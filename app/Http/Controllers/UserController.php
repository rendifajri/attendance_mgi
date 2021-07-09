<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;

class UserController extends Controller
{
    public function index()
    {
        $user = User::orderBy("username")->get();
        $res = [
            "status" => "success",
            "message" => "Get User list success",
            "response" => $user
        ];

        return response($res);
    }
    public function detail($id)
    {
        $user = User::find($id);
        if($user == null)
            throw new \ModelNotFoundException("User not found.");
        else {
            $res = [
                "status" => "success",
                "message" => "Get User success",
                "response" => $user
            ];
        }

        return response($res);
    }
    public function create(Request $request)
    {
       // try{
            $valid_arr = [
                "username" => "required|alpha_dash|unique:App\Models\User,username",
                "name" => "required",
                "password" => "required",
                "role" => "required|in:Admin,User"
            ];
            $valid = Validator::make($request->all(), $valid_arr);
            if ($valid->fails())
                throw new \ValidationException($valid);

            $user = User::create([
                "username" => $request->username,
                "name" => $request->name,
                "password" => \Hash::make($request->password),
                "role" => $request->role,
                "api_token" => md5($request->username)
            ]);
            $res = [
                "status" => "success",
                "message" => "Create User success",
                "response" => $user
            ];
            return response($res);
        // } catch (\Throwable $e) {
        //     //DB::rollback();
        //     throw $e;
        // }
    }
    public function update(Request $request, $id)
    {
        $valid_arr = [
            "username" => "required|alpha_dash|unique:App\Models\User,username,{$id},id",
            "name" => "required",
            "password" => "required",
            "role" => "required|in:Admin,User"
        ];
        $valid = Validator::make($request->all(), $valid_arr);
        if ($valid->fails())
            throw new \ValidationException($valid);

        $user = User::find($id);
        if($user == null)
            throw new \ModelNotFoundException("User not found.");
        else{
            $user->update([
                "username" => $request->username,
                "name" => $request->name,
                "password" => \Hash::make($request->password),
                "role" => $request->role
            ]);
            $res = [
                "status" => "success",
                "message" => "Update User success",
                "response" => $user
            ];
        }
        return response($res);
    }
    public function delete($id)
    {
        $user = User::find($id);
        if($user == null)
            throw new \ModelNotFoundException("User not found.");
        else {
            $user->delete();
            $res = [
                "status" => "success",
                "message" => "Delete User success",
                "response" => $user
            ];
        }

        return response($res);
    }
    public function login(Request $request)
    {
        $valid_arr = [
            "username" => "required",
            "password" => "required"
        ];
        $valid = Validator::make($request->all(), $valid_arr);
        if ($valid->fails())
            throw new \ValidationException($valid);
        //throw \ValidationException::withMessages(["degree" => "Please fill degree!"]);

        $where = [
            "username" => $request->username,
        ];
        $user = User::where($where)->first();
        if($user == null)
            throw new \ModelNotFoundException("Username not found.");
        else if(!\Hash::check($request->password, $user->password))
            throw new \AccessDeniedHttpException("Password not match.");
        else{
            $user->update(["api_token" => md5($user->username.$user->updated_at)]);
            $res = [
                "status" => "success",
                "message" => "Login success",
                "response" => $user
            ];
        }
        return response($res);
    }
    public function profile()
    {
        $res = [
            "status" => "success",
            "message" => "Get Profile success",
            "response" => Auth()->user()
        ];

        return response($res);
    }
}
