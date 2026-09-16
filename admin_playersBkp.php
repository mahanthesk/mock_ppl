<?php
session_start();
require 'db_connection.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM registrations WHERE deleted_at IS NULL ORDER BY created_at ASC");
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
            --gold: #D4AF37;
            --gold-gradient: linear-gradient(135deg, #D4AF37 0%, #F5D76E 50%, #B8860B 100%);
            --black: #000000;
            --dark-gray: #1a1a1a;
            --glass: rgba(255, 255, 255, 0.05);
            --glass-border: rgba(212, 175, 55, 0.3);
        }

        body {
            background-color: var(--black);
            color: #fff;
            font-family: 'Poppins', sans-serif;
            background: radial-gradient(circle at center, #1a1a1a 0%, #000 100%);
            min-height: 100vh;
        }

        .navbar {
            background: rgba(0, 0, 0, 0.9);
            border-bottom: 1px solid var(--glass-border);
            backdrop-filter: blur(10px);
        }

        .navbar-brand {
            font-family: 'Cinzel', serif;
            color: var(--gold) !important;
            font-weight: 700;
            letter-spacing: 2px;
        }

        .dashboard-container {
            padding: 40px 20px;
        }

        .sidebar {
            background: rgba(255, 255, 255, 0.03);
            border-right: 1px solid var(--glass-border);
            min-height: calc(100vh - 56px);
            padding: 20px 0;
        }

        .nav-link {
            color: #ccc;
            padding: 12px 25px;
            transition: all 0.3s;
        }

        .nav-link:hover,
        .nav-link.active {
            color: var(--gold);
            background: rgba(212, 175, 55, 0.05);
        }

        .nav-link i {
            margin-right: 10px;
            width: 20px;
        }

        .table-container {
            background: var(--glass);
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            padding: 20px;
            overflow-x: auto;
        }

        .table {
            color: #eee;
            margin-bottom: 0;
            background-color: transparent !important;
        }

        .table thead th {
            border-bottom: 1px solid var(--glass-border) !important;
            color: var(--gold) !important;
            font-family: 'Cinzel', serif;
            text-transform: uppercase;
            font-size: 0.85rem;
            text-align: center;
            letter-spacing: 1px;
            padding: 15px;
            background-color: rgba(255, 255, 255, 0.02) !important;
        }

        table.dataTable thead>tr>th.sorting_asc,
        table.dataTable thead>tr>th.sorting_desc, 
        table.dataTable thead>tr>th.sorting{
            text-align: center;
        }

        .table tbody td {
            border-bottom: 1px solid rgba(255, 255, 255, 0.05) !important;
            padding: 12px 15px;
            vertical-align: middle;
            text-align: center;
            font-size: 0.9rem;
            color: #ccc !important;
            background-color: transparent !important;
        }

        .player-row:hover {
            background: rgba(212, 175, 55, 0.08) !important;
        }

        .btn-view {
            background: rgba(212, 175, 55, 0.1);
            border: 1px solid var(--gold);
            color: var(--gold);
            font-size: 0.8rem;
            padding: 5px 12px;
            text-transform: uppercase;
            transition: all 0.3s;
        }

        .btn-view:hover {
            background: var(--gold);
            color: #000;
        }

        .avatar-mini {
            width: 40px;
            height: 40px;
            border-radius: 4px;
            object-fit: cover;
            border: 1px solid var(--gold);
        }

        .badge-category {
            background: rgba(212, 175, 55, 0.15);
            color: var(--gold);
            border: 1px solid rgba(212, 175, 55, 0.3);
            font-weight: 500;
        }

        /* DataTables Custom Styles */
        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter,
        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_processing,
        .dataTables_wrapper .dataTables_paginate {
            color: #ccc !important;
            margin-bottom: 15px;
        }

        .dataTables_wrapper .dataTables_filter input,
        .dataTables_wrapper .dataTables_length select {
            background-color: rgba(255, 255, 255, 0.05) !important;
            border: 1px solid var(--glass-border) !important;
            color: #fff !important;
            border-radius: 4px;
        }

        .page-link {
            background-color: var(--glass) !important;
            border-color: var(--glass-border) !important;
            color: var(--gold) !important;
        }

        .page-item.active .page-link {
            background: var(--gold-gradient) !important;
            border-color: var(--gold) !important;
            color: var(--black) !important;
        }

        .page-item.disabled .page-link {
            background-color: transparent !important;
            color: #555 !important;
        }

        /* Mobile Adjustments */
        @media (max-width: 768px) {
            .sidebar {
                min-height: auto;
                border-right: none;
                border-bottom: 1px solid var(--glass-border);
            }

            .dashboard-container {
                padding: 20px 10px;
            }

            .navbar-brand {
                font-size: 1rem;
            }
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="#" style="display:flex;align-items:center;gap:10px;"><img src="assets/PPL-Season-2.png" alt="PPL Logo" style="height:40px;filter:drop-shadow(0 0 6px rgba(212,175,55,0.6));"><span>PPL ADMIN</span></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminSidebar"
                aria-controls="adminSidebar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="adminNavbarContent">
                <div class="ms-auto d-flex align-items-center mt-2 mt-lg-0">
                    <span class="text-light me-3">Welcome, <?php echo $_SESSION['admin_username']; ?></span>
                    <a href="admin_logout.php" class="btn btn-outline-danger btn-sm">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-lg-2 col-md-3 collapse d-md-block sidebar" id="adminSidebar">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="admin_dashboard.php">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="admin_players.php">
                            <i class="fas fa-users"></i> All Players
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Main Content -->
            <div class="col-lg-10 col-md-9 dashboard-container">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 style="font-family: 'Cinzel', serif; color: var(--gold);">Registrations</h2>
                    <span class="badge bg-dark border border-warning px-3 py-2">
                        <?php echo count($players); ?> total
                    </span>
                </div>

                <div class="table-container">
                    <table class="table" id="playersTable">
                        <thead>
                            <tr>
                                <th>Sl. No.</th>
                                <th>Player ID</th>
                                <th>Name</th>
                                <th>Mobile</th>
                                <th>Category</th>
                                <th>Role</th>
                                <th>Jersey #</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($players as $player): ?>
                                <tr class="player-row">
                                    <td>
                                        <?php echo $player['id']; ?>
                                    </td>
                                    <td class="fw-bold text-warning">
                                        <?php echo $player['player_id']; ?>
                                    </td>
                                    <td>
                                        <?php echo $player['full_name']; ?>
                                    </td>
                                    <td>
                                        <?php echo $player['mobile']; ?>
                                    </td>
                                    <td><span class="badge badge-category">
                                            <?php echo $player['category']; ?>
                                        </span></td>
                                    <td>
                                        <?php echo $player['role']; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php echo $player['jersey_number']; ?>
                                    </td>
                                    <td>
                                        <a href="admin_player_view.php?id=<?php echo $player['id']; ?>"
                                            class="btn btn-view">
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
            $('#playersTable').DataTable({
                "pageLength": 25,
                "ordering": true,
                "order": [[1, "asc"]], // Sort by Player ID by default
                "language": {
                    "search": "Search Players:",
                    "lengthMenu": "Display _MENU_ per page"
                }
            });
        });
    </script>
</body>

</html>