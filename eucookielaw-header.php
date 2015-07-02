<?php
/**
 * EUCookieLaw: EUCookieLaw a complete solution to accomplish european law requirements about cookie consent
 * @version 2.2.2
 * @link https://github.com/diegolamonica/EUCookieLaw/
 * @author Diego La Monica (diegolamonica) <diego.lamonica@gmail.com>
 * @copyright 2015 Diego La Monica <http://diegolamonica.info>
 * @license http://www.gnu.org/licenses/lgpl-3.0-standalone.html GNU Lesser General Public License
 * @note This program is distributed in the hope that it will be useful - WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */


if(!function_exists('gzdecode')) {
	function gzdecode( $data ) {
		$len = strlen( $data );
		if ( $len < 18 || strcmp( substr( $data, 0, 2 ), "\x1f\x8b" ) ) {
			return null;  // Not GZIP format (See RFC 1952)
		}
		$method = ord( substr( $data, 2, 1 ) );  // Compression method
		$flags  = ord( substr( $data, 3, 1 ) );  // Flags
		if ( $flags & 31 != $flags ) {
			// Reserved bits are set -- NOT ALLOWED by RFC 1952
			return null;
		}
		// NOTE: $mtime may be negative (PHP integer limitations)
		$mtime     = unpack( "V", substr( $data, 4, 4 ) );
		$mtime     = $mtime[1];
		$xfl       = substr( $data, 8, 1 );
		$os        = substr( $data, 8, 1 );
		$headerlen = 10;
		$extralen  = 0;
		$extra     = "";
		if ( $flags & 4 ) {
			// 2-byte length prefixed EXTRA data in header
			if ( $len - $headerlen - 2 < 8 ) {
				return false;    // Invalid format
			}
			$extralen = unpack( "v", substr( $data, 8, 2 ) );
			$extralen = $extralen[1];
			if ( $len - $headerlen - 2 - $extralen < 8 ) {
				return false;    // Invalid format
			}
			$extra = substr( $data, 10, $extralen );
			$headerlen += 2 + $extralen;
		}

		$filenamelen = 0;
		$filename    = "";
		if ( $flags & 8 ) {
			// C-style string file NAME data in header
			if ( $len - $headerlen - 1 < 8 ) {
				return false;    // Invalid format
			}
			$filenamelen = strpos( substr( $data, 8 + $extralen ), chr( 0 ) );
			if ( $filenamelen === false || $len - $headerlen - $filenamelen - 1 < 8 ) {
				return false;    // Invalid format
			}
			$filename = substr( $data, $headerlen, $filenamelen );
			$headerlen += $filenamelen + 1;
		}

		// $commentlen = 0;
		// $comment    = "";
		if ( $flags & 16 ) {
			// C-style string COMMENT data in header
			if ( $len - $headerlen - 1 < 8 ) {
				return false;    // Invalid format
			}
			$commentlen = strpos( substr( $data, 8 + $extralen + $filenamelen ), chr( 0 ) );
			if ( $commentlen === false || $len - $headerlen - $commentlen - 1 < 8 ) {
				return false;    // Invalid header format
			}
			$comment = substr( $data, $headerlen, $commentlen );
			$headerlen += $commentlen + 1;
		}

		// $headercrc = "";
		if ( $flags & 1 ) {
			// 2-bytes (lowest order) of CRC32 on header present
			if ( $len - $headerlen - 2 < 8 ) {
				return false;    // Invalid format
			}
			$calccrc   = crc32( substr( $data, 0, $headerlen ) ) & 0xffff;
			$headercrc = unpack( "v", substr( $data, $headerlen, 2 ) );
			$headercrc = $headercrc[1];
			if ( $headercrc != $calccrc ) {
				return false;    // Bad header CRC
			}
			$headerlen += 2;
		}

		// GZIP FOOTER - These be negative due to PHP's limitations
		$datacrc = unpack( "V", substr( $data, - 8, 4 ) );
		$datacrc = $datacrc[1];
		$isize   = unpack( "V", substr( $data, - 4 ) );
		$isize   = $isize[1];

		// Perform the decompression:
		$bodylen = $len - $headerlen - 8;
		if ( $bodylen < 1 ) {
			// This should never happen - IMPLEMENTATION BUG!
			return null;
		}
		$body = substr( $data, $headerlen, $bodylen );
		$data = "";
		if ( $bodylen > 0 ) {
			switch ( $method ) {
				case 8:
					// Currently the only supported compression method:
					$data = gzinflate( $body );
					break;
				default:
					// Unknown compression method
					return false;
			}
		} else {
			// I'm not sure if zero-byte body content is allowed.
			// Allow it for now...  Do nothing...
		}

		// Verifiy decompressed size and CRC32:
		// NOTE: This may fail with large data sizes depending on how
		//       PHP's integer limitations affect strlen() since $isize
		//       may be negative for large sizes.
		if ( $isize != strlen( $data ) || crc32( $data ) != $datacrc ) {
			// Bad format!  Length or CRC doesn't match!
			return false;
		}

		return $data;
	}
}


