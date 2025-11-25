
<?php 
    if(isset($_SESSION['user_id'])){
        $stmt = $pdo->prepare("SELECT a.user_id, a.usertype_id, a.username, a.email, a.is_verified, a.accountstatus_id, a.join_date,
        b.firstname, b.lastname, CONCAT(b.house, ' ', tb.barangay_name, ', ', tm.municipality_name, ', ', tp.province_name) AS address, b.house,
        b.contact_number,
        c.usertype_name
        FROM users a 
        LEFT JOIN userdetails b ON a.user_id = b.user_id
        LEFT JOIN usertype c ON a.usertype_id = c.usertype_id
        LEFT JOIN table_region tr ON b.region_id = tr.region_id
        LEFT JOIN table_province tp ON b.province_id = tp.province_id
        LEFT JOIN table_municipality tm ON b.municipality_id = tm.municipality_id
        LEFT JOIN table_barangay tb ON b.barangay_id = tb.barangay_id
        WHERE a.user_id = :user_id");
        $stmt->execute([':user_id' => $_SESSION['user_id']]);
        $user_data = $stmt->fetch();
    }
?>
<nav class="navbar navbar-expand navbar-light navbar-bg">
    <!-- Single Hamburger for both mobile and desktop -->
    <a class="sidebar-toggle js-sidebar-toggle" style="cursor: pointer;">
        <i class="hamburger align-self-center"></i>
    </a>

    <div class="navbar-collapse collapse">
        <ul class="navbar-nav navbar-align">
            <li class="nav-item dropdown">
                <a class="nav-icon dropdown-toggle d-inline-block d-sm-none" href="#" data-bs-toggle="dropdown">
                    <i class="align-middle" data-feather="settings"></i>
                </a>

                <!-- Notifications Dropdown -->
                <?php
                    // Fetch unread notifications count for admin (delivery + return requests)
                    $notif_count_stmt = $pdo->prepare("
                        SELECT COUNT(*) 
                        FROM notifications 
                        WHERE user_id = :user_id 
                        AND is_read = 0 
                        AND (title LIKE '%Delivered%' OR title LIKE '%Return%')
                    ");
                    $notif_count_stmt->execute([':user_id' => $_SESSION['user_id']]);
                    $unread_count = $notif_count_stmt->fetchColumn();
                    
                    // Add unread wholesaler messages count
                    $unread_msg_stmt = $pdo->query("SELECT COUNT(*) FROM bulk_buyer_messages 
                                                     WHERE sender_type = 'buyer' AND is_read = 0");
                    $unread_messages_count = $unread_msg_stmt->fetchColumn();
                    $unread_count += $unread_messages_count;

                    // Fetch recent notifications (delivery + return requests)
                    $notif_stmt = $pdo->prepare("
                        SELECT notification_id, order_id, title, message, type, is_read, created_at 
                        FROM notifications 
                        WHERE user_id = :user_id 
                        AND (title LIKE '%Delivered%' OR title LIKE '%Return%')
                        ORDER BY created_at DESC 
                        LIMIT 10
                    ");
                    $notif_stmt->execute([':user_id' => $_SESSION['user_id']]);
                    $notifications = $notif_stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    // Fetch recent wholesaler messages
                    $msg_stmt = $pdo->query("
                        SELECT m.message_id, m.user_id, m.message, m.is_read, m.created_at,
                               CONCAT(ud.firstname, ' ', ud.lastname) as buyer_name
                        FROM bulk_buyer_messages m
                        JOIN users u ON m.user_id = u.user_id
                        JOIN userdetails ud ON u.user_id = ud.user_id
                        WHERE m.sender_type = 'buyer'
                        ORDER BY m.created_at DESC
                        LIMIT 5
                    ");
                    $bulk_messages = $msg_stmt->fetchAll(PDO::FETCH_ASSOC);
                ?>
                <li class="nav-item dropdown me-3">
                    <a class="nav-link position-relative p-2" href="#" id="notificationDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-bell-fill text-dark" style="font-size: 1.3rem;"></i>
                        <?php if ($unread_count > 0): ?>
                            <span class="position-absolute badge rounded-pill bg-danger d-flex align-items-center justify-content-center" 
                                  style="font-size: 0.65rem; top: 0; right: -8px; min-width: 20px; height: 20px; padding: 0 6px;">
                                <?= $unread_count > 9 ? '9+' : $unread_count ?>
                            </span>
                        <?php endif; ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0" aria-labelledby="notificationDropdown" style="width: 380px; max-height: 550px; overflow-y: auto; overflow-x: hidden; border-radius: 12px;">
                        <li class="dropdown-header bg-primary text-white d-flex justify-content-between align-items-center py-3" style="border-radius: 12px 12px 0 0; position: sticky; top: 0; z-index: 10;">
                            <span><i class="bi bi-bell me-2"></i><strong>Notifications</strong></span>
                            <?php if ($unread_count > 0): ?>
                                <button class="btn btn-sm btn-light" style="font-size: 0.75rem; padding: 2px 8px; white-space: nowrap;" onclick="markAllAsRead()">
                                    <i class="bi bi-check-all me-1"></i>Mark all read
                                </button>
                            <?php endif; ?>
                        </li>
                        <?php if (empty($notifications) && empty($bulk_messages)): ?>
                            <li class="text-center py-5 text-muted">
                                <i class="bi bi-bell-slash" style="font-size: 3rem; opacity: 0.3;"></i>
                                <p class="mt-2 mb-0">No notifications</p>
                            </li>
                        <?php else: ?>
                            <?php foreach ($bulk_messages as $msg): ?>
                                <li>
                                    <a class="dropdown-item py-3 px-3 <?= $msg['is_read'] ? '' : 'bg-light border-start border-success border-3' ?>" 
                                       href="bulk_messages.php?user_id=<?= $msg['user_id'] ?>"
                                       style="transition: all 0.2s; white-space: normal; <?= $msg['is_read'] ? '' : 'background-color: #f8f9fa !important;' ?>">
                                        <div class="d-flex align-items-start">
                                            <div class="flex-shrink-0 me-3">
                                                <div class="rounded-circle d-flex align-items-center justify-content-center" 
                                                     style="width: 40px; height: 40px; min-width: 40px; background-color: #d1f4e0;">
                                                    <i class="bi bi-chat-dots-fill text-success" style="font-size: 1.2rem;"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1" style="min-width: 0; overflow: hidden;">
                                                <div class="d-flex justify-content-between align-items-start mb-1">
                                                    <strong class="text-dark" style="font-size: 0.9rem; word-wrap: break-word; flex: 1;">
                                                        <?= htmlspecialchars($msg['buyer_name']) ?> sent a message
                                                    </strong>
                                                    <?php if (!$msg['is_read']): ?>
                                                        <span class="badge bg-success rounded-pill ms-2 flex-shrink-0" style="font-size: 0.65rem;">New</span>
                                                    <?php endif; ?>
                                                </div>
                                                <p class="mb-1 text-muted" style="font-size: 0.85rem; line-height: 1.4; word-wrap: break-word; overflow-wrap: break-word;">
                                                    <?= htmlspecialchars(strlen($msg['message']) > 60 ? substr($msg['message'], 0, 60) . '...' : $msg['message']) ?>
                                                </p>
                                                <small class="text-muted d-flex align-items-center" style="font-size: 0.75rem;">
                                                    <i class="bi bi-clock me-1"></i>
                                                    <?php
                                                        $time_diff = time() - strtotime($msg['created_at']);
                                                        if ($time_diff < 60) echo 'Just now';
                                                        elseif ($time_diff < 3600) echo floor($time_diff / 60) . ' min ago';
                                                        elseif ($time_diff < 86400) echo floor($time_diff / 3600) . ' hr ago';
                                                        else echo date('M d, Y', strtotime($msg['created_at']));
                                                    ?>
                                                </small>
                                            </div>
                                        </div>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                            <?php foreach ($notifications as $notif): ?>
                                <?php 
                                    // Determine redirect URL based on notification type
                                    $redirect_url = 'view_order.php?order_id=' . $notif['order_id'];
                                    $isReturn = stripos($notif['title'], 'Return') !== false;
                                    if ($isReturn) {
                                        $redirect_url = 'returns.php?highlight=' . $notif['order_id'];
                                    }
                                ?>
                                <li>
                                    <a class="dropdown-item py-3 px-3 <?= $notif['is_read'] ? '' : 'bg-light border-start border-primary border-3' ?>" 
                                       href="<?= $redirect_url ?>" 
                                       onclick="markAsRead(<?= $notif['notification_id'] ?>)"
                                       style="transition: all 0.2s; white-space: normal; <?= $notif['is_read'] ? '' : 'background-color: #f8f9fa !important;' ?>">
                                        <div class="d-flex align-items-start">
                                            <div class="flex-shrink-0 me-3">
                                                <div class="rounded-circle d-flex align-items-center justify-content-center" 
                                                     style="width: 40px; height: 40px; min-width: 40px; background-color: <?= $notif['type'] === 'success' ? '#d4edda' : ($notif['type'] === 'warning' ? '#fff3cd' : '#d1ecf1') ?>;">
                                                    <i class="bi bi-<?= $notif['type'] === 'success' ? 'check-circle-fill text-success' : ($notif['type'] === 'warning' ? 'exclamation-triangle-fill text-warning' : 'info-circle-fill text-info') ?>" style="font-size: 1.2rem;"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1" style="min-width: 0; overflow: hidden;">
                                                <div class="d-flex justify-content-between align-items-start mb-1">
                                                    <strong class="text-dark" style="font-size: 0.9rem; word-wrap: break-word; flex: 1;"><?= htmlspecialchars($notif['title']) ?></strong>
                                                    <?php if (!$notif['is_read']): ?>
                                                        <span class="badge bg-primary rounded-pill ms-2 flex-shrink-0" style="font-size: 0.65rem;">New</span>
                                                    <?php endif; ?>
                                                </div>
                                                <p class="mb-1 text-muted" style="font-size: 0.85rem; line-height: 1.4; word-wrap: break-word; overflow-wrap: break-word;">
                                                    <?= htmlspecialchars($notif['message']) ?>
                                                </p>
                                                <small class="text-muted d-flex align-items-center" style="font-size: 0.75rem;">
                                                    <i class="bi bi-clock me-1"></i>
                                                    <?= date('M j, g:i A', strtotime($notif['created_at'])) ?>
                                                </small>
                                            </div>
                                        </div>
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider m-0"></li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </li>

                <li class="nav-item dropdown me-3">
                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-fill me-1"></i>
                        <span class="text-dark"><?php echo htmlspecialchars($user_data['firstname']); ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <li>
                            <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#userModal">View Profile</a>
                        </li>
                        <li>
                            <a class="dropdown-item" id="logoutBtn">Logout</a>
                        </li>
                    </ul>
                </li>
            </li>
        </ul>
    </div>
</nav>

<!-- Modal for User Info -->
<div class="modal fade" id="userModal" tabindex="-1" aria-labelledby="userModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="userModalLabel">User Profile</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p><strong>Username:</strong> <?php echo htmlspecialchars($user_data['username']); ?></p>
                <p><strong>Full Name:</strong> <?php echo $user_data['firstname'].' '.$user_data['lastname']; ?></p>
                <p><strong>Address:</strong> <?php echo htmlspecialchars($user_data['address'] ?? ''); ?></p>
                <p><strong>Contact Number:</strong> <?php echo htmlspecialchars($user_data['contact_number']); ?></p>
                <p><strong>Email:</strong> 
                    <?php echo htmlspecialchars($user_data['email']); ?>
                    <?php if (!empty($user_data['is_verified']) && $user_data['is_verified']): ?>
                        <span class="text-primary ms-2" title="Verified">
                            <i class="bi bi-check-circle-fill"></i> Verified
                        </span>
                    <?php else: ?>
                        <span class="text-danger ms-2" title="Not Verified">
                            <i class="bi bi-x-circle-fill"></i> Not Verified
                        </span>
                        <a href="../verify_email.php" class="ms-2 text-primary">Verify now</a>
                    <?php endif; ?>
                </p>
                <p><strong>Joined:</strong> <?php echo date('F j, Y', strtotime($user_data['join_date'])); ?></p>
            </div>
            <div class="modal-footer">
                <a href="../edit_profile.php" class="btn btn-primary">Edit Profile</a>
            </div>
        </div>
    </div>
</div>

<style>
    .custom-spinner {
      width: 3rem;
      height: 3rem;
      border: 0.4rem solid #f3f3f3;
      border-top: 0.4rem solid var(--bs-primary);
      border-radius: 50%;
      animation: spin 1s linear infinite;
      margin: 1rem auto;
    }

    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }

    /* Sidebar Toggle Styles - Enhanced for mobile */
    .sidebar-toggle {
        cursor: pointer;
        transition: all 0.3s ease;
        padding: 15px !important;
        display: inline-flex !important;
        align-items: center;
        justify-content: center;
    }
    
    .sidebar-toggle:hover {
        opacity: 0.7;
    }
    
    /* Make hamburger icon larger and more visible on mobile */
    @media (max-width: 767.98px) {
        .sidebar-toggle {
            padding: 18px !important;
            min-width: 60px !important;
            min-height: 60px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            background: rgba(0,0,0,0.05) !important;
            border-radius: 8px !important;
            margin-left: 8px !important;
            z-index: 10000 !important;
            position: relative !important;
        }
        
        .sidebar-toggle .hamburger {
            transform: scale(1.5) !important;
            color: #333 !important;
        }
        
        .sidebar-toggle:active {
            background: rgba(0,0,0,0.1) !important;
        }
    }

    /* Desktop - Let app.js handle sidebar, just ensure smooth transition */
    @media (min-width: 992px) {
        .sidebar {
            transition: margin-left 0.3s ease-in-out, transform 0.3s ease-in-out;
        }
    }

    /* Mobile and Tablet navbar and dropdown adjustments */
    @media (max-width: 991.98px) {
        /* Mobile navbar adjustments */
        .navbar {
            padding-left: 0.5rem !important;
            padding-right: 0.5rem !important;
        }
        
        /* Notification dropdown mobile - FIX CUT OFF */
        .dropdown-menu {
            position: fixed !important;
            width: 95vw !important;
            max-width: 380px !important;
            right: 10px !important;
            left: auto !important;
            top: 60px !important;
            max-height: 80vh !important;
            overflow-y: auto !important;
        }
        
        /* Notification dropdown specific positioning */
        #notificationDropdown + .dropdown-menu {
            right: 5px !important;
            left: auto !important;
            transform: none !important;
        }
    }
    
    /* Overlay for mobile sidebar */
    .sidebar-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        z-index: 1040;
    }
    
    .sidebar-overlay.active {
        display: block;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const logoutBtn = document.getElementById('logoutBtn');
    const modalElement = document.getElementById('userModal');
    const modal = modalElement ? new bootstrap.Modal(modalElement) : null;

    const customSwal = (title, text) => {
        Swal.fire({
            title: title,
            html: `
                <div class="custom-spinner mb-2"></div>
                <p>${text}</p>
            `,
            showConfirmButton: false,
            allowOutsideClick: false,
            allowEscapeKey: false
        });
    };

    if (logoutBtn) {
        logoutBtn.addEventListener('click', function (e) {
            console.log('Logout button clicked');
            e.preventDefault();
            
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
                    console.log('Logout confirmed');
                    customSwal('Logging out...', 'Please wait...');
                    
                    // Hide modal if it exists
                    if (modal) {
                        try {
                            modal.hide();
                        } catch(e) {
                            console.error('Error hiding modal:', e);
                        }
                    }
                    
                    // Remove backdrop
                    const backdrop = document.querySelector('.modal-backdrop');
                    if (backdrop) {
                        backdrop.remove();
                    }
                    
                    setTimeout(() => {
                        window.location.href = '../auth/logout.php';
                    }, 1000);
                }
            });
        });
        console.log('Logout button handler attached');
    } else {
        console.error('Logout button not found!');
    }

    // Notification functions
    window.markAsRead = function(notificationId) {
        fetch('backend/mark_notifications_read.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'notification_id=' + notificationId
        });
    };

    window.markAllAsRead = function() {
        fetch('backend/mark_notifications_read.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'mark_all=1'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
    };
    
    // SIDEBAR HANDLER - Works with AdminKit app.js
    const sidebar = document.querySelector('.sidebar');
    const hamburger = document.querySelector('.js-sidebar-toggle');
    
    if (!sidebar || !hamburger) {
        console.error('Sidebar or hamburger not found!');
        return;
    }
    
    // Create overlay for mobile
    let overlay = document.querySelector('.sidebar-overlay');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.className = 'sidebar-overlay';
        document.body.appendChild(overlay);
    }
    
    // Initialize sidebar position on mobile
    if (window.innerWidth < 992) {
        sidebar.style.position = 'fixed';
        sidebar.style.left = '-280px';
        sidebar.style.width = '280px';
        sidebar.style.top = '0';
        sidebar.style.bottom = '0';
        sidebar.style.zIndex = '99999';
        console.log('Mobile detected - initialized sidebar position');
    }
    
    // Add mobile-specific handler (increased breakpoint to 992px for tablets)
    hamburger.addEventListener('click', function(e) {
        const isMobile = window.innerWidth < 992;
        
        console.log('=== HAMBURGER CLICKED ===');
        console.log('Screen Width:', window.innerWidth);
        console.log('Screen Height:', window.innerHeight);
        console.log('Is Mobile:', isMobile);
        console.log('Orientation:', window.innerWidth > window.innerHeight ? 'landscape' : 'portrait');
        
        if (isMobile) {
            // Mobile/Tablet: use our custom mobile-open class
            e.preventDefault();
            e.stopPropagation();
            
            const isOpen = sidebar.classList.contains('mobile-open');
            console.log('Sidebar currently open:', isOpen);
            console.log('Sidebar element:', sidebar);
            
            if (isOpen) {
                console.log('CLOSING sidebar');
                sidebar.classList.remove('mobile-open');
                overlay.classList.remove('active');
                document.body.style.overflow = '';
                
                // Force inline styles for closing
                sidebar.style.left = '-280px';
            } else {
                console.log('OPENING sidebar');
                sidebar.classList.add('mobile-open');
                overlay.classList.add('active');
                document.body.style.overflow = 'hidden';
                
                // FORCE inline styles to override everything
                sidebar.style.position = 'fixed';
                sidebar.style.left = '0';
                sidebar.style.top = '0';
                sidebar.style.bottom = '0';
                sidebar.style.width = '280px';
                sidebar.style.zIndex = '99999';
                sidebar.style.background = '#ffffff';
                sidebar.style.display = 'block';
                sidebar.style.visibility = 'visible';
                sidebar.style.transform = 'translateX(0)';
                
                // Force reflow
                void sidebar.offsetWidth;
            }
            
            console.log('Sidebar classes after toggle:', sidebar.className);
            console.log('Computed left:', window.getComputedStyle(sidebar).left);
            console.log('Computed width:', window.getComputedStyle(sidebar).width);
            console.log('Computed position:', window.getComputedStyle(sidebar).position);
            console.log('Computed z-index:', window.getComputedStyle(sidebar).zIndex);
            console.log('Computed display:', window.getComputedStyle(sidebar).display);
            console.log('Computed transform:', window.getComputedStyle(sidebar).transform);
            console.log('=========================');
        }
        // Desktop: let app.js handle it (don't prevent default)
    });
    
    // Overlay click to close (mobile only)
    overlay.addEventListener('click', function() {
        if (window.innerWidth < 992) {
            sidebar.classList.remove('mobile-open');
            overlay.classList.remove('active');
            document.body.style.overflow = '';
            sidebar.style.left = '-280px';
        }
    });
    
    // Close sidebar when clicking menu items on mobile
    const menuLinks = sidebar.querySelectorAll('.sidebar-link');
    menuLinks.forEach(function(link) {
        link.addEventListener('click', function() {
            if (window.innerWidth < 992 && sidebar.classList.contains('mobile-open')) {
                setTimeout(function() {
                    sidebar.classList.remove('mobile-open');
                    overlay.classList.remove('active');
                    document.body.style.overflow = '';
                    sidebar.style.left = '-280px';
                }, 150);
            }
        });
    });
    
    // Clean up mobile classes on resize to desktop
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            if (window.innerWidth >= 992) {
                sidebar.classList.remove('mobile-open');
                overlay.classList.remove('active');
                document.body.style.overflow = '';
            }
        }, 250);
    });
    
    // SMART FIX - Only add manual handler if Bootstrap dropdown doesn't work
    setTimeout(function() {
        const userDropdown = document.getElementById('userDropdown');
        
        if (!userDropdown) return;
        
        // Check if Bootstrap dropdown is working
        let bootstrapWorking = false;
        
        if (typeof bootstrap !== 'undefined') {
            const instance = bootstrap.Dropdown.getInstance(userDropdown);
            if (instance) {
                bootstrapWorking = true;
                console.log('Bootstrap dropdown working - no manual fix needed');
            } else {
                // Try to initialize
                try {
                    new bootstrap.Dropdown(userDropdown);
                    bootstrapWorking = true;
                    console.log('Bootstrap dropdown initialized successfully');
                } catch(e) {
                    console.log('Bootstrap initialization failed:', e);
                }
            }
        }
        
        // Only add manual handler if Bootstrap is NOT working
        if (!bootstrapWorking) {
            console.log('Adding manual dropdown handler for POS');
            
            const newDropdown = userDropdown.cloneNode(true);
            userDropdown.parentNode.replaceChild(newDropdown, userDropdown);
            
            newDropdown.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('Manual dropdown clicked');
                
                const menu = this.nextElementSibling;
                if (menu) {
                    const isOpen = menu.classList.contains('show');
                    
                    // Close all dropdowns
                    document.querySelectorAll('.dropdown-menu').forEach(function(m) {
                        m.classList.remove('show');
                    });
                    
                    // Toggle this one
                    if (!isOpen) {
                        menu.classList.add('show');
                    }
                }
            });
            
            // Close on outside click
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.dropdown')) {
                    document.querySelectorAll('.dropdown-menu.show').forEach(function(m) {
                        m.classList.remove('show');
                    });
                }
            });
        }
    }, 1000);
});
</script>