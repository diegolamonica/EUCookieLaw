=== Plugin Name ===
Contributors: diego-la-monica
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=me%40diegolamonica%2einfo&lc=IT&item_name=EU%20Cookie%20Law&no_note=0&currency_code=EUR&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHostedGuest
Tags: Cookie, Law Compliance
Requires at least: 4.0
Tested up to: 4.2.2
Stable tag: 4.2.2
License: LGPLv3
License URI: http://www.gnu.org/licenses/lgpl-3.0-standalone.html

A Wordpress solution to the European Cookie Law Issue

== Description ==

EUROPA websites must follow the Commission's guidelines on [privacy and data protection](http://ec.europa.eu/ipg/basics/legal/data_protection/index_en.htm) and inform
users that cookies are not being used to gather information unnecessarily.
   
The [ePrivacy directive](http://eur-lex.europa.eu/LexUriServ/LexUriServ.do?uri=CELEX:32002L0058:EN:HTML) – more specifically Article 5(3) – requires prior informed consent for storage for access to information stored on a user's terminal equipment.
In other words, you must ask users if they agree to most cookies and similar technologies (e.g. web beacons, Flash cookies, etc.) before the site starts to use them.

For consent to be valid, it must be informed, specific, freely given and must constitute a real indication of the individual's wishes.

In this context this plugin lives.
It simply alters the default `document.cookie` behavior to disallow cookies to be written on the client side,
until the user accept the agreement. The same does for the server side where since the user does not have accepted the agreement,
then it would not store any cookie.

   Note that this plugin is not able to block third-part cookies.

To get detailed informations about the plugin go to https://github.com/diegolamonica/EUCookieLaw

== Installation ==

For manual installation just download the zip file then upload via the WordPress plugin installer page or extract files
in a local directory then upload the whole directory under `/wp-content/plugins/`.

== Changelog ==

= 1.0 =
* First release
