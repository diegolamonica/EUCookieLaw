if(file_exists('%%DIR%%/eucookielaw-cache.php') && defined('WP_CACHE') && WP_CACHE){
	define('EUCL_CONTENT_DIR', '%%WP_CONTENT%%');
	require_once '%%DIR%%/eucookielaw-cache.php';
}
