<?php
namespace App\Services;

use App\Repository\UserRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class AuthService{
    private $userRepo;

    public function __construct(){
        $this->userRepo = new UserRepository();
    }

    public function doGenerateOtp($username){
        $user = $this->userRepo->getUser($username);
        $otp = null;

        $response = null;
        if(!empty($user)){
            $oldOtp = Redis::get("otp_".$user->user_id);
            if($oldOtp){
                $otp = $oldOtp;

                $response = [
                    'error_code'=>101,
                    'message'=>"Failed, Otp has been created. Please wait until your old otp expired before create a new otp"
                ];
            } else {
                $otp = $this->generate_random(4);
                Redis::set("otp_".$user->user_id, $otp, 'EX', env('OTP_LIFETIME'));
                $response = [
                    'error_code'=>0,
                    'message'=>"Success",
                    'otp'=>$otp
                ];
            }
        } else {
            $response = [
                'error_code'=>101,
                'message'=>"Failed, user doesn't exist"
            ];
        }

        return response()->json($response);
    }

    public function generate_random($digits = 4) {
        return str_pad(rand(0, pow(10, $digits)-1), $digits, '0', STR_PAD_LEFT);
    }

    public function doLoginWithOtp($username, $otp){
        $user = $this->userRepo->getUser($username);
        $response = null;

        if(!empty($user)){
            $saveOtp = Redis::get("otp_".$user->user_id);
            if(!empty($saveOtp)){
                if($saveOtp == $otp){
                    $request =[
                        'grant_type' => 'otp',
                        'client_id' => env('CLIENT_ID'),
                        'client_secret' => env('CLIENT_SECRET'),
                        'otp' => $otp,
                        'email'=>$user->email,
                        'scope' => '*'
                    ];

                    $req = Request::create('/oauth/token', 'POST', $request);
                    $res = app()->handle($req);
                    $responseBody = json_decode($res->getContent());

                    if(isset($responseBody->access_token)){
                        $response = [
                            'error_code'=>0,
                            'message'=>"Success login",
                            'data'=>$responseBody
                        ];
                        Redis::del("otp_".$user->user_id);
                    } else {
                        $response = [
                            'error_code'=>101,
                            'message'=>"Failed",
                            'errors'=>$responseBody
                        ];
                    }
                } else {
                    $response = [
                        'error_code'=>101,
                        'message'=>"Otp didn't match"
                    ];
                }
            } else {
                $response = [
                    'error_code'=>101,
                    'message'=>"Your otp has expired"
                ];
            }
        } else {
            $response = [
                'error_code'=>101,
                'message'=>"Failed, user doesn't exist"
            ];
        }

        return response()->json($response);
    }
}