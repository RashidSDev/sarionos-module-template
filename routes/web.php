<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\CallbackController;

Route::get('/whoami', function () {
    return 'MODULE TEMPLATE';
});

Route::get('/', function () {
    return redirect('/dashboard');
});

Route::get('/auth/callback', [CallbackController::class, 'handle'])
    ->name('auth.callback')
    ->middleware('verify.token');

Route::get('/logout', function (Request $request) {
    session()->flush();

    return redirect()->away(
        rtrim(env('SARIONOS_CORE_URL'), '/') . '/logout?redirect=' . urlencode(
            rtrim(env('SARIONOS_SELF_URL'), '/') . '/auth/callback'
        )
    )
    ->withCookie(cookie()->forget('sarionos_sso', '/', '.dev.sarionos.com'))
    ->withCookie(cookie()->forget(env('SESSION_COOKIE', 'sarionos_template_session'), '/', '.dev.sarionos.com'))
    ->withCookie(cookie()->forget('XSRF-TOKEN', '/', '.dev.sarionos.com'));
})->name('logout');

Route::post('/workspace/switch', function (Request $request) {
    $request->validate([
        'workspace_uuid' => ['required', 'uuid'],
    ]);

    $token = session('sarionos_token');
    if (! $token) {
        return redirect('/logout');
    }

    $core = rtrim(env('SARIONOS_CORE_URL'), '/');

    try {
        $res = \Illuminate\Support\Facades\Http::withToken($token)
            ->acceptJson()
            ->timeout(10)
            ->post($core . '/api/me/workspace', [
                'workspace_uuid' => $request->workspace_uuid,
            ]);
    } catch (\Throwable $e) {
        return back()->withErrors([
            'workspace_uuid' => 'Unable to switch workspace.',
        ]);
    }

    if (! $res->ok()) {
        if (in_array($res->status(), [401, 403], true)) {
            return redirect('/logout');
        }

        return back()->withErrors([
            'workspace_uuid' => 'Unable to switch workspace.',
        ]);
    }

    session([
        'sarionos_active_workspace_uuid' => $res->json('workspace_uuid'),
        'sarionos_active_workspace_name' => $res->json('workspace_name'),
        'force_refresh_users' => true,
        'force_refresh_modules' => true,
        'force_refresh_context' => true,
    ]);

    return back();
})->middleware('verify.token')->name('workspace.switch');


Route::middleware([
    'verify.token',
    'sync.workspace.from.core',
    'load.workspace.context',
    'ensure.module.enabled',
])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
});