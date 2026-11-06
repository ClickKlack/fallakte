<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

/**
 * Legt einen Benutzer an. Ersatz für die deaktivierte Registrierung.
 */
class CreateUser extends Command
{
    protected $signature = 'user:create
        {--name= : Anzeigename}
        {--email= : E-Mail-Adresse (Login)}
        {--password= : Passwort (ohne Angabe wird es verdeckt abgefragt)}';

    protected $description = 'Legt einen Benutzer für den Login an';

    public function handle(): int
    {
        $data = [
            'name' => $this->option('name') ?? text(label: 'Name', required: true),
            'email' => $this->option('email') ?? text(label: 'E-Mail', required: true),
            'password' => $this->option('password') ?? password(label: 'Passwort', required: true),
        ];

        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'string', Password::min(8)],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $this->info("Benutzer {$user->email} wurde angelegt.");

        return self::SUCCESS;
    }
}
