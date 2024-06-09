<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FetchCnpjDataController;

Route::get('/', function () {
    return redirect('/admin');
});
