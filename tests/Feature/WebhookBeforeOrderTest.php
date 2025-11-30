<?php
namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Product;
use App\Models\Hold;
use App\Models\Order;
use App\Models\WebhookIdempotency;

class WebhookBeforeOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_before_order_is_reconciled_on_order_creation()
    {
        $this->seed(\Database\Seeders\ProductSeeder::class);
        $product = Product::first();

        $payload = ['idempotency_key' => 'before-1', 'order_id' => 9999, 'status' => 'success'];
        $this->postJson('/api/payments/webhook', $payload)->assertStatus(200);

        $hold = Hold::create(['product_id'=>$product->id,'qty'=>1,'expires_at'=>now()->addMinute(), 'status'=>'active']);
        $order = Order::create(['hold_id'=>$hold->id,'status'=>'pending','amount'=>10]);

        // associate webhook record with this new order id
        WebhookIdempotency::where('key','before-1')->update(['order_id'=>$order->id]);

        // Simulate reconciliation: create order via endpoint (which runs reconciliation)
        $this->postJson('/api/orders', ['hold_id' => $hold->id])->assertStatus(201);

        $order->refresh();
        $this->assertEquals('paid', $order->status);
    }
}
