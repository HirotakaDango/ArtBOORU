<?php
  session_start();
  if (!isset($_SESSION['username'])) {
    header("Location: session.php");
    exit;
  }

  // Connect to the SQLite database
  $db = new SQLite3('database.sqlite');

  // Get the artist name from the database
  $username = $_SESSION['username'];
  $stmt = $db->prepare("SELECT artist, pic, `desc`, bgpic FROM users WHERE username = :username");
  $stmt->bindValue(':username', $username);
  $result = $stmt->execute();
  $row = $result->fetchArray();
  $artist = $row['artist'];
  $pic = $row['pic'];
  $desc = $row['desc'];
  $bgpic = $row['bgpic'];

  // Get all of the images uploaded by the current user
  $stmt = $db->prepare("SELECT * FROM images WHERE username = :username ORDER BY id DESC");
  $stmt->bindValue(':username', $username);
  $result = $stmt->execute();

  // Count the number of images uploaded by the current user
  $count = 0;
  while ($image = $result->fetchArray()) {
    $count++;
  }
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $artist; ?></title>
    <?php include('bootstrapcss.php'); ?>
  </head>
  <body>
    <?php include('header.php'); ?>
    <div class="images">
      <?php while ($image = $result->fetchArray()): ?>
        <div class="position-relative">
          <a class="rounded shadow" href="image.php?filename=<?php echo $image['filename']; ?>">
            <img class="lazy-load" data-src="thumbnails/<?php echo $image['filename']; ?>">
          </a> 
          <div class="btn-group position-absolute top-0 start-0 m-2">
            <form action="delete.php" method="post">
              <input type="hidden" name="filename" value="<?php echo $image['filename']; ?>">
              <button class="btn btn-sm rounded-end-0 btn-dark opacity-75 fw-bold" type="submit" onclick="return confirm('Are you sure?')"><i class="bi bi-trash-fill"></i></button>
            </form>
            <button class="btn btn-sm btn-dark opacity-75 fw-bold" onclick="location.href='edit_image.php?id=<?php echo $image['id']; ?>'" ><i class="bi bi-pencil-fill"></i></button> 
          </div>
        </div>
      <?php endwhile; ?>
    </div>
    <div class="mt-5"></div>
    <style>
      .images {
        display: grid;
        grid-template-columns: repeat(2, 1fr); /* Two columns in mobile view */
        grid-gap: 3px;
        justify-content: center;
        margin-right: 3px;
        margin-left: 3px;
      }
      
      @media (min-width: 768px) {
        /* For desktop view, change the grid layout */
        .images {
          grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        }
      }

      .images a {
        display: block;
        overflow: hidden;
      }

      .images img {
        width: 100%;
        height: auto;
        object-fit: cover;
        height: 200px;
        transition: transform 0.5s ease-in-out;
      }
    </style>
    <script>
      document.addEventListener("DOMContentLoaded", function() {
        let lazyloadImages;
        if("IntersectionObserver" in window) {
          lazyloadImages = document.querySelectorAll(".lazy-load");
          let imageObserver = new IntersectionObserver(function(entries, observer) {
            entries.forEach(function(entry) {
              if(entry.isIntersecting) {
                let image = entry.target;
                image.src = image.dataset.src;
                image.classList.remove("lazy-load");
                imageObserver.unobserve(image);
              }
            });
          });
          lazyloadImages.forEach(function(image) {
            imageObserver.observe(image);
          });
        } else {
          let lazyloadThrottleTimeout;
          lazyloadImages = document.querySelectorAll(".lazy-load");

          function lazyload() {
            if(lazyloadThrottleTimeout) {
              clearTimeout(lazyloadThrottleTimeout);
            }
            lazyloadThrottleTimeout = setTimeout(function() {
              let scrollTop = window.pageYOffset;
              lazyloadImages.forEach(function(img) {
                if(img.offsetTop < (window.innerHeight + scrollTop)) {
                  img.src = img.dataset.src;
                  img.classList.remove('lazy-load');
                }
              });
              if(lazyloadImages.length == 0) {
                document.removeEventListener("scroll", lazyload);
                window.removeEventListener("resize", lazyload);
                window.removeEventListener("orientationChange", lazyload);
              }
            }, 20);
          }
          document.addEventListener("scroll", lazyload);
          window.addEventListener("resize", lazyload);
          window.addEventListener("orientationChange", lazyload);
        }
      })
    </script>
    <?php include('bootstrapjs.php'); ?>
  </body>
</html>
