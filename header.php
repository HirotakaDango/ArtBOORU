    <?php
    $current_page_header = basename($_SERVER['PHP_SELF']);
    $has_query = !empty($_SERVER['QUERY_STRING']);
    ?>
    
    <nav class="navbar navbar-expand-md navbar-expand-lg mb-2">
      <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="index.php">ArtBOORU</a>
        <button class="mb-1 btn d-md-none d-lg-none btn-sm p-0 border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
          <i class="bi bi-list p-1 fs-1 link-body-emphasis" style="-webkit-text-stroke: 1px;"></i>
        </button>
        <div class="collapse navbar-collapse" id="navbarSupportedContent">
          <ul class="navbar-nav me-auto mb-2 mb-lg-0">
            <li class="nav-item">
              <a class="nav-link <?php echo ($current_page_header == 'index.php' && !$has_query) ? 'active' : ''; ?>" href="index.php">Home</a>
            </li>
            <li class="nav-item">
              <a class="nav-link <?php echo ($current_page_header == 'upload.php') ? 'active' : ''; ?>" href="upload.php">Upload</a>
            </li>
            <li class="nav-item">
              <a class="nav-link <?php echo (isset($_GET['tags']) && $_GET['tags'] == 'all') ? 'active' : ''; ?>" href="?tags=all">Tags</a>
            </li>
            <li class="nav-item">
              <a class="nav-link <?php echo (isset($_GET['artists']) && $_GET['artists'] == 'all') ? 'active' : ''; ?>" href="?artists=all">Users</a>
            </li>
            <?php if (isset($_SESSION['email'])): ?>
              <li class="nav-item">
                <a class="nav-link <?php echo (isset($_GET['uid']) && $_GET['uid'] == $user['uid']) ? 'active' : ''; ?>" href="?uid=<?php echo $user['uid']; ?>">Profile</a>
              </li>
              <li class="nav-item">
                <a class="nav-link <?php echo ($current_page_header == 'settings.php') ? 'active' : ''; ?>" href="settings.php">Settings</a>
              </li>
            <?php else: ?>
              <li class="nav-item">
                <a class="nav-link" href="session.php">Login/Register</a>
              </li>
            <?php endif; ?>
          </ul>
          <form class="d-flex" role="search" action="index.php">
            <input class="form-control me-2" name="q" type="search" placeholder="Search" aria-label="Search"/>
            <button class="btn btn-outline-success" type="submit">Search</button>
          </form>
        </div>
      </div>
    </nav>
