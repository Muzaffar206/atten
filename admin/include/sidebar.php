 <!-- Main Sidebar Container -->
 <aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo -->
    <a href="index.php" class="brand-link">
      <img src="../assest/images/MESCO.png" alt="MESCO Logo" style="background-color: white; border-radius: 15px;" width="120px" >
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
      <!-- Sidebar user panel (optional) -->
      <div class="user-panel mt-3 pb-3 mb-3 d-flex">
        <div class="info">
          <a href="#" class="d-block">ADMIN</a>
        </div>
      </div>

      <!-- SidebarSearch Form -->
      <div class="form-inline">
        <div class="input-group" data-widget="sidebar-search">
          <input class="form-control form-control-sidebar" type="search" placeholder="Search" aria-label="Search">
          <div class="input-group-append">
            <button class="btn btn-sidebar">
              <i class="fas fa-search fa-fw"></i>
            </button>
          </div>
        </div>
      </div>

      <!-- Sidebar Menu -->
      <nav class="mt-2">
        <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
          <!-- Add icons to the links using the .nav-icon class
               with font-awesome or any other icon font library -->
          <li class="nav-item menu-open">
            <a href="#" class="nav-link active">
              <i class="nav-icon fas fa-tachometer-alt"></i>
              <p>
                Dashboard
                <i class="right fas fa-angle-left"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="./index.php" class="nav-link <?php echo ($activePage === 'home') ? 'active' : ''; ?>">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Home</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="./registration.php" class="nav-link <?php echo ($activePage === 'registration') ? 'active' : ''; ?>">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Create a new user</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="./users.php" class="nav-link <?php echo ($activePage === 'employee') ? 'active' : ''; ?>">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Total Employee</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="./attendance_report.php" class="nav-link <?php echo ($activePage === 'attendance_report') ? 'active' : ''; ?>">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Attendance report</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="./get_atten.php" class="nav-link <?php echo ($activePage === 'monthly_attendance') ? 'active' : ''; ?>">
                                <i class="far fa-circle nav-icon"></i>
                                <p>M/Y Attendance</p>
                            </a>
                        </li>
                    </ul>
          </li>
          
        </ul>
      </nav>
      <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
  </aside>