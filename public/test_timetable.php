<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$t = new \App\Models\CourseTimetable();
$t->start_time = '09:00:00';
$t->end_time = '11:00:00';

echo "Start Time Attribute: " . $t->start_time . "\n";
echo "End Time Attribute: " . $t->end_time . "\n";
