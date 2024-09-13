<?php
session_start();
session_regenerate_id(true);
if (!isset($_SESSION['user_id'])) {
  header("Location: ../login.php");
  exit();
}
if ($_SESSION['role'] !== 'admin') {
  header("Location: ../home.php");
  exit();
}
include("../assest/connection/config.php");
include("include/header.php");
include("include/topbar.php");
include("include/sidebar.php");
?>
<div class="content-wrapper">
  <!-- Content Header (Page header) -->
  <section class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1>Contact Us</h1>
        </div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="#">Home</a></li>
            <li class="breadcrumb-item active">Contact Us</li>
          </ol>
        </div>
      </div>
    </div><!-- /.container-fluid -->
  </section>

  <!-- Main content -->
  <section class="content">
    <!-- Default box -->
    <div class="card shadow-sm border-light">
      <div class="card-body row">
        <div class="col-md-12 text-center p-4">
          <h2>Outer<strong>Info</strong></h2>
          <p class="lead mb-4">Matunga Labour Camp<br>Mumbai 400019<br>Phone: +91 9136207140<br>shaikhmuzaffar206@gmail.com</p>
          <p class="text-muted">For further inquiries, please reach out to us via the contact information above.</p>
        </div>
      </div>
    </div>
  </section>
  <!-- /.content -->
</div>
<!-- /.content-wrapper -->
<?php include("include/footer.php"); ?>

<!-- Add CSS styling to enhance the design -->
<style>
  .content-wrapper {
    background-color: #f8f9fa;
  }

  .content-header h1 {
    font-size: 1.5rem;
    color: #343a40;
  }

  .breadcrumb-item a {
    color: #007bff;
  }

  .breadcrumb-item.active {
    color: #6c757d;
  }

  .card {
    border-radius: .5rem;
    overflow: hidden;
  }

  .card-body {
    background: #ffffff;
    padding: 2rem;
  }

  .card.shadow-sm {
    box-shadow: 0 .125rem .25rem rgba(0,0,0,.075);
  }

  .text-muted {
    color: #6c757d;
  }
</style>
