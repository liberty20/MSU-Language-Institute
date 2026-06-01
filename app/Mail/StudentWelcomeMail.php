<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class StudentWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public $application;
    public $courseName;
    public $password;

    public function __construct($application, $courseName, $password)
    {
        $this->application = $application;
        $this->courseName = $courseName;
        $this->password = $password;
    }

    public function build()
    {
        return $this->subject('MSULI Short Course Enrollment Confirmation')
                    ->html($this->buildHtmlContent());
    }

    protected function buildHtmlContent()
    {
        return '
        <html>
        <head>
            <title>MSULI Short Course Enrollment Approved</title>
        </head>
        <body style="font-family: Arial, sans-serif; color: #333; line-height: 1.6; background-color: #f4f4f4; padding: 20px; margin: 0;">
            <div style="max-width: 600px; margin: 0 auto; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.05); border: 1px solid #e2e8f0;">
                <div style="background-color: #0a1f44; padding: 30px; text-align: center; color: white;">
                    <h1 style="margin: 0; font-size: 24px; font-weight: bold; letter-spacing: 0.5px;">MSULI</h1>
                    <p style="margin: 5px 0 0 0; font-size: 12px; text-transform: uppercase; color: #f5c242; font-weight: bold; letter-spacing: 1px;">National Language Institute</p>
                </div>
                <div style="padding: 30px;">
                    <h2 style="color: #0a1f44; margin-top: 0; font-size: 20px;">Course Enrollment Approved!</h2>
                    <p>Dear <strong>' . htmlspecialchars($this->application->full_name) . '</strong>,</p>
                    <p>We are pleased to inform you that your application for the short course has been successfully validated and approved by the Director!</p>
                    
                    <div style="background-color: #f7fafc; border: 1px solid #edf2f7; padding: 20px; border-radius: 6px; margin: 20px 0;">
                        <h3 style="margin-top: 0; color: #0a1f44; font-size: 16px; border-bottom: 1px solid #edf2f7; padding-bottom: 10px;">Enrollment Details</h3>
                        <table style="width: 100%; font-size: 14px; border-collapse: collapse;">
                            <tr>
                                <td style="color: #718096; padding: 6px 0; width: 120px; font-weight: bold;">Course:</td>
                                <td style="color: #2d3748; padding: 6px 0;">' . htmlspecialchars($this->courseName) . '</td>
                            </tr>
                            <tr>
                                <td style="color: #718096; padding: 6px 0; font-weight: bold;">Intake Batch:</td>
                                <td style="color: #2d3748; padding: 6px 0;">' . htmlspecialchars($this->application->intake->name) . '</td>
                            </tr>
                            <tr>
                                <td style="color: #718096; padding: 6px 0; font-weight: bold;">Start Date:</td>
                                <td style="color: #2d3748; padding: 6px 0;">' . date('M d, Y', strtotime($this->application->intake->start_date)) . '</td>
                            </tr>
                        </table>
                    </div>

                    <div style="background-color: #fffaf0; border: 1px solid #feebc8; padding: 20px; border-radius: 6px; margin: 20px 0;">
                        <h3 style="margin-top: 0; color: #c05621; font-size: 16px; border-bottom: 1px solid #feebc8; padding-bottom: 10px;">Portal Login Information</h3>
                        <p style="margin: 5px 0;">You can now log in to the Student Portal to track your course materials, active class schedule, and view your verified receipt.</p>
                        <p style="margin: 15px 0 5px 0; font-size: 14px;"><strong>Portal Link:</strong> <a href="http://127.0.0.1:8000/login" style="color: #0a1f44; font-weight: bold; text-decoration: none;">http://127.0.0.1:8000/login</a></p>
                        <p style="margin: 5px 0;"><strong>Username / Email:</strong> ' . htmlspecialchars($this->application->email) . '</p>
                        <p style="margin: 5px 0;"><strong>Temporary Password:</strong> <code style="background-color: #fff; padding: 3px 8px; border: 1px dashed #feebc8; font-weight: bold; color: #2d3748; border-radius: 4px;">' . htmlspecialchars($this->password) . '</code></p>
                        <p style="margin: 15px 0 0 0; font-size: 11px; color: #dd6b20;">* For security, please make sure to change your password immediately upon your first login.</p>
                    </div>

                    <p>If you have any questions or require assistance, please contact the MSULI Helpdesk at info@languageinstitute.msu.ac.zw or telephone +263 54 231583.</p>
                    
                    <p style="margin-top: 30px; border-top: 1px solid #edf2f7; pt: 15px;">Warm Regards,<br/><strong>MSULI Executive & Training Management Team</strong></p>
                </div>
                <div style="background-color: #edf2f7; padding: 15px; text-align: center; font-size: 11px; color: #a0aec0; border-top: 1px solid #e2e8f0;">
                    This is an automated enrollment notification. Please do not reply directly to this email.
                </div>
            </div>
        </body>
        </html>';
    }
}
