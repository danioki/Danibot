<?php
use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
require('../vendor/autoload.php');
include("tools.inc.php");
include("cryptoexchange.class.php");
include("bittrex_api.class.php");
include_once 'config.php';

session_start();
 
// If session variable is not set it will redirect to login page
if(!isset($_SESSION['username']) || empty($_SESSION['username'])){
  header("location: login.php");
  exit;
}
$username = $_SESSION["username"];
$user_key_encoded = $_SESSION['uke'];
$user_key = Key::loadFromAsciiSafeString($user_key_encoded);

$conn = new mysqli($servername_db, $username_db, $password_db, $dbname_db);
 if ($conn->connect_error) {
	die("Connection failed: " . $conn->connect_error);
}


$sql = "SELECT apikey, apisecret FROM users WHERE BINARY username = '$username'";
$result = $conn->query($sql);


while($row = $result->fetch_array(MYSQLI_ASSOC)){
$data[] = $row;
}
$data = array_shift($data);

$encrypted_apikey = $data['apikey'];
$encrypted_apisecret = $data['apisecret'];

try {
	$apikey = Crypto::decrypt($encrypted_apikey, $user_key);
	$apisecret = Crypto::decrypt($encrypted_apisecret, $user_key);
} catch (Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException $ex) {
}

if(!isset($config)) $config = array();
	  $config["bittrex"] = array(
		"apiKey"    => $apikey,
		"apiSecret" => $apisecret
	  );

$exchangeName = "bittrex";
if(!isSet($config) || !isSet($config[$exchangeName])) die("no config for ". $exchangeName ." found!");
if(!isSet($config[$exchangeName]["apiKey"])) die("please configure the apiKey");
if(!isSet($config[$exchangeName]["apiSecret"])) die("please configure the apiSecret");

$exchange  = new BittrexApi($config[$exchangeName]["apiKey"] , $config[$exchangeName]["apiSecret"] );

$totalBtcBalanceFormatted = 0;

$btcUsdtRate  = 0;
if(!isSet($_GET["usd"])) {
	$tickerOBJ = $exchange->getTicker(array("_market" => "USDT" , "_currency" => "BTC"));
	if(!empty($tickerOBJ)) {
	if($tickerOBJ["success"] === true) {
		$btcUsdtRate  = number_format($tickerOBJ["result"]["Last"], 8, '.', '');
	}
	}
} else {
	$btcUsdtRate  = $_GET["usd"];
}

$higestProfitCellCurrency = "";
$higestProfitCellCurrencyValue  = 0;

$lowestProfitCellCurrency = "";
$lowestProfitCellCurrencyValue  = 0;


