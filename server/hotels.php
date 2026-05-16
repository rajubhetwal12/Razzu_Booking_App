<?php
$pageTitle = 'Browse Hotels — LuxStay';
require_once 'config/db.php';
require_once 'includes/auth.php';
$db = getDB();

$city     = trim($_GET['city'] ?? '');
$stars    = (int)($_GET['stars'] ?? 0);
$minPrice = (int)($_GET['min_price'] ?? 0);
$maxPrice = (int)($_GET['max_price'] ?? 0);
$sort     = $_GET['sort'] ?? 'rating';
$featured = isset($_GET['featured']);

$where = ['h.is_active=1'];
$params = [];
if ($city)     { $where[] = '(h.city LIKE ? OR h.name LIKE ?)'; $params[] = "%$city%"; $params[] = "%$city%"; }
if ($stars)    { $where[] = 'h.stars >= ?'; $params[] = $stars; }
if ($minPrice) { $where[] = 'h.min_price >= ?'; $params[] = $minPrice; }
if ($maxPrice) { $where[] = 'h.min_price <= ?'; $params[] = $maxPrice; }
if ($featured) { $where[] = 'h.is_featured=1'; }

$orderBy = match($sort) {
    'price_asc'  => 'h.min_price ASC',
    'price_desc' => 'h.min_price DESC',
    'newest'     => 'h.id DESC',
    default      => 'h.rating DESC',
};
$sql = 'SELECT * FROM hotels h WHERE ' . implode(' AND ', $where) . " ORDER BY $orderBy";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$hotels = $stmt->fetchAll();

include 'includes/header.php';
?>
<div class="page-content">
<div class="container" style="padding-top:2rem;padding-bottom:4rem">
  <div style="display:grid;grid-template-columns:240px 1fr;gap:1.5rem;align-items:start">

    <!-- Sidebar Filters -->
    <aside class="filters-sidebar">
      <form method="GET">
        <h3 class="filter-title">🔍 Filters</h3>
        <div class="filter-group">
          <label class="filter-label">Destination</label>
          <div class="input-wrapper"><span class="input-icon">📍</span>
            <input type="text" name="city" class="form-input" placeholder="City or name" value="<?=e($city)?>">
          </div>
        </div>
        <div class="filter-group">
          <label class="filter-label">Min Stars</label>
          <select name="stars" class="form-input">
            <option value="">Any</option>
            <?php for($s=5;$s>=2;$s--): ?><option value="<?=$s?>" <?=$stars==$s?'selected':''?>><?=$s?>★</option><?php endfor; ?>
          </select>
        </div>
        <div class="filter-group">
          <label class="filter-label">Price Range (NPR)</label>
          <input type="number" name="min_price" class="form-input" placeholder="Min" value="<?=e($minPrice?:'')?>" style="margin-bottom:.5rem">
          <input type="number" name="max_price" class="form-input" placeholder="Max" value="<?=e($maxPrice?:'')?>">
        </div>
        <div class="filter-group">
          <label class="filter-label">Sort By</label>
          <select name="sort" class="form-input">
            <option value="rating" <?=$sort=='rating'?'selected':''?>>Top Rated</option>
            <option value="price_asc" <?=$sort=='price_asc'?'selected':''?>>Price ↑</option>
            <option value="price_desc" <?=$sort=='price_desc'?'selected':''?>>Price ↓</option>
            <option value="newest" <?=$sort=='newest'?'selected':''?>>Newest</option>
          </select>
        </div>
        <button type="submit" class="btn-full btn-primary" style="margin-bottom:.5rem">Apply Filters</button>
        <a href="hotels.php" style="display:block;text-align:center;font-size:.8125rem;color:var(--text3);margin-top:.5rem">Clear All</a>
      </form>
    </aside>

    <!-- Hotel Results -->
    <div>
      <div class="flex items-center justify-between" style="margin-bottom:1.25rem;flex-wrap:wrap;gap:.75rem">
        <h1 style="font-family:'Playfair Display',serif;font-size:1.5rem;font-weight:800;color:var(--text)">
          <?= $featured ? 'Featured Hotels' : ($city ? "Hotels in ".e($city) : 'All Hotels') ?>
          <span style="font-size:.875rem;font-weight:400;color:var(--text3);margin-left:.5rem">(<?= count($hotels) ?> found)</span>
        </h1>
      </div>

      <?php if(empty($hotels)): ?>
        <div style="text-align:center;padding:4rem 2rem;background:var(--card);border:1px solid var(--border);border-radius:14px">
          <div style="font-size:3rem;margin-bottom:1rem">🏨</div>
          <h3 style="color:var(--text);margin-bottom:.5rem">No hotels found</h3>
          <p style="color:var(--text3);font-size:.875rem">Try adjusting your filters</p>
          <a href="hotels.php" class="btn-sm btn-primary" style="margin-top:1.25rem;display:inline-flex">View All Hotels</a>
        </div>
      <?php else: ?>
        <div class="hotel-grid">
          <?php foreach($hotels as $h):
            $amenities = json_decode($h['amenities']??'[]',true);
            $discounted = $h['discount']>0 ? round($h['min_price']*(1-$h['discount']/100)) : null;
          ?>
          <a href="hotel-detail.php?id=<?=$h['id']?>" class="hotel-card">
            <div class="hotel-img-wrap">
              <img src="<?=e($h['cover_image'])?>" alt="<?=e($h['name'])?>" loading="lazy">
              <div class="hotel-img-overlay"></div>
              <div class="hotel-stars"><?=str_repeat('<span class="star-filled">★</span>',$h['stars'])?></div>
              <?php if($h['discount']>0): ?><div class="hotel-discount">-<?=$h['discount']?>% OFF</div><?php endif; ?>
            </div>
            <div class="hotel-body">
              <div class="hotel-name"><?=e($h['name'])?></div>
              <div class="hotel-location">📍 <?=e($h['city'])?>, <?=e($h['country'])?></div>
              <?php if($h['short_desc']): ?>
                <p style="font-size:.8rem;color:var(--text3);margin-bottom:.75rem;line-height:1.5;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden"><?=e($h['short_desc'])?></p>
              <?php endif; ?>
              <div class="hotel-amenities">
                <?php foreach(array_slice($amenities,0,3) as $a): ?>
                  <span class="amenity-tag"><?=e($a)?></span>
                <?php endforeach; ?>
              </div>
              <div class="hotel-footer">
                <div class="hotel-price">
                  <?php if($discounted): ?><s style="font-size:.7rem;color:var(--text3);font-weight:400">NPR <?=number_format($h['min_price'])?></s><br><?php endif; ?>
                  NPR <?=number_format($discounted??$h['min_price'])?>
                  <small>per night · <?=$h['rating']?>★ (<?=$h['review_count']?>)</small>
                </div>
                <span class="badge badge-gold">Book →</span>
              </div>
            </div>
          </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
</div>
<?php include 'includes/footer.php'; ?>
