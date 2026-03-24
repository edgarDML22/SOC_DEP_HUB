<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SystemController extends Controller
{
    public function getSupportLink(Request $request)
    {
        //Retorno de json 
        return response()->json([
            "success" => true,
            "data" => [
                "support_url" => config('services.google_forms.support_url')
            ]

        ]);
    }
}