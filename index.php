<?php
session_start();
if (!isset($_SESSION['username'])) {
  header("Location: session.php");
  exit;
}

$username = $_SESSION['username'];

// Connect to the SQLite database using parameterized query
$db = new SQLite3('database.sqlite');
$stmt = $db->prepare("CREATE TABLE IF NOT EXISTS images (id INTEGER PRIMARY KEY AUTOINCREMENT, filename TEXT, username TEXT, tags TEXT, title TEXT, imgdesc TEXT, link TEXT)");
$stmt->execute();

// Function to create folders if they don't exist
function createFoldersIfNotExist()
{
  if (!file_exists('images')) {
    mkdir('images');
  }
  if (!file_exists('thumbnails')) {
    mkdir('thumbnails');
  }
}

// Call the function to create folders
createFoldersIfNotExist();

// Get all of the images from the database using parameterized query
$stmt = $db->prepare("SELECT * FROM images ORDER BY id DESC");
$result = $stmt->execute();
?>

<!DOCTYPE html>
  <html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ArtBOORU</title>
    <?php include('bootstrapcss.php'); ?>
  </head>
  <body>
    <form action="search.php" style="margin-top: 250px;" class="container position-absolute top-0 start-50 translate-middle w-100" method="GET">
      <h1 class="text-center fw-bold">ArtBOORU</h1>
      <p class="text-center"><small>simple image board</small></p>
      <div class="d-flex">
        <input class="form-control me-2" type="search" name="search" placeholder="Search" aria-label="Search" onkeyup="debouncedShowSuggestions(this, 'suggestions1')" />
        <button class="btn btn-outline-success" type="submit">Search</button>
      </div>
      <div class="dropdown" id="suggestions1"></div>
      <div class="mt-2 d-flex justify-content-center gap-2">
        <a class="fw-bold btn btn-sm" href="profile.php">profile</a>
        <a class="fw-bold btn btn-sm" href="setting.php">settings</a>
        <a class="fw-bold btn btn-sm" href="imgupload.php">upload</a>
        <a class="fw-bold btn btn-sm" href="tags.php">tags</a>
        <a class="fw-bold btn btn-sm text-danger" href="logout.php">logout</a>
      </div>
    </form>
    <script>
      var suggestedTags = {};

      function debounce(func, wait) {
        let timeout;
        return function (...args) {
          clearTimeout(timeout);
          timeout = setTimeout(() => {
            func.apply(this, args);
          }, wait);
        };
      }

      function showSuggestions(input, suggestionsId) {
        // Get the suggestions element
        var suggestionsElement = document.getElementById(suggestionsId);

        // Clear previous suggestions
        suggestionsElement.innerHTML = "";

        // If the input is empty, hide the suggestions
        var inputValue = input.value.trim();
        if (inputValue === "") {
          return;
        }

        // Fetch suggestions from the server using AJAX
        var xhttp = new XMLHttpRequest();
        xhttp.onreadystatechange = function () {
          if (this.readyState === 4 && this.status === 200) {
            var suggestions = JSON.parse(this.responseText);

            // Create a dropdown for suggestions using Bootstrap classes
            var dropdownDiv = document.createElement("div");
            dropdownDiv.classList.add("dropdown-menu", "show");

            // Clear the suggestedTags array before adding new suggestions
            suggestedTags[suggestionsId] = [];

            suggestions.forEach(function (suggestion) {
              // Check if the suggestion is not already in the suggestedTags array
              if (!suggestedTags[suggestionsId].includes(suggestion)) {
                suggestedTags[suggestionsId].push(suggestion);

                var a = document.createElement("a");
                a.classList.add("dropdown-item");
                a.textContent = suggestion;
                a.href = "#";
                a.onclick = function () {
                  addTag(input, suggestionsId, suggestion);
                };
                dropdownDiv.appendChild(a);
              }
            });

            // Append the dropdown to the suggestions element
            suggestionsElement.appendChild(dropdownDiv);
          }
        };
        xhttp.open("GET", "get_suggestions.php?q=" + inputValue, true);
        xhttp.send();
      }

      var debouncedShowSuggestions = debounce(showSuggestions, 300);
  
      function addTag(input, suggestionsId, tag) {
        // Get the current input value
        var currentValue = input.value.trim();

        // If the current input value is empty, set the clicked suggestion as the input value
        if (currentValue === "") {
          input.value = tag;
        } else {
          // Otherwise, add the clicked suggestion as a new tag
          var tags = currentValue.split(",").map(function (item) {
            return item.trim();
          });

          // Check if the tag is not already in the tags list
          if (!tags.includes(tag)) {
            // Check if the tag starts with the current input prefix
            var prefix = tags[tags.length - 1];
            if (tag.toLowerCase().startsWith(prefix.toLowerCase())) {
              // Remove the prefix from the new tag to avoid duplication
              var newTag = tag.slice(prefix.length).trim();

              // If there is a comma at the end of the prefix, remove it
              if (tags[tags.length - 1].endsWith(",")) {
                tags[tags.length - 1] = tags[tags.length - 1].slice(0, -1).trim();
              }

              // Add the new tag to the list without any whitespace
              tags[tags.length - 1] = tags[tags.length - 1] + newTag;
            } else {
              tags.push(tag);
            }

            input.value = tags.join(", ");
          }
        }

        // Clear the suggestions
        var suggestionsElement = document.getElementById(suggestionsId);
        suggestionsElement.innerHTML = "";
      }
    </script>
    <?php include('bootstrapjs.php'); ?>
  </body>
</html>
