<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Revolution\Google\Sheets\Facades\Sheets;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {

    $sheet = Sheets::spreadsheet(env('SPREADSHEET_ID'))->sheet('Лист1');
//    $data = $sheet->all();
    
    $sheet->range('AC2')->update([['1']]);
//    dump($data[0]); exit;
});
