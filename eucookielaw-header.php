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
        foreach($headers as $header){
            if(preg_match("#^Set-Cookie:#", $header)) {
                header('Set-Cookie:');
                break;
            }
        }
    }
    return $buffer;
}

ob_start("euCookieLaw_callback",2);