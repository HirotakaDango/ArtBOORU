<?php
  session_start();
  if (!isset($_SESSION['username'])) {
    header("Location: session.php");
    exit;
  }
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Settings</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" integrity="sha384-GLhlTQ8iRABdZLl6O3oVMWSktQOp6b7In1Zl3/Jr59b6EGGoI1aFkw7cmDA6j6gD" crossorigin="anonymous">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <?php include('bootstrapcss.php'); ?>
</head>
<body>
    <?php include('header.php'); ?>
  <!-- Page Content -->
  <h4 class="text-secondary fw-bold text-center mt-4 mb-4"><i class="bi bi-gear-fill"></i> Settings</h1>
  <div class="list-group w-auto ms-2 me-2">
    <a href="yourname.php" class="list-group-item list-group-item-action d-flex gap-3 py-3" aria-current="true">
      <i class="bi bi-person-circle" style="font-size: 35px; color: gray;"></i>
      <div class="d-flex gap-2 w-100 justify-content-between">
        <div>
          <h6 class="mb-0">Change Your Name</h6>
          <p class="mb-0 opacity-75">Change how people see your name.</p>
        </div>
      </div>
    </a>
    <a href="setpass.php" class="list-group-item list-group-item-action d-flex gap-3 py-3" aria-current="true">
      <i class="bi bi-key-fill" style="font-size: 35px; color: gray;"></i>
      <div class="d-flex gap-2 w-100 justify-content-between">
        <div>
          <h6 class="mb-0">Change Your Password</h6>
          <p class="mb-0 opacity-75">Change your password for security.</p>
        </div>
      </div>
    </a>
  </div> 
  
  <a href="profile.php" class="btn btn-danger ms-2 mt-2 fw-bold" role="button">back to profile</a>

    <?php include('bootstrapjs.php'); ?>
</body>
</html>
