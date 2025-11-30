<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Models\Hold;
use App\Models\Order;
use App\Models\Product;
use App\Models\WebhookIdempotency;

class OrderController extends Controller
{
    public function store(Request $req)
    {
        $req->validate(['hold_id'=>'required|integer']);
        $holdId = $req->input('hold_id');

        $order = null;

        DB::transaction(function() use ($holdId, &$order) {
            $hold = Hold::where('id', $holdId)->lockForUpdate()->firstOrFail();
            if ($hold->status !== 'active') abort(400, 'Hold not active');
            if ($hold->expires_at->isPast()) abort(400, 'Hold expired');

            $hold->status = 'used';
            $hold->used_at = now();
            $hold->save();

            $product = Product::where('id', $hold->product_id)->lockForUpdate()->first();
            $product->reserved = max(0, $product->reserved - $hold->qty);
            $product->sold += $hold->qty;
            $product->save();

            $order = Order::create([
                'hold_id' => $hold->id,
                'status' => 'pending',
                'amount' => $product->price * $hold->qty,
            ]);

            // Immediately reconcile any pending webhooks that arrived before order creation
            $pending = WebhookIdempotency::where('order_id', $order->id)->where('processed', false)->get();
            foreach ($pending as $record) {
                $payload = $record->payload;
                if (isset($payload['status']) && $payload['status'] === 'success') {
                    $order->status = 'paid';
                    $order->save();
                } elseif (isset($payload['status']) && $payload['status'] === 'failed') {
                    $order->status = 'cancelled';
                    $order->save();

                    // release inventory: update hold/product accordingly
                    $hold->status = 'expired';
                    $hold->save();

                    $product->sold = max(0, $product->sold - $hold->qty);
                    $product->save();
                }

                $record->processed = true;
                $record->processed_at = now();
                $record->save();
            }

            // invalidate cache
            Cache::forget("product:{$product->id}");
        });

        return response()->json(['order_id'=>$order->id,'status'=>$order->status], 201);
    }
}
