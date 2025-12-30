<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MovieSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \DB::table('movies')->insert([
        ['id' => 1, 'title' => 'Toy Story', 'genre' => 'Animation'],
        ['id' => 2, 'title' => 'Jumanji', 'genre' => 'Adventure'],
        // Lanjutkan sampai 110 film...
    ]);
    }

    
}
