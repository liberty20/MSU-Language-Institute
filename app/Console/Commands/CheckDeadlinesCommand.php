<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Assignment;
use App\Models\Task;
use App\Models\CourseEnrollment;
use App\Models\CourseAssignment;
use App\Models\CourseAssignmentSubmission;
use App\Models\CourseIntake;
use App\Notifications\SystemNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CheckDeadlinesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'deadlines:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check and generate notifications for overdue and approaching task and course assignment deadlines.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        Log::info("CheckDeadlinesCommand started.");
        $this->info("Checking deadlines...");

        $now = now();
        $fortyEightHoursLater = now()->addDays(2);

        // Fetch all active users
        $users = User::where('is_active', true)->get();

        $notificationsSent = 0;

        foreach ($users as $user) {
            // 1. Task Management Deadlines (Tasks under Assignment)
            $assignmentIds = Assignment::where('assigned_to', $user->id)
                ->whereNotIn('status', ['completed'])
                ->pluck('id');

            if ($assignmentIds->isNotEmpty()) {
                // Overdue Tasks
                $overdueTasks = Task::whereIn('assignment_id', $assignmentIds)
                    ->where('status', '!=', 'completed')
                    ->whereNotNull('due_date')
                    ->where('due_date', '<', $now)
                    ->get();

                foreach ($overdueTasks as $task) {
                    $exists = $user->notifications->contains(function ($n) use ($task) {
                        return ($n->data['task_id'] ?? null) == $task->id && ($n->data['alert_type'] ?? null) === 'overdue';
                    });

                    if (!$exists) {
                        $user->notify(new SystemNotification(
                            'Tasks',
                            'Task Overdue',
                            'Your assigned task "' . $task->title . '" is overdue!',
                            route('completed-tasks.index'),
                            ['task_id' => $task->id, 'alert_type' => 'overdue'],
                            'checkDeadlines'
                        ));
                        $notificationsSent++;
                    }
                }

                // Approaching Tasks (due in next 48 hours)
                $approachingTasks = Task::whereIn('assignment_id', $assignmentIds)
                    ->where('status', '!=', 'completed')
                    ->whereNotNull('due_date')
                    ->whereBetween('due_date', [$now, $fortyEightHoursLater])
                    ->get();

                foreach ($approachingTasks as $task) {
                    $exists = $user->notifications->contains(function ($n) use ($task) {
                        return ($n->data['task_id'] ?? null) == $task->id && ($n->data['alert_type'] ?? null) === 'approaching';
                    });

                    if (!$exists) {
                        $user->notify(new SystemNotification(
                            'Tasks',
                            'Task Due Soon',
                            'Your assigned task "' . $task->title . '" is due on ' . Carbon::parse($task->due_date)->format('M d, Y') . '.',
                            route('completed-tasks.index'),
                            ['task_id' => $task->id, 'alert_type' => 'approaching'],
                            'checkDeadlines'
                        ));
                        $notificationsSent++;
                    }
                }
            }

            // 2. Assessments / Course Assignment Deadlines (Students & Instructors)
            if ($user->hasRole('student')) {
                $intakeIds = CourseEnrollment::where('user_id', $user->id)
                    ->where('payment_status', 'verified')
                    ->where('enrollment_status', 'active')
                    ->pluck('course_intake_id');

                if ($intakeIds->isNotEmpty()) {
                    $assignments = CourseAssignment::whereIn('course_intake_id', $intakeIds)
                        ->whereNotNull('due_date')
                        ->get();

                    foreach ($assignments as $asg) {
                        $submitted = CourseAssignmentSubmission::where('course_assignment_id', $asg->id)
                            ->where('user_id', $user->id)
                            ->exists();

                        if (!$submitted) {
                            $due = Carbon::parse($asg->due_date);
                            if ($due->isPast()) {
                                $exists = $user->notifications->contains(function ($n) use ($asg) {
                                    return ($n->data['course_assignment_id'] ?? null) == $asg->id && ($n->data['alert_type'] ?? null) === 'overdue';
                                });

                                if (!$exists) {
                                    $user->notify(new SystemNotification(
                                        'Assessments',
                                        'Assignment Overdue',
                                        'Your coursework assignment "' . $asg->title . '" is overdue!',
                                        route('student.assignments'),
                                        ['course_assignment_id' => $asg->id, 'alert_type' => 'overdue'],
                                        'checkDeadlines'
                                    ));
                                    $notificationsSent++;
                                }
                            } elseif ($due->between($now, $fortyEightHoursLater)) {
                                $exists = $user->notifications->contains(function ($n) use ($asg) {
                                    return ($n->data['course_assignment_id'] ?? null) == $asg->id && ($n->data['alert_type'] ?? null) === 'approaching';
                                });

                                if (!$exists) {
                                    $user->notify(new SystemNotification(
                                        'Assessments',
                                        'Assignment Due Soon',
                                        'Your coursework assignment "' . $asg->title . '" is due on ' . $due->format('M d, H:i') . '.',
                                        route('student.assignments'),
                                        ['course_assignment_id' => $asg->id, 'alert_type' => 'approaching'],
                                        'checkDeadlines'
                                    ));
                                    $notificationsSent++;
                                }
                            }
                        }
                    }
                }
            } elseif ($user->hasRole('language_expert') || $user->hasRole('part_time_staff') || $user->instructedIntakes()->exists()) {
                $intakeIds = CourseIntake::where('instructor_id', $user->id)
                    ->whereNotIn('status', ['completed', 'closed'])
                    ->pluck('id');
                if ($intakeIds->isNotEmpty()) {
                    $assignments = CourseAssignment::whereIn('course_intake_id', $intakeIds)
                        ->whereNotNull('due_date')
                        ->get();

                    foreach ($assignments as $asg) {
                        $due = Carbon::parse($asg->due_date);
                        if ($due->between($now, $fortyEightHoursLater)) {
                            $exists = $user->notifications->contains(function ($n) use ($asg) {
                                return ($n->data['course_assignment_id'] ?? null) == $asg->id && ($n->data['alert_type'] ?? null) === 'instructor_approaching';
                            });

                            if (!$exists) {
                                $user->notify(new SystemNotification(
                                    'Assessments',
                                    'Assignment Deadline Approaching',
                                    'The deadline for assignment "' . $asg->title . '" is on ' . $due->format('M d, H:i') . '.',
                                    route('instructor.assignments.index'),
                                    ['course_assignment_id' => $asg->id, 'alert_type' => 'instructor_approaching'],
                                    'checkDeadlines'
                                ));
                                $notificationsSent++;
                            }
                        }
                    }
                }
            }
        }

        Log::info("CheckDeadlinesCommand completed. Sent {$notificationsSent} notifications.");
        $this->info("Completed! {$notificationsSent} notifications generated.");

        return 0;
    }
}
