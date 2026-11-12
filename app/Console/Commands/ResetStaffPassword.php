<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class ResetStaffPassword extends Command
{
    protected $signature = 'cms:reset-password {email}';

    protected $description = 'Reset a staff password from a trusted server console';

    public function handle(): int
    {
        $user = User::where('email', mb_strtolower($this->argument('email')))->first();
        if (! $user) {
            $this->error('Account not found.');

            return self::FAILURE;
        }
        $password = $this->secret('New password');
        $v = Validator::make(['password' => $password], ['password' => ['required', Password::min(12)->letters()->mixedCase()->numbers()->symbols()]]);
        if ($v->fails()) {
            $this->error($v->errors()->first());

            return self::FAILURE;
        }
        $user->password = $password;
        $user->remember_token = null;
        $user->save();
        $this->info('Password reset. Existing staff sessions will require authentication again.');

        return self::SUCCESS;
    }
}
