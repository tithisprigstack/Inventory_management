<?php

namespace App\Console\Commands;

use App\Models\Inventory;
use App\Models\Vendor;
use Illuminate\Console\Command;
use App\Models\User;
use App\Mail\LowStockReminderMail;
use Log;
use Mail;

class SendEmails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-emails';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'send Email for low stock';

    /**
     * Execute the console command.
     */
    public function handle()
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

        $this->info('Low stock mail has been sent.');
    }
}
