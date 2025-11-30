<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Models\Product;
use App\Models\Hold;
use App\Jobs\ExpireHoldJob;

class HoldController extends Controller
{
    public function store(Request $req)
    {
        $req->validate(['product_id'=>'required|integer','qty'=>'required|integer|min:1']);
        $productId = $req->input('product_id');
        $qty = (int)$req->input('qty');

        $hold = null;

        DB::transaction(function() use ($productId, $qty, &$hold) {
            $product = Product::where('id', $productId)->lockForUpdate()->firstOrFail();
            $available = (int)$product->stock_total - (int)$product->reserved - (int)$product->sold;
            if ($available < $qty) {
                abort(409, 'Not enough stock');
            }
            $product->reserved += $qty;
            $product->save();

            $hold = Hold::create([
                'product_id'=>$product->id,
                'qty'=>$qty,
                'expires_at'=>now()->addMinutes(2),
                'status'=>'active',
            ]);
        });

        // Dispatch delayed job to expire
        ExpireHoldJob::dispatch($hold->id)->delay(now()->addMinutes(2));

        // Invalidate cache
        Cache::forget("product:{$productId}");

        return response()->json(['hold_id'=>$hold->id,'expires_at'=>$hold->expires_at->toDateTimeString()], 201);
    }
}
