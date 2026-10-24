<?php
// database.php

// Load configuration
$config_path = __DIR__ . '/config.php';
if (!file_exists($config_path)) {
    die("Configuration file not found: config.php");
}

$config = include $config_path;

// Check if db configuration exists
if (!isset($config['db'])) {
    die("Database configuration not found in config.php");
}

$db = $config['db'];

// Establish database connection
$conn = mysqli_connect($db['host'], $db['user'], $db['pass'], $db['dbname']);

if (!$conn) {
    die("DB Connect error: " . mysqli_connect_error());
}

// Set charset
if (isset($db['charset'])) {
    mysqli_set_charset($conn, $db['charset']);
}

// Set PHP timezone from config
if (isset($config['timezone'])) {
    date_default_timezone_set($config['timezone']);
} else {
    date_default_timezone_set('Africa/Lagos');
}

// Set MySQL timezone from config or default
if (isset($config['mysql_timezone_offset'])) {
    $mysql_tz = $config['mysql_timezone_offset'];
} else {
    $mysql_tz = '+01:00'; // Default for Africa/Lagos
}

mysqli_query($conn, "SET time_zone = '$mysql_tz'");
?>