<!-- backend/routes/api.php -->
<?php
use Illuminate\Support\Facades\Route;

Route::get('/nombres', function () {
    // Laravel convierte automáticamente este arreglo en un JSON
    return response()->json([
        'names' => ['Jeff', 'John', 'Mary']
    ]);
});
