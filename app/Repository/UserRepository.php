<?php
namespace App\Repository;

use App\Models\User;

class UserRepository{
    private $model;

    public function __construct(){
        $this->model = new User();    
    }

    public function getUser($username){
        $user = User::where('email', $username)->first();
        return $user;
    }
}