/**
 * EUCookieLaw: simple object to accomplish european law requirements about cookie transmission to clients
 * @class EUCookieLaw
 * @version 2.3.2
 * @link https://github.com/diegolamonica/EUCookieLaw/
 * @author Diego La Monica (diegolamonica) <diego.lamonica@gmail.com>
 * @copyright 2015 Diego La Monica
 * @license http://www.gnu.org/licenses/lgpl-3.0-standalone.html GNU Lesser General Public License
 * @note This program is distributed in the hope that it will be useful - WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */


(function($) {
	if( pagenow !== 'eu-cookie-law_page_EUCookieLaw-settings') return;

	var EUCookieLawServices = {};
	var mode = 'export';

	function manageSettings(){
		var $ = jQuery;
		if($('#eucookielaw-settings').length == 0){
			$('<div id="eucookielaw-settings"><textarea></textarea><p><button>done</button></p>').appendTo('body');
		}
		$('#eucookielaw-settings textarea').val( $('form').serialize() );
	}

	function exportSettings(event){
		event.preventDefault();
		mode = 'export';
		manageSettings();
	}
	function importSettings(event){
		event.preventDefault();
		mode = 'import';
		manageSettings();
	}

	window.EUCookieLawAddService = function( serviceName, rules){
		EUCookieLawServices[serviceName] = rules;
	}

	function cloneItem(theContainer){
		// Cloning the container with events
		var clonedSection = $(theContainer).clone(true);

		// And appending it just after the current container
		$(clonedSection).insertAfter(theContainer);
	}

	function makeRepeater(sectionsSelector, addClass, removeClass, AYSMsg) {
		$(document
		).on("click",
				sectionsSelector + " " + addClass + "," +
				sectionsSelector + " " + removeClass,
			function (event) {
			// Avoiding the link to do the default behavior.
			event.preventDefault();

			// Get the container to be removed/cloned
			var theContainer = $(this).parents(sectionsSelector);
			if ($(this).is(addClass)) {

				cloneItem(theContainer);

			} else {
				// If the user confirm the "Are You Sure" message
				// we can remove the current container
				// if (confirm(AYSMsg)) {

					// Making fade out, hide and remove element a sequence
					// to provide a nice UX when removing element.
					$(theContainer).fadeOut('normal',
						function () {
							$(this).hide('fast',
								function () {
									$(this).remove();
								}
							);
						}
					);
				// }
			}
		});
	}

	makeRepeater(
		'.eucookie-repeated-section',   /* The container selector */
		'.add',                   /* The add action selector */
		'.remove',                /* The remove action selector */
		'Are you sure you want to remove this section?' /* The AYS message. */
	);

	EUCookieLawAddService('google-all', [
		'.google.it',
		'.google.com',
		'.googleapis.com'
	]);
	EUCookieLawAddService('google-maps', [
		'fonts.googleapis.com'
	]);
	EUCookieLawAddService('google-fonts', [
		'maps.google.com',
		'maps.googleapis.com',
		'www.google.com/maps',
		'www.google.it/maps'
	]);
	EUCookieLawAddService('google-analytics', [
		'.google-analytics.com'
	]);

	EUCookieLawAddService('google-adsense', [
		'.googlesyndication.com/pagead/'
	]);

	EUCookieLawAddService('google-doubleclick', [
		'.doubleclick.net'
	]);

	EUCookieLawAddService('facebook', [
		'.facebook.net',
		'.facebook.com',
		'.facebook.it',
		'.facebook.net'
	]);

	EUCookieLawAddService('instagram', [
		'.instagram.com',
		'.cdninstagram.com'
	]);

	EUCookieLawAddService('linkedin', [
		'.linkedin.com'
	]);

	EUCookieLawAddService('pinterest', [
		'.pinterest.com'
	]);

	EUCookieLawAddService('twitter', [
		'.twitter.com',
		'.twitterfeed.com'
	]);

	EUCookieLawAddService('vimeo', [
		'.vimeo.com'
	]);

	EUCookieLawAddService('google-youtube', [
		'.youtube-nocookie.com',
		'.youtube.com'
	]);

	EUCookieLawAddService('Vimeo', [
		'.vimeo.com'
	]);

	EUCookieLawAddService('digg', [
		'.digg.com'
	]);

	EUCookieLawAddService('addthis', [
		'.addthis.com'
	]);

	EUCookieLawAddService('eventbrite', [
		'.eventbrite.it',
		'.eventbrite.com'
	]);


	$('[data-eucookielaw-include]').on('click', function(event){
		event.preventDefault();
		var serviceRules = EUCookieLawServices[ $(this).data('eucookielaw-include')].slice() ;
		if(serviceRules) {
			$('#blocked-urls .eucookie-repeated-section input').each(function(){
				var idx;
				if(idx = serviceRules.indexOf( $(this).val() ) !=-1){
					serviceRules[idx] = false;
				}
			});

			serviceRules.forEach( function(item){
				if(item != false){
					cloneItem('#blocked-urls .eucookie-repeated-section:last');
					$('#blocked-urls .eucookie-repeated-section:last input').val(item);
				}
			});
		}
	});

	$('[data-set-url]').on('click', function(event){
		event.preventDefault();
		var $this = $(this);
		$( $this.attr('href')).val( $this.data('set-url') );

	});


	$('#import-settings').on('click', importSettings);
	$('#export-settings').on('click', exportSettings);


	$(document).on('click', '#eucookielaw-settings button', function(){
		if(mode =='import') {
			var settings = $('#eucookielaw-settings textarea').val();
			var queryString = {};
			settings.replace(
				new RegExp("([^?=&]+)(=([^&]*))?", "g"),
				function($0, param, $2, value) {
					param = decodeURIComponent(param);
					value = decodeURIComponent(value);
					if(/.*\[]$/.test(param)){
						param = param.substr(0, param.length-2);
						if(!$.isArray( queryString[param] )){
							if(queryString[param] !== undefined)
								queryString[param] = [ queryString[param] ];
							else
								queryString[param] = [];
						}
						if(value!=='') {
							queryString[param].push(value);
						}
					}else {
						queryString[param] = value;
					}
				}
			);

			for(var key in queryString){
				var value = queryString[key],
					selector = '[name="' + key + '"]';

				if( $.isArray( value ) ){
					selector = '[name="' + key + '[]"]';
					$(selector).parent('.eucookie-repeated-section').not(':first').remove();
					selector += ":last";
					for(var i = 0; i<= value.length; i++) {
						if(i>0){
							cloneItem( $(selector).parent('.eucookie-repeated-section') );
						}
						$(selector).val( value[i] );
					}
				}else {
					$(selector).val(value);
				}
			}
		}
		$('#eucookielaw-settings').remove();
	});

	$('.handlediv').on('click', function(event){
		$(this).siblings('.inside').slideToggle('fast');
	});

	var pos = $('#submit').offset().top;
	$(window).on('scroll', function () {

		var scrollTop = $(window).scrollTop();
		if (pos < scrollTop) {
			$('#submit').parent().addClass('fixed-on-top');
		} else {
			$('#submit').parent().removeClass('fixed-on-top');
		}

	});


	$('[name=debug]').on('change', function(){

		var val = $('[name=debug]:checked').val();
		$('.on-debug-'+ val).slideDown();
		$('.on-debug').not('.on-debug-'+ val).slideUp();

	}).change();

})(jQuery);
