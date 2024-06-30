<?php
session_start();

if (!isset($_SESSION['email'])) {
  header('Location: session.php');
  exit();
}

// Establish database connection
try {
  $db = new PDO('sqlite:database.sqlite');
  $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
  die("Database connection failed: " . $e->getMessage());
}

// Get user email from session
$email = $_SESSION['email'];

// Initialize success message and errors array
$success_message = "";
$errors = [];

// Handle form submissions based on the page parameter
$page = $_GET['page'] ?? 'password'; // Default page is 'password'

if ($page === 'password' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  // Handle password change
  $current_password = htmlspecialchars($_POST['current_password']);
  $new_password = htmlspecialchars($_POST['new_password']);
  $confirm_password = htmlspecialchars($_POST['confirm_password']);

  // Validate input data
  if (empty($current_password)) {
    $errors[] = "Please enter your current password.";
  }
  if (empty($new_password)) {
    $errors[] = "Please enter a new password.";
  }
  if ($new_password !== $confirm_password) {
    $errors[] = "New password and confirm password do not match.";
  }

  // Check if current password is correct
  $stmt = $db->prepare("SELECT * FROM users WHERE email = :email AND password = :password");
  $stmt->bindParam(":email", $email);
  $stmt->bindParam(":password", $current_password);
  $stmt->execute();
  $user = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$user) {
    $errors[] = "Current password is incorrect.";
  }

  // If no errors, update password in the database
  if (empty($errors)) {
    $stmt = $db->prepare("UPDATE users SET password = :new_password WHERE email = :email");
    $stmt->bindParam(":new_password", $new_password);
    $stmt->bindParam(":email", $email);
    $stmt->execute();
    $success_message = "Password updated successfully.";
  }
} elseif ($page === 'artist' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  // Handle artist name change
  $artist = htmlspecialchars($_POST['artist']);

  // Validate input data
  if (empty($artist)) {
    $errors[] = "Please enter the artist name.";
  }

  // If no errors, update artist name in the database
  if (empty($errors)) {
    $stmt = $db->prepare("UPDATE users SET artist = :artist WHERE email = :email");
    $stmt->bindParam(":artist", $artist);
    $stmt->bindParam(":email", $email);
    $stmt->execute();
    $success_message = "Artist name updated successfully.";
  }
}

// Fetch the artist name to display in the form
$stmt = $db->prepare("SELECT artist, id AS uid FROM users WHERE email = :email");
$stmt->bindParam(":email", $email);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$artist = $user['artist'];
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
  <head>
    <title>Settings</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php include('bootstrap.php'); ?>
  </head>
  <body>
    <?php include('header.php'); ?>
    <div class="container" style="max-width: 400px;">
      <?php if (!empty($success_message)): ?>
        <div class="alert alert-success"><?php echo $success_message; ?></div>
      <?php endif; ?>
      <?php if (!empty($errors)): ?>
        <?php foreach ($errors as $error): ?>
          <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endforeach; ?>
      <?php endif; ?>

      <div class="btn-group w-100 gap-2 mb-2">
        <a href="settings.php?page=password" class="btn btn-secondary fw-bold w-50 rounded">Change Password</a>
        <a href="settings.php?page=artist" class="btn btn-secondary fw-bold w-50 rounded">Change Name</a>
      </div>

      <?php if ($page === 'password'): ?>
        <!-- Password Change Form -->
        <form method="post">
          <div class="form-floating mb-2">
            <input type="password" class="form-control rounded border-3 focus-ring focus-ring-dark" name="current_password" placeholder="Enter current password" maxlength="40" pattern="^[a-zA-Z0-9_@.-]+$">
            <label for="floatingPassword" class="fw-bold"><small>Enter current password</small></label>
          </div>
          <div class="form-floating mb-2">
            <input type="password" class="form-control rounded border-3 focus-ring focus-ring-dark" name="new_password" placeholder="Type new password" maxlength="40" pattern="^[a-zA-Z0-9_@.-]+$">
            <label for="floatingPassword" class="fw-bold"><small>Type new password</small></label>
          </div>
          <div class="form-floating mb-2">
            <input type="password" class="form-control rounded border-3 focus-ring focus-ring-dark" name="confirm_password" placeholder="Confirm new password" maxlength="40" pattern="^[a-zA-Z0-9_@.-]+$">
            <label for="floatingPassword" class="fw-bold"><small>Confirm new password</small></label>
          </div>
          <div class="btn-group gap-2 w-100">
            <button type="submit" class="btn btn-secondary fw-bold w-50 rounded" name="submit">Save</button>
            <a href="index.php" class="btn btn-secondary fw-bold w-50 rounded">Back</a>
          </div>
        </form>
      <?php elseif ($page === 'artist'): ?>
        <!-- Artist Name Change Form -->
        <form method="post">
          <div class="form-floating mb-2">
            <input type="text" class="form-control rounded border-3 focus-ring focus-ring-dark" name="artist" placeholder="Enter artist name" value="<?php echo htmlspecialchars($artist); ?>" maxlength="200">
            <label for="floatingArtistName" class="fw-bold"><small>Enter artist name</small></label>
          </div>
          <div class="btn-group gap-2 w-100">
            <button type="submit" class="btn btn-secondary fw-bold w-50 rounded" name="submit">Save</button>
            <a href="index.php" class="btn btn-secondary fw-bold w-50 rounded">Back</a>
          </div>
        </form>
      <?php endif; ?>
    </div>
  </body>
</html>