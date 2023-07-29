    <nav class="navbar navbar-expand-md navbar-expand-lg bg-body-tertiary mb-2">
      <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="index.php">ArtBOORU</a>
        <button class="btn btn-sm btn-outline-dark" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
          <i class="bi bi-list p-1 fs-5" style="-webkit-text-stroke: 1px;"></i>
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
              <a class="nav-link" href="tags.php">Tags</a>
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
            <input class="form-control me-2" name="search" type="search" placeholder="Search" aria-label="Search" onkeyup="showSuggestions(this, 'suggestions2')" />
            <button class="btn btn-outline-success" type="submit">Search</button>
          </form>
         <div id="suggestions2"></div> 
       </div>
      </div>
    </nav>
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
