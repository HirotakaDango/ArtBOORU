<?php
session_start();
if (!isset($_SESSION['username'])) {
  header("Location: session.php");
  exit;
}

// Connect to the database using PDO
$db = new PDO('sqlite:database.sqlite');

// Get the image ID from the query string
$image_id = $_GET['id'];

// Get the current image information from the database
$stmt = $db->prepare("SELECT * FROM images WHERE id = :image_id");
$stmt->bindParam(':image_id', $image_id);
$stmt->execute();
$image = $stmt->fetch();

// Get the ID of the current image and the username of the owner
$username = $image['username'];

// Get the previous image information from the database
$stmt = $db->prepare("SELECT * FROM images WHERE id < :image_id AND username = :username ORDER BY id DESC LIMIT 1");
$stmt->bindParam(':image_id', $image_id);
$stmt->bindParam(':username', $username);
$stmt->execute();
$prev_image = $stmt->fetch();

// Get the next image information from the database
$stmt = $db->prepare("SELECT * FROM images WHERE id > :image_id AND username = :username ORDER BY id ASC LIMIT 1");
$stmt->bindParam(':image_id', $image_id);
$stmt->bindParam(':username', $username);
$stmt->execute();
$next_image = $stmt->fetch();

// Check if the user is logged in and get their username
$username = isset($_SESSION['username']) ? $_SESSION['username'] : '';

// Get the username of the selected user
$user_username = $image['username'];

// Get the selected user's information from the database
$query = $db->prepare('SELECT * FROM users WHERE username = :username');
$query->bindParam(':username', $user_username);
$query->execute();
$user = $query->fetch();

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
    <link rel="stylesheet" href="transitions.css" />
    <script type="module" src="swup.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  </head>
  <body>
    <?php include('header.php'); ?>
    <main id="swup" class="transition-main">
    <div class="container-fluid">
      <div class="alert alert-warning mt-3 fw-bold d-none d-md-block">compressed to <?php echo round($reduction_percentage, 2); ?>% <a href="images/<?php echo $image['filename']; ?>">view original</a></div>
      <div class="row">
        <div class="col-md-7 order-md-1 mb-2">
          <div class="position-relative">
            <a href="images/<?php echo $image['filename']; ?>">
              <img class="shadow rounded" src="thumbnails/<?php echo $image['filename']; ?>" width="100%" height="100%">
            </a>
            <div class="btn-group position-absolute bottom-0 end-0 m-2">
              <a class="btn btn-sm btn-primary fw-bold rounded-start" href="images/<?php echo $image['filename']; ?>" download><i class="bi bi-cloud-arrow-down-fill"></i> Download Image</a> 
              <button class="btn btn-sm btn-primary fw-bold rounded-end" onclick="sharePage()"><i class="bi bi-share-fill"></i> Share Image</button>
            </div>
            <?php if ($next_image): ?>
              <a class="btn opacity-75 rounded fw-bold position-absolute start-0 top-50 translate-middle-y ms-1 d-md-none" href="image.php?id=<?= $next_image['id'] ?>">
                <i class="bi bi-arrow-left-circle-fill fs-1"></i>
              </a>
            <?php endif; ?> 
            <?php if ($prev_image): ?>
              <a class="btn opacity-75 rounded fw-bold position-absolute end-0 top-50 translate-middle-y me-1 d-md-none" href="image.php?id=<?= $prev_image['id'] ?>">
                <i class="bi bi-arrow-right-circle-fill fs-1"></i>
              </a>
            <?php endif; ?> 
          </div>
          <div class="alert alert-warning mt-3 fw-bold d-md-none">compressed to <?php echo round($reduction_percentage, 2); ?>% <a href="images/<?php echo $image['filename']; ?>">view original</a></div>
        </div>
        <div class="col-md-5 order-md-1">
          <div class="d-flex gap-2 mb-4">
            <?php if ($next_image): ?>
              <a class="image-containerA shadow rounded" href="?id=<?= $next_image['id'] ?>">
                <div class="position-relative">
                  <img class="img-blur object-fit-cover rounded opacity-75" style="width: 100%; height: 160px;" src="../thumbnails/<?php echo $next_image['filename']; ?>" alt="<?php echo $next_image['title']; ?>">
                  <h6 class="fw-bold shadowed-text text-white position-absolute top-50 start-50 translate-middle">
                    <i class="bi bi-arrow-left-circle text-stroke"></i>
                  </h6>
                </div>
              </a>
            <?php endif; ?>
            <a class="image-containerA shadow rounded" href="?id=<?= $image['id'] ?>">
              <img class="object-fit-cover opacity-50 rounded" style="width: 100%; height: 160px;" src="../thumbnails/<?= $image['filename'] ?>" alt="<?php echo $image['title']; ?>">
            </a>
            <?php if ($prev_image): ?>
              <a class="image-containerA shadow rounded" href="?id=<?= $prev_image['id'] ?>">
                <div class="position-relative">
                  <img class="img-blur object-fit-cover rounded opacity-75" style="width: 100%; height: 160px;" src="../thumbnails/<?php echo $prev_image['filename']; ?>" alt="<?php echo $prev_image['title']; ?>">
                  <h6 class="fw-bold shadowed-text text-white position-absolute top-50 start-50 translate-middle">
                    <i class="bi bi-arrow-right-circle text-stroke"></i>
                  </h6>
                </div>
              </a>
            <?php endif; ?>
          </div>
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
          <div class="my-3">
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
    </main>
    <div class="mt-5"></div>
    <style>
      .img-blur {
        filter: blur(2px);
      }

      .image-containerA {
        width: 33.33%;
        flex-grow: 1;
      }

      .text-stroke {
        -webkit-text-stroke: 1px;
      }
    </style>
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