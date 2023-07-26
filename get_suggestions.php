<?php
// Connect to the SQLite database using parameterized query
$db = new SQLite3('database.sqlite');

if (isset($_GET['q'])) {
  // Get the user input from the query parameter
  $input = $_GET['q'];

  // Separate the input by commas to get individual words
  $words = explode(',', $input);

  // Fetch all tags from the database
  $stmt = $db->prepare("SELECT DISTINCT tags FROM images");
  $result = $stmt->execute();

  // Store all tags in a flat array
  $allTags = array();
  while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $tags = explode(',', $row['tags']);
    foreach ($tags as $tag) {
      $suggestion = trim($tag);
      if (!in_array($suggestion, $allTags)) {
        $allTags[] = $suggestion;
      }
    }
  }

  // Filter the tags based on each word and store the suggestions
  $suggestions = array();
  foreach ($words as $word) {
    $trimmedWord = trim($word);
    foreach ($allTags as $tag) {
      // Check if the tag starts with the word
      if (stripos($tag, $trimmedWord) === 0) {
        $suggestions[] = $tag;
      }
    }
  }

  // Send the suggestions as a JSON response
  echo json_encode($suggestions);
}
?>
