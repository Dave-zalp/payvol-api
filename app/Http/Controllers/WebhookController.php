<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    //

    public function virtualbank (Request $request){
        dd($request);
        Log::info($request);
    }
}
