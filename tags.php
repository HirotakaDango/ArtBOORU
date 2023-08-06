<?php
session_start();
if (!isset($_SESSION['username'])) {
  header("Location: session.php");
  exit;
}

// Connect to the SQLite database
$db = new SQLite3('database.sqlite');

// Retrieve all tags used in the images table
$result = $db->query("SELECT DISTINCT tags FROM images");

// Store the tags as an array
$tags = [];
while ($row = $result->fetchArray()) {
  $tagList = explode(',', $row['tags']);
  foreach ($tagList as $tag) {
    $tags[] = htmlspecialchars(trim($tag));
  }
}
$tags = array_unique($tags);

// Filter out any empty tags
$tags = array_filter($tags);

?>

<!DOCTYPE html>
<html>
  <head>
    <title>Tags</title>
    <meta charset="UTF-8"> 
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php include('bootstrapcss.php'); ?>
  </head>
  <body>
    <?php include('header.php'); ?>
    <div class="container-fluid">
      <h3 class="text-secondary ms-2 mt-3 fw-bold"><i class="bi bi-tags-fill"></i> tags</h3>
      <div class="input-group mb-3">
        <input type="text" class="form-control" placeholder="Search tags" id="search-input">
      </div>
      <!-- Display the tags as a group of buttons -->
      <?php foreach ($tags as $tag): ?>
        <?php
          // Check if the tag has any associated images
          $stmt = $db->prepare("SELECT COUNT(*) FROM images WHERE tags LIKE ?");
          $stmt->bindValue(1, '%' . $tag . '%');
          $countResult = $stmt->execute()->fetchArray()[0];
          if ($countResult > 0):
        ?>
          <a href="tagged_images.php?tag=<?php echo urlencode($tag); ?>"
             class="tag-button btn btn-sm btn-outline-dark mb-1">
            <?php echo $tag; ?>
          </a>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>
    <script>
      // Get the search input element
      const searchInput = document.getElementById('search-input');

      // Get all the tag buttons
      const tagButtons = document.querySelectorAll('.tag-button');

      // Add an event listener to the search input field
      searchInput.addEventListener('input', () => {
        const searchTerm = searchInput.value.toLowerCase();

        // Filter the tag buttons based on the search term
        tagButtons.forEach(button => {
          const tag = button.textContent.toLowerCase();

          if (tag.includes(searchTerm)) {
            button.style.display = 'inline-block';
          } else {
            button.style.display = 'none';
          }
        });
      });
    </script>
    <?php include('bootstrapjs.php'); ?>
  </body>
</html>