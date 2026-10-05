<?php
function admin_session_start(){
 if(session_status()!==PHP_SESSION_ACTIVE){$secure=(!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off');session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>$secure,'httponly'=>true,'samesite'=>'Lax']);session_start();}
}
function admin_flash($type,$message){if(!isset($_SESSION['wave_admin_flash'])||!is_array($_SESSION['wave_admin_flash']))$_SESSION['wave_admin_flash']=[];$_SESSION['wave_admin_flash'][]=['type'=>$type==='error'?'error':'success','message'=>trim((string)$message)];}
function admin_flash_take(){if(empty($_SESSION['wave_admin_flash'])||!is_array($_SESSION['wave_admin_flash']))return [];$flashes=$_SESSION['wave_admin_flash'];unset($_SESSION['wave_admin_flash']);return $flashes;}
function wave_ini_bytes($value){$value=trim((string)$value);if($value===''||$value==='-1')return 0;$unit=strtolower(substr($value,-1));$number=(float)$value;return (int)round($number*(['k'=>1024,'m'=>1048576,'g'=>1073741824][$unit]??1));}
// Chunked bulk uploads drive the endpoint over fetch(), which cannot follow a redirect and
// read the resulting flash banner, so those requests get a JSON result instead.
function wave_json_response($payload){if(!headers_sent())header('Content-Type: application/json; charset=utf-8');echo json_encode($payload);exit;}
// Largest combined file size the admin bulk uploader will attempt. post_max_size covers
// the entire request body, so reserve headroom for the multipart headers and the other
// form fields rather than billing every byte of it to the file payload. Ceiling the result
// at 320 MB keeps a host with an unusually large post_max_size from inviting a batch that
// will only hit max_execution_time. Falls back to a conservative 200 MB when the host
// reports post_max_size as unlimited.
function wave_batch_limit_bytes(){static $cached=null;if($cached!==null)return $cached;$post=wave_ini_bytes((string)ini_get('post_max_size'));if($post<=0)return $cached=200*1024*1024;return $cached=max(2097152,min(335544320,$post-4194304));}
function wave_upload_error_message($code){$map=[UPLOAD_ERR_INI_SIZE=>'is larger than this host allows per file (upload_max_filesize)',UPLOAD_ERR_FORM_SIZE=>'is larger than the form size limit',UPLOAD_ERR_PARTIAL=>'was only uploaded partially',UPLOAD_ERR_NO_TMP_DIR=>'could not be stored: the server has no temporary folder',UPLOAD_ERR_CANT_WRITE=>'could not be written to the server disk',UPLOAD_ERR_EXTENSION=>'was blocked by a PHP extension'];return $map[(int)$code]??('failed with upload error '.(int)$code.'.');}
// Single source of truth for the per-image ceiling. The hosting limit can be lower than this,
// so what an admin can actually upload is always the smaller of the two - see
// wave_effective_image_cap_bytes() - and every message and label quotes the real number
// rather than a constant that can drift.
define('WAVE_IMAGE_CAP_BYTES',30*1024*1024);
function wave_image_cap_bytes(){return WAVE_IMAGE_CAP_BYTES;}
function wave_image_cap_label(){return round(wave_image_cap_bytes()/1048576).' MB';}
function wave_host_image_cap_bytes(){return wave_ini_bytes((string)ini_get('upload_max_filesize'));}
function wave_effective_image_cap_bytes(){return wave_lower_limit(wave_image_cap_bytes(),wave_host_image_cap_bytes());}
// post_max_size caps the whole request, so a single image is also bounded by it. Without
// this a host with upload_max_filesize 64M but post_max_size 20M rejects any large image by
// discarding the entire POST before this code runs at all.
function wave_effective_image_cap_bytes_with_request(){return wave_lower_limit(wave_effective_image_cap_bytes(),wave_request_cap_bytes());}
function wave_request_cap_bytes(){$post=wave_ini_bytes((string)ini_get('post_max_size'));return $post>0?max(1,$post-1048576):0;}
function wave_lower_limit($app,$host){if($host<=0)return $app;if($app<=0)return $host;return min($app,$host);}
function wave_effective_image_cap_label(){return round(wave_effective_image_cap_bytes()/1048576).' MB';}
function wave_effective_image_cap_label_with_request(){return round(wave_effective_image_cap_bytes_with_request()/1048576).' MB';}
// The hosting per-file limit is the usual reason a large image is refused, so name it and
// the ini that should have raised it rather than telling the admin to raise "25M".
function wave_host_image_cap_message(){return 'The hosting server only allows '.ini_get('upload_max_filesize').' per image (PHP upload_max_filesize), so this image was refused before it could be saved. The project ships a .user.ini that asks for a higher limit; if that is not being applied, your host must raise upload_max_filesize to at least '.wave_image_cap_label().'.';}

// ---- Resumable uploads -----------------------------------------------------
// A host with upload_max_filesize=20M refuses a 30 MB image before any of our code runs,
// and no PHP setting can raise it from inside the request. The only way through is to stop
// sending the file in one piece: the browser slices it into chunks that each fit under the
// host's own limits and the server appends them into one file. Each request is then a normal
// small upload that the host is happy to accept.
define('WAVE_CHUNK_MAX_TOTAL',524288000);
define('WAVE_CHUNK_MIN_PAYLOAD',262144);
function wave_chunk_payload_bytes(){
 $candidates=[8*1024*1024];
 $perFile=wave_host_image_cap_bytes();if($perFile>0)$candidates[]=$perFile-65536;
 $perRequest=wave_request_cap_bytes();if($perRequest>0)$candidates[]=$perRequest-1048576;
 $size=min($candidates);
 return max(WAVE_CHUNK_MIN_PAYLOAD,min($size,8*1024*1024));
}
function wave_chunk_root(){return dirname(__DIR__).'/uploads/tmp/chunks';}
function wave_chunk_dir($token){return wave_chunk_root().'/c_'.$token;}
function wave_chunk_valid_token($token){return is_string($token)&&preg_match('/^[a-f0-9]{32}$/',$token)===1;}
function wave_chunk_meta_file($dir){return $dir.'/meta.json';}
function wave_chunk_data_file($dir){return $dir.'/data.part';}
function wave_chunk_remove($dir){if(!is_dir($dir))return;foreach((array)glob($dir.'/*') as $file)if(is_file($file))@unlink($file);@rmdir($dir);}
// Abandoned sessions would otherwise leave partial files on disk forever. Only directories
// carrying a valid token name are touched, and only once they are well past any real upload.
function wave_chunk_sweep($maxAge=3600){
 $root=wave_chunk_root();if(!is_dir($root))return;
 foreach((array)glob($root.'/c_*',GLOB_ONLYDIR) as $dir){
  $token=basename($dir);if(!wave_chunk_valid_token(substr($token,2)))continue;
  $meta=wave_chunk_meta_file($dir);$created=is_file($meta)?(int)json_decode((string)file_get_contents($meta),true)['created']??0:0;
  if($created>0&&time()-$created<$maxAge)continue;
  if(is_file($meta)&&time()-(int)filemtime($meta)<$maxAge)continue;
  wave_chunk_remove($dir);
 }
}
function admin_logged_in(){return !empty($_SESSION['wave_admin_id']);}
function admin_h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function admin_csrf(){if(empty($_SESSION['wave_admin_csrf']))$_SESSION['wave_admin_csrf']=bin2hex(random_bytes(32));return $_SESSION['wave_admin_csrf'];}
function admin_csrf_check(){if(!isset($_POST['csrf'])||!hash_equals(admin_csrf(),(string)$_POST['csrf'])){http_response_code(419);exit('Your session expired. Refresh the page and try again.');}}
function admin_log($action,$details=''){if(!admin_logged_in())return;try{$q=db()->prepare("INSERT INTO admin_activity_logs(admin_id,action,details,ip_address) VALUES(?,?,?,?)");$q->execute([(int)$_SESSION['wave_admin_id'],$action,substr($details,0,500),substr($_SERVER['REMOTE_ADDR']??'',0,45)]);}catch(Throwable $e){error_log('[Wave Admin log] '.$e->getMessage());}}
function admin_logout(){if(session_status()!==PHP_SESSION_ACTIVE)session_start();if(!empty($_SESSION['wave_admin_id']))admin_log('logout','Administrator signed out');$_SESSION=[];if(ini_get('session.use_cookies')){$p=session_get_cookie_params();setcookie(session_name(),'',time()-42000,$p['path'],$p['domain'],$p['secure'],$p['httponly']);}session_destroy();}
