<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;


// Health-check — used by load balancers / Docker HEALTHCHECK
Route::get('/health', function () {
    $checks = [];

    try {
        DB::connection()->getPdo();
        $checks['database'] = 'ok';
    } catch (\Throwable) {
        $checks['database'] = 'error';
    }

    try {
        Cache::store('redis')->put('health', 1, 5);
        $checks['redis'] = 'ok';
    } catch (\Throwable) {
        $checks['redis'] = 'error';
    }

    $status = in_array('error', $checks) ? 503 : 200;

    return response()->json(
        ['status' => $status === 200 ? 'ok' : 'degraded'] + $checks,
        $status
    );
});
