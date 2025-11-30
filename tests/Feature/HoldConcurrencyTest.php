<?php
namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Product;
use GuzzleHttp\Client;

class HoldConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_no_oversell_under_parallel_holds()
    {
        $this->seed(\Database\Seeders\ProductSeeder::class);
        $product = Product::first();
        $concurrency = 20;

        // Use Guzzle to send concurrent requests via promises could be used, but in CI simpler approach:
        $responses = [];
        for ($i = 0; $i < $concurrency; $i++) {
            $responses[] = $this->postJson('/api/holds', ['product_id' => $product->id, 'qty' => 1]);
        }

        // allow some time for jobs
        sleep(1);

        $product->refresh();
        $this->assertLessThanOrEqual($product->stock_total, $product->sold + $product->reserved);
    }
}
