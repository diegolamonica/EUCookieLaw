<?php
/*
 * Plugin Name: EUCookieLaw
 * Plugin URI: https://github.com/diegolamonica/EUCookieLaw
 * Description: A simple WP solution to the European Cookie Law Issue
 * Author: Diego La Monica
 * Version: 2.6.1
 * Author URI: http://diegolamonica.info
 * Text Domain: EUCookieLaw
 * Domain Path: /languages
*/

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

require_once dirname(__FILE__) . '/eucookielaw-wp.php';
if(!EUCookieLaw::$initialized && !isset($GLOBALS['eucookielaw'])){
	$GLOBALS['eucookielaw'] = new EUCookieLaw();
}