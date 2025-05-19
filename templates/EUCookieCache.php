<?php
/**
 * EUCookieLaw: EUCookieLaw a complete solution to accomplish european law requirements about cookie consent
 * @version 2.4.0
 * @link https://github.com/diegolamonica/EUCookieLaw/
 * @author Diego La Monica (diegolamonica) <diego.lamonica@gmail.com>
 * @copyright 2015 Diego La Monica <https://diegolamonica.info>
 * @license http://www.gnu.org/licenses/lgpl-3.0-standalone.html GNU Lesser General Public License
 * @note This program is distributed in the hope that it will be useful - WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

if(isset($_SERVER['REDIRECT_URL']) && isset($_GET['p'])){

	require_once('%%DIR%%/gzcompat.php');

	if(!defined('ABSPATH')) define('ABSPATH', '%%ABSPATH%%');

	if(!function_exists('sanitize_file_name')) {
		require_once ABSPATH . 'wp-load.php';
	}

	$redirectURL = $_SERVER['REDIRECT_URL'];
	$requestedPage = $_GET['p'];
	$sanitized = sanitize_file_name( $requestedPage );

	$redirectURL = str_replace("//", "/", $redirectURL);

	if( preg_match( "#" . preg_quote($redirectURL, '$#') . "#", $redirectURL) ) {

		$buffer = file_get_contents( $sanitized );
		$decoded = gzdecode($buffer);
		if($decoded) $buffer = $decoded;
		if(!defined('WP_CACHE')) define('WP_CACHE', true);

		require_once('%%DIR%%/eucookielaw-cache.php');

		echo $buffer;

	}
}else{

}