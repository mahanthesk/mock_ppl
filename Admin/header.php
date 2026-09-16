<?php
// header.php - Pretium Premier League Modern Navigation Bar
$currentPage = basename($_SERVER['PHP_SELF'] ?? '');
?>
<nav class="navbar navbar-expand-xl sticky-top ppl-auction-navbar">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/pretium-glass-theme.css">
    <div class="container-fluid px-3 px-lg-4">
        <!-- Brand & Logos -->
        <a class="navbar-brand d-flex align-items-center text-decoration-none py-1" href="dashboard.php">
            <div class="brand-logo-combo d-flex align-items-center">
                <img src="assets/ppl-logo-transparent.png" alt="PPL Logo" class="ppl-header-logo me-2" onerror="this.onerror=null;this.src='assets/PPL-Season-2.png';">
                <div class="d-flex flex-column lh-1">
                    <span class="ppl-brand-title">PRETIUM PREMIER LEAGUE</span>
                    <span class="ppl-brand-sub d-none d-sm-inline">OFFICIAL AUCTION PORTAL</span>
                </div>
            </div>
        </a>

        <!-- Mobile Toggler -->
        <button class="navbar-toggler border-0 p-2 text-white" type="button" data-bs-toggle="collapse" data-bs-target="#auctionNavbar" aria-controls="auctionNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <i class="fas fa-bars fs-4" style="color: var(--ppl-cyan, #3abdd9);"></i>
        </button>

        <!-- Navbar Links -->
        <div class="collapse navbar-collapse" id="auctionNavbar">
            <ul class="navbar-nav mx-auto mb-2 mb-xl-0 gap-1 gap-xl-2">
                <li class="nav-item">
                    <a class="nav-link auction-nav-link <?php echo ($currentPage == 'dashboard.php') ? 'active' : ''; ?>" href="dashboard.php">
                        <i class="fas fa-chart-pie me-1 text-cyan"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link auction-nav-link <?php echo in_array($currentPage, ['teams.php', 'team_details.php', 'team_dashboard.php', 'team_grid.php']) ? 'active' : ''; ?>" href="teams.php">
                        <i class="fas fa-shield-halved me-1 text-cyan"></i>Teams
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link auction-nav-link <?php echo ($currentPage == 'categories.php') ? 'active' : ''; ?>" href="categories.php">
                        <i class="fas fa-tags me-1 text-cyan"></i>Categories
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link auction-nav-link live-nav-link <?php echo in_array($currentPage, ['bid_player.php', 'bid_icon.php']) ? 'active' : ''; ?>" href="bid_player.php">
                        <span class="live-pulse-dot me-1"></span>
                        <i class="fas fa-gavel me-1 text-cyan"></i>Bid Player
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link auction-nav-link <?php echo ($currentPage == 'sold.php') ? 'active' : ''; ?>" href="sold.php">
                        <i class="fas fa-circle-check me-1 text-emerald"></i>Sold
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link auction-nav-link <?php echo in_array($currentPage, ['unsold.php', 'unsold-list.php']) ? 'active' : ''; ?>" href="unsold.php">
                        <i class="fas fa-circle-xmark me-1 text-muted"></i>Unsold
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link auction-nav-link <?php echo in_array($currentPage, ['player_list.php', 'player_listing.php', 'all_players.php']) ? 'active' : ''; ?>" href="player_list.php">
                        <i class="fas fa-users me-1 text-cyan"></i>All Players
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link auction-nav-link <?php echo in_array($currentPage, ['pool_setup.php', 'final_pool.php', 'final_list.php']) ? 'active' : ''; ?>" href="pool_setup.php">
                        <i class="fas fa-layer-group me-1 text-cyan"></i>Pools
                    </a>
                </li>
            </ul>

            <!-- Corporate Emblem & Logout -->
            <div class="d-flex align-items-center gap-3 mt-3 mt-xl-0 ms-xl-2">
                <div class="company-badge-header d-none d-xxl-flex align-items-center px-2 py-1 rounded-pill" title="Pretium Corporate" style="background: #f8fafc; border: 1px solid #e2e8f0;">
                    <img src="assets/pretium-company-logo.png" alt="Company Logo" style="height: 24px; width: 24px; object-fit: contain;">
                </div>
                <a class="btn btn-outline-danger btn-sm px-3 rounded-pill d-flex align-items-center gap-2" href="logout.php">
                    <i class="fas fa-arrow-right-from-bracket"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
    </div>
</nav>

<style>
.ppl-auction-navbar {
    background: rgba(255, 255, 255, 0.96) !important;
    backdrop-filter: blur(16px) saturate(180%) !important;
    -webkit-backdrop-filter: blur(16px) saturate(180%) !important;
    border-bottom: 1px solid #e2e8f0 !important;
    box-shadow: 0 2px 12px rgba(15, 23, 42, 0.04) !important;
}

.ppl-header-logo {
    height: 52px;
    filter: drop-shadow(0 2px 8px rgba(2, 132, 199, 0.25));
    transition: transform 0.3s ease;
}

.ppl-header-logo:hover {
    transform: scale(1.05);
}

.ppl-brand-title {
    font-family: var(--font-display, 'Outfit', sans-serif);
    font-size: 1.05rem;
    font-weight: 800;
    letter-spacing: 1.5px;
    background: linear-gradient(120deg, #0f2a4a 0%, #0284c7 100%);
    -webkit-background-clip: text;
    background-clip: text;
    -webkit-text-fill-color: transparent;
}

.ppl-brand-sub {
    font-family: var(--font-display, 'Outfit', sans-serif);
    font-size: 0.65rem;
    font-weight: 700;
    letter-spacing: 2px;
    color: #64748b;
    margin-top: 2px;
}

.auction-nav-link {
    font-family: var(--font-body, 'Plus Jakarta Sans', sans-serif);
    font-size: 0.88rem !important;
    font-weight: 600 !important;
    text-transform: uppercase !important;
    letter-spacing: 0.6px !important;
    color: #334155 !important;
    padding: 0.5rem 0.85rem !important;
    border-radius: 8px !important;
    transition: all 0.25s ease !important;
    display: inline-flex !important;
    align-items: center !important;
}

.auction-nav-link:hover {
    color: #0284c7 !important;
    background: #e0f2fe !important;
}

.auction-nav-link.active {
    color: #0284c7 !important;
    background: #e0f2fe !important;
    border: 1px solid rgba(2, 132, 199, 0.25) !important;
    box-shadow: 0 2px 6px rgba(2, 132, 199, 0.1) !important;
}

.live-pulse-dot {
    display: inline-block;
    width: 8px;
    height: 8px;
    background-color: #ef4444;
    border-radius: 50%;
    animation: livePulseDot 1.6s infinite ease-in-out;
}

@keyframes livePulseDot {
    0% { transform: scale(0.9); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); }
    70% { transform: scale(1.1); box-shadow: 0 0 0 6px rgba(239, 68, 68, 0); }
    100% { transform: scale(0.9); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
}

.text-cyan {
    color: #0284c7 !important;
}

.text-emerald {
    color: #059669 !important;
}
</style>