class EUCookieLawHeader{

	const VERSION = '2.2.2';

	const WRITE_ON_ERROR_LOG = 0;
	const WRITE_ON_FILE = 1;
	const WRITE_ON_SCREEN = 2;

	private $isGZipped = false;
	private $scriptIndex = -1;
	private $commentIndex = -1;
	private $scripts = array();
	private $comments = array();
	private $logOn = self::WRITE_ON_ERROR_LOG;

	private $isRejected = false;

	private function log( $message ){
		if(EUCOOKIELAW_DEBUG) {
			switch ( $this->logOn ) {
				case self::WRITE_ON_ERROR_LOG:
					error_log( $message );
					break;
				case self::WRITE_ON_FILE:
					/* NOT YET IMPLEMENTED */
					break;
				case self::WRITE_ON_SCREEN:
					/* NOT YET IMPLEMENTED */
					break;
			}
		}
	}

	private function isSearchEngine(){
		$searchengines  = array(
			'Googlebot',
			'Slurp',
			'search.msn.com',
			'nutch',
			'simpy',
			'bot',
			'ASPSeek',
			'crawler',
			'msnbot',
			'Libwww-perl',
			'FAST',
			'Baidu',
		);
		$isSearchEngine = false;
		foreach ( $searchengines as $searchengine ) {
			if ( ! empty( $_SERVER['HTTP_USER_AGENT'] ) and
			     false !== strpos( strtolower( $_SERVER['HTTP_USER_AGENT'] ), strtolower( $searchengine ) )
			) {
				$isSearchEngine = true;
				break;
			}
		}
		return $isSearchEngine;
	}

	private function canStart(){
		$isCron = defined('DOING_CRON') && DOING_CRON;
		$isAdmin = defined('WP_ADMIN');
		$isAjax = defined('DOING_AJAX') && DOING_AJAX;
		$isAppReq = defined('APP_REQUEST');
		$isXRPC = defined('XMLRPC_REQUEST') && XMLRPC_REQUEST;
		$hasCookie = false;

		$headers = headers_list();
		foreach($headers as $header) {

			if ( preg_match( '#set-cookie:\s__eucookielaw=true#i', $header ) ) {
				$hasCookie = true;
				break;
			}
			if ( preg_match( '#set-cookie:\s__eucookielaw=rejected#i', $header ) ) {
				$this->isRejected = true;
				break;
			}
		}


		return !($isCron || $isAdmin || $isAjax || $isAppReq || $isXRPC || $hasCookie) ;

	}

	public function EUCookieLawHeader( $logType = self::WRITE_ON_ERROR_LOG ) {

		$this->logOn = $logType;

		if(defined('EUCOOKIELAW_STARTED')) return;
		define('EUCOOKIELAW_STARTED', true);

		!defined('EUCOOKIELAW_SEARCHBOT_AS_HUMAN') && define('EUCOOKIELAW_SEARCHBOT_AS_HUMAN', true);

		if(!defined('EUCOOKIELAW_AUTOSTART') || EUCOOKIELAW_AUTOSTART) {
			$this->log("Autostart is enabled");
			$isHuman = EUCOOKIELAW_SEARCHBOT_AS_HUMAN || !$this->isSearchEngine();
			$hasCookie = isset( $_COOKIE['__eucookielaw'] ) && $_COOKIE['__eucookielaw'] == 'true';
			if ( $isHuman && !$hasCookie && $this->canStart() ) {

				$this->log("Start buffering");
				ob_start( array( $this, "buffering" ) );
			}
		}
	}

	private function removeBlockedScripts($buffer){

		if ( preg_match( '#<script\W[^>]*(data-eucookielaw="block")[^>]*>.*?</script>#ms', $buffer, $items ) ) {
			$buffer = str_replace( $items[0], '', $buffer );

		}
		return $buffer;
	}

