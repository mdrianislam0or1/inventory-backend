<?php

namespace App\Http\Controllers;

use App\Helper\JWTToken;
use App\Mail\OTPMail;
use App\Models\User;
use Exception;
use Firebase\JWT\JWT;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class UserController extends Controller
{
    public function UserRegistration(Request $request)
    {
       try{
        $registerData = User::create([
            'firstName' => $request->input('firstName'),
            'lastName' => $request->input('lastName'),
            'email' => $request->input('email'),
            'mobile' => $request->input('mobile'),
            'password' => $request->input('password'),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'User Registration Successfully',
            'data' => $registerData
        ]);
       }catch(Exception $e){
            return response()->json([
                'status' => 'failed',
                'message' => 'User Registration',
                'error'=> $e
            ]);
       }
    }

    function UserLogin(Request $request){
        $count = User::where('email', '=' , $request->input('email'))->where('password', '=', $request->input('password'))->count();
        if($count==1){
            $token = JWTToken::CreateToken($request->input('email'));
            return response()->json([
                'status' => 'success',
                'message' => 'User Login Successful',
                'token' => $token
            ]);
        }else{
            return response()->json([
                'status' => 'failed',
                'message'=> 'User Login Failed'
            ]);
        }
    }

    function SendOTPCode(Request $request){
        $email = $request->input('email');
        $otp = rand(100000,999999);
        $count= User::where('email','=',$email)->count();

        if($count==1){
            //OTP EMAIL ADDRESS
            Mail::to($email)->send(new OTPMail($otp));
            //OTP CODE TABLE UPDATE
            User::where('email','=',$email)->update(['otp' => $otp]);
        
            return response()->json([
                'status' => 'success',
                'message' => '6 Digit OTP Code has been send to your email'
            ],200);
        }else{
            return response()->json([
                'status' => 'failed',
                'message' => 'unauthorized'
            ],401);
        }
    }

    function VerifyOTP(Request $request){
        $email = $request->input('email');
        $otp = $request->input('otp');
        $count = User::where('email','=',$email)->where('otp','=',$otp)->count();

        if($count==1){
            //Database OTP Update
            User::where('email','=',$email)->update(['otp' => '0']);

            //Pass Reset Token Issue
            $token = JWTToken::CreateTokenForSetPassword($request->input('email'));
            return response()->json([
                'status' => 'success',
                'message' => 'OTP Verification Successful',
                'token' => $token
            ],200);
        }else{
            return response()->json([
                'status' => 'failed',
                'message' => 'unauthorized'
            ],401);
        }
    }

    function ResetPassword(Request $request){
        try{
            $email = $request->header('email');
            $password = $request->input('password');
            User::where('email','=',$email)->update(['password' => $password]);
            return response()->json([
                'status' => 'success',
                'message'=> 'Request Successful',
            ],200);
        }
        catch(Exception $e){
            return response()->json([
                'status' => 'Failed',
                'message' => "Failed".$e,

            ]);
        }
    }
}
