<?php
namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Product;
use App\Models\Hold;
use App\Models\Order;

class WebhookIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_duplicate_webhook_keys_are_idempotent()
    {
        $this->seed(\Database\Seeders\ProductSeeder::class);
        $product = Product::first();

        $hold = Hold::create(['product_id'=>$product->id,'qty'=>1,'expires_at'=>now()->addMinute(), 'status'=>'used','used_at'=>now()]);
        $order = Order::create(['hold_id'=>$hold->id,'status'=>'pending','amount'=>10]);

        $payload = ['idempotency_key' => 'abc-123', 'order_id' => $order->id, 'status' => 'success'];

        $this->postJson('/api/payments/webhook', $payload)->assertStatus(200);
        $this->postJson('/api/payments/webhook', $payload)->assertStatus(200);

        $order->refresh();
        $this->assertEquals('paid', $order->status);

        $this->assertDatabaseCount('webhook_idempotencies', 1);
    }
}
