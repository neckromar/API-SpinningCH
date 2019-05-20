<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\User;
use App\Video;
use App\Log;
use App\Imagen;
use App\Post;
use App\Comentario;
use Illuminate\Support\Facades\DB;
use App\Helpers\JwtAuth;

class LogController extends Controller
{
    public function store(Request $request) {
        $hash = $request->header('Authorization', null);

        $jwtAuth = new JwtAuth();
        $checktoken = $jwtAuth->checkToken($hash);

        if ($checktoken) {
        //recoger datos por post
            $json = $request->input('json', null);
            $params = json_decode($json);
          
            //conseguir usuario
            $user = $jwtAuth->checkToken($hash, true);

            //validacion de los parametros
            //guardar el coche
            $log = new Log();
            $log->prioridad = $params->prioridad;
            $log->nombre =$params->nombre;
            $log->save();


            $data = array(
                'log' => $log,
                'status' => 'success',
                'code' => 200
            );
           
            
        }else {
            $data = array(
                'message' => 'Login incorecto',
                'status' => 'error',
                'code' => 400
            );
        }
        return response()->json($data, 200);
    }

}
