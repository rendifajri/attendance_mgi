<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class Role
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ... $roles)
    {
        //var_dump($roles);
        $user = User::where('api_token', $request->bearerToken())->first();
        //var_dump($user);
        //var_dump($request->bearerToken());
        if ($user != null){
            Auth::login($user);
            //echo Auth::check()
            if (in_array(Auth()->user()->role, $roles) || count($roles) == 0)
                return $next($request);
            else
                return response(['status' => 'unauthenticated', 'message' => 'No authorization.'], 401);
        }
        return response(['status' => 'unauthenticated', 'message' => 'Please login first.'], 401);
    }
}
