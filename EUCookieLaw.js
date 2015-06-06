MRRLSU73P42L259E
/**
 * EUCookieLaw: simple object to accomplish european law requirements about cookie transmission to clients
 * @class EUCookieLaw
 * @version 1.3.1
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
				var propObject = {
					configurable: true
				};
				if (typeof(_set) == 'function') propObject['set'] = _set;
				if (typeof(_get) == 'function') propObject['get'] = _get;

				Object.defineProperty(object, prop, propObject);
			} else {

				if (typeof(_set) == 'function') object.__defineSetter__(prop, _set);
				if (typeof(_get) == 'function') object.__defineGetter__(prop, _get);

			}
		},
		cookieEnabled = false,
		onScroll = false,
		isOriginal = true,
		instance = null,
		cookieRejected = false,
		askForCookie = null,
		showBanner = false,
		defaultTitle = '',
		disagreeLabel = '',
		agreeLabel = '',
		defaultMessage = '',
		reloadAfterAgree = false,
		theBannerId = '',
		theTitleTag = '', // default is h1
		fixOn = '', // default is 'top'
		body;


	return function EUCookieLaw(options) {
		if (/__eucookielaw=true/.test(doc.cookie)) return instance;
		if (instance instanceof EUCookieLaw) return instance;

		instance = this;

		this.enableCookies = function () {
			cookieEnabled = true;
			cookieRejected = false;
			if (!isOriginal) {
				delete doc.cookie;
				isOriginal = true;
			}
			doc.cookie = "__eucookielaw=true"
						+ ";domain=" + window.location.host + ";path=/";
			removeBanner();

			if(reloadAfterAgree && !onScroll) window.location.reload();

		};
		this.reject = function () {
			cookieRejected = true;
			removeBanner();

		};
		this.isRejected = function () {
			return cookieRejected;
		};
		this.isCookieEnabled = function () {
			return cookieEnabled;
		};

		var buildBanner = function () {
			body = doc.body;
			if(theBannerId!='' && doc.getElementById(theBannerId)){

			}else {

				var theDiv = doc.createElement("div");
				theBannerId = 'eucookielaw-' + parseInt(Math.random() * 200);
				theDiv.setAttribute('id', theBannerId);
				theDiv.className = "eucookielaw-banner fixedon-" + fixOn;
				theDiv.innerHTML =  '<div class="well">' +
					'<' + theTitleTag + ' class="banner-title">' + defaultTitle + '</' + theTitleTag + '>' +
					'<p class="banner-message">' + defaultMessage + '</p>' +
					'<p class="banner-agreement-buttons">' +
						((disagreeLabel!= '') ? '<a href="#" class="disagree-button btn btn-danger" onclick="(new EUCookieLaw()).reject();">' + disagreeLabel + '</a>' : '') +
						'<a href="#" class="agree-button btn btn-primary" onclick="(new EUCookieLaw()).enableCookies();">' + agreeLabel + '</a>'+
					'</p>' +
				'</div>';
				var firstNode = body.childNodes[0];
				body.insertBefore(theDiv, firstNode);
			}
		},
		removeBanner = function(){
			if(theBannerId!=''){
				body.removeChild( doc.getElementById(theBannerId) );
				theBannerId = '';
			}
		};
		theTitleTag = options.tag || 'h1';
		defaultMessage = options.message || 'La legge europea sulla privacy e la conservazione dei cookie richiede il tuo consenso prima di conservare i cookie. Me lo consenti?';
		reloadAfterAgree = options.reload;
		askForCookie = typeof(options.showAgreement) == 'function' ? options.showAgreement : function () {
			if(showBanner) {
				buildBanner();
			}else {
				if (!instance.isRejected() && confirm(options.message)) {
					instance.enableCookies();
				} else {
					cookieRejected = true;
				}
			}
			return instance.isCookieEnabled();
		};

		showBanner = options.showBanner;
		if(showBanner && options.bannerTitle) {
			defaultTitle = options.bannerTitle;
			disagreeLabel = options.disagreeLabel || "";
			agreeLabel = options.agreeLabel || "Agree";
			onScroll = options.agreeOnScroll || false;
			fixOn   = options.fixOn || 'top';

			var waitReady = function () {
				if (document.readyState === 'complete') {
					buildBanner();
				} else {
					setTimeout(waitReady, 100);
				}
			}

			waitReady();
			// window.addEventListener('load', buildBanner);
			if (onScroll){
				document.addEventListener('scroll', function () {
					console.log("Scrolling");
					instance.enableCookies();
				});
			}
		}

		isOriginal = false;
		setProperty(doc, 'cookie', function (cookie) {
			body = doc.body;
			if (!cookieEnabled) {
				if (askForCookie()) {
					instance.enableCookies();
					doc.cookie = cookie;
				}
				return false;
			} else {
				delete doc.cookie;
				doc.cooke = cookie;
			}
			return cookie;
		}, null);
	};
})(document);