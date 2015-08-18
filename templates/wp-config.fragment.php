if(file_exists('%%DIR%%/eucookielaw-cache.php')){
	define('EUCL_CONTENT_DIR', '%%WP_CONTENT%%');
	require_once '%%DIR%%/eucookielaw-cache.php';
}
