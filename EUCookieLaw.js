/**
 * EUCookieLaw: simple object to accomplish european law requirements about cookie transmission to clients
 * @class EUCookieLaw
 * @version 1.0
 * @link https://github.com/diegolamonica/EUCookieLaw/
 * @author Diego La Monica (diegolamonica) <diego.lamonica@gmail.com>
 * @copyright 2015 Diego La Monica
 * @license http://www.gnu.org/licenses/lgpl-3.0-standalone.html GNU Lesser General Public License
 * @note This program is distributed in the hope that it will be useful - WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */
var EUCookieLaw = (function (doc) {
	var setProperty = function (object, prop, _set, _get) {
			if (typeof(Object.defineProperty) === 'function') {
				Object.defineProperty(object, prop, {
					set: _set,
					get: _get
				});
			} else {

				if (typeof(_set) == 'function') object.__defineSetter__(prop, _set);
				if (typeof(_get) == 'function') object.__defineGetter__(prop, _get);

			}
		},
		cookieEnabled = false,
		isOriginal = true,
		instance = null,
		cookieRejected = false,
		askForCookie = null,
		defaultMessage = '';

	return function EUCookieLaw(options) {
		if (/__eucookielaw=true/.test(document.cookie)) return instance;
		if (instance instanceof EUCookieLaw) return instance;

		instance = this;

		this.enableCookies = function () {
			cookieEnabled = true;
			cookieRejected = false;
			if (!isOriginal) {
				delete document.cookie;
				isOriginal = true;
			}
			document.cookie = "__eucookielaw=true";
		};
		this.reject = function () {
			cookieRejected = true;
		};
		this.isRejected = function () {
			return cookieRejected;
		};
		this.isCookieEnabled = function () {
			return cookieEnabled;
		};
		defaultMessage = options.message || 'La legge europea sulla privacy e la conservazione dei cookie richiede il tuo consenso prima di conservare i cookie. Me lo consenti?';

		askForCookie = typeof(options.showAgreement) == 'function' ? options.showAgreement : function () {
			if (!instance.isRejected() && confirm(options.message)) {
				instance.enableCookies();
			} else {
				cookieRejected = true;
			}
			return instance.isCookieEnabled();
		};

		setProperty(document, 'cookie', function (cookie) {
			if (!cookieEnabled) {
				if (askForCookie()) {
					instance.enableCookies();
					document.cookie = cookie;
				}
				return false;
			} else {
				document.cooke = cookie;
			}
			return cookie;
		}, null);
		isOriginal = false;
	};
})(document);