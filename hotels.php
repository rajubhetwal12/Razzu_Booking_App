<?php
$pageTitle='Browse Hotels — LuxStay';
require_once __DIR__.'/config/db.php';
require_once __DIR__.'/includes/auth.php';
$db=getDB();
$city=trim($_GET['city']??'');$stars=(int)($_GET['stars']??0);
$minP=(int)($_GET['min_price']??0);$maxP=(int)($_GET['max_price']??0);
$sort=$_GET['sort']??'rating';$feat=isset($_GET['featured']);
$roomType=trim($_GET['room_type']??'');
$validTypes=['standard','deluxe','luxury','presidential','couple','family'];
if(!in_array($roomType,$validTypes))$roomType='';
$where=['h.is_active=1','h.is_verified=1'];$params=[];
if($city){$where[]='(h.city LIKE ? OR h.name LIKE ?)';$params[]="%$city%";$params[]="%$city%";}
if($stars){$where[]='h.stars>=?';$params[]=$stars;}
if($minP){$where[]='h.min_price>=?';$params[]=$minP;}
if($maxP){$where[]='h.max_price<=?';$params[]=$maxP;}
if($feat){$where[]='h.is_featured=1';}
if($roomType){$where[]='EXISTS(SELECT 1 FROM rooms r WHERE r.hotel_id=h.id AND r.type=? AND r.is_available=1)';$params[]=$roomType;}
$ob=match($sort){'price_asc'=>'h.min_price ASC','price_desc'=>'h.min_price DESC','newest'=>'h.id DESC',default=>'h.rating DESC,h.is_featured DESC'};
$st=$db->prepare('SELECT * FROM hotels h WHERE '.implode(' AND ',$where)." ORDER BY $ob");
$st->execute($params);$hotels=$st->fetchAll();
$typeLabels=['standard'=>['Standard','🛏️','#6b7280'],'deluxe'=>['Deluxe','✨','#3b82f6'],'luxury'=>['Luxury','👑','#d4a017'],'family'=>['Family','👨‍👩‍👧‍👦','#10b981'],'couple'=>['Couple','💑','#ec4899'],'presidential'=>['Presidential','🏆','#f59e0b']];
include __DIR__.'/includes/header.php';
?>
<div style="padding:2rem 0 4rem">
<div class="container">
  <div style="display:grid;grid-template-columns:230px 1fr;gap:1.5rem;align-items:start">
    <!-- Filter Sidebar -->
    <aside class="card p3 rounded" style="position:sticky;top:80px">
      <h3 style="font-weight:700;margin-bottom:1.25rem">🔍 Filter Hotels</h3>
      <form method="GET">
        <div class="form-group">
          <label class="form-label">Destination</label>
          <div class="input-wrap"><span class="input-icon">📍</span>
            <input type="text" name="city" class="form-input" placeholder="City or hotel" value="<?=e($city)?>">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Min Stars</label>
          <select name="stars" class="form-input">
            <option value="">Any Stars</option>
            <?php for($s=5;$s>=2;$s--):?><option value="<?=$s?>" <?=$stars==$s?'selected':''?>><?=$s?>★ & above</option><?php endfor;?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Min Price (NPR)</label>
          <input type="number" name="min_price" class="form-input" placeholder="5000" value="<?=$minP?:''?>">
        </div>
        <div class="form-group">
          <label class="form-label">Max Price (NPR)</label>
          <input type="number" name="max_price" class="form-input" placeholder="80000" value="<?=$maxP?:''?>">
        </div>
        <div class="form-group">
          <label class="form-label">Sort By</label>
          <select name="sort" class="form-input">
            <option value="rating"     <?=$sort==='rating'?'selected':''?>>Top Rated</option>
            <option value="price_asc"  <?=$sort==='price_asc'?'selected':''?>>Price: Low → High</option>
            <option value="price_desc" <?=$sort==='price_desc'?'selected':''?>>Price: High → Low</option>
            <option value="newest"     <?=$sort==='newest'?'selected':''?>>Newest</option>
          </select>
        </div>
        <label style="display:flex;align-items:center;gap:.5rem;font-size:.875rem;color:var(--text2);margin-bottom:1rem;cursor:pointer">
          <input type="checkbox" name="featured" value="1" <?=$feat?'checked':''?> style="accent-color:var(--gold);width:15px;height:15px">
          Featured Hotels Only
        </label>
        <button type="submit" class="btn btn-primary btn-full mb2">Apply Filters</button>
        <a href="<?=BASE_URL?>/hotels.php" style="display:block;text-align:center;font-size:.8rem;color:var(--text3)">Clear Filters</a>
      </form>
    </aside>

    <!-- Results -->
    <div>
      <!-- Room Type Quick-Filter Pills -->
      <div style="display:flex;flex-wrap:wrap;gap:.5rem;margin-bottom:1.25rem">
        <a href="<?=BASE_URL?>/hotels.php?<?=http_build_query(array_filter(['city'=>$city,'stars'=>$stars,'sort'=>$sort,'featured'=>$feat?1:null]))?>" 
           style="padding:.35rem .875rem;border-radius:20px;font-size:.78rem;font-weight:600;text-decoration:none;border:1.5px solid <?=$roomType===''?'var(--gold)':'var(--border)'?>;color:<?=$roomType===''?'var(--gold)':'var(--text3)'?>;background:<?=$roomType===''?'rgba(212,160,23,.1)':'transparent'?>;transition:.2s">All Types</a>
        <?php foreach($typeLabels as $tk=>[$tl,$ti,$tc]):?>
          <a href="<?=BASE_URL?>/hotels.php?room_type=<?=urlencode($tk)?>&<?=http_build_query(array_filter(['city'=>$city,'stars'=>$stars,'sort'=>$sort,'featured'=>$feat?1:null]))?>" 
             style="padding:.35rem .875rem;border-radius:20px;font-size:.78rem;font-weight:600;text-decoration:none;border:1.5px solid <?=$roomType===$tk?$tc:'var(--border)'?>;color:<?=$roomType===$tk?$tc:'var(--text3)'?>;background:<?=$roomType===$tk?"rgba(0,0,0,.15)":'transparent'?>;transition:.2s"><?=$ti?> <?=$tl?></a>
        <?php endforeach;?>
      </div>
      <div class="flex aic jcsb mb3" style="flex-wrap:wrap;gap:.75rem">
        <div>
          <h1 style="font-family:'Playfair Display',serif;font-size:1.5rem;font-weight:800">
            <?php if($roomType&&isset($typeLabels[$roomType])):echo $typeLabels[$roomType][1].' '.$typeLabels[$roomType][0].' Hotels';
            elseif($feat):echo '⭐ Featured Hotels';
            elseif($city):echo 'Hotels in '.e($city);
            else:echo 'All Luxury Hotels';endif;?>
          </h1>
          <p style="color:var(--text3);font-size:.8rem;margin-top:.2rem"><?=count($hotels)?> hotel<?=count($hotels)!=1?'s':''?> found</p>
        </div>
        <div class="flex gap-1" style="flex-wrap:wrap">
          <?php if($city):?><span class="badge badge-gold">📍 <?=e($city)?></span><?php endif;?>
          <?php if($stars):?><span class="badge badge-gold"><?=$stars?>★+</span><?php endif;?>
          <?php if($roomType&&isset($typeLabels[$roomType])):?><span class="badge" style="background:<?=$typeLabels[$roomType][2]?>22;color:<?=$typeLabels[$roomType][2]?>;border:1px solid <?=$typeLabels[$roomType][2]?>44"><?=$typeLabels[$roomType][1]?> <?=$typeLabels[$roomType][0]?></span><?php endif;?>
        </div>
      </div>

      <?php if(empty($hotels)):?>
        <div style="text-align:center;padding:4rem 2rem;background:var(--card);border:1px solid var(--border);border-radius:var(--rad)">
          <div style="font-size:3rem;margin-bottom:1rem">🔍</div>
          <h3 style="margin-bottom:.5rem">No Hotels Found</h3>
          <p style="color:var(--text3);font-size:.875rem;margin-bottom:1.25rem">Try adjusting your filters.</p>
          <a href="<?=BASE_URL?>/hotels.php" class="btn btn-primary">View All Hotels</a>
        </div>
      <?php else:?>
        <div class="hotels-grid">
          <?php foreach($hotels as $h):
            $am=json_decode($h['amenities']??'[]',true)?:[];
            $dp=$h['discount']>0?round($h['min_price']*(1-$h['discount']/100)):null;
          ?>
            <a href="<?=BASE_URL?>/hotel-detail.php?id=<?=$h['id']?>" class="hotel-card">
              <div class="hotel-img">
                <img src="<?=e($h['cover_image'])?>" alt="<?=e($h['name'])?>" loading="lazy">
                <div class="hotel-img-overlay"></div>
                <div class="hotel-stars"><?=str_repeat('★',(int)$h['stars'])?></div>
                <?php if($h['discount']>0):?><div class="hotel-disc">-<?=$h['discount']?>% OFF</div><?php endif;?>
                <?php if($h['is_featured']):?><div class="hotel-feat">⭐ Featured</div><?php endif;?>
              </div>
              <div class="hotel-body">
                <div class="hotel-name"><?=e($h['name'])?></div>
                <div class="hotel-loc">📍 <?=e($h['city'])?>, <?=e($h['country'])?></div>
                <div class="hotel-desc"><?=e($h['short_desc']??'')?></div>
                <?php
                // Show available room types as tags
                try{
                  $rtypes=$db->prepare('SELECT DISTINCT type FROM rooms WHERE hotel_id=? AND is_available=1 LIMIT 4');
                  $rtypes->execute([$h['id']]);
                  $rtypes=$rtypes->fetchAll(PDO::FETCH_COLUMN);
                }catch(Exception $re){$rtypes=[];}
                ?>
                <div class="hotel-tags"><?php foreach($rtypes as $rt):$tc2=$typeLabels[$rt][2]??'#d4a017';?><span class="hotel-tag" style="border-color:<?=$tc2?>44;color:<?=$tc2?>"><?=$typeLabels[$rt][1]??''?> <?=ucfirst($rt)?></span><?php endforeach;?></div>
                <div class="hotel-foot">
                  <div class="hotel-price">
                    <?php if($dp):?><s style="font-size:.67rem;color:var(--text3);font-weight:400">NPR <?=number_format($h['min_price'])?></s><br><?php endif;?>
                    NPR <?=number_format($dp??$h['min_price'])?>
                    <small>/night · <?=$h['rating']?>★ (<?=$h['review_count']?>)</small>
                  </div>
                  <span class="badge badge-gold">Book →</span>
                </div>
              </div>
            </a>
          <?php endforeach;?>
        </div>
      <?php endif;?>
    </div>
  </div>
</div>
</div>
<?php include __DIR__.'/includes/footer.php';?>
