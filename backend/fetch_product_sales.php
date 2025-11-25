<?php
require '../../config/db.php';
require '../../config/session_admin.php';

header('Content-Type: application/json');

try {
    // === Forecasted Product Sales (with month/year filter) ===
    if (isset($_GET['forecast']) && $_GET['forecast'] == 1) {
        // Get filter parameters
        $forecastMonth = $_GET['forecastmonth'] ?? null;
        $forecastYear = isset($_GET['forecastyear']) ? (int)$_GET['forecastyear'] : null;
        
        // Determine target month for forecast
        if ($forecastMonth && $forecastYear) {
            $targetMonthNum = (int)date('n', strtotime($forecastMonth . " 1"));
            $targetYear = $forecastYear;
        } else {
            // Default: next month
            $nextMonth = strtotime('+1 month');
            $targetMonthNum = (int)date('n', $nextMonth);
            $targetYear = (int)date('Y', $nextMonth);
        }
        
        // Fetch historical data (last 12 months before target)
        $sql = "
            SELECT p.product_name, YEAR(o.date) AS year, MONTH(o.date) AS month, SUM(oi.quantity) AS qty
            FROM order_items oi
            JOIN orders o ON oi.order_id = o.order_id
            JOIN products p ON oi.product_id = p.product_id
            WHERE o.status IN ('Delivered','Received')
            GROUP BY p.product_name, YEAR(o.date), MONTH(o.date)
            ORDER BY p.product_name, year DESC, month DESC";
        $stmt = $pdo->query($sql);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $productData = [];
        foreach ($rows as $r) {
            $product = $r['product_name'];
            $productData[$product][] = [
                'year' => (int)$r['year'],
                'month' => (int)$r['month'],
                'qty' => (int)$r['qty']
            ];
        }

        $forecast = [];
        foreach ($productData as $product => $history) {
            // Get last 12 months of data
            $recentHistory = array_slice($history, 0, 12);
            $quantities = array_column($recentHistory, 'qty');
            $n = count($quantities);

            if ($n >= 3) {
                // Calculate trend
                $x = range(1, $n);
                $y = $quantities;
                $x_mean = array_sum($x) / $n;
                $y_mean = array_sum($y) / $n;
                
                $num = 0; $den = 0;
                for ($i = 0; $i < $n; $i++) {
                    $num += ($x[$i] - $x_mean) * ($y[$i] - $y_mean);
                    $den += pow($x[$i] - $x_mean, 2);
                }
                $slope = $den ? $num / $den : 0;
                $intercept = $y_mean - $slope * $x_mean;
                
                // Predict for target month
                $prediction = $intercept + $slope * ($n + 1);
                
                // Check if this month has seasonal pattern
                $sameMonthHistory = array_filter($recentHistory, function($item) use ($targetMonthNum) {
                    return $item['month'] == $targetMonthNum;
                });
                
                if (count($sameMonthHistory) > 0) {
                    $sameMonthAvg = array_sum(array_column($sameMonthHistory, 'qty')) / count($sameMonthHistory);
                    // Blend trend with seasonal average (70% trend, 30% seasonal)
                    $prediction = 0.7 * $prediction + 0.3 * $sameMonthAvg;
                }
                
                // Add deterministic fluctuation based on product name and prediction
                // This ensures same data = same forecast (no random changes on refresh)
                $productSeed = array_sum(array_map('ord', str_split($product))) + (int)$prediction;
                $fluctuation = sin($productSeed) * 0.08; // ±8% deterministic
                $predicted = max(0, round($prediction * (1 + $fluctuation)));
            } else {
                // Fallback: average
                $avg = array_sum($quantities) / max(1, $n);
                $predicted = round($avg);
            }

            $forecast[] = [
                'product' => $product,
                'predicted' => $predicted
            ];
        }
        
        // Sort by predicted quantity and limit to top 6
        usort($forecast, function($a, $b) {
            return $b['predicted'] - $a['predicted'];
        });
        $forecast = array_slice($forecast, 0, 6);

        echo json_encode($forecast);
        exit;
    }

    // === Actual Product Sales ===
    $monthSelected = $_GET['piemonth'] ?? date('F');
    $yearSelected = isset($_GET['pieyear']) ? (int)$_GET['pieyear'] : (int)date('Y');
    $monthNumber = (int)date('n', strtotime($monthSelected . " 1"));

    $sql = "
        SELECT p.product_name AS product, SUM(oi.quantity) AS quantity
        FROM order_items oi
        JOIN orders o ON oi.order_id = o.order_id
        JOIN products p ON oi.product_id = p.product_id
        WHERE o.status IN ('Delivered','Received')
          AND MONTH(o.date) = :month
          AND YEAR(o.date) = :year
        GROUP BY p.product_name
        ORDER BY quantity DESC
        LIMIT 6";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['month' => $monthNumber, 'year' => $yearSelected]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($rows);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

