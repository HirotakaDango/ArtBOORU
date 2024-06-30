<?php
session_start();
if (!isset($_SESSION['email'])) {
  header("Location: session.php");
  exit;
}

// Connect to the SQLite database
$db = new SQLite3('database.sqlite');

// Check if any images were uploaded
if (isset($_FILES['image'])) {

  ob_start(); // Start output buffering to prevent header errors

  $images = $_FILES['image'];

  // Loop through each uploaded image
  for ($i = 0; $i < count($images['name']); $i++) {
    $image = array(
      'name' => $images['name'][$i],
      'type' => $images['type'][$i],
      'tmp_name' => $images['tmp_name'][$i],
      'error' => $images['error'][$i],
      'size' => $images['size'][$i]
    );

    // Check if the image is valid
    if ($image['error'] == 0) {
      // Generate a unique file name
      $ext = pathinfo($image['name'], PATHINFO_EXTENSION);
      $filename = uniqid() . '.' . $ext;

      // Save the original image
      move_uploaded_file($image['tmp_name'], 'images/' . $filename);

      // Determine the image type and generate the thumbnail
      $image_info = getimagesize('images/' . $filename);
      $mime_type = $image_info['mime'];
      switch ($mime_type) {
        case 'image/jpeg':
          $source = imagecreatefromjpeg('images/' . $filename);
          break;
        case 'image/png':
          $source = imagecreatefrompng('images/' . $filename);
          break;
        case 'image/gif':
          $source = imagecreatefromgif('images/' . $filename);
          break;
        default:
          echo "Error: Unsupported image format.";
          exit;
      }

      if ($source === false) {
        echo "Error: Failed to create image source.";
        exit;
      }

      $original_width = imagesx($source);
      $original_height = imagesy($source);
      $ratio = $original_width / $original_height;
      $thumbnail_width = 300;
      $thumbnail_height = intval(300 / $ratio); // Convert float to integer

      $thumbnail = imagecreatetruecolor($thumbnail_width, $thumbnail_height);

      if ($thumbnail === false) {
        echo "Error: Failed to create thumbnail.";
        exit;
      }

      imagecopyresampled($thumbnail, $source, 0, 0, 0, 0, $thumbnail_width, $thumbnail_height, $original_width, $original_height);

      switch ($ext) {
        case 'jpg':
        case 'jpeg':
          imagejpeg($thumbnail, 'thumbnails/' . $filename);
          break;
        case 'png':
          imagepng($thumbnail, 'thumbnails/' . $filename);
          break;
        case 'gif':
          imagegif($thumbnail, 'thumbnails/' . $filename);
          break;
        default:
          echo "Error: Unsupported image format.";
          exit;
      }

      // Add the image to the database
      $email = $_SESSION['email'];
      $tags = filter_var($_POST['tags'], FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_FLAG_STRIP_LOW);
      $tags = explode(",", $tags);
      $tags = array_map('trim', $tags); // Remove extra white space from each tag
      $tags = array_filter($tags); // Remove any empty tags
      $tags = array_values($tags); // Reset array indexes
      $tags = implode(",", $tags); // Join tags by comma
      $stmt = $db->prepare("INSERT INTO images (email, filename, tags) VALUES (:email, :filename, :tags)");
      $stmt->bindValue(':email', $email);
      $stmt->bindValue(':filename', $filename);
      $stmt->bindValue(':tags', $tags);
      $stmt->execute();
    } else {
      echo "Error uploading image.";
    }
  }

  header("Location: index.php");
  exit;
}
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Upload</title>
    <?php include('bootstrap.php'); ?>
  </head>
  <body>
    <?php include('header.php'); ?>
    <div class="container-fluid mt-2">
      <div class="row">
        <div class="col-6">
          <div class="caard">
            <div id="preview-container"></div>
          </div>
        </div>
        <div class="col-6">
          <div class="caard container">
            <form method="post" enctype="multipart/form-data">
              <input class="form-control mb-2 border rounded-3 text-secondary fw-bold border-4" type="file" name="image[]" id="file-ip-1" accept="image/*" onchange="showPreview(event);" multiple required>
              <div class="form-floating mb-2">
                <input class="form-control border rounded-3 text-secondary fw-bold border-4" type="text" name="tags" placeholder="Enter tag for your image" maxlength="180" required>  
                <label for="floatingInput" class="text-secondary fw-bold">Enter tag for your image</label>
              </div>
              <input class="btn btn-lg btn-primary fw-bold w-100" type="submit" name="submit" value="upload">
            </form> 
          </div> 
        </div>
      </div>
    </div>
    <div class="mt-5"></div>
    <script>
      function showPreview(event) {
        // Get the container for the preview images
        var container = document.getElementById("preview-container");

        // Clear any existing preview images
        container.innerHTML = "";

        // Set the height of the image based on the viewport width and number of images
        var imgHeight = window.innerWidth < 768 ? (event.target.files.length > 1 ? 200 : 400) : (event.target.files.length > 2 ? 100 : 424);

        // Loop through all selected files
        for (var i = 0; i < event.target.files.length; i++) {
          // Create a new image element for each file
          var img = document.createElement("img");
          img.style.width = "100%";
          img.style.height = imgHeight + "px";
          img.classList.add("rounded", "object-fit-cover", "shadow");

          // Set the source of the image to the URL of the file
          img.src = URL.createObjectURL(event.target.files[i]);

          // Add the image element to the container
          container.appendChild(img);
        }
  
        // Set the grid display properties
        container.style.display = "grid";
        container.style.gridTemplateColumns = "repeat(auto-fit, minmax(150px, 1fr))";
        container.style.gridGap = "2px";
        container.style.justifyContent = "center";
        container.style.marginRight = "3px";
        container.style.marginLeft = "3px";
      }
    </script>
  </body>
</html>