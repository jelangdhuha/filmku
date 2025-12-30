<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ImportRatingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // 1. Coba cari file di folder utama (Root)
        $csvFile = base_path('ratings_final_fixed_100User.csv');

        // Jika tidak ada di root, coba cari di folder Public
        if (!File::exists($csvFile)) {
            $csvFile = public_path('ratings_final_fixed_100User.csv');
            
            if (!File::exists($csvFile)) {
                $this->command->error("File CSV tidak ditemukan di folder utama maupun public.");
                return;
            }
        }

        // 2. Baca file CSV
        $data = array_map('str_getcsv', file($csvFile));
        
        // Hapus header (baris pertama: user_id, movie_id, rating)
        $header = array_shift($data);

        // 3. Proses Insert per Batch
        $batchSize = 500; 
        $chunk = [];

        foreach ($data as $row) {
            // Pastikan baris memiliki minimal 3 kolom
            if (count($row) >= 3) {
                $chunk[] = [
                    'user_id'    => $row[0],
                    'movie_id'   => $row[1],
                    'rating'     => $row[2],
                    // 'created_at' dan 'updated_at' SAYA HAPUS agar tidak error
                ];
            }

            // Jika tampungan sudah 500, masukkan ke DB
            if (count($chunk) >= $batchSize) {
                DB::table('ratings')->insertOrIgnore($chunk); 
                $chunk = [];
            }
        }

        // Masukkan sisa data terakhir
        if (!empty($chunk)) {
            DB::table('ratings')->insertOrIgnore($chunk);
        }

        $this->command->info('Berhasil import data ratings dari CSV!');
    }
}