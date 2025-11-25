<?php
// refresh_deliveries.php - Backend endpoint for refreshing deliveries table
require_once('../config/session_courier.php');
require_once('../config/db.php');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // Fetch active deliveries for the logged-in courier
    $stmt = $pdo->prepare("SELECT o.order_id, o.fullname, o.house, b.barangay_name, m.municipality_name, p.province_name, o.status FROM orders o LEFT JOIN table_barangay b ON o.barangay_id=b.barangay_id LEFT JOIN table_municipality m ON o.municipality_id=m.municipality_id LEFT JOIN table_province p ON o.province_id=p.province_id WHERE o.rider_id = :rider AND o.status IN ('Pending','Processing','Shipping') ORDER BY o.date DESC LIMIT 50");
    $stmt->execute([':rider' => $user_id]);
    $active = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Generate HTML for the deliveries table body
    ob_start();
    if(empty($active)):
    ?>
        <tr>
            <td colspan="5" class="py-4 text-center text-slate-500">
                <div class="flex flex-col items-center gap-2">
                    <i class="bi bi-inbox text-4xl text-slate-300"></i>
                    <span>No active deliveries at the moment.</span>
                </div>
            </td>
        </tr>
    <?php else:
        foreach($active as $row):
            $addr = trim(($row['house'] ?? '').' '.($row['barangay_name'] ?? '').', '.($row['municipality_name'] ?? '').', '.($row['province_name'] ?? ''));
            $badge = 'bg-gray-100 text-gray-700 border-gray-200';
            $icon = 'bi-clock';
            $statusLabel = 'Pending';

            switch($row['status']) {
                case 'Pending':
                    $badge = 'bg-gradient-to-r from-gray-100 to-gray-200 text-gray-800 border-gray-300 shadow-sm';
                    $icon = 'bi-clock';
                    $statusLabel = 'Pending';
                    break;
                case 'Processing':
                    $badge = 'bg-gradient-to-r from-orange-100 to-orange-200 text-orange-800 border-orange-300 shadow-sm';
                    $icon = 'bi-bicycle';
                    $statusLabel = 'Out for Delivery';
                    break;
                case 'Shipping':
                    $badge = 'bg-gradient-to-r from-blue-100 to-blue-200 text-blue-800 border-blue-300 shadow-sm';
                    $icon = 'bi-truck';
                    $statusLabel = 'On Route';
                    break;
                case 'Delivered':
                    $badge = 'bg-gradient-to-r from-emerald-100 to-emerald-200 text-emerald-800 border-emerald-300 shadow-sm';
                    $icon = 'bi-check2-circle';
                    $statusLabel = 'Delivered';
                    break;
                case 'Cancelled':
                    $badge = 'bg-gradient-to-r from-red-100 to-red-200 text-red-800 border-red-300 shadow-sm';
                    $icon = 'bi-x-circle';
                    $statusLabel = 'Failed/Returned';
                    break;
                default:
                    $badge = 'bg-gradient-to-r from-gray-100 to-gray-200 text-gray-800 border-gray-300 shadow-sm';
                    $icon = 'bi-clock';
                    $statusLabel = $row['status'];
            }
        ?>
        <tr class="hover:bg-gradient-to-r hover:from-blue-50 hover:to-indigo-50 hover:shadow-md transition-all duration-300 cursor-pointer group transform hover:scale-[1.02] border-b border-slate-100" data-status="<?= htmlspecialchars($row['status']) ?>" data-id="<?= (int)$row['order_id'] ?>" data-addr="<?= htmlspecialchars($addr) ?>" data-receiver="<?= htmlspecialchars($row['fullname']) ?>">
            <td class="py-1 px-1 sm:px-2 font-semibold w-24 sm:w-32">JT<?= (int)$row['order_id'] ?>PH</td>
            <td class="py-1 px-1 sm:px-2 w-32 sm:w-48 truncate" title="<?= htmlspecialchars($row['fullname']) ?>"><?= htmlspecialchars($row['fullname']) ?></td>
            <td class="py-1 px-1 sm:px-2 flex-1 min-w-0 truncate" title="<?= htmlspecialchars($addr) ?>"><?= htmlspecialchars($addr) ?></td>
            <td class="py-1 px-1 sm:px-2 w-24 sm:w-32"><span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 <?= $badge ?>"><i class="bi bi-<?= $icon ?>"></i> <?= $statusLabel ?></span></td>
            <td class="py-1 px-1 sm:px-2 text-center w-40 sm:w-56">
                <div class="flex items-center justify-center gap-0.5 sm:gap-1 opacity-90 group-hover:opacity-100 transition-opacity duration-200">
                    <button type="button" class="btn-view inline-flex items-center gap-1 px-1.5 sm:px-2 py-1 text-xs font-medium text-blue-700 bg-gradient-to-r from-blue-50 to-blue-100 hover:from-blue-100 hover:to-blue-200 border border-blue-200 rounded-lg transition-all duration-200 hover:shadow-sm transform hover:scale-105 cursor-pointer" data-id="<?= (int)$row['order_id'] ?>" title="View Details">
                        <i class="bi bi-eye text-xs sm:text-sm"></i>
                        <span class="hidden sm:inline">View</span>
                    </button>
                    <button type="button" class="btn-navigate inline-flex items-center gap-1 px-1.5 sm:px-2 py-1 text-xs font-medium text-green-700 bg-gradient-to-r from-green-50 to-green-100 hover:from-green-100 hover:to-green-200 border border-green-200 rounded-lg transition-all duration-200 hover:shadow-sm transform hover:scale-105 cursor-pointer" data-id="<?= (int)$row['order_id'] ?>" title="Navigate to Location">
                        <i class="bi bi-geo-alt text-xs sm:text-sm"></i>
                        <span class="hidden sm:inline">Navigate</span>
                    </button>
                    <button type="button" class="btn-mark-delivered inline-flex items-center gap-1 px-1.5 sm:px-2 py-1 text-xs font-medium text-emerald-700 bg-gradient-to-r from-emerald-50 to-emerald-100 hover:from-emerald-100 hover:to-emerald-200 border border-emerald-200 rounded-lg transition-all duration-200 hover:shadow-sm transform hover:scale-105 cursor-pointer" data-id="<?= (int)$row['order_id'] ?>" title="Mark as Delivered">
                        <i class="bi bi-check2-circle text-xs sm:text-sm"></i>
                        <span class="hidden sm:inline">Delivered</span>
                    </button>
                    <button type="button" class="btn-failed inline-flex items-center gap-1 px-1.5 sm:px-2 py-1 text-xs font-medium text-red-700 bg-gradient-to-r from-red-50 to-red-100 hover:from-red-100 hover:to-red-200 border border-red-200 rounded-lg transition-all duration-200 hover:shadow-sm transform hover:scale-105 cursor-pointer" data-id="<?= (int)$row['order_id'] ?>" title="Mark as Failed">
                        <i class="bi bi-x-circle text-xs sm:text-sm"></i>
                        <span class="hidden sm:inline">Failed</span>
                    </button>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
    <?php endif;

    $html = ob_get_clean();
    echo $html;

} catch (PDOException $e) {
    http_response_code(500);
    echo '<tr><td colspan="5" class="py-4 text-center text-red-500">Database error: ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
} catch (Exception $e) {
    http_response_code(500);
    echo '<tr><td colspan="5" class="py-4 text-center text-red-500">Server error: ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
}
?>
