    <nav class="navbar navbar-expand-md navbar-expand-lg bg-body-tertiary mb-2">
      <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="index.php">ArtCODE</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarSupportedContent">
          <ul class="navbar-nav me-auto mb-2 mb-lg-0 fw-bold">
            <li class="nav-item">
              <a class="nav-link" href="index.php">Home</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="imgupload.php">Upload</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="profile.php">Profile</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="setting.php">Settings</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="logout.php">Logout</a>
            </li>
          </ul>
          <form class="d-flex" role="search" action="search.php">
            <input class="form-control me-2" name="search" type="search" placeholder="Search" aria-label="Search" onkeyup="showSuggestions(this.value)">
            <button class="btn btn-outline-success" type="submit">Search</button>
          </form>
         <div class="dropdown" id="suggestions"></div>
       </div>
      </div>
    </nav>
    <script>
      function showSuggestions(input) {
        // Get the suggestions element
        var suggestionsElement = document.getElementById("suggestions");

        // Clear previous suggestions
        suggestionsElement.innerHTML = "";

        // If the input is empty, hide the suggestions
        if (input === "") {
          return;
        }

        // Fetch suggestions from the server using AJAX
        var xhttp = new XMLHttpRequest();
        xhttp.onreadystatechange = function() {
          if (this.readyState === 4 && this.status === 200) {
            var suggestions = JSON.parse(this.responseText);

            // Create a dropdown for suggestions using Bootstrap classes
            var dropdownDiv = document.createElement("div");
            dropdownDiv.classList.add("dropdown-menu", "show");

            suggestions.forEach(function(suggestion) {
              var a = document.createElement("a");
              a.classList.add("dropdown-item");
              a.textContent = suggestion;
              a.onclick = function() {
                // Set the clicked suggestion as the search input value
                document.querySelector(".form-control").value = suggestion;
                // Clear the suggestions
                suggestionsElement.innerHTML = "";
              };
              dropdownDiv.appendChild(a);
            });

            // Append the dropdown to the suggestions element
            suggestionsElement.appendChild(dropdownDiv);
          }
        };
        xhttp.open("GET", "get_suggestions.php?q=" + input, true);
        xhttp.send();
        }
      </script>