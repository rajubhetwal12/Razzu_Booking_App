<?php
/* manager_actions.php – included by manager.php AFTER session/db/auth are loaded */

function mgr_upload(array $f, string $folder): string {
    $mt = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    $ext = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'][$mt] ?? '';
    if (!$ext || $f['size'] > 5*1024*1024) return '';
    $dir = __DIR__.'/../assets/uploads/'.$folder.'/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $fn  = $folder.'_'.time().'_'.bin2hex(random_bytes(4)).'.'.$ext;
    if (function_exists('imagecreatefromstring')) {
        $src = @imagecreatefromstring(file_get_contents($f['tmp_name']));
        if ($src) {
            $w=imagesx($src);$h=imagesy($src);$r=min(1400/$w,1000/$h,1.0);
            $nw=(int)($w*$r);$nh=(int)($h*$r);
            $dst=imagecreatetruecolor($nw,$nh);
            if ($mt==='image/png'){imagealphablending($dst,false);imagesavealpha($dst,true);}
            imagecopyresampled($dst,$src,0,0,0,0,$nw,$nh,$w,$h);
            $mt==='image/png' ? imagepng($dst,$dir.$fn,6) : imagejpeg($dst,$dir.$fn,85);
            imagedestroy($src);imagedestroy($dst);
        } else move_uploaded_file($f['tmp_name'],$dir.$fn);
    } else move_uploaded_file($f['tmp_name'],$dir.$fn);
    return BASE_URL.'/assets/uploads/'.$folder.'/'.$fn;
}

function mgr_owns(PDO $db, int $hid, int $uid): bool {
    $s=$db->prepare('SELECT id FROM hotels WHERE id=? AND owner_id=?');
    $s->execute([$hid,$uid]); return (bool)$s->fetch();
}

global $db, $user;

/* ── GET actions ── */
if (isset($_GET['approve'])) { $db->prepare('UPDATE bookings SET status="confirmed" WHERE id=?')->execute([(int)$_GET['approve']]); redirect(BASE_URL.'/dashboard/manager.php?tab=bookings'); }
if (isset($_GET['cancel']))  { $db->prepare('UPDATE bookings SET status="cancelled"  WHERE id=?')->execute([(int)$_GET['cancel']]);  redirect(BASE_URL.'/dashboard/manager.php?tab=bookings'); }

if (isset($_GET['del_img'])) {
    $i=$db->prepare('SELECT hi.*,h.owner_id FROM hotel_images hi JOIN hotels h ON h.id=hi.hotel_id WHERE hi.id=?');
    $i->execute([(int)$_GET['del_img']]); $i=$i->fetch();
    if ($i && $i['owner_id']==$user['id']) {
        $loc=__DIR__.'/../'.ltrim(str_replace(BASE_URL,'',parse_url($i['image_url'],PHP_URL_PATH)),'//');
        if (file_exists($loc)) @unlink($loc);
        $db->prepare('DELETE FROM hotel_images WHERE id=?')->execute([$i['id']]);
        flash('success','Image deleted.');
    }
    redirect(BASE_URL.'/dashboard/manager.php?tab=images&hotel_id='.($i['hotel_id']??0));
}
if (isset($_GET['del_room'])) {
    $rid=(int)$_GET['del_room']; $hid2=(int)($_GET['hotel_id']??0);
    $r=$db->prepare('SELECT r.id,h.owner_id FROM rooms r JOIN hotels h ON h.id=r.hotel_id WHERE r.id=?');
    $r->execute([$rid]); $r=$r->fetch();
    if ($r && $r['owner_id']==$user['id']) $db->prepare('DELETE FROM rooms WHERE id=?')->execute([$rid]);
    redirect(BASE_URL.'/dashboard/manager.php?tab=rooms&hotel_id='.$hid2);
}
if (isset($_GET['del_hl'])) {
    $hid2=(int)($_GET['hotel_id']??0);
    if (mgr_owns($db,$hid2,$user['id'])) try{ $db->prepare('DELETE FROM hotel_highlights WHERE id=? AND hotel_id=?')->execute([(int)$_GET['del_hl'],$hid2]); }catch(PDOException $e){}
    redirect(BASE_URL.'/dashboard/manager.php?tab=about&hotel_id='.$hid2);
}
if (isset($_GET['set_cover'])) {
    $hid2=(int)($_GET['hotel_id']??0); $imgId=(int)$_GET['set_cover'];
    $i=$db->prepare('SELECT hi.*,h.owner_id FROM hotel_images hi JOIN hotels h ON h.id=hi.hotel_id WHERE hi.id=?');
    $i->execute([$imgId]); $i=$i->fetch();
    if ($i && $i['owner_id']==$user['id']) {
        $db->prepare('UPDATE hotel_images SET is_cover=0 WHERE hotel_id=?')->execute([$hid2]);
        $db->prepare('UPDATE hotel_images SET is_cover=1 WHERE id=?')->execute([$imgId]);
        $db->prepare('UPDATE hotels SET cover_image=? WHERE id=?')->execute([$i['image_url'],$hid2]);
        flash('success','Cover image updated!');
    }
    redirect(BASE_URL.'/dashboard/manager.php?tab=images&hotel_id='.$hid2);
}

