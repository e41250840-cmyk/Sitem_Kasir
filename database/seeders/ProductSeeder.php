<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            ['code' => 'BRG001', 'barcode' => '899123456001', 'name' => 'Indomie Goreng', 'price' => 3500, 'stock' => 100],
            ['code' => 'BRG002', 'barcode' => '899123456002', 'name' => 'Aqua 600ml', 'price' => 4000, 'stock' => 50],
            ['code' => 'BRG003', 'barcode' => '899123456003', 'name' => 'Teh Botol Sosro', 'price' => 5000, 'stock' => 50],
            ['code' => 'BRG004', 'barcode' => '899123456004', 'name' => 'Beras 5 Kg', 'price' => 75000, 'stock' => 20],
            ['code' => 'BRG005', 'barcode' => '899123456005', 'name' => 'Minyak Goreng 1 Liter', 'price' => 18000, 'stock' => 30],
            ['code' => 'BRG006', 'barcode' => '899123456006', 'name' => 'Gula Pasir 1 Kg', 'price' => 17000, 'stock' => 40],
        ];

        foreach ($products as $p) {
            Product::updateOrCreate(['code' => $p['code']], $p);
        }
    }
}
