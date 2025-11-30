<?php
namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Support\Facades\Cache;

class ProductController extends Controller
{
    public function show($id)
    {
        $cacheKey = "product:{$id}";
        $data = Cache::remember($cacheKey, 10, function() use ($id) {
            $p = Product::findOrFail($id);
            return [
                'id'=>$p->id,
                'name'=>$p->name,
                'price'=>$p->price,
                'stock_total'=>$p->stock_total,
                'reserved'=>$p->reserved,
                'sold'=>$p->sold,
                'available'=>$p->available,
            ];
        });
        return response()->json($data);
    }


}
