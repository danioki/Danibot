<?php
// Include config file
require_once 'includes/config.php';
use Defuse\Crypto\KeyProtectedByPassword;
use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
require('vendor/autoload.php');

 
// Define variables and initialize with empty values
$username = $password = $confirm_password = $apikey = $apisecret = "";
$username_err = $password_err = $confirm_password_err = $apikey_err = $apisecret_err = "";
 
// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
 
    // Validate username
    if(empty(trim($_POST["username"]))){
        $username_err = "Please enter a username.";
    } else{
        // Prepare a select statement
        $sql = "SELECT id FROM users WHERE username = ?";
        
        if($stmt = mysqli_prepare($link, $sql)){
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "s", $param_username);
            
            // Set parameters
            $param_username = trim($_POST["username"]);
            
            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)){
                /* store result */
                mysqli_stmt_store_result($stmt);
                
                if(mysqli_stmt_num_rows($stmt) == 1){
                    $username_err = "This username is already taken.";
                } else{
                    $username = trim($_POST["username"]);
                }
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }
        }
         
        // Close statement
        mysqli_stmt_close($stmt);
    }
    
	// Validate apikey
    if(empty(trim($_POST['apikey']))){
        $apikey_err = "Please enter a apikey.";     
    } else{
        $apikey = trim($_POST['apikey']);
    }
		// Validate apisecret
    if(empty(trim($_POST['apisecret']))){
        $apisecret_err = "Please enter a apisecret.";     
    } else{
        $apisecret = trim($_POST['apisecret']);
    }
	
    // Validate password
    if(empty(trim($_POST['password']))){
        $password_err = "Please enter a password.";     
    } elseif(strlen(trim($_POST['password'])) < 6){
        $password_err = "Password must have atleast 6 characters.";
    } else{
        $password = trim($_POST['password']);
		
    }
    
    // Validate confirm password
    if(empty(trim($_POST["confirm_password"]))){
        $confirm_password_err = 'Please confirm password.';     
    } else{
        $confirm_password = trim($_POST['confirm_password']);
        if($password != $confirm_password){
            $confirm_password_err = 'Password did not match.';
        }
    }
    $protected_key = KeyProtectedByPassword::createRandomPasswordProtectedKey($password);
    $protected_key_encoded = $protected_key->saveToAsciiSafeString();
    
    $user_key = $protected_key->unlockKey($password);
    $user_key_encoded = $user_key->saveToAsciiSafeString();
    $user_key = Key::loadFromAsciiSafeString($user_key_encoded);

    $encrypted_apikey = Crypto::encrypt($apikey, $user_key);
    $encrypted_apisecret = Crypto::encrypt($apisecret, $user_key);

    // Check input errors before inserting in database
    if(empty($username_err) && empty($password_err) && empty($confirm_password_err) && empty($apikey_err) && empty($apisecret_err)){
        
        // Prepare an insert statement
        $sql = "INSERT INTO users (username, password, apikey, apisecret, prot_key_enc) VALUES (?, ?, ?, ?, ?)";
         
        if($stmt = mysqli_prepare($link, $sql)){
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "sssss", $param_username, $param_password, $encrypted_apikey, $encrypted_apisecret, $protected_key_encoded);
            
            // Set parameters
            $param_username = $username;
            $param_password = password_hash($password, PASSWORD_DEFAULT); // Creates a password hash
            
            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)){
                // Redirect to login page
                header("location: login.php");
            } else{
                echo "Something went wrong. Please try again later.";
            }
        }
         
        // Close statement
        mysqli_stmt_close($stmt);
    }
    
    // Close connection
    mysqli_close($link);
}
?>
 
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>DaniBot V0.00001</title>

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
                  <a class="nav-link active" href="/#">
                    <span data-feather="home"></span>
                    Indicadores <span class="sr-only">(current)</span>
                  </a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" href="wallet.php>
                    <span data-feather="file"></span>
                    wallet
                  </a>
                </li>
                </li>
              </ul>
            </div>
          </nav>
		  
			<main role="main" class="col-md-9 ml-sm-auto col-lg-10 pt-3 px-4">
				<div class="col-sm">
					<div class="container">
						<h2>Sign Up</h2>
						<p>Please fill this form to create an account.</p>
						<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
							<div class="form-group <?php echo (!empty($username_err)) ? 'has-error' : ''; ?>">
								<label>Username:<sup>*</sup></label>
								<input type="text" name="username"class="form-control" value="<?php echo $username; ?>">
								<span class="help-block"><?php echo $username_err; ?></span>
							</div>    
							 <div class="form-group" <?php echo (!empty($apikey_err)) ? 'has-error' : ''; ?>>
								<label>apikey:<sup>*</sup></label>
								<input type="text" name="apikey"class="form-control" value="<?php echo $apikey; ?>">
							</div> 
							 <div class="form-group" <?php echo (!empty($apisecret_err)) ? 'has-error' : ''; ?>>
								<label>apisecret:<sup>*</sup></label>
								<input type="text" name="apisecret"class="form-control" value="<?php echo $apisecret; ?>">
							</div> 
							<div class="form-group <?php echo (!empty($password_err)) ? 'has-error' : ''; ?>">
								<label>Password:<sup>*</sup></label>
								<input type="password" name="password" class="form-control" value="<?php echo $password; ?>">
								<span class="help-block"><?php echo $password_err; ?></span>
							</div>
							<div class="form-group <?php echo (!empty($confirm_password_err)) ? 'has-error' : ''; ?>">
								<label>Confirm Password:<sup>*</sup></label>
								<input type="password" name="confirm_password" class="form-control" value="<?php echo $confirm_password; ?>">
								<span class="help-block"><?php echo $confirm_password_err; ?></span>
							</div>
							<div class="form-group">
								<input type="submit" class="btn btn-primary" value="Submit">
								<input type="reset" class="btn btn-default" value="Reset">
							</div>
							<p>Already have an account? <a href="login.php">Login here</a>.</p>
						</form>
					</div>
				</div>
			</main>
		</div>
	</div>
      <!-- Bootstrap core JavaScript
    ================================================== -->
    <!-- Placed at the end of the document so the pages load faster -->
    <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
    <script>window.jQuery || document.write('<script src="assets/js/vendor/jquery-slim.min.js"><\/script>')</script>
    <script src="assets/js/vendor/popper.min.js"></script>
    <script src="dist/js/bootstrap.min.js"></script>
    <script src="https://code.jquery.com/jquery-1.12.4.js"></script>
    <script src="https://cdn.datatables.net/1.10.16/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.16/js/dataTables.bootstrap4.min.js"></script>

    
    <!-- Icons -->
    <script src="https://unpkg.com/feather-icons/dist/feather.min.js"></script>
    <script>
      feather.replace()
    </script>

    <!-- Graphs -->
    
  </body>
</html>