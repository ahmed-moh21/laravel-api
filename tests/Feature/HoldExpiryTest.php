<?php
namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Product;
use App\Models\Hold;
use App\Jobs\ExpireHoldJob;

class HoldExpiryTest extends TestCase
{
    use RefreshDatabase;

    public function test_hold_expiry_releases_inventory()
    {
        $this->seed(\Database\Seeders\ProductSeeder::class);
        $product = Product::first();

        $hold = Hold::create(['product_id'=>$product->id,'qty'=>2,'expires_at'=>now()->subSeconds(1),'status'=>'active']);
        (new ExpireHoldJob($hold->id))->handle();

        $product->refresh();
        $this->assertEquals(0, $product->reserved);
    }
}
