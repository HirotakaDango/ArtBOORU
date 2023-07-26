<?php
session_start();
if (!isset($_SESSION['username'])) {
  header("Location: session.php");
  exit;
}

// Connect to SQLite database
$db = new PDO('sqlite:database.sqlite');

// Retrieve image details
if (isset($_GET['id'])) {
  $id = $_GET['id'];
  $stmt = $db->prepare('SELECT * FROM images WHERE id = :id');
  $stmt->bindParam(':id', $id);
  $stmt->execute();
  $image = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
  // Redirect to error page if image ID is not specified
  header('Location: edit_image.php?id=' . $id);
  exit();
}

// Update image details
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $title = htmlspecialchars($_POST['title']);
  $imgdesc = htmlspecialchars($_POST['imgdesc']);
  $link = htmlspecialchars($_POST['link']);
  $tags = htmlspecialchars($_POST['tags']);
  
  $stmt = $db->prepare('UPDATE images SET title = :title, imgdesc = :imgdesc, link = :link, tags = :tags WHERE id = :id');
  $stmt->bindParam(':title', $title);
  $stmt->bindParam(':imgdesc', $imgdesc);
  $stmt->bindParam(':link', $link);
  $stmt->bindParam(':tags', $tags);
  $stmt->bindParam(':id', $id);
  $stmt->execute();
  
  // Redirect to image details page after update
  header('Location: edit_image.php?id=' . $id);
  exit();
}
?>
<!DOCTYPE html>
<html>
  <head>
    <title>Edit</title>
    <meta charset="UTF-8"> 
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php include('bootstrapcss.php'); ?>
  </head>
  <body>
    <?php include('header.php'); ?>
    <div class="container-fluid">
      <h3 class="text-secondary fw-bold mt-2 ms-2 text-center"><i class="bi bi-image"></i> Edit Image</h3>
      <div class="row featurette">
        <div class="col-md-5 order-md-1 mb-2">
          <img class="rounded shadow" src="thumbnails/<?php echo htmlspecialchars($image['filename']); ?>" alt="<?php echo htmlspecialchars($image['title']); ?>" class="img-fluid border-top border-bottom border-3" style="width: 100%; height: auto; object-fit: cover;">
        </div>
        <div class="col-md-7 order-md-2">
          <form method="POST">
            <div class="mb-2">
              <input type="text" placeholder="image title" name="title" value="<?php echo htmlspecialchars($image['title']); ?>" class="form-control" maxlength="40">
            </div>
            <div class="mb-2">
              <textarea name="imgdesc" class="form-control" placeholder="image description" maxlength="200"><?php echo htmlspecialchars($image['imgdesc']); ?></textarea>
            </div>
            <div class="mb-2">
              <input type="text" placeholder="image link" name="link" value="<?php echo htmlspecialchars($image['link']); ?>" class="form-control" maxlength="120">
            </div>
            <div class="mb-2">
              <input type="text" name="tags" placeholder="image tag" value="<?php echo htmlspecialchars($image['tags']); ?>" class="form-control">
            </div>
            <input type="submit" value="Save" class="form-control bg-primary text-white fw-bold mb-2">
            <a class="btn btn-danger form-control fw-bold text-white" href="profile.php">back</a>
            <div class="mt-5"></div>
          </form>
        </div>
      </div>
    </div>
    <div class="mt-5"></div>
    <?php include('bootstrapjs.php'); ?>
  </body>
</html>