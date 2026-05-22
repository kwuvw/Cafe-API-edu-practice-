<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/_ops/clear-caches', function (Request $request) {
    $secret = 'replace-this-with-a-long-random-string';

    abort_if(
        $secret === 'replace-this-with-a-long-random-string',
        503,
        'Set a real secret in routes/web.php before using this endpoint.'
    );

    abort_unless(
        hash_equals($secret, (string) $request->query('token')),
        403,
        'Forbidden.'
    );

    $commands = [
        'route:clear',
        'config:clear',
        'cache:clear',
        'view:clear',
    ];

    $results = [];

    foreach ($commands as $command) {
        Artisan::call($command);
        $results[$command] = trim(Artisan::output());
    }

    return response()->json([
        'ok' => true,
        'message' => 'Caches cleared. Remove this temporary route after use.',
        'results' => $results,
    ]);
});
