<?php
echo "Xdebug Status:\n";
echo "Loaded: " . (extension_loaded('xdebug') ? 'YES' : 'NO') . "\n";
echo "Version: " . phpversion('xdebug') . "\n";
echo "Mode: " . ini_get('xdebug.mode') . "\n";
echo "Client host: " . ini_get('xdebug.client_host') . "\n";
echo "Client port: " . ini_get('xdebug.client_port') . "\n";

// Попробуем записать в лог
error_log("Xdebug test from web request");

// Вызовем xdebug_break() для принудительной остановки
if (function_exists('xdebug_break')) {
    echo "\nCalling xdebug_break()...\n";
    xdebug_break();
}
