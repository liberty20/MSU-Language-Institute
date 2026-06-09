<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SystemSetting;

class SystemSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if (SystemSetting::count() > 0) {
            return;
        }

        $temp = \App\Services\UserBackupService::$shouldBackup;
        \App\Services\UserBackupService::$shouldBackup = false;

        try {
            // 1. FAQs
            SystemSetting::set('short_courses_faqs', [
            [
                'question' => 'How do I apply for a short course?',
                'answer' => 'To apply, click on the "Apply Now" button on the Short Courses page, select your preferred course and intake schedule, fill in your personal details, and upload the required documents (national ID/passport and academic certificates).'
            ],
            [
                'question' => 'How long does the enrollment approval take?',
                'answer' => 'The approval process usually takes 2 to 4 working days. Your application goes through a complete verification flow starting with the Administrative Assistant, review by the Deputy Director, and final approval by the Executive Director.'
            ],
            [
                'question' => 'When will I receive my login credentials?',
                'answer' => 'Once your payment proof is verified by the ICT Administrator and final approval is granted by the Executive Director, the system automatically creates your student account and sends an acceptance email containing your portal login credentials.'
            ],
            [
                'question' => 'Can I apply for multiple courses simultaneously?',
                'answer' => 'Yes, you can apply for multiple courses at the same time. You will need to submit a separate application form and pay the tuition fee for each individual course intake.'
            ],
            [
                'question' => 'Are certificates issued upon completion?',
                'answer' => 'Absolutely. Highly recognized MSU certificates of competency are awarded to students who satisfy all continuous assessment marks, coursework assignments, and practical assessment requirements.'
            ]
        ]);

        // 2. Testimonials
        SystemSetting::set('short_courses_testimonials', [
            [
                'name' => 'Tendai Chinyama',
                'course' => 'Zimbabwean Sign Language Beginners',
                'text' => 'The evening Zimbabwean Sign Language class was exceptional. The instructor paced the class beautifully, and the hybrid delivery mode fit perfectly into my busy hospital duty roster. I am now able to communicate with deaf patients with confidence.'
            ],
            [
                'name' => 'Sandra Gumbo',
                'course' => 'Unified English Braille Certification',
                'text' => 'Enrolling in the Braille course at MSULI was career-transforming. The Special Needs Services Unit staff are highly passionate and experienced. The continuous assessment feedback was precise and encouraging.'
            ],
            [
                'name' => 'Carlos da Silva',
                'course' => 'Conversational Swahili Swac-101',
                'text' => 'The weekend Swahili cohort at Gweru campus was outstanding. Ideal for business professionals seeking language fluency in East Africa. Highly recommended!'
            ]
        ]);

        // 3. Announcements
        SystemSetting::set('short_courses_announcements', [
            [
                'date' => '2026-05-28',
                'title' => 'Unified English Braille Training Open',
                'text' => 'Applications for the Winter 2026 Unified English Braille (UEB) training batches are officially open. Enrollment capacity is limited to 25 seats per intake. Standard tuition fees apply.'
            ],
            [
                'date' => '2026-05-25',
                'title' => 'Weekend Swahili Conversational Cohort',
                'text' => 'Interactive Swahili conversational cohorts will begin operations on Saturday mornings at the Gweru Main Campus. Ideal for corporate stakeholders and business travelers.'
            ],
            [
                'date' => '2026-05-20',
                'title' => 'Sign Language Evening Classes Scheduled',
                'text' => 'To promote national inclusivity, evening Zimbabwean Sign Language batches are now scheduled from 5:30 PM to 7:30 PM to accommodate public service and healthcare personnel.'
            ]
        ]);

        // 4. Contact Information
        SystemSetting::set('short_courses_contact_info', [
            'email' => 'language.institute@msu.ac.zw',
            'phone' => '+263 54 2260331',
            'mobile' => '+263 772 123 456',
            'location' => 'MSU Gweru Main Campus, Administration Block, 1st Floor, Gweru, Zimbabwe',
            'hours' => 'Monday - Friday: 8:00 AM - 4:30 PM (Closed on public holidays)'
        ]);

        // 5. Banking Details
        SystemSetting::set('short_courses_banking_details', [
            'account_name' => 'Midlands State University National Language Institute',
            'bank' => 'CBZ Bank',
            'branch' => 'Gweru Branch',
            'account_number' => '01223456789012',
            'nostro_number' => '01223456789099',
            'type' => 'Current Account',
            'currency_accepted' => 'USD, ZiG'
        ]);
        } finally {
            \App\Services\UserBackupService::$shouldBackup = $temp;
        }
    }
}
