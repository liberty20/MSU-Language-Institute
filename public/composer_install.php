<?php
header('Content-Type: text/plain');
echo "Starting composer install...\n";
exec('cd .. && composer install > public/composer_log.txt 2>&1');
echo "Done!\n";
