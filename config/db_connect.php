<?php
// Database connection configuration
$host = 'localhost'; // Database host
$port = 5432; // Database port
$db_name = 'kimochi_shop'; // Database name
$user = 'postgres'; // Database username
$password = '3571khai_IDMnU3MQ=='; // Database password

$connection_string = "host=$host port=$port dbname=$db_name user=$user password=$password";
$dbconnection = pg_connect($connection_string);

if (!$dbconnection) {
    die("Kết nối đã thất bại: " . pg_last_error());
}
?>