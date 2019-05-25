<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\User;
use App\Video;
use App\UserDeleted;
use App\Imagen;
use App\Post;
use App\Log;
use App\Comentario;
use Illuminate\Support\Facades\DB;
use App\Helpers\JwtAuth;

class AdminController extends Controller {

   
    public function login(Request $request) {
        $jwtAuth = new JwtAuth();

        //recibir datos por post

        $json = $request->input('json', null);
        $params = json_decode($json);

        $email = (!is_null($json) && isset($params->email)) ? $params->email : null;
        $password = (!is_null($json) && isset($params->password)) ? $params->password : null;
        $getToken = (!is_null($json) && isset($params->gettoken) ) ? $params->gettoken : null;

        //cifrar la password+
        $pwd = hash('sha256', $password);

        if (!is_null($email) && !is_null($password) && ($getToken == null || $getToken=='false')  && $params->role_id == 1) {
            
            $signup = $jwtAuth->signupAdmin($email, $pwd);

        } elseif ($getToken != null) {
            $signup = $jwtAuth->signupAdmin($email, $pwd, $getToken);
           
        } else {
          $signup= array(
            'status' => 'error',
            'code' => 400,
            'message' => 'Envia tus datos por post'
            );
        }
        
         return response()->json($signup, 200);
    }

    public function getUsersDeleted(){
        $users = UserDeleted::all();
        if(is_object( $users)){
            $users = UserDeleted::all();

            return response()->json(array(
                'users' => $users,
                'status' => 'success'
                    ), 200);
        }
        else{
            return response()->json(array(
                'message' => 'El usuario no existe',
                'status' => 'error'
                    ), 400);
        }
    }


    public function getLogs(){
        $logs = Log::all();
        if(is_object( $logs)){
            $logs = Log::all();

            return response()->json(array(
                'logs' => $logs,
                'status' => 'success'
                    ), 200);
        }
        else{
            return response()->json(array(
                'message' => 'El usuario no existe',
                'status' => 'error'
                    ), 400);
        }
       
    }

    public function getUsersInactived(){
        $user_in = User::where('role_id',3)->get();
        if(is_object( $user_in)){
            $user_in = User::where('role_id',3)->get();

            return response()->json(array(
                'users' => $user_in,
                'status' => 'success'
                    ), 200);
        }
        else{
            return response()->json(array(
                'message' => 'El usuario no existe',
                'status' => 'error'
                    ), 400);
        }
       
    }

    public function getUsersActived(){
        $user_1 = User::where('role_id',1)->get();
        if(is_object( $user_1)){
            $user_2 = User::where('role_id',2)->get();
            $user_4 = User::where('role_id',4)->get();
            $array_user=[];
            array_push($array_user,$user_1,$user_2,$user_4);
            return response()->json(array(
                'users' => $array_user,
                'status' => 'success'
                    ), 200);
        }
        else{
            return response()->json(array(
                'message' => 'El usuario no existe',
                'status' => 'error'
                    ), 400);
        }
       
    }


    public function showUser($id) {
        $user = User::find($id);
        if(is_object($user)){
            $user = User::find($id);
           
            return response()->json(array(
                'user' => $user,
                'status' => 'success'
                    ), 200);
        }
        else{
            return response()->json(array(
                'message' => 'El user no existe',
                'status' => 'error'
                    ), 400);
        }
       

      
    }

    //hay que mejorar la validacion
    public function update($id, Request $request) {
        $hash = $request->header('Authorization', null);

        $jwtAuth = new JwtAuth();
        $checktoken = $jwtAuth->checkToken($hash);

        if ($checktoken) {
            //recoger los parametros que llegan por post
            $json= $request->input('json',null);
            $params = json_decode($json);
            $params_array=json_decode($json,true);
         
            
            //validar los datos
            $validate = \Validator::make($params_array, [
                        'name' => 'required',
                        'surname' => 'required',
                        'email' => 'required|email'
            ]);
            if ($validate->fails()) {
                return response()->json($validate->errors(), 400);
            }
       
            //actualizar el coche
            unset($params_array['id']);
            unset($params_array['password']);
            unset($params_array['created_at']);
            unset($params_array['image_path']);
          
            $user=User::where('id',$id)->update($params_array);
           

            $array_contenido=[
                'log' => 'USUARIO EDITADO '.$id,
                'parametros'=> $params_array,
                'prioridad' => 2,
                'usuario' => $id
            ];

            $log= new Log();
            $log->prioridad=2;
            $log->nombre='USUARIO EDITADO '.$id;
            $log->save();

            //para descargar el archivo json con formato de contenido-id del mensaje
            $json_string = json_encode($array_contenido);
            $file =  "C:/wamp64/www/ApiSpinningCH/logs/USUARIO EDITADO ".$id .'.json';
            file_put_contents($file, $json_string);
            
            $data = array(
                'user' => $params,
                'message' => 'El usuario se ha actualizado correctamente',
                'status' => 'success',
                'code' => 200
            );
            
            
        } else {
            $data = array(
                'message' => 'Login incorecto al update',
                'status' => 'error',
                'code' => 300
            );
        }
        return response()->json($data, 200);
    }


    public function getTodoInactivo(){
       
            $posts = Post::where('status','ACTIVAR')->get()->load('user');
            $imagenes = Imagen::where('status','ACTIVAR')->get()->load('user');
            $videos = Video::where('status','ACTIVAR')->get()->load('user');
            $comentarios = Comentario::where('status','ACTIVAR')->get()->load('usercomentario');
            $array_contenidos=[];
            array_push($array_contenidos,$posts,$imagenes,$videos, $comentarios);


            return response()->json(array(
                'contenidos' => $array_contenidos,
                'status' => 'success'
                    ), 200);
        
       
    }

    
    
    
    
}
