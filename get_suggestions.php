<?php
// Connect to the SQLite database using parameterized query
$db = new SQLite3('database.sqlite');

if (isset($_GET['q'])) {
  // Get the user input from the query parameter
  $input = $_GET['q'];

  // Fetch suggestions from the database that start with the user input
  $stmt = $db->prepare("SELECT DISTINCT tags FROM images WHERE tags LIKE :input");
  $stmt->bindValue(':input', $input . '%', SQLITE3_TEXT);
  $result = $stmt->execute();

  // Store the suggestions in an array
  $suggestions = array();
  while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $suggestions[] = $row['tags'];
  }

  // Send the suggestions as a JSON response
  echo json_encode($suggestions);
}
?>
