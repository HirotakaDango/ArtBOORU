<?php
session_start();

$email = isset($_SESSION['email']) ? $_SESSION['email'] : '';

// Connect to the SQLite database
$db = new SQLite3('database.sqlite');

$query = "SELECT users.id AS uid, users.* FROM users WHERE email = :email";
$stmt = $db->prepare($query);
$stmt->bindValue(':email', $email, SQLITE3_TEXT);
$result = $stmt->execute();

// Fetch user data
$user = $result->fetchArray(SQLITE3_ASSOC);

// Define the number of images per page
$images_per_page = 12;

// Determine the current page
$current_page = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($current_page < 1) {
  $current_page = 1;
}

// Calculate the offset for the SQL query
$offset = ($current_page - 1) * $images_per_page;

// Build the base query
$query = "SELECT * FROM images";
$count_query = "SELECT COUNT(*) as count FROM images";
$where_clauses = [];
$params = [];

// Add filters for tags
if (isset($_GET['q'])) {
  $tags = explode(" ", $_GET['q']);
  foreach ($tags as $index => $tag) {
    $where_clauses[] = "tags LIKE :tag" . $index;
    $params[':tag' . $index] = '%' . $tag . '%';
  }
}

if (isset($_GET['userid'])) {
  $where_clauses[] = "email = (SELECT email FROM users WHERE id = :userid)";
  $params[':userid'] = intval($_GET['userid']);
}

if (count($where_clauses) > 0) {
  $query .= " WHERE " . implode(" AND ", $where_clauses);
  $count_query .= " WHERE " . implode(" AND ", $where_clauses);
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['image_id']) && isset($_POST['tags'])) {
  $image_id = intval($_POST['image_id']);
  $tags = $_POST['tags'];

  // Update tags in the database
  $stmt_update = $db->prepare("UPDATE images SET tags = :tags WHERE id = :id");
  $stmt_update->bindValue(':tags', $tags, SQLITE3_TEXT);
  $stmt_update->bindValue(':id', $image_id, SQLITE3_INTEGER);

  if ($stmt_update->execute()) {
    header("Location: index.php?view=image&id=$image_id");
    exit;
  } else {
    echo "Failed to update tags.";
  }
}

if (isset($_GET['type']) && $_GET['type'] == 'image' && isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])):
  $image_id = intval($_GET['id']);
  
  // Fetch the image details to get the filename
  $stmt_edit = $db->prepare("SELECT * FROM images WHERE id = :id");
  $stmt_edit->bindValue(':id', $image_id, SQLITE3_INTEGER);
  $edit_image = $stmt_edit->execute()->fetchArray(SQLITE3_ASSOC);

  if ($edit_image && $edit_image['email'] == $email) {
    // Delete image files
    $image_path = 'images/' . $edit_image['filename'];
    $thumbnail_path = 'thumbnails/' . $edit_image['filename'];

    if (file_exists($image_path) && is_writable($image_path)) {
      unlink($image_path);
    } else {
      error_log("Failed to delete image file: $image_path");
    }

    if (file_exists($thumbnail_path) && is_writable($thumbnail_path)) {
      unlink($thumbnail_path);
    } else {
      error_log("Failed to delete thumbnail file: $thumbnail_path");
    }

    // Delete image record from the database
    $stmt_delete = $db->prepare("DELETE FROM images WHERE id = :id");
    $stmt_delete->bindValue(':id', $image_id, SQLITE3_INTEGER);
    $result = $stmt_delete->execute();

    if ($result) {
      $_SESSION['message'] = "Image deleted successfully.";
    } else {
      $_SESSION['error'] = "Failed to delete image from database.";
      error_log("Failed to delete image from database. Image ID: $image_id");
    }

    header("Location: index.php");
    exit;
  } else {
    $_SESSION['error'] = "You don't have permission to delete this image or the image doesn't exist.";
    header("Location: index.php");
    exit;
  }
endif;

// Get the total number of images
$stmt_count = $db->prepare($count_query);
foreach ($params as $key => $value) {
  $stmt_count->bindValue($key, $value);
}
$total_images_query = $stmt_count->execute()->fetchArray(SQLITE3_ASSOC);
$total_images = intval($total_images_query['count']);

