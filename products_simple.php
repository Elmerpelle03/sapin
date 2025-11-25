<?php
require __DIR__ . '/config/db.php';

$materials = $pdo->query("SELECT material_id, material_name, unit, stock FROM materials ORDER BY material_name ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_product') {
	$product_name = trim($_POST['product_name'] ?? '');
	$material_id = (int)($_POST['material_id'] ?? 0);
	$qty_per_material = (float)($_POST['qty_per_material'] ?? 0);

	if ($product_name === '' || $material_id <= 0 || $qty_per_material < 0) {
		$error = 'All fields are required and must be valid (qty per material can be 0 for imported items).';
	} else {
		$stmt = $pdo->prepare("INSERT INTO products (product_name, material_id, qty_per_material) VALUES (:pn, :mid, :qpm)");
		$stmt->execute([':pn' => $product_name, ':mid' => $material_id, ':qpm' => $qty_per_material]);
		$success = 'Product added.';
	}
}

$sql = "SELECT p.product_id, p.product_name, p.qty_per_material, m.material_name, m.unit 
        FROM products p 
        JOIN materials m ON p.material_id = m.material_id
        ORDER BY p.product_name ASC";
$products = $pdo->query($sql)->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title>Products (Simple)</title>
	<style>
		body { font-family: Arial, sans-serif; max-width: 900px; margin: 30px auto; }
		table { width: 100%; border-collapse: collapse; }
		th, td { border: 1px solid #ddd; padding: 8px; }
		th { background: #f5f5f5; text-align: left; }
		form > div { margin-bottom: 10px; }
	</style>
</head>
<body>
	<h2>Products (Simple)</h2>

	<?php if (!empty($error)): ?><p style="color:#b00020;"><?php echo htmlspecialchars($error); ?></p><?php endif; ?>
	<?php if (!empty($success)): ?><p style="color:#0a7d00;"><?php echo htmlspecialchars($success); ?></p><?php endif; ?>

	<h3>Add Product</h3>
	<form method="post">
		<input type="hidden" name="action" value="add_product">
		<div>
			<label>Product Name</label><br>
			<input type="text" name="product_name" required>
		</div>
		<div>
			<label>Material</label><br>
			<select name="material_id" required>
				<option value="">-- Select Material --</option>
				<?php foreach ($materials as $mat): ?>
					<option value="<?php echo (int)$mat['material_id']; ?>">
						<?php echo htmlspecialchars($mat['material_name']); ?> (<?php echo number_format((float)$mat['stock'], 2).' '.$mat['unit']; ?>)
					</option>
				<?php endforeach; ?>
			</select>
		</div>
		<div>
			<label>Material consumption per product</label><br>
			<input type="number" step="0.0001" min="0" name="qty_per_material" placeholder="e.g., 2 for 2 yards; 0 for imported towels" required>
		</div>
		<button type="submit">Add Product</button>
	</form>

	<h3 style="margin-top:30px;">Product List</h3>
	<table>
		<thead>
			<tr>
				<th>ID</th>
				<th>Product</th>
				<th>Material</th>
				<th>Qty per Product</th>
			</tr>
		</thead>
		<tbody>
		<?php foreach ($products as $p): ?>
			<tr>
				<td><?php echo (int)$p['product_id']; ?></td>
				<td><?php echo htmlspecialchars($p['product_name']); ?></td>
				<td><?php echo htmlspecialchars($p['material_name']); ?></td>
				<td><?php echo rtrim(rtrim(number_format((float)$p['qty_per_material'], 4), '0'), '.'); ?> <?php echo htmlspecialchars($p['unit']); ?></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
</body>
</html>


