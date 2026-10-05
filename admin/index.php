<?php
require_once dirname(__DIR__).'/includes/bootstrap.php';
require_once dirname(__DIR__).'/includes/admin.php';
require_once dirname(__DIR__).'/includes/css_sanitizer.php';
admin_session_start();
$pdo=db();
$page=isset($_GET['page'])?preg_replace('/[^a-z_]/','',$_GET['page']):'dashboard';
$allowed=['dashboard','sections','services','gallery','content','theme','navigation','settings','testimonials','bookings','account','activity','media'];
if(!in_array($page,$allowed,true))$page='dashboard';
if(isset($_GET['logout'])){admin_logout();header('Location: index.php');exit;}
$error='';$notice='';
foreach(admin_flash_take() as $flash){$flashText=preg_replace('/\s+/',' ',trim((string)($flash['message']??'')));if($flashText==='')continue;if(($flash['type']??'')==='error')$error=$error!==''?$error.' '.$flashText:$flashText;else $notice=$notice!==''?$notice.' '.$flashText:$flashText;}
$brandSettings=$pdo->query("SELECT setting_key,setting_value FROM site_settings WHERE setting_key IN ('logo','logo_admin')")->fetchAll(PDO::FETCH_KEY_PAIR);$adminBrandLogo=$brandSettings['logo_admin']??($brandSettings['logo']??'../assets/logo/wave-photography-primary.png');if($adminBrandLogo!==''&&strpos($adminBrandLogo,'../')!==0)$adminBrandLogo='../'.ltrim($adminBrandLogo,'/');
if(!admin_logged_in()){
  if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['login'])){
    admin_csrf_check();
    $username=trim($_POST['username']??'');$password=$_POST['password']??'';
    $stmt=$pdo->prepare("SELECT * FROM admin_users WHERE username=? AND is_active=1 LIMIT 1");$stmt->execute([$username]);$user=$stmt->fetch();
    if($user && (!empty($user['locked_until']) && strtotime($user['locked_until'])>time())){$error='Too many failed attempts. Please try again later.';}
    elseif($user && password_verify($password,$user['password_hash'])){
      session_regenerate_id(true);$_SESSION['wave_admin_id']=$user['id'];$_SESSION['wave_admin_name']=$user['display_name'];
      $pdo->prepare("UPDATE admin_users SET failed_attempts=0,locked_until=NULL,last_login_at=NOW() WHERE id=?")->execute([$user['id']]);
      admin_log('login','Administrator signed in');header('Location: index.php');exit;
    }else{
      if($user){$fails=(int)$user['failed_attempts']+1;$lock=$fails>=5?date('Y-m-d H:i:s',time()+900):null;$pdo->prepare("UPDATE admin_users SET failed_attempts=?,locked_until=? WHERE id=?")->execute([$fails,$lock,$user['id']]);}
      $error='Invalid username or password.';
    }
  }
  ?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Admin Login | Wave Photography</title><link rel="stylesheet" href="admin.css"></head><body class="login-body"><main class="login-card"><div class="login-logo"><img src="<?=admin_h($adminBrandLogo)?>" alt="Wave Photography"></div><span class="eyebrow">SECURE ADMINISTRATION</span><h1>Welcome back</h1><p>Sign in to manage your photography website.</p><?php if($error):?><div class="alert error"><?=admin_h($error)?></div><?php endif;?><form method="post"><input type="hidden" name="csrf" value="<?=admin_h(admin_csrf())?>"><label>Username<input name="username" autocomplete="username" required autofocus></label><label>Password<input type="password" name="password" autocomplete="current-password" required></label><button class="btn primary full" name="login" value="1">Sign in to dashboard</button></form><a class="back-link" href="../index.php">← Back to website</a></main></body></html><?php
  exit;
}
$adminId=(int)$_SESSION['wave_admin_id'];
function postv($k,$d=''){return trim((string)($_POST[$k]??$d));}
function redirect_page($p){header('Location: index.php?page='.rawurlencode($p));exit;}
function upload_image($field,$existing=''){
  if(empty($_FILES[$field]) || $_FILES[$field]['error']===UPLOAD_ERR_NO_FILE)return $existing;
  $f=$_FILES[$field];
  if($f['error']!==UPLOAD_ERR_OK){
    $messages=[
      UPLOAD_ERR_INI_SIZE=>wave_host_image_cap_message(),
      UPLOAD_ERR_FORM_SIZE=>'The image exceeds the form upload size limit.',
      UPLOAD_ERR_PARTIAL=>'The image upload was interrupted. Please try again.',
      UPLOAD_ERR_NO_TMP_DIR=>'The server temporary upload directory is unavailable.',
      UPLOAD_ERR_CANT_WRITE=>'The server could not write the uploaded image.',
      UPLOAD_ERR_EXTENSION=>'The server blocked this image upload.'
    ];
    throw new RuntimeException($messages[$f['error']]??'Image upload failed. Please try again.');
  }
  if((int)$f['size']>wave_image_cap_bytes())throw new RuntimeException('Each image must be '.wave_image_cap_label().' or smaller.');
  // These only bite when the host is configured below the application cap, but then they are
  // the real reason a large image was refused, so say which one applied.
  if((int)$f['size']>wave_host_image_cap_bytes())throw new RuntimeException(wave_host_image_cap_message());
  if((int)$f['size']>wave_request_cap_bytes())throw new RuntimeException('The hosting server accepts only '.ini_get('post_max_size').' for an entire request (PHP post_max_size), so an image this large cannot be sent. Ask your host to raise post_max_size, or upload a smaller image.');
  $finfo=new finfo(FILEINFO_MIME_TYPE);$mime=$finfo->file($f['tmp_name']);
  $types=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
  if(!isset($types[$mime]))throw new RuntimeException('Only JPG, PNG and WebP images are supported.');
  $dir=dirname(__DIR__).'/uploads/media';if(!is_dir($dir) && !mkdir($dir,0755,true))throw new RuntimeException('Upload directory could not be created.');
  $name=bin2hex(random_bytes(16)).'.'.$types[$mime];
  if(!move_uploaded_file($f['tmp_name'],$dir.'/'.$name))throw new RuntimeException('Could not save uploaded image. Check folder permissions.');
  $relative='uploads/media/'.$name;
  try{$q=db()->prepare("INSERT INTO media_library(original_name,stored_name,file_path,mime_type,file_size,uploaded_by) VALUES(?,?,?,?,?,?)");$q->execute([substr($f['name'],0,255),$name,$relative,$mime,(int)$f['size'],!empty($_SESSION['wave_admin_id'])?(int)$_SESSION['wave_admin_id']:null]);}catch(Throwable $e){error_log('[Wave media library] '.$e->getMessage());}
  return $relative;
}
function make_thumbnail($relative){
 try{
  $source=dirname(__DIR__).'/'.$relative;if(!extension_loaded('gd')||!is_file($source))return $relative;
  $info=@getimagesize($source);if(!$info||empty($info['mime']))return $relative;
  $loaders=['image/jpeg'=>'imagecreatefromjpeg','image/png'=>'imagecreatefrompng','image/webp'=>'imagecreatefromwebp'];
  $loader=$loaders[$info['mime']]??null;if(!$loader||!function_exists($loader))return $relative;
  $w=(int)$info[0];$h=(int)$info[1];if($w<1||$h<1)return $relative;
  $max=720;$scale=min(1,$max/max($w,$h));$nw=max(1,(int)round($w*$scale));$nh=max(1,(int)round($h*$scale));
  // Decoding a modern camera file needs roughly 4 bytes per pixel for the bitmap plus the
  // destination buffer. Going over memory_limit is a fatal error that kills the whole
  // request, so skip the thumbnail instead of taking the batch down with it.
  $needed=(int)ceil($w*$h*4+$nw*$nh*4+2097152);
  $limit=wave_ini_bytes((string)ini_get('memory_limit'));
  if($limit>0&&$needed>$limit*0.75){error_log('[Wave thumbnail] skipped '.$relative.': needs ~'.round($needed/1048576).'MB of '.(int)round($limit/1048576).'MB');return $relative;}
  $src=@$loader($source);if(!$src)return $relative;
  $dst=imagecreatetruecolor($nw,$nh);imagealphablending($dst,false);imagesavealpha($dst,true);
  $transparent=imagecolorallocatealpha($dst,0,0,0,127);imagefilledrectangle($dst,0,0,$nw,$nh,$transparent);
  imagecopyresampled($dst,$src,0,0,0,0,$nw,$nh,$w,$h);
  $dir=dirname($source).'/thumbs';if(!is_dir($dir))@mkdir($dir,0755,true);
  $name=pathinfo($source,PATHINFO_FILENAME).'-thumb.webp';$target=$dir.'/'.$name;
  $ok=function_exists('imagewebp')?imagewebp($dst,$target,82):false;
  imagedestroy($src);imagedestroy($dst);unset($src,$dst);gc_collect_cycles();
  return $ok?'uploads/media/thumbs/'.$name:$relative;
 }catch(Throwable $e){error_log('[Wave thumbnail] '.$e->getMessage());return $relative;}
}
function slugify_admin($s){$s=strtolower(trim($s));$s=preg_replace('/[^a-z0-9]+/','-',$s);return trim($s,'-')?:('section-'.time());}
try{
if($_SERVER['REQUEST_METHOD']==='POST' && !isset($_POST['login'])){
  $bulkChunkRequest=postv('bulk_chunk')==='1'||postv('action')==='chunk_upload';
  if(empty($_POST) && empty($_FILES) && (int)($_SERVER['CONTENT_LENGTH']??0)>0){
   throw new RuntimeException('The hosting server rejected this upload before PHP could read it, which means the batch was bigger than post_max_size ('.ini_get('post_max_size').'). Upload fewer or smaller images at a time, or ask your host to raise post_max_size - this project also ships a .user.ini that raises it where the host allows it.');
  }
 admin_csrf_check();$action=postv('action');
 if($action==='save_section'){
  $id=(int)($_POST['id']??0);$name=postv('name');$cat=postv('category',$name);$slug=slugify_admin(postv('slug',$name));$desc=postv('description');$image=upload_image('cover_image',postv('existing_image'));
  if(!$name)throw new RuntimeException('Section name is required.');
  if($id){$q=$pdo->prepare("UPDATE website_sections SET name=?,slug=?,category=?,description=?,cover_image=?,layout=?,sort_order=?,is_active=? WHERE id=?");$q->execute([$name,$slug,$cat,$desc,$image,postv('layout','gallery'),(int)postv('sort_order'),isset($_POST['is_active'])?1:0,$id]);}
  else{$q=$pdo->prepare("INSERT INTO website_sections(name,slug,category,description,cover_image,layout,sort_order,is_active) VALUES(?,?,?,?,?,?,?,?)");$q->execute([$name,$slug,$cat,$desc,$image,postv('layout','gallery'),(int)postv('sort_order'),isset($_POST['is_active'])?1:0]);}
  $defaultImage=$image?:'assets/gallery/sample-14.svg';
  $pdo->prepare("INSERT INTO services(slug,name,category,image,description,sort_order,is_active) VALUES(?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE name=VALUES(name),category=VALUES(category),image=VALUES(image),description=VALUES(description),sort_order=VALUES(sort_order),is_active=VALUES(is_active)")->execute([$slug,$name,$cat,$defaultImage,$desc,(int)postv('sort_order'),isset($_POST['is_active'])?1:0]);
  admin_log('section_saved',$name);redirect_page('sections');
 }
 if($action==='delete_section'){$id=(int)$_POST['id'];$q=$pdo->prepare("SELECT category FROM website_sections WHERE id=?");$q->execute([$id]);$cat=$q->fetchColumn();if($cat){$pdo->prepare("UPDATE portfolio_photos SET is_active=0 WHERE category=?")->execute([$cat]);$pdo->prepare("UPDATE website_sections SET is_active=0,deleted_at=NOW() WHERE id=?")->execute([$id]);$pdo->prepare("UPDATE services SET is_active=0 WHERE category=?")->execute([$cat]);admin_log('section_archived',$cat);admin_flash('success','"'.$cat.'" is now hidden from the website. You can bring it back at any time from Hidden sections.');}redirect_page('sections');}
 if($action==='restore_section'){
  // Undo exactly what hiding did. Leaving the photographs and services inactive would mean
  // showing the section again still showed an empty gallery, which reads as data loss.
  $id=(int)$_POST['id'];$q=$pdo->prepare("SELECT category FROM website_sections WHERE id=?");$q->execute([$id]);$cat=$q->fetchColumn();
  if($cat){
   $pdo->prepare("UPDATE website_sections SET is_active=1,deleted_at=NULL WHERE id=?")->execute([$id]);
   $pdo->prepare("UPDATE portfolio_photos SET is_active=1 WHERE category=?")->execute([$cat]);
   $pdo->prepare("UPDATE services SET is_active=1 WHERE category=?")->execute([$cat]);
   $restored=(int)$pdo->query("SELECT COUNT(*) FROM portfolio_photos WHERE category=".$pdo->quote($cat))->fetchColumn();
   admin_log('section_restored',$cat);
   admin_flash('success','"'.$cat.'" is visible on the website again, along with its '.$restored.' photograph(s) and services.');
  }else{admin_flash('error','That section could not be found.');}
  redirect_page('sections');
 }
 if($action==='save_service'){
  $id=(int)($_POST['id']??0);$name=postv('name');$slug=slugify_admin(postv('slug',$name));$cat=postv('category',$name);$img=upload_image('image_file',postv('image'));$desc=postv('description');$inc=array_values(array_filter(array_map('trim',explode("\n",postv('inclusions')))));
  if(!$name||!$img)throw new RuntimeException('Service name and service image are required.');
  if($id)$pdo->prepare("UPDATE services SET name=?,slug=?,category=?,image=?,description=?,inclusions=?,sort_order=?,is_active=? WHERE id=?")->execute([$name,$slug,$cat,$img,$desc,json_encode($inc), (int)postv('sort_order'),isset($_POST['is_active'])?1:0,$id]);
  else $pdo->prepare("INSERT INTO services(name,slug,category,image,description,inclusions,sort_order,is_active) VALUES(?,?,?,?,?,?,?,?)")->execute([$name,$slug,$cat,$img,$desc,json_encode($inc),(int)postv('sort_order'),isset($_POST['is_active'])?1:0]);
  $pdo->prepare("INSERT INTO website_sections(name,slug,category,description,cover_image,sort_order,is_active) VALUES(?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE name=VALUES(name),description=VALUES(description),cover_image=VALUES(cover_image),is_active=VALUES(is_active)")->execute([$name,$slug,$cat,$desc,$img,(int)postv('sort_order'),isset($_POST['is_active'])?1:0]);
  admin_log('service_saved',$name);redirect_page('services');
 }
 if($action==='delete_service'){$id=(int)$_POST['id'];$n=$pdo->prepare("SELECT name FROM services WHERE id=?");$n->execute([$id]);$label=$n->fetchColumn();$pdo->prepare("UPDATE services SET is_active=0 WHERE id=?")->execute([$id]);admin_log('service_hidden',(string)$label);admin_flash('success','"'.$label.'" is now hidden from the website. You can show it again at any time.');redirect_page('services');}
 if($action==='restore_service'){$id=(int)$_POST['id'];$n=$pdo->prepare("SELECT name FROM services WHERE id=?");$n->execute([$id]);$label=$n->fetchColumn();if($label===false)admin_flash('error','That service could not be found.');else{$pdo->prepare("UPDATE services SET is_active=1 WHERE id=?")->execute([$id]);admin_log('service_restored',$label);admin_flash('success','"'.$label.'" is visible on the website again.');}redirect_page('services');}
 if($action==='chunk_upload'){
  // Resumable upload used when one image is larger than this host accepts in a single
  // request. The browser sends start -> part* -> finish; each part is a small upload that
  // fits under the host's own upload_max_filesize, so nothing is ever rejected wholesale.
  $phase=postv('chunk_phase');
  wave_chunk_sweep();
  if($phase==='start'){
   $label=basename(str_replace('\\','/',postv('chunk_name')));
   $total=(int)postv('chunk_total');
   if($total<1)throw new RuntimeException('That file reported a size of 0 bytes, so there is nothing to upload.');
   if($total>WAVE_CHUNK_MAX_TOTAL)throw new RuntimeException('That image is '.round($total/1048576).' MB, above the '.round(WAVE_CHUNK_MAX_TOTAL/1048576).' MB maximum for a single image. Please resize it before uploading.');
   if(!is_dir(wave_chunk_root())&&!@mkdir(wave_chunk_root(),0755,true))throw new RuntimeException('The temporary upload folder could not be created.');
   $token=bin2hex(random_bytes(16));
   $chunkDir=wave_chunk_dir($token);
   if(!@mkdir($chunkDir,0750,true))throw new RuntimeException('The upload could not be started. Check that the uploads folder is writable.');
   file_put_contents(wave_chunk_meta_file($chunkDir),json_encode(['name'=>$label,'total'=>$total,'type'=>postv('chunk_type'),'created'=>time()]));
   file_put_contents(wave_chunk_data_file($chunkDir),'');
   wave_json_response(['ok'=>true,'added'=>0,'token'=>$token,'chunkSize'=>wave_chunk_payload_bytes(),'received'=>0,'total'=>$total]);
  }
  if($phase==='part'||$phase==='finish'||$phase==='abort'){
   $token=postv('chunk_token');
   if(!wave_chunk_valid_token($token))throw new RuntimeException('Invalid upload session.');
   $chunkDir=wave_chunk_dir($token);
   $metaFile=wave_chunk_meta_file($chunkDir);
   if(!is_dir($chunkDir)||!is_file($metaFile))throw new RuntimeException('That upload session has expired. Please select the file again.');
   $meta=json_decode((string)file_get_contents($metaFile),true);
   if(!is_array($meta)||!isset($meta['total'],$meta['name'])){wave_chunk_remove($chunkDir);throw new RuntimeException('That upload session is damaged. Please select the file again.');}
   if($phase==='abort'){wave_chunk_remove($chunkDir);wave_json_response(['ok'=>true,'added'=>0,'aborted'=>true,'notes'=>[],'failures'=>[],'message'=>'Upload cancelled.']);}
   $dataFile=wave_chunk_data_file($chunkDir);
   clearstatcache(true,$dataFile);
   if($phase==='part'){
    $offset=(int)postv('chunk_offset');$declared=(int)postv('chunk_total');
    if($declared!==(int)$meta['total'])throw new RuntimeException('The reported file size changed during the upload. Please start again.');
    $have=(int)filesize($dataFile);
    if($offset!==$have)throw new RuntimeException('Data arrived out of order (expected offset '.$have.'). Please retry.');
    $part=$_FILES['chunk_file']??null;
    if(!is_array($part)||is_array($part['name']??null))throw new RuntimeException('A chunk of the file was not received.');
    if((int)$part['error']!==UPLOAD_ERR_OK)throw new RuntimeException('A chunk was refused by the server (upload error '.(int)$part['error'].'). '.wave_upload_error_message((int)$part['error']));
    if($have+(int)$part['size']>(int)$meta['total'])throw new RuntimeException('More data arrived than the file should contain. Please start again.');
    $sink=fopen($dataFile,'ab');$source=fopen($part['tmp_name'],'rb');
    if(!$sink||!$source){if(is_resource($sink))fclose($sink);if(is_resource($source))fclose($source);throw new RuntimeException('A chunk could not be written to disk. Check free space and folder permissions.');}
    $copied=stream_copy_to_stream($source,$sink);
    fclose($sink);fclose($source);
    if($copied!==(int)$part['size'])throw new RuntimeException('A chunk was not written completely. Please retry.');
    wave_json_response(['ok'=>true,'added'=>0,'received'=>$have+(int)$part['size'],'total'=>(int)$meta['total']]);
   }
   // finish: the parts are already contiguous on disk, so verify the whole thing before it
   // is promoted into the media library. A truncated or tampered file must never get in.
   clearstatcache(true,$dataFile);
   $have=(int)filesize($dataFile);
   if($have!==(int)$meta['total']){wave_chunk_remove($chunkDir);throw new RuntimeException('Only '.$have.' of '.$meta['total'].' bytes arrived, so the image was discarded. Please upload it again.');}
   $mime=(new finfo(FILEINFO_MIME_TYPE))->file($dataFile);
   $types=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
   if(!isset($types[$mime])){wave_chunk_remove($chunkDir);throw new RuntimeException('The uploaded data is not a valid JPG, PNG or WebP image, so it was discarded.');}
   $mediaDir=dirname(__DIR__).'/uploads/media';
   if(!is_dir($mediaDir)&&!@mkdir($mediaDir,0755,true)){wave_chunk_remove($chunkDir);throw new RuntimeException('The media folder could not be created.');}
   $storedName=bin2hex(random_bytes(16)).'.'.$types[$mime];
   $final=$mediaDir.'/'.$storedName;
   if(!@rename($dataFile,$final)){wave_chunk_remove($chunkDir);throw new RuntimeException('The assembled image could not be saved. Check free space and folder permissions.');}
   @unlink($metaFile);@rmdir($chunkDir);
   $relative='uploads/media/'.$storedName;
   try{$q=db()->prepare("INSERT INTO media_library(original_name,stored_name,file_path,mime_type,file_size,uploaded_by) VALUES(?,?,?,?,?,?)");$q->execute([substr((string)$meta['name'],0,255),$storedName,$relative,$mime,$have,!empty($_SESSION['wave_admin_id'])?(int)$_SESSION['wave_admin_id']:null]);}catch(Throwable $e){error_log('[Wave media library] '.$e->getMessage());}
   $thumb=make_thumbnail($relative);
   $title=trim(pathinfo((string)$meta['name'],PATHINFO_FILENAME));if($title==='')$title='Photograph';
   $title=function_exists('mb_substr')?mb_substr($title,0,200):substr($title,0,200);
   $category=postv('category');
   $sortOrder=(int)postv('sort_order');
   if($category!==''){
    $pdo->prepare("INSERT INTO portfolio_photos(title,category,image,thumbnail,featured,is_placeholder,sort_order,is_active) VALUES(?,?,?,?,0,0,?,1)")->execute([$title,$category,$relative,$thumb,$sortOrder]);
   }
   admin_log('gallery_chunk_upload',$title.' uploaded in slices ('.$have.' bytes)');
   wave_json_response(['ok'=>true,'added'=>1,'path'=>$relative,'title'=>$title,'received'=>$have,'notes'=>[],'failures'=>[],'message'=>$title.' uploaded to "'.$category.'".']);
  }
  throw new RuntimeException('Unknown upload step.');
 }
 if($action==='bulk_upload'){
  $cat=postv('category');if($cat==='')throw new RuntimeException('Choose the section these photographs belong to.');
  $files=$_FILES['image_files']??null;if(!$files||!is_array($files['name']??null))throw new RuntimeException('Choose at least one image to upload.');
  $names=array_values($files['name']);$sizes=array_values($files['size']??[]);$errors=array_values($files['error']??[]);$types=array_values($files['type']??[]);$tmpNames=array_values($files['tmp_name']??[]);
  $notes=[];
  // PHP drops whole batches silently once max_file_uploads is exceeded, so compare the
  // count the browser sent with the count that actually arrived.
  $sent=max(0,(int)postv('bulk_file_count',0));$received=0;foreach($errors as $uploadErrorCode)if((int)$uploadErrorCode!==UPLOAD_ERR_NO_FILE)$received++;
  $maxFiles=(int)ini_get('max_file_uploads');
  if($sent>$received&&$maxFiles>0)$notes[]=$sent-$received.' of the '.$sent.' images you selected were dropped by this host before upload because it accepts only '.$maxFiles.' files per request - split your selection into smaller batches.';
  if(count($names)>50){$names=array_slice($names,0,50);$sizes=array_slice($sizes,0,50);$errors=array_slice($errors,0,50);$types=array_slice($types,0,50);$tmpNames=array_slice($tmpNames,0,50);$notes[]='Only the first 50 images of your selection were processed (batch limit).';}
  $perFileServerLimit=wave_ini_bytes((string)ini_get('upload_max_filesize'));
  $batchLimit=wave_batch_limit_bytes();
  $queue=[];$queueLabels=[];$batchBytes=0;
  foreach($names as $i=>$original){
   $label=basename(str_replace('\\','/',(string)$original));if($label==='')$label='image #'.($i+1);
   $uploadErrorCode=(int)($errors[$i]??UPLOAD_ERR_NO_FILE);
   if($uploadErrorCode===UPLOAD_ERR_NO_FILE)continue;
   if($uploadErrorCode!==UPLOAD_ERR_OK){$notes[]=$label.' was skipped because it '.wave_upload_error_message($uploadErrorCode).'.';continue;}
   $size=(int)($sizes[$i]??0);
   if($size>wave_image_cap_bytes()){$notes[]=$label.' was skipped because it is larger than '.wave_image_cap_label().'.';continue;}
   if($perFileServerLimit>0&&$size>$perFileServerLimit){$notes[]=$label.' was skipped because it is larger than this host allows per file (upload_max_filesize = '.ini_get('upload_max_filesize').').';continue;}
   if($batchBytes+$size>$batchLimit){$notes[]=$label.' was skipped because the batch would exceed the '.round($batchLimit/1048576).' MB total request size this host accepts (post_max_size = '.ini_get('post_max_size').').';continue;}
   $batchBytes+=$size;$queue[]=$i;$queueLabels[$i]=$label;
  }
  $added=0;$failures=[];
  foreach($queue as $i){
   $_FILES['bulk_one']=['name'=>(string)($names[$i]??''),'type'=>(string)($types[$i]??''),'tmp_name'=>(string)($tmpNames[$i]??''),'error'=>(int)($errors[$i]??UPLOAD_ERR_OK),'size'=>(int)($sizes[$i]??0)];
   $label=$queueLabels[$i];
   try{
    $path=upload_image('bulk_one','');$thumb=make_thumbnail($path);
    $title=trim(pathinfo($label,PATHINFO_FILENAME));if($title==='')$title='Photograph';
    $title=function_exists('mb_substr')?mb_substr($title,0,200):substr($title,0,200);
    $pdo->prepare("INSERT INTO portfolio_photos(title,category,image,thumbnail,featured,is_placeholder,sort_order,is_active) VALUES(?,?,?,?,0,0,?,1)")->execute([$title,$cat,$path,$thumb,(int)postv('sort_order')+$added]);
    $added++;
   }catch(Throwable $fileError){
    $failures[]=$label.' could not be saved ('.$fileError->getMessage().')';
    error_log('[Wave bulk upload] '.$label.': '.$fileError->getMessage());
   }
  }
  $summary=$added.' image(s) uploaded to "'.$cat.'".';
  if($notes)$summary.=' '.count($notes).' skipped: '.implode(' ',array_slice($notes,0,3)).(count($notes)>3?' (+'.(count($notes)-3).' more)':'');
  if($failures)$summary.=' '.count($failures).' failed: '.implode(' ',array_slice($failures,0,3)).(count($failures)>3?' (+'.(count($failures)-3).' more)':'');
  if($added===0){
   admin_log('gallery_bulk_upload_failed',$summary);
   throw new RuntimeException('No images were uploaded. '.$summary);
  }
  admin_log('gallery_bulk_upload',$added.' images uploaded');
  if($bulkChunkRequest)wave_json_response(['ok'=>true,'added'=>$added,'notes'=>$notes,'failures'=>$failures,'message'=>$summary]);
  admin_flash($notes||$failures?'error':'success',$summary);redirect_page('gallery');
 }
 if($action==='save_photo'){
  $id=(int)($_POST['id']??0);$title=postv('title');$cat=postv('category');$img=upload_image('image_file',postv('image'));$thumb=make_thumbnail($img);if(!$title||!$cat||!$img)throw new RuntimeException('Title, category and image are required.');
  if($id)$pdo->prepare("UPDATE portfolio_photos SET title=?,category=?,image=?,thumbnail=?,featured=?,is_active=?,caption=?,alt_text=?,sort_order=?,is_cover=? WHERE id=?")->execute([$title,$cat,$img,$thumb,isset($_POST['featured'])?1:0,isset($_POST['is_active'])?1:0,postv('caption'),postv('alt_text'),(int)postv('sort_order'),isset($_POST['is_cover'])?1:0,$id]);
  else $pdo->prepare("INSERT INTO portfolio_photos(title,category,image,thumbnail,featured,is_placeholder,sort_order,is_active) VALUES(?,?,?,?,?,0,?,1)")->execute([$title,$cat,$img,$thumb,isset($_POST['featured'])?1:0,(int)postv('sort_order')]);
  admin_log('photo_saved',$title);redirect_page('gallery');
 }
 if($action==='delete_photo'){$pdo->prepare("UPDATE portfolio_photos SET is_active=0 WHERE id=?")->execute([(int)$_POST['id']]);redirect_page('gallery');}
 if($action==='save_content'){foreach(($_POST['content']??[]) as $k=>$v){$pdo->prepare("INSERT INTO website_content(content_key,content_value) VALUES(?,?) ON DUPLICATE KEY UPDATE content_value=VALUES(content_value)")->execute([$k,trim($v)]);}foreach(['hero_image_file'=>'hero_image','about_image_file'=>'about_image'] as $f=>$k){$path=upload_image($f,'');if($path)$pdo->prepare("INSERT INTO website_content(content_key,content_value,content_type) VALUES(?,?,?) ON DUPLICATE KEY UPDATE content_value=VALUES(content_value),content_type=VALUES(content_type)")->execute([$k,$path,'image']);}admin_log('content_updated','Website copy and media updated');redirect_page('content');}
 if($action==='reset_theme'){
$savedDefault=$pdo->prepare("SELECT setting_value FROM site_settings WHERE setting_key='__theme_default_snapshot' LIMIT 1");$savedDefault->execute();$savedDefaultJson=$savedDefault->fetchColumn();
if($savedDefaultJson){$snapshot=json_decode($savedDefaultJson,true);if(is_array($snapshot)&&isset($snapshot['theme'])&&is_array($snapshot['theme'])){foreach($snapshot['theme'] as $key=>$value){if(!is_string($key)||!is_string($value)||strpos($key,'__')===0)continue;$pdo->prepare("INSERT INTO theme_settings(setting_key,setting_value) VALUES(?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)")->execute([$key,$value]);}if(isset($snapshot['logo'])&&is_string($snapshot['logo'])){$pdo->prepare("INSERT INTO site_settings(setting_key,setting_value) VALUES('logo',?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)")->execute([$snapshot['logo']]);}foreach(($snapshot['logos']??[]) as $logoKey=>$logoValue){if(in_array($logoKey,['logo','logo_header','logo_footer','logo_preloader','logo_admin'],true)&&is_string($logoValue))$pdo->prepare("INSERT INTO site_settings(setting_key,setting_value) VALUES(?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)")->execute([$logoKey,$logoValue]);}admin_log('theme_reset','Theme restored to the saved custom defaults');redirect_page('theme');}}
$defaults=['primary_color'=>'#0b6f82','secondary_color'=>'#1c5664','accent_color'=>'#20b99a','page_background'=>'#f5fbfa','section_background'=>'#ffffff','card_background'=>'#ffffff','header_background'=>'#1c5664','footer_background'=>'#073b49','heading_color'=>'#123342','text_color'=>'#344f59','nav_text_color'=>'#ffffff','button_background'=>'#20b99a','button_text_color'=>'#073b49','font_family'=>'Inter','container_width'=>'1200','section_spacing'=>'80','border_radius'=>'18','overlay_menu_hover_color'=>'#20b99a','overlay_menu_text_color'=>'#ffffff','overlay_menu_background'=>'#073b49','header_button_text_color'=>'#073b49','header_button_background'=>'#20b99a','cta_section_text_color'=>'#ffffff','dark_section_text_color'=>'#ffffff','hero_background'=>'#eaf8f5','hero_text_color'=>'#123342','hero_heading_color'=>'#ffffff','hero_overlay_color'=>'#ffffff','default_section_background'=>'#f5fbfa','light_section_background'=>'#ffffff','dark_section_background'=>'#073b49','alt_section_background'=>'#eaf8f5','cta_section_background'=>'#1c5664','section_heading_color'=>'#123342','section_text_color'=>'#344f59','header_text_color'=>'#ffffff','header_link_hover_color'=>'#20b99a','footer_heading_color'=>'#ffffff','footer_text_color'=>'#d5e6e9','footer_link_color'=>'#ffffff','card_heading_color'=>'#123342','card_text_color'=>'#344f59','card_border_color'=>'#dce9e7','button_hover_background'=>'#159d83','button_hover_text_color'=>'#ffffff','form_background'=>'#ffffff','form_text_color'=>'#123342','form_border_color'=>'#dce9e7','hero_background_image'=>'','default_section_background_image'=>'','dark_section_text_color' => '#ffffff',
'cta_section_text_color' => '#ffffff',
'header_button_background' => '#20b99a',
'header_button_text_color' => '#073b49',
'overlay_menu_background' => '#073b49',
'overlay_menu_text_color' => '#ffffff',
'overlay_menu_hover_color' => '#20b99a',
'dark_section_text_color' => '#ffffff',
'cta_section_text_color' => '#ffffff',
'header_button_background' => '#20b99a',
'header_button_text_color' => '#073b49',
'overlay_menu_background' => '#073b49',
'overlay_menu_text_color' => '#ffffff',
'overlay_menu_hover_color' => '#20b99a',
'footer_background_image'=>''];$defaults=array_merge($defaults,['hero_gradient_enabled'=>'0','hero_gradient_start'=>'#eaf8f5','hero_gradient_end'=>'#1c5664','hero_gradient_angle'=>'135','header_gradient_enabled'=>'0','header_gradient_start'=>'#1c5664','header_gradient_end'=>'#073b49','header_gradient_angle'=>'135','footer_gradient_enabled'=>'0','footer_gradient_start'=>'#073b49','footer_gradient_end'=>'#0b4556','footer_gradient_angle'=>'135','page_gradient_enabled'=>'0','page_gradient_start'=>'#f5fbfa','page_gradient_end'=>'#eaf8f5','page_gradient_angle'=>'135','section_gradient_enabled'=>'0','section_gradient_start'=>'#f5fbfa','section_gradient_end'=>'#eaf8f5','section_gradient_angle'=>'135','light_section_gradient_enabled'=>'0','light_section_gradient_start'=>'#ffffff','light_section_gradient_end'=>'#eaf8f5','light_section_gradient_angle'=>'135','dark_section_gradient_enabled'=>'0','dark_section_gradient_start'=>'#073b49','dark_section_gradient_end'=>'#0b4556','dark_section_gradient_angle'=>'135','alt_section_gradient_enabled'=>'0','alt_section_gradient_start'=>'#eaf8f5','alt_section_gradient_end'=>'#ffffff','alt_section_gradient_angle'=>'135','cta_section_gradient_enabled'=>'0','cta_section_gradient_start'=>'#1c5664','cta_section_gradient_end'=>'#073b49','cta_section_gradient_angle'=>'135','card_gradient_enabled'=>'0','card_gradient_start'=>'#ffffff','card_gradient_end'=>'#eaf8f5','card_gradient_angle'=>'135','button_gradient_enabled'=>'0','button_gradient_start'=>'#20b99a','button_gradient_end'=>'#0b6f82','button_gradient_angle'=>'135','overlay_menu_gradient_enabled'=>'0','overlay_menu_gradient_start'=>'#073b49','overlay_menu_gradient_end'=>'#1c5664','overlay_menu_gradient_angle'=>'135','form_gradient_enabled'=>'0','form_gradient_start'=>'#ffffff','form_gradient_end'=>'#eaf8f5','form_gradient_angle'=>'135']);$defaults=array_merge($defaults,wave_logo_navigation_theme_defaults());foreach($defaults as $k=>$v)$pdo->prepare("INSERT INTO theme_settings(setting_key,setting_value) VALUES(?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)")->execute([$k,$v]);admin_log('theme_reset','Theme restored to defaults');redirect_page('theme');}
 if($action==='save_theme'){$colors=['logo_background','header_shortcut_text_color','header_shortcut_hover_text_color','header_shortcut_hover_background','header_shortcut_active_text_color','header_shortcut_active_background','header_menu_text_color','header_menu_background','header_menu_border_color','primary_color','secondary_color','accent_color','page_background','section_background','card_background','header_background','footer_background','heading_color','text_color','nav_text_color','button_background','button_text_color','hero_background','hero_text_color','hero_heading_color','hero_overlay_color','default_section_background','light_section_background','dark_section_background','dark_section_text_color','alt_section_background','cta_section_background','cta_section_text_color','section_heading_color','section_text_color','header_text_color','header_link_hover_color','footer_heading_color','footer_text_color','footer_link_color','card_heading_color','card_text_color','card_border_color','button_hover_background','button_hover_text_color','form_background','form_text_color','form_border_color','dark_section_text_color','cta_section_text_color','header_button_background','header_button_text_color','overlay_menu_background','overlay_menu_text_color','overlay_menu_hover_color','header_shortcut_text_color','header_shortcut_hover_text_color','header_shortcut_hover_background','header_shortcut_active_text_color','header_shortcut_active_background','header_menu_text_color','header_menu_background','header_menu_border_color'];$colors=array_values(array_unique(array_merge($colors,['logo_border_color','logo_box_shadow_color','navbar_background','navbar_link_text_color','navbar_hover_text_color','navbar_hover_background_color','navbar_active_text_color','navbar_active_background_color','navbar_border_color','navbar_box_shadow_color'])));
$toggles=['logo_background_transparent','logo_border_enabled','logo_box_shadow_enabled','navbar_visible','navbar_mobile_visible','navbar_background_transparent','navbar_hover_underline','navbar_box_shadow_enabled','navbar_book_button_visible','navbar_menu_button_visible'];
$selects=['logo_border_style'=>['solid','dashed','dotted','double','none'],'navbar_border_style'=>['solid','dashed','dotted','double','none'],'navbar_active_style'=>['pill','underline','solid','none'],'navbar_alignment'=>['left','center','right'],'navbar_font_weight'=>['400','500','600','700','800'],'navbar_text_transform'=>['none','uppercase','capitalize','lowercase']];
$textKeys=['navbar_link_padding','navbar_label','navbar_book_button_label','navbar_book_button_url','navbar_menu_button_label'];
$labelMax=['navbar_label'=>60,'navbar_book_button_label'=>40,'navbar_menu_button_label'=>24];
$numericRanges=['navbar_font_size'=>[9,40],'navbar_gap'=>[0,80],'navbar_border_radius'=>[0,80],'navbar_letter_spacing'=>[-5,10],'navbar_border_width'=>[0,12],'navbar_box_shadow_x'=>[-60,60],'navbar_box_shadow_y'=>[-60,60],'navbar_box_shadow_blur'=>[0,120],'navbar_box_shadow_spread'=>[-60,60],'logo_border_width'=>[0,24],'logo_box_shadow_x'=>[-60,60],'logo_box_shadow_y'=>[-60,60],'logo_box_shadow_blur'=>[0,120],'logo_box_shadow_spread'=>[-60,60],'logo_box_shadow_opacity'=>[0,100],'navbar_box_shadow_opacity'=>[0,100]];
$fonts=['Inter','Arial','Georgia','Poppins','Roboto','Montserrat'];foreach(($_POST['theme']??[]) as $k=>$v){
$v=trim((string)$v);
$isGradientColor=(bool)preg_match('/^[a-z_]+_gradient_(start|end)$/',$k);
$isGradientEnabled=(bool)preg_match('/^[a-z_]+_gradient_enabled$/',$k);
$isGradientAngle=(bool)preg_match('/^[a-z_]+_gradient_angle$/',$k);
if(($isGradientColor||in_array($k,$colors,true))&&!preg_match('/^#[0-9a-fA-F]{6}$/',$v))continue;
if($isGradientEnabled)$v=$v==='1'?'1':'0';
if($isGradientAngle)$v=(string)max(0,min(360,(int)$v));
if($k==='custom_button_target'&&!in_array($v,['load_more','explore_service','header_booking','hero_primary','hero_secondary','menu_button','all_buttons'],true))continue;
if($k==='custom_button_css')$v=wave_sanitize_css_declarations($v,wave_button_css_properties(),2400);
if($k==='font_family'&&!in_array($v,$fonts,true))continue;if(in_array($k,['hero_background_image','default_section_background_image','footer_background_image'],true)){if($v!==''&&(!preg_match('~^(https?://|/|assets/)~i',$v)||preg_match('~[\"\'()\\\\<>]~',$v)))continue;$v=substr($v,0,490);}if(in_array($k,['container_width','section_spacing','border_radius'],true))$v=(string)max(0,min(2400,(int)$v));if(in_array($k,['logo_padding','logo_radius'],true))$v=(string)max(0,min(60,(int)$v));if($k==='logo_width')$v=(string)max(80,min(520,(int)$v));if(in_array($k,$toggles,true))$v=$v==='1'?'1':'0';
if(isset($selects[$k])&&!in_array($v,$selects[$k],true))continue;
if(isset($numericRanges[$k])){$range=$numericRanges[$k];$v=(string)max($range[0],min($range[1],(int)$v));}
if($k==='navbar_link_padding'&&!preg_match('/^\d{1,3}px(\s+\d{1,3}px){0,3}$/',$v))continue;
if(isset($labelMax[$k])){$v=trim(preg_replace('/[\r\n\t]+/',' ',$v));$v=function_exists('mb_substr')?mb_substr($v,0,$labelMax[$k]):substr($v,0,$labelMax[$k]);}
if($k==='navbar_book_button_url'){if(!preg_match('~^(https?://|/|#|[a-zA-Z0-9_-])~',$v)||(preg_match('~^[a-z][a-z0-9+.-]*:~i',$v)&&!preg_match('~^https?://~i',$v)))continue;$v=substr($v,0,300);}
if(!in_array($k,$colors,true)&&!in_array($k,$fonts,true)&&!in_array($k,['font_family','container_width','section_spacing','border_radius','logo_padding','logo_radius','logo_width','custom_button_target','custom_button_css','hero_background_image','default_section_background_image','footer_background_image'],true)&&!in_array($k,$toggles,true)&&!isset($selects[$k])&&!in_array($k,$textKeys,true)&&!isset($numericRanges[$k])&&!$isGradientColor&&!$isGradientEnabled&&!$isGradientAngle)continue;$pdo->prepare("INSERT INTO theme_settings(setting_key,setting_value) VALUES(?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)")->execute([$k,$v]);}$newLogo=upload_image('theme_logo_file','');if($newLogo){$pdo->prepare("INSERT INTO site_settings(setting_key,setting_value) VALUES('logo',?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)")->execute([$newLogo]);}foreach(['header'=>'logo_header_file','footer'=>'logo_footer_file','preloader'=>'logo_preloader_file','admin'=>'logo_admin_file'] as $slot=>$fieldName){$slotLogo=upload_image($fieldName,'');if($slotLogo){$pdo->prepare("INSERT INTO site_settings(setting_key,setting_value) VALUES(?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)")->execute(['logo_'.$slot,$slotLogo]);}}if(isset($_POST['save_as_default'])){$currentTheme=$pdo->query("SELECT setting_key,setting_value FROM theme_settings WHERE setting_key NOT LIKE '\\_\\_%'")->fetchAll(PDO::FETCH_KEY_PAIR);$currentLogo=$pdo->query("SELECT setting_value FROM site_settings WHERE setting_key='logo' LIMIT 1")->fetchColumn();$logoRows=$pdo->query("SELECT setting_key,setting_value FROM site_settings WHERE setting_key IN ('logo','logo_header','logo_footer','logo_preloader','logo_admin')")->fetchAll(PDO::FETCH_KEY_PAIR);$snapshot=['theme'=>$currentTheme,'logo'=>$currentLogo?:'','logos'=>$logoRows];$json=json_encode($snapshot,JSON_UNESCAPED_SLASHES|JSON_INVALID_UTF8_SUBSTITUTE);$pdo->prepare("INSERT INTO site_settings(setting_key,setting_value) VALUES('__theme_default_snapshot',?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)")->execute([$json]);admin_log('theme_default_saved','Current website theme saved as the custom default');redirect_page('theme');}admin_log('theme_updated','Theme configuration updated');redirect_page('theme');}
  if($action==='save_navigation'){$id=(int)($_POST['id']??0);$url=postv('url');if(!preg_match('~^(https?://|/|#|[a-zA-Z0-9_-])~',$url)||(preg_match('~^[a-z][a-z0-9+.-]*:~i',$url)&&!preg_match('~^https?://~i',$url)))throw new RuntimeException('Enter a valid internal path or http/https URL.');$label=postv('label');if($label==='')throw new RuntimeException('Menu label is required.');if(function_exists('mb_substr'))$label=mb_substr($label,0,100);else $label=substr($label,0,100);$placement=postv('placement','both');if(!in_array($placement,['both','header','mobile'],true))$placement='both';$navHex=function($val){$val=trim((string)$val);return preg_match('/^#[0-9a-fA-F]{6}$/',$val)?strtolower($val):'';};$navNum=function($val,$min,$max){$val=trim((string)$val);if($val==='')return null;$n=(int)$val;return($n<$min||$n>$max)?null:$n;};$navChoice=function($val,$allowed){$val=trim((string)$val);return in_array($val,$allowed,true)?$val:null;};$v=[$label,$url,(int)postv('sort_order'),isset($_POST['open_new_tab'])?1:0,isset($_POST['is_active'])?1:0,$placement,$navHex(postv('text_color')),$navHex(postv('background_color')),$navHex(postv('hover_text_color')),$navHex(postv('hover_background_color')),$navNum(postv('border_radius'),0,80),$navNum(postv('font_size'),8,48),$navChoice(postv('font_weight'),['400','500','600','700','800']),$navChoice(postv('text_transform'),['none','uppercase','capitalize','lowercase']),wave_sanitize_css_declarations(postv('custom_css'),wave_nav_css_properties(),600)?:null];$columns='label=?,url=?,sort_order=?,open_new_tab=?,is_active=?,placement=?,text_color=?,background_color=?,hover_text_color=?,hover_background_color=?,border_radius=?,font_size=?,font_weight=?,text_transform=?,custom_css=?';if($id)$pdo->prepare("UPDATE navigation_items SET $columns WHERE id=?")->execute(array_merge($v,[$id]));else $pdo->prepare("INSERT INTO navigation_items($columns) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)")->execute($v);admin_log('navigation_saved',$v[0]);redirect_page('navigation');}
 if($action==='delete_navigation'){$pdo->prepare("DELETE FROM navigation_items WHERE id=?")->execute([(int)$_POST['id']]);redirect_page('navigation');}
 if($action==='save_settings'){foreach(($_POST['settings']??[]) as $k=>$v){$v=trim($v);if(in_array($k,['instagram','facebook','youtube','google_maps_url'],true)&&$v!==''&&(!filter_var($v,FILTER_VALIDATE_URL)||!in_array(parse_url($v,PHP_URL_SCHEME),['http','https'],true)))throw new RuntimeException('Social and map links must be valid http/https URLs.');if($k==='whatsapp')$v=preg_replace('/[^0-9]/','',$v);$pdo->prepare("INSERT INTO site_settings(setting_key,setting_value) VALUES(?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)")->execute([$k,$v]);}foreach(['logo_file'=>'logo','favicon_file'=>'favicon'] as $fieldName=>$key){$path=upload_image($fieldName,'');if($path)$pdo->prepare("INSERT INTO site_settings(setting_key,setting_value) VALUES(?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)")->execute([$key,$path]);}admin_log('settings_updated','Global website settings updated');redirect_page('settings');}
 if($action==='save_testimonial'){$id=(int)($_POST['id']??0);$vals=[postv('customer_name'),postv('category'),postv('review'),isset($_POST['is_active'])?1:0];if($id)$pdo->prepare("UPDATE testimonials SET customer_name=?,category=?,review=?,is_active=? WHERE id=?")->execute(array_merge($vals,[$id]));else $pdo->prepare("INSERT INTO testimonials(customer_name,category,review,is_active,is_sample) VALUES(?,?,?,?,0)")->execute($vals);redirect_page('testimonials');}
 if($action==='delete_testimonial'){$pdo->prepare("UPDATE testimonials SET is_active=0 WHERE id=?")->execute([(int)$_POST['id']]);redirect_page('testimonials');}
 if($action==='booking_status'){$pdo->prepare("UPDATE bookings SET status=? WHERE id=?")->execute([postv('status'),(int)$_POST['id']]);redirect_page('bookings');}
 if($action==='change_password'){$u=$pdo->prepare("SELECT password_hash FROM admin_users WHERE id=?");$u->execute([$adminId]);$hash=$u->fetchColumn();if(!password_verify(postv('current_password'),$hash))throw new RuntimeException('Current password is incorrect.');if(strlen(postv('new_password'))<10)throw new RuntimeException('New password must contain at least 10 characters.');$pdo->prepare("UPDATE admin_users SET password_hash=? WHERE id=?")->execute([password_hash(postv('new_password'),PASSWORD_DEFAULT),$adminId]);admin_log('password_changed','Administrator password changed');$notice='Password updated.';}
}
}catch(Throwable $e){if(!empty($bulkChunkRequest))wave_json_response(['ok'=>false,'added'=>0,'notes'=>[],'failures'=>[$e->getMessage()],'message'=>$e->getMessage()]);$error=$e->getMessage();error_log('[Wave Admin] '.$e->getMessage());}
function count_table($table){$allowed=['services','portfolio_photos','testimonials','bookings','website_sections','website_content','media_library'];if(!in_array($table,$allowed,true))return 0;return (int)db()->query("SELECT COUNT(*) FROM `$table`".(in_array($table,['services','portfolio_photos','testimonials','website_sections'])?' WHERE is_active=1':''))->fetchColumn();}
function admin_form_start($action){echo '<form method="post" enctype="multipart/form-data" class="admin-form"><input type="hidden" name="csrf" value="'.admin_h(admin_csrf()).'"><input type="hidden" name="action" value="'.admin_h($action).'">';}
function field($label,$name,$value='',$type='text',$extra=''){echo '<label>'.admin_h($label).'<input type="'.admin_h($type).'" name="'.admin_h($name).'" value="'.admin_h($value).'" '.$extra.'></label>';}
function textarea_field($label,$name,$value=''){echo '<label>'.admin_h($label).'<textarea name="'.admin_h($name).'" rows="4">'.admin_h($value).'</textarea></label>';}
function nav_color_field($label,$name,$value=''){
  $value=(string)$value;
  echo '<label class="nav-color">'.admin_h($label).'<input type="hidden" name="'.admin_h($name).'" value="'.admin_h($value).'" data-nav-style="'.admin_h($name).'">'.
       '<input type="color" value="'.admin_h(preg_match('/^#[0-9a-fA-F]{6}$/',$value)?$value:'#173b49').'" data-nav-color-for="'.admin_h($name).'">'.
       '<span class="check"><input type="checkbox" data-nav-style-clear="'.admin_h($name).'"'.($value===''?' checked':'').'> Use navigation bar style</span>'.
       '</label>';
}
function rows($sql){return db()->query($sql)->fetchAll();}
$stats=['Sections'=>count_table('website_sections'),'Images'=>count_table('portfolio_photos'),'Uploaded media'=>count_table('media_library'),'Services'=>count_table('services'),'Testimonials'=>count_table('testimonials'),'Content blocks'=>count_table('website_content')];
$labels=['dashboard'=>'Dashboard','sections'=>'Sections','gallery'=>'Gallery','services'=>'Services','content'=>'Website content','navigation'=>'Navigation','theme'=>'Theme','settings'=>'Settings','testimonials'=>'Testimonials','bookings'=>'Bookings','account'=>'Admin account','activity'=>'Activity log','media'=>'Media library'];
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=admin_h($labels[$page])?> | Wave Admin</title><link rel="stylesheet" href="admin.css"></head><body class="admin-body"><div class="admin-shell"><aside class="sidebar" id="sidebar"><a class="side-brand" href="../index.php"><img src="<?=admin_h($adminBrandLogo)?>" alt="Wave Photography"></a><div class="side-label">WORKSPACE</div><?php foreach($labels as $k=>$v):?><a class="side-link <?=$page===$k?'active':''?>" href="?page=<?=$k?>"><span><?=admin_h($v)?></span></a><?php endforeach;?><a class="side-link" href="../index.php" target="_blank">View website ↗</a><a class="side-link logout" href="?logout=1">Sign out</a></aside><main class="admin-main"><header class="topbar"><button class="mobile-menu" onclick="document.getElementById('sidebar').classList.toggle('show')">☰</button><div><small>WAVE PHOTOGRAPHY / ADMIN</small><h1><?=admin_h($labels[$page])?></h1></div><div class="top-user"><span class="avatar">A</span><span><?=admin_h($_SESSION['wave_admin_name'])?></span></div></header><div class="content-area"><?php if($error):?><div class="alert error"><?=admin_h($error)?></div><?php endif;?><?php if($notice):?><div class="alert success"><?=admin_h($notice)?></div><?php endif;?>
<?php if($page==='dashboard'):?><div class="welcome"><div><span class="eyebrow">OVERVIEW</span><h2>Your website, one place.</h2><p>Manage your photography website and keep every story up to date.</p></div><a class="btn primary" href="../index.php" target="_blank">Preview website ↗</a></div><div class="stats"><?php foreach($stats as $k=>$v):?><div class="stat-card"><span><?=admin_h($k)?></span><strong><?=$v?></strong><small>Live database records</small></div><?php endforeach;?></div><div class="panel"><div class="panel-head"><div><h3>Quick actions</h3><p>Common website management tasks</p></div></div><div class="quick-grid"><?php foreach(['sections'=>'Add new section','gallery'=>'Upload photographs','services'=>'Manage services','content'=>'Edit website content','theme'=>'Customize theme','navigation'=>'Manage navigation','settings'=>'Website settings','bookings'=>'View enquiries'] as $k=>$v):?><a class="quick-card" href="?page=<?=$k?>"><b>＋</b><span><?=admin_h($v)?></span><i>→</i></a><?php endforeach;?></div></div><div class="panel"><div class="panel-head"><div><h3>Recent activity</h3><p>Latest changes made in this portal</p></div><a href="?page=activity">View all</a></div><?php $logs=rows("SELECT action,details,created_at FROM admin_activity_logs ORDER BY id DESC LIMIT 6");if(!$logs):?><p class="muted">No activity recorded yet.</p><?php else:foreach($logs as $l):?><div class="activity-row"><b><?=admin_h(str_replace('_',' ',$l['action']))?></b><span><?=admin_h($l['details'])?></span><time><?=admin_h($l['created_at'])?></time></div><?php endforeach;endif;?></div>
<?php elseif($page==='sections'):?><div class="panel" id="section-editor"><div class="panel-head"><div><h3>Create or edit a photography section</h3><p>New sections automatically become available as gallery categories.</p></div></div><?php admin_form_start('save_section');?><input type="hidden" name="id" value=""><input type="hidden" name="existing_image" value=""><div class="form-grid"><?php field('Section name','name');field('URL slug (optional)','slug');field('Gallery category','category');field('Display order','sort_order','0','number');?><label>Cover image<input type="file" name="cover_image" accept="image/jpeg,image/png,image/webp" data-image-cap-bytes="<?=wave_image_cap_bytes()?>" data-image-cap-label="<?=admin_h(wave_image_cap_label())?>"></label><label>Layout<select name="layout"><option value="gallery">Gallery</option><option value="masonry">Masonry</option><option value="grid">Grid</option></select></label></div><?php textarea_field('Description','description');?><label class="check"><input type="checkbox" name="is_active" checked> Publish this section</label><button class="btn primary">Save section</button></form></div><div class="panel"><h3>Existing sections</h3><?php
// Hidden sections keep their rows so they can be brought back; the list is split so an
// archived section is never mistaken for one that was never created.
$sectionGroups=[['Published sections',rows("SELECT * FROM website_sections WHERE deleted_at IS NULL AND is_active=1 ORDER BY sort_order,id")],['Hidden sections',rows("SELECT * FROM website_sections WHERE deleted_at IS NOT NULL OR is_active=0 ORDER BY sort_order,id")]];
foreach($sectionGroups as [$groupTitle,$groupRows]):?><h4 class="manage-group"><?=admin_h($groupTitle)?> <span class="pill"><?=count($groupRows)?></span></h4><?php if(!$groupRows):?><p class="muted"><?= $groupTitle==='Hidden sections'?'Nothing is hidden. Sections you hide will appear here so you can show them again.':'No published sections yet.' ?></p><?php endif;foreach($groupRows as $r):$isHidden=(int)$r['is_active']!==1||$r['deleted_at']!==null;?><div class="manage-row<?= $isHidden?' is-hidden':''?>"><div><b><?=admin_h($r['name'])?></b><small><?=admin_h($r['category'])?> · <?= $isHidden?'Hidden':'Published'?></small></div><span><?=admin_h($r['description'])?></span><?php if($isHidden):?><form method="post"><input type="hidden" name="csrf" value="<?=admin_h(admin_csrf())?>"><input type="hidden" name="action" value="restore_section"><input type="hidden" name="id" value="<?=$r['id']?>"><button class="btn primary small">Show on website</button></form><?php else:?><button type="button" class="btn small" data-edit-section="<?=admin_h(json_encode($r))?>">Edit</button><form method="post"><input type="hidden" name="csrf" value="<?=admin_h(admin_csrf())?>"><input type="hidden" name="action" value="delete_section"><input type="hidden" name="id" value="<?=$r['id']?>"><button class="btn danger small" onclick="return confirm('Hide this section and its gallery images? You can show it again later.')">Hide</button></form><?php endif;?></div><?php endforeach;endforeach;?></div>
<?php elseif($page==='services'):?><div class="panel" id="service-editor"><div class="panel-head"><div><h3>Add or update a service</h3><p>Service changes are reflected on the public website.</p></div></div><?php admin_form_start('save_service');?><input type="hidden" name="id" value=""><input type="hidden" name="image" value=""><div class="form-grid"><?php field('Service name','name');field('Slug (optional)','slug');field('Gallery category','category');field('Display order','sort_order','0','number');?><label>Service image<input type="file" name="image_file" accept="image/jpeg,image/png,image/webp" data-image-cap-bytes="<?=wave_image_cap_bytes()?>" data-image-cap-label="<?=admin_h(wave_image_cap_label())?>"></label></div><?php textarea_field('Description','description');textarea_field('What is included (one item per line)','inclusions');?><label class="check"><input type="checkbox" name="is_active" checked> Active on website</label><button class="btn primary">Save service</button></form></div><div class="panel"><h3>Current services</h3><?php
$serviceGroups=[['Published services',rows("SELECT * FROM services WHERE is_active=1 ORDER BY sort_order,id")],['Hidden services',rows("SELECT * FROM services WHERE is_active=0 ORDER BY sort_order,id")]];
foreach($serviceGroups as [$groupTitle,$groupRows]):?><h4 class="manage-group"><?=admin_h($groupTitle)?> <span class="pill"><?=count($groupRows)?></span></h4><?php if(!$groupRows):?><p class="muted"><?= $groupTitle==='Hidden services'?'Nothing is hidden. Services you hide will appear here so you can show them again.':'No published services yet.' ?></p><?php endif;foreach($groupRows as $r):$isHidden=(int)$r['is_active']!==1;?><div class="manage-row<?= $isHidden?' is-hidden':''?>"><img class="thumb" src="../<?=admin_h($r['image'])?>" alt=""><div><b><?=admin_h($r['name'])?></b><small><?=admin_h($r['category'])?> · <?=$isHidden?'Hidden':'Active'?></small></div><span><?=admin_h($r['description'])?></span><?php if($isHidden):?><form method="post"><input type="hidden" name="csrf" value="<?=admin_h(admin_csrf())?>"><input type="hidden" name="action" value="restore_service"><input type="hidden" name="id" value="<?=$r['id']?>"><button class="btn primary small">Show on website</button></form><?php else:?><button type="button" class="btn small" data-edit-service="<?=admin_h(json_encode($r))?>">Edit</button><form method="post"><input type="hidden" name="csrf" value="<?=admin_h(admin_csrf())?>"><input type="hidden" name="action" value="delete_service"><input type="hidden" name="id" value="<?=$r['id']?>"><button class="btn danger small" onclick="return confirm('Hide this service? You can show it again later.')">Hide</button></form><?php endif;?></div><?php endforeach;endforeach;?></div>
<?php elseif($page==='gallery'):?><div class="panel"><h3>Upload photographs</h3><p class="muted">JPG, PNG and WebP · maximum <?=admin_h(wave_image_cap_label())?> per image · maximum <?=round(wave_batch_limit_bytes()/1048576)?> MB per request · up to 50 images at once. Selections larger than one request are split into several batches automatically, and any single image above the per-file limit is uploaded in slices, so size is not limited by the hosting server.</p><p class="muted">Uploads are stored in the website media library.</p><p class="muted">Hosting limits currently reported by PHP: upload_max_filesize <strong><?=admin_h(ini_get('upload_max_filesize'))?></strong> · post_max_size <strong><?=admin_h(ini_get('post_max_size'))?></strong> · max_file_uploads <strong><?=admin_h(ini_get('max_file_uploads'))?></strong>.</p><?php if(wave_ini_bytes((string)ini_get('post_max_size'))<64*1024*1024):?><p class="alert error">This host accepts only <?=admin_h(ini_get('post_max_size'))?> in a single request, so each upload is split into smaller batches. Large selections will take longer to upload, and a single image must still be within the per-file limit shown above.</p><?php endif;?><?php admin_form_start('bulk_upload');?><div class="form-grid"><label>Section / category<select name="category" required><?php foreach(rows("SELECT DISTINCT category FROM website_sections WHERE is_active=1 ORDER BY sort_order") as $c):?><option><?=admin_h($c['category'])?></option><?php endforeach;?></select></label><label>Photographs<input type="file" name="image_files[]" accept="image/jpeg,image/png,image/webp" multiple required data-bulk-image-upload data-image-cap-bytes="<?=wave_image_cap_bytes()?>" data-image-cap-label="<?=admin_h(wave_image_cap_label())?>" data-max-image-bytes="<?=WAVE_CHUNK_MAX_TOTAL?>" data-server-max-files="<?=(int)ini_get('max_file_uploads')?>" data-server-max-file-bytes="<?=wave_ini_bytes((string)ini_get('upload_max_filesize'))?>" data-server-max-total-bytes="<?=wave_batch_limit_bytes()?>" data-server-max-file-label="<?=admin_h(ini_get('upload_max_filesize'))?>" data-server-max-total-label="<?=round(wave_batch_limit_bytes()/1048576)?> MB"><input type="hidden" name="bulk_file_count" value="0" data-bulk-file-count><small id="bulk-upload-status">Select multiple images. Each image can be up to <?=admin_h(wave_image_cap_label())?>.</small></label><?php field('Display order','sort_order','0','number');?></div><button class="btn primary">Upload selected photographs</button></form></div><div class="photo-grid"><?php foreach(rows("SELECT * FROM portfolio_photos WHERE is_active=1 ORDER BY sort_order,id DESC") as $r):?><article class="photo-card"><img src="../<?=admin_h($r['image'])?>" alt="<?=admin_h($r['title'])?>"><div><b><?=admin_h($r['title'])?></b><small><?=admin_h($r['category'])?> <?=$r['featured']?'· Featured':''?></small><details><summary>Edit details / replace image</summary><?php admin_form_start('save_photo');?><input type="hidden" name="id" value="<?=$r['id']?>"><input type="hidden" name="image" value="<?=admin_h($r['image'])?>"><?php field('Title','title',$r['title']);?><label>Category<select name="category"><?php foreach(rows("SELECT DISTINCT category FROM website_sections WHERE is_active=1 ORDER BY sort_order") as $c):?><option <?= $c['category']===$r['category']?'selected':''?>><?=admin_h($c['category'])?></option><?php endforeach;?></select></label><?php field('Caption','caption',$r['caption']??'');field('Alt text','alt_text',$r['alt_text']??'');field('Display order','sort_order',$r['sort_order'],'number');?><label class="check"><input type="checkbox" name="is_cover" <?=$r['is_cover']?'checked':''?>> Use as section cover</label><label>Replace image<input type="file" name="image_file" accept="image/jpeg,image/png,image/webp" data-image-cap-bytes="<?=wave_image_cap_bytes()?>" data-image-cap-label="<?=admin_h(wave_image_cap_label())?>"></label><label class="check"><input type="checkbox" name="featured" <?=$r['featured']?'checked':''?>> Featured</label><label class="check"><input type="checkbox" name="is_active" checked> Published</label><button class="btn primary small">Save</button></form></details><form method="post"><input type="hidden" name="csrf" value="<?=admin_h(admin_csrf())?>"><input type="hidden" name="action" value="delete_photo"><input type="hidden" name="id" value="<?=$r['id']?>"><button class="btn danger small" onclick="return confirm('Remove this photograph from the website?')">Remove</button></form></div></article><?php endforeach;?></div>
<?php elseif($page==='content'):?><div class="panel"><h3>Website wording</h3><p class="muted">Edit the text displayed on your public pages.</p><?php admin_form_start('save_content');$items=$pdo->query("SELECT * FROM website_content ORDER BY id")->fetchAll();foreach($items as $r){if(in_array($r['content_key'],['hero_image','about_image'],true))continue;textarea_field(ucwords(str_replace('_',' ',$r['content_key'])),'content['.$r['content_key'].']',$r['content_value']);}?><div class="form-grid"><label>Hero image<input type="file" name="hero_image_file" accept="image/jpeg,image/png,image/webp" data-image-cap-bytes="<?=wave_image_cap_bytes()?>" data-image-cap-label="<?=admin_h(wave_image_cap_label())?>"></label><label>About section image<input type="file" name="about_image_file" accept="image/jpeg,image/png,image/webp" data-image-cap-bytes="<?=wave_image_cap_bytes()?>" data-image-cap-label="<?=admin_h(wave_image_cap_label())?>"></label></div><button class="btn primary">Save website content</button></form></div>
<?php elseif($page==='theme'):?><div class="panel"><h3>Complete website styling</h3><p class="muted">Edit each area and save only the section you changed, save everything together, or save the current appearance as your reusable default.</p><?php $hasCustomThemeDefault=(bool)$pdo->query("SELECT COUNT(*) FROM site_settings WHERE setting_key='__theme_default_snapshot'")->fetchColumn();?><p class="theme-default-state"><?= $hasCustomThemeDefault?'A custom default theme is saved. “Restore saved default theme” will bring it back.':'No custom default saved yet. Use “Save current changes as default” to create one.'?></p><?php $themeDefaults=['hero_background'=>'#eaf8f5','hero_text_color'=>'#123342','hero_heading_color'=>'#ffffff','hero_overlay_color'=>'#ffffff','default_section_background'=>'#f5fbfa','light_section_background'=>'#ffffff','dark_section_background'=>'#073b49','alt_section_background'=>'#eaf8f5','cta_section_background'=>'#1c5664','section_heading_color'=>'#123342','section_text_color'=>'#344f59','header_text_color'=>'#ffffff','header_link_hover_color'=>'#20b99a','footer_heading_color'=>'#ffffff','footer_text_color'=>'#d5e6e9','footer_link_color'=>'#ffffff','card_heading_color'=>'#123342','card_text_color'=>'#344f59','card_border_color'=>'#dce9e7','button_hover_background'=>'#159d83','button_hover_text_color'=>'#ffffff','form_background'=>'#ffffff','form_text_color'=>'#123342','form_border_color'=>'#dce9e7','hero_background_image'=>'','default_section_background_image'=>'','footer_background_image'=>'','dark_section_text_color'=>'#ffffff', 'cta_section_text_color'=>'#ffffff', 'header_button_background'=>'#20b99a', 'header_button_text_color'=>'#073b49', 'overlay_menu_background'=>'#073b49', 'overlay_menu_text_color'=>'#ffffff', 'overlay_menu_hover_color'=>'#20b99a'];$themeDefaults=array_merge($themeDefaults,['logo_background'=>'#ffffff','logo_padding'=>'10','logo_radius'=>'16','logo_width'=>'220','header_shortcut_text_color'=>'#173b49','header_shortcut_hover_text_color'=>'#ffffff','header_shortcut_hover_background'=>'#20b99a','header_shortcut_active_text_color'=>'#ffffff','header_shortcut_active_background'=>'#1c5664','header_menu_text_color'=>'#173b49','header_menu_background'=>'#ffffff','header_menu_border_color'=>'#20b99a','custom_button_target'=>'load_more','custom_button_css'=>'']);$themeDefaults=array_merge($themeDefaults,['hero_gradient_enabled'=>'0','hero_gradient_start'=>'#eaf8f5','hero_gradient_end'=>'#1c5664','hero_gradient_angle'=>'135','header_gradient_enabled'=>'0','header_gradient_start'=>'#1c5664','header_gradient_end'=>'#073b49','header_gradient_angle'=>'135','footer_gradient_enabled'=>'0','footer_gradient_start'=>'#073b49','footer_gradient_end'=>'#0b4556','footer_gradient_angle'=>'135','page_gradient_enabled'=>'0','page_gradient_start'=>'#f5fbfa','page_gradient_end'=>'#eaf8f5','page_gradient_angle'=>'135','section_gradient_enabled'=>'0','section_gradient_start'=>'#f5fbfa','section_gradient_end'=>'#eaf8f5','section_gradient_angle'=>'135','light_section_gradient_enabled'=>'0','light_section_gradient_start'=>'#ffffff','light_section_gradient_end'=>'#eaf8f5','light_section_gradient_angle'=>'135','dark_section_gradient_enabled'=>'0','dark_section_gradient_start'=>'#073b49','dark_section_gradient_end'=>'#0b4556','dark_section_gradient_angle'=>'135','alt_section_gradient_enabled'=>'0','alt_section_gradient_start'=>'#eaf8f5','alt_section_gradient_end'=>'#ffffff','alt_section_gradient_angle'=>'135','cta_section_gradient_enabled'=>'0','cta_section_gradient_start'=>'#1c5664','cta_section_gradient_end'=>'#073b49','cta_section_gradient_angle'=>'135','card_gradient_enabled'=>'0','card_gradient_start'=>'#ffffff','card_gradient_end'=>'#eaf8f5','card_gradient_angle'=>'135','button_gradient_enabled'=>'0','button_gradient_start'=>'#20b99a','button_gradient_end'=>'#0b6f82','button_gradient_angle'=>'135','overlay_menu_gradient_enabled'=>'0','overlay_menu_gradient_start'=>'#073b49','overlay_menu_gradient_end'=>'#1c5664','overlay_menu_gradient_angle'=>'135','form_gradient_enabled'=>'0','form_gradient_start'=>'#ffffff','form_gradient_end'=>'#eaf8f5','form_gradient_angle'=>'135']);$seed=$pdo->prepare("INSERT IGNORE INTO theme_settings(setting_key,setting_value) VALUES(?,?)");$themeDefaults=array_merge($themeDefaults,wave_logo_navigation_theme_defaults());foreach($themeDefaults as $tk=>$tv)$seed->execute([$tk,$tv]);$theme=$pdo->query("SELECT setting_key, setting_value FROM theme_settings ORDER BY setting_key")->fetchAll(PDO::FETCH_KEY_PAIR);?><div id="theme-preview" class="theme-preview" style="--preview-header:<?=admin_h($theme['header_background']??'#1c5664')?>;--preview-page:<?=admin_h($theme['page_background']??'#f5fbfa')?>;--preview-card:<?=admin_h($theme['card_background']??'#fff')?>;--preview-heading:<?=admin_h($theme['heading_color']??'#123342')?>;--preview-text:<?=admin_h($theme['text_color']??'#344f59')?>;--preview-button:<?=admin_h($theme['button_background']??'#20b99a')?>;--preview-button-text:<?=admin_h($theme['button_text_color']??'#073b49')?>;--preview-footer:<?=admin_h($theme['footer_background']??'#073b49')?>;--preview-section:<?=admin_h($theme['default_section_background']??'#f5fbfa')?>;--preview-section-heading:<?=admin_h($theme['section_heading_color']??'#123342')?>;--preview-section-text:<?=admin_h($theme['section_text_color']??'#344f59')?>;--preview-card-heading:<?=admin_h($theme['card_heading_color']??'#123342')?>;--preview-card-text:<?=admin_h($theme['card_text_color']??'#344f59')?>;--preview-card-border:<?=admin_h($theme['card_border_color']??'#dce9e7')?>;--preview-footer-text:<?=admin_h($theme['footer_text_color']??'#d5e6e9')?>"><div class="theme-preview-header">Wave Photography <span>Menu</span></div><div class="theme-preview-body"><b>Every Picture Tells a Story</b><p>Your hero section preview</p><span class="theme-preview-button">Book a Session</span></div><div class="theme-preview-section"><h4>Content section preview</h4><div class="theme-preview-card"><b>Photography service card</b><p>Card text and border preview</p></div></div><div class="theme-preview-footer">Footer preview · Contact · Instagram</div></div><?php admin_form_start('save_theme');$currentLogo=$pdo->query("SELECT setting_value FROM site_settings WHERE setting_key='logo' LIMIT 1")->fetchColumn();?><div class="logo-manager"><div><strong>Website logo</strong><p class="muted">Upload a replacement logo. The new logo is used in the public header and footer.</p><?php if($currentLogo):?><img class="logo-manager-preview" src="../<?=admin_h(ltrim($currentLogo,'/'))?>" alt="Current website logo"><?php else:?><span class="muted">Using the bundled Wave Photography logo</span><?php endif;?></div><label>Upload global logo<input type="file" name="theme_logo_file" accept="image/jpeg,image/png,image/webp" data-image-cap-bytes="<?=wave_image_cap_bytes()?>" data-image-cap-label="<?=admin_h(wave_image_cap_label())?>"><small>Used everywhere unless a location-specific logo is selected.</small></label><div class="logo-slot-grid"><label>Header logo override<input type="file" name="logo_header_file" accept="image/jpeg,image/png,image/webp" data-image-cap-bytes="<?=wave_image_cap_bytes()?>" data-image-cap-label="<?=admin_h(wave_image_cap_label())?>"><small>Leave empty to use global logo.</small></label><label>Footer logo override<input type="file" name="logo_footer_file" accept="image/jpeg,image/png,image/webp" data-image-cap-bytes="<?=wave_image_cap_bytes()?>" data-image-cap-label="<?=admin_h(wave_image_cap_label())?>"><small>Leave empty to use global logo.</small></label><label>Preloader logo override<input type="file" name="logo_preloader_file" accept="image/jpeg,image/png,image/webp" data-image-cap-bytes="<?=wave_image_cap_bytes()?>" data-image-cap-label="<?=admin_h(wave_image_cap_label())?>"><small>Leave empty to use global logo.</small></label><label>Admin portal logo override<input type="file" name="logo_admin_file" accept="image/jpeg,image/png,image/webp" data-image-cap-bytes="<?=wave_image_cap_bytes()?>" data-image-cap-label="<?=admin_h(wave_image_cap_label())?>"><small>Leave empty to use global logo.</small></label></div></div><div class="theme-control-grid"><?php
$themeGroups=[
'Global colours'=>['primary_color','secondary_color','accent_color','page_background','section_background','card_background','heading_color','text_color','page_gradient_enabled','page_gradient_start','page_gradient_end','page_gradient_angle'],
'Header & navigation'=>['header_background','header_text_color','nav_text_color','header_link_hover_color','header_gradient_enabled','header_gradient_start','header_gradient_end','header_gradient_angle'],
'Logo appearance'=>['logo_background','logo_background_transparent','logo_padding','logo_radius','logo_width','logo_border_enabled','logo_border_color','logo_border_width','logo_border_style','logo_box_shadow_enabled','logo_box_shadow_color','logo_box_shadow_opacity','logo_box_shadow_x','logo_box_shadow_y','logo_box_shadow_blur','logo_box_shadow_spread'],
'Navigation bar'=>['navbar_visible','navbar_mobile_visible','navbar_label','navbar_alignment','navbar_background','navbar_background_transparent','navbar_font_size','navbar_font_weight','navbar_text_transform','navbar_letter_spacing','navbar_gap','navbar_link_padding','navbar_border_radius','navbar_border_color','navbar_border_width','navbar_border_style','navbar_box_shadow_enabled','navbar_box_shadow_color','navbar_box_shadow_opacity','navbar_box_shadow_x','navbar_box_shadow_y','navbar_box_shadow_blur','navbar_box_shadow_spread','navbar_link_text_color','navbar_hover_text_color','navbar_hover_background_color','navbar_hover_underline','navbar_active_style','navbar_active_text_color','navbar_active_background_color','navbar_book_button_visible','navbar_book_button_label','navbar_book_button_url','navbar_menu_button_visible','navbar_menu_button_label'],
'Header buttons'=>['header_shortcut_text_color','header_shortcut_hover_text_color','header_shortcut_hover_background','header_shortcut_active_text_color','header_shortcut_active_background','header_menu_text_color','header_menu_background','header_menu_border_color','header_button_background','header_button_text_color'],
'Footer'=>['footer_background','footer_heading_color','footer_text_color','footer_link_color','footer_background_image','footer_gradient_enabled','footer_gradient_start','footer_gradient_end','footer_gradient_angle'],
'Hero section'=>['hero_background','hero_text_color','hero_heading_color','hero_overlay_color','hero_background_image','hero_gradient_enabled','hero_gradient_start','hero_gradient_end','hero_gradient_angle'],
'Content sections'=>['default_section_background','light_section_background','dark_section_background','dark_section_text_color','alt_section_background','cta_section_background','cta_section_text_color','section_heading_color','section_text_color','default_section_background_image','section_gradient_enabled','section_gradient_start','section_gradient_end','section_gradient_angle','light_section_gradient_enabled','light_section_gradient_start','light_section_gradient_end','light_section_gradient_angle','dark_section_gradient_enabled','dark_section_gradient_start','dark_section_gradient_end','dark_section_gradient_angle','alt_section_gradient_enabled','alt_section_gradient_start','alt_section_gradient_end','alt_section_gradient_angle','cta_section_gradient_enabled','cta_section_gradient_start','cta_section_gradient_end','cta_section_gradient_angle'],
'Cards & buttons'=>['card_background','card_heading_color','card_text_color','card_border_color','button_background','button_text_color','button_hover_background','button_hover_text_color','card_gradient_enabled','card_gradient_start','card_gradient_end','card_gradient_angle','button_gradient_enabled','button_gradient_start','button_gradient_end','button_gradient_angle'],
'Forms & inputs'=>['form_background','form_text_color','form_border_color','form_gradient_enabled','form_gradient_start','form_gradient_end','form_gradient_angle'],
'Overlay menu'=>['overlay_menu_background','overlay_menu_text_color','overlay_menu_hover_color','overlay_menu_gradient_enabled','overlay_menu_gradient_start','overlay_menu_gradient_end','overlay_menu_gradient_angle'],
'Button designer'=>['custom_button_target','custom_button_css'],'Layout & typography'=>['font_family','container_width','section_spacing','border_radius']
];
$themeToggles=['logo_background_transparent'=>['Logo background','Use chosen colour','Transparent'],'logo_border_enabled'=>['Logo border','No border','Border on'],'logo_box_shadow_enabled'=>['Logo box shadow','No shadow','Shadow on'],'navbar_visible'=>['Header navigation links','Hidden','Visible'],'navbar_mobile_visible'=>['Links inside the mobile menu','Hidden','Visible'],'navbar_background_transparent'=>['Navigation bar background','Use chosen colour','Transparent'],'navbar_hover_underline'=>['Underline links on hover','Disabled','Enabled'],'navbar_box_shadow_enabled'=>['Navigation bar box shadow','No shadow','Shadow on'],'navbar_book_button_visible'=>['Header booking button','Hidden','Visible'],'navbar_menu_button_visible'=>['Header menu button','Hidden','Visible']];
$themeSelects=['logo_border_style'=>['Logo border style',['solid'=>'Solid','dashed'=>'Dashed','dotted'=>'Dotted','double'=>'Double','none'=>'None']],'navbar_border_style'=>['Navigation bar border style',['solid'=>'Solid','dashed'=>'Dashed','dotted'=>'Dotted','double'=>'Double','none'=>'None']],'navbar_active_style'=>['Current page link style',['pill'=>'Filled pill','underline'=>'Underline','solid'=>'Solid block','none'=>'Colour only']],'navbar_alignment'=>['Navigation alignment',['left'=>'Left','center'=>'Centre','right'=>'Right']],'navbar_font_weight'=>['Navigation font weight',['400'=>'400 · Regular','500'=>'500 · Medium','600'=>'600 · Semi bold','700'=>'700 · Bold','800'=>'800 · Extra bold']],'navbar_text_transform'=>['Navigation letter case',['none'=>'As typed','uppercase'=>'UPPERCASE','capitalize'=>'Capitalise Each Word','lowercase'=>'lowercase']]];
$themeTextLabels=['navbar_link_padding'=>'Link spacing (CSS padding, e.g. 9px 14px)','navbar_label'=>'Navigation accessible label','navbar_book_button_label'=>'Booking button label (blank = use website content)','navbar_book_button_url'=>'Booking button link','navbar_menu_button_label'=>'Menu button label'];
$themeNumericRanges=['navbar_font_size'=>[9,40],'navbar_gap'=>[0,80],'navbar_border_radius'=>[0,80],'navbar_letter_spacing'=>[-5,10],'navbar_border_width'=>[0,12],'navbar_box_shadow_x'=>[-60,60],'navbar_box_shadow_y'=>[-60,60],'navbar_box_shadow_blur'=>[0,120],'navbar_box_shadow_spread'=>[-60,60],'navbar_box_shadow_opacity'=>[0,100],'logo_border_width'=>[0,24],'logo_box_shadow_x'=>[-60,60],'logo_box_shadow_y'=>[-60,60],'logo_box_shadow_blur'=>[0,120],'logo_box_shadow_spread'=>[-60,60],'logo_box_shadow_opacity'=>[0,100]];
foreach($themeGroups as $group=>$keys):?><div class="theme-group"><h4><?=admin_h($group)?></h4><div class="form-grid"><?php foreach($keys as $key):$value=$theme[$key]??'';if(substr($key,-6)==='_image'):?><label><?=admin_h(ucwords(str_replace('_',' ',$key)))?><input type="text" name="theme[<?=admin_h($key)?>]" value="<?=admin_h($value)?>" placeholder="https://... or assets/..." data-theme-key="<?=admin_h($key)?>"><small>Optional image URL/path. Leave blank to use the colour only.</small></label><?php elseif(isset($themeToggles[$key])):list($toggleLabel,$toggleOff,$toggleOn)=$themeToggles[$key];?><label class="gradient-toggle"><?=admin_h($toggleLabel)?><select name="theme[<?=admin_h($key)?>]" data-theme-key="<?=admin_h($key)?>"><option value="0" <?=$value==='1'?'':'selected'?>>Off – <?=admin_h($toggleOff)?></option><option value="1" <?=$value==='1'?'selected':''?>>On – <?=admin_h($toggleOn)?></option></select></label><?php elseif(isset($themeSelects[$key])):list($selectLabel,$selectOptions)=$themeSelects[$key];?><label><?=admin_h($selectLabel)?><select name="theme[<?=admin_h($key)?>]" data-theme-key="<?=admin_h($key)?>"><?php foreach($selectOptions as $optionValue=>$optionLabel):?><option value="<?=admin_h((string)$optionValue)?>" <?=$value===(string)$optionValue?'selected':''?>><?=admin_h($optionLabel)?></option><?php endforeach;?></select></label><?php elseif(isset($themeTextLabels[$key])):?><label><?=admin_h($themeTextLabels[$key])?><input type="text" name="theme[<?=admin_h($key)?>]" value="<?=admin_h($value)?>" data-theme-key="<?=admin_h($key)?>"></label><?php elseif(preg_match('/_gradient_enabled$/',$key)):?><label class="gradient-toggle"><?=admin_h(ucwords(str_replace('_',' ',$key)))?><select name="theme[<?=admin_h($key)?>]" data-theme-key="<?=admin_h($key)?>"><option value="0" <?=$value!=='1'?'selected':''?>>Solid colour</option><option value="1" <?=$value==='1'?'selected':''?>>Enable gradient</option></select></label><?php elseif(preg_match('/_gradient_angle$/',$key)):?><label><?=admin_h(ucwords(str_replace('_',' ',$key)))?><input type="number" min="0" max="360" step="1" name="theme[<?=admin_h($key)?>]" value="<?=admin_h($value?:'135')?>" data-theme-key="<?=admin_h($key)?>"></label><?php elseif(preg_match('/_gradient_(start|end)$/',$key)||substr($key,-6)==='_color'||substr($key,-11)==='_background'):?><label><?=admin_h(ucwords(str_replace('_',' ',$key)))?><input type="color" name="theme[<?=admin_h($key)?>]" value="<?=admin_h(preg_match('/^#[0-9a-fA-F]{6}$/',$value)?$value:'#ffffff')?>" data-theme-key="<?=admin_h($key)?>"></label><?php elseif($key==='custom_button_target'):?><label>Apply custom CSS to<select name="theme[custom_button_target]" data-theme-key="custom_button_target"><?php foreach(['load_more'=>'Load more photos','explore_service'=>'Explore Service','header_booking'=>'Header – Book a Session','hero_primary'=>'Hero – Explore Our Work','hero_secondary'=>'Hero – Book Your Session','menu_button'=>'Header menu','all_buttons'=>'All main buttons'] as $target=>$label):?><option value="<?=admin_h($target)?>" <?=$value===$target?'selected':''?>><?=admin_h($label)?></option><?php endforeach;?></select><small>Choose one button group. Your CSS below will only target that group.</small></label><?php elseif($key==='custom_button_css'):?><label class="custom-css-field">Custom CSS declarations<textarea name="theme[custom_button_css]" rows="7" spellcheck="false" placeholder="background: linear-gradient(135deg, #20b99a, #0b6f82);&#10;color: #073b49;&#10;border-radius: 18px;&#10;padding: 16px 24px;"><?=admin_h($value)?></textarea><small>Enter CSS declarations only (property: value;). The selected button target is scoped automatically. Unsafe properties and external URLs are blocked.</small></label><?php elseif($key==='font_family'):?><label>Font Family<select name="theme[font_family]" data-theme-key="font_family"><?php foreach(['Inter','Arial','Georgia','Poppins','Roboto','Montserrat'] as $font):?><option value="<?=admin_h($font)?>" <?=$value===$font?'selected':''?>><?=admin_h($font)?></option><?php endforeach;?></select></label><?php else:$numRange=isset($themeNumericRanges[$key])?$themeNumericRanges[$key]:($key==='container_width'?[760,1800]:($key==='section_spacing'?[32,180]:[0,48]));?><label><?=admin_h(ucwords(str_replace('_',' ',$key)))?><input type="number" name="theme[<?=admin_h($key)?>]" value="<?=admin_h($value)?>" min="<?=(int)$numRange[0]?>" max="<?=(int)$numRange[1]?>" step="1" data-theme-key="<?=admin_h($key)?>"></label><?php endif;endforeach;?></div><div class="theme-group-actions"><button type="button" class="btn primary" data-save-theme-group>Save changes</button><button type="button" class="btn secondary" data-reset-theme-group>Reset</button></div></div><?php endforeach;?></div><div class="theme-sticky-actions"><span class="theme-dirty-status" aria-live="polite">Edit any section to reveal its Save changes and Reset buttons.</span><div class="theme-sticky-buttons"><button type="submit" class="btn primary theme-save-all">Save all changes</button><button type="submit" name="save_as_default" value="1" class="btn secondary theme-save-default" onclick="return confirm('Save the current website appearance and logo as your new default settings?')">Save current changes as default</button></div></div></form><?php admin_form_start('reset_theme');?><button class="btn danger" type="submit" onclick="return confirm('Restore default theme values?')"><?= $hasCustomThemeDefault ? "Restore saved default theme" : "Restore system defaults" ?></button></form></div>
<?php elseif($page==='navigation'):?><div class="panel" id="navigation-editor"><h3>Add / edit navigation link</h3><p class="muted">Control the label, destination and placement of every header / menu link. The styling fields below are optional – anything left blank inherits the global navigation bar styling from the Theme page.</p><?php admin_form_start('save_navigation');?><input type="hidden" name="id" value=""><div class="form-grid"><?php field('Menu label','label');field('Destination URL','url');field('Display order','sort_order','0','number');?><label>Show in<select name="placement"><option value="both">Header bar and mobile menu</option><option value="header">Header bar only</option><option value="mobile">Mobile menu only</option></select></label></div><label class="check"><input type="checkbox" name="is_active" checked> Visible</label><label class="check"><input type="checkbox" name="open_new_tab"> Open in new tab</label><h4>Link styling (optional)</h4><div class="form-grid"><?php nav_color_field('Text colour','text_color');nav_color_field('Background colour','background_color');nav_color_field('Hover text colour','hover_text_color');nav_color_field('Hover background colour','hover_background_color');field('Border radius px (blank = inherit)','border_radius','','number');field('Font size px (blank = inherit)','font_size','','number');?><label>Font weight<select name="font_weight"><option value="">Inherit from navigation bar</option><option value="400">400 · Regular</option><option value="500">500 · Medium</option><option value="600">600 · Semi bold</option><option value="700">700 · Bold</option><option value="800">800 · Extra bold</option></select></label><label>Letter case<select name="text_transform"><option value="">Inherit from navigation bar</option><option value="none">As typed</option><option value="uppercase">UPPERCASE</option><option value="capitalize">Capitalise Each Word</option><option value="lowercase">lowercase</option></select></label></div><label class="custom-css-field">Extra CSS declarations<input type="text" name="custom_css" value="" spellcheck="false" placeholder="text-transform: uppercase; letter-spacing: .08em"><small>Enter property: value pairs separated by semicolons. Unsafe values such as position, url() and external imports are blocked.</small></label><button class="btn primary">Add menu item</button></form></div><div class="panel"><h3>Menu items</h3><?php $navPlacementLabels=['both'=>'Header + mobile menu','header'=>'Header bar only','mobile'=>'Mobile menu only'];foreach(rows("SELECT * FROM navigation_items ORDER BY sort_order,id") as $r):?><div class="manage-row"><div><b><?=admin_h($r['label'])?></b><small><?=admin_h($r['url'])?></small></div><span><?=$r['is_active']?'Visible':'Hidden'?> · <?=admin_h($navPlacementLabels[$r['placement']??'both']??'Header + mobile menu')?> · Order <?=(int)$r['sort_order']?> · <?=($r['text_color']||$r['background_color']||$r['hover_text_color']||$r['custom_css']||$r['font_size']||$r['border_radius'])?'Custom style':'Inherits bar style'?><?=($r['open_new_tab']??0)?' · New tab':''?></span><button type="button" class="btn small" data-edit-nav="<?=admin_h(json_encode($r))?>">Edit</button><form method="post"><input type="hidden" name="csrf" value="<?=admin_h(admin_csrf())?>"><input type="hidden" name="action" value="delete_navigation"><input type="hidden" name="id" value="<?=$r['id']?>"><button class="btn danger small" onclick="return confirm('Delete this menu item?')">Delete</button></form></div><?php endforeach;?></div>
<?php elseif($page==='settings'):?><div class="panel"><h3>Global website settings</h3><?php admin_form_start('save_settings');$settings=$pdo->query("SELECT * FROM site_settings ORDER BY setting_key")->fetchAll();?><div class="form-grid"><?php foreach($settings as $r):if(in_array($r['setting_key'],['logo','favicon','__theme_default_snapshot'],true))continue;field(ucwords(str_replace('_',' ',$r['setting_key'])),'settings['.$r['setting_key'].']',$r['setting_value']);endforeach;?><label>Website logo (PNG/JPG/WebP)<input type="file" name="logo_file" accept="image/jpeg,image/png,image/webp" data-image-cap-bytes="<?=wave_image_cap_bytes()?>" data-image-cap-label="<?=admin_h(wave_image_cap_label())?>"></label><label>Favicon image<input type="file" name="favicon_file" accept="image/jpeg,image/png,image/webp" data-image-cap-bytes="<?=wave_image_cap_bytes()?>" data-image-cap-label="<?=admin_h(wave_image_cap_label())?>"></label></div><button class="btn primary">Save settings</button></form></div>
<?php elseif($page==='testimonials'):?><div class="panel" id="testimonial-editor"><h3>Add / edit customer testimonial</h3><?php admin_form_start('save_testimonial');?><input type="hidden" name="id" value=""><div class="form-grid"><?php field('Customer name','customer_name');field('Photography category','category');?></div><?php textarea_field('Review','review');?><label class="check"><input type="checkbox" name="is_active" checked> Publish</label><button class="btn primary">Save testimonial</button></form></div><div class="panel"><?php foreach(rows("SELECT * FROM testimonials ORDER BY sort_order,id") as $r):?><div class="manage-row"><div><b><?=admin_h($r['customer_name'])?></b><small><?=admin_h($r['category'])?></small></div><span><?=admin_h($r['review'])?></span><button type="button" class="btn small" data-edit-testimonial="<?=admin_h(json_encode($r))?>">Edit</button><form method="post"><input type="hidden" name="csrf" value="<?=admin_h(admin_csrf())?>"><input type="hidden" name="action" value="delete_testimonial"><input type="hidden" name="id" value="<?=$r['id']?>"><button class="btn danger small">Hide</button></form></div><?php endforeach;?></div>
<?php elseif($page==='bookings'):?><div class="panel"><h3>Customer enquiries</h3><?php foreach(rows("SELECT * FROM bookings ORDER BY created_at DESC LIMIT 200") as $r):?><div class="manage-row"><div><b><?=admin_h($r['customer_name'])?></b><small><?=admin_h($r['phone'])?> · <?=admin_h($r['email'])?></small></div><span><?=admin_h($r['event_type'])?> · <?=admin_h($r['event_date'])?><br><?=admin_h($r['message'])?></span><form method="post"><input type="hidden" name="csrf" value="<?=admin_h(admin_csrf())?>"><input type="hidden" name="action" value="booking_status"><input type="hidden" name="id" value="<?=$r['id']?>"><select name="status"><option <?= $r['status']==='new'?'selected':''?>>new</option><option <?= $r['status']==='contacted'?'selected':''?>>contacted</option><option <?= $r['status']==='confirmed'?'selected':''?>>confirmed</option><option <?= $r['status']==='closed'?'selected':''?>>closed</option></select><button class="btn small primary">Update</button></form></div><?php endforeach;?></div>
<?php elseif($page==='account'):?><div class="panel"><h3>Change administrator password</h3><?php admin_form_start('change_password');?><div class="form-grid"><?php field('Current password','current_password','','password');field('New password (minimum 10 characters)','new_password','','password');?></div><button class="btn primary">Update password</button></form></div>
<?php elseif($page==='media'):?><div class="panel"><h3>Uploaded media library</h3><p class="muted">All images uploaded through the admin portal, including service covers, gallery photos, website logo and content images.</p><div class="photo-grid"><?php foreach(rows("SELECT * FROM media_library ORDER BY id DESC LIMIT 300") as $r):?><article class="photo-card"><img src="../<?=admin_h($r['file_path'])?>" alt="<?=admin_h($r['alt_text']??$r['original_name'])?>"><div><b><?=admin_h($r['original_name'])?></b><small><?=admin_h($r['mime_type'])?> · <?=number_format((int)$r['file_size']/1024)?> KB</small><small><?=admin_h($r['file_path'])?></small></div></article><?php endforeach;?></div></div>
<?php elseif($page==='activity'):?><div class="panel"><h3>Administrative activity</h3><?php foreach(rows("SELECT l.*,u.username FROM admin_activity_logs l LEFT JOIN admin_users u ON u.id=l.admin_id ORDER BY l.id DESC LIMIT 100") as $r):?><div class="activity-row"><b><?=admin_h($r['action'])?></b><span><?=admin_h($r['details'])?></span><time><?=admin_h($r['username'].' · '.$r['created_at'])?></time></div><?php endforeach;?></div><?php endif;?></div><footer class="admin-footer">Wave Photography Admin · Secure website management</footer></main></div><script>
(function(){
  document.querySelectorAll('input[type="color"][name^="theme["]').forEach(function(input){
    input.addEventListener('input',function(){
      var key=input.name.slice(6,-1).replace(/_/g,'-');
      document.documentElement.style.setProperty('--'+key,input.value);
    });
  });
  document.querySelectorAll('form').forEach(function(form){
    form.addEventListener('submit',function(){
      var button=form.querySelector('button[type="submit"],button:not([type])');
      if(button && !button.disabled){button.disabled=true;button.dataset.oldText=button.textContent;button.textContent='Saving…';}
    });
  });
  function editRows(selector,dataKey,formSelector,fields,after){
    document.querySelectorAll(selector).forEach(function(button){
      button.addEventListener('click',function(){
        var form=document.querySelector(formSelector);
        if(!form)return;
        var data;
        try{data=JSON.parse(button.dataset[dataKey]||'{}');}
        catch(error){return;}
        fields.forEach(function(name){var input=form.querySelector('[name="'+name+'"]');if(input)input.value=data[name] == null ? '' : data[name];});
        if(after)after(form,data);
        var panel=form.closest('.panel')||form;
        panel.scrollIntoView({behavior:'smooth',block:'start'});
      });
    });
  }
  editRows('[data-edit-section]','editSection','#section-editor form',['id','name','slug','category','description','sort_order','layout'],function(form,data){
    var image=form.querySelector('[name="existing_image"]');if(image)image.value=data.cover_image||'';
    var active=form.querySelector('[name="is_active"]');if(active)active.checked=!!Number(data.is_active);
  });
  editRows('[data-edit-service]','editService','#service-editor form',['id','name','slug','category','description','sort_order'],function(form,data){
    var image=form.querySelector('[name="image"]');if(image)image.value=data.image||'';
    var active=form.querySelector('[name="is_active"]');if(active)active.checked=!!Number(data.is_active);
    var inclusions=form.querySelector('[name="inclusions"]');if(inclusions){try{inclusions.value=JSON.parse(data.inclusions||'[]').join('\n');}catch(error){inclusions.value='';}}
  });
  editRows('[data-edit-nav]','editNav','#navigation-editor form',['id','label','url','sort_order','border_radius','font_size','custom_css'],function(form,data){
    ['open_new_tab','is_active'].forEach(function(name){var input=form.querySelector('[name="'+name+'"]');if(input)input.checked=!!Number(data[name]);});
    var placement=form.querySelector('[name="placement"]');if(placement)placement.value=['both','header','mobile'].indexOf(data.placement)!==-1?data.placement:'both';
    ['font_weight','text_transform'].forEach(function(name){var input=form.querySelector('[name="'+name+'"]');if(input)input.value=data[name]==null?'':data[name];});
    ['text_color','background_color','hover_text_color','hover_background_color'].forEach(function(name){
      var value=data[name]==null?'':String(data[name]);
      var store=form.querySelector('[data-nav-style="'+name+'"]');
      var picker=form.querySelector('[data-nav-color-for="'+name+'"]');
      var clear=form.querySelector('[data-nav-style-clear="'+name+'"]');
      if(store)store.value=value;
      if(picker)picker.value=/^#[0-9a-f]{6}$/i.test(value)?value:'#173b49';
      if(clear)clear.checked=!value;
    });
    var submit=form.querySelector('button.primary');if(submit)submit.textContent=data.id?'Update menu item':'Add menu item';
  });
  editRows('[data-edit-testimonial]','editTestimonial','#testimonial-editor form',['id','customer_name','category','review','rating'],function(form,data){
    var active=form.querySelector('[name="is_active"]');if(active)active.checked=!!Number(data.is_active);
    var image=form.querySelector('[name="customer_image"]');if(image)image.value=data.customer_image||'';
  });
  var preview=document.getElementById('theme-preview');
  if(preview){
    var previewMap={header_background:'--preview-header',page_background:'--preview-page',card_background:'--preview-card',heading_color:'--preview-heading',text_color:'--preview-text',button_background:'--preview-button',button_text_color:'--preview-button-text',footer_background:'--preview-footer',default_section_background:'--preview-section',section_heading_color:'--preview-section-heading',section_text_color:'--preview-section-text',card_heading_color:'--preview-card-heading',card_text_color:'--preview-card-text',card_border_color:'--preview-card-border',footer_text_color:'--preview-footer-text',header_button_background:'--preview-button',header_button_text_color:'--preview-button-text'};
    document.querySelectorAll('[data-theme-key]').forEach(function(input){
      var update=function(){var key=input.dataset.themeKey;if(previewMap[key])preview.style.setProperty(previewMap[key],input.value);if(key==='font_family')preview.querySelector('.theme-preview-body b').style.fontFamily=input.value;};
      input.addEventListener('input',update);input.addEventListener('change',update);
    });
  }
  var menuButton=document.querySelector('.mobile-menu');
  if(menuButton)menuButton.addEventListener('click',function(){document.getElementById('sidebar')?.classList.toggle('show');});
  document.querySelectorAll('#navigation-editor [data-nav-color-for]').forEach(function(picker){
    var name=picker.dataset.navColorFor;
    var store=document.querySelector('#navigation-editor [data-nav-style="'+name+'"]');
    var clear=document.querySelector('#navigation-editor [data-nav-style-clear="'+name+'"]');
    if(!store||!clear)return;
    picker.addEventListener('input',function(){store.value=picker.value;clear.checked=false;});
    clear.addEventListener('change',function(){store.value=clear.checked?'':picker.value;});
  });
})();
</script><script>
document.addEventListener("DOMContentLoaded",function(){
 // Single-image fields had no client-side size check, so an oversized file uploaded in full
 // before the server refused it. The cap and the host's own upload_max_filesize are both
 // rendered onto the form so the browser can answer immediately instead.
 var forms=document.querySelectorAll('form');
 Array.prototype.forEach.call(forms,function(form){
  Array.prototype.forEach.call(form.querySelectorAll('input[type="file"]'),function(input){
   if(input.multiple)return;
   var cap=parseInt(input.dataset.imageCapBytes,10);
   if(!(cap>0))return;
   var capLabel=input.dataset.imageCapLabel||Math.round(cap/1048576)+' MB';
   var note=document.createElement('small');
   note.className='field-note';
   input.parentNode.insertBefore(note,input.nextSibling);
   input.addEventListener('change',function(){
    var file=input.files&&input.files[0];
    if(!file){note.textContent='';note.classList.remove('is-error');return;}
    if(file.size>cap){
     note.textContent='"'+file.name+'" is '+Math.round(file.size/1048576)+' MB. The maximum is '+capLabel+'. Choose a smaller image.';
     note.classList.add('is-error');
     input.value='';
     return;
    }
    note.textContent='"'+file.name+'" is '+Math.round(file.size/1048576)+' MB - within the '+capLabel+' limit.';
    note.classList.remove('is-error');
   });
});
 });
  var actionInput=document.querySelector('form.admin-form input[name="action"][value="save_theme"]');
 var form=actionInput? actionInput.closest("form"):null;
 if(!form)return;
 var groups=Array.prototype.slice.call(form.querySelectorAll(".theme-group"));
 var logoFile=form.querySelector('input[name="theme_logo_file"]');
 function valueOf(el){if(el.type==="checkbox"||el.type==="radio")return el.checked?"1":"0";return el.value;}
 function markDirty(group){if(!group)return;group.classList.add("is-dirty");var status=form.querySelector(".theme-dirty-status");if(status)status.textContent="Unsaved changes in "+((group.querySelector("h4")||{}).textContent||"this section").trim()+".";}
 function markClean(group){if(!group)return;group.classList.remove("is-dirty");}
 groups.forEach(function(group){
  group.querySelectorAll(".form-grid input,.form-grid select,.form-grid textarea").forEach(function(control){control.dataset.initialValue=valueOf(control);});
  group.addEventListener("input",function(event){if(event.target.matches("input,select,textarea"))markDirty(group);});
  group.addEventListener("change",function(event){if(event.target.matches("input,select,textarea"))markDirty(group);});
 });
 var logoFiles=Array.prototype.slice.call(form.querySelectorAll('input[type="file"][name$="_file"]'));
 logoFiles.forEach(function(file){file.addEventListener("change",function(){var header=groups.find(function(g){return (g.querySelector("h4")||{}).textContent.toLowerCase().indexOf("header")!==-1;});if(file.files&&file.files.length)markDirty(header);});});
 function submitGroup(group,button){
  if(!group)return;
  form.querySelectorAll('[name^="theme["]').forEach(function(control){if(!group.contains(control))control.disabled=true;});
  var isHeader=(group.querySelector("h4")||{}).textContent.toLowerCase().indexOf("header")!==-1;
  logoFiles.forEach(function(file){file.disabled=!isHeader;});
  if(button){button.disabled=true;button.textContent="Saving…";}
  HTMLFormElement.prototype.submit.call(form);
 }
 form.querySelectorAll("[data-save-theme-group]").forEach(function(button){button.addEventListener("click",function(){submitGroup(button.closest(".theme-group"),button);});});
 form.querySelectorAll("[data-reset-theme-group]").forEach(function(button){button.addEventListener("click",function(){
  var group=button.closest(".theme-group");if(!group)return;
  group.querySelectorAll(".form-grid input,.form-grid select,.form-grid textarea").forEach(function(control){
   if(control.type==="file")return;
   if(control.type==="checkbox"||control.type==="radio")control.checked=control.defaultChecked;
   else control.value=control.defaultValue;
   control.dispatchEvent(new Event("input",{bubbles:true}));
  });
  markClean(group);
  var status=form.querySelector(".theme-dirty-status");if(status)status.textContent="Section restored to the values loaded when this page opened.";
 });});
});
</script><script>
(function(){
  var input=document.querySelector('[data-bulk-image-upload]');
  if(!input)return;
  var status=document.getElementById('bulk-upload-status');
  var counter=document.querySelector('[data-bulk-file-count]');
  var form=input.closest('form');
  var submit=form?form.querySelector('button[type="submit"],button:not([type])'):null;
  var csrfField=form?form.querySelector('input[name="csrf"]'):null;
  var categoryField=form?form.querySelector('[name="category"]'):null;
  var orderField=form?form.querySelector('[name="sort_order"]'):null;
  var MB=1024*1024,perFile=parseInt(input.dataset.imageCapBytes,10)||30*MB,totalLimit=320*MB,maxFiles=50;
  var WAVE_TOTAL_CEILING=parseInt(input.dataset.maxImageBytes,10)||500*MB;
  var serverMaxFiles=parseInt(input.dataset.serverMaxFiles,10)||0;
  var serverMaxFile=parseInt(input.dataset.serverMaxFileBytes,10)||0;
  var serverMaxTotal=parseInt(input.dataset.serverMaxTotalBytes,10)||0;
  var serverFileLabel=input.dataset.serverMaxFileLabel||'';
  var serverTotalLabel=input.dataset.serverMaxTotalLabel||'';
  // The server already clamps the batch to what this host can accept, so prefer its number
  // over our own fallback. Silently sending a bigger batch is what produced the blank
  // failures this replaces.
  var effectiveFileLimit=serverMaxFile>0?Math.min(perFile,serverMaxFile):perFile;
  var effectiveTotalLimit=serverMaxTotal>0?Math.min(totalLimit,serverMaxTotal):totalLimit;
  var effectiveCountLimit=serverMaxFiles>0?Math.min(maxFiles,serverMaxFiles):maxFiles;
  function fileLabel(){return serverMaxFile>0&&serverMaxFile<perFile?serverFileLabel:Math.round(perFile/MB)+' MB';}
  function totalLabel(){return serverMaxTotal>0?serverTotalLabel:Math.round(effectiveTotalLimit/MB)+' MB';}
  function describe(){return 'Each image up to '+fileLabel()+', up to '+effectiveCountLimit+' images per request, '+totalLabel()+' per request. Larger selections are sent as several batches automatically.';}
  function reject(message){if(status){status.textContent=message;status.classList.add('is-error');}input.value='';if(counter)counter.value='0';return false;}
  // Greedy split so that every request stays under this host's post_max_size. Without this
  // a 125 MB selection is thrown away whole by PHP on a host with a small post_max_size,
  // which is the failure this whole path exists to prevent.
  function planBatches(files){
   var budget=Math.max(effectiveFileLimit,effectiveTotalLimit);
   var perRequest=Math.min(maxFiles,effectiveCountLimit);
   var batches=[],current=[],bytes=0;
   files.forEach(function(file){
    var projected=bytes+file.size;
    if(current.length&&(projected>budget||current.length>=perRequest)){
     batches.push({files:current,bytes:bytes});current=[];bytes=0;projected=file.size;
    }
    current.push(file);bytes+=file.size;
   });
   if(current.length)batches.push({files:current,bytes:bytes});
   return batches;
  }
  function inspect(){
   var files=Array.prototype.slice.call(input.files||[]);
   if(status)status.classList.remove('is-error');
   if(!files.length){if(counter)counter.value='0';return true;}
   if(counter)counter.value=String(files.length);
   var total=files.reduce(function(sum,file){return sum+file.size;},0);
   var oversized=files.filter(needsSlicing);
   var normal=files.filter(function(file){return !needsSlicing(file);});
   var batches=planBatches(normal);
   if(files.length>maxFiles)return reject('This batch has '+files.length+' images but only '+maxFiles+' can be uploaded at once. Select fewer images.');
   var tooBig=normal.find(function(file){return file.size>WAVE_TOTAL_CEILING;});
   if(tooBig)return reject('"'+tooBig.name+'" is '+Math.round(tooBig.size/MB)+' MB, above the '+Math.round(WAVE_TOTAL_CEILING/MB)+' MB maximum for one image. Please resize it before uploading.');
   if(total>effectiveTotalLimit&&batches.length<=1&&!oversized.length)return reject('The selection totals '+(total/MB).toFixed(1)+' MB but this batch is limited to '+totalLabel()+'. Upload fewer images at a time.');
   var plan=describePlan(files.length,total,oversized,batches);
   if(status)status.textContent=plan;
   return true;
  }
  // A file bigger than one request can carry cannot be sent whole on any host, so it is
  // sliced into pieces the host will accept and stitched back together server side.
  function needsSlicing(file){return file.size>wholeFileLimit();}
  function wholeFileLimit(){return Math.min(effectiveFileLimit,effectiveTotalLimit);}
  function describePlan(count,total,oversized,batches){
   var head=count+' image(s) selected · '+(total/MB).toFixed(1)+' MB. ';
   if(oversized.length)head+=oversized.length+' file(s) exceed '+fileLabel()+' and will be uploaded in slices. ';
   head+=batches.length>1?'The rest will be sent as '+batches.length+' batches of up to '+totalLabel()+' each.':describe();
   return head;
  }
  function postFields(fields){
   var body=new FormData();
   Object.keys(fields).forEach(function(key){body.append(key,String(fields[key]));});
   body.set('csrf',csrfField?csrfField.value:'');
   body.set('action','chunk_upload');
   return fetch(form.getAttribute('action')||window.location.href,{method:'POST',body:body,credentials:'same-origin'})
    .then(function(response){return response.text();})
    .then(function(text){
     var payload;
     try{payload=JSON.parse(text);}catch(parseError){throw new Error('The server did not return a valid response. It may have restarted or timed out.');}
     if(!payload||typeof payload!=='object'||typeof payload.ok!=='boolean')throw new Error('The server did not return a valid response.');
     if(!payload.ok)throw new Error(payload.message||'The server rejected this upload.');
     return payload;
    });
  }
  function uploadInSlices(file,orderBase,category,onProgress){
   var label=file.name;
   return postFields({chunk_phase:'start',chunk_name:label,chunk_total:file.size,chunk_type:file.type,category:category,sort_order:orderBase})
    .then(function(started){
     var token=started.token,chunkSize=started.chunkSize>0?started.chunkSize:MB,offset=0,token_=token;
     function step(){
      if(offset>=file.size)return postFields({chunk_phase:'finish',chunk_token:token_,category:category,sort_order:orderBase});
      var end=Math.min(file.size,offset+chunkSize);
      var body=new FormData();
      body.append('csrf',csrfField?csrfField.value:'');
      body.append('action','chunk_upload');
      body.append('chunk_phase','part');
      body.append('chunk_token',token_);
      body.append('chunk_offset',String(offset));
      body.append('chunk_total',String(file.size));
      body.append('chunk_file',file.slice(offset,end),label);
      if(onProgress)onProgress(offset,file.size);
      return fetch(form.getAttribute('action')||window.location.href,{method:'POST',body:body,credentials:'same-origin'})
       .then(function(response){return response.text();})
       .then(function(text){
        var payload;
        try{payload=JSON.parse(text);}catch(parseError){throw new Error('The server did not return a valid response while uploading "'+label+'".');}
        if(!payload||payload.ok!==true)throw new Error((payload&&payload.message)||'The server rejected part of "'+label+'".');
        offset=end;
        return step();
       });
     }
     return step();
    });
  }
  function sendBatch(batch,orderBase,category,csrf){
   var body=new FormData();
   body.append('csrf',csrf);
   body.append('action','bulk_upload');
   body.append('category',category);
   body.append('sort_order',String(orderBase));
   body.append('bulk_file_count',String(batch.files.length));
   body.append('bulk_chunk','1');
   batch.files.forEach(function(file){body.append('image_files[]',file,file.name);});
   return fetch(form.getAttribute('action')||window.location.href,{method:'POST',body:body,credentials:'same-origin'})
    .then(function(response){return response.text();})
    .then(function(text){
     var payload;
     try{payload=JSON.parse(text);}catch(parseError){throw new Error('The server did not return a valid response for this batch. It may have restarted or timed out.');}
     if(!payload||typeof payload!=='object'||typeof payload.ok!=='boolean')throw new Error('The server did not return a valid response for this batch.');
     if(!payload.ok)throw new Error(payload.message||'The server rejected this batch.');
     return payload;
    });
  }
  var running=false;
  // Runs the whole selection: files that fit a request go out as normal batches, files that
  // do not are sliced. Sliced files go first so the slow part starts immediately.
  function uploadInBatches(files){
   var sliced=files.filter(needsSlicing);
   var plain=files.filter(function(file){return !needsSlicing(file);});
   var batches=planBatches(plain);
   var steps=batches.map(function(batch){return {kind:'batch',batch:batch};})
                  .concat(sliced.map(function(file){return {kind:'slice',file:file};}));
   var category=categoryField?(categoryField.value||''):'';
   var orderBase=orderField?parseInt(orderField.value,10)||0:0;
   var csrf=csrfField?csrfField.value:'';
   var added=0,notes=[],failures=[],index=0,finished=0;
   running=true;
   if(submit)submit.disabled=true;
   function finish(){
    running=false;
    if(submit)submit.disabled=false;
    var message=added+' of '+files.length+' image(s) uploaded to "'+category+'".';
    if(notes.length)message+=' '+notes.length+' skipped: '+notes.slice(0,3).join(' ')+(notes.length>3?' (+'+(notes.length-3)+' more)':'');
    if(failures.length)message+=' '+failures.length+' failed: '+failures.slice(0,3).join(' ')+(failures.length>3?' (+'+(failures.length-3)+' more)':'');
    if(status){status.textContent=message;if(notes.length||failures.length)status.classList.add('is-error');}
    input.value='';if(counter)counter.value='0';
    setTimeout(function(){window.location.reload();},3000);
   }
   function abort(error){
    running=false;
    if(submit)submit.disabled=false;
    // Clear the selection: resending it would re-upload the work that already landed.
    // The message tells the admin to pick the remaining files again.
    input.value='';if(counter)counter.value='0';
    if(status){status.textContent='Stopped after '+finished+' of '+steps.length+' step(s): '+error.message+(added?' '+added+' image(s) were already saved - reselect the remaining files to continue.':' Nothing was uploaded.');status.classList.add('is-error');}
   }
   function next(){
    if(index>=steps.length){finish();return;}
    var step=steps[index];
    if(step.kind==='batch'){
     if(status){status.classList.remove('is-error');status.textContent='Uploading batch '+(index+1)+' of '+steps.length+' · '+step.batch.files.length+' image(s), '+(step.batch.bytes/MB).toFixed(1)+' MB…';}
     sendBatch(step.batch,orderBase+added,category,csrf).then(function(payload){
      added+=payload.added||0;
      if(payload.notes)notes=notes.concat(payload.notes);
      if(payload.failures)failures=failures.concat(payload.failures);
      finished++;index++;
      next();
     }).catch(abort);
     return;
    }
    var label=step.file.name;
    uploadInSlices(step.file,orderBase+added,category,function(sent){
     if(status){status.classList.remove('is-error');status.textContent='Uploading "'+label+'" in slices · '+Math.round(sent/MB)+' MB of '+Math.round(step.file.size/MB)+' MB sent ('+(index+1)+' of '+steps.length+')…';}
    }).then(function(payload){
     added+=payload.added||0;
     if(payload.notes)notes=notes.concat(payload.notes);
     if(payload.failures)failures=failures.concat(payload.failures);
     finished++;index++;
     next();
    }).catch(abort);
   }
   next();
  }
  input.addEventListener('change',inspect);
  if(status)status.textContent='Select multiple images. '+describe();
  if(form)form.addEventListener('submit',function(event){
   if(running){event.preventDefault();return;}
   if(!inspect()){event.preventDefault();return;}
   var files=Array.prototype.slice.call(input.files||[]);
   var plain=files.filter(function(file){return !needsSlicing(file);});
   // A single request covers everything, so keep the ordinary form post and its
   // redirect-and-flash flow. Only take over when a request would be discarded.
   if(planBatches(plain).length<=1&&!files.some(needsSlicing))return;
   event.preventDefault();
   uploadInBatches(files);
  });
})();
</script></body></html>
