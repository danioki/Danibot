<?php
include_once 'config.php';


$conn = new mysqli($servername_db, $username_db, $password_db, "crypto_db");
// Check connection
if ($conn->connect_error) {
	die("Connection failed: " . $conn->connect_error);
} 
unset($data);
$sql = "SELECT table_name FROM information_schema.tables where table_schema='crypto_db'";
$buff = $conn->query($sql);
while($row = $buff->fetch_array(MYSQLI_ASSOC)){
	$markets[] = $row;
	}
foreach($markets as $market) {
	$curr = $market["table_name"];
	$sql = "SELECT 
	g1.id,
	FORMAT(g1.vol, 2) AS vol,
	FORMAT(100 * (g1.vol - g2.vol) / g1.vol, 2) AS vol_change,
	FORMAT(100 * (g1.last - g2.last) / g1.last, 2) AS change_,
	FORMAT(100 * (g1.last - g3.last) / g1.last, 2) AS change_10,
	FORMAT(100 * (g1.last - g4.last) / g1.last, 2) AS change_30,
	g1.bid AS bid,
	g1.ask AS ask,
	FORMAT(100 * (g1.ask - g1.bid) / g1.ask, 2) AS spread,
	'$curr' AS curr
	FROM
	$curr g1
		INNER JOIN $curr g2 ON g2.id = g1.id + 1
		INNER JOIN $curr g3 ON g3.id = g1.id + 3
		INNER JOIN $curr g4 ON g4.id = g1.id + 10
	WHERE
	1
	ORDER BY id DESC
	LIMIT
	1
	";

	$result = $conn->query($sql);
	while($row = $result->fetch_array(MYSQLI_ASSOC)){
		$data[] = $row;
		}
	}
$results = ["sEcho" => 1,
				"iTotalRecords" => count($data),
				"iTotalDisplayRecords" => count($data),
				"aaData" => $data ];

echo json_encode($results);
$conn->close(); 

?>
