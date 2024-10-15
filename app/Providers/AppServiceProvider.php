<?php

namespace App\Providers;

use App\Console\Commands\SendEmails;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\Test\Constraint\ResponseStatusCodeSame;
use Symfony\Component\HttpFoundation\Tests\Test\Constraint\ResponseStatusCodeSameTest;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->commands([
           SendEmails ::class,
        ]);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Response::macro('customJson', function ($status= 'success',$message = 'Success', $statuscode = 200) {
            return response()->json([
                'status' => $status,
                'message' => $message,
            ], $statuscode);
        });

        // Schedule $schedule
        // $schedule->command('app:send-emails')->everyMinute();
    }
}