	private function replaceInSource( $regexp, $disallowedDomain, $buffer){
		while ( preg_match( $regexp, $buffer, $items ) ) {

			$newAttr = ' data-eucookielaw-dest="' . $items[4] . '"';
			$newAttr .= ' data-eucookielaw-attr="' . $items[2] . '"';

			!defined('EUCOOKIELAW_IFRAME_DEFAULT_SOURCE') && define('EUCOOKIELAW_IFRAME_DEFAULT_SOURCE', 'about:blank');
			!defined('EUCOOKIELAW_SCRIPT_DEFAULT_SOURCE') && define('EUCOOKIELAW_SCRIPT_DEFAULT_SOURCE', 'about:blank');

			$defaultUrl = "about:blank";

			switch(  trim($items[1]) ){
				case 'iframe':
					$defaultUrl = EUCOOKIELAW_IFRAME_DEFAULT_SOURCE;
					break;
				case 'script':
					$defaultUrl = EUCOOKIELAW_SCRIPT_DEFAULT_SOURCE;
					break;
			}

			$replaced = str_replace( $items[4], $defaultUrl, $items[0] );

			// Firefox issue https://bugzilla.mozilla.org/show_bug.cgi?id=356558
			if(strtolower( trim($items[1]) ) == 'iframe'){
				$newAttr .= 'name="' . date('YmdHis') .'" ';
			}

			$replaced = str_replace( '<' . $items[1], '<' . $items[1] . $newAttr, $replaced );
			$buffer   = str_replace( $items[0], ( EUCOOKIELAW_DEBUG ? ( '<!-- (rule: ' . $disallowedDomain . ' - replaced -->' ) : '' ) . $replaced, $buffer );

		}
		return $buffer;
	}

	public function checkHeaders(){
		$headers = headers_list();
		$buffer = '';

		$this->log("obtained header list");
		$allowedCookies = explode(",",EUCOOKIELAW_ALLOWED_COOKIES);
		$allowedCookies[] = '__eucookielaw';

		$headersToBeSetted = array();
		$headersChanged = false;

		foreach ( $headers as $header ) {
			if(EUCOOKIELAW_DEBUG) error_log("Header: $header" );

			if ( preg_match( "#^Set-Cookie:\\s([^;]+)#", $header, $cookieData ) ) {

				$buffer .= $header ."\n";

				foreach ( $allowedCookies as $allowedCookie ) {
					$buffer .= "\nChecking for $allowedCookie";
					switch ( true ) {
						case ( preg_match( '#^' . preg_quote( $allowedCookie, '#' ) . '=#', $cookieData[1]) ):
						case substr( $allowedCookie, - 1, 1 ) == '*' && ( preg_match( '#^' . preg_quote( substr( $allowedCookie, 0, - 1 ), '#' ) . '[^=]*=#', $cookieData[1] ) ):
							$headersToBeSetted[] = $header;
							$buffer .= " matched!\n";
							$headersChanged = true;
							break 2;
					}
				}
				$buffer .= "\n";
			} else {
				$headersToBeSetted[] = $header;
			}

			if( preg_match("#Content-Encoding: gzip#", $header)){
				$this->isGZipped = true;
				$buffer .="Content is gzipped\n";
			}
		}

		if($headersChanged) {
			# Resetting all headers
			header_remove();
			header_remove( 'Set-Cookie' );
			header( 'Set-Cookie: ' );
			$buffer .= "\n\n -- Output headers --";
			if ( $headersToBeSetted ) {
				foreach ( $headersToBeSetted as $header ) {
					header( $header );
					$buffer .= $header . "\n";
				}
			}
		}
		return EUCOOKIELAW_DEBUG ? "<!--\n $buffer \n-->": '';
	}

	public function removeInlineScripts($matches){
		$this->scriptIndex+=1;
		$this->scripts[$this->scriptIndex] = $matches[0];
		return '{@@EUCOOKIESCRIPT['. $this->scriptIndex . ']}';
	}

	public function restoreInlineScripts($matches){

		return $this->scripts[$matches[1]];
	}

	public function preserveComments($matches){
		$this->commentIndex+=1;
		$this->comments[$this->commentIndex] = $matches[0];
		return '{@@EUCOOKIECOMMENTS['. $this->commentIndex. ']}';
	}

	public function restoreComments($matches){

		return $this->comments[$matches[1]];
	}

