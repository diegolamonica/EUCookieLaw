<?php
/**
 * EUCookieLaw: simple object to accomplish european law requirements about cookie transmission to client
 * @version 1.0
 * @link https://github.com/diegolamonica/EUCookieLaw/
 * @author Diego La Monica (diegolamonica) <diego.lamonica@gmail.com>
 * @copyright 2015 Diego La Monica
 * @license http://www.gnu.org/licenses/lgpl-3.0-standalone.html GNU Lesser General Public License
 * @note This program is distributed in the hope that it will be useful - WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

function euCookieLaw_callback($buffer){
    if(!isset($_COOKIE['__eucookielaw'])) {
	    $headers = headers_list();
	    foreach ( $headers as $header ) {
		    if ( preg_match( "#^Set-Cookie:#", $header ) ) {
			    header( 'Set-Cookie:' );
			    break;
		    }
	    }

	    if ( preg_match( '#<script\W[^>]*(data-eucookielaw="block")[^>]*>.*?</script>#ms', $buffer, $items ) ) {
		    $buffer = str_replace( $items[0], '', $buffer );

	    }

	    !defined('EUCOOKIELAW_DISALLOWED_DOMAINS')  && define('EUCOOKIELAW_DISALLOWED_DOMAINS', '');
	    !defined('EUCOOKIELAW_LOOK_IN_SCRIPTS')     && define('EUCOOKIELAW_LOOK_IN_SCRIPTS', false);

	    if(EUCOOKIELAW_DISALLOWED_DOMAINS!='') {

		    ! defined( 'EUCOOKIELAW_LOOK_IN_TAGS' ) && define( 'EUCOOKIELAW_LOOK_IN_TAGS', 'iframe|srcript|link' );

		    $disallowedDomains = preg_split( "#[;\n]#", EUCOOKIELAW_DISALLOWED_DOMAINS );

		    foreach ( $disallowedDomains as $disallowedDomain ) {
			    $disallowedDomain = trim($disallowedDomain);
			    if ( !empty($disallowedDomain) ) {

				    // Non empty tags (eg. <iframe>...</iframe>)
				    $multiLineTagRegExp = '#<(' . EUCOOKIELAW_LOOK_IN_TAGS . ')\W[^>]*(href|src)=("|\')(http(s)?:)?//' . preg_quote( $disallowedDomain, "#" ) . '.*?(\\3)[^>]*>.*?</\\1>#ms';
				    if ( preg_match( $multiLineTagRegExp, $buffer, $items ) ) {
					    $replaced = str_replace($items[4] . '//' . $disallowedDomain, 'about:blank', $items[0]);
					    $buffer = str_replace( $items[0], $replaced, $buffer );

				    }

				    // Empty tags ( eg. <link href="..." />)
				    $singleLineTagRegExp = '#<(' . EUCOOKIELAW_LOOK_IN_TAGS . ')\W[^>]*(href|src)=("|\')(http(s)?:)?//' . preg_quote( $disallowedDomain, "#"  ) . '.*?("|\').*?/>#ms';
				    if ( preg_match( $singleLineTagRegExp, $buffer, $items ) ) {
					    $buffer = str_replace( $items[0], '', $buffer );
				    }

				    if(EUCOOKIELAW_LOOK_IN_SCRIPTS){

					    $pattern = "#<script[^>]*>(.*?)</script>#ims";
					    if(preg_match_all($pattern, $buffer, $matches)){

						    foreach($matches[1] as $index => $match){

							    if(strpos($match, $disallowedDomain)!==false){
								    $buffer = str_replace($match, "<![CDATA[\n//Removed by EUCookieLaw\n]]>", $buffer );
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

ob_start("euCookieLaw_callback");