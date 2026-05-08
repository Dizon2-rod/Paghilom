<?php require_once __DIR__.'/bootstrap.php'; ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
  <title>Owner · Paghilom</title>
  <link rel="icon" type="image/png" href="<?= e(APP_URL) ?>assets/img/logo.png">
  <link rel="stylesheet" href="<?= e(APP_URL) ?>admin/assets/css/owner.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script></script>
  <style>
    @media print {
      .owner-layout > aside.sidebar {
        display: none !important;
      }
      .owner-layout > .content {
        margin-left: 0 !important;
        max-width: 100% !important;
      }
    }
  </style>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const menuToggle = document.getElementById('menuToggle');
      const sidebar = document.querySelector('.sidebar');
      const sidebarClose = document.querySelector('.sidebar-close');
      const overlay = document.createElement('div');
      overlay.className = 'sidebar-overlay';
      document.querySelector('.content').prepend(overlay);

      function toggleSidebar(show) {
        if (show) {
          sidebar.classList.add('active');
          document.body.classList.add('sidebar-open');
          overlay.style.display = 'block';
          setTimeout(() => overlay.style.opacity = '1', 10);
        } else {
          sidebar.classList.remove('active');
          document.body.classList.remove('sidebar-open');
          overlay.style.opacity = '0';
          setTimeout(() => overlay.style.display = 'none', 300);
        }
      }

      if (menuToggle) {
        menuToggle.addEventListener('click', (e) => {
          e.stopPropagation();
          toggleSidebar(true);
        });
      }

      if (sidebarClose) {
        sidebarClose.addEventListener('click', (e) => {
          e.stopPropagation();
          toggleSidebar(false);
        });
      }

      overlay.addEventListener('click', () => toggleSidebar(false));

      // Close on ESC key
      document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && sidebar.classList.contains('active')) {
          toggleSidebar(false);
        }
      });

      // Handle window resize
      function handleResize() {
        if (window.innerWidth > 992) {
          toggleSidebar(false);
        }
      }

      window.addEventListener('resize', handleResize);
      
      // Close sidebar when clicking a nav link on mobile
      document.querySelectorAll('.nav-link').forEach(link => {
        link.addEventListener('click', () => {
          if (window.innerWidth <= 992) {
            toggleSidebar(false);
          }
        });
      });
    });
  </script>
</head>
<body>
<div class="owner-layout">
  <?php include __DIR__.'/sidebar.php'; ?>
  <main class="content">
    <div class="topbar">
      <div class="title">Admin Dashboard</div>
      <div class="small"><?= date('l, F j, Y · g:i A') ?></div>
    </div>


