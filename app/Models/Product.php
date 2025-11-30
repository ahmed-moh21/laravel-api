<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;
    protected $fillable = ['name','stock_total','reserved','sold','price'];

    public function holds() { return $this->hasMany(Hold::class); }

    public function getAvailableAttribute()
    {
        $available = (int)$this->stock_total - (int)$this->reserved - (int)$this->sold;
        return max(0, $available);
    }
}


