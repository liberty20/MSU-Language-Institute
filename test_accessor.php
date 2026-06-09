<?php
require 'vendor/autoload.php';
$t = new \App\Models\CourseTimetable();
$t->start_time = '09:00:00';
echo "Result: " . $t->start_time . "\n";
