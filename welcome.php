<?php
// Initialize the session
session_start();
 
// If session variable is not set it will redirect to login page
if(!isset($_SESSION['username']) || empty($_SESSION['username'])){
  header("location: login.php");
  exit;
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
                  <a class="nav-link" href="/#">
                    <span data-feather="home"></span>
                    Indicadores
                  </a>
                </li>
                <li class="nav-item">
                  <a class="nav-link active" href="welcome.php">
                    <span data-feather="file"></span>
                    wallet <span class="sr-only">(current)</span>
                  </a>
                </li>
                </li>
              </ul>
            </div>
          </nav>
		  
			<main role="main" class="col-md-9 ml-sm-auto col-lg-10 pt-3 px-4">
				<div class="col-sm">
					<div class="container" id="Status">
					
          <?php 
          $now = time(); // Checking the time now when home page starts.

          if ($now > $_SESSION['expire']) {
              // Unset all of the session variables
              $_SESSION = array();
              
              // Destroy the session.
              session_destroy();
              include 'login.php';
          } else {
      
              include 'includes/wallet.php';

            }
          ?>
					<br></br>
					<p><a href="logout.php" class="btn btn-danger">LogOut</a></p>
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