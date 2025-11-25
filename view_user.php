<?php 
    require ('../config/session_admin.php');
    require ('../config/db.php');
	if(isset($_SESSION['success_message'])){
        $success_message = $_SESSION['success_message'];
        unset($_SESSION['success_message']);
    }
    elseif(isset($_SESSION['error_message'])){
        $error_message = $_SESSION['error_message'];
        unset($_SESSION['error_message']);
    }

    $user_id = $_GET['user_id'] ?? null;
    
    if (!$user_id) {
        $_SESSION['error_message'] = "User ID does not exist.";
        header('Location: users.php');
        exit();
    }

    $stmt = $pdo->prepare("
        SELECT u.*, ud.*, ac.accountstatus_name
        FROM users u
        JOIN userdetails ud ON u.user_id = ud.user_id
        JOIN accountstatus ac ON u.accountstatus_id = ac.accountstatus_id
        WHERE u.user_id = :user_id
    ");
    $stmt->execute([':user_id' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // If the user does not exist, show an error
    if (!$user) {
        $_SESSION['error_message'] = "User not found.";
        header('Location: users.php');
        exit();
    }

    // Fetch user account status
    $status_options = ['Active', 'Disabled'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<meta name="description" content="Responsive Admin &amp; Dashboard Template based on Bootstrap 5">
	<meta name="author" content="AdminKit">
	<meta name="keywords" content="adminkit, bootstrap, bootstrap 5, admin, dashboard, template, responsive, css, sass, html, theme, front-end, ui kit, web">

	<link rel="preconnect" href="https://fonts.gstatic.com">
	<link rel="shortcut icon" href="img/icons/icon-48x48.png" />

	<link rel="canonical" href="https://demo-basic.adminkit.io/pages-blank.html" />

	<title>User #<?php echo $user['user_id'] ?></title>

	<link href="css/app.css" rel="stylesheet">
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
	<link href="css/custom.css" rel="stylesheet">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
	<link href="../assets/css/style.css" rel="stylesheet">
	<!-- DataTables CSS -->
	<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
	<!-- Responsive extension CSS -->
	<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.dataTables.min.css">
	
</head>

<body>
	<div class="wrapper">
		<?php $active = 'usermanagement'; ?>
		<?php require ('../includes/sidebar_admin.php');?>

		<div class="main">
			<?php require ('../includes/navbar_admin.php');?>

			<main class="content">
				<div class="container-fluid p-0">

				<div class="d-flex justify-content-between align-items-center mb-3">
                    <a href="users.php" class="btn btn-secondary btn-sm mb-3">
                        <i class="bi bi-arrow-left"></i> Back to Users
                    </a>
				</div>

					<div class="row">
						<div class="col-12">
							<div class="card">
								<div class="card-header">
									<h5 class="card-title mb-0">User #<?= $user['user_id'] ?></h5>
								</div>
								<div class="card-body">
                                    <!-- Display user info -->
                                    <div class="row mb-2">
                                        <div class="col-sm-4"><strong>Username:</strong></div>
                                        <div class="col-sm-8"><?= $user['username'] ?></div>
                                    </div>
                                    <div class="row mb-2">
                                        <div class="col-sm-4"><strong>Email:</strong></div>
                                        <div class="col-sm-8"><?= $user['email'] ?></div>
                                    </div>
                                    <div class="row mb-2">
                                        <div class="col-sm-4"><strong>Full Name:</strong></div>
                                        <div class="col-sm-8"><?= $user['firstname'] ?> <?= $user['lastname'] ?></div>
                                    </div>
                                    <div class="row mb-2">
                                        <div class="col-sm-4"><strong>Contact Number:</strong></div>
                                        <div class="col-sm-8"><?= $user['contact_number'] ?></div>
                                    </div>
                                    <div class="row mb-2">
                                        <div class="col-sm-4"><strong>Address:</strong></div>
                                        <div class="col-sm-8"><?= $user['address'] ?></div>
                                    </div>
                                    <div class="row mb-2">
                                        <div class="col-sm-4"><strong>Account Status:</strong></div>
                                        <div class="col-sm-8"><?= $user['accountstatus_name'] ?></div>
                                    </div>
                                    <div class="row mb-2">
                                        <div class="col-sm-4"><strong>Verified:</strong></div>
                                        <div class="col-sm-8">
                                            <?= $user['is_verified'] ? '<span class="badge bg-success"><i class="bi bi-check-circle"></i> Verified</span>' :
                                            '<span class="badge bg-danger"><i class="bi bi-x-circle"></i> Not Verified</span>' ?>
                                        </div>
                                    </div>
                                    <div class="row mb-2">
                                        <div class="col-sm-4"><strong>Account Status:</strong></div>
                                        <div class="col-sm-8">
                                            <?= $user['accountstatus_name'] == 'Active' ? '<span class="badge bg-success"><i class="bi bi-check-circle"></i> Active</span>' :
                                            '<span class="badge bg-danger"><i class="bi bi-x-circle"></i> Disabled</span>' ?>
                                        </div>
                                    </div>

                                    <!-- Edit and Delete Actions -->
                                    <div class="mt-3">
                                        <a href="edit_user.php?user_id=<?= $user['user_id'] ?>" class="btn btn-primary btn-sm">
                                            <i class="bi bi-pencil-square"></i> Edit User
                                        </a>
                                        <button class="btn btn-danger btn-sm delete-btn" data-user-id="<?= $user['user_id'] ?>">
                                            <i class="bi bi-trash"></i> Delete User
                                        </button>
                                    </div>
                                </div>

							</div>
						</div>
					</div>

				</div>
			</main>

			<footer class="footer">
				<div class="container-fluid">
					
				</div>
			</footer>
		</div>
	</div>
	

	<script src="js/app.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
	<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
	<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
	<!-- Responsive extension JS -->
	<script src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>

    <?php require 'modals/addproduct.php'?>
    <?php require 'modals/editproduct.php'?>
	
	
	<?php if(isset($success_message)): ?>
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: '<?php echo $success_message; ?>'
            });
        </script>
    <?php elseif(isset($error_message)): ?>
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: '<?php echo $error_message; ?>'
            });
        </script>
    <?php endif; ?>

    <script>
        // Populate the edit modal with the product data when Edit button is clicked
        function populateEditModal(productId) {
            // Make an AJAX call to fetch the product data based on productId
            fetch(`backend/fetch_product.php?id=${productId}`)
                .then(response => response.json())
                .then(data => {
                    // Populate the modal fieldss
                    document.getElementById('edit_product_name').value = data.product_name;
                    document.getElementById('edit_price').value = data.price;
                    document.getElementById('edit_bundle_price').value = data.bundle_price;
                    document.getElementById('edit_description').value = data.description;
                    document.getElementById('edit_stock').value = data.stock;
                    document.getElementById('edit_pieces_per_bundle').value = data.pieces_per_bundle;
                    document.getElementById('edit_category_id').value = data.category_id;
                    document.getElementById('edit_size').value = data.size;
                    document.getElementById('edit_material').value = data.material;
                    document.getElementById('edit_product_id').value = data.product_id;

                    // Show the existing image in the preview
                    document.getElementById('editImagePreview').src = `../uploads/products/${data.image_url}`;
                })
                .catch(error => console.error('Error fetching product data:', error));
        }
    </script>
    <script>
        function confirmDelete(productId) {
            Swal.fire({
                title: 'Are you sure?',
                text: "This product will be deleted permanently.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Redirect to PHP delete handler
                    window.location.href = `backend/deleteproduct.php?id=${productId}`;
                }
            });
        }
    </script>



</body>

</html>