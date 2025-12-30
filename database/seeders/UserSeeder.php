<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    public function run()
    {
        // Pastikan nama file CSV ini sama dengan yang ada di folder project Anda
        $csvFile = base_path('users_fixed (1).csv'); 

        if (!File::exists($csvFile)) {
            $this->command->error("File CSV tidak ditemukan!");
            return;
        }

        // Hash password sekali saja di luar loop biar cepat
        $hashedPassword = Hash::make('password123');

        if (($handle = fopen($csvFile, 'r')) !== FALSE) {
            fgetcsv($handle, 1000, ','); // Lewati header CSV

            DB::beginTransaction();
            try {
                $count = 0;
                while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
                    if (count($data) < 3) continue;

                    User::updateOrCreate(
                        ['id' => $data[0]], // Cari ID 1-100
                        [
                            'name'     => $data[1],
                            'username' => $data[2],
                            'password' => $hashedPassword,
                        ]
                    );
                    $count++;
                }
                DB::commit();
                $this->command->info("Berhasil! $count user dari dataset masuk ke database.");
            } catch (\Exception $e) {
                DB::rollBack();
                $this->command->error("Gagal import: " . $e->getMessage());
            }
            fclose($handle);
        }
    }
}