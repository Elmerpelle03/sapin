<nav id="sidebar" class="sidebar js-sidebar">
    <div class="sidebar-content js-simplebar d-flex flex-column justify-content-between">
        <div>
            <style>
                /* Minimal outline logout button */
                .btn-logout-modern{
                    display:inline-flex;align-items:center;justify-content:center;
                    width:32px;height:32px;border-radius:50%;
                    background: transparent;
                    color:#ffffff; border:1px solid rgba(255,255,255,0.45);
                    box-shadow:none;
                    transition:all .15s ease;
                }
                .btn-logout-modern:hover{ 
                    background: rgba(255,255,255,0.12);
                    border-color: rgba(255,255,255,0.65);
                    transform: translateY(-1px);
                }
                .btn-logout-modern i{ font-size:16px; }
            </style>
            <div class="d-flex align-items-center px-2 mt-2">
                <a class="sidebar-brand m-0 d-flex align-items-center gap-2 text-decoration-none" href="index.php">
                    <img src="../assets/img/logo_forsapin.jpg" alt="SAPIN" style="width:20px;height:20px;border-radius:4px;object-fit:cover;"/>
                    <span class="align-middle fw-semibold text-white" style="letter-spacing:.5px;">SAPIN</span>
                </a>
                <a id="sidebar-logout-link" class="btn-logout-modern ms-auto me-1" href="../auth/logout.php" data-bs-toggle="tooltip" data-bs-placement="right" title="Logout" aria-label="Logout">
                    <i class="align-middle" data-feather="log-out"></i>
                </a>
            </div>

            <!-- Sidebar Quick Search -->
            <div class="mt-3 px-3">
                <input id="sidebar-search" type="text" class="form-control form-control-sm" placeholder="Search menu..." />
            </div>

            <ul class="sidebar-nav">
                <li class="sidebar-header">
                    Pages
                </li>

                <li class="sidebar-item <?php echo ($active === 'index') ? 'active' : ''; ?>">
                    <a class="sidebar-link" href="index.php">
                        <i class="align-middle" data-feather="sliders"></i> <span class="align-middle">Dashboard</span>
                    </a>
                </li>

                

                <li class="sidebar-header">Operations</li>
                <li class="sidebar-item <?php echo ($active === 'materialinventory') ? 'active' : ''; ?>">
                    <a class="sidebar-link" href="materialinventory.php">
                        <i class="align-middle" data-feather="package"></i> <span class="align-middle">Material Inventory</span>
                    </a>
                </li>

                <li class="sidebar-item <?php echo ($active === 'supplier_requests') ? 'active' : ''; ?>">
                    <a class="sidebar-link" href="supplier_requests_history.php">
                        <i class="align-middle" data-feather="clock"></i> <span class="align-middle">Supplier Requests</span>
                    </a>
                </li>

                <li class="sidebar-header">Sales</li>
                <?php 
                    $stmt = $pdo->query("SELECT COUNT(*) FROM orders WHERE seen = 0");
                    $new_order_count = $stmt->fetchColumn();
                ?>
                <li class="sidebar-item <?php echo ($active === 'orders') ? 'active' : ''; ?>">
                    <a class="sidebar-link d-flex justify-content-between align-items-center" href="orders.php">
                        <div>
                            <i class="align-middle" data-feather="shopping-cart"></i>
                            <span class="align-middle">Orders</span>
                        </div>
                        <?php if ($new_order_count > 0): ?>
                            <span class="badge bg-danger ms-2"><?php echo $new_order_count; ?></span>
                        <?php endif; ?>
                    </a>
                </li>

                <?php 
                    $return_stmt = $pdo->query("SELECT COUNT(*) FROM return_requests WHERE return_status = 'Pending'");
                    $pending_returns = $return_stmt->fetchColumn();
                ?>
                <li class="sidebar-item <?php echo ($active === 'returns') ? 'active' : ''; ?>">
                    <a class="sidebar-link d-flex justify-content-between align-items-center" href="returns.php">
                        <div>
                            <i class="align-middle" data-feather="rotate-ccw"></i>
                            <span class="align-middle">Returns/Refunds</span>
                        </div>
                        <?php if ($pending_returns > 0): ?>
                            <span class="badge bg-danger ms-2"><?php echo $pending_returns; ?></span>
                        <?php endif; ?>
                    </a>
                </li>

                <?php 
                    // Check if cancellation_requests table exists before querying
                    try {
                        $cancel_stmt = $pdo->query("SELECT COUNT(*) FROM cancellation_requests WHERE status = 'pending'");
                        $pending_cancellations = $cancel_stmt->fetchColumn();
                    } catch (PDOException $e) {
                        // Table doesn't exist yet, set to 0
                        $pending_cancellations = 0;
                    }
                ?>
                <li class="sidebar-item <?php echo ($active === 'cancellations') ? 'active' : ''; ?>">
                    <a class="sidebar-link d-flex justify-content-between align-items-center" href="cancellation_requests.php">
                        <div>
                            <i class="align-middle" data-feather="x-circle"></i>
                            <span class="align-middle">Cancellations</span>
                        </div>
                        <?php if ($pending_cancellations > 0): ?>
                            <span class="badge bg-warning ms-2"><?php echo $pending_cancellations; ?></span>
                        <?php endif; ?>
                    </a>
                </li>

                <li class="sidebar-item <?php echo ($active === 'shipping') ? 'active' : ''; ?>">
                    <a class="sidebar-link" href="shipping.php">
                        <i class="align-middle" data-feather="truck"></i> <span class="align-middle">Shipping</span>
                    </a>
                </li>

                <li class="sidebar-header">Catalog & POS</li>
                <li class="sidebar-item <?php echo ($active === 'products') ? 'active' : ''; ?>">
                    <a class="sidebar-link" href="products.php">
                        <i class="align-middle" data-feather="shopping-bag"></i> <span class="align-middle">Product Inventory</span>
                    </a>
                </li>

                <li class="sidebar-item <?php echo ($active === 'pos') ? 'active' : ''; ?>">
                    <a class="sidebar-link" href="pos.php">
                        <i class="align-middle" data-feather="credit-card"></i> <span class="align-middle">POS</span>
                    </a>
                </li>

         

                <li class="sidebar-header">People</li>
                <?php if (isset($_SESSION['usertype_id']) && $_SESSION['usertype_id'] == 5): ?>
                    <li class="sidebar-item <?php echo ($active === 'adminmanagement') ? 'active' : ''; ?>">
                        <a class="sidebar-link" href="admins.php">
                            <i class="align-middle" data-feather="shield"></i> 
                            <span class="align-middle">Admin Management</span>
                        </a>
                    </li>
                <?php endif; ?>

                <li class="sidebar-item <?php echo ($active === 'usermanagement') ? 'active' : ''; ?>">
                    <a class="sidebar-link" href="users.php">
                        <i class="align-middle" data-feather="users"></i> <span class="align-middle">User Management</span>
                    </a>
                </li>

                <li class="sidebar-item <?php echo ($active === 'courier') ? 'active' : ''; ?>">
                    <a class="sidebar-link" href="courier.php">
                        <i class="align-middle" data-feather="compass"></i> <span class="align-middle">Courier</span>
                    </a>
                </li>

                <li class="sidebar-header">Wholesalers</li>
                <?php 
                    try {
                        $pending_apps_stmt = $pdo->query("SELECT COUNT(*) FROM bulk_buyer_applications WHERE status = 'Pending'");
                        $pending_apps = (int)$pending_apps_stmt->fetchColumn();
                    } catch (PDOException $e) {
                        $pending_apps = 0; // table may not exist on some environments
                    }
                ?>
                <li class="sidebar-item <?php echo ($active === 'bulk') ? 'active' : ''; ?>">
                    <a class="sidebar-link d-flex justify-content-between align-items-center" href="bulkbuyers.php">
                        <div>
                            <i class="align-middle" data-feather="book-open"></i> <span class="align-middle">Applications</span>
                        </div>
                        <?php if ($pending_apps > 0): ?>
                            <span class="badge bg-danger ms-2"><?php echo $pending_apps; ?></span>
                        <?php endif; ?>
                    </a>
                </li>

                <li class="sidebar-item <?php echo ($active === 'bulk_messages') ? 'active' : ''; ?>">
                    <a class="sidebar-link" href="bulk_messages.php">
                        <i class="align-middle" data-feather="message-square"></i> <span class="align-middle">Messages</span>
                        <?php
                        // Get unread message count
                        $unread_stmt = $pdo->query("SELECT COUNT(*) FROM bulk_buyer_messages WHERE sender_type = 'buyer' AND is_read = 0");
                        $unread_count = $unread_stmt->fetchColumn();
                        if($unread_count > 0):
                        ?>
                            <span class="badge bg-danger ms-auto"><?= $unread_count ?></span>
                        <?php endif; ?>
                    </a>
                </li>

                <li class="sidebar-item <?php echo ($active === 'manage_bulk_buyers') ? 'active' : ''; ?>">
                    <a class="sidebar-link" href="manage_bulk_buyers.php">
                        <i class="align-middle" data-feather="users"></i> <span class="align-middle">Manage Wholesalers</span>
                    </a>
                </li>

                <li class="sidebar-item <?php echo ($active === 'forecasting') ? 'active' : ''; ?>">
                    <a class="sidebar-link" href="forcasting.php">
                        <i class="align-middle" data-feather="trending-up"></i> <span class="align-middle">Leaderboards</span>
                    </a>
                </li>

                <li class="sidebar-item <?php echo ($active === 'reports') ? 'active' : ''; ?>">
                    <a class="sidebar-link" href="reports.php">
                        <i class="align-middle" data-feather="pie-chart"></i> <span class="align-middle">Reports</span>
                    </a>
                </li>

                <?php if (isset($_SESSION['usertype_id']) && $_SESSION['usertype_id'] == 5): ?>
                    <li class="sidebar-header">Finance</li>
                    <li class="sidebar-item <?php echo ($active === 'expenses') ? 'active' : ''; ?>">
                        <a class="sidebar-link" href="expenses.php">
                            <i class="align-middle" data-feather="credit-card"></i> <span class="align-middle">Expenses</span>
                        </a>
                    </li>

                    <li class="sidebar-item <?php echo ($active === 'profitloss') ? 'active' : ''; ?>">
                        <a class="sidebar-link" href="profitloss.php">
                            <i class="align-middle" data-feather="bar-chart-2"></i> <span class="align-middle">Profit & Loss</span>
                        </a>
                    </li>
                <?php endif; ?>

            </ul>
            <script>
            (function(){
                const input = document.getElementById('sidebar-search');
                if (!input) return;
                const nav = document.querySelector('#sidebar .sidebar-nav');
                if (!nav) return;
                const getText = el => (el.textContent || '').toLowerCase();
                function filterMenu(q){
                    const items = Array.from(nav.querySelectorAll('li.sidebar-item'));
                    items.forEach(li => {
                        const label = getText(li);
                        li.style.display = q === '' || label.includes(q) ? '' : 'none';
                    });
                    const headers = Array.from(nav.querySelectorAll('li.sidebar-header'));
                    // Hide headers that have no visible items below them (until next header)
                    headers.forEach((hdr, idx) => {
                        const nextHdr = headers[idx + 1] || null;
                        let hasVisible = false;
                        let sib = hdr.nextElementSibling;
                        while (sib && sib !== nextHdr) {
                            if (sib.classList && sib.classList.contains('sidebar-item') && sib.style.display !== 'none') {
                                hasVisible = true; break;
                            }
                            sib = sib.nextElementSibling;
                        }
                        hdr.style.display = hasVisible || q === '' ? '' : 'none';
                    });
                }
                input.addEventListener('input', function(){
                    filterMenu(this.value.trim().toLowerCase());
                });
            })();
            </script>
            <script>
            // Persist sidebar scroll and reveal active item (robust)
            (function(){
                const KEY = 'sidebar_scroll_top';
                function getScroller(){
                    // Try common containers in order
                    const cands = [
                        document.querySelector('#sidebar .sidebar-content'),
                        document.querySelector('#sidebar .js-simplebar'),
                        document.querySelector('#sidebar')
                    ].filter(Boolean);
                    for (const el of cands){
                        if (el.scrollHeight > el.clientHeight) return el;
                    }
                    return cands[0] || null;
                }
                function restore(){
                    const scroller = getScroller();
                    if (!scroller) return;
                    const saved = parseInt(localStorage.getItem(KEY) || '0', 10) || 0;
                    scroller.scrollTop = saved;
                    // Ensure active item is visible
                    const active = document.querySelector('#sidebar li.sidebar-item.active');
                    if (active){
                        const sRect = scroller.getBoundingClientRect();
                        const aRect = active.getBoundingClientRect();
                        const inView = aRect.top >= sRect.top && aRect.bottom <= sRect.bottom;
                        if (!inView){
                            active.scrollIntoView({ block: 'center', behavior: 'auto' });
                        }
                    }
                    // Save on scroll and before unload
                    scroller.addEventListener('scroll', () => {
                        localStorage.setItem(KEY, String(scroller.scrollTop));
                    }, { passive: true });
                    window.addEventListener('beforeunload', () => {
                        localStorage.setItem(KEY, String(scroller.scrollTop));
                    });
                }
                // Run after load and again on next frame to beat any layout scripts
                window.addEventListener('load', () => {
                    restore();
                    requestAnimationFrame(() => setTimeout(restore, 0));
                });
            })();
            </script>
            <script>
            // Initialize Bootstrap tooltip for the logout button if available
            (function(){
                if (window.bootstrap && document.querySelector('#sidebar-logout-link')){
                    try { new bootstrap.Tooltip(document.querySelector('#sidebar-logout-link')); } catch(e) {}
                }
            })();
            </script>
            <script>
            // SweetAlert confirm before logout (fallback to confirm)
            (function(){
                const link = document.getElementById('sidebar-logout-link');
                if (!link) return;
                link.addEventListener('click', function(e){
                    e.preventDefault();
                    const href = this.href;
                    if (window.Swal && typeof Swal.fire === 'function') {
                        Swal.fire({
                            title: 'Are you sure?',
                            text: 'Do you really want to logout?',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonText: 'Yes, logout',
                            cancelButtonText: 'Cancel',
                            reverseButtons: true
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.location.href = href;
                            }
                        });
                    } else {
                        if (window.confirm('Are you sure you want to logout?')) {
                            window.location.href = href;
                        }
                    }
                });
            })();
            </script>
        </div>
    </div>
</nav>