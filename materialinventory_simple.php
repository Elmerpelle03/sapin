<?php
require __DIR__ . '/config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
	$name = trim($_POST['material_name'] ?? '');
	$unit = trim($_POST['unit'] ?? '');
	$stock = (float)($_POST['stock'] ?? 0);

	if ($name === '' || $unit === '') {
		$error = 'Material name and unit are required.';
	} else {
		$stmt = $pdo->prepare("INSERT INTO materials (material_name, unit, stock) VALUES (:n, :u, :s)");
		$stmt->execute([':n' => $name, ':u' => $unit, ':s' => $stock]);
		$success = 'Material added.';
	}
}

$materials = $pdo->query("SELECT material_id, material_name, unit, stock FROM materials ORDER BY material_name ASC")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title>Material Inventory (Simple)</title>
	<style>
		body { font-family: Arial, sans-serif; max-width: 900px; margin: 30px auto; }
		table { width: 100%; border-collapse: collapse; }
		th, td { border: 1px solid #ddd; padding: 8px; }
		th { background: #f5f5f5; text-align: left; }
		form > div { margin-bottom: 10px; }
	</style>
</head>
<body>
	<h2>Material Inventory (Simple)</h2>

	<?php if (!empty($error)): ?><p style="color:#b00020;"><?php echo htmlspecialchars($error); ?></p><?php endif; ?>
	<?php if (!empty($success)): ?><p style="color:#0a7d00;"><?php echo htmlspecialchars($success); ?></p><?php endif; ?>

	<h3>Add Material</h3>
	<form method="post">
		<input type="hidden" name="action" value="add">
		<div>
			<label>Material Name</label><br>
			<input type="text" name="material_name" required>
		</div>
		<div>
			<label>Unit (e.g., yards, rolls)</label><br>
			<input type="text" name="unit" required>
		</div>
		<div>
			<label>Initial Stock</label><br>
			<input type="number" step="0.01" name="stock" value="0">
		</div>
		<button type="submit">Add Material</button>
	</form>

	<h3 style="margin-top:30px;">Materials</h3>
	<table>
		<thead>
			<tr>
				<th>ID</th>
				<th>Name</th>
				<th>Unit</th>
				<th>Stock</th>
			</tr>
		</thead>
		<tbody>
		<?php foreach ($materials as $m): ?>
			<tr>
				<td><?php echo (int)$m['material_id']; ?></td>
				<td><?php echo htmlspecialchars($m['material_name']); ?></td>
				<td><?php echo htmlspecialchars($m['unit']); ?></td>
				<td><?php echo number_format((float)$m['stock'], 2); ?></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
</body>
</html>


