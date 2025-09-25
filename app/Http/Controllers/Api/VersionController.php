<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class VersionController extends Controller
{
    public function __invoke(Request $request)
    {
        return response()->json([
            'version'     => config('app_version.latest'),
            'min_version' => config('app_version.minimum'),
            'url'         => config('app_version.url'),
            'notes'       => config('app_version.notes'),
        ]);
    }
}
