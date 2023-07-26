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
    <div class="row">
      <div class="col-md-7 order-md-1 mb-2">
        <a href="images/<?php echo $filename; ?>">
          <img class="shadow rounded" src="thumbnails/<?php echo $filename; ?>" width="100%" height="100%">
        </a>
      </div>
      <div class="col-md-5 order-md-1">
        <h5 class="text-dark fw-bold text-center"><?php echo $image['title']; ?></h5>
        <div style="word-break: break-word;" data-lazyload>
          <p class="text-secondary fw-bold" style="word-break: break-word;">
            <small>
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
            </small>
          </p>
        </div>
        <p class="text-dark" style="word-wrap: break-word;"><a class="text-primary" href="<?php echo $image['link']; ?>"><?php echo (strlen($image['link']) > 40) ? substr($image['link'], 0, 40) . '...' : $image['link']; ?></a></p>
        <div class="btn-group w-100">
          <button class="btn btn-primary rounded-end-0 dropdown-toggle fw-bold" type="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="bi bi-info-circle-fill"></i> info</button>
          <a class="btn btn-primary fw-bold rounded-start-0" href="images/<?php echo $image['filename']; ?>" download>Download Image</a> 
          <button class="btn btn-primary fw-bold rounded-end" onclick="sharePage()"><i class="bi bi-share-fill"></i> share</button>
          <ul class="dropdown-menu">
            <?php
              // Get the image information from the database
              $stmt = $db->prepare("SELECT * FROM images WHERE filename = :filename");
              $stmt->bindParam(':filename', $filename);
              $stmt->execute();
              $image = $stmt->fetch();

              // Get image size in megabytes
              $image_size = round(filesize('images/' . $image['filename']) / (1024 * 1024), 2);

              // Get image dimensions
              list($width, $height) = getimagesize('images/' . $image['filename']);

              // Display image information
              echo "<li class='me-1 ms-1'>Image data size: " . $image_size . " MB</li>";
              echo "<li class='me-1 ms-1'>Image dimensions: " . $width . "x" . $height . "</li>";
            ?>
          </ul>
        </div>
        <?php if ($next_image): ?>
          <button class="btn btn-sm btn-primary fw-bold float-start rounded-pill mt-1" onclick="location.href='image.php?filename=<?= $next_image['filename'] ?>'">
            <i class="bi bi-arrow-left-circle-fill"></i> Next
          </button>
        <?php endif; ?> 
        <?php if ($prev_image): ?>
          <button class="btn btn-sm btn-primary fw-bold float-end rounded-pill mt-1" onclick="location.href='image.php?filename=<?= $prev_image['filename'] ?>'">
            Previous <i class="bi bi-arrow-right-circle-fill"></i>
          </button>
        <?php endif; ?>
        <p class="text-dark mt-5"><i class="bi bi-tags-fill"></i> tags</p>
        <div class="tag-buttons container">
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
