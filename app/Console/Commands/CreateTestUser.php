<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class CreateTestUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-test-user';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a test admin user for the application';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (User::where('email', 'admin@example.com')->exists()) {
            $this->info('Admin user already exists');
            return;
        }

        User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'email_verified_at' => now(),
            'password' => bcrypt('password')
        ]);

        $this->info('Admin user created successfully!');
        $this->info('Email: admin@example.com');
        $this->info('Password: password');
    }
}
