<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;

class ProductSeeder extends Seeder
{
    public function run()
    {
        Product::create([
            'name' => 'Flash Widget',
            'stock_total' => 10,
            'price' => 19.99,
        ]);
    }
}
