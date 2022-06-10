<?php
namespace App\Http\Controllers;

use App\Services\AuthService;
use Illuminate\Http\Request;

class AuthController extends Controller{
    private $authService;

    public function __construct(){
        $this->authService = new AuthService();
    }

    public function requestToken(Request $request){
        return $this->authService->doGenerateOtp($request->username);
    }

    public function login(Request $request){
        return $this->authService->doLoginWithOtp($request->username, $request->otp);
    }
}