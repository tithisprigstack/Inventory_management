<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Inventory;
use App\Models\User;
use App\Mail\LowStockReminderMail;
use Log;
use Mail;

class SendEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    /**
     * Create a new job instance.
     */
    public function __construct()
    {
    //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $allInventories = Inventory::whereColumn('quantity', '<=', 'reminder_quantity')->get();
        foreach($allInventories as $inventory)
        {
            $user = User::find(1);
                    $data = [
                        'userDetails' => $user,
                        'inventoryDetails' => $inventory
                    ];
                    try {
                        Mail::to($user->email)->send(new LowStockReminderMail($data));
                    } catch (\Exception $e) {
                        Log::error("Mail sending failed: " . $e->getMessage());
                    }
        }
    }
}
