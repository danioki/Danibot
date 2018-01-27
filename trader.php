<?php
use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
require('vendor/autoload.php');
include("includes/tools.inc.php");
include("includes/cryptoexchange.class.php");
include("includes/bittrex_api.class.php");
include_once 'includes/config.php';


// Initialize the session
session_start();
 
// If session variable is not set it will redirect to login page
if(!isset($_SESSION['username']) || empty($_SESSION['username'])){
  header("location: login.php");
  exit;
}
$username = $_SESSION['username'];
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
?>

<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>DaniBot V0.01</title>

    <!-- Bootstrap core CSS -->
    <link href="/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.10.16/css/dataTables.bootstrap4.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.0.0/css/bootstrap.css" rel="stylesheet">


    <!-- Custom styles for this template -->
    <link href="/css/dashboard.css" rel="stylesheet">
    </head>

  <body>
    <nav class="navbar navbar-dark sticky-top bg-dark flex-md-nowrap p-0">
      <a class="navbar-brand col-sm-3 col-md-2 mr-0" href="#">DaniBot v0.01 Alpha</a>
      <ul class="navbar-nav px-3">
      </ul>
    </nav>

    <div class="container-fluid">
      <div class="row">
        <nav class="col-md-2 d-none d-md-block bg-light sidebar">
          <div class="sidebar-sticky">
            <ul class="nav flex-column">
              <li class="nav-item">
                <a class="nav-link " href="/#">
                  Indicadores
				  </a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="welcome.php">
                  wallet
                </a>
              </li>
			  <li class="nav-item">
                <a class="nav-link active" href="#">
                  Trader <span class="sr-only">(current)</span>
                </a>
              </li>
			  <li class="nav-item">
                  <a class="nav-link">
                   <?php
				  if(isset($_SESSION['username']) || !empty($_SESSION['username'])){echo "<small>Logueado como ". $username. "</small>"; }?> <span class="sr-only">(current)</span>
                  </a>
                </li>
				<li class="nav-item">
                  <a class="nav-link" href="">
				  <?php
				  if(isset($_SESSION['username']) || !empty($_SESSION['username'])){echo '<p><a href="logout.php" class="btn btn-danger" style="margin: 10px">LogOut</a></p>';} ?>
                  </a>
                </li>
              </li>
            </ul>
          </div>
        </nav>
        <main role="main" class="col-md-9 ml-sm-auto col-lg-10 pt-3 px-4">
			<div class="col-sm">
				<div class="container">
				  <table id="my-table" class="display responsive table-striped table" cellspacing="0" width="100%">
					<thead>
					  <tr>
						<th>Currency</th>
						<th>Volume</th>
						<th>Volume Change [%]</th>
						<th>Value Change [%]</th>
						<th>Value Change 10m[%]</th>
						<th>Value Change 30m[%]</th>
					  </tr>
					</thead>
				  </table>
				</div>
			</div>
			<br></br>
			<div class="row">
				<div class="col-sm-6">
				<div class="card">
					<div class="card-header">
						Comprar
					  </div>
				  <div class="card-body">
					<form>
					  <div class="form-group">
						<label for="inputToken">Token</label>
						<input type="text" class="form-control" id="tokenSearchBuy" aria-describedby="tokenhelp" placeholder="Ingresar Token">
						<div id="tokenSearchBuy-sugg"></div>
						<small id="tokenhelp" class="form-text text-muted">Buscar Crypto moneda</small>
					  </div>
					</form>
				  </div>
				</div>
				</div>
				<div class="col-sm-6">
				<div class="card">
					<div class="card-header">
						Vender
					  </div>
				  <div class="card-body">
					<form>
					  <div class="form-group">
						<label for="inputToken">Token</label>
						<input type="text" class="form-control" id="tokenSearchSell" aria-describedby="tokenhelp" placeholder="Ingresar Token">
						<div id="tokenSearchSell-sugg"></div>
						<small id="tokenhelp" class="form-text text-muted">Buscar Crypto moneda</small>
					  </div>
					</form>
				  </div>
				</div>
				</div>
			</div>
					

        </main>
      </div>
    </div>

    <!-- Bootstrap core JavaScript
    ================================================== -->
    <!-- Placed at the end of the document so the pages load faster -->
    <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
