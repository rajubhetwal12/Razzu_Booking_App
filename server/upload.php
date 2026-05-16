<?php
/**
 * server/upload.php — Secure image upload handler
 * Returns JSON: {success, url, error}
 */
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) { echo json_encode(['success'=>false,'error'=>'Not logged in']); exit; }

$role = $_SESSION['urole'] ?? '';
if (!in_array($role, ['admin','manager'])) { echo json_encode(['success'=>false,'error'=>'Permission denied']); exit; }

$type = $_POST['type'] ?? 'hotel'; // hotel | room | user | review
$id   = (int)($_POST['id'] ?? 0);

// Validate type
if (!in_array($type, ['hotel','room','user','review'])) {
    echo json_encode(['success'=>false,'error'=>'Invalid type']); exit;
}

$uploadDir = __DIR__.'/../assets/uploads/'.$type.'s/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success'=>false,'error'=>'No file or upload error']); exit;
}

$file = $_FILES['image'];
$maxSize = 5 * 1024 * 1024; // 5MB
if ($file['size'] > $maxSize) { echo json_encode(['success'=>false,'error'=>'File too large (max 5MB)']); exit; }

// Validate MIME
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime  = $finfo->file($file['tmp_name']);
$allowed = ['image/jpeg','image/png','image/webp','image/gif'];
if (!in_array($mime, $allowed)) { echo json_encode(['success'=>false,'error'=>'Invalid file type']); exit; }

// Generate unique filename
$ext = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif'][$mime];
$filename = $type.'_'.time().'_'.bin2hex(random_bytes(4)).'.'.$ext;
$savePath = $uploadDir.$filename;

// Resize & compress with GD
if (function_exists('imagecreatefromstring')) {
    $src = imagecreatefromstring(file_get_contents($file['tmp_name']));
    if ($src) {
        $w = imagesx($src); $h = imagesy($src);
        $maxW = 1400; $maxH = 1000;
        $ratio = min($maxW/$w, $maxH/$h, 1);
        $nw = (int)($w*$ratio); $nh = (int)($h*$ratio);
        $dst = imagecreatetruecolor($nw, $nh);
        // Preserve transparency for PNG
        if ($mime === 'image/png') {
            imagealphablending($dst, false); imagesavealpha($dst, true);
        }
        imagecopyresampled($dst, $src, 0,0,0,0, $nw,$nh,$w,$h);
        if ($mime === 'image/jpeg') imagejpeg($dst, $savePath, 85);
        elseif ($mime === 'image/png') imagepng($dst, $savePath, 6);
        else imagejpeg($dst, $savePath, 85);
        imagedestroy($src); imagedestroy($dst);
    } else {
        move_uploaded_file($file['tmp_name'], $savePath);
    }
} else {
    move_uploaded_file($file['tmp_name'], $savePath);
}

$url = BASE_URL.'/assets/uploads/'.$type.'s/'.$filename;

// Save to DB
$db = getDB();
try {
    if ($type === 'hotel' && $id) {
        $isCover = (int)($_POST['is_cover'] ?? 0);
        if ($isCover) $db->prepare('UPDATE hotel_images SET is_cover=0 WHERE hotel_id=?')->execute([$id]);
        $db->prepare('INSERT INTO hotel_images(hotel_id,image_url,caption,is_cover,sort_order) VALUES(?,?,?,?,?)')
           ->execute([$id, $url, $_POST['caption']??'', $isCover,
                      (int)$db->query("SELECT COALESCE(MAX(sort_order),0)+1 FROM hotel_images WHERE hotel_id=$id")->fetchColumn()]);
        if ($isCover) $db->prepare('UPDATE hotels SET cover_image=? WHERE id=?')->execute([$url,$id]);
    } elseif ($type === 'room' && $id) {
        $isPrimary = (int)($_POST['is_primary'] ?? 0);
        $hotelId   = (int)($_POST['hotel_id'] ?? 0);
        if ($isPrimary) $db->prepare('UPDATE room_images SET is_primary=0 WHERE room_id=?')->execute([$id]);
        $db->prepare('INSERT INTO room_images(room_id,hotel_id,image_url,caption,is_primary) VALUES(?,?,?,?,?)')
           ->execute([$id, $hotelId, $url, $_POST['caption']??'', $isPrimary]);
        if ($isPrimary) $db->prepare('UPDATE rooms SET image=? WHERE id=?')->execute([$url,$id]);
    }
} catch (PDOException $e) {
    // Image saved to disk even if DB insert fails
}

echo json_encode(['success'=>true,'url'=>$url,'filename'=>$filename]);
