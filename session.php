<?php
session_start();
$db = new PDO('sqlite:database.sqlite');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->exec("CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY AUTOINCREMENT, email TEXT NOT NULL, password TEXT NOT NULL, artist TEXT)");

if (isset($_POST['login'])) {
  $email = htmlspecialchars(trim($_POST['email']));
  $password = htmlspecialchars($_POST['password']);

  if (empty($email) || empty($password)) {
    echo 'Please enter email and password';
    exit;
  }

  $query = "SELECT * FROM users WHERE email=:email AND password=:password";
  $stmt = $db->prepare($query);
  $stmt->execute(array(':email' => $email, ':password' => $password));
  $user = $stmt->fetch();

  if ($user) {
    $_SESSION['email'] = $user['email']; // Store email in session
    setcookie('email', $user['email'], time() + (365 * 24 * 60 * 60), '/');
    header('Location: index.php');
    exit;
  } else {
    echo 'Invalid email or password';
    exit;
  }
} elseif (isset($_POST['register'])) {
  $artist = htmlspecialchars(trim($_POST['artist']));
  $email = htmlspecialchars(trim($_POST['email']));
  $password = htmlspecialchars($_POST['password']);

  if (empty($email) || empty($password)) {
    echo 'Please enter email and password';
    exit;
  }

  $query = "SELECT * FROM users WHERE email=:email";
  $stmt = $db->prepare($query);
  $stmt->execute(array(':email' => $email));
  $user = $stmt->fetch();

  if ($user) {
    echo 'Email already taken';
    exit;
  }

  $query = "INSERT INTO users (email, password, artist) VALUES (:email, :password, :artist)";
  $stmt = $db->prepare($query);
  $stmt->execute(array(':email' => $email, ':password' => $password, ':artist' => $artist));

  $_SESSION['email'] = $email; // Store email in session
  setcookie('email', $email, time() + (365 * 24 * 60 * 60), '/');
  header('Location: index.php');
  exit;
}
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login/Register</title>
    <?php include('bootstrap.php'); ?>
  </head>
  <body>
    <div class="container">
      <div class="d-flex justify-content-center align-items-center vh-100">
        <div class="p-0" style="max-width: 300px;">
          <?php if (!isset($_GET['page']) || $_GET['page'] == 'login'): ?>
            <h1 class="fw-bold mb-0 fs-2 text-center mb-5">Login</h1>
            <form method="post">
              <div class="form-floating mb-3">
                <input type="email" name="email" class="form-control rounded-3" id="floatingInputEmail" placeholder="name@example.com" required>
                <label for="floatingInputEmail">Email</label>
              </div>
              <div class="form-floating mb-3">
                <input type="password" name="password" class="form-control rounded-3" id="floatingPassword" placeholder="password" required>
                <label for="floatingPassword">Password</label>
              </div>
              <div class="btn-group w-100 gap-3 mb-3">
                <button class="btn btn-primary fw-bold rounded w-50" type="submit" name="login">Login</button>
              </div>
              <a class="text-decoration-none" href="?page=register">Don't have an account?</a>
            </form>
          <?php elseif ($_GET['page'] == 'register'): ?>
            <h1 class="fw-bold mb-0 fs-2 text-center mb-5">Register</h1>
            <form method="post">
              <div class="form-floating mb-3">
                <input type="text" name="artist" class="form-control rounded-3" id="floatingInputArtist" placeholder="artist name" required>
                <label for="floatingInputArtist">Artist</label>
              </div>
              <div class="form-floating mb-3">
                <input type="email" name="email" class="form-control rounded-3" id="floatingInputEmail" placeholder="name@example.com" required>
                <label for="floatingInputEmail">Email</label>
              </div>
              <div class="form-floating mb-3">
                <input type="password" name="password" class="form-control rounded-3" id="floatingPassword" placeholder="password" required>
                <label for="floatingPassword">Password</label>
              </div>
              <div class="btn-group w-100 gap-3 mb-3">
                <button class="btn btn-primary fw-bold rounded w-50" type="submit" name="register">Register</button>
              </div>
              <a class="text-decoration-none" href="?page=login">Already have an account?</a>
            </form>
          <?php else: ?>
            <p>Invalid page request.</p>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </body>
</html>