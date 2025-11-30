<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Hold;

class SweepExpiredHolds extends Command
{
    protected $signature = 'holds:sweep';
    protected $description = 'Sweep expired holds and release inventory';

    public function handle()
    {
        $expired = Hold::where('status','active')->where('expires_at','<',now())->pluck('id');
        foreach ($expired as $id) {
            \App\Jobs\ExpireHoldJob::dispatch($id);
        }
        $this->info('Dispatched '.count($expired).' expire jobs');
    }
}
