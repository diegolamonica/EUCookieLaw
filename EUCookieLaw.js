/**
 * EUCookieLaw: simple object to accomplish european law requirements about cookie transmission to clients
 * @class EUCookieLaw
 * @version 1.5
 * @link https://github.com/diegolamonica/EUCookieLaw/
 * @author Diego La Monica (diegolamonica) <diego.lamonica@gmail.com>
 * @copyright 2015 Diego La Monica
 * @license http://www.gnu.org/licenses/lgpl-3.0-standalone.html GNU Lesser General Public License
 * @note This program is distributed in the hope that it will be useful - WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */
var EUCookieLaw = (function (doc) {

	var getScrollTop = function (){
		if(typeof pageYOffset != 'undefined'){
			// most browsers except IE before #9
			return pageYOffset;
		}
		else{

			var body = doc.body, //IE 'quirks'
				docEl = doc.documentElement; //IE with doctype
			return (docEl.clientHeight)? docEl: body;
		}
	};

	var buildBanner = function () {
			if(theBannerId!='' && doc.getElementById(theBannerId)){

			}else {

				var theDiv = doc.createElement("div");
				theBannerId = 'eucookielaw-' + parseInt(Math.random() * 200);
				theDiv.setAttribute('id', theBannerId);
				theDiv.className = "eucookielaw-banner fixedon-" + settings.fixOn;
				theDiv.innerHTML =  '<div class="well">' +
				((settings.tag!='')?('<' + settings.tag + ' class="banner-title">' + settings.bannerTitle + '</' + settings.tag + '>'):'') +
				'<p class="banner-message">' + settings.message + '</p>' +
				'<p class="banner-agreement-buttons">' +
				((settings.disagreeLabel!= '') ? '<a href="#" class="disagree-button btn btn-danger" onclick="(new EUCookieLaw()).reject();">' + settings.disagreeLabel + '</a>' : '') +
				'<a href="#" class="agree-button btn btn-primary" onclick="(new EUCookieLaw()).enableCookies();">' + settings.agreeLabel + '</a>'+
				'</p>' +
				'</div>';
				var firstNode = body.childNodes[0];
				body.insertBefore(theDiv, firstNode);
			}
		},
		setProperty = function (object, prop, _set, _get) {
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
		hasCookie = function(){ return /__eucookielaw=true/.test(doc.cookie); },
		applySettings = function(settings) {

			for (var key in defaultSettings) {
				if (settings[key] === undefined) {
					settings[key] = defaultSettings[key];
				}
			}

			return settings;
		},
		userStorage = window.sessionStorage || window.localStorage,
		originalCookie = doc.cookie, // For future use
		scrolled = false,
		instance = null,
		settings,
		didAChoice = false,
		defaultSettings = {
			debug: false,
			agreeOnScroll: false,
			cookieEnabled: false,
			cookieRejected: false,
			showBanner: false,
			bannerTitle: '',
			message: 'La legge europea sulla privacy e la conservazione dei cookie richiede il tuo consenso prima di conservare i cookie. Me lo consenti?',
			disagreeLabel: '',
			agreeLabel: 'Agree',
			reload: false,
			tag: 'h1',
			fixOn: 'top',
			duration: 0,
			path: '/',
			blacklist: [],
			showAgreement: function () {
				if(settings.showBanner) {
					var _showBanner = function () {
						if (body) {
							buildBanner();
						}else{
							setTimeout(_showBanner, 50);
						}
					};
					_showBanner();
				}else {
					didAChoice = true;

					if (!instance.isRejected() && confirm(settings.message)) {
						instance.enableCookies();
					} else {

						settings.cookieRejected = true;

					}
				}
				return instance.isCookieEnabled();
			}
		},
		isOriginal = true,
		theBannerId = '',
		docWriteOverrided = {},
		docWriteContext = undefined,
		body;
	return function EUCookieLaw(options) {

		if (instance instanceof EUCookieLaw) return instance;

		settings = applySettings(options);

		instance = this;

		var onAgree = function(){
			var elements = document.querySelectorAll("[data-eucookielaw-attr]");
			window.selectedElements = elements; // Global var for debug purpose

			for(var elIndex =0; elIndex < elements.length; elIndex++) {

				var element = elements[elIndex],
					theAttribute    = element.getAttribute('data-eucookielaw-attr'),
					theValue        = element.getAttribute('data-eucookielaw-dest'),
					theId           = element.getAttribute('id');

				switch (theAttribute){
					case 'html':
						element.innerHTML = theValue;
						var epn = element.parentNode,
							ecn = element.childNodes;
						while(ecn[0]){
							epn.insertBefore(ecn[0], element);
						}
						epn.removeChild(element);
						break;
					case 'direct':
						theValue = docWriteOverrided[theId];
						element.innerHTML = theValue;
						element.setAttribute('style', '');
						break;
					case 'script':

						docWriteContext = element;
						var script = (element.childNodes[0])?element.childNodes[0].innerHTML:docWriteOverrided[theId];
						try {
							var f = new Function('', script);
							f();
						}catch(e){
							if(settings.debug){
								console.error("Something goes wrong in function execution", f.toString());
							}

						}
						docWriteContext.parentNode.removeChild( docWriteContext );
						docWriteContext = undefined;
					default:
						element.setAttribute(theAttribute, theValue);
				}

			}

		};

		this.enableCookies = function () {
			didAChoice = true;
			userStorage.removeItem('rejected');

			settings.cookieEnabled = true;
			settings.cookieRejected = false;
			if (!isOriginal) {
				delete doc.cookie;
				onAgree();
				isOriginal = true;
			}

			if(settings.duration!=0) {
				var expires = new Date();
				expires.setDate(expires.getDate() + parseInt( settings.duration) );
				if(settings.debug) console.log("Injected cookie expires on: ", expires);
			}

			doc.cookie = "__eucookielaw=true"
						+ ";domain=" + window.location.host
						+ ";path=" + settings.path
						+ ((settings.duration!=0)?";expires=" + expires.toGMTString():'');
			removeBanner();

			if(settings.reload && !settings.agreeOnScroll) window.location.reload();

		};
		this.reject = function () {
			if(!hasCookie()){
				console.log("Calling Reject");
				didAChoice = true;
				userStorage.setItem("rejected", true);
				settings.cookieRejected = true;
				removeBanner();
			}

		};
		this.reconsider = function(){

			userStorage.removeItem('rejected');
			doc.cookie = "__eucookielaw="
				+ ";domain=" + window.location.host
				+ ";path=" + settings.path
				+ ';expires=Thu, 01 Jan 1970 00:00:01 GMT';

			scrolled = false;
			didAChoice = false;
			settings.showAgreement();
		};
		this.isRejected = function () {
			return settings.cookieRejected;
		};
		this.isCookieEnabled = function () {
			return settings.cookieEnabled;
		};

		var removeBanner = function(){
			if(theBannerId!=''){
				body.removeChild( doc.getElementById(theBannerId) );
				theBannerId = '';
			}
		};

		if(settings.showBanner) {

			var waitReady = function () {
				if (document.readyState === 'complete' && doc.body) {
					body = doc.body;
					previousScrollTop = getScrollTop();
					if(!userStorage.getItem('rejected') && !hasCookie()){
						buildBanner();
					} else{
						instance.reject();
					}
				} else {
					setTimeout(waitReady, 100);
				}
			};
			waitReady();

			if (settings.agreeOnScroll){
				previousScrollTop = getScrollTop();
				var evt = document.addEventListener || document.attachEvent ;
				evt('scroll', function () {

					if(!scrolled && body && Math.abs(getScrollTop() - previousScrollTop)>50 && !didAChoice) {
						scrolled = true;
						instance.enableCookies();
						removeBanner();
					}
				});
			}
		}

		if(hasCookie()) return instance;

		isOriginal = false;
		setProperty(doc, 'cookie', function (cookie) {
			if(settings.debug) console.info("Trying to write the cookie " + cookie);
			if (!settings.cookieEnabled) {
				if(settings.debug) console.log("But document cookie is not enabled");
				if (settings.showAgreement()) {
					instance.enableCookies();
					doc.cookie = cookie;
				}
				return false;
			} else {
				if(settings.debug) console.log("I'm resetting the original document cookie");
				delete doc.cookie;
				doc.cooke = cookie;
			}
			return cookie;
		}, function(){
			return ''; //originalCookie;
		});

		var documentWrite = doc.write;

		doc.write = function(buffer) {
			function getUniqueId(){

				var id = '';
				while(true){
					id = '__eucookielaw-document-write-' + (Math.random() * 200);
					if(!document.getElementById(id)) break;

				}
				return id;

			}
			if(!instance.isCookieEnabled()) {
				if(settings.debug) console.log("Cookie not enabled setting protection");
				for (var i = 0; i < settings.blacklist.length; i++) {
					if(settings.debug) console.log(buffer);
					var entry = settings.blacklist[i];
					if (buffer.indexOf(entry) !== -1) {
						if(settings.debug) console.log(entry);
						var id = getUniqueId();
						docWriteOverrided[id] = buffer;
						buffer = '<!-- Removed by EUCookieLaw because matches "' + entry + '" --><span id="' + id + '" data-eucookielaw-attr="direct" data-eucookie-dest="direct" style="display:none;"></span>';
						break;
					}
				}
			}
			if(docWriteContext) {
				var docFrag = doc.createDocumentFragment(),
					docTemp = doc.createElement('div'),
					parentContainer = docWriteContext.parentNode;
				docTemp.innerHTML = buffer;
				while (docTemp.firstChild) docFrag.appendChild(docTemp.firstChild);
				// docFrag.innerHTML = buffer;
				if (docWriteContext.nextSibling) {
					if(settings.debug) console.log("appending after");
					parentContainer.insertBefore(docFrag, docWriteContext.nextSibling);
				} else {
					parentContainer.appendChild(docFrag);
					if(settings.debug) console.log("appending as last");
				}
			}else{
				documentWrite.apply(document,[buffer]);
			}
		};
	};
})(document);