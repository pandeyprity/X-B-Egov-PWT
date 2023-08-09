<?php

use Illuminate\Support\Facades\Route;

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
#---------------------------- document read ------------------------------
Route::get('/getImageLink', function () {
    return view('getImageLink');
});
Route::get('/whatsappTest', [\App\Http\Controllers\Notice\NoticeController::class, 'openNoticiList']);
// Laravel Logging
Route::get('logs', [\Rap2hpoutre\LaravelLogViewer\LogViewerController::class, 'index']);
