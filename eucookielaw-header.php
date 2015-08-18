<?php
/**
 * EUCookieLaw: EUCookieLaw a complete solution to accomplish european law requirements about cookie consent
 * @version 2.4.0
 * @link https://github.com/diegolamonica/EUCookieLaw/
 * @author Diego La Monica (diegolamonica) <diego.lamonica@gmail.com>
 * @copyright 2015 Diego La Monica <http://diegolamonica.info>
 * @license http://www.gnu.org/licenses/lgpl-3.0-standalone.html GNU Lesser General Public License
 * @note This program is distributed in the hope that it will be useful - WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

if(!function_exists('gzdecode')) {

	if(file_exists(dirname(__FILE__) . '/gzcompat.php')) {
		require_once(dirname(__FILE__) . '/gzcompat.php');
	}else{

		die("file <code>" . dirname(__FILE__) . '/gzcompat.php' . '</code> not found! Please download <code>gzcompat.php</code> from <a href="https://github.com/diegolamonica/EUCookieLaw/">EUCookieLaw</a>');

	}
}

class EUCookieLawHeader{

	const VERSION = '2.4.0';

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

	private $hasCookie = false;
	private function log( $message, $logOn = null){
		if(is_null($logOn)) $logOn = $this->logOn;
		if(EUCOOKIELAW_DEBUG) {
			switch ( $logOn ) {
				case self::WRITE_ON_FILE:

					if(!defined('EUCOOKIELAW_LOG_FILE')){
						$this->log("EUCOOKIELAW_LOG_FILE constant not defined", self::WRITE_ON_ERROR_LOG);
						$this->log($message, self::WRITE_ON_ERROR_LOG);
					}else{
						if(!file_exists(EUCOOKIELAW_LOG_FILE)) touch(EUCOOKIELAW_LOG_FILE);
						$fh = @fopen(EUCOOKIELAW_LOG_FILE, 'a+');
						if($fh){
							fwrite($fh, '[' . date('Y-m-d H:i:s')  . ' @ ' . $_SERVER['REMOTE_ADDR'] . ']: ' . $message ."\n" );
							fclose($fh);
						}else{
							$this->log("file " . EUCOOKIELAW_LOG_FILE  . " is not writable", self::WRITE_ON_ERROR_LOG);
							$this->log($message, self::WRITE_ON_ERROR_LOG);
						}
					}
					break;

				case self::WRITE_ON_ERROR_LOG:
					error_log( $message );
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
		$this->hasCookie = false;

		$headers = headers_list();
		$this->hasCookie = isset($_COOKIE['__eucookielaw']) && $_COOKIE['__eucookielaw'] == 'true';
		foreach($headers as $header) {

			if ( preg_match( '#set-cookie:\s__eucookielaw=true#i', $header ) ) {
				$this->hasCookie = true;
				break;
			}
			if ( preg_match( '#set-cookie:\s__eucookielaw=rejected#i', $header ) ) {
				$this->isRejected = true;
				break;
			}
		}


		return !($isCron || $isAdmin || $isAjax || $isAppReq || $isXRPC || $this->hasCookie) ;

	}

	public function EUCookieLawHeader( $logType = self::WRITE_ON_ERROR_LOG ) {

		$this->logOn = $logType;

		if(defined('EUCOOKIELAW_STARTED')) return;
		define('EUCOOKIELAW_STARTED', true);

		!defined('EUCOOKIELAW_SEARCHBOT_AS_HUMAN') && define('EUCOOKIELAW_SEARCHBOT_AS_HUMAN', true);

		if(!defined('EUCOOKIELAW_AUTOSTART') || EUCOOKIELAW_AUTOSTART) {
			$this->log("Autostart is enabled");
			$isHuman = EUCOOKIELAW_SEARCHBOT_AS_HUMAN || !$this->isSearchEngine();
			$this->hasCookie = isset( $_COOKIE['__eucookielaw'] ) && $_COOKIE['__eucookielaw'] == 'true';
			if( $isHuman) $this->log("Is detected as human");
			if( !$this->hasCookie) $this->log("Cookie not detected");
			if( $this->canStart()) $this->log("Now I can start");

			if ( $isHuman && !$this->hasCookie && $this->canStart() ) {

				$this->log("Start buffering");
				$this->log($_SERVER['REQUEST_URI']);
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

    private function getDefaultSourceByType($type){
			!defined('EUCOOKIELAW_IFRAME_DEFAULT_SOURCE') && define('EUCOOKIELAW_IFRAME_DEFAULT_SOURCE', 'about:blank');
			!defined('EUCOOKIELAW_SCRIPT_DEFAULT_SOURCE') && define('EUCOOKIELAW_SCRIPT_DEFAULT_SOURCE', 'about:blank');

			$defaultUrl = "about:blank";

        switch( strtolower($type) ){
				case 'iframe':
					$defaultUrl = EUCOOKIELAW_IFRAME_DEFAULT_SOURCE;
					break;
				case 'script':
					$defaultUrl = EUCOOKIELAW_SCRIPT_DEFAULT_SOURCE;
					break;
			}
        return $defaultUrl;
    }

	private function replaceInSource( $regexp, $disallowedDomain, $buffer){
		while ( preg_match( $regexp, $buffer, $items ) ) {

			$newAttr = ' data-eucookielaw-dest="' . $items[4] . '"';
			$newAttr .= ' data-eucookielaw-attr="' . $items[2] . '"';

			!defined('EUCOOKIELAW_IFRAME_DEFAULT_SOURCE') && define('EUCOOKIELAW_IFRAME_DEFAULT_SOURCE', 'about:blank');
			!defined('EUCOOKIELAW_SCRIPT_DEFAULT_SOURCE') && define('EUCOOKIELAW_SCRIPT_DEFAULT_SOURCE', 'about:blank');

			$defaultUrl = $this->getDefaultSourceByType( trim($items[1]) );

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
			$this->log("Header: $header" );

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
			if(function_exists('header_remove')) {
				header_remove();
				header_remove( 'Set-Cookie' );
			}else {
				header( 'Set-Cookie: ' );
			}

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

    private function hasDisallowedURL($url){
        if (EUCOOKIELAW_DISALLOWED_DOMAINS != '') {
            $disallowedDomains = preg_split("#[;\n]#", EUCOOKIELAW_DISALLOWED_DOMAINS);
            foreach($disallowedDomains as $disallowedDomain) {
	            $disallowedDomain = trim($disallowedDomain);
	            # Avoid to threat empty strings (else it would mean everything is blocked)
	            if(!empty($disallowedDomain)) {
		            if ( $disallowedDomain[0] == '.' ) {
			            $domainToScan = '([a-z0-9\-_]{1,63}\.)*' . preg_quote( substr( $disallowedDomain, 1 ), "#" );
		            } else {
			            $domainToScan = preg_quote( $disallowedDomain, "#" );
		            }

		            $pregString ='#\/\/' . $domainToScan . '#';
		            if ( preg_match( $pregString, $url ) ) {

			            return true;
		            }
	            }
            }
        }

        return false;
    }

    private function useDOM( $buffer, $tags, $specialAttriutes, $inScripts ){

        libxml_use_internal_errors(true);
        $doc = new DOMDocument();
        $doc->encoding = 'utf-8';

	    $doc->loadHTML(mb_convert_encoding($buffer, 'HTML-ENTITIES', 'UTF-8'));

	    $this->log( "Buffer size is: " . strlen($buffer));

	    foreach($tags as $tag) {
		    $attribute = isset($specialAttriutes[$tag]) ? $specialAttriutes[$tag] : $specialAttriutes['*'];

            $elements = $doc->getElementsByTagName($tag);
		    $this->log("Found " . $elements->length . ' with query selector ' . $tag.'[' . $attribute . ']');
            foreach($elements as $element){
                # $attribute = isset($specialAttriutes[$tag]) ? $specialAttriutes[$tag] : $specialAttriutes['*'];
                $url = $element->hasAttribute($attribute) ? $element->getAttribute($attribute) : null;
	            if(!is_null($url) ) $this->log("Checking $url");
                if(!empty($url) && $this->hasDisallowedURL($url)){
	                $this->log(">>> Found $url <<<");
	                $newURL = $this->getDefaultSourceByType($tag);
                    $element->setAttribute($attribute, $newURL);
                    $element->setAttribute('data-eucookielaw-dest', $url);
                    $element->setAttribute('data-eucookielaw-attr', $attribute);
                }
            }
        }

        if($inScripts) {
            $this->log("looking in scripts");
            $scripts = $doc->getElementsByTagName('script');

            foreach($scripts as $script){

                $buffer = $script->nodeValue;
	            $this->log("--- SCRIPT CONTENT START --");
	            # $this->log($buffer);
                if( $this->hasDisallowedURL( $buffer ) ){
	                $this->log(">>> Found <<<");
	                $this->log($buffer);
                    $script->nodeValue = ' /* Removed by EUCookieLaw */';
                }
	            $this->log("--- SCRIPT CONTENT END --");

            }

        }


        $buffer = $doc->saveHTML();
        libxml_use_internal_errors(false);
        return $buffer;
    }

	private function logURLs(){
		$disallowedDomains = preg_split("#[;\n]#", EUCOOKIELAW_DISALLOWED_DOMAINS);
		$this->log("Disallowed domains are:");
		foreach($disallowedDomains as $disallowedDomain){
			$this->log( trim($disallowedDomain) );
		}
	}

	public function buffering( $buffer ) {
		$this->log( "Ob level is: " . ob_get_level() );
		$obhandlers = ob_list_handlers();
		foreach( $obhandlers as $hi => $handler){
			$this->log("handler $hi -> $handler");
		}
		if(empty($buffer)) return $buffer;

		! defined( 'EUCOOKIELAW_DISALLOWED_DOMAINS' ) && define( 'EUCOOKIELAW_DISALLOWED_DOMAINS', '' );
		! defined( 'EUCOOKIELAW_LOOK_IN_SCRIPTS' ) && define( 'EUCOOKIELAW_LOOK_IN_SCRIPTS', false );
		! defined( 'EUCOOKIELAW_DEBUG' ) && define( 'EUCOOKIELAW_DEBUG', false );
		! defined( 'EUCOOKIELAW_LOOK_IN_TAGS' ) && define( 'EUCOOKIELAW_LOOK_IN_TAGS', 'script|iframe|img|embed|param' );
		! defined( 'EUCOOKIELAW_ALLOWED_COOKIES' ) && define( 'EUCOOKIELAW_ALLOWED_COOKIES', '' );

		$this->log("buffering contents");



		$specialAttriutes = array(
			'form'  => 'action',
			'link'  => 'href',
			'param' => 'value',
			'*'     => 'src',
		);
		$this->log("Checking headers");
		$headersDetails = $this->checkHeaders();
		$this->log("Headers checked");
		if($this->isGZipped){
			$buffer  =gzdecode( $buffer );
		}

		if(class_exists('finfo')) {
			$this->log("detecting output type by finfo");
			$fi = new finfo( FILEINFO_MIME_ENCODING );
			$finfoType = $fi->buffer( $buffer );
			$this->log( "Resource type is: $finfoType");

			if ( $finfoType == 'binary' ){
				$this->log( $_SERVER['REQUEST_URI'] . ' is a binary resource');
				if($this->isGZipped)
					$buffer = gzencode($buffer);

				return $buffer;
			}
		}
        $tags = explode( "|", EUCOOKIELAW_LOOK_IN_TAGS );
        $disallowedDomains = preg_split("#[;\n]#", EUCOOKIELAW_DISALLOWED_DOMAINS);

        if( class_exists('DOMDocument') && ( !defined('EUCOOKIELAW_USE_DOM') || defined('EUCOOKIELAW_USE_DOM') && EUCOOKIELAW_USE_DOM==true)) {

            $this->log("Processing via DOM");
	        $this->logURLs();
            $buffer = $this->useDOM($buffer, $tags, $specialAttriutes, EUCOOKIELAW_LOOK_IN_SCRIPTS);
            $this->log("Dom Processed");
        } else {

	        $this->log("Processing via RegExp");
	        $this->log( class_exists('DOMDocument') ? "Even if the DOMDocument exists": "Because DOMDocument does not exists" );
	        $this->log(defined('EUCOOKIELAW_USE_DOM') ? "The EUCOOKIELAW_USE_DOM constant is defined and its value is " . var_export(EUCOOKIELAW_USE_DOM, true) : 'The EUCOOKIELAW_USE_DOM constant is not defined' );
	        $this->logURLs();
			# Removing blocked sections
			$buffer = preg_replace("#<!-- EUCookieLaw:start -->(.*?)<!-- EUCookieLaw:end -->#ms", '', $buffer);

			# stripping out comments from HTML


	        $this->log("Starting with replacements");

            $buffer = preg_replace_callback('#<script[^>]*>.*?</script>#ims', array($this, 'removeInlineScripts'), $buffer);
            $buffer = preg_replace_callback("#(\r?\n)*<!--.*?-->(\r?\n)*#ims", array($this, 'preserveComments'), $buffer);
			$buffer = preg_replace_callback('#\{@@EUCOOKIESCRIPT\[(\d+)\]\}#', array($this, 'restoreInlineScripts'), $buffer);

	        $this->log("Removing blocked scripts");
			$buffer = $this->removeBlockedScripts($buffer);

			if ( EUCOOKIELAW_DISALLOWED_DOMAINS != '' ) {


				$this->log("Defining regex rules for URL replacements");
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
		if(!$this->isRejected && !$this->hasCookie ) {
			$buffer = preg_replace( '#(<body[^>]*?>)#ism', '$1' . $htmlTemplate, $buffer );
		}

		if ( EUCOOKIELAW_DEBUG ) {
			$buffer = "<!-- (EUCookieLaw Debug Enabled) -->\n" .
			          "<!-- " . self::VERSION . " -->\n" .
                  (( class_exists('DOMDocument') && ( !defined('EUCOOKIELAW_USE_DOM') || defined('EUCOOKIELAW_USE_DOM') && EUCOOKIELAW_USE_DOM==true)) ? "<!-- Using DOMDocument -->\n": '').
			          $headersDetails . "\n" .
			          "<!-- Processed on " . date('Y-m-d H:i:s') . " -->\n ".
			          "<!-- Searching in the following tags: " . EUCOOKIELAW_LOOK_IN_TAGS . ") -->\n" .
                  (isset($expectedAttributes)?"<!-- Searching in the following attributes: " . $expectedAttributes . ") -->\n":'') .
			          $buffer . "\n<!-- EUCookieLaw End -->";
		}

        if( class_exists('DOMDocument') && ( !defined('EUCOOKIELAW_USE_DOM') || defined('EUCOOKIELAW_USE_DOM') && EUCOOKIELAW_USE_DOM==true)) {

        } else {

		$buffer = preg_replace_callback('#{@@EUCOOKIECOMMENTS\[(\d+)\]}#', array($this, 'restoreComments'), $buffer);
        }

		if($this->isGZipped)
			$buffer = gzencode($buffer);

		return $buffer;
	}
}

!defined('EUCOOKIELAW_DOMAIN') && define('EUCOOKIELAW_DOMAIN', ($_SERVER['REMOTE_ADDR'] == '127.0.0.1')?'': $_SERVER['HTTP_HOST']);

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

	# Fixed Issue #49
	if(!defined('EUCOOKIELAW_BANNER_AGREE_LINK') && !defined('EUCOOKIELAW_BANNER_DISAGREE_LINK')){

		$url = $_SERVER['REQUEST_URI'];

		$url = preg_replace( '#(\?|&)__eucookielaw=([^&]+)(&(.*))?#', '$1$4', $url );
		$url = preg_replace( '#(\?|&)$#', '', $url );

		$disagreeLink = $url . ( preg_match( '#\?#', $url ) ? '&' : '?' ) . '__eucookielaw=disagree';
		$agreeLink    = $url . ( preg_match( '#\?#', $url ) ? '&' : '?' ) . '__eucookielaw=agree';


		! defined( 'EUCOOKIELAW_BANNER_AGREE_LINK' ) && define( 'EUCOOKIELAW_BANNER_AGREE_LINK', $agreeLink );
		! defined( 'EUCOOKIELAW_BANNER_DISAGREE_LINK' ) && define( 'EUCOOKIELAW_BANNER_DISAGREE_LINK', $disagreeLink );

	}


	# new EUCookieLawHeader( EUCookieLawHeader::WRITE_ON_FILE );

	new EUCookieLawHeader( defined('EUCOOKIELAW_LOG_FILE') ? EUCookieLawHeader::WRITE_ON_FILE : EUCookieLawHeader::WRITE_ON_ERROR_LOG);
}