<!-- En este archivo se colocan las rutas de todas las páginas del Sitio Web
    Y te redirige a la view correspondiente (pagina de HTML con la info a presentar)
-->
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


