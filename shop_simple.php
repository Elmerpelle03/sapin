<?php
require __DIR__ . '/config/db.php';

$products = $pdo->query("
	SELECT p.product_id, p.product_name, m.material_name, m.unit
	FROM products p
	JOIN materials m ON p.material_id = m.material_id
	ORDER BY p.product_name ASC
")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'sell') {
	$product_id = (int)($_POST['product_id'] ?? 0);
	$qty = (int)($_POST['qty'] ?? 0);

	if ($product_id <= 0 || $qty <= 0) {
		$error = 'Select a product and enter a valid quantity.';
	} else {
		try {
			$pdo->beginTransaction();

			$stmt = $pdo->prepare("
				SELECT p.product_id, p.qty_per_material, m.material_id, m.material_name, m.unit, m.stock
				FROM products p
				JOIN materials m ON p.material_id = m.material_id
				WHERE p.product_id = :pid
				FOR UPDATE
			");
			$stmt->execute([':pid' => $product_id]);
			$row = $stmt->fetch();

			if (!$row) { throw new RuntimeException('Product not found.'); }

			$use_per_unit = (float)$row['qty_per_material'];
			$required = $use_per_unit * $qty; // may be 0 for imported items
			$current_stock = (float)$row['stock'];

			if ($required > 0 && $required > $current_stock) {
				throw new RuntimeException('Insufficient material stock: need '.$required.' '.$row['unit'].', available '.$current_stock.' '.$row['unit'].'.');
			}

			$ins = $pdo->prepare("INSERT INTO sales (product_id, qty) VALUES (:pid, :qty)");
			$ins->execute([':pid' => $product_id, ':qty' => $qty]);

			if ($required > 0) {
				$upd = $pdo->prepare("UPDATE materials SET stock = stock - :deduct WHERE material_id = :mid");
				$upd->execute([':deduct' => $required, ':mid' => (int)$row['material_id']]);
			}

			$pdo->commit();
			$success = $required > 0
				? ('Sale recorded. Deducted '.rtrim(rtrim(number_format($required, 4), '0'), '.').' '.$row['unit'].' of '.$row['material_name'].'.')
				: 'Sale recorded (no material deduction for imported item).';
		} catch (Throwable $e) {
			if ($pdo->inTransaction()) $pdo->rollBack();
			$error = $e->getMessage();
		}
	}
}
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title>Shop - Record Sale (Simple)</title>
	<style>
		body { font-family: Arial, sans-serif; max-width: 700px; margin: 30px auto; }
		form > div { margin-bottom: 10px; }
	</style>
</head>
<body>
	<h2>Record Sale (Simple)</h2>

	<?php if (!empty($error)): ?><p style="color:#b00020;"><?php echo htmlspecialchars($error); ?></p><?php endif; ?>
	<?php if (!empty($success)): ?><p style="color:#0a7d00;"><?php echo htmlspecialchars($success); ?></p><?php endif; ?>

	<form method="post">
		<input type="hidden" name="action" value="sell">
		<div>
			<label>Product</label><br>
			<select name="product_id" required>
				<option value="">-- Select Product --</option>
				<?php foreach ($products as $p): ?>
					<option value="<?php echo (int)$p['product_id']; ?>">
						<?php echo htmlspecialchars($p['product_name']); ?> (<?php echo htmlspecialchars($p['material_name']); ?>)
					</option>
				<?php endforeach; ?>
			</select>
		</div>
		<div>
			<label>Quantity</label><br>
			<input type="number" name="qty" min="1" step="1" required>
		</div>
		<button type="submit">Record Sale</button>
	</form>
</body>
</html>