<!-- <script>window.jQuery || document.write('<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.13.0/popper.min.js"><\/script>')</script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.13.0/popper.min.js"></script> -->
    <script src="dist/js/bootstrap.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.16/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.16/js/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.2.1/js/dataTables.responsive.min.js"></script>


    <!-- Graphs -->
    
  </body>
  <script type="text/javascript">
    $(document).ready(function() {
        var table = $('#my-table').dataTable({
          "order": [[ 3, "asc" ]],
          "bresponsive": true,
          "columnDefs": [
          { responsivePriority: 1, targets: 0 },
          { responsivePriority: 2, targets: 3 },
          { responsivePriority: 3, targets: 4 }
          ],
          "iDisplayLength": 5,
          "aLengthMenu": [[5, 10, 15, -1], [5, 10, 20, "All"]],
          "bpaging": false,
          "bProcessing": true,
          "sAjaxSource": "includes/diff_per2.php",
          "aoColumns": [
                { mData: 'curr',
					"fnCreatedCell": function (nTd, sData, oData, iRow, iCol) {
						$(nTd).html("<a href='https://bittrex.com/Market/Index?MarketName=BTC-"+oData.curr+"' target='_blank' >"+oData.curr+"</a>");
					}
				} ,
                { mData: 'vol' },
				{ mData: 'vol_change' },
				{ mData: 'change_',
					"fnCreatedCell": function (nTd, sData, oData, iRow, iCol) {
							if ( parseFloat(oData.change_) < -2 ) {
								$(nTd).css('color', '#9ACD32 ')
							}
						}
				},
				{ mData: 'change_10',
					"fnCreatedCell": function (nTd, sData, oData, iRow, iCol) {
							if ( parseFloat(oData.change_10) > 2 ) {
								$(nTd).css('color', '#9ACD32 ')
							}
							if ( parseFloat(oData.change_10) < 0.5 ) {
								$(nTd).css('color', 'red ')
							}
						}
						
				},
				{ mData: 'change_30',
					"fnCreatedCell": function (nTd, sData, oData, iRow, iCol) {
							if ( parseFloat(oData.change_30) > 2 || parseFloat(oData.change_30) < 8) {
								$(nTd).css('color', '#9ACD32 ')
							}
							if ( parseFloat(oData.change_30) < 2 || parseFloat(oData.change_30) > 8) {
								$(nTd).css('color', 'red ')
							}
						}
				}
              ]
        });  
        setInterval( function () {
          table.api().ajax.reload( null, false ); // user paging is not reset on reload
      }, 10000 );
    });
  
  </script>
  <script>
		$(document).ready(function(){
			$("#tokenSearchBuy").keyup(function(){
				$.ajax({
				type: "POST",
				url: "includes/tokenSearchBuy.php",
				data:'keyword='+$(this).val(),
				beforeSend: function(){
					$("#tokenSearchBuy").css("background","#FFF url(LoaderIcon.gif) no-repeat 165px");
				},
				success: function(data){
					$("#tokenSearchBuy-sugg").show();
					$("#tokenSearchBuy-sugg").html(data);
					$("#tokenSearchBuy").css("background","#FFF");
				}
				});
			});
		});
		function selectTokenBuy(val) {
		$("#tokenSearchBuy").val(val);
		$("#tokenSearchBuy-sugg").hide();
		}
	</script>
      <script>
		$(document).ready(function(){
			$("#tokenSearchSell").keyup(function(){
				$.ajax({
				type: "POST",
				url: "includes/tokenSearchSell.php",
				data:'keyword='+$(this).val(),
				beforeSend: function(){
					$("#tokenSearchSell").css("background","#FFF url(LoaderIcon.gif) no-repeat 165px");
				},
				success: function(data){
					$("#tokenSearchSell-sugg").show();
					$("#tokenSearchSell-sugg").html(data);
					$("#tokenSearchSell").css("background","#FFF");
				}
				});
			});
		});
		function selectTokenSell(val) {
		$("#tokenSearchSell").val(val);
		$("#tokenSearchSell-sugg").hide();
		}
	</script>
</html>
