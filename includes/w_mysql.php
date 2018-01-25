 <?php

include("tools.inc.php");
include("cryptoexchange.class.php");
include("bittrex_api.class.php");

include("config.inc.php");
include_once 'config.php';

$exchangeName = "bittrex";

if(!isSet($config) || !isSet($config[$exchangeName])) die("no config for ". $exchangeName ." found!");
if(!isSet($config[$exchangeName]["apiKey"])) die("please configure the apiKey");
if(!isSet($config[$exchangeName]["apiSecret"])) die("please configure the apiSecret");

$exchange  = new BittrexApi($config[$exchangeName]["apiKey"] , $config[$exchangeName]["apiSecret"] );

$marketsOBJ = $exchange->getMarkets();

$marketsumOBJ = $exchange->getMarketSummaries();
if(!empty($marketsOBJ)) {
	if($marketsOBJ["success"] === true) {
		// Create connection
		$conn = new mysqli($servername_db, $username_db, $password_db, $dbname_db);
		// Check connection
		if ($conn->connect_error) {
			die("Connection failed: " . $conn->connect_error);
		} 
		$sql = "SET time_zone = '-3:00'";
		$conn->query($sql);
		$markets  = $marketsOBJ["result"];
		$marketssum  = $marketsumOBJ["result"];

		foreach($markets as $item) {
			if ($item['BaseCurrency'] ==="BTC"){
				$curr = $item['MarketCurrency'];
				$tickerOBJ = $exchange->getTicker(array("_market" => "BTC" , "_currency" => $item['MarketCurrency']));
				foreach($marketssum as $ms){
					if ($ms["MarketName"] === "BTC-".$curr){
						$vol = $ms["Volume"];
					}
				}
				
				$sql = "CREATE TABLE $curr (
					id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY, 
					last VARCHAR(30) NOT NULL,
					bid VARCHAR(30) NOT NULL,
					ask VARCHAR(30) NOT NULL,
					vol VARCHAR(30) NOT NULL,
					reg_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
					)";
				if ($conn->query($sql) === TRUE) {
						$last  = number_format($tickerOBJ["result"]["Last"], 8, '.', '');
						$bid  = number_format($tickerOBJ["result"]["Bid"], 8, '.', '');
						$ask  = number_format($tickerOBJ["result"]["Ask"], 8, '.', '');
						$sql = "INSERT INTO $curr (last, bid, ask, vol) VALUES ($last, $bid, $ask, $vol)";
						$conn->query($sql);
				} else {
						$last  = number_format($tickerOBJ["result"]["Last"], 8, '.', '');
						$bid  = number_format($tickerOBJ["result"]["Bid"], 8, '.', '');
						$ask  = number_format($tickerOBJ["result"]["Ask"], 8, '.', '');
						$sql = "INSERT INTO $curr (last, bid, ask, vol) VALUES ($last, $bid, $ask, $vol)";
						$conn->query($sql);
					}
			}
		}
		$conn->close();
	}
}
?>