<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Di sini kita memanggil UserSeeder agar dijalankan
        $this->call([
            UserSeeder::class,
        ]);
        
        // Jika nanti Anda punya MovieSeeder atau RatingSeeder, 
        // tinggal tambahkan di dalam array di atas.
    }
}