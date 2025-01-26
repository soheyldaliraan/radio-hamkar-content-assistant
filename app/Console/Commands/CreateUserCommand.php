<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CreateUserCommand extends Command
{
    protected $signature = 'user:create 
                          {--name= : The name of the user}
                          {--email= : The email of the user}
                          {--password= : The password for the user}
                          {--admin : Whether the user should be an admin}';

    protected $description = 'Create a new user';

    public function handle(): int
    {
        // Get or prompt for user details
        $name = $this->option('name') ?: $this->ask('What is the user\'s name?');
        $email = $this->option('email') ?: $this->ask('What is the user\'s email?');
        $password = $this->option('password') ?: $this->secret('What is the user\'s password?');
        $isAdmin = $this->option('admin') ?: $this->confirm('Should this user be an admin?', false);

        // Validate input
        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', Password::defaults()],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }
            return Command::FAILURE;
        }

        try {
            // Create the user
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'is_admin' => $isAdmin,
            ]);

            $this->info('User created successfully!');
            $this->table(
                ['Name', 'Email', 'Admin'],
                [[$user->name, $user->email, $user->is_admin ? 'Yes' : 'No']]
            );

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to create user: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
} 