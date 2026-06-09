<?php
header('Content-Type: text/plain');
$logPath = 'C:\\Users\\user\\.gemini\\antigravity-ide\\brain\\dfcd2f8f-3007-4e32-9809-4297da621b70\\.system_generated\\logs\\transcript.jsonl';
if (!file_exists($logPath)) {
    die("Log file not found.");
}

$handle = fopen($logPath, "r");
$lineNumber = 0;
while (($line = fgets($handle)) !== false) {
    $lineNumber++;
    if (strpos($line, '8.') !== false || strpos($line, '9.') !== false || strpos($line, '10.') !== false || strpos($line, '11.') !== false || strpos($line, '12.') !== false) {
        echo "Line $lineNumber matches:\n";
        // Print matching fragments around the match
        $matches = [];
        preg_match_all('/.{0,100}(?:8\.|9\.|10\.|11\.|12\.).{0,150}/u', $line, $matches);
        foreach ($matches[0] as $match) {
            echo "   ... " . trim($match) . " ...\n";
        }
    }
}
fclose($handle);
