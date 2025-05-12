/**
 * EUCookieLaw: simple object to accomplish european law requirements about cookie transmission to clients
 * @class EUCookieLaw
 * @link https://github.com/diegolamonica/EUCookieLaw/
 * @author Diego La Monica (diegolamonica) <diego.lamonica@gmail.com>
 * @copyright 2015 Diego La Monica
 * @license http://www.gnu.org/licenses/lgpl-3.0-standalone.html GNU Lesser General Public License
 * @note This program is distributed in the hope that it will be useful - WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

if (!window.Node){
	var Node =
	{
		ELEMENT_NODE                :  1,
		ATTRIBUTE_NODE              :  2,
		TEXT_NODE                   :  3,
		CDATA_SECTION_NODE          :  4,
		ENTITY_REFERENCE_NODE       :  5,
		ENTITY_NODE                 :  6,
		PROCESSING_INSTRUCTION_NODE :  7,
		COMMENT_NODE                :  8,
		DOCUMENT_NODE               :  9,
		DOCUMENT_TYPE_NODE          : 10,
		DOCUMENT_FRAGMENT_NODE      : 11,
		NOTATION_NODE               : 12
	};
}
var EUCOOKIELAW_VERSION = '2.7.0.1';
var EUCookieLaw = (function (doc) {


	var firstLanguage = false,
		getScrollTop = function (){
			if(typeof pageYOffset != 'undefined'){
				// most browsers except IE before #9
				return pageYOffset;
			}
			else{
				if(window.scrollY) return window.scrollY;

				var body = doc.body, //IE 'quirks'
					docEl = doc.documentElement; //IE with doctype
				return (docEl)? docEl.clientHeight: body.clientHeight;
			}
		};

	var removeHTMLBanner = function () {
			var theHTMLBaner = doc.querySelector('#eucookielaw-in-html');
			if(theBannerId!= 'eucookielaw-in-html' && theHTMLBaner){
				theHTMLBaner.parentNode.removeChild(theHTMLBaner);
			}
		},
		buildLanguageSwitcher = function(){
			var langSwitcher = '';

			if(settings.languages && typeof(settings.languages) === 'object') {
				var keys = Object.keys(settings.languages);
				if (keys.length > 1) {
					langSwitcher = '<ul id="eucookielaw-language-switcher">';
					for (var lang in settings.languages) {
						if (!firstLanguage) firstLanguage = lang;
						var htmlLang = lang.replace(/</g, '&lt;').replace(/"/g, '&quot;');
						langSwitcher += '<li onclick="(new EUCookieLaw()).switchLanguage(\'' + htmlLang + '\'); return false;">' + htmlLang + '</li>';
					}
					langSwitcher += '</ul>';
				}else{
					firstLanguage = keys[0];
				}
			}
			return langSwitcher;
		},
		buildBanner = function () {
			removeHTMLBanner();
			if(theBannerId!='' && doc.querySelector('#'+ theBannerId)){

			}else {

				var theDiv = doc.createElement("div");
				theBannerId = 'eucookielaw-' + parseInt(Math.random() * 200);
				theDiv.setAttribute('id', theBannerId);
				theDiv.className = "eucookielaw-banner fixedon-" + settings.fixOn;
				if(settings.classes != ''){
					theDiv.className += " " + settings.classes;
				}
				theDiv.innerHTML =  '<div class="well">' +
				((settings.tag!='')?('<' + settings.tag + ' class="banner-title">' + '</' + settings.tag + '>'):'') +
				'<div class="banner-message">' + '</div>' +
				buildLanguageSwitcher() +
				'<p class="banner-agreement-buttons">' +
				((settings.languages[firstLanguage].disagreeLabel!= '') ? '<a href="#" class="disagree-button btn btn-danger" onclick="(new EUCookieLaw()).reject(); return false;">' + '</a>' : '') +
				'<a href="#" class="agree-button btn btn-primary" onclick="(new EUCookieLaw()).enableCookies(); return false;">' + '</a>'+
				'</p>' +
				'</div>';
				var firstNode = body.childNodes[0];
				body.insertBefore(theDiv, firstNode);
				if(firstLanguage) (new EUCookieLaw).switchLanguage(firstLanguage);
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
		hasRejectedCookie = function(){ return /__eucookielaw=rejected/.test(doc.cookie.toString()); },
		hasCookie = function(){ return /__eucookielaw=/.test(doc.cookie.toString()) && !hasRejectedCookie() },
		applySettings = function(settings) {
			if(settings.languages && settings.languages['']) delete settings.languages[''];
			if(settings.languages && typeof(settings.languages) === 'object' && Object.keys(settings.languages).length>1){
				// I will remove the default language from defaults settings, because it's managed
				console.log("Rimuovo la lingua predefinita");
				delete defaultSettings.languages.Default;

			}else{
				/*
				 * Deprecated items will replace default settings for now.
				 */
				if(settings.languages === undefined) settings.languages = {};
				if(Object.keys( settings.languages).length == 0 ) {

					settings.languages.Default = {
						title: settings.bannerTitle,
						message: settings.message,
						agreeLabel: settings.agreeLabel,
						disagreeLabel: settings.disagreeLabel
					};
					firstLanguage = 'Default';
				}else if(settings.languages.Default === undefined && Object.keys( settings.languages).length == 1){
					/*
					 * If it is just one language defined, then it will put into the "Default" object
					 */

					var lonelyKey = Object.keys( settings.languages);

					settings.languages.Default = settings.languages[lonelyKey];
					delete settings.languages[lonelyKey];
					firstLanguage = 'Default';
				}

			}

			for (var key in defaultSettings) {
				if (settings[key] === undefined) {
					settings[key] = defaultSettings[key];
				}
			}
			if(typeof(settings.cookieList) =='string'){
				settings.cookieList = settings.cookieList.split(',');
			}
			return settings;
		},
		originalCookie = doc.cookie, // For future use
		scrolled = false,
		instance = null,
		settings,
		didAChoice = hasCookie() || hasRejectedCookie(), // userStorage.getItem('rejected'),
		defaultSettings = {
			id: false,
			debug: false,
			agreeOnScroll: false,
			agreeOnClick: false,
			cookieEnabled: false,
			cookieRejected: false,
			showBanner: false,
			isAllowedForUserRoles: false,
			/**
			 * @deprecated
			 */
			bannerTitle: '',
			/**
			 * @deprecated
			 */
			message: 'La legge europea sulla privacy e la conservazione dei cookie richiede il tuo consenso prima di conservare i cookie. Me lo consenti?',
			/**
			 * @deprecated
			 */
			disagreeLabel: '',
			/**
			 * @deprecated
			 */
			agreeLabel: 'Agree',
			languages: {
				'Default': {
					'title':  "",
					'message':      'La legge europea sulla privacy e la conservazione dei cookie richiede il tuo consenso prima di conservare i cookie. Me lo consenti?',
					'agreeLabel':   "Sono d'accordo",
					'disagreeLabel':""
				}
			},
			raiseLoadEvent: true,
			reload: false,
			tag: 'h1',
			fixOn: 'top',
			classes: '',
			duration: 0,
			minScroll: 100,
			remember: false,
			path: '/',
			domain: window.location.host,
			cookieList: [],
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
						settings.cookieEnabled = false;

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

		if (instance != null) return instance;

		settings = applySettings(options);
		if(settings.id) theBannerId = settings.id;

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
						var script = (element.childNodes[0])?element.childNodes[0].innerHTML:docWriteOverrided[theId],
							f = new Function('', script);
						try {
							f();
						}catch(e){
							if (settings.debug) console.error("Something goes wrong in function execution", f.toString());
						}
						docWriteContext.parentNode.removeChild( docWriteContext );
						docWriteContext = undefined;
						break;
					default:
						element.setAttribute(theAttribute, theValue);
				}

			}

		};

		var writeInternalCookie = function( value, expires ){
			expires = (expires === undefined) ? '' : (';expires=' + expires);
			doc.cookie = "__eucookielaw=" + value
						+ (settings.domain?(";domain=" + settings.domain):'')
						+ ";path=" + settings.path
						+ expires;
		};

		this.enableCookies = function () {
			didAChoice = true;
			writeInternalCookie('', 'Thu, 01 Jan 1970 00:00:01 GMT');
			settings.cookieEnabled = true;
			settings.cookieRejected = false;
			if (!isOriginal) {
				delete doc.cookie;
				if(!settings.reload) onAgree();
				isOriginal = true;
			}

			var expiresCookie = '';
			if(settings.duration!=0) {
				var expires = new Date();
				expires.setDate(expires.getDate() + parseInt( settings.duration) );
				if(settings.debug) console.log("Injected cookie expires on: ", expires);
				expiresCookie = ";expires=" + expires.toGMTString();
			}
			writeInternalCookie('true', expiresCookie);
			removeBanner();

			if(settings.reload) window.location.reload(true);

		};
		this.reject = function () {
			if(!hasCookie()){
				if(settings.debug) console.log("Calling Reject");
				didAChoice = true;
				if(settings.remember) writeInternalCookie('rejected');

				settings.cookieRejected = true;
				settings.cookieEnabled = false;
				removeBanner();
			}
		};

		function _switchLanguageElement( selector, html ){
			var theElement = document.querySelector('#' +theBannerId + ' ' + selector);
			if(theElement) theElement.innerHTML = html;
		}

		this.switchLanguage = function( lang ) {

			var info = settings.languages[lang];
			if (info) {
				_switchLanguageElement('.banner-title',     info.title);
				_switchLanguageElement('.banner-message',   info.message);
				_switchLanguageElement('.agree-button',     info.agreeLabel);
				_switchLanguageElement('.disagree-button',  info.disagreeLabel);
			}
		};

		this.reconsider = function(){

			writeInternalCookie('', 'Thu, 01 Jan 1970 00:00:01 GMT');

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
			removeHTMLBanner();
			if(theBannerId!=''){
				var theBanner = doc.getElementById(theBannerId);
				if(theBanner) theBanner.parentNode.removeChild(theBanner);
				theBannerId = '';
			}
			if(!settings.cookieRejected) {
				var scripts = document.querySelectorAll('script[data-cookielaw-index]');
				for (var i = 0; i < scripts.length; i++) {
					var script = scripts[i],
						idx = script.getAttribute('data-cookielaw-index'),
						next = script.nextSibling;
					if (next && next.className == 'eucookielaw-replaced-content') next.parentNode.removeChild(next);
					eucookieLawWriteHTML(script, idx);
				}
				var event = document.createEvent('Event');
				if(settings.raiseLoadEvent) {
					event.initEvent('load', false, false);
					window.dispatchEvent(event);
				}
			}

		};

		if(settings.showBanner) {

			var previousScrollTop = 0;

			var waitReady = function () {
				if (settings.debug) console.log(doc.readyState);
				if ((doc.readyState === 'complete' || doc.readyState === 'interactive') && doc.body) {
					body = doc.body;
					previousScrollTop = getScrollTop();
					if(!hasRejectedCookie() && !hasCookie()){
						buildBanner();
					} else{
						if(hasRejectedCookie()){
							instance.reject();
						}
						removeBanner();
					}

				} else {
					setTimeout(waitReady, 100);
				}

			};
			waitReady();

			var evt = function(object, eventType, callback){
				var eventAttacher = object.addEventListener || object.attachEvent ;
				if(eventAttacher){
					eventAttacher(eventType, callback);
				}
			};

			if (settings.agreeOnScroll){
				previousScrollTop = getScrollTop();
				evt(window, 'scroll', function () {
					window.title = 'scrolled? ' + (new Date().getMilliseconds());
					if(!scrolled && body && Math.abs(getScrollTop() - previousScrollTop)>settings.minScroll && !didAChoice) {
						scrolled = true;
						instance.enableCookies();
						removeBanner();
					}
				});
			}

			if (settings.agreeOnClick){

				function isDescendantOf(parent, child) {
					var node = child.parentNode;
					if( node == null ) return false;
					if (node == parent) return true;
					return isDescendantOf(parent, node);
				}

				evt(window, 'click', function (event) {
					if(theBannerId=='') return;
					if( document.querySelector('#'+theBannerId) ) {
						if (!isDescendantOf(document.querySelector('#' + theBannerId), event.target)
						&& !/eucookielaw-reconsider-button/.test(event.target.className)) {
							instance.enableCookies();
							removeBanner();
						}
					}
				});
			}
		}

		if(/__eucookielaw=rejected/.test(doc.cookie)){
			this.reject();
		}


		if(hasCookie()) return instance;

		isOriginal = false;
		var blockCookie = function() {
			setProperty(doc, 'cookie', function (cookie) {

				if (settings.debug) console.info("Trying to write the cookie " + cookie);

				if (!settings.cookieEnabled) {

					if (settings.debug) console.log("But document cookie is not enabled");

					var cookiePart = cookie.split('='),
						cookieAllowed = false;

					if(/^__eucookielaw$/.test(cookiePart[0]) ){
						if (settings.debug) console.info("Is the technical cookie");
						cookieAllowed = true;
					}else {
						if (settings.debug) console.log("Checking in cookie list");
						for (var cookieIndex in settings.cookieList) {

							var cookieKey = settings.cookieList[cookieIndex],
								lastChar = cookieKey.substr(-1),
								regexString = "^" +
									((lastChar == '*') ?
										(cookieKey.substr(0, cookieKey.length - 1) + '.*') :
										cookieKey) +
									"$",
								regexCookie = new RegExp(regexString);
							if (settings.debug) console.log("Checking if the cookie '" + cookiePart[0] + "' matches the value defined in " + cookieKey + " (rule: " + regexString + ")");
							if (regexCookie.test(cookiePart[0])) {
								cookieAllowed = true;
								break;
							}
						}
					}
					if(cookieAllowed){
						if (settings.debug) console.log("The cookie " + cookiePart[0] + ' is allowed');
						delete doc.cookie;
						doc.cookie = cookie;
						if (settings.debug) console.info(doc.cookie);
						blockCookie();
					} else {
						if (settings.debug) console.log("The cookie " + cookiePart[0] + ' is not allowed');
						if (settings.showAgreement()) {
							instance.enableCookies();
							doc.cookie = cookie;
						}
					}
					return false;
				} else {
					if (settings.debug) console.warn("I'm resetting the original document cookie");
					delete doc.cookie;
					doc.cookie = cookie;
				}
				return cookie;
			}, function () {
				return originalCookie;
			});
		};
		if(!instance.isCookieEnabled()) blockCookie();
		var documentWrite = doc.write;

		doc.write = function(buffer) {
			function getUniqueId(){

				while(true){
					var id = '__eucookielaw-document-write-' + (Math.random() * 200);
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
			}else{

				docWriteContext = document.currentScript;

			}
			if(docWriteContext) {
				var docFrag = doc.createDocumentFragment(),
					docTemp = doc.createElement('div'),
					parentContainer = docWriteContext.parentNode;

				while(parentContainer.tagName.toLowerCase() == 'script'){
					parentContainer = parentContainer.parentNode;
				}

				docTemp.innerHTML = buffer;
				while (docTemp.firstChild) docFrag.appendChild(docTemp.firstChild);

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
		if(options.isAllowedForUserRoles) {
			return this.enableCookies();
		}
	};
})(document);

