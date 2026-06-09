<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class CourseDummySeeder extends Seeder
{
    public function run()
    {
        if (DB::table('courses')->count() > 0) {
            return;
        }

        $temp = \App\Services\UserBackupService::$shouldBackup;
        \App\Services\UserBackupService::$shouldBackup = false;

        try {
            $snsu = DB::table('departments')->where('code', 'SNSU')->first();
        $ilas = DB::table('departments')->where('code', 'ILASU')->first();
        $expert = User::where('email', 'expert@msunli.edu')->first();
        $parttime = User::where('email', 'parttime@msunli.edu')->first();
        $client = User::where('email', 'client@example.com')->first();

        $courses = [
            [
                'title' => 'Basic Zimbabwean Sign Language (ZSL)',
                'code' => 'ZSL-101',
                'category' => 'Sign Language',
                'description' => 'Introduction to basic conversational Zimbabwean Sign Language, including alphabet, common greetings, vocabulary, and cultural etiquette.',
                'department_id' => $snsu ? $snsu->id : null,
                'price' => 150.00,
                'currency' => 'USD',
                'duration_weeks' => 8,
                'is_published' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Unified English Braille (UEB) Grade 1',
                'code' => 'BR-101',
                'category' => 'Braille',
                'description' => 'Comprehensive training in Unified English Braille (UEB) Grade 1 reading and writing using manual and electronic braillers.',
                'department_id' => $snsu ? $snsu->id : null,
                'price' => 180.00,
                'currency' => 'USD',
                'duration_weeks' => 10,
                'is_published' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Conversational Swahili for Beginners',
                'code' => 'SWA-101',
                'category' => 'Swahili',
                'description' => 'A practical course focusing on Swahili communication skills for business, travel, and cultural engagement in East Africa.',
                'department_id' => $ilas ? $ilas->id : null,
                'price' => 120.00,
                'currency' => 'USD',
                'duration_weeks' => 6,
                'is_published' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Professional Translation and Interpretation',
                'code' => 'TRANS-201',
                'category' => 'Translation',
                'description' => 'Advanced principles, workflows, and standards in document translation and consecutive sign language/foreign language interpretation.',
                'department_id' => $ilas ? $ilas->id : null,
                'price' => 250.00,
                'currency' => 'USD',
                'duration_weeks' => 12,
                'is_published' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($courses as $course) {
            DB::table('courses')->updateOrInsert(
                ['code' => $course['code']],
                $course
            );
        }

        $zslCourse = DB::table('courses')->where('code', 'ZSL-101')->first();
        $swaCourse = DB::table('courses')->where('code', 'SWA-101')->first();

        if ($zslCourse) {
            DB::table('course_intakes')->updateOrInsert(
                ['course_id' => $zslCourse->id, 'name' => 'Winter 2026 Intake'],
                [
                    'course_id' => $zslCourse->id,
                    'name' => 'Winter 2026 Intake',
                    'start_date' => now()->addDays(7),
                    'end_date' => now()->addDays(7 + 8 * 7),
                    'capacity' => 30,
                    'status' => 'open',
                    'instructor_id' => $expert ? $expert->id : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        if ($swaCourse) {
            DB::table('course_intakes')->updateOrInsert(
                ['course_id' => $swaCourse->id, 'name' => 'June 2026 Batch'],
                [
                    'course_id' => $swaCourse->id,
                    'name' => 'June 2026 Batch',
                    'start_date' => now()->addDays(14),
                    'end_date' => now()->addDays(14 + 6 * 7),
                    'capacity' => 25,
                    'status' => 'open',
                    'instructor_id' => $parttime ? $parttime->id : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        // Add a demo enrollment for the client in the ZSL course
        $intake = DB::table('course_intakes')->where('name', 'Winter 2026 Intake')->first();
        if ($intake && $client) {
            DB::table('course_enrollments')->updateOrInsert(
                ['course_intake_id' => $intake->id, 'user_id' => $client->id],
                [
                    'course_intake_id' => $intake->id,
                    'user_id' => $client->id,
                    'payment_status' => 'verified',
                    'payment_proof_path' => 'payments/proof_zsl.pdf',
                    'amount_paid' => 150.00,
                    'enrollment_status' => 'active',
                    'certificate_code' => null,
                    'certificate_issued_at' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
        } finally {
            \App\Services\UserBackupService::$shouldBackup = $temp;
        }
    }
}
