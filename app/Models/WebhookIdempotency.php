<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebhookIdempotency extends Model
{
    use HasFactory;
    protected $fillable = ['key','order_id','payload','processed','processed_at'];
    protected $casts = ['payload' => 'array','processed' => 'boolean','processed_at' => 'datetime'];
}
