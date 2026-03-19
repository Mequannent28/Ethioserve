<?php $current_page = basename($_SERVER['PHP_SELF']); ?>
<!-- Teacher Sidebar -->
<nav id="sidebarMenu" class="teacher-sidebar">
    <div class="sidebar-inner">
        <div class="sidebar-brand">
            <a href="../customer/index.php" class="text-decoration-none d-flex align-items-center gap-2">
                <i class="fas fa-chalkboard-teacher text-warning fa-2x"></i>
                <div>
                    <h4 class="text-white fw-bold mb-0">Ethio<span class="text-warning">Edu</span></h4>
                    <small class="text-white-50">Teacher Portal</small>
                </div>
            </a>
        </div>

        <div class="sidebar-nav-wrapper">
            <ul class="nav flex-column sidebar-nav">
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                        <i class="fas fa-home"></i> <span>Dashboard</span>
                    </a>
                </li>

                <li class="nav-section-title"><span>ACADEMICS</span></li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'my_classes.php' ? 'active' : ''; ?>" href="my_classes.php">
                        <i class="fas fa-school"></i> <span>My Classes</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'my_students.php' ? 'active' : ''; ?>" href="my_students.php">
                        <i class="fas fa-user-graduate"></i> <span>My Students</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'attendance.php' ? 'active' : ''; ?>" href="attendance.php">
                        <i class="fas fa-calendar-check"></i> <span>Mark Attendance</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'warning_list.php' ? 'active' : ''; ?>" href="warning_list.php">
                        <i class="fas fa-exclamation-triangle"></i> <span>Warning List</span>
                    </a>
                </li>

                <li class="nav-section-title"><span>LMS & CONTENT</span></li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'manage_courses.php' ? 'active' : ''; ?>" href="manage_courses.php">
                        <i class="fas fa-book"></i> <span>Course Materials</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'assignments.php' ? 'active' : ''; ?>" href="assignments.php">
                        <i class="fas fa-tasks"></i> <span>Assignments</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'exams.php' ? 'active' : ''; ?>" href="exams.php">
                        <i class="fas fa-file-signature"></i> <span>Manage Exams</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-info" href="../customer/lms.php" target="_blank">
                        <i class="fas fa-external-link-alt"></i> <span>View Portal (LMS)</span>
                    </a>
                </li>

                <li class="nav-section-title"><span>COMMUNICATION</span></li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'chat.php' ? 'active' : ''; ?>" href="chat.php">
                        <i class="fas fa-comments"></i> <span>Chat & Messages</span>
                    </a>
                </li>
            </ul>
        </div>

        <div class="sidebar-footer">
            <a class="nav-link text-danger" href="../logout.php">
                <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
            </a>
        </div>
    </div>
</nav>

<style>
    .teacher-sidebar {
        position: fixed; left: 0; top: 0; bottom: 0; width: 260px;
        background: #1B5E20; z-index: 1000; box-shadow: 4px 0 15px rgba(0,0,0,0.1);
    }
    .sidebar-brand { padding: 30px 20px; border-bottom: 1px solid rgba(255,255,255,0.1); }
    .nav-section-title { padding: 20px 20px 10px; color: rgba(255,255,255,0.4); font-size: 0.7rem; font-weight: bold; text-transform: uppercase; list-style: none; }
    .sidebar-nav .nav-link { color: rgba(255,255,255,0.7); padding: 12px 20px; margin: 2px 15px; border-radius: 8px; transition: all 0.3s; text-decoration: none; display: flex; align-items: center; gap: 12px; }
    .sidebar-nav .nav-link:hover { background: rgba(255,255,255,0.1); color: #fff; }
    .sidebar-nav .nav-link.active { background: #F9A825; color: #1B5E20; font-weight: bold; box-shadow: 0 4px 10px rgba(249,168,37,0.3); }
    .sidebar-footer { padding: 20px; border-top: 1px solid rgba(255,255,255,0.1); }
</style>
