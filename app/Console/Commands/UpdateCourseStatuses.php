<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CourseIntake;

class UpdateCourseStatuses extends Command
{
    protected $signature = 'courses:update-statuses';
    protected $description = 'Automatically update course intake statuses to completed when the end date is reached';

    public function handle()
    {
        $today = now()->toDateString();

        $completedCount = CourseIntake::where('status', 'open')
            ->where('end_date', '<', $today)
            ->update(['status' => 'completed']);

        $this->info("Successfully updated {$completedCount} intakes to 'completed'.");
        return 0;
    }
}
