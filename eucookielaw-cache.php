<?php
/**
 * EUCookieLaw: EUCookieLaw a complete solution to accomplish european law requirements about cookie consent
 * @version 2.6.1
 * @link https://github.com/diegolamonica/EUCookieLaw/
 * @author Diego La Monica (diegolamonica) <diego.lamonica@gmail.com>
 * @copyright 2015 Diego La Monica <http://diegolamonica.info>
 * @license http://www.gnu.org/licenses/lgpl-3.0-standalone.html GNU Lesser General Public License
 * @note This program is distributed in the hope that it will be useful - WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

if(defined('EUCOOKIELAW_FORCE_AS_CACHE') || defined('WP_CACHE') && WP_CACHE && (!defined('WP_ADMIN') || defined('WP_ADMIN') && WP_ADMIN !==true)) {

	require_once dirname(__FILE__) . '/eucookielaw-wp.php';
	if(!preg_match('#^EUCookieLaw:[^(]\(WordPress/#', $_SERVER['HTTP_USER_AGENT'] )) {

		global $euc_iniFile;

		if ( ! defined( 'EUCL_CONTENT_DIR' ) ) define('EUCL_CONTENT_DIR', ABSPATH . '/wp-content');

		if ( is_dir( EUCL_CONTENT_DIR . '/plugins/nextgen-gallery' ) &&
		     ( strpos( strtolower( $_SERVER['REQUEST_URI'] ), 'nextgen-attach_to_post' ) !== false )
		) {

			# Has NextGenGallery and is its URL? Then ignoring requests

		} else {

			if ( ! function_exists( 'EUCgetOption' ) ) {
				if ( file_exists( EUCL_CONTENT_DIR . '/cache/eucookielaw.ini' ) ) {

					# error_log("Loading the INI File");
					$euc_iniFile = parse_ini_file( EUCL_CONTENT_DIR . '/cache/eucookielaw.ini' );

				} else {
					$euc_iniFile = array();
				}

				function EUCgetOption( $key, $defaultValue = false ) {
					global $euc_iniFile;

					$value = $defaultValue;
					if ( function_exists( 'get_option' ) ) {
						# error_log("Getting informations from options");
						$value = get_option( $key, $defaultValue );
					} elseif ( isset( $euc_iniFile[ $key ] ) ) {
						# error_log("Getting informations from ini file");
						$value = $euc_iniFile[ $key ];
					}

					return $value;
				}
			}

			$disalloweddDomains = EUCgetOption( EUCookieLaw::OPT_3RDPDOMAINS );
			$lookInTags         = EUCgetOption( EUCookieLaw::OPT_LOOKINTAGS, EUCookieLaw::OPT_DEFAULT_LOOKINTAGS );
			$lookInScripts      = EUCgetOption( EUCookieLaw::OPT_LOOKINSCRIPTS, 'n' );
			$debug              = EUCgetOption( EUCookieLaw::OPT_DEBUG, 'n' );
			$logFile            = EUCgetOption( EUCookieLaw::OPT_LOGFILE, '' );
			$verbosity          = EUCgetOption( EUCookieLaw::OPT_DEBUG_VERBOSITY, '99' );
			$enabled            = EUCgetOption( EUCookieLaw::OPT_ENABLED, 'y' );
			$whitelstCookies    = EUCgetOption( EUCookieLaw::OPT_WHITELIST_COOKIES, '' );

			$title           = EUCgetOption( EUCookieLaw::OPT_TITLE, '' );
			$message         = EUCgetOption( EUCookieLaw::OPT_MESSAGE, '' );
			$agree           = EUCgetOption( EUCookieLaw::OPT_AGREE, '' );
			$disagree        = EUCgetOption( EUCookieLaw::OPT_DISAGREE, '' );
			$fixedOn         = EUCgetOption( EUCookieLaw::OPT_FIXED_ON, 'top' );
			$additionalClass = EUCgetOption( EUCookieLaw::OPT_BANNER_STYLE, '' );

			$engine = EUCgetOption( EUCookieLaw::OPT_ENGINE, 'regexp' );

			$iframeSrc = EUCgetOption( EUCookieLaw::OPT_DEFAULT_IFRAME_SRC, false );
			$scriptSrc = EUCgetOption( EUCookieLaw::OPT_DEFAULT_SCRIPT_SRC, false );
			$imageSrc = EUCgetOption( EUCookieLaw::OPT_DEFAULT_IMAGE_SRC, false );

			$ignoredUrl = EUCgetOption( EUCookieLaw::OPT_UNAPPLY_ON_URL, '');

			if ( ! $iframeSrc ) $iframeSrc = 'about:blank';
			if ( ! $scriptSrc ) $scriptSrc = 'about:blank';

			if ( $logFile !== '' && ! defined( 'EUCOOKIELAW_LOG_FILE' ) ) {
				define( 'EUCOOKIELAW_LOG_FILE', $logFile );
			}
			$url = $_SERVER['REQUEST_URI'];

			$url = preg_replace( '#(\?|&)__eucookielaw=([^&]+)(&(.*))?#', '$1$4', $url );
			$url = preg_replace( '#(\?|&)$#', '', $url );

			$disagreeLink = $url . ( preg_match( '#\?#', $url ) ? '&' : '?' ) . '__eucookielaw=disagree';
			$agreeLink    = $url . ( preg_match( '#\?#', $url ) ? '&' : '?' ) . '__eucookielaw=agree';

			# error_log("Current engine is: " . $engine);

			# error_log(defined('EUCOOKIELAW_USE_DOM')?"EUCOOKIELAW_USE_DOM=".EUCOOKIELAW_USE_DOM:"EUCOOKIELAW_USE_DOM not defined");
			! defined( 'EUCOOKIELAW_USE_DOM' ) && define( 'EUCOOKIELAW_USE_DOM', $engine == 'dom' );
			! defined( 'EUCOOKIELAW_DISALLOWED_DOMAINS' ) && define( 'EUCOOKIELAW_DISALLOWED_DOMAINS', $disalloweddDomains );
			! defined( 'EUCOOKIELAW_LOOK_IN_TAGS' ) && define( 'EUCOOKIELAW_LOOK_IN_TAGS', $lookInTags );
			! defined( 'EUCOOKIELAW_LOOK_IN_SCRIPTS' ) && define( 'EUCOOKIELAW_LOOK_IN_SCRIPTS', $lookInScripts == 'y' );

			! defined( 'EUCOOKIELAW_BANNER_ADDITIONAL_CLASS' ) && define( 'EUCOOKIELAW_BANNER_ADDITIONAL_CLASS', 'fixedon-' . $fixedOn . ( empty( $additionalClass ) ? '' : " $additionalClass" ) );
			! defined( 'EUCOOKIELAW_BANNER_TITLE' ) && define( 'EUCOOKIELAW_BANNER_TITLE', $title );
			! defined( 'EUCOOKIELAW_BANNER_DESCRIPTION' ) && define( 'EUCOOKIELAW_BANNER_DESCRIPTION', $message );
			! defined( 'EUCOOKIELAW_BANNER_AGREE_BUTTON' ) && define( 'EUCOOKIELAW_BANNER_AGREE_BUTTON', $agree );
			! defined( 'EUCOOKIELAW_BANNER_DISAGREE_BUTTON' ) && define( 'EUCOOKIELAW_BANNER_DISAGREE_BUTTON', $disagree );

			! defined( 'EUCOOKIELAW_BANNER_AGREE_LINK' ) && define( 'EUCOOKIELAW_BANNER_AGREE_LINK', $agreeLink );
			! defined( 'EUCOOKIELAW_BANNER_DISAGREE_LINK' ) && define( 'EUCOOKIELAW_BANNER_DISAGREE_LINK', $disagreeLink );

			! defined( 'EUCOOKIELAW_DEBUG' ) && define( 'EUCOOKIELAW_DEBUG', ( $debug !== 'n' ) );
			! defined( 'EUCOOKIELAW_DEBUG_VERBOSITY') && define('EUCOOKIELAW_DEBUG_VERBOSITY', (int)$verbosity);
			! defined( 'EUCOOKIELAW_DISABLED' ) && define( 'EUCOOKIELAW_DISABLED', $enabled !== 'y' );
			! defined( 'EUCOOKIELAW_ALLOWED_COOKIES' ) && define( 'EUCOOKIELAW_ALLOWED_COOKIES', $whitelstCookies );

			! defined( 'EUCOOKIELAW_IFRAME_DEFAULT_SOURCE' ) && define( 'EUCOOKIELAW_IFRAME_DEFAULT_SOURCE', $iframeSrc );
			! defined( 'EUCOOKIELAW_SCRIPT_DEFAULT_SOURCE' ) && define( 'EUCOOKIELAW_SCRIPT_DEFAULT_SOURCE', $scriptSrc );
			! defined( 'EUCOOKIELAW_IMAGE_DEFAULT_SOURCE' ) && define( 'EUCOOKIELAW_IMAGE_DEFAULT_SOURCE', $imageSrc );

			! defined( 'EUCOOKIELAW_IGNORED_URLS') && define( 'EUCOOKIELAW_IGNORED_URLS', $ignoredUrl);

			require_once dirname( __FILE__ ) . '/eucookielaw-header.php';
		}
	}
}