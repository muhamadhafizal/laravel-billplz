<?php

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

Route::post('/token/buytoken', 'ProductController@index');
Route::get('/product/bill', 'ProductController@bill');
Route::get('/redirect', 'ProductController@redirect');

Route::post('/webhook', 'ProductController@webhook');
