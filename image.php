<?php
session_start();
if (!isset($_SESSION['username'])) {
  header("Location: session.php");
  exit;
}

// Connect to the database using PDO
$db = new PDO('sqlite:database.sqlite');

// Get the filename from the query string
$filename = $_GET['filename'];

// Get the current image information from the database
$stmt = $db->prepare("SELECT * FROM images WHERE filename = :filename");
$stmt->bindParam(':filename', $filename);
$stmt->execute();
$image = $stmt->fetch();

// Get the ID of the current image and the username of the owner
$image_id = $image['id'];
$username = $image['username'];

// Get the previous image information from the database
$stmt = $db->prepare("SELECT * FROM images WHERE id < :id AND username = :username ORDER BY id DESC LIMIT 1");
$stmt->bindParam(':id', $image_id);
$stmt->bindParam(':username', $username);
$stmt->execute();
$prev_image = $stmt->fetch();

// Get the next image information from the database
$stmt = $db->prepare("SELECT * FROM images WHERE id > :id AND username = :username ORDER BY id ASC LIMIT 1");
$stmt->bindParam(':id', $image_id);
$stmt->bindParam(':username', $username);
$stmt->execute();
$next_image = $stmt->fetch();

// Get the image information from the database
$stmt = $db->prepare("SELECT * FROM images WHERE filename = :filename");
$stmt->bindParam(':filename', $filename);
$stmt->execute();
$image = $stmt->fetch();

// Check if the user is logged in and get their username
$username = '';
if (isset($_SESSION['username'])) {
  $username = $_SESSION['username'];
}

// Get the username of the selected user
$user_username = $image['username'];

// Get the selected user's information from the database
$query = $db->prepare('SELECT * FROM users WHERE username = :username');
$query->bindParam(':username', $user_username);
$query->execute();
$user = $query->fetch();

// Get the image information from the database
$stmt = $db->prepare("SELECT * FROM images WHERE filename = :filename");
$stmt->bindParam(':filename', $filename);
$stmt->execute();
$image = $stmt->fetch();

// Get image size of the original image in megabytes
$original_image_size = round(filesize('images/' . $image['filename']) / (1024 * 1024), 2);

// Get image size of the thumbnail in megabytes
$thumbnail_image_size = round(filesize('thumbnails/' . $image['filename']) / (1024 * 1024), 2);

// Calculate the percentage of reduction
$reduction_percentage = ((($original_image_size - $thumbnail_image_size) / $original_image_size) * 100);

// Get image dimensions
list($width, $height) = getimagesize('images/' . $image['filename']);
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8"> 
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $image['title']; ?></title>
    <?php include('bootstrapcss.php'); ?>
  </head>
  <body>
    <?php include('header.php'); ?>
    <div class="container-fluid">
      <div class="alert alert-warning mt-3 fw-bold">compressed to <?php echo round($reduction_percentage, 2); ?>% <a href="images/<?php echo $filename; ?>">view original</a></div>
      <div class="row">
        <div class="col-md-7 order-md-1 mb-2">
          <div class="position-relative">
            <a href="images/<?php echo $filename; ?>">
              <img class="shadow rounded" src="thumbnails/<?php echo $filename; ?>" width="100%" height="100%">
            </a>
            <div class="btn-group position-absolute bottom-0 end-0 m-2">
              <a class="btn btn-sm btn-primary fw-bold rounded-start" href="images/<?php echo $image['filename']; ?>" download><i class="bi bi-cloud-arrow-down-fill"></i> Download Image</a> 
              <button class="btn btn-sm btn-primary fw-bold rounded-end" onclick="sharePage()"><i class="bi bi-share-fill"></i> Share Image</button>
            </div>
            <?php if ($next_image): ?>
              <button class="btn opacity-75 rounded fw-bold position-absolute start-0 top-50 translate-middle-y ms-1"  onclick="location.href='image.php?filename=<?= $next_image['filename'] ?>'">
                <i class="bi bi-arrow-left-circle-fill fs-1"></i>
              </button>
            <?php endif; ?> 
            <?php if ($prev_image): ?>
              <button class="btn opacity-75 rounded fw-bold position-absolute end-0 top-50 translate-middle-y me-1"  onclick="location.href='image.php?filename=<?= $prev_image['filename'] ?>'">
                <i class="bi bi-arrow-right-circle-fill fs-1"></i>
              </button>
            <?php endif; ?> 
          </div>
        </div>
        <div class="col-md-5 order-md-1">
          <?php
            $stmt = $db->prepare("SELECT u.id, u.username, u.password, u.artist, u.pic, u.desc, u.bgpic, i.id AS image_id, i.filename, i.tags FROM users u INNER JOIN images i ON u.id = i.id WHERE u.id = :id");
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
          ?>
          <p><i>uploaded by <?php echo $user['artist']; ?></i></p>
          <h5 class="text-dark fw-bold text-center"><?php echo $image['title']; ?></h5>
          <div style="word-break: break-word;" data-lazyload>
            <p class="text-secondary fw-bold" style="word-break: break-word;"><small>
              <?php
                $messageText = $image['imgdesc'];
                $messageTextWithoutTags = strip_tags($messageText);
                $pattern = '/\bhttps?:\/\/\S+/i';

                $formattedText = preg_replace_callback($pattern, function ($matches) {
                  $url = htmlspecialchars($matches[0]);
                  return '<a target="_blank" href="' . $url . '">' . $url . '</a>';
                }, $messageTextWithoutTags);

                $formattedTextWithLineBreaks = nl2br($formattedText);
                echo $formattedTextWithLineBreaks;
              ?>
            </small></p>
          </div>
          <p class="text-dark" style="word-wrap: break-word;"><a class="text-primary" href="<?php echo $image['link']; ?>"><?php echo (strlen($image['link']) > 40) ? substr($image['link'], 0, 40) . '...' : $image['link']; ?></a></p>
          <?php
            // Display image information
            echo "<li class='me-1 ms-1'>Image data size: " . $original_image_size . " MB</li>";
            echo "<li class='me-1 ms-1'>Image dimensions: " . $width . "x" . $height . "</li>";
            echo "<li class='me-1 ms-1'>Reduction percentage: " . round($reduction_percentage, 2) . "%</li>";
          ?>
          <div class="mt-3">
            <?php
              $tags = explode(',', $image['tags']);
              foreach ($tags as $tag) {
                $tag = trim($tag);
                if (!empty($tag)) {
              ?>
                <a href="tagged_images.php?tag=<?php echo urlencode($tag); ?>"
                  class="btn btn-sm mb-1 btn-outline-dark">
                  <?php echo $tag; ?>
                </a>
                  <?php
                }
              }
            ?>
          </div>
        </div>
      </div>
    </div>
    <div class="mt-5"></div>
    <script>
      function sharePage() {
        if (navigator.share) {
          navigator.share({
            title: document.title,
            url: window.location.href
          }).then(() => {
          console.log('Page shared successfully.');
          }).catch((error) => {
            console.error('Error sharing page:', error);
          });
        } else {
          console.log('Web Share API not supported.');
        }
      }
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <?php include('bootstrapjs.php'); ?>
  </body>
</html>
