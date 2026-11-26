<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CreateAdmin extends Command
{
    protected $signature = 'cms:admin {email} {--name=Administrator}';

    protected $description = 'Create the initial administrator using an interactive hidden password';

    public function handle(): int
    {
        $data = ['email' => mb_strtolower($this->argument('email')), 'name' => $this->option('name'), 'password' => $this->secret('Password (12+ characters, upper/lowercase, number and symbol)')];
        $v = Validator::make($data, ['email' => 'required|email|unique:users,email', 'name' => 'required|string|max:100', 'password' => ['required', Password::min(12)->letters()->mixedCase()->numbers()->symbols()]]);
        if ($v->fails()) {
            foreach ($v->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }
        $user = new User($data);
        $user->role = 'admin';
        $user->active = true;
        $user->save();
        $this->info('Administrator created. Sign in at /login.');

        return self::SUCCESS;
    }
}
