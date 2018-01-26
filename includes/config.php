<?php
/* Database credentials. Assuming you are running MySQL
server with default setting (user 'root' with no password) */
define('DB_SERVER', 'mysql.danidb.com');
define('DB_USERNAME', 'danbec24');
define('DB_PASSWORD', 'Starwars0');
define('DB_NAME', 'cryptousers_db');

$servername_db = 'mysql.danidb.com';
$username_db = "danbec24";
$password_db = "Starwars0";
$dbname_db = "cryptousers_db";
$link = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
 
// Check connection
if($link === false){
    die("ERROR: Could not connect. " . mysqli_connect_error());
}
?>