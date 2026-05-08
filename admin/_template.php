<?php
// Start output buffering
ob_start();
include __DIR__.'/includes/header.php';

// Your page content here
$pageTitle = "Page Title"; // Set this for each page
?>

<div class="topbar">
  <button class="hamburger-menu" id="hamburgerMenu" aria-label="Toggle menu">
    <span></span>
    <span></span>
    <span></span>
  </button>
  <div class="title"><?= htmlspecialchars($pageTitle) ?></div>
  <!-- Add any action buttons here -->
  <div class="ml-auto">
    <!-- Example button -->
    <!-- <a href="add_item.php" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Add New</a> -->
  </div>
</div>

<div class="content">
  <div class="container-fluid">
    <!-- Your page content goes here -->
    
    <!-- Example card structure -->
    <!--
    <div class="card">
      <div class="card-header">
        <h5 class="card-title">Card Title</h5>
      </div>
      <div class="card-body">
        <!-- Card content -->
      </div>
    </div>
    -->
    
  </div>
</div>

<?php 
// End output buffering and include footer
ob_end_flush();
include __DIR__.'/includes/footer.php'; 
?>
