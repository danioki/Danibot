<?php
require_once("config.php");

$conn = new mysqli($servername_db, $username_db, $password_db, "crypto_db");
$dbname = "crypto_db";
// Check connection
if ($conn->connect_error) {
	die("Connection failed: " . $conn->connect_error);
} 
if(!empty($_POST["keyword"])) {
	$key = $_POST['keyword']."%";
	$sql = "SELECT table_name FROM information_schema.tables where table_schema='$dbname' and table_name LIKE '$key' LIMIT 0,6";
	$buff = $conn->query($sql);
	while($row = $buff->fetch_array(MYSQLI_ASSOC)){
		$markets[] = $row;
		}

if(!empty($markets)) {
?>
<ul class="list-group" style="position: absolute; z-index: 999;">
<?php
foreach($markets as $market) {
?>
<li class="list-group-item list-group-item-action" onClick="selectTokenSell('<?php echo $market["table_name"]; ?>');"><?php echo $market["table_name"]; ?></li>
<?php } ?>
</ul>
<?php } } ?>