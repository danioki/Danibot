    <?php
    use Defuse\Crypto\Crypto;
    use Defuse\Crypto\Key;
    require('vendor/autoload.php');
    include("tools.inc.php");
    include("cryptoexchange.class.php");
    include("bittrex_api.class.php");
    include_once 'config.php';

    $user_key_encoded = $_SESSION['uke'];
    $user_key = Key::loadFromAsciiSafeString($user_key_encoded);

	$conn = new mysqli($servername_db, $username_db, $password_db, $dbname_db);
     if ($conn->connect_error) {
		die("Connection failed: " . $conn->connect_error);
    }
    $username = $_SESSION["username"];
    
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

    echo "<div class='container'>";
    echo "<h2>Portafolio</h2>";

    echo "<form method='GET'>";
	echo "<div class='form-group'>";
	echo "<div class='row'>";
	echo "<div class='col-md-auto'>";
    echo "Actual BTC:  <input type='text' name='usd' value='" . number_format($btcUsdtRate, 0) . "'readonly class='form-control'> ";
    echo "</div></div></div></form>";
    echo "</div>";

    $portfolioOBJ = $exchange->getBalances();
    if(!empty($portfolioOBJ)) {
        if($portfolioOBJ["success"] === true) {
            $portoflio  = $portfolioOBJ["result"];

            echo '<table class="table">';
            echo "<thead>";
            echo "<tr>";
            echo "<th>Currency</th>";
            echo "<th>Units</th>";
            echo "<th>Rate</th>";
            echo "<th>Value</th>";
            echo "<th>Cost / Proceeds</th>";
            echo "<th>Profit on sell</th>";
            echo "<th>Breakeven rate</th>";
            echo "<th>Breakeven profit %</th>";
            echo "</tr>";
            echo "</thead>";

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

                $color  = $profitSell>0 ? "background-color:rgba(48, 151, 51, 0.79);color:white;" : "";
                echo "<tbody>";
                echo "<tr style='" . $color   . "'>";

                // Currency
                echo "<td>";
                if($profitSell > 0) echo "<strong>";
                echo $item["Currency"];
                if($profitSell > 0) echo "</strong>";
                echo "</td>";

                // Units
                echo "<td>" . $item["Balance"] . "</td>";

                // rate
                echo "<td>" . $last ."</td>";

                // Value
                echo "<td>";
                echo $lastEstSellFormatted . " BTC";
                echo " / ";
                echo round(number_format($lastEstSellFormatted*$btcUsdtRate, 8, '.', ''),2) . " USD";
                echo "</td>";

                // Costs / proceeds
                echo "<td>";
                echo $BtcBalanceFormatted;
                echo "</td>";

                // Profit on sell
                echo "<td>";
                echo $profitSell . " BTC";
                echo " / ";
                echo round(number_format($profitSell*$btcUsdtRate, 8, '.', ''),2) . " USD";
                echo "</td>";

                if($profitSell > $higestProfitCellCurrencyValue) {
                $higestProfitCellCurrency = $item["Currency"];
                $higestProfitCellCurrencyValue  = $profitSell;
                }


                if($profitSell < $lowestProfitCellCurrencyValue) {
                $lowestProfitCellCurrency = $item["Currency"];
                $lowestProfitCellCurrencyValue  = $profitSell;
                }

                // Breakeven rate
                echo "<td>" . $breakEvenRate1 . "</td>";

                // 	Breakeven profit %
                echo "<td>";
                if($breakEvenRate1 > 0) {
                $profitFromBreakEven  = (($last - $breakEvenRate1) / $breakEvenRate1) * 100;
                echo round($profitFromBreakEven,2) . "%";
                } else {
                echo "0%";
                }
                echo "</td>";

                echo "</tr>";

                //die();
            }

            }
            echo "</tbody>";
            echo "</table>";

            echo "<br>";
			echo "<div class='container'>";
			echo "<form class='form-inline'>";
			echo "<div class='form-group'>";
			echo "<div class='row'>";
			echo "<div class='col-md-auto'>";
            echo "Valor Total Estimado: <input type='text' value='$totalBtcBalanceFormatted 'readonly class='form-control'></strong> BTC / <input type='text' value='".round(number_format($totalBtcBalanceFormatted*$btcUsdtRate, 8, '.', ''),2)." 'readonly class='form-control'> USD<br>";
			echo "</div></div></div></form></div>";
		}
    }
    echo "</div>";
    ?>