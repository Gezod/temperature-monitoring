<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class CreateUser extends Command
{
    /**
     * Nama command di terminal
     */
    protected $signature = 'user:create';

    /**
     * Deskripsi
     */
    protected $description = 'Create a default user for login';

    /**
     * Logic yang dijalankan
     */
    public function handle()
    {
        $user = User::create([
            'name' => 'User 27',
            'email' => 'user27@gmail.com',
            'password' => Hash::make('user27'),
            'phone' => '083847499297',
        ]);

        $this->info('User created successfully!');
        $this->info('Email: ' . $user->email);
    }
}
