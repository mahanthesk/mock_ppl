<?php
session_start();
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
        :root {
            --gold: #0284c7;
            --gold-gradient: linear-gradient(135deg, #0284c7 0%, #0ea5e9 100%);
            --glass: #ffffff;
            --glass-border: rgba(2, 132, 199, 0.2);
        }

        body {
            color: #0f2a4a;
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
        }

        .dashboard-container {
            padding: 30px 20px;
        }

        .table-container {
            background: #ffffff;
            border: 1px solid rgba(2, 132, 199, 0.2);
            border-radius: 16px;
            padding: 20px;
            overflow-x: auto;
            box-shadow: 0 10px 30px rgba(15, 42, 74, 0.08);
        }

        .table {
            color: #0f2a4a;
            margin-bottom: 0;
            background-color: #ffffff !important;
        }

        .table thead th {
            border-bottom: none !important;
            color: #ffffff !important;
            font-family: 'Poppins', sans-serif;
            text-transform: uppercase;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            padding: 14px 15px;
            background-color: #28779b !important;
        }

        .table tbody td {
            border-bottom: 1px solid #f1f5f9 !important;
            padding: 12px 15px;
            vertical-align: middle;
            font-size: 0.9rem;
            color: #334155 !important;
            background-color: #ffffff !important;
        }

        .player-row:hover td {
            background-color: #f8fafc !important;
        }

        .btn-view {
            background: rgba(2, 132, 199, 0.1);
            border: 1px solid #0284c7;
            color: #0284c7;
            font-size: 0.8rem;
            font-weight: 600;
            padding: 5px 14px;
            border-radius: 20px;
            text-transform: uppercase;
            transition: all 0.3s;
            text-decoration: none;
        }

        .btn-view:hover {
            background: #0284c7;
            color: #ffffff;
        }

        .avatar-mini {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            object-fit: cover;
            border: 1px solid #0284c7;
        }

        .badge-category {
            background: rgba(2, 132, 199, 0.1);
            color: #0284c7;
            border: 1px solid rgba(2, 132, 199, 0.25);
            font-weight: 600;
            font-size: 0.75rem;
            padding: 4px 10px;
            border-radius: 20px;
        }

        /* DataTables Custom Styles */
        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter,
        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_processing,
        .dataTables_wrapper .dataTables_paginate {
            color: #64748b !important;
            margin-bottom: 15px;
            font-size: 0.85rem;
        }

        .dataTables_wrapper .dataTables_filter input,
        .dataTables_wrapper .dataTables_length select {
            background-color: #f8fafc !important;
            border: 1px solid #cbd5e1 !important;
            color: #0f2a4a !important;
            border-radius: 8px;
            padding: 4px 10px;
        }

        .page-link {
            background-color: #ffffff !important;
            border-color: #cbd5e1 !important;
            color: #0f2a4a !important;
        }

        .page-item.active .page-link {
            background: linear-gradient(135deg, #0284c7, #0ea5e9) !important;
            border-color: #0284c7 !important;
            color: #ffffff !important;
        }

        .page-item.disabled .page-link {
            background-color: transparent !important;
            color: #94a3b8 !important;
        }

        #categoryFilter option {
            background-color: #ffffff;
            color: #0f2a4a;
        }

        .custom-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 20px;
            background: rgba(255, 255, 255, 0.95);
            border-bottom: 1px solid rgba(2, 132, 199, 0.2);
            position: sticky;
            top: 0;
            z-index: 1000;
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 20px rgba(15, 42, 74, 0.05);
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .top-logo {
            height: 60px;
        }

        .left-logo {
            height: 60px;
        }

        .header-center h1 {
            font-family: 'Poppins', sans-serif;
            font-size: 1.6rem;
            font-weight: 800;
            margin: 0;
            color: #0f2a4a;
            letter-spacing: -0.5px;
            text-transform: uppercase;
            line-height: 1.2;
            text-align: center;
        }

        .header-right .right-logo {
            height: 50px;
        }

        @media (max-width: 768px) {
            .dashboard-container {
                padding: 20px 10px;
            }
            
            .dashboard-container .d-flex{
                flex-direction: column;
            }

            .custom-header {
                flex-wrap: wrap;
                padding: 10px;
                gap: 5px;
            }

            .header-left {
                order: 1;
                gap: 10px;
            }

            .header-right {
                order: 2;
            }

            .header-center {
                order: 3;
                width: 100%;
                margin-top: 5px;
            }

            .top-logo {
                height: 50px;
            }

            .left-logo,
            .right-logo {
                height: 50px;
            }

            .header-center h1 {
                font-size: 1.3rem;
            }
        }
    </style>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/pretium-glass-theme.css">
</head>

<body>
    <div class="custom-header d-flex align-items-center justify-content-between px-4 py-2">
        <div class="header-left d-flex align-items-center">
            <img src="assets/ppl-logo-transparent.png" alt="PPL Logo" style="height: 55px;" onerror="this.onerror=null;this.src='assets/PPL-Season-2.png';">
        </div>
        <div class="header-center text-center">
            <h1 class="m-0 fw-bold" style="font-family: 'Poppins', sans-serif; font-size: 1.6rem; color: #0f2a4a; letter-spacing: -0.5px;">PRETIUM <span style="color: #0284c7;">PREMIER LEAGUE</span></h1>
            <span class="small fw-bold text-uppercase" style="letter-spacing: 2px; font-size: 0.75rem; color: #64748b;">Official Player Roster</span>
        </div>
        <div class="header-right d-flex align-items-center">
            <img src="assets/pretium-company-logo.png" alt="Company Logo" style="height: 42px; width: 42px; object-fit: contain;">
        </div>
    </div>

    <div class="container-fluid">
        <div class="row">
            <!-- Main Content -->
            <div class="col-12 dashboard-container">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="fw-bold m-0" style="font-family: 'Poppins', sans-serif; color: #0f2a4a; letter-spacing: -0.5px;">REGISTRATION <span style="color: #0284c7;">ROSTER</span></h2>
                        <p class="text-secondary small mb-0">Browse and inspect registered players for the tournament</p>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <select id="categoryFilter" class="form-select w-auto"
                            style="background-color: #ffffff; border: 1px solid #cbd5e1; color: #0f2a4a; font-weight: 600; border-radius: 10px;">
                            <option value="">All Categories</option>
                            <?php
                            if (!empty($globalCategories)) {
                                foreach ($globalCategories as $cat) {
                                    echo "<option value='" . htmlspecialchars($cat) . "'>" . htmlspecialchars($cat) . "</option>";
                                }
                            }
                            ?>
                        </select>
                        <span class="badge px-3 py-2 text-nowrap rounded-pill" style="background: rgba(2, 132, 199, 0.1); border: 1px solid rgba(2, 132, 199, 0.3); color: #0284c7; font-weight: 700; font-size: 0.85rem;">
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
                                <th>Jersey #</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($players as $player): ?>
                                <tr class="player-row">
                                    <td><span class="fw-bold" style="color: #0284c7;"><?php echo $player['player_id']; ?></span></td>
                                    <td class="fw-bold" style="color: #0f2a4a;"><?php echo $player['full_name']; ?></td>
                                    <td><span class="badge badge-category"><?php echo $player['category']; ?></span></td>
                                    <td style="color: #334155;"><?php echo $player['role']; ?></td>
                                    <td class="text-center" style="color: #334155;"><?php echo $player['jersey_number']; ?></td>
                                    <td>
                                        <a href="player_view.php?id=<?php echo $player['id']; ?>" class="btn-view">
                                            <i class="fas fa-eye me-1"></i> View
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($players)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">No players registered yet.</td>
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
                "order": [[0, "asc"]], // Sort by Player ID by default
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