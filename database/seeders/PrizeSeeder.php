<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PrizeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('prizes_am_level_up')->delete();

        DB::table('prizes_am_level_up')->insert([
            [
                'img' => '10.png',
                'name' => 'Saldo LinkAja/Ovo/Gopay 10 juta',
                'point' => 250,
                'stock' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'img' => 'apple ipad gen 11.png',
                'name' => 'Apple iPad Gen 11 2025',
                'point' => 220,
                'stock' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'img' => 'apple watch se3.png',
                'name' => 'Apple Watch SE3',
                'point' => 200,
                'stock' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'img' => 'nescafe.png',
                'name' => 'Nescafe Dolce Gusto Genio S Plus Mesin Kopi',
                'point' => 170,
                'stock' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'img' => 'tv cooca.png',
                'name' => 'TV COOCAA S3U 32 Inch Smart TV',
                'point' => 150,
                'stock' => 5,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'img' => '10.png',
                'name' => 'Saldo LinkAja/Ovo/Gopay 1 juta',
                'point' => 130,
                'stock' => 10,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'img' => '10.png',
                'name' => 'Saldo LinkAja/Ovo/Gopay 700 ribu',
                'point' => 90,
                'stock' => 14,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'img' => 'exsport casual totepack.png',
                'name' => 'Exsport Daily Casual Laptop Totepack',
                'point' => 75,
                'stock' => 10,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'img' => 'xiaomi watch 5 active.png',
                'name' => 'Xiaomi Redmi Watch 5 Active',
                'point' => 60,
                'stock' => 10,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'img' => '10.png',
                'name' => 'Saldo LinkAja/Ovo/Gopay 300 ribu',
                'point' => 45,
                'stock' => 33,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
