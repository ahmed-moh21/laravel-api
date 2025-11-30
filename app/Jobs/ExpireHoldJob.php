<?php
namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;
use App\Models\Hold;
use App\Models\Product;

class ExpireHoldJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    public $holdId;

    public function __construct($holdId) { $this->holdId = $holdId; }

    public function handle()
    {
        DB::transaction(function() {
            $hold = Hold::where('id', $this->holdId)->lockForUpdate()->first();
            if (!$hold) return;
            if ($hold->status !== 'active') return; // already used/expired
            if ($hold->expires_at->isFuture()) return; // not yet expired

            // mark expired, decrement product reserved
            $hold->status = 'expired';
            $hold->save();

            $product = Product::where('id', $hold->product_id)->lockForUpdate()->first();
            $product->reserved = max(0, $product->reserved - $hold->qty);
            $product->save();

            // invalidate cache
            \Illuminate\Support\Facades\Cache::forget("product:{$product->id}");
        });
    }
}
