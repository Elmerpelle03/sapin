<?php
require __DIR__ . '/config/db.php';

// Fetch per-product monthly forecasts
$stmt = $pdo->query("SELECT f.product_id, p.product_name, f.forecast_date, f.forecast_qty
                     FROM forecasts f
                     JOIN products p ON p.product_id = f.product_id
                     ORDER BY p.product_name, f.forecast_date");
$rows = $stmt->fetchAll();

// Fetch best-sellers
$best = $pdo->query("SELECT b.month_key, b.product_id, p.product_name, b.forecast_qty
                     FROM forecast_best_sellers b
                     JOIN products p ON p.product_id = b.product_id
                     ORDER BY b.month_key")->fetchAll();

// Fetch metrics
$metrics = $pdo->query("SELECT m.product_id, p.product_name, m.metric_window, m.mape, m.mae, m.rmse, m.created_at
                        FROM forecast_metrics m
                        JOIN products p ON p.product_id = m.product_id
                        ORDER BY p.product_name")->fetchAll();

// Prepare data for Chart.js
$byProduct = [];
foreach ($rows as $r) {
	$pid = (int)$r['product_id'];
	$name = $r['product_name'];
	$byProduct[$pid]['name'] = $name;
	$byProduct[$pid]['points'][] = [
		'label' => $r['forecast_date'],
		'value' => (float)$r['forecast_qty']
	];
}

?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title>Forecasts</title>
	<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
	<style>
		body { font-family: Arial, sans-serif; max-width: 1100px; margin: 30px auto; }
		.grid { display: grid; grid-template-columns: 2fr 1fr; gap: 24px; }
		table { width: 100%; border-collapse: collapse; }
		th, td { border: 1px solid #ddd; padding: 8px; }
		th { background: #f5f5f5; text-align: left; }
		canvas { background: #fff; border: 1px solid #eee; padding: 8px; }
	</style>
</head>
<body>
	<h2>12-Month Forecasts</h2>

	<div class="grid">
		<div>
			<canvas id="lineChart"></canvas>
		</div>
		<div>
			<h3>Predicted Best Sellers (per month)</h3>
			<canvas id="barChart"></canvas>
		</div>
	</div>

	<h3 style="margin-top:28px;">Accuracy Metrics</h3>
	<table>
		<thead>
			<tr>
				<th>Product</th>
				<th>Window</th>
				<th>MAPE %</th>
				<th>MAE</th>
				<th>RMSE</th>
				<th>As of</th>
			</tr>
		</thead>
		<tbody>
		<?php foreach ($metrics as $m): ?>
			<tr>
				<td><?php echo htmlspecialchars($m['product_name']); ?></td>
				<td><?php echo htmlspecialchars($m['metric_window']); ?></td>
				<td><?php echo is_null($m['mape']) ? '-' : number_format((float)$m['mape'], 2); ?></td>
				<td><?php echo number_format((float)$m['mae'], 2); ?></td>
				<td><?php echo number_format((float)$m['rmse'], 2); ?></td>
				<td><?php echo htmlspecialchars($m['created_at']); ?></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>

	<script>
	const productSeries = <?php echo json_encode($byProduct, JSON_NUMERIC_CHECK); ?>;
	const bestRaw = <?php echo json_encode($best, JSON_NUMERIC_CHECK); ?>;

	// Line chart datasets per product
	const lineLabels = Array.from(new Set(Object.values(productSeries).flatMap(p => p.points.map(pt => pt.label)))).sort();
	const colors = ['#ff6384','#36a2eb','#cc65fe','#ffce56','#4bc0c0','#9966ff','#ff9f40','#8dd17e','#d62728','#1f77b4'];
	const datasets = Object.values(productSeries).map((p, idx) => {
		const map = new Map(p.points.map(pt => [pt.label, pt.value]));
		return {
			label: p.name,
			data: lineLabels.map(d => map.get(d) ?? null),
			borderColor: colors[idx % colors.length],
			fill: false,
			tension: 0.2
		};
	});
	const lineCtx = document.getElementById('lineChart').getContext('2d');
	new Chart(lineCtx, {
		type: 'line',
		data: { labels: lineLabels, datasets },
		options: {
			responsive: true,
			scales: { y: { beginAtZero: true } },
			plugins: { legend: { position: 'bottom' } }
		}
	});

	// Bar chart for best sellers
	const bestLabels = bestRaw.map(r => r.month_key);
	const bestData = bestRaw.map(r => r.forecast_qty);
	const bestNames = bestRaw.map(r => r.product_name);
	const barCtx = document.getElementById('barChart').getContext('2d');
	new Chart(barCtx, {
		type: 'bar',
		data: {
			labels: bestLabels,
			datasets: [{
				label: 'Best-seller forecast qty',
				data: bestData,
				backgroundColor: '#36a2eb'
			}]
		},
		options: {
			plugins: {
				legend: { display: true },
				tooltip: {
					callbacks: {
						label: (ctx) => `${bestNames[ctx.dataIndex]}: ${ctx.formattedValue}`
					}
				}
			}
		}
	});
	</script>
</body>
</html>


