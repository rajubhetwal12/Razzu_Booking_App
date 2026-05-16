<?php
/**
 * server/nearby.php — Returns hotels near given lat/lng as JSON
 * Called via: fetch('/project/server/nearby.php?lat=27.7&lng=85.3&radius=50')
 */
require_once __DIR__.'/../config/db.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$lat    = (float)($_GET['lat']    ?? 0);
$lng    = (float)($_GET['lng']    ?? 0);
$radius = min((int)($_GET['radius'] ?? 50), 200); // max 200km

if (!$lat || !$lng) {
    echo json_encode(['success'=>false,'error'=>'Invalid coordinates']);
    exit;
}

$db = getDB();

// Haversine formula in SQL to calculate distance in km
$sql = "
SELECT id, name, slug, city, country, address,
       cover_image, min_price, max_price, rating, review_count,
       stars, is_featured, discount, latitude, longitude,
       (6371 * ACOS(
           COS(RADIANS(:lat)) * COS(RADIANS(latitude)) *
           COS(RADIANS(longitude) - RADIANS(:lng)) +
           SIN(RADIANS(:lat)) * SIN(RADIANS(latitude))
       )) AS distance_km
FROM hotels
WHERE is_active = 1
  AND is_verified = 1
  AND latitude IS NOT NULL
  AND longitude IS NOT NULL
HAVING distance_km <= :radius
ORDER BY distance_km ASC
LIMIT 20
";

$st = $db->prepare($sql);
$st->execute([':lat'=>$lat, ':lng'=>$lng, ':radius'=>$radius]);
$hotels = $st->fetchAll();

// Format for frontend
$results = array_map(fn($h) => [
    'id'           => (int)$h['id'],
    'name'         => $h['name'],
    'slug'         => $h['slug'],
    'city'         => $h['city'],
    'address'      => $h['address'],
    'cover_image'  => $h['cover_image'],
    'min_price'    => (int)$h['min_price'],
    'rating'       => (float)$h['rating'],
    'review_count' => (int)$h['review_count'],
    'stars'        => (int)$h['stars'],
    'is_featured'  => (bool)$h['is_featured'],
    'discount'     => (int)$h['discount'],
    'latitude'     => (float)$h['latitude'],
    'longitude'    => (float)$h['longitude'],
    'distance_km'  => round((float)$h['distance_km'], 1),
    'url'          => BASE_URL.'/hotel-detail.php?id='.$h['id'],
], $hotels);

echo json_encode(['success'=>true, 'count'=>count($results), 'hotels'=>$results]);
