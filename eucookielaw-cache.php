<?php
/**
 * EUCookieLaw: EUCookieLaw a complete solution to accomplish european law requirements about cookie consent
 * @version 2.0
 * @link https://github.com/diegolamonica/EUCookieLaw/
 * @author Diego La Monica (diegolamonica) <diego.lamonica@gmail.com>
 * @copyright 2015 Diego La Monica <http://diegolamonica.info>
 * @license http://www.gnu.org/licenses/lgpl-3.0-standalone.html GNU Lesser General Public License
 * @note This program is distributed in the hope that it will be useful - WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

if(defined('EUCOOKIELAW_FORCE_AS_CACHE') || defined('WP_CACHE') && WP_CACHE && (!defined('WP_ADMIN') || defined('WP_ADMIN') && WP_ADMIN !==true)) {

	require_once dirname(__FILE__) . '/eucookielaw-wp.php';
	global $euc_iniFile;
	if(!defined('WP_CONTENT_DIR')) define('WP_CONTENT_DIR', ABSPATH .'wp-content/');


	if(!function_exists('EUCgetOption')) {
		if ( file_exists( WP_CONTENT_DIR . '/cache/eucookielaw.ini' ) ) {

			$euc_iniFile = parse_ini_file( WP_CONTENT_DIR . '/cache/eucookielaw.ini' );

		} else {
			$euc_iniFile = array();
		}

		function EUCgetOption( $key, $defaultValue = false ) {
			global $euc_iniFile;
			$value = $defaultValue;
			if ( function_exists( 'get_option' ) ) {
				$value = get_option( $key, $defaultValue );
			} elseif ( isset( $euc_iniFile[ $key ] ) ) {

				$value = $euc_iniFile[ $key ];
			}

			return $value;
		}
	}

	$disalloweddDomains = EUCgetOption( EUCookieLaw::OPT_3RDPDOMAINS );
	$lookInTags         = EUCgetOption( EUCookieLaw::OPT_LOOKINTAGS, EUCookieLaw::OPT_DEFAULT_LOOKINTAGS );
	$lookInScripts      = EUCgetOption( EUCookieLaw::OPT_LOOKINSCRIPTS, 'n' );
	$debug              = EUCgetOption( EUCookieLaw::OPT_DEBUG, 'n' );
	$enabled            = EUCgetOption( EUCookieLaw::OPT_ENABLED, 'y' );
	$whitelstCookies    = EUCgetOption( EUCookieLaw::OPT_WHITELIST_COOKIES, '' );

	$title           = EUCgetOption( EUCookieLaw::OPT_TITLE, '' );
	$message         = EUCgetOption( EUCookieLaw::OPT_MESSAGE, '' );
	$agree           = EUCgetOption( EUCookieLaw::OPT_AGREE, '' );
	$disagree        = EUCgetOption( EUCookieLaw::OPT_DISAGREE, '' );
	$fixedOn         = EUCgetOption( EUCookieLaw::OPT_FIXED_ON, 'top' );
	$additionalClass = EUCgetOption( EUCookieLaw::OPT_BANNER_STYLE, '' );

	$agreeLink = $_SERVER['REQUEST_URI'];

	$url = preg_replace('#(\?|&)__eucookielaw=([^&]+)(&?(.*))#','$1$4', $_SERVER['REQUEST_URI']);
	$url = preg_replace('#(\?|&)$#','',$url);

	$disagreeLink = (preg_match( '#\?#', $url ) ? '&' : '?').'__eucookielaw=disagree';
	$agreeLink    = (preg_match( '#\?#', $url ) ? '&' : '?').'__eucookielaw=agree';

	!defined('EUCOOKIELAW_DISALLOWED_DOMAINS') && define( 'EUCOOKIELAW_DISALLOWED_DOMAINS', $disalloweddDomains );
	!defined('EUCOOKIELAW_LOOK_IN_TAGS') && define( 'EUCOOKIELAW_LOOK_IN_TAGS', $lookInTags );
	!defined('EUCOOKIELAW_LOOK_IN_SCRIPTS') && define( 'EUCOOKIELAW_LOOK_IN_SCRIPTS', $lookInScripts == 'y' );

	!defined('EUCOOKIELAW_BANNER_ADDITIONAL_CLASS') && define( 'EUCOOKIELAW_BANNER_ADDITIONAL_CLASS', 'fixedon-' . $fixedOn . ( empty( $additionalClass ) ? '' : " $additionalClass" ) );
	!defined('EUCOOKIELAW_BANNER_TITLE') && define( 'EUCOOKIELAW_BANNER_TITLE', $title );
	!defined('EUCOOKIELAW_BANNER_DESCRIPTION') && define( 'EUCOOKIELAW_BANNER_DESCRIPTION', $message );
	!defined('EUCOOKIELAW_BANNER_AGREE_BUTTON') && define( 'EUCOOKIELAW_BANNER_AGREE_BUTTON', $agree );
	!defined('EUCOOKIELAW_BANNER_DISAGREE_BUTTON') && define( 'EUCOOKIELAW_BANNER_DISAGREE_BUTTON', $disagree );

	!defined('EUCOOKIELAW_BANNER_AGREE_LINK') && define( 'EUCOOKIELAW_BANNER_AGREE_LINK', $agreeLink );
	!defined('EUCOOKIELAW_BANNER_DISAGREE_LINK') && define( 'EUCOOKIELAW_BANNER_DISAGREE_LINK', $disagreeLink );

	!defined('EUCOOKIELAW_DEBUG') && define( 'EUCOOKIELAW_DEBUG', ( $debug == 'y' ) );
	!defined('EUCOOKIELAW_DISABLED') && define( 'EUCOOKIELAW_DISABLED', $enabled !== 'y' );
	!defined('EUCOOKIELAW_ALLOWED_COOKIES') && define( 'EUCOOKIELAW_ALLOWED_COOKIES', $whitelstCookies );

	require_once dirname( __FILE__ ) . '/eucookielaw-header.php';

}