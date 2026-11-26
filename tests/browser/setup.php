<?php

use App\Models\User;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! app()->environment('testing')) {
    fwrite(STDERR, "Browser fixtures require APP_ENV=testing.\n");
    exit(1);
}
$user = User::firstOrNew(['email' => 'browser@example.invalid']);
$user->name = 'Browser Test Administrator';
$user->password = 'Browser-Only-Test!123';
$user->role = 'admin';
$user->active = true;
$user->save();
