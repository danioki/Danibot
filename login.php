<?php
use Defuse\Crypto\KeyProtectedByPassword;
require('vendor/autoload.php');
// Include config file
require_once 'includes/config.php';
 
$conn = new mysqli($servername_db, $username_db, $password_db, $dbname_db);
     if ($conn->connect_error) {
		die("Connection failed: " . $conn->connect_error);
    }

// Define variables and initialize with empty values
$username = $password = "";
$username_err = $password_err = "";
 
// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
 
    // Check if username is empty
    if(empty(trim($_POST["username"]))){
        $username_err = 'Please enter username.';
    } else{
        $username = trim($_POST["username"]);
    }
    
    // Check if password is empty
    if(empty(trim($_POST['password']))){
        $password_err = 'Please enter your password.';
    } else{
        $password = trim($_POST['password']);
    }
    
    // Validate credentials
    if(empty($username_err) && empty($password_err)){
        // Prepare a select statement
        $sql = "SELECT username, password FROM users WHERE username = ?";
        
        if($stmt = mysqli_prepare($link, $sql)){
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "s", $param_username);
            
            // Set parameters
            $param_username = $username;
            
            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)){
                // Store result
                mysqli_stmt_store_result($stmt);
                
                // Check if username exists, if yes then verify password
                if(mysqli_stmt_num_rows($stmt) == 1){                    
                    // Bind result variables
                    mysqli_stmt_bind_result($stmt, $username, $hashed_password);
                    if(mysqli_stmt_fetch($stmt)){
                        if(password_verify($password, $hashed_password)){
                            /* Password is correct, so start a new session and
                            save the username to the session */
                            session_start();
                            $_SESSION['username'] = $username;   
                            
                            $sql = "SELECT prot_key_enc FROM users WHERE BINARY username = '$username'";
                            $result = $conn->query($sql);

                            while($row = $result->fetch_array(MYSQLI_ASSOC)){
                            $data[] = $row;
                            }
                            $data = array_shift($data);
        
                             $protected_key_encoded = $data['prot_key_enc'];
                            // echo $protected_key_encoded;
                            //var_dump($data);

                            $protected_key = KeyProtectedByPassword::loadFromAsciiSafeString($protected_key_encoded);
                            $user_key = $protected_key->unlockKey($password);
                            $user_key_encoded = $user_key->saveToAsciiSafeString();

                            $_SESSION['uke'] = $user_key_encoded;     
                            $_SESSION['start'] = time(); // Taking now logged in time.
                            // Ending a session in 30 minutes from the starting time.
                            $_SESSION['expire'] = $_SESSION['start'] + (30 * 60);
                            header("location: welcome.php"); 

                        } else{
                            // Display an error message if password is not valid
                            $password_err = 'The password you entered was not valid.';
                        }
                    }
                } else{
                    // Display an error message if username doesn't exist
                    $username_err = 'No account found with that username.';
                }
            } else{
                echo "Oops! Something went wrong. Please try again later.";
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
                  <a class="nav-link" href="welcome.php">
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
						<h2>Login</h2>
						<p>Please fill in your credentials to login.</p>
						<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
							<div class="form-group <?php echo (!empty($username_err)) ? 'has-error' : ''; ?>">
								<label>Username:<sup>*</sup></label>
								<input type="text" name="username"class="form-control" value="<?php echo $username; ?>">
								<span class="help-block"><?php echo $username_err; ?></span>
							</div>    
							<div class="form-group <?php echo (!empty($password_err)) ? 'has-error' : ''; ?>">
								<label>Password:<sup>*</sup></label>
								<input type="password" name="password" class="form-control">
								<span class="help-block"><?php echo $password_err; ?></span>
							</div>
							<div class="form-group">
								<input type="submit" class="btn btn-primary" value="Submit">
							</div>
							<p>Don't have an account? <a href="register.php">Sign up now</a>.</p>
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