<nav id="sidebar" class="sidebar js-sidebar">
    <div class="sidebar-content js-simplebar d-flex flex-column justify-content-between">
        <div>
            <a class="sidebar-brand" href="index.php">
                <span class="align-middle">Sapin Bedsheets</span>
            </a>

            <ul class="sidebar-nav">
                <li class="sidebar-header">
                    Pages
                </li>

                <li class="sidebar-item <?php echo ($active === 'index') ? 'active' : ''; ?>">
                    <a class="sidebar-link" href="index.php">
                        <i class="align-middle" data-feather="sliders"></i> <span class="align-middle">Dashboard</span>
                    </a>
                </li>

                <li class="sidebar-item <?php echo ($active === 'deliveries') ? 'active' : ''; ?>">
                    <a class="sidebar-link" href="deliveries.php">
                        <i class="align-middle" data-feather="package"></i> <span class="align-middle">Deliveries</span>
                    </a>
                </li>

                <li class="sidebar-item mt-5">
                    <a class="sidebar-link" href="../auth/logout.php">
                        <i class="align-middle" data-feather="log-out"></i> <span class="align-middle">Logout</span>
                    </a>
                </li>

                
            </ul>
        </div>
    </div>
</nav>