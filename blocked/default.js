/**
 * EUCookieLaw: simple object to accomplish european law requirements about cookie transmission to clients
 * @class EUCookieLaw
 * @version 2.2.0
 * @link https://github.com/diegolamonica/EUCookieLaw/
 * @author Diego La Monica (diegolamonica) <diego.lamonica@gmail.com>
 * @copyright 2015 Diego La Monica
 * @license http://www.gnu.org/licenses/lgpl-3.0-standalone.html GNU Lesser General Public License
 * @note This program is distributed in the hope that it will be useful - WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

/*
 * Script blocked until user consent by EUCookieLaw
 */

function isDescendantOf(parent, child) {
	var node = child.parentNode;
	if( node == null ) return false;
	if (node == parent) return true;
	return isDescendantOf(parent, node);
}

if(isDescendantOf( document.querySelector('body'), document.currentScript)){
	// Only if into the body
	var theSpan = document.createElement('span');
	theSpan.innerHTML = 'This content is restricted until the cookie policy consent';
	theSpan.className = 'eucookielaw-blocked';
	document.currentScript.parentNode.insertBefore(theSpan, document.currentScript);
}