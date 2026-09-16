<?php

require_once '../auth.php';
check_access('auction_admin');

require 'db_connection.php';

$stmt = $pdo->prepare("SELECT * FROM registrations WHERE deleted_at IS NULL ORDER BY created_at DESC, id DESC");
$stmt->execute();
$players = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Players List - PPL</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700&family=Poppins:wght@300;400;600&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <style>
        .dashboard-container {
            padding: 40px 24px;
        }

        .table-container {
            background: #ffffff;
            border: 1px solid rgba(2, 132, 199, 0.18);
            border-radius: 20px;
            padding: 24px;
            box-shadow: 0 10px 30px rgba(15, 42, 74, 0.08);
            overflow-x: auto;
        }

        .table {
            color: #334155;
            margin-bottom: 0;
            background-color: transparent !important;
            vertical-align: middle;
        }

        .table thead th {
            background-color: #28779b !important;
            color: #ffffff !important;
            font-family: 'Poppins', sans-serif;
            text-transform: uppercase;
            font-size: 0.82rem;
            font-weight: 700;
            letter-spacing: 0.6px;
            padding: 14px 16px;
            border: none !important;
        }

        .table thead th:first-child {
            border-top-left-radius: 12px;
        }

        .table thead th:last-child {
            border-top-right-radius: 12px;
        }

        .table tbody td {
            border-bottom: 1px solid #f1f5f9 !important;
            padding: 14px 16px;
            vertical-align: middle;
            font-size: 0.92rem;
            color: #334155 !important;
            background-color: transparent !important;
        }

        .player-row:hover {
            background-color: #f8fafc !important;
        }

        .btn-view {
            background: linear-gradient(135deg, #0284c7 0%, #0f2a4a 100%);
            border: none;
            color: #ffffff !important;
            font-size: 0.8rem;
            font-weight: 700;
            padding: 6px 16px;
            border-radius: 20px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 3px 10px rgba(2, 132, 199, 0.25);
            transition: all 0.25s ease;
            display: inline-flex;
            align-items: center;
            text-decoration: none;
        }

        .btn-view:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(2, 132, 199, 0.35);
            color: #ffffff !important;
        }

        .badge-category {
            background: #e0f2fe;
            color: #0284c7;
            border: 1px solid #bae6fd;
            font-weight: 600;
            font-size: 0.8rem;
            padding: 5px 12px;
            border-radius: 8px;
        }

        /* DataTables Custom Styles for White Theme */
        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter,
        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_processing,
        .dataTables_wrapper .dataTables_paginate {
            color: #64748b !important;
            margin-bottom: 15px;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .dataTables_wrapper .dataTables_filter input,
        .dataTables_wrapper .dataTables_length select {
            background-color: #f8fafc !important;
            border: 1.5px solid #cbd5e1 !important;
            color: #0f2a4a !important;
            border-radius: 10px;
            padding: 6px 12px;
            outline: none;
            font-weight: 500;
        }

        .dataTables_wrapper .dataTables_filter input:focus,
        .dataTables_wrapper .dataTables_length select:focus {
            border-color: #0284c7 !important;
            box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.15);
            background-color: #ffffff !important;
        }

        .page-link {
            background-color: #ffffff !important;
            border-color: #e2e8f0 !important;
            color: #0284c7 !important;
            border-radius: 8px !important;
            margin: 0 2px;
            font-weight: 600;
        }

        .page-item.active .page-link {
            background: linear-gradient(135deg, #0f2a4a 0%, #0284c7 100%) !important;
            border-color: transparent !important;
            color: #ffffff !important;
            box-shadow: 0 4px 10px rgba(2, 132, 199, 0.25);
        }

        .page-item.disabled .page-link {
            background-color: #f8fafc !important;
            color: #94a3b8 !important;
            border-color: #e2e8f0 !important;
        }

        .custom-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 24px;
            background: rgba(255, 255, 255, 0.96);
            border-bottom: 1px solid rgba(2, 132, 199, 0.18);
            position: sticky;
            top: 0;
            z-index: 1000;
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            box-shadow: 0 4px 20px rgba(15, 42, 74, 0.05);
        }

        /* Mobile Adjustments */
        @media (max-width: 768px) {
            .dashboard-container {
                padding: 20px 12px;
            }

            .custom-header {
                flex-wrap: wrap;
                padding: 10px;
                gap: 8px;
            }
        }
    </style>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/pretium-glass-theme.css">
</head>

