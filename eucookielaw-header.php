<?php
/**
 * EUCookieLaw: simple object to accomplish european law requirements about cookie transmission to client
 * @version 1.5
 * @link https://github.com/diegolamonica/EUCookieLaw/
 * @author Diego La Monica (diegolamonica) <diego.lamonica@gmail.com>
 * @copyright 2015 Diego La Monica
 * @license http://www.gnu.org/licenses/lgpl-3.0-standalone.html GNU Lesser General Public License
 * @note This program is distributed in the hope that it will be useful - WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

function euCookieLaw_callback($buffer){

	$version = '1.5';

	$specialAttriutes = array(
		'form'      => 'action',
		'link'      => 'href',
		'param'     => 'value',
		'*'         => 'src',
	);

	$searchengines = array(
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
	foreach ($searchengines as $searchengine){
		if (!empty($_SERVER['HTTP_USER_AGENT']) and
		    false !== strpos(strtolower($_SERVER['HTTP_USER_AGENT']), strtolower($searchengine)))
		{
			$isSearchEngine = true;
			break;
		}
	}



    if(!isset($_COOKIE['__eucookielaw']) && !$isSearchEngine) {
	    $headers = headers_list();
	    foreach ( $headers as $header ) {
		    if ( preg_match( "#^Set-Cookie:#", $header ) ) {
			    header( 'Set-Cookie:' );
			    break;
		    }
	    }
	    header('Expires: '.gmdate('D, d M Y H:i:s \G\M\T', time() - 3600));

	    if ( preg_match( '#<script\W[^>]*(data-eucookielaw="block")[^>]*>.*?</script>#ms', $buffer, $items ) ) {
		    $buffer = str_replace( $items[0], '', $buffer );

	    }

	    !defined('EUCOOKIELAW_DISALLOWED_DOMAINS')  && define('EUCOOKIELAW_DISALLOWED_DOMAINS', '');
	    !defined('EUCOOKIELAW_LOOK_IN_SCRIPTS')     && define('EUCOOKIELAW_LOOK_IN_SCRIPTS', false);
		!defined('EUCOOKIELAW_DEBUG')               && define('EUCOOKIELAW_DEBUG', false);
	    if(EUCOOKIELAW_DISALLOWED_DOMAINS!='') {

		    ! defined( 'EUCOOKIELAW_LOOK_IN_TAGS' ) && define( 'EUCOOKIELAW_LOOK_IN_TAGS', 'iframe|script|link' );

		    $disallowedDomains = preg_split( "#[;\n]#", EUCOOKIELAW_DISALLOWED_DOMAINS );

		    $tags = explode("|", EUCOOKIELAW_LOOK_IN_TAGS);
		    $expectedAttributes = array();
		    foreach($tags as $tag){
			    if(!isset($specialAttriutes[$tag]) ) $expectedAttributes[] = $specialAttriutes['*'];
		    }
		    $expectedAttributes = implode('|', array_unique($expectedAttributes) );

		    foreach ( $disallowedDomains as $disallowedDomain ) {
			    $disallowedDomain = trim($disallowedDomain);
			    if ( !empty($disallowedDomain) ) {

				    // Non empty tags (eg. <iframe>...</iframe>)

				    if($disallowedDomain[0] == '.'){
					    $domainToScan = '([a-z0-9\-_]{1,63}\.)*' . preg_quote( substr($disallowedDomain, 1), "#" );
				    }else{
					    $domainToScan = preg_quote( $disallowedDomain, "#" );
				    }
				    if(EUCOOKIELAW_DEBUG) $buffer = '<!-- rule: ' . $domainToScan . ' -->'."\n". $buffer;
				    $multiLineTagRegExp = '#<(' . EUCOOKIELAW_LOOK_IN_TAGS . ')\W[^>]*(href|src)=("|\')((http(s)?:)?//' . $domainToScan . '.*?)(\\3)[^>]*>.*?</\\1>#ms';



				    while ( preg_match( $multiLineTagRegExp, $buffer, $items ) ) {

					    $newAttr = ' data-eucookielaw-dest="' . $items[4] . '"';
					    $newAttr .= ' data-eucookielaw-attr="' . $items[2] . '"';

					    $replaced = str_replace($items[4], 'about:blank', $items[0]);


					    $replaced = str_replace('<'.$items[1] , '<'.$items[1]. $newAttr, $replaced);
					    $buffer = str_replace( $items[0], (EUCOOKIELAW_DEBUG?('<!-- (rule: ' . $disallowedDomain . ' - replaced -->'):'') . $replaced, $buffer );

				    }

				    // Empty tags ( eg. <link href="..." />)
				    $singleLineTagRegExp = '#<(' . EUCOOKIELAW_LOOK_IN_TAGS . ')\W[^>]*(href|src)=("|\')((http(s)?:)?//' . $domainToScan . '.*?)("|\').*?>#ms';
				    while ( preg_match( $singleLineTagRegExp, $buffer, $items ) ) {

					    $newAttr = ' data-eucookielaw-dest="' . $items[4] . '"';
					    $newAttr .= ' data-eucookielaw-attr="' . $items[2] . '"';

					    $replaced = str_replace($items[4], 'about:blank', $items[0]);


					    $replaced = str_replace('<'.$items[1] , '<'.$items[1]. $newAttr, $replaced);
					    $buffer = str_replace( $items[0], (EUCOOKIELAW_DEBUG?('<!-- (rule: ' . $disallowedDomain . ' - replaced -->'):'') . $replaced, $buffer );


					    // $buffer = str_replace( $items[0], (EUCOOKIELAW_DEBUG?('<!-- (rule: ' . $disallowedDomain . ' - removed ' . $items[4] . ' -->'):''), $buffer );
				    }

				    if(EUCOOKIELAW_LOOK_IN_SCRIPTS){

					    $pattern = "#<script([^>]*)>(.*?)</script>#ims";
					    if(preg_match_all($pattern, $buffer, $matches)){

						    foreach($matches[2] as $index => $match){

							    if(preg_match('#'. $domainToScan.'#', $match)){

								    $newAttr = ' data-eucookielaw-dest="execute"';
								    $newAttr .= ' data-eucookielaw-attr="script"';

								    $buffer = str_replace($matches[0][$index],
									    (EUCOOKIELAW_DEBUG?('<!-- (rule: ' . $disallowedDomain . ' - suspended -->'):'').
								        '<span '.$newAttr.' style="display: none;"><span '. $matches[1][$index] .'>'. "\n//Removed by EUCookieLaw\n\n" .$match.'</span></span>', $buffer );
							    }
						    }

					    }

				    }
			    }


		    }
	    }

	    if(defined('EUCOOKIELAW_DEBUG') && EUCOOKIELAW_DEBUG) {
		    $buffer = "<!-- (EUCookieLaw Debug Enabled) -->\n" .
		              "<!-- $version -->\n" .
		              "<!-- Searching in the following tags: " . EUCOOKIELAW_LOOK_IN_TAGS . ") -->\n" .
		              "<!-- Searching in the following attributes: " . $expectedAttributes . ") -->\n" .
		              $buffer;
	    }
    }

	return $buffer;
}

ob_start("euCookieLaw_callback");