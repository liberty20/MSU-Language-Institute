<?php
$logPath = 'C:\\Users\\user\\.gemini\\antigravity-ide\\brain\\dfcd2f8f-3007-4e32-9809-4297da621b70\\.system_generated\\logs\\transcript.jsonl';
if (!file_exists($logPath)) {
    die("Log file not found at: " . $logPath);
}

$handle = fopen($logPath, "r");
if ($handle) {
    $line = fgets($handle);
    fclose($handle);
    $data = json_decode($line, true);
    if (isset($data['content'])) {
        header('Content-Type: text/plain');
        echo $data['content'];
    } else {
        echo "No 'content' field in the first line.";
    }
} else {
    echo "Could not open log file.";
}