var EUCookieLawHTMLFragments = EUCookieLawHTMLFragments || [];
function eucookieLawWriteHTML(context, index){

	var docFrag = document.createDocumentFragment(),
		div = document.createElement('div'),
		parent = context.parentElement;

	div.innerHTML = EUCookieLawHTMLFragments[index];
	docFrag.appendChild(div);

	while( div.childNodes.length > 0){
		var currentNode = div.childNodes[0];
		if(currentNode.nodeType == Node.ELEMENT_NODE && currentNode.tagName.toString().toLowerCase() == 'script'){

			var async = currentNode.getAttribute('async'),
				defer = currentNode.getAttribute('defer'),
				src = currentNode.getAttribute('src'),
				type = currentNode.getAttribute('type'),
				html = currentNode.innerHTML,
				scriptTag = document.createElement('script');

			if(async!=undefined) scriptTag.setAttribute('async', async);
			if(defer!=undefined) scriptTag.setAttribute('defer', defer);
			if(src!=undefined) scriptTag.setAttribute('src', src);
			if(type!=undefined) scriptTag.setAttribute('type', type);

			scriptTag.innerHTML = html;

			if(async!=undefined){
				document.body.appendChild(scriptTag);
			}else{
				context.appendChild(scriptTag);
			}

			div.removeChild(currentNode);

		}else{

			docFrag.appendChild(currentNode);

		}
	}
	docFrag.removeChild(div);

	parent.insertBefore(docFrag, context);
	parent.removeChild(context);
}