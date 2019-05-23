<?php

namespace App\Helpers;

use Firebase\JWT\JWT;
use Illuminate\Support\Facades\DB;
use App\User;

class JwtAuth {

    public $key;

    public function __construct() {
        $this->key = 'esta-es-mi-clave-secreta-*123456789';
    }

    public function signup($email, $password, $getToken = null) {

        $user = User::where(
                        array(
                            'email' => $email,
                            'password' => $password
                ))->first();

        $signup = false;
        if (is_object($user)) {
            $signup = true;
        }

        if ($signup) {
            //generar token
            $token = array(
                'sub' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
                'image_path' => $user->image_path,
                'surname' => $user->surname,
                'role_id' => $user->role_id,
                'iat' => time(),
                'exp' => time() + (7 * 24 * 60 * 60)
            );

            $jwt = JWT::encode($token, $this->key, 'HS256');

            $decode = JWT::decode($jwt, $this->key, array('HS256'));

            if ($getToken == null) {
                return $jwt;
            } else {
                return $decode;
            }
        } else {
            return array("status" => "error", "message" => "Login ha fallado!");
        }
    }

    public function checkToken($jwt, $getIdentity = false) {
        $auth = false;

        try {

            $decoded = JWT::decode($jwt, $this->key, array('HS256'));
            
        } catch (\UnexpectedValueException $e) {
            $auth = false;
        } catch (\DomainException $e) {
            $auth = false;
        }

        if (isset($decoded) && is_object($decoded) && isset($decoded->sub)) {
            $auth = true;
        } else {
            $auth = false;
        }

        if ($getIdentity) {
            return $decoded;
        }


        return $auth;
    }

    public function signupAdmin($email, $password, $getToken = null) {

        $user = User::where(
                        array(
                            'email' => $email,
                            'password' => $password,
                            'role_id' => 1
                ))->first();

        $signup = false;
        if (is_object($user)) {
            $signup = true;
        }

        if ($signup) {
            //generar token
            $token = array(
                'sub' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
                'image_path' => $user->image_path,
                'surname' => $user->surname,
                'role_id' => $user->role_id,
                'iat' => time(),
                'exp' => time() + (7 * 24 * 60 * 60)
            );

            $jwt = JWT::encode($token, $this->key, 'HS256');

            $decode = JWT::decode($jwt, $this->key, array('HS256'));

            if ($getToken == null) {
                return $jwt;
            } else {
                return $decode;
            }
        } else {
            return array("status" => "error", "message" => "Login ha fallado!");
        }
    }

}
