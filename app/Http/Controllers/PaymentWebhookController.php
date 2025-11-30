<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use App\Models\WebhookIdempotency;
use App\Models\Order;

class PaymentWebhookController extends Controller
{
    public function receive(Request $req)
    {
        $req->validate(['idempotency_key'=>'required|string','order_id'=>'required|integer','status'=>'required|in:success,failed']);

        $key = $req->input('idempotency_key');
        $payload = $req->all();

        // Use firstOrCreate to avoid race conditions and to preserve payload on first write
        try {
            $record = WebhookIdempotency::firstOrCreate(
                ['key' => $key],
                ['order_id' => $req->input('order_id'), 'payload' => $payload, 'processed' => false]
            );
        } catch (QueryException $e) {
            // If there's a duplicate key error because of a race, fetch the existing row
            $record = WebhookIdempotency::where('key', $key)->first();
            if (!$record) throw $e; // unexpected
        }

        // If already processed, nothing to do
        if ($record->processed) return response()->json(['status'=>'ok']);

        DB::transaction(function() use ($record, $req) {
            $order = Order::where('id', $req->input('order_id'))->lockForUpdate()->first();
            if (!$order) {
                // Order not created yet — leave record unprocessed for reconciliation when order is created
                return;
            }

            if ($record->processed) return;

            if ($req->input('status') === 'success') {
                $order->status = 'paid';
                $order->save();
            } else {
                $order->status = 'cancelled';
                $order->save();

                // release inventory: fetch hold and update product
                $hold = $order->hold()->lockForUpdate()->first();
                if ($hold && $hold->status === 'used') {
                    $hold->status = 'expired';
                    $hold->save();

                    $product = $hold->product()->lockForUpdate()->first();
                    $product->sold = max(0,$product->sold - $hold->qty);
                    $product->save();
                }
            }

            $record->processed = true;
            $record->processed_at = now();
            $record->save();
        });

        return response()->json(['status'=>'ok']);
    }
}