$portfolioOBJ = $exchange->getBalances();
if(!empty($portfolioOBJ)) {
	if($portfolioOBJ["success"] === true) {
		$portoflio  = $portfolioOBJ["result"];

		foreach($portoflio as $item) {
			if($item["Balance"] > 0) {
				$item["Balance"]    = number_format($item["Balance"], 8, '.', '');
				$item["Available"]  = number_format($item["Available"], 8, '.', '');

				$currency = $item["Currency"];
				$market   = $exchange->getMarketPair("BTC" , $currency);

				$bid  = 0;
				$ask  = 0;
				$last = 1;
				$tickerOBJ = $exchange->getTicker(array("_market" => "BTC" , "_currency" => $currency));
				if($tickerOBJ) {
				if($tickerOBJ["success"] == true) {
					$bid  = number_format($tickerOBJ["result"]["Bid"], 8, '.', '');
					$ask  = number_format($tickerOBJ["result"]["Ask"], 8, '.', '');
					$last  = number_format($tickerOBJ["result"]["Last"], 8, '.', '');
				}
				}

				$btcValue = number_format($last * $item["Balance"], 8, '.', '');

				// --- BEGIN history
				$historyBalance = 0;
				$historyUnits   = 0;
				$totalCommision     = 0;

				$totalUnitsFilled = 0;
				$BtcBalance = 0;
				$BtcLoss = 0;
				$BtcGain = 0;

				$_market  = "BTC";
				$historyOBJ = $exchange->getOrderHistory(array("_market" => $_market , "_currency" => $currency));

				$_history = array();
				if($historyOBJ) {
				if($historyOBJ["success"] == true) {

					$__history  = array();
					foreach($historyOBJ["result"] as $history) {
					$timestamp  = $history["TimeStamp"];
					$timestamp  = str_replace("-" , "",$timestamp);
					$timestamp  = str_replace("T" , "",$timestamp);
					$timestamp  = str_replace(":" , "",$timestamp);
					$timestamp  = str_replace("." , "",$timestamp);
					$timestamp  = str_replace(" " , "",$timestamp);
					$__history[$timestamp] = $history;
					}
					$arr = bubble_sort($__history);
					$_history = null;
					$__history = array_reverse($arr);

					foreach($__history as $history) {
					//debug($history);

					$timestamp  = $history["TimeStamp"];
					$timestamp  = str_replace("-" , "",$timestamp);
					$timestamp  = str_replace("T" , "",$timestamp);
					$timestamp  = str_replace(":" , "",$timestamp);
					$timestamp  = str_replace("." , "",$timestamp);
					$timestamp  = str_replace(" " , "",$timestamp);

					$unitsFilled  = $history["Quantity"] - $history["QuantityRemaining"];
					$balance  = $history["PricePerUnit"] * $unitsFilled;

					$commision  = number_format($history["Commission"], 8, '.', '');
					$totalCommision += $commision;

					$orderSpend = 0;
					$orderGain  = 0;

					switch($history["OrderType"]) {
						case "LIMIT_BUY" : {
						$orderSpend = $balance + $commision;
						$BtcLoss += $orderSpend;
						$totalUnitsFilled += $unitsFilled;
						$BtcBalance -= $orderSpend;
						break;
						}
						case "LIMIT_SELL" : {
						$totalUnitsFilled -= $unitsFilled;
						$orderGain  = $balance - $commision;
						$BtcGain  += $orderGain;
						$BtcBalance += $orderGain;
						break;
						}
					}

					$history["_totalUnitsFilled"] = $totalUnitsFilled;
					$history["_unitsFilled"] = $unitsFilled;
					$history["_BtcSpend"] = $orderSpend;
					$history["_BtcGain"] = $orderGain;
					$history["_BtcBalance"] = number_format($BtcBalance, 8, '.', '');
					$history["_commision"]  = number_format($history["Commission"], 8, '.', '');
					$history["_timestamp"]  = str_replace("T" , " " , $history["TimeStamp"]);
					$history["PricePerUnit"]  = number_format($history["PricePerUnit"], 8, '.', '');
					$_history[$timestamp] = $history;

					}
				} else {
					$totalUnitsFilled = $item["Balance"];
				}
			}

			if($currency == "USDT") {
			$bidEstSellFormatted = number_format($totalUnitsFilled * $bid, 8, '.', '');
			$askEstSellFormatted = number_format($totalUnitsFilled * $ask, 8, '.', '');
			$lastEstSellFormatted = number_format($totalUnitsFilled / $btcUsdtRate , 8, '.', '');
			} else {
			$bidEstSellFormatted = number_format($totalUnitsFilled * $bid, 8, '.', '');
			$askEstSellFormatted = number_format($totalUnitsFilled * $ask, 8, '.', '');
			$lastEstSellFormatted = number_format($totalUnitsFilled * $last, 8, '.', '');
			}


			$BtcBalanceFormatted = number_format($BtcBalance, 8, '.', '');

			$profitSell = number_format($BtcBalanceFormatted + $lastEstSellFormatted, 8, '.', '');

			$breakEvenRate = 0;
			$breakEvenRate1 = 0;
			if($totalUnitsFilled > 0) {
			$breakEvenRate1  = number_format($BtcBalanceFormatted / $totalUnitsFilled, 8, '.' , '');
			}
			if($breakEvenRate1 < 0) {
			$breakEvenRate = $breakEvenRate1 * -1;
			} else {
			$breakEvenRate  = $breakEvenRate1;
			}

			$breakEvenRate1  = number_format($breakEvenRate, 8, '.' , '');
			// --- END history

			$totalBtcBalanceFormatted += $lastEstSellFormatted;


			if($profitSell > $higestProfitCellCurrencyValue) {
			$higestProfitCellCurrency = $item["Currency"];
			$higestProfitCellCurrencyValue  = $profitSell;
			}


			if($profitSell < $lowestProfitCellCurrencyValue) {
			$lowestProfitCellCurrency = $item["Currency"];
			$lowestProfitCellCurrencyValue  = $profitSell;
			}

			if($breakEvenRate1 > 0) {
			$profitFromBreakEven  = (($last - $breakEvenRate1) / $breakEvenRate1) * 100;
			}
			else{$profitFromBreakEven = 0;}

			$data1[] = ["currency" => $item["Currency"],
							"balance" => $item["Balance"],
							"last" => $last,
							"value" => $lastEstSellFormatted . ' BTC / '. round(number_format($lastEstSellFormatted*$btcUsdtRate, 8, '.', ''),2) . ' USD', 
							"cost" => $BtcBalanceFormatted,
							"profit" => $profitSell . 'BTC / '. round(number_format($profitSell*$btcUsdtRate, 8, '.', ''),2) . ' USD',
							"breakEvenRate" => $breakEvenRate1,
							"breakevenprofit" => round($profitFromBreakEven,2)];
			 
			}
		}
	}
}
$results = ["sEcho" => 1,
			"iTotalRecords" => count($data1),
			"iTotalDisplayRecords" => count($data1),
			"aaData" => $data1 ];

echo json_encode($results);

?>