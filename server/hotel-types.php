<?php
/**
 * server/hotel-types.php — AJAX endpoint for homepage modal
 * Returns hotel package types. Gracefully handles missing migration tables.
 */
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/auth.php';
header('Content-Type: application/json');
$db = getDB();

// Fallback data (always works, even without migration)
$fallback = [
    ['type_key'=>'standard',    'display_name'=>'Standard',    'icon'=>'🛏️', 'badge_color'=>'#6b7280','tagline'=>'Clean, comfortable stays for savvy travellers','price_label'=>'From NPR 5,000'],
    ['type_key'=>'deluxe',      'display_name'=>'Deluxe',      'icon'=>'✨', 'badge_color'=>'#3b82f6','tagline'=>'Elevated comfort with premium views & amenities','price_label'=>'From NPR 10,000'],
    ['type_key'=>'luxury',      'display_name'=>'Luxury',      'icon'=>'👑', 'badge_color'=>'#d4a017','tagline'=>'Unparalleled elegance & exclusive perks','price_label'=>'From NPR 20,000'],
    ['type_key'=>'family',      'display_name'=>'Family',      'icon'=>'👨‍👩‍👧‍👦','badge_color'=>'#10b981','tagline'=>'Spacious rooms designed for the whole family','price_label'=>'From NPR 12,000'],
    ['type_key'=>'couple',      'display_name'=>'Couple',      'icon'=>'💑', 'badge_color'=>'#ec4899','tagline'=>'Romantic suites with jacuzzis & champagne','price_label'=>'From NPR 18,000'],
    ['type_key'=>'presidential','display_name'=>'Presidential','icon'=>'🏆', 'badge_color'=>'#f59e0b','tagline'=>'The pinnacle of luxury — panoramic views & butler','price_label'=>'From NPR 40,000'],
];

$packages = [];

// Try DB first
try {
    $rows = $db->query("SELECT * FROM hotel_type_packages WHERE is_active=1 ORDER BY sort_order ASC")->fetchAll();
    if ($rows) {
        foreach ($rows as &$pkg) {
            try {
                $stmt = $db->prepare("SELECT f.name,f.icon,f.category FROM hotel_type_facilities htf JOIN facilities f ON f.id=htf.facility_id WHERE htf.type_key=? ORDER BY f.category,f.name");
                $stmt->execute([$pkg['type_key']]);
                $pkg['facilities'] = $stmt->fetchAll();
            } catch(Exception $e) { $pkg['facilities'] = []; }
            try {
                $cnt = $db->prepare("SELECT COUNT(DISTINCT h.id) FROM hotels h JOIN rooms r ON r.hotel_id=h.id WHERE r.type=? AND h.is_verified=1 AND h.is_active=1");
                $cnt->execute([$pkg['type_key']]);
                $pkg['hotel_count'] = (int)$cnt->fetchColumn();
            } catch(Exception $e) { $pkg['hotel_count'] = 0; }
        }
        unset($pkg);
        $packages = $rows;
    }
} catch(Exception $e) { /* table doesn't exist, use fallback */ }

// If DB returned nothing, use fallback with facility data from facilities table
if (empty($packages)) {
    // Map room type → typical facility names
    $typeToFacilities = [
        'standard'     => ['Free WiFi','Air Conditioning','Room Service'],
        'deluxe'       => ['Free WiFi','Air Conditioning','Room Service','Breakfast Included','Balcony'],
        'luxury'       => ['Free WiFi','Air Conditioning','Room Service','Breakfast Included','Spa','Balcony','Mountain View'],
        'family'       => ['Free WiFi','Air Conditioning','Room Service','Breakfast Included','Kids Club'],
        'couple'       => ['Free WiFi','Air Conditioning','Room Service','Breakfast Included','Spa','Balcony'],
        'presidential' => ['Free WiFi','Air Conditioning','Room Service','Breakfast Included','Spa','Gym','Concierge','Balcony','Mountain View'],
    ];
    foreach ($fallback as &$pkg) {
        $names = $typeToFacilities[$pkg['type_key']] ?? [];
        $facs = [];
        if (!empty($names)) {
            try {
                $in = implode(',', array_fill(0, count($names), '?'));
                $fst = $db->prepare("SELECT name,icon,category FROM facilities WHERE name IN ($in)");
                $fst->execute($names);
                $facs = $fst->fetchAll();
            } catch(Exception $e) { $facs = []; }
        }
        $pkg['facilities'] = $facs;
        try {
            $cnt = $db->prepare("SELECT COUNT(DISTINCT h.id) FROM hotels h JOIN rooms r ON r.hotel_id=h.id WHERE r.type=? AND h.is_verified=1 AND h.is_active=1");
            $cnt->execute([$pkg['type_key']]);
            $pkg['hotel_count'] = (int)$cnt->fetchColumn();
        } catch(Exception $e) { $pkg['hotel_count'] = 0; }
    }
    unset($pkg);
    $packages = $fallback;
}

echo json_encode(['success' => true, 'packages' => $packages]);
