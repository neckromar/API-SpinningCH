<?php
header("access-control-allow-origin: *");
header('Access-Control-Allow-Headers: X-CSRF-Token, Access-Control-Request-Headers, Access-Control-Request-Method, Accept, X-Requested-With, Content-Type, X-Auth-Token, Origin, Authorization');
header('Access-Control-Allow-Methods: PATCH, GET, POST, PUT, DELETE, OPTIONS');
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::post('/register','UserController@register');
Route::post('/login','UserController@login');


Route::resource('/user','UserController');
Route::post('/user/image-upload','UserController@upload');
Route::get('/user/image-collect/{filename}','UserController@getImage');

Route::resource('/videos','VideoController');


Route::resource('/imagenes','ImagenController');
Route::post('/imagenes/image-upload','ImagenController@upload');
Route::get('/imagenes/image-collect/{filename}','ImagenController@getImage');

Route::resource('/comentario','ComentarioController');

Route::resource('/videos','VideoController');

Route::post('/videos/video-upload','VideoController@upload');
Route::post('/videos/miniatura-upload','VideoController@uploadminiatura');

Route::get('/videos/video-collect/{filename}','VideoController@getVideo');
Route::get('/videos/miniatura-collect/{filename}','VideoController@getMiniatura');