// Retrieve the images for the current page
$query .= " ORDER BY id DESC LIMIT :limit OFFSET :offset";
$stmt = $db->prepare($query);
foreach ($params as $key => $value) {
  $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $images_per_page, SQLITE3_INTEGER);
$stmt->bindValue(':offset', $offset, SQLITE3_INTEGER);
$result = $stmt->execute();

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
<html data-bs-theme="dark">
  <head>
    <title>
      <?php
      if (isset($_GET['id'])) {
        echo 'Image ID: ' . $_GET['id'];
      } elseif (isset($_GET['artists'])) {
        echo 'All Artists';
      } elseif (isset($_GET['tags'])) {
        echo 'All Tags';
      } elseif (isset($_GET['userid'])) {
        echo 'User ID: ' . $_GET['userid'];
      } elseif (isset($_GET['q'])) {
        echo 'Query: "' . $_GET['q'] . '"';
      } else {
        echo 'ArtBOORU';
      }
      ?>
    </title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php include('bootstrap.php'); ?>
  </head>
  <body>
    <?php include('header.php'); ?>
    <?php if (isset($_SESSION['email'])): ?>
      <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable modal-fullscreen">
          <div class="modal-content">
            <div class="modal-header border-0">
              <h1 class="modal-title fs-5" id="exampleModalLabel">Upload</h1>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body mt-2">
              <div class="row">
                <div class="col-md-6 mb-2 mb-md-0">
                  <div class="">
                    <div id="preview-container"></div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="caarcontainer">
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
          </div>
        </div>
      </div>
    <?php endif; ?>
    <?php if (isset($_GET['view']) && $_GET['view'] == 'image' && isset($_GET['id'])): 
      $image_id = intval($_GET['id']);
      $stmt = $db->prepare("SELECT images.*, users.*, users.id AS uid FROM images JOIN users ON images.email = users.email WHERE images.id = :id");
      $stmt->bindValue(':id', $image_id, SQLITE3_INTEGER);
      $image = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

      if ($image):
        // Get the previous image information from the database
        $stmt_prev = $db->prepare("SELECT * FROM images WHERE id < :id AND email = :email ORDER BY id DESC LIMIT 1");
        $stmt_prev->bindValue(':id', $image_id, SQLITE3_INTEGER);
        $stmt_prev->bindValue(':email', $image['email'], SQLITE3_TEXT);
        $prev_image = $stmt_prev->execute()->fetchArray(SQLITE3_ASSOC);
    
        // Get the next image information from the database
        $stmt_next = $db->prepare("SELECT * FROM images WHERE id > :id AND email = :email ORDER BY id ASC LIMIT 1");
        $stmt_next->bindValue(':id', $image_id, SQLITE3_INTEGER);
        $stmt_next->bindValue(':email', $image['email'], SQLITE3_TEXT);
        $next_image = $stmt_next->execute()->fetchArray(SQLITE3_ASSOC);
      
        // Shows tags from current image id
        $tags = explode(',', $image['tags']);
        $tag_counts = [];
  
        foreach ($tags as $tag) {
          $tag_stmt = $db->prepare("SELECT COUNT(*) as count FROM images WHERE tags LIKE :tag");
          $tag_stmt->bindValue(':tag', '%' . trim($tag) . '%');
          $tag_counts[trim($tag)] = $tag_stmt->execute()->fetchArray(SQLITE3_ASSOC)['count'];
        }
        ?>
        <div class="container-fluid mt-4 mb-5">
          <div class="row">
            <div class="col-md-9 order-md-2 mb-5 mb-md-0">
              <?php
              // Function to check if file size is above 2MB
              function isLargeImage($filename) {
                $filepath = 'images/' . $filename;
                $filesize = filesize($filepath); // in bytes
                $filesize_kb = $filesize / 1024; // in KB
                return $filesize_kb > 2048; // 2MB in KB
              }
            
              // Assuming $image['filename'] contains the filename of the image
              $image_filename = $image['filename'];
              $src = isLargeImage($image_filename) ? 'thumbnails/' . $image_filename : 'images/' . $image_filename;
              ?>
              <img src="<?php echo $src; ?>" class="w-100 rounded object-fit-cover shadow">
            </div>
            <div class="col-md-3 order-md-1">
              <div class="d-flex">
                <?php if ($next_image): ?>
                  <a class="btn btn-lg p-0 border-0 me-auto rounded fw-bold" href="?view=image&id=<?= $next_image['id'] ?>">
                    Next
                  </a>
                <?php endif; ?> 
                <?php if ($prev_image): ?>
                  <a class="btn btn-lg p-0 border-0 ms-auto rounded fw-bold" href="?view=image&id=<?= $prev_image['id'] ?>">
                    Previous
                  </a>
                <?php endif; ?> 
              </div>
              <div class="metadata">
                <h5 class="fw-bold mt-4">Tags</h5>
                <?php foreach ($tags as $tag): ?>
                  <a class="text-decoration-none fw-medium small" href="?q=<?php echo urlencode($tag); ?>"><?php echo trim($tag); ?> (<?php echo $tag_counts[trim($tag)]; ?>)</a></br>
                <?php endforeach; ?>
                <h5 class="fw-bold mt-4">Informations</h5>
                <?php
                // Get file metadata
                $image_path = 'images/' . $image['filename'];
                $file_info = getimagesize($image_path);
                $file_size = filesize($image_path);
                $file_size_kb = number_format($file_size / 1024, 2); // Convert to KB
                
                // Extract dimensions
                $image_width = $file_info[0];
                $image_height = $file_info[1];
                
                // File type
                $image_type = image_type_to_mime_type($file_info[2]);
                
                // Get image size of the original image in megabytes
                $original_image_size = round(filesize('images/' . $image['filename']) / (1024 * 1024), 2);
                
                // Get image size of the thumbnail in megabytes
                $thumbnail_image_size = round(filesize('thumbnails/' . $image['filename']) / (1024 * 1024), 2);
                
                // Calculate the percentage of reduction
                $reduction_percentage = ((($original_image_size - $thumbnail_image_size) / $original_image_size) * 100);
                ?>
                <h6 class="fw-medium small">Uploaded by: <a class="text-decoration-none" href="?userid=<?php echo $image['uid']; ?>"><?php echo $image['artist']; ?></a><h6>
                <h6 class="fw-medium small">Image ID: <?php echo $_GET['id']; ?><h6>
                <h6 class="fw-medium small">Filename: <?php echo $image['filename']; ?><h6>
                <h6 class="fw-medium small">Compressed: <?php echo round($reduction_percentage, 2); ?>%<h6>
                <h6 class="fw-medium small">Date: <?php echo date("l, d F, Y", filemtime($image_path)); ?><h6>
                <h6 class="fw-medium small">Size: <?php echo $file_size_kb; ?> KB<h6>
                <h6 class="fw-medium small">Dimensions: <?php echo $image_width; ?>x<?php echo $image_height; ?><h6>
                <h6 class="fw-medium small">Type: <?php echo $image_type; ?><h6>
                <h5 class="fw-bold mt-4">Options</h5>
                <a class="text-decoration-none fw-medium small" href="#" data-bs-toggle="modal" data-bs-target="#shareLink">Share</a></br>
                <?php if ($image['email'] == $email): ?>
                  <a class="text-decoration-none fw-medium small" href="?type=image&action=edit&id=<?php echo $_GET['id']; ?>">Edit</a></br>
                  <a class="text-decoration-none fw-medium small" href="?type=image&action=delete&id=<?php echo $_GET['id']; ?>" onclick="return confirm('Are you sure you want to delete this image?');">Delete</a></br>
                <?php endif; ?>
                <a class="text-decoration-none fw-medium small" href="images/<?php echo $image['filename']; ?>" download>Download</a></br>
                <a class="text-decoration-none fw-medium small" href="images/<?php echo $image['filename']; ?>">View original</a>
                <div class="modal fade" id="shareLink" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                  <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content bg-transparent border-0 rounded-0">
                      <div class="card rounded-4 p-4">
                        <p class="text-start fw-bold">share to:</p>
                        <div class="btn-group w-100 mb-2" role="group" aria-label="Share Buttons">
                          <!-- Twitter -->
                          <a class="btn rounded-start-4" href="https://twitter.com/intent/tweet?url=<?php echo 'http' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>" target="_blank" rel="noopener noreferrer">
                            <i class="bi bi-twitter"></i>
                          </a>
                                            
                          <!-- Line -->
                          <a class="btn" href="https://social-plugins.line.me/lineit/share?url=<?php echo 'http' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>" target="_blank" rel="noopener noreferrer">
                            <i class="bi bi-line"></i>
                          </a>
                                            
                          <!-- Email -->
                          <a class="btn" href="mailto:?body=<?php echo 'http' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>">
                            <i class="bi bi-envelope-fill"></i>
                          </a>
                                            
                          <!-- Reddit -->
                          <a class="btn" href="https://www.reddit.com/submit?url=<?php echo 'http' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>" target="_blank" rel="noopener noreferrer">
                            <i class="bi bi-reddit"></i>
                          </a>
                                            
                          <!-- Instagram -->
                          <a class="btn" href="https://www.instagram.com/?url=<?php echo 'http' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>" target="_blank" rel="noopener noreferrer">
                            <i class="bi bi-instagram"></i>
                          </a>
                                            
                          <!-- Facebook -->
                          <a class="btn rounded-end-4" href="https://www.facebook.com/sharer/sharer.php?u=<?php echo 'http' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>" target="_blank" rel="noopener noreferrer">
                            <i class="bi bi-facebook"></i>
                          </a>
                        </div>
                        <div class="btn-group w-100 mb-2" role="group" aria-label="Share Buttons">
                          <!-- WhatsApp -->
                          <a class="btn rounded-start-4" href="https://wa.me/?text=<?php echo 'http' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>" target="_blank" rel="noopener noreferrer">
                            <i class="bi bi-whatsapp"></i>
                          </a>
                
                          <!-- Pinterest -->
                          <a class="btn" href="https://pinterest.com/pin/create/button/?url=<?php echo 'http' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>" target="_blank" rel="noopener noreferrer">
                            <i class="bi bi-pinterest"></i>
                          </a>
                
                          <!-- LinkedIn -->
                          <a class="btn" href="https://www.linkedin.com/shareArticle?url=<?php echo 'http' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>" target="_blank" rel="noopener noreferrer">
                            <i class="bi bi-linkedin"></i>
                          </a>
                
                          <!-- Messenger -->
                          <a class="btn" href="https://www.facebook.com/dialog/send?link=<?php echo 'http' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>&app_id=YOUR_FACEBOOK_APP_ID" target="_blank" rel="noopener noreferrer">
                            <i class="bi bi-messenger"></i>
                          </a>
                
                          <!-- Telegram -->
                          <a class="btn" href="https://telegram.me/share/url?url=<?php echo 'http' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>" target="_blank" rel="noopener noreferrer">
                            <i class="bi bi-telegram"></i>
                          </a>
                
                          <!-- Snapchat -->
                          <a class="btn rounded-end-4" href="https://www.snapchat.com/share?url=<?php echo 'http' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>" target="_blank" rel="noopener noreferrer">
                            <i class="bi bi-snapchat"></i>
                          </a>
                        </div>
                        <div class="input-group">
                          <input type="text" id="urlInput1" value="<?php echo 'http' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>" class="form-control border-2 fw-bold" readonly>
                          <button class="btn btn-secondary opacity-50 fw-bold" onclick="copyToClipboard1()">
                            <i class="bi bi-clipboard-fill"></i>
                          </button>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <?php
      else:
        echo "Image not found.";
      endif;
      exit;
    endif; ?>

    <?php
    if (isset($_GET['type']) && $_GET['type'] == 'image' && isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])):
      session_start();
      
      // Check if the user is logged in
      if (!isset($_SESSION['email'])) {
        header("Location: session.php?page=login");
        exit;
      }
      
      $image_id = intval($_GET['id']);
      
      // Fetch image details for editing
      $stmt_edit = $db->prepare("SELECT * FROM images WHERE id = :id");
      $stmt_edit->bindValue(':id', $image_id, SQLITE3_INTEGER);
      $edit_image = $stmt_edit->execute()->fetchArray(SQLITE3_ASSOC);
      
      if ($edit_image && $edit_image['email'] == $_SESSION['email']): ?>
        <div class="container mt-4">
          <h2>Edit Tags for Image: <?php echo htmlspecialchars($edit_image['id']); ?></h2>
          <form method="post" action="index.php">
            <input type="hidden" name="image_id" value="<?php echo $image_id; ?>">
            <div class="mb-3">
              <label for="tags" class="form-label">Tags</label>
              <input type="text" class="form-control" id="tags" name="tags" value="<?php echo htmlspecialchars($edit_image['tags']); ?>">
            </div>
            <button type="submit" class="btn btn-primary">Update Tags</button>
          </form>
        </div>
      <?php else:
        // Redirect to index.php if the image is not found or if the user is not the owner
        header("Location: index.php");
      endif;
      exit;
    endif;
    ?>

    <?php if (isset($_GET['tags']) && $_GET['tags'] == 'all'): 
      $tag_counts_query = $db->query("SELECT tags FROM images");
      $tag_counts = [];
      while ($row = $tag_counts_query->fetchArray(SQLITE3_ASSOC)) {
        $tags = explode(',', $row['tags']);
        foreach ($tags as $tag) {
          $tag = trim($tag);
          if (!isset($tag_counts[$tag])) {
            $tag_counts[$tag] = 0;
          }
          $tag_counts[$tag]++;
        }
      }
      ?>
      <div class="container mt-4">
        <h2>All Tags</h2>
        <ul>
          <?php foreach ($tag_counts as $tag => $count): ?>
            <li><a class="text-decoration-none fw-medium" href="?q=<?php echo urlencode($tag); ?>"><?php echo $tag; ?> (<?php echo $count; ?>)</a></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <?php
      exit;
    endif; ?>
    
    <?php if (isset($_GET['artists']) && $_GET['artists'] == 'all'): 
      $artists_query = $db->query("SELECT * FROM users");
      ?>
      <div class="container mt-4">
        <h2>All Artists</h2>
        <ul>
          <?php while ($artist = $artists_query->fetchArray(SQLITE3_ASSOC)): ?>
            <li><a class="text-decoration-none fw-medium" href="?userid=<?php echo $artist['id']; ?>"><?php echo $artist['artist']; ?></a></li>
          <?php endwhile; ?>
        </ul>
      </div>
      <?php
      exit;
    endif; ?>
    
    <div class="w-100 px-1">
      <div class="row row-cols-2 row-cols-sm-2 row-cols-md-6 g-1">
        <?php while ($image = $result->fetchArray()): ?>
          <div class="col">
            <a class="ratio ratio-1x1" href="index.php?view=image&id=<?php echo $image['id']; ?>">
              <img class="rounded shadow object-fit-cover" src="thumbnails/<?php echo $image['filename']; ?>">
            </a>
          </div>
        <?php endwhile; ?>
      </div>
    </div>
    <nav aria-label="Page navigation" class="pagination d-flex gap-1 justify-content-center mt-3">
      <ul class="pagination">
        <?php if ($current_page > 1): ?>
          <li class="page-item"><a class="page-link fw-medium" href="?page=1"><i class="bi bi-chevron-double-left" style="-webkit-text-stroke: 1px;"></i></a></li>
          <li class="page-item"><a class="page-link fw-medium" href="?page=<?php echo $current_page - 1; ?>"><i class="bi bi-chevron-left" style="-webkit-text-stroke: 1px;"></i></a></li>
        <?php endif; ?>

        <?php
        $start = max(1, $current_page - 2);
        $end = min($start + 4, ceil($total_images / $images_per_page));

        if ($end - $start < 4) {
           $start = max(1, $end - 4);
        }

        for ($i = $start; $i <= $end; $i++):
        ?>
          <li class="page-item <?php if ($i == $current_page) echo 'active'; ?>">
            <a class="page-link fw-medium" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
          </li>
        <?php endfor; ?>

        <?php if ($current_page < ceil($total_images / $images_per_page)): ?>
          <li class="page-item"><a class="page-link fw-medium" href="?page=<?php echo $current_page + 1; ?>"><i class="bi bi-chevron-right" style="-webkit-text-stroke: 1px;"></i></a></li>
          <li class="page-item"><a class="page-link fw-medium" href="?page=<?php echo $end; ?>"><i class="bi bi-chevron-double-right" style="-webkit-text-stroke: 1px;"></i></a></li>
        <?php endif; ?>
      </ul>
    </nav>
  </body>
</html>