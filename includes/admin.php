<?php
function admin_session_start(){
 if(session_status()!==PHP_SESSION_ACTIVE){$secure=(!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off');session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>$secure,'httponly'=>true,'samesite'=>'Lax']);session_start();}
}
function admin_logged_in(){return !empty($_SESSION['wave_admin_id']);}
function admin_h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function admin_csrf(){if(empty($_SESSION['wave_admin_csrf']))$_SESSION['wave_admin_csrf']=bin2hex(random_bytes(32));return $_SESSION['wave_admin_csrf'];}
function admin_csrf_check(){if(!isset($_POST['csrf'])||!hash_equals(admin_csrf(),(string)$_POST['csrf'])){http_response_code(419);exit('Your session expired. Refresh the page and try again.');}}
function admin_log($action,$details=''){if(!admin_logged_in())return;try{$q=db()->prepare("INSERT INTO admin_activity_logs(admin_id,action,details,ip_address) VALUES(?,?,?,?)");$q->execute([(int)$_SESSION['wave_admin_id'],$action,substr($details,0,500),substr($_SERVER['REMOTE_ADDR']??'',0,45)]);}catch(Throwable $e){error_log('[Wave Admin log] '.$e->getMessage());}}
function admin_logout(){if(session_status()!==PHP_SESSION_ACTIVE)session_start();if(!empty($_SESSION['wave_admin_id']))admin_log('logout','Administrator signed out');$_SESSION=[];if(ini_get('session.use_cookies')){$p=session_get_cookie_params();setcookie(session_name(),'',time()-42000,$p['path'],$p['domain'],$p['secure'],$p['httponly']);}session_destroy();}