<body>
    <div class="custom-header d-flex align-items-center justify-content-between px-4 py-2">
        <div class="header-left d-flex align-items-center">
            <a href="dashboard.php" class="text-decoration-none">
                <img src="assets/ppl-logo-transparent.png" alt="PPL Logo" style="height: 52px; filter: drop-shadow(0 2px 8px rgba(2, 132, 199, 0.3));" onerror="this.onerror=null;this.src='assets/PPL-Season-2.png';">
            </a>
        </div>
        <div class="header-center text-center">
            <h1 class="m-0 fw-bold" style="font-family: 'Poppins', sans-serif; font-size: 1.5rem; letter-spacing: -0.5px; color: #0f2a4a;">PRETIUM <span style="color: #0284c7;">PREMIER LEAGUE</span></h1>
            <span class="small fw-semibold text-uppercase" style="letter-spacing: 2px; font-size: 0.72rem; color: #0284c7;">Player Listing Directory</span>
        </div>
        <div class="header-right d-flex align-items-center gap-3">
            <img src="../assets/pretium-company-logo.png" alt="Company Logo" style="height: 36px; width: 36px; object-fit: contain;" onerror="this.onerror=null;this.src='assets/pretium-company-logo.png';">
            <a href="dashboard.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3 fw-semibold">
                <i class="fas fa-arrow-left me-1"></i> Dashboard
            </a>
        </div>
    </div>

    <div class="container-fluid">
        <div class="row">
            <!-- Main Content -->
            <div class="col-12 dashboard-container">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                    <h2 class="fw-bold mb-0" style="font-family: 'Poppins', sans-serif; color: #0f2a4a; letter-spacing: -0.5px;">Registered <span style="color: #0284c7;">Players</span></h2>
                    <div class="d-flex align-items-center gap-3">
                        <select id="categoryFilter" class="form-select w-auto"
                            style="background-color: #ffffff; border: 1.5px solid rgba(2, 132, 199, 0.25); color: #0f2a4a; border-radius: 12px; font-weight: 600; padding: 8px 16px;">
                            <option value="">All Categories</option>
                            <?php
                            if (!empty($globalCategories)) {
                                foreach ($globalCategories as $cat) {
                                    echo "<option value='" . htmlspecialchars($cat) . "'>" . htmlspecialchars($cat) . "</option>";
                                }
                            }
                            ?>
                        </select>
                        <span class="badge px-3 py-2 text-nowrap" style="background: #e0f2fe; color: #0284c7; border: 1px solid #bae6fd; border-radius: 20px; font-weight: 700; font-size: 0.9rem;">
                            <?php echo count($players); ?> total
                        </span>
                    </div>
                </div>

                <div class="table-container">
                    <table class="table" id="playersTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Role</th>
                                <th class="text-center">Jersey #</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($players as $player): ?>
                                <tr class="player-row">
                                    <td class="fw-bold" style="color: #0284c7;">
                                        <?php echo $player['player_id']; ?>
                                    </td>
                                    <td class="fw-semibold text-dark">
                                        <?php echo $player['full_name']; ?>
                                    </td>
                                    <td><span class="badge badge-category">
                                            <?php echo $player['category']; ?>
                                        </span></td>
                                    <td>
                                        <?php echo $player['role']; ?>
                                    </td>
                                    <td class="text-center fw-bold text-secondary">
                                        <?php echo $player['jersey_number']; ?>
                                    </td>
                                    <td class="text-center">
                                        <a href="player_view.php?id=<?php echo $player['id']; ?>" class="btn btn-view">
                                            <i class="fas fa-eye me-1"></i> View
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($players)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">No players registered yet.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <script>
        $(document).ready(function () {
            var table = $('#playersTable').DataTable({
                "pageLength": 25,
                "ordering": true,
                "order": [], // Retain server-side sorting
                "language": {
                    "search": "Search Players:",
                    "lengthMenu": "Display _MENU_ per page"
                }
            });

            // Restore category filter from sessionStorage
            var savedCategory = sessionStorage.getItem('spl_category_filter');
            if (savedCategory) {
                $('#categoryFilter').val(savedCategory);
                var val = $.fn.dataTable.util.escapeRegex(savedCategory);
                table.column(2).search(val ? '^\\s*' + val + '\\s*$' : '', true, false).draw();
            }

            // Handle Category Filter
            $('#categoryFilter').on('change', function () {
                var selectedValue = $(this).val();
                sessionStorage.setItem('spl_category_filter', selectedValue);
                var val = $.fn.dataTable.util.escapeRegex(selectedValue);
                table.column(2).search(val ? '^\\s*' + val + '\\s*$' : '', true, false).draw();
            });
        });
    </script>
</body>

</html>