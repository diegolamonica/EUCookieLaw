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

	const LOG_LEVEL_NONE = 0;
	const LOG_LEVEL_NORMAL = 10;
	const LOG_LEVEL_HIGH = 20;
	const LOG_LEVEL_VERBOSE = 99;

	const COMMENT_START         = ' EUCookieLaw:start ';
	const COMMENT_END           = ' EUCookieLaw:end ';

	const REGEXP_COMMENT_START  = '<!-- EUCookieLaw:start -->';
	const REGEXP_COMMENT_END    = '<!-- EUCookieLaw:end -->';

	private $isGZipped = false;
	private $scriptIndex = -1;
	private $commentIndex = -1;
	private $scripts = array();
	private $comments = array();

	private $logOn      = self::WRITE_ON_ERROR_LOG;
	private $logLevel   = self::LOG_LEVEL_VERBOSE;

	private $isRejected = false;

	private $parsedNodes = array();

	private $hasCookie = false;
	private function log( $message, $level = self::LOG_LEVEL_VERBOSE,  $logOn = null){
		if(is_null($logOn)) $logOn = $this->logOn;
		if(EUCOOKIELAW_DEBUG) {
			switch ( $logOn && $level <= $this->logLevel ) {
				case self::WRITE_ON_FILE:

					if(!defined('EUCOOKIELAW_LOG_FILE')){
						$this->log("EUCOOKIELAW_LOG_FILE constant not defined", self::LOG_LEVEL_NORMAL, self::WRITE_ON_ERROR_LOG);
						$this->log($message, self::LOG_LEVEL_NORMAL, self::WRITE_ON_ERROR_LOG);
					}else{
						if(!file_exists(EUCOOKIELAW_LOG_FILE)) touch(EUCOOKIELAW_LOG_FILE);
						$fh = @fopen(EUCOOKIELAW_LOG_FILE, 'a+');
						if($fh){
							fwrite($fh, '[' . date('Y-m-d H:i:s')  . ' @ ' . $_SERVER['REMOTE_ADDR'] . ']: ' . $message ."\n" );
							fclose($fh);
						}else{
							$this->log("file " . EUCOOKIELAW_LOG_FILE  . " is not writable", self::LOG_LEVEL_NORMAL, self::WRITE_ON_ERROR_LOG);
							$this->log($message, self::LOG_LEVEL_NORMAL, self::WRITE_ON_ERROR_LOG);
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

	public function isIgnoredURL(){
		$urls = defined('EUCOOKIELAW_IGNORED_URLS') ? EUCOOKIELAW_IGNORED_URLS : '';

		$this->log("Current URL is: " .$_SERVER['REQUEST_URI'], self::LOG_LEVEL_HIGH );
		$this->log("Ignored url are:", self::LOG_LEVEL_HIGH);
		$this->log( $urls, self::LOG_LEVEL_HIGH );
		$this->log("----", self::LOG_LEVEL_HIGH);
		if($urls !== ''){

			$ru = $_SERVER['REQUEST_URI'];

			$url = explode("\n", $urls);
			foreach($url as $u){

				$u = explode("*", $u);
				foreach($u as $index => $value)
					$u[$index] = preg_quote($value, '/');

				$u = implode(".*", $u);

				if( preg_match("/^" . $u . "$/", $ru)) return true;

			}

		};

		return false;

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

		return !($isCron || $isAdmin || $isAjax || $isAppReq || $isXRPC || $this->hasCookie || $this->isIgnoredURL());

	}

	private function getAttributeFor($tags){

		!is_array($tags) && $tags = array($tags);

		$attributes = array();
		foreach($tags as $tag) {
			$tag = strtolower($tag);
			$attributes[] = (isset($this->specialAttributes[$tag])) ?
				$this->specialAttributes[$tag][0] :
				$this->specialAttributes['*'][0];
		}

		return array_unique($attributes);
	}

	private function getTagInfo($tag){
		$elementSelector = (!isset($this->specialAttributes[$tag]))? '*' : $tag;
		$tagInfo = $this->specialAttributes[$elementSelector];
		return $tagInfo;
	}

	private function getReplacedTagFor($tag){

		$tagInfo = $this->getTagInfo($tag);
		return isset($tagInfo[2]) ? $tagInfo[2] : $tag ;
	}

	private function getElementsByDisplayType($type, $filter = array()){
		$tags = array();

		!is_array($filter) && $filter = array($filter);

		foreach($filter as $element){
			# $elementSelector = (!isset($this->specialAttributes[$element]))? '*':$element;

			$tag = $this->getTagInfo($element); # $this->specialAttributes[$elementSelector];
			if( $tag[1] == $type || $tag[1] == 'any' ){
				$tags[] = $element;
			}
		}

		return $tags;
	}
	public function __construct( $logType = self::WRITE_ON_ERROR_LOG, $logLevel = self::LOG_LEVEL_VERBOSE ) {

		$this->specialAttributes = array(
			'form'  => array('action', 'block', false),
			'link'  => array('href', 'inline', false),
			'param' => array('value', 'inline', false),
			'object'=> array('data', 'block', 'iframe'),
			'iframe'=> array('src', 'block'),
			'script'=> array('src', 'block'),
			'input' => array('src', 'inline', false),
			'input' => array('src', 'inline', 'img'),
			'img'   => array('src', 'inline'),
			'*'     => array('src', 'any', false)
		);
		$this->logOn = $logType;

		$this->logLevel = $logLevel;

		if(defined('EUCOOKIELAW_STARTED')) return;
		define('EUCOOKIELAW_STARTED', true);

		!defined('EUCOOKIELAW_SEARCHBOT_AS_HUMAN') && define('EUCOOKIELAW_SEARCHBOT_AS_HUMAN', true);

		$this->log("Debug Log Level is: " . $this->logLevel, self::LOG_LEVEL_NORMAL);
		if(!defined('EUCOOKIELAW_AUTOSTART') || EUCOOKIELAW_AUTOSTART) {
			$this->log("Autostart is enabled", self::LOG_LEVEL_NORMAL);
			$isHuman = EUCOOKIELAW_SEARCHBOT_AS_HUMAN || !$this->isSearchEngine();
			$this->hasCookie = isset( $_COOKIE['__eucookielaw'] ) && $_COOKIE['__eucookielaw'] == 'true';
			if( $isHuman) $this->log("Is detected as human", self::LOG_LEVEL_NORMAL);
			if( !$this->hasCookie) $this->log("Cookie not detected", self::LOG_LEVEL_NORMAL);
			if( $this->isIgnoredURL()) $this->log("The URL is ignored", self::LOG_LEVEL_NORMAL);
			if( $this->canStart()) $this->log("Now I can start", self::LOG_LEVEL_NORMAL);

			if ( $isHuman && !$this->hasCookie && $this->canStart() ) {

				$this->log("Start buffering", self::LOG_LEVEL_NORMAL);
				$this->log($_SERVER['REQUEST_URI'], self::LOG_LEVEL_HIGH);
				ob_start( array( $this, "buffering" ) );
			}
		}
	}

	private function removeBlockedScripts($buffer){

		if ( preg_match( '#<script\W[^>]*(data-eucookielaw="block")[^>]*>.*?</script>#ms', $buffer, $items ) ) {
			$buffer = str_replace( $items[0], self::REGEXP_COMMENT_START.'$0' . self::REGEXP_COMMENT_END, $buffer );

		}
		return $buffer;
	}

    private function getDefaultSourceByType($type){
		!defined('EUCOOKIELAW_IFRAME_DEFAULT_SOURCE') && define('EUCOOKIELAW_IFRAME_DEFAULT_SOURCE', 'about:blank');
	    !defined('EUCOOKIELAW_SCRIPT_DEFAULT_SOURCE') && define('EUCOOKIELAW_SCRIPT_DEFAULT_SOURCE', 'about:blank');
	    !defined('EUCOOKIELAW_IMAGE_DEFAULT_SOURCE') && define('EUCOOKIELAW_IMAGE_DEFAULT_SOURCE', 'about:blank');

	    $defaultUrl = "about:blank";

        switch( strtolower($type) ){
			case 'iframe':
				$defaultUrl = EUCOOKIELAW_IFRAME_DEFAULT_SOURCE;
				break;
			case 'script':
				$defaultUrl = EUCOOKIELAW_SCRIPT_DEFAULT_SOURCE;
				break;
	        case 'img':
		        $defaultUrl = EUCOOKIELAW_IMAGE_DEFAULT_SOURCE;
		        break;
		}
        return $defaultUrl;
    }
	private function replaceInSource( $regexp, $disallowedDomain, $buffer){
		$this->log($regexp, self::LOG_LEVEL_HIGH);
		$lastIndex = 0;
		while ( preg_match( $regexp, $buffer, $items, PREG_OFFSET_CAPTURE, $lastIndex ) ) {

			$matched = $items[0][0];
			$offset = $items[0][1];
			$tag = $items[1][0];
			$attribute = $items[2][0];
			$expectedAttribute = $this->getAttributeFor($tag);


			if(strtolower($attribute) == strtolower($expectedAttribute[0]) &&
				!preg_match("#^<[^>]data-cookielaw-index=#im", $matched) &&
				!preg_match("#^<[^>]class=\"eucookielaw-replaced-content\"#im", $matched)
			) {

				$this->log("before", self::LOG_LEVEL_VERBOSE);
				$this->log($matched, self::LOG_LEVEL_VERBOSE);

				$replaced = (EUCOOKIELAW_DEBUG ? ('<!-- (rule: ' . $disallowedDomain . ' - replaced) -->') : '') .
					(self::REGEXP_COMMENT_START . str_replace( array(self::REGEXP_COMMENT_START,self::REGEXP_COMMENT_END), '', $matched) . self::REGEXP_COMMENT_END);

				$this->log("after", self::LOG_LEVEL_VERBOSE);
				$this->log($matched, self::LOG_LEVEL_VERBOSE);

				$replacedTag = $this->getReplacedTagFor($tag);

				if($replacedTag){
					$url = $this->getDefaultSourceByType($replacedTag);
					$replaced .= sprintf('<%s class="eucookielaw-replaced-content" src="%s"></%1$s>', $replacedTag, $url);
				}

				$buffer = str_replace($matched, $replaced, $buffer);

				$lastIndex = $offset + strlen($replaced);
			}else{
				$lastIndex = $offset + strlen($matched);
			}

		}
		return $buffer;
	}

	public function checkHeaders(){
		$headers = headers_list();
		$buffer = '';

		$this->log("obtained header list", self::LOG_LEVEL_NORMAL);
		$allowedCookies = explode(",",EUCOOKIELAW_ALLOWED_COOKIES);
		$allowedCookies[] = '__eucookielaw';

		$headersToBeSetted = array();
		$headersChanged = false;

		foreach ( $headers as $header ) {
			$this->log("Header: $header", self::LOG_LEVEL_HIGH );

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

	private function useRegExp( $buffer, $tags, $inScripts ){

		$disallowedDomains = preg_split("#[;\n]#", EUCOOKIELAW_DISALLOWED_DOMAINS);

		# stripping out comments from HTML
		$this->log("Starting with replacements", self::LOG_LEVEL_HIGH);

		$buffer = preg_replace_callback('#<script[^>]*>.*?</script>#ims', array($this, 'removeInlineScripts'), $buffer);
		$buffer = preg_replace_callback("#(\r?\n)*<!--.*?-->(\r?\n)*#ims", array($this, 'preserveComments'), $buffer);
		$buffer = preg_replace_callback('#\{@@EUCOOKIESCRIPT\[(\d+)\]\}#', array($this, 'restoreInlineScripts'), $buffer);

		$this->log("Removing blocked scripts", self::LOG_LEVEL_HIGH);
		$buffer = $this->removeBlockedScripts($buffer);

		if ( EUCOOKIELAW_DISALLOWED_DOMAINS != '' ) {

			$this->log("Defining regex rules for URL replacements", self::LOG_LEVEL_NORMAL);
			# $expectedAttributes = array();
			$blockElements = $this->getElementsByDisplayType('block', $tags);
			$blockAttributes = implode( '|', $this->getAttributeFor($blockElements));

			$inlineElements = $this->getElementsByDisplayType('inline', $tags);
			$inlineAttributes = implode( '|', $this->getAttributeFor($inlineElements));

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

					// Empty tags ( eg. <link href="..." />)
					$this->log("single line replacements", self::LOG_LEVEL_HIGH);
					$singleLineTagRegExp = '#<(' . implode("|", $inlineElements) . ')\W[^>]*('.$inlineAttributes.')=("|\')((http(s)?:)?//' . $domainToScan . '.*?)("|\').*?>#ms';
					$buffer = $this->replaceInSource($singleLineTagRegExp, $disallowedDomain, $buffer);

					$this->log("mulitline replacements", self::LOG_LEVEL_HIGH);
					$multiLineTagRegExp = '#<(' . implode("|",$blockElements) . ')\W[^>]*('.$blockAttributes.')=("|\')((http(s)?:)?//' . $domainToScan . '.*?)(\\3)[^>]*>.*?</\\1>#ms';
					$buffer = $this->replaceInSource($multiLineTagRegExp, $disallowedDomain, $buffer);

					if ( $inScripts ) {

						$pattern = "#\<script(.*?)>(.*?)<\/script>#ims";
						if ( preg_match_all( $pattern, $buffer, $matches ) ) {
							$this->log("URL to scan is: $domainToScan", self::LOG_LEVEL_HIGH);
							foreach ( $matches[2] as $index => $match ) {
								if(!empty($match)) {
									$this->log("looking in script #$index", self::LOG_LEVEL_HIGH);
									if (preg_match('#' . $domainToScan . '#', $match)) {
										$this->log($match, self::LOG_LEVEL_VERBOSE);
										if (!preg_match('#euCookieLawConfig#', $match)) {

											$currentScript = $matches[0][$index];

											$newScript = self::REGEXP_COMMENT_START. $currentScript . self::REGEXP_COMMENT_END;
											$this->log("Script changed in $newScript", self::LOG_LEVEL_VERBOSE);

											$buffer = preg_replace('#'. preg_quote($currentScript,'#') . '#', $newScript, $buffer);
										}
									} else if (EUCOOKIELAW_DEBUG) {
										$this->log("Not matched!", self::LOG_LEVEL_HIGH);
									}
								}
							}

						}

					}
				}

			}
		}
		return $buffer;
	}

	private function insertNodeReplacement(DOMNode $node){

		$tag = $this->getReplacedTagFor($node->tagName);

		$this->log("Has {$node->tagName} a replacement?", self::LOG_LEVEL_VERBOSE);
		if($tag) {
			$this->log("Yes! Using $tag", self::LOG_LEVEL_VERBOSE);
			$element = $node->ownerDocument->createElement($tag);
			$attributes = $this->getAttributeFor($tag);
			$url = $this->getDefaultSourceByType($tag);
			$element->setAttribute( $attributes[0], $url);
			$element->setAttribute('class', 'eucookielaw-replaced-content');
			if($node->nextSibling){
				$node->parentNode->insertBefore( $element, $node->nextSibling);
			}else{

				$node->parentNode->appendChild( $element);

			}
		}else{
			$this->log("Nope!", self::LOG_LEVEL_VERBOSE);
		}

	}

	private function encapsulateInBlockingComments( $element ){
		$doc = $element->ownerDocument;

		$commentStart = $doc->createComment( self::COMMENT_START);
		$commentEnd = $doc->createComment( self::COMMENT_END);

		$element->parentNode->insertBefore( $commentStart, $element);

		if(isset($element->nextSibling)){
			$element->parentNode->insertBefore( $commentEnd, $element->nextSibling);
		}else{
			$element->parentNode->appendChild( $commentEnd);
		}
	}

	private function isChildOfParsedNode(DOMNode $element){

		foreach ($this->parsedNodes as $parsedNode) {
			$node = $element;
			$parsedPath = $parsedNode->getNodePath();
			$this->log("Parsed path: " . $parsedPath, self::LOG_LEVEL_VERBOSE);

			while ($node->parentNode) {
				$node = $node->parentNode;
				$parentPath = $node->getNodePath();
				$this->log("Parsed path: " . $parentPath, self::LOG_LEVEL_VERBOSE);

				if ($parsedPath == $parentPath) {

					return true;
				}
			}
		}

		return false;
	}

	private function useDOM( $buffer, $tags, $inScripts ){

        libxml_use_internal_errors(true);
        $doc = new DOMDocument();
        $doc->encoding = 'utf-8';

	    $doc->loadHTML(mb_convert_encoding($buffer, 'HTML-ENTITIES', 'UTF-8'));

	    $this->log( "Buffer size is: " . strlen($buffer), self::LOG_LEVEL_HIGH);

	    foreach($tags as $tag) {
		    $this->log("Processing $tag", self::LOG_LEVEL_HIGH);
		    $attribute = $this->getAttributeFor($tag);
		    $attribute = $attribute[0];

            $elements = $doc->getElementsByTagName($tag);
		    $this->log("Found " . $elements->length . ' with query selector ' . $tag.'[' . $attribute . ']', self::LOG_LEVEL_HIGH);
            foreach($elements as $element) {

	            $url = $element->hasAttribute($attribute) ? $element->getAttribute($attribute) : null;

	            if (!is_null($url) && !empty($url)) {
		            if (!$this->isChildOfParsedNode($element)) {
			            $this->log($element->tagName . " is not a child", self::LOG_LEVEL_HIGH);
			            $this->log("Checking $url", self::LOG_LEVEL_HIGH);


			            if ($this->hasDisallowedURL($url)) {

				            $this->parsedNodes[] = $element;

				            $this->insertNodeReplacement($element);
				            $this->encapsulateInBlockingComments($element);

				            $this->log(">>> Found $url <<<", self::LOG_LEVEL_VERBOSE);

			            }
		            }
	            }
            }
        }

        if($inScripts) {
            $this->log("looking in scripts", self::LOG_LEVEL_NORMAL);
            $scripts = $doc->getElementsByTagName('script');

            foreach($scripts as $script){

                $buffer = $script->nodeValue;
	            if( $this->hasDisallowedURL( $buffer ) ){
	                $this->log(">>> Found <<<", self::LOG_LEVEL_VERBOSE);
	                $this->insertNodeReplacement( $script );
	                $this->encapsulateInBlockingComments($script);
                }else{
		            $this->log(">>> Not found <<<", self::LOG_LEVEL_VERBOSE);
	            }

            }

        }


        $buffer = $doc->saveHTML();
        libxml_use_internal_errors(false);
        return $buffer;
    }

	private function logURLs(){
		$disallowedDomains = preg_split("#[;\n]#", EUCOOKIELAW_DISALLOWED_DOMAINS);
		$this->log("Disallowed domains are:", self::LOG_LEVEL_HIGH);
		foreach($disallowedDomains as $disallowedDomain){
			$this->log( trim($disallowedDomain), self::LOG_LEVEL_HIGH );
		}
	}

	public function renderElementAsJSON($data){
		static $index = 0;

		$output = '<script type="text/javascript" data-cookielaw-index="'.$index .'">
			EUCookieLawHTMLFragments['.$index.'] = '.json_encode($data[1]).'</script>';
		$index +=1;

		return $output;

	}

	public function buffering( $buffer ) {
		$this->log( "Ob level is: " . ob_get_level(), self::LOG_LEVEL_VERBOSE );
		$obhandlers = ob_list_handlers();
		foreach( $obhandlers as $hi => $handler){
			$this->log("handler $hi -> $handler", self::LOG_LEVEL_VERBOSE);
		}
		if(empty($buffer)) return $buffer;

		! defined( 'EUCOOKIELAW_DISALLOWED_DOMAINS' ) && define( 'EUCOOKIELAW_DISALLOWED_DOMAINS', '' );
		! defined( 'EUCOOKIELAW_LOOK_IN_SCRIPTS' ) && define( 'EUCOOKIELAW_LOOK_IN_SCRIPTS', false );
		! defined( 'EUCOOKIELAW_DEBUG' ) && define( 'EUCOOKIELAW_DEBUG', false );
		! defined( 'EUCOOKIELAW_LOOK_IN_TAGS' ) && define( 'EUCOOKIELAW_LOOK_IN_TAGS', 'script|iframe|img|embed|param' );
		! defined( 'EUCOOKIELAW_ALLOWED_COOKIES' ) && define( 'EUCOOKIELAW_ALLOWED_COOKIES', '' );

		$this->log("Checking headers", self::LOG_LEVEL_HIGH);
		$headersDetails = $this->checkHeaders();
		$this->log("Headers checked", self::LOG_LEVEL_HIGH);
		if($this->isGZipped){
			$buffer  =gzdecode( $buffer );
		}

		if(class_exists('finfo')) {
			$this->log("detecting output type by finfo", self::LOG_LEVEL_HIGH);
			$fi = new finfo( FILEINFO_MIME_ENCODING );
			$finfoType = $fi->buffer( $buffer );
			$this->log( "Resource type is: $finfoType", self::LOG_LEVEL_VERBOSE);

			if ( $finfoType == 'binary' ){
				$this->log( $_SERVER['REQUEST_URI'] . ' is a binary resource', self::LOG_LEVEL_VERBOSE);
				if($this->isGZipped)
					$buffer = gzencode($buffer);

				return $buffer;
			}
		}
        $tags = explode( "|", EUCOOKIELAW_LOOK_IN_TAGS );

		# Removing blocked sections
		$buffer = preg_replace_callback("#" . preg_quote(self::REGEXP_COMMENT_START, '#') . "(.*?)" . preg_quote(self::REGEXP_COMMENT_END, '#') . "#ms", array($this, 'renderElementAsJSON'), $buffer);

		if( class_exists('DOMDocument') && ( !defined('EUCOOKIELAW_USE_DOM') || defined('EUCOOKIELAW_USE_DOM') && EUCOOKIELAW_USE_DOM==true)) {

            $this->log("Processing via DOM", self::LOG_LEVEL_NORMAL);
	        $this->logURLs();
	        $buffer = $this->useDOM($buffer, $tags, EUCOOKIELAW_LOOK_IN_SCRIPTS);
            $this->log("Dom Processed", self::LOG_LEVEL_NORMAL);
        } else {

	        $this->log("Processing via RegExp", self::LOG_LEVEL_NORMAL);
	        $this->log( class_exists('DOMDocument') ? "Even if the DOMDocument exists": "Because DOMDocument does not exists", self::LOG_LEVEL_NORMAL );
	        $this->log(defined('EUCOOKIELAW_USE_DOM') ? "The EUCOOKIELAW_USE_DOM constant is defined and its value is " . var_export(EUCOOKIELAW_USE_DOM, true) : 'The EUCOOKIELAW_USE_DOM constant is not defined', self::LOG_LEVEL_NORMAL );
	        $this->logURLs();
	        $this->log('EUCOOKIELAW_LOOK_IN_SCRIPTS='. var_export(EUCOOKIELAW_LOOK_IN_SCRIPTS, true), self::LOG_LEVEL_HIGH);
	        $buffer = $this->useRegExp($buffer, $tags, EUCOOKIELAW_LOOK_IN_SCRIPTS);

        }

		$buffer = preg_replace_callback("#".preg_quote(self::REGEXP_COMMENT_START, '#')."(.*?)".preg_quote(self::REGEXP_COMMENT_END, '#')."#ms", array($this, 'renderElementAsJSON'), $buffer);

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

	new EUCookieLawHeader(
		defined('EUCOOKIELAW_LOG_FILE') ? EUCookieLawHeader::WRITE_ON_FILE : EUCookieLawHeader::WRITE_ON_ERROR_LOG,
		defined('EUCOOKIELAW_DEBUG_VERBOSITY') ? EUCOOKIELAW_DEBUG_VERBOSITY : EUCookieLawHeader::LOG_LEVEL_VERBOSE
	);
}