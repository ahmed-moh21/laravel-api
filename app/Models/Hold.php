<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Hold extends Model
{
    use HasFactory;
    protected $fillable = ['product_id','qty','expires_at','status','used_at'];

    protected $dates = ['expires_at','used_at','created_at','updated_at'];

    public function product() { return $this->belongsTo(Product::class); }

    public function isExpired() { return $this->expires_at->isPast() || $this->status === 'expired'; }
}
