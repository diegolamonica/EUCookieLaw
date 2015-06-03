=== Plugin Name ===
Contributors: diego-la-monica
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=me%40diegolamonica%2einfo&lc=IT&item_name=EU%20Cookie%20Law&no_note=0&currency_code=EUR&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHostedGuest
Tags: Cookie, Law Compliance
Requires at least: 4.0
Tested up to: 4.2.2
Stable tag: 1.3
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

= 1.3.1 =
* **BUGFIX:** the default text for disagree button when not given was `Disagree` instead it should be empty.
* **BUGFIX:** whatever is the name of the plugin directory the directory for the customizations (translations and CSS) must be `/wp-content/plugins/EUCookieLawCustom/`.
* updated documentation
* updated the version number

= 1.3 =

* Updated the eucookielaw-header.php,
  * **NEW:** now the disallowed domains trims the spaces on each domain. It means that is allowed to write `domain1.com ; domain2.com` and they will be correctly interpreted as `domain1.com` and `domain2.com`
* **NEW:** If not defined the disagee label text then the button is not shown. Useful for informative non-restrictive cookie policy.
* **BUGFIX:** the cookie `__eucookielaw` setted by javascript is defined at root domain level.
* updated documentation
* updated the version number

= 1.2 =

* Updated the eucookielaw-header.php,
  * **NEW:** now the search of url is performed in `<script>...</script>` tags too.
  * **BUGFIX:** some translations strings were broken.
* updated translation files
* updated documentation
* updated the version number

= 1.1 =

This update introduces several improvements, features and bugfixes. For a detailed information about the new release see:
[Issue #1](https://github.com/diegolamonica/EUCookieLaw/issues/1)

* updated the eucookielaw-header.php,
  * **NEW:** now it blocks script tags with `data-eucookielaw="block"` attribute
  * **NEW:** now is possible to define a blacklist of domains to block before the user consent the agreement
  * **NEW:** the blacklist is related to a set of tags (by default the plugin will scan `iframe`, `link` and `script` tags
* **NEW::** managed title tag, blocked domains and tags to scan
* **NEW:** if the plugin WP Super Cache is installed then the plugin will clear the cache until the user has not approved the agreeement to ensure to show always the right contents
* **NEW::** if there is a CSS file named `eucookielaw.css` in the custom directory `wp-content/plugins/EUCookieLawCustom/` the it will be appliead in place of the default one.
* **BUGFIX:** unescaped post data before saving the admin settings
* updated the version number
* updated translation strings
* updated documentation

= 1.0 =
* First release
