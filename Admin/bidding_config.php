<?php
// bidding_config.php

/**
 * Returns the base price for a given category.
 * Marquee A: 250000
 * Marquee B: 250000
 * Marquee C: 100000
 * Marquee D: 50000
 */
function getBasePrice($category) {
    $cat = strtoupper(trim($category));
    if (strpos($cat, 'MARQUEE A') !== false) {
        return 250000;
    } elseif (strpos($cat, 'MARQUEE B') !== false) {
        return 250000;
    } elseif (strpos($cat, 'MARQUEE C') !== false) {
        return 100000;
    } elseif (strpos($cat, 'MARQUEE D') !== false) {
        return 50000;
    }
    // Default fallback if category doesn't match perfectly
    return 50000;
}

/**
 * Calculates the next valid bid based on the current bid amount.
 * < 5,00,000 : +25,000
 * 5,00,000 to < 10,00,000 : +50,000
 * 10,00,000 to < 20,00,000 : +1,00,000
 * 20,00,000 to < 50,00,000 : +2,50,000
 * >= 50,00,000 : +5,00,000
 */
function calculateNextBid($currentBid) {
    $currentBid = floatval($currentBid);
    
    if ($currentBid < 500000) {
        $increment = 25000;
    } elseif ($currentBid >= 500000 && $currentBid < 1000000) {
        $increment = 50000;
    } elseif ($currentBid >= 1000000 && $currentBid < 2000000) {
        $increment = 100000;
    } elseif ($currentBid >= 2000000 && $currentBid < 5000000) {
        $increment = 250000;
    } else { // >= 5000000
        $increment = 500000;
    }
    
    return $currentBid + $increment;
}

/**
 * Expose configuration logic to frontend via JSON format
 */
function getBiddingConfigJSON() {
    return json_encode([
        'basePrices' => [
            'MARQUEE A' => 250000,
            'MARQUEE B' => 250000,
            'MARQUEE C' => 100000,
            'MARQUEE D' => 50000
        ],
        'slabs' => [
            ['limit' => 500000, 'increment' => 25000],
            ['limit' => 1000000, 'increment' => 50000],
            ['limit' => 2000000, 'increment' => 100000],
            ['limit' => 5000000, 'increment' => 250000],
            ['limit' => 999999999, 'increment' => 500000] // Catch-all max
        ]
    ]);
}
?>
