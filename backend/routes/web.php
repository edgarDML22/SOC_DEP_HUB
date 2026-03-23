<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/nombres', function () {
    $names = [
        'Jeff',
        'John',
        'Mary'
    ];

    // Llamamos a una vista llamada 'nombres' y le pasamos el arreglo
    return view('nombres', ['names' => $names]);
});


