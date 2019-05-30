<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\User;
use App\Video;
use App\Imagen;
use App\Post;
use App\Role;
use App\Log;
use App\Comentario;
use Illuminate\Support\Facades\DB;
use App\Helpers\JwtAuth;

class UserController extends Controller {

    public function show($id) {
        $user = User::find($id);
        if(is_object( $user)){
            $user = User::find($id);

            return response()->json(array(
                'user' => $user,
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
            //file_put_contents($file, $json_string);
            
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


    public function register(Request $request) {
        //recoger post
        $json = $request->input('json', null);
        $params = json_decode($json);

        $email = (!is_null($json) && isset($params->email)) ? $params->email : null;

        $name = (!is_null($json) && isset($params->name)) ? $params->name : null;
        $surname = (!is_null($json) && isset($params->surname)) ? $params->surname : null;
        
        $image_path = "perfil.jpg";
        $role =(!is_null($json) && isset($params->role_id)) ? $params->role_id : 3;
        $password = (!is_null($json) && isset($params->password)) ? $params->password : null;

        if (!is_null($email) && !is_null($name) && !is_null($password)) {
            //crear el usuario
            $user = new User();
            $user->email = $email;
            $user->name = $name;
            $user->image_path = $image_path;
            $user->surname = $surname;
            $user->role_id = $role;

            //cifrar la clave
            $pwd = hash('sha256', $password);
            $user->password = $pwd;

            //comprobar usuario duplicado
            $isset_user = User::where('email', $email)->first();

            if ($isset_user == null) {

                //PARA HACER EL LOG DEL REGISTRO
                $tiempo=time();
                $array_contenido=[
                        'log' => 'NUEVO USUARIO IDENT '.$tiempo,
                        'rol' =>$user->role_id,
                        'nombre'=> $name .' '.$surname,
                        'email' => $email,
                        'prioridad' => 1
                    ];

                $log= new Log();
                $log->prioridad=1;
                $log->nombre='NUEVO USUARIO IDENT '.$tiempo;
                $log->save();

                //para descargar el archivo json con formato de contenido-id del mensaje
                $json_string = json_encode($array_contenido);
                $file =  "C:/wamp64/www/ApiSpinningCH/logs/NUEVO USUARIO IDENT ".$tiempo .'.json';
             //   file_put_contents($file, $json_string);
                //hasta aqui

                $user->save();
                
                $data = array(
                    'status' => 'success',
                    'code' => 200,
                    'message' => 'Te has registrado correctamente'
                );
            } else {
                //no guardarlo si existe
                $data = array(
                    'status' => 'error',
                    'code' => 400,
                    'message' => 'usuario duplicado, no puedes registrarte'
                );
            }
        } else {
            $data = array(
                'status' => 'error',
                'code' => 400,
                'message' => 'usuario no cread'
            );
        }
        return response()->json($data, 200);
    }

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

        if (!is_null($email) && !is_null($password) && ($getToken == null || $getToken=='false')) {
            
            $signup = $jwtAuth->signup($email, $pwd);

        } elseif ($getToken != null) {
            $signup = $jwtAuth->signup($email, $pwd, $getToken);
           
        } else {
          $signup= array(
            'status' => 'error',
            'code' => 400,
            'message' => 'Envia tus datos por post'
            );
        }
        
         return response()->json($signup, 200);
    }


    public function upload(Request $request){

        //re4coger datos de la peticion
        $image=$request->file('file0');

        //validate

        $validate=\Validator::make($request->all(),[
        'file0' => 'required|image|mimes:jpg,jpeg,png'
    ]);


        //guardar imagen
        if( !$image || $validate->fails())
        {
            $data = array(
                'status' => 'error',
                'code' => 400,
                'message' => 'Error al subir el archivo '
            );
       
        }else{
          
            $image_name=time().$image->getClientOriginalName();
            \Storage::disk('users')->put($image_name, \File::get($image));
 
            $data=array(
                'code' => 200,
                'image' => $image_name,
                'status' => 'success'
            );
        }

      
        return response()->json($data,$data['code']);
    }
   

    public function getImage($filename){

        $isset =\Storage::disk('users')->exists($filename);
        if($isset){
            $file=\Storage::disk('users')->get($filename);
            return new Response($file,200);
        }else{
            $data = array(
                'status' => 'error',
                'code' => 404,
                'message' => 'No existe el archivo '
            );
        }
       
        return response()->json($data,$data['code']);
      
    }

    
    public function destroy($id, Request $request){
        $hash = $request->header('Authorization', null);
        
        $jwtAuth= new JwtAuth();
        $checkToken=$jwtAuth->checkToken($hash);
        
        if($checkToken){
            //comprobar si existe el registro
            $user=User::find($id);

            //borrar comentarios de este usuario
           $comentarios=Comentario::where('user_id',$id)->get()->each->delete();
        

            //borrar fotos
            $imagenes=Imagen::where('user_id',$id)->get();
            foreach($imagenes as $imagen){
                //borrarlo
                \Storage::disk('imagenes')->delete($imagen->imagen_path);
                $imagen->delete();

            }
               
            //borrar videos
            $videos=Video::where('user_id',$id)->get();
            foreach($videos as $video){
                //borrarlo
                \Storage::disk('videos')->delete($video->video_path);
                \Storage::disk('miniaturas')->delete($video->miniatura);
                $video->delete();

            }

            //borrar post
            $posts=Post::where('user_id',$id)->get()->each->delete();



            $array_contenido=[
                'log' => 'USUARIO ELIMINADO '.$id,
                'prioridad' => 3,
                'nombre'=>$user->name,
                'correo' => $user->email,
                'comentarios_borrados' => $comentarios,
                'imagenes_borradas' =>$imagenes,
                'videos' =>  $videos,
                'posts' =>$posts,
                'usuario_del_com'=>$user->id
            ];

            $log= new Log();
            $log->prioridad=3;
            $log->nombre='USUARIO ELIMINADO '.$user->id;
            $log->save();

            //para descargar el archivo json con formato de contenido-id del mensaje
            $json_string = json_encode($array_contenido);
            $file =  "C:/wamp64/www/ApiSpinningCH/logs/USUARIO ELIMINADO ".$id .'.json';
           // file_put_contents($file, $json_string);

            DB::table('deleted_users')->insert([
                ['id' => $user->id, 'email' =>  $user->email,'role_id' =>  $user->role_id,'name' =>  $user->name,'surname' =>  $user->surname]
              
            ]);


            //borrarlo
            $user->delete();
            
            //devolver el registro borrado
             $user = array(
                'message' => 'Usuario borrado correctamente',
                'status' => 'error',
                'code' => 200
            );
             
        }else{
              $data = array(
                'message' => 'Login incorecto',
                'status' => 'error',
                'code' => 400
            );
        }     
        
        return response()->json($data, 200);
    }

}