	public function buffering( $buffer ) {

		! defined( 'EUCOOKIELAW_DISALLOWED_DOMAINS' ) && define( 'EUCOOKIELAW_DISALLOWED_DOMAINS', '' );
		! defined( 'EUCOOKIELAW_LOOK_IN_SCRIPTS' ) && define( 'EUCOOKIELAW_LOOK_IN_SCRIPTS', false );
		! defined( 'EUCOOKIELAW_DEBUG' ) && define( 'EUCOOKIELAW_DEBUG', false );
		! defined( 'EUCOOKIELAW_LOOK_IN_TAGS' ) && define( 'EUCOOKIELAW_LOOK_IN_TAGS', 'script|iframe|img|embed|param' );
		! defined( 'EUCOOKIELAW_ALLOWED_COOKIES' ) && define( 'EUCOOKIELAW_ALLOWED_COOKIES', '' );

		if(EUCOOKIELAW_DEBUG) error_log("buffering contents");

		$specialAttriutes = array(
			'form'  => 'action',
			'link'  => 'href',
			'param' => 'value',
			'*'     => 'src',
		);
		if(EUCOOKIELAW_DEBUG) error_log("Checking headers");
		$headersDetails = $this->checkHeaders();
		if(EUCOOKIELAW_DEBUG) error_log("Headers checked");
		if($this->isGZipped){
			$buffer  =gzdecode( $buffer );
		}

		# Removing blocked sections
		$buffer = preg_replace("#<!-- EUCookieLaw:start -->(.*?)<!-- EUCookieLaw:end -->#ms", '', $buffer);

		# stripping out comments from HTML


		$buffer = preg_replace_callback('#<script(.*?)>([^<].+?)</script>#ims', array($this, 'removeInlineScripts'), $buffer);
		$buffer = preg_replace_callback("#(\r?\n)*<!--.*?-->(\r?\n)*#ims", array($this, 'preserveComments'), $buffer);
		$buffer = preg_replace_callback('#\{@@EUCOOKIESCRIPT\[(\d+)\]\}#', array($this, 'restoreInlineScripts'), $buffer);

		$buffer = $this->removeBlockedScripts($buffer);

		if ( EUCOOKIELAW_DISALLOWED_DOMAINS != '' ) {

			$disallowedDomains = preg_split( "#[;\n]#", EUCOOKIELAW_DISALLOWED_DOMAINS );

			$tags               = explode( "|", EUCOOKIELAW_LOOK_IN_TAGS );
			$expectedAttributes = array();
			foreach ( $tags as $tag ) {
				if ( ! isset( $specialAttriutes[ $tag ] ) ) {
					$expectedAttributes[] = $specialAttriutes['*'];
				}
			}
			$expectedAttributes = implode( '|', array_unique( $expectedAttributes ) );

			foreach ( $disallowedDomains as $disallowedDomain ) {
				$disallowedDomain = trim( $disallowedDomain );
				if ( ! empty( $disallowedDomain ) ) {

					// Non empty tags (eg. <iframe>...</iframe>)

					if ( $disallowedDomain[0] == '.' ) {
						$domainToScan = '([a-z0-9\-_]{1,63}\.)*' . preg_quote( substr( $disallowedDomain, 1 ), "#" );
					} else {
						$domainToScan = preg_quote( $disallowedDomain, "#" );
					}
					if ( EUCOOKIELAW_DEBUG ) {
						$buffer = '<!-- rule: ' . $domainToScan . ' -->' . "\n" . $buffer;
					}
					$multiLineTagRegExp = '#<(' . EUCOOKIELAW_LOOK_IN_TAGS . ')\W[^>]*('.$expectedAttributes.')=("|\')((http(s)?:)?//' . $domainToScan . '.*?)(\\3)[^>]*>.*?</\\1>#ms';

					$buffer = $this->replaceInSource($multiLineTagRegExp, $disallowedDomain, $buffer);

					// Empty tags ( eg. <link href="..." />)
					$singleLineTagRegExp = '#<(' . EUCOOKIELAW_LOOK_IN_TAGS . ')\W[^>]*('.$expectedAttributes.')=("|\')((http(s)?:)?//' . $domainToScan . '.*?)("|\').*?>#ms';
					$buffer = $this->replaceInSource($singleLineTagRegExp, $disallowedDomain, $buffer);

					if ( EUCOOKIELAW_LOOK_IN_SCRIPTS ) {

						$pattern = "#\<script(.*?)>(.+?)<\/script>#ims";
						if ( preg_match_all( $pattern, $buffer, $matches ) ) {



							foreach ( $matches[2] as $index => $match ) {

								if ( preg_match( '#' . $domainToScan . '#', $match ) ) {
									if(!preg_match('#euCookieLawConfig#', $match)) {
										$newAttr = ' data-eucookielaw-dest="execute"';
										$newAttr .= ' data-eucookielaw-attr="script"';


										$buffer = str_replace( $matches[0][ $index ],
											( EUCOOKIELAW_DEBUG ? ( '<!-- (rule: ' . $disallowedDomain . ' - suspended -->' ) : '' ) .
											'<span ' . $newAttr . ' style="display: none;"><span ' . $matches[1][ $index ] . '>' . "\n//Removed by EUCookieLaw\n\n" . $match . '</span></span>', $buffer );
									}
								}else if(EUCOOKIELAW_DEBUG){
									$buffer = str_replace( $matches[0][ $index ],
										'<!-- (rule: ' . $disallowedDomain . ' - not matched -->' .  $matches[0][ $index ],
										$buffer
									);
								}
							}

						}

					}
				}

			}

			$theTitle = EUCOOKIELAW_BANNER_TITLE;
			$theMessage = EUCOOKIELAW_BANNER_DESCRIPTION;
			$agree = EUCOOKIELAW_BANNER_AGREE_BUTTON;
			$additionalClass = EUCOOKIELAW_BANNER_ADDITIONAL_CLASS;
			$agreeLink = EUCOOKIELAW_BANNER_AGREE_LINK;
			if(defined('EUCOOKIELAW_BANNER_DISAGREE_BUTTON') && EUCOOKIELAW_BANNER_DISAGREE_BUTTON!='') {
				$disagree = EUCOOKIELAW_BANNER_DISAGREE_BUTTON;
				$disagreeLink = EUCOOKIELAW_BANNER_DISAGREE_LINK;
				$disagreeHTML = "<a href=\"$disagreeLink\" class=\"disagree-button btn btn-danger\" onclick=\"(new EUCookieLaw()).reject(); return false;\">$disagree</a>";
			 }else{
				$disagreeHTML = "";
			}
			$htmlTemplate = <<<EOT
			<div class="eucookielaw-banner $additionalClass" id="eucookielaw-in-html">
				<div class="well">
					<h1 class="banner-title">$theTitle</h1>
					<p class="banner-message">$theMessage</p>
					<p class="banner-agreement-buttons text-right">
						$disagreeHTML
						<a href="$agreeLink" class="agree-button btn btn-primary" onclick="(new EUCookieLaw()).enableCookies(); return false;">$agree</a>
					</p>
				</div>
			</div>
EOT;
			if(!$this->isRejected) {
				$buffer = preg_replace( '#(<body[^>]*?>)#ism', '$1' . $htmlTemplate, $buffer );
			}

			if ( EUCOOKIELAW_DEBUG ) {
				$buffer = "<!-- (EUCookieLaw Debug Enabled) -->\n" .
				          "<!-- " . self::VERSION . " -->\n" .
				          $headersDetails . "\n" .
				          "<!-- Processed on " . date('Y-m-d H:i:s') . " -->\n ".
				          "<!-- Searching in the following tags: " . EUCOOKIELAW_LOOK_IN_TAGS . ") -->\n" .
				          "<!-- Searching in the following attributes: " . $expectedAttributes . ") -->\n" .
				          $buffer . "\n<!-- EUCookieLaw End -->";
			}
		}

		$buffer = preg_replace_callback('#{@@EUCOOKIECOMMENTS\[(\d+)\]}#', array($this, 'restoreComments'), $buffer);

		if($this->isGZipped)
			$buffer = gzencode($buffer);

		return $buffer;
	}
}

!defined('EUCOOKIELAW_DOMAIN') && define('EUCOOKIELAW_DOMAIN', $_SERVER['HTTP_HOST']);

if(isset($_GET['__eucookielaw'])){
	switch($_GET['__eucookielaw']){
		case 'agree':
			setcookie('__eucookielaw','true', time()+31556926, '/', EUCOOKIELAW_DOMAIN );
			break;
		case 'disagree':
			setcookie('__eucookielaw','rejected', null, '/', EUCOOKIELAW_DOMAIN );
			break;
		case 'reconsider':
			setcookie('__eucookielaw', null, time()-31556926, '/', EUCOOKIELAW_DOMAIN);
			unset($_COOKIE['__eucookielaw']);
			break;
	}
}

if(!defined('EUCOOKIELAW_DISABLED') || defined('EUCOOKIELAW_DISABLED') && !EUCOOKIELAW_DISABLED) {
	new EUCookieLawHeader();
}