/* ── POST actions ── */
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $hid = (int)($_POST['hotel_id']??0);

    /* Add Hotel */
    if (isset($_POST['add_hotel'])) {
        $nm=trim($_POST['name']??''); $ct=trim($_POST['city']??'');
        $de=trim($_POST['description']??''); $sd=trim($_POST['short_desc']??'');
        $st=(int)($_POST['stars']??5); $mp=(float)($_POST['min_price']??5000);
        $cat=$_POST['category']??'luxury'; $ph=trim($_POST['phone']??'');
        $em=trim($_POST['email']??''); $addr=trim($_POST['address']??'');
        $ci=$_POST['check_in']??'14:00'; $co=$_POST['check_out']??'11:00';
        $slug=preg_replace('/[^a-z0-9]+/','-',strtolower($nm)).'-'.substr(uniqid(),8);
        $cv='';
        if (!empty($_FILES['cover_file']['tmp_name'])&&$_FILES['cover_file']['error']===UPLOAD_ERR_OK)
            $cv=mgr_upload($_FILES['cover_file'],'hotels');
        $db->prepare('INSERT INTO hotels(owner_id,name,slug,description,short_desc,category,stars,cover_image,city,country,address,phone,email,check_in_time,check_out_time,min_price,max_price,rating,is_active,is_verified)VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,4.5,1,0)')
           ->execute([$user['id'],$nm,$slug,$de,$sd,$cat,$st,$cv,$ct,'Nepal',$addr,$ph,$em,$ci,$co,$mp,$mp*5]);
        $nid=(int)$db->lastInsertId();
        if ($cv&&$nid) $db->prepare('INSERT INTO hotel_images(hotel_id,image_url,caption,is_cover,sort_order)VALUES(?,?,?,1,1)')->execute([$nid,$cv,'Cover']);
        try{ $db->prepare('INSERT IGNORE INTO hotel_policies(hotel_id,check_in_time,check_out_time)VALUES(?,?,?)')->execute([$nid,$ci,$co]); }catch(PDOException $e){}
        flash('success','Hotel "'.$nm.'" submitted for approval!');
        redirect(BASE_URL.'/dashboard/manager.php?tab=images&hotel_id='.$nid);
    }

    /* Edit Hotel */
    if (isset($_POST['edit_hotel']) && mgr_owns($db,$hid,$user['id'])) {
        $db->prepare('UPDATE hotels SET name=?,city=?,short_desc=?,description=?,category=?,stars=?,min_price=?,phone=?,email=?,address=?,check_in_time=?,check_out_time=? WHERE id=?')
           ->execute([trim($_POST['name']),trim($_POST['city']),trim($_POST['short_desc']),trim($_POST['description']),$_POST['category'],(int)$_POST['stars'],(float)$_POST['min_price'],trim($_POST['phone']),trim($_POST['email']),trim($_POST['address']),$_POST['check_in'],$_POST['check_out'],$hid]);
        if (!empty($_FILES['cover_file']['tmp_name'])&&$_FILES['cover_file']['error']===UPLOAD_ERR_OK) {
            $url=mgr_upload($_FILES['cover_file'],'hotels');
            if ($url) {
                $nx=(int)$db->query("SELECT COALESCE(MAX(sort_order),0)+1 FROM hotel_images WHERE hotel_id=$hid")->fetchColumn();
                $db->prepare('UPDATE hotels SET cover_image=? WHERE id=?')->execute([$url,$hid]);
                $db->prepare('UPDATE hotel_images SET is_cover=0 WHERE hotel_id=?')->execute([$hid]);
                $db->prepare('INSERT INTO hotel_images(hotel_id,image_url,caption,is_cover,sort_order)VALUES(?,?,?,1,?)')->execute([$hid,$url,'Cover',$nx]);
            }
        }
        flash('success','Hotel updated!');
        redirect(BASE_URL.'/dashboard/manager.php?tab=edit_hotel&hotel_id='.$hid);
    }

    /* Save Facilities */
    if (isset($_POST['save_facilities']) && mgr_owns($db,$hid,$user['id'])) {
        $db->prepare('DELETE FROM hotel_facilities WHERE hotel_id=?')->execute([$hid]);
        $st=$db->prepare('INSERT IGNORE INTO hotel_facilities(hotel_id,facility_id)VALUES(?,?)');
        foreach (array_map('intval',$_POST['fids']??[]) as $fid) if ($fid>0) $st->execute([$hid,$fid]);
        flash('success','Facilities saved!');
        redirect(BASE_URL.'/dashboard/manager.php?tab=facilities&hotel_id='.$hid);
    }

    /* Save Hotel Types */
    if (isset($_POST['save_hotel_types']) && mgr_owns($db,$hid,$user['id'])) {
        try {
            $db->prepare('DELETE FROM hotel_offered_types WHERE hotel_id=?')->execute([$hid]);
            $st=$db->prepare('INSERT IGNORE INTO hotel_offered_types(hotel_id,type_key)VALUES(?,?)');
            $valid=['standard','deluxe','luxury','presidential','couple','family'];
            foreach ($_POST['types']??[] as $t) if (in_array($t,$valid)) $st->execute([$hid,$t]);
            flash('success','Hotel types saved!');
        } catch(PDOException $e){ flash('error','Please run hotel_settings_migration.sql first.'); }
        redirect(BASE_URL.'/dashboard/manager.php?tab=hotel_types&hotel_id='.$hid);
    }

    /* Save Policies */
    if (isset($_POST['save_policies']) && mgr_owns($db,$hid,$user['id'])) {
        try {
            $pm=implode(',',array_filter($_POST['payment_methods']??[]));
            $db->prepare('INSERT INTO hotel_policies(hotel_id,check_in_time,check_out_time,cancellation_policy,smoking_policy,pet_policy,child_policy,extra_bed_policy,payment_methods,age_restriction,important_info)VALUES(?,?,?,?,?,?,?,?,?,?,?)ON DUPLICATE KEY UPDATE check_in_time=VALUES(check_in_time),check_out_time=VALUES(check_out_time),cancellation_policy=VALUES(cancellation_policy),smoking_policy=VALUES(smoking_policy),pet_policy=VALUES(pet_policy),child_policy=VALUES(child_policy),extra_bed_policy=VALUES(extra_bed_policy),payment_methods=VALUES(payment_methods),age_restriction=VALUES(age_restriction),important_info=VALUES(important_info)')
               ->execute([$hid,$_POST['check_in']??'14:00',$_POST['check_out']??'11:00',trim($_POST['cancellation_policy']??''),$_POST['smoking_policy']??'not_allowed',$_POST['pet_policy']??'not_allowed',trim($_POST['child_policy']??''),trim($_POST['extra_bed_policy']??''),$pm,(int)($_POST['age_restriction']??0),trim($_POST['important_info']??'')]);
            $db->prepare('UPDATE hotels SET check_in_time=?,check_out_time=? WHERE id=?')->execute([$_POST['check_in']??'14:00',$_POST['check_out']??'11:00',$hid]);
            flash('success','Policies saved!');
        } catch(PDOException $e){ flash('error','Run hotel_settings_migration.sql first.'); }
        redirect(BASE_URL.'/dashboard/manager.php?tab=policies&hotel_id='.$hid);
    }

    /* Save About */
    if (isset($_POST['save_about']) && mgr_owns($db,$hid,$user['id'])) {
        $db->prepare('UPDATE hotels SET description=?,short_desc=? WHERE id=?')->execute([trim($_POST['description']??''),trim($_POST['short_desc']??''),$hid]);
        flash('success','About section updated!');
        redirect(BASE_URL.'/dashboard/manager.php?tab=about&hotel_id='.$hid);
    }

    /* Add Highlight */
    if (isset($_POST['add_highlight']) && mgr_owns($db,$hid,$user['id'])) {
        $t=trim($_POST['h_title']??'');
        if ($t) try {
            $nx=(int)$db->query("SELECT COALESCE(MAX(sort_order),0)+1 FROM hotel_highlights WHERE hotel_id=$hid")->fetchColumn();
            $db->prepare('INSERT INTO hotel_highlights(hotel_id,icon,title,detail,sort_order)VALUES(?,?,?,?,?)')->execute([$hid,trim($_POST['h_icon']??'✨'),$t,trim($_POST['h_detail']??''),$nx]);
            flash('success','Highlight added!');
        } catch(PDOException $e){ flash('error','Run migration first.'); }
        redirect(BASE_URL.'/dashboard/manager.php?tab=about&hotel_id='.$hid);
    }

    /* Add Room */
    if (isset($_POST['add_room']) && mgr_owns($db,$hid,$user['id'])) {
        $ri='';
        if (!empty($_FILES['room_img']['tmp_name'])&&$_FILES['room_img']['error']===UPLOAD_ERR_OK)
            $ri=mgr_upload($_FILES['room_img'],'rooms');
        $db->prepare('INSERT INTO rooms(hotel_id,type,name,description,base_price,quantity,max_guests,max_adults,max_children,room_size_sqm,image,breakfast_included,cancellation_policy)VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?)')
           ->execute([$hid,$_POST['rtype'],trim($_POST['rname']),trim($_POST['rdesc']),(float)$_POST['rprice'],(int)$_POST['qty'],(int)$_POST['max_guests'],(int)$_POST['max_adults'],(int)$_POST['max_children'],(int)$_POST['room_size'],$ri,(int)($_POST['breakfast']??0),trim($_POST['cancel_policy']??'')]);
        flash('success','Room added!');
        redirect(BASE_URL.'/dashboard/manager.php?tab=rooms&hotel_id='.$hid);
    }

    /* Upload Gallery */
    if (isset($_POST['upload_gallery']) && mgr_owns($db,$hid,$user['id'])) {
        if (!empty($_FILES['gallery_file']['tmp_name'])&&$_FILES['gallery_file']['error']===UPLOAD_ERR_OK) {
            $cap=trim($_POST['caption']??''); $isc=(int)($_POST['is_cover']??0);
            $url=mgr_upload($_FILES['gallery_file'],'hotels');
            if ($url) {
                if ($isc) $db->prepare('UPDATE hotel_images SET is_cover=0 WHERE hotel_id=?')->execute([$hid]);
                $nx=(int)$db->query("SELECT COALESCE(MAX(sort_order),0)+1 FROM hotel_images WHERE hotel_id=$hid")->fetchColumn();
                $db->prepare('INSERT INTO hotel_images(hotel_id,image_url,caption,is_cover,sort_order)VALUES(?,?,?,?,?)')->execute([$hid,$url,$cap,$isc,$nx]);
                if ($isc) $db->prepare('UPDATE hotels SET cover_image=? WHERE id=?')->execute([$url,$hid]);
                flash('success','Image uploaded!');
            }
        }
        redirect(BASE_URL.'/dashboard/manager.php?tab=images&hotel_id='.$hid);
    }

    /* Reply to Review */
    if (isset($_POST['reply_review'])) {
        $rid=(int)($_POST['review_id']??0); $rp=trim($_POST['reply_text']??'');
        $rv=$db->prepare('SELECT r.id,h.owner_id FROM reviews r JOIN hotels h ON h.id=r.hotel_id WHERE r.id=?');
        $rv->execute([$rid]); $rv=$rv->fetch();
        if ($rv&&$rv['owner_id']==$user['id']&&$rp) {
            $db->prepare('UPDATE reviews SET manager_reply=?,replied_at=NOW() WHERE id=?')->execute([$rp,$rid]);
            flash('success','Reply posted!');
        }
        redirect(BASE_URL.'/dashboard/manager.php?tab=reviews&hotel_id='.$hid);
    }
}
