<?php
header('Content-Type: text/plain');
echo "Starting compilation...\n";
exec('cd .. && npm run prod > public/build_log.txt 2>&1');
echo "Done!\n";
