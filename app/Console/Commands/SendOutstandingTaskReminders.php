<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ReminderService;

class SendOutstandingTaskReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reminders:send';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Identify outstanding user tasks and send email and system notification reminders without duplicate spamming.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(ReminderService $reminderService)
    {
        $this->info('Starting outstanding task reminders dispatch...');
        $count = $reminderService->sendReminders();
        $this->info("Successfully dispatched {$count} task reminders.");
        return 0;
    }
}
