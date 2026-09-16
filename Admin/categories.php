<?php
require_once '../auth.php';
check_access('auction_admin');
require_once 'db_connection.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories Management - PPL</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="auction_style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/pretium-glass-theme.css">
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container my-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold m-0" style="font-family: 'Poppins', sans-serif; color: #0f2a4a; letter-spacing: -0.5px;">CATEGORIES <span style="color: #0284c7;">MANAGEMENT</span></h2>
                <p class="text-secondary mb-0 small">Configure player categories and groupings</p>
            </div>
            <button class="btn rounded-pill px-4 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#addCategoryModal" style="background: linear-gradient(135deg, #0284c7, #0ea5e9); color: #ffffff; border: none; font-family: 'Poppins', sans-serif;">
                <i class="fas fa-plus me-2"></i> Add Category
            </button>
        </div>

        <div class="row g-4">
            <?php
            try {
                $sql = "SELECT id, category_name FROM categories ORDER BY category_name ASC";
                $stmt = $pdo->query($sql);

                while ($row = $stmt->fetch()) {
                    ?>
                    <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                        <div class="ppl-card text-center h-100 d-flex flex-column align-items-center position-relative pb-5" style="border: 1px solid rgba(2, 132, 199, 0.15); box-shadow: 0 10px 25px rgba(15, 42, 74, 0.05); border-radius: 16px; overflow: hidden; background: #ffffff;">
                            <div class="my-auto py-4 px-3">
                                <div class="d-inline-flex align-items-center justify-content-center mb-3" style="width: 54px; height: 54px; border-radius: 50%; background: rgba(2, 132, 199, 0.1); color: #0284c7; font-size: 1.3rem;">
                                    <i class="fas fa-layer-group"></i>
                                </div>
                                <h4 class="m-0 fw-bold" style="font-size: 1.25rem; color: #0f2a4a; font-family: 'Poppins', sans-serif;">
                                    <?php echo htmlspecialchars($row['category_name']); ?>
                                </h4>
                            </div>
                            
                            <!-- Action Buttons -->
                            <div class="position-absolute bottom-0 w-100 p-2 d-flex justify-content-center gap-2" style="background: #f8fafc; border-top: 1px solid #e2e8f0; border-bottom-left-radius: 16px; border-bottom-right-radius: 16px;">
                                <button class="btn btn-sm btn-outline-primary rounded-pill px-3" title="Edit Category" onclick="event.stopPropagation(); openEditModal(<?php echo htmlspecialchars(json_encode([
                                    'id' => $row['id'],
                                    'category_name' => $row['category_name']
                                ])); ?>)">
                                    <i class="fas fa-edit me-1"></i> Edit
                                </button>
                                <button class="btn btn-sm btn-outline-danger rounded-pill px-3" title="Delete Category" onclick="event.stopPropagation(); deleteCategory(<?php echo $row['id']; ?>)">
                                    <i class="fas fa-trash me-1"></i> Delete
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php
                }
            } catch (PDOException $e) {
                echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
            }
            ?>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Add Category Modal -->
    <div class="modal fade" id="addCategoryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form id="addCategoryForm" class="modal-content" style="background: #ffffff; border: 1px solid rgba(2, 132, 199, 0.2); color: #0f2a4a; border-radius: 20px; box-shadow: 0 25px 50px rgba(15, 42, 74, 0.2);">
                <div class="modal-header border-bottom" style="border-color: #f1f5f9 !important;">
                    <h5 class="modal-title fw-bold" style="color: #0f2a4a;"><i class="fas fa-layer-group me-2" style="color: #0284c7;"></i>Add New Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div id="addAlert" class="alert d-none"></div>
                    <div class="mb-3">
                        <label class="form-label small text-uppercase fw-bold" style="color: #64748b;">Category Name *</label>
                        <input type="text" name="category_name" class="form-control" style="background: #f8fafc; border: 1px solid #cbd5e1; color: #0f2a4a; font-weight: 500; border-radius: 10px;" required>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn rounded-pill px-4 fw-bold text-white shadow-sm" style="background: linear-gradient(135deg, #0284c7, #0ea5e9); border: none;">Save Category</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Category Modal -->
    <div class="modal fade" id="editCategoryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form id="editCategoryForm" class="modal-content" style="background: #ffffff; border: 1px solid rgba(2, 132, 199, 0.2); color: #0f2a4a; border-radius: 20px; box-shadow: 0 25px 50px rgba(15, 42, 74, 0.2);">
                <div class="modal-header border-bottom" style="border-color: #f1f5f9 !important;">
                    <h5 class="modal-title fw-bold" style="color: #0f2a4a;"><i class="fas fa-edit me-2" style="color: #0284c7;"></i>Edit Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div id="editAlert" class="alert d-none"></div>
                    <input type="hidden" name="id" id="edit_category_id">
                    <div class="mb-3">
                        <label class="form-label small text-uppercase fw-bold" style="color: #64748b;">Category Name *</label>
                        <input type="text" name="category_name" id="edit_category_name" class="form-control" style="background: #f8fafc; border: 1px solid #cbd5e1; color: #0f2a4a; font-weight: 500; border-radius: 10px;" required>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn rounded-pill px-4 fw-bold text-white shadow-sm" style="background: linear-gradient(135deg, #0284c7, #0ea5e9); border: none;">Update Category</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="background: #ffffff; border: 1px solid rgba(239, 68, 68, 0.3); color: #0f2a4a; border-radius: 20px; box-shadow: 0 25px 50px rgba(15, 42, 74, 0.2);">
                <div class="modal-header border-bottom border-danger-subtle">
                    <h5 class="modal-title fw-bold text-danger"><i class="fas fa-exclamation-triangle me-2"></i>Confirm Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 text-center">
                    <div id="deleteAlert" class="alert d-none text-start"></div>
                    <p class="mb-0 fs-5 fw-semibold" style="color: #0f2a4a;">Are you sure you want to delete this category?</p>
                    <p class="text-secondary small mt-2">Any players linked to it might lose their category association.</p>
                </div>
                <div class="modal-footer border-top-0 justify-content-center pt-0">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger rounded-pill px-4 fw-bold shadow-sm" id="confirmDeleteBtn">Yes, Delete</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            $('#addCategoryForm').on('submit', function(e) {
                e.preventDefault();
                var btn = $(this).find('button[type="submit"]');
                btn.prop('disabled', true).text('Saving...');
                $('#addAlert').addClass('d-none');
                
                $.ajax({
                    url: 'api_add_category.php',
                    type: 'POST',
                    data: $(this).serialize(),
                    dataType: 'json',
                    success: function(res) {
                        if (res.status === 'success') {
                            $('#addAlert').removeClass('d-none alert-danger').addClass('alert-success').text(res.message);
                            setTimeout(() => window.location.reload(), 1000);
                        } else {
                            $('#addAlert').removeClass('d-none alert-success').addClass('alert-danger').text(res.message);
                            btn.prop('disabled', false).text('Save Category');
                        }
                    },
                    error: function() {
                        $('#addAlert').removeClass('d-none alert-success').addClass('alert-danger').text('Network Error.');
                        btn.prop('disabled', false).text('Save Category');
                    }
                });
            });

            $('#editCategoryForm').on('submit', function(e) {
                e.preventDefault();
                var btn = $(this).find('button[type="submit"]');
                btn.prop('disabled', true).text('Updating...');
                $('#editAlert').addClass('d-none');
                
                $.ajax({
                    url: 'api_edit_category.php',
                    type: 'POST',
                    data: $(this).serialize(),
                    dataType: 'json',
                    success: function(res) {
                        if (res.status === 'success') {
                            $('#editAlert').removeClass('d-none alert-danger').addClass('alert-success').text(res.message);
                            setTimeout(() => window.location.reload(), 1000);
                        } else {
                            $('#editAlert').removeClass('d-none alert-success').addClass('alert-danger').text(res.message);
                            btn.prop('disabled', false).text('Update Category');
                        }
                    },
                    error: function() {
                        $('#editAlert').removeClass('d-none alert-success').addClass('alert-danger').text('Network Error.');
                        btn.prop('disabled', false).text('Update Category');
                    }
                });
            });
        });

        window.openEditModal = function(cat) {
            $('#edit_category_id').val(cat.id);
            $('#edit_category_name').val(cat.category_name);
            $('#editAlert').addClass('d-none');
            new bootstrap.Modal(document.getElementById('editCategoryModal')).show();
        };

        let categoryToDelete = null;

        window.deleteCategory = function(id) {
            categoryToDelete = id;
            $('#deleteAlert').addClass('d-none');
            new bootstrap.Modal(document.getElementById('deleteConfirmModal')).show();
        };

        $('#confirmDeleteBtn').on('click', function() {
            if (!categoryToDelete) return;
            
            var btn = $(this);
            var originalText = btn.text();
            btn.prop('disabled', true).text('Deleting...');

            $.ajax({
                url: 'api_delete_category.php',
                type: 'POST',
                data: { id: categoryToDelete },
                dataType: 'json',
                success: function(res) {
                    if(res.status === 'success') {
                        window.location.reload();
                    } else {
                        $('#deleteAlert').removeClass('d-none alert-success').addClass('alert-danger').text(res.message);
                        btn.prop('disabled', false).text(originalText);
                    }
                },
                error: function() {
                    $('#deleteAlert').removeClass('d-none alert-success').addClass('alert-danger').text('Network error while deleting.');
                    btn.prop('disabled', false).text(originalText);
                }
            });
        });
    </script>
</body>
</html>
