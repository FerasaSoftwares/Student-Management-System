<?php
// Central database settings. Picks local (XAMPP) or live (InfinityFree) automatically.

$__host = isset($_SERVER['HTTP_HOST']) ? strtolower($_SERVER['HTTP_HOST']) : 'localhost';
$IS_LOCAL = (strpos($__host, 'localhost') === 0 || strpos($__host, '127.0.0.1') === 0);

if ($IS_LOCAL) {
    // XAMPP
    $HOST          = 'localhost';
    $USER          = 'root';
    $PASSWORD      = '';
    $DATABASE_NAME = 'sms';
} else {
    // InfinityFree: copy these from Control Panel -> MySQL Databases
    $HOST          = 'sql111.infinityfree.com';   // "MySQL Hostname"
    $USER          = 'if0_43191973';              // "MySQL Username"
    $PASSWORD      = 'JUKon54t51fjg7';      // your hosting account password
    $DATABASE_NAME = 'if0_43191973_sms';          // full database name
}
