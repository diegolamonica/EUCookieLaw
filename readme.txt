=== Plugin Name ===
Contributors: diego-la-monica
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=me%40diegolamonica%2einfo&lc=IT&item_name=EU%20Cookie%20Law&no_note=0&currency_code=EUR&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHostedGuest
Tags: Cookie, Law Compliance
Requires at least: 4.0
Tested up to: 4.2.2
Stable tag: 1.5
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

   **Note that this plugin blocks any URL you define which generates third-party cookies.**

To get detailed informations about the plugin go to https://github.com/diegolamonica/EUCookieLaw

== Installation ==

For manual installation just download the zip file then upload via the WordPress plugin installer page or extract files
in a local directory then upload the whole directory under `/wp-content/plugins/`.

== Changelog ==

== 1.5 ==

This update introduces several improvements, features and bugfixes. For a detailed information about the new release see
the [documentation page](https://github.com/diegolamonica/EUCookieLaw/) and the [Milestone 1.5](https://github.com/diegolamonica/EUCookieLaw/issues?q=milestone%3A1.5+is%3Aclosed)

* **NEW:** Now the plugin is able to detect if the user agent and does not block contents if it is search engine
* **NEW:** All the external contents are loaded after the user consent without page reloading ( Issues [#4](https://github.com/diegolamonica/EUCookieLaw/issues/4) and [#10](https://github.com/diegolamonica/EUCookieLaw/issues/10))
* **NEW:** The script allows to define the consent duration in days (Issue [#7](https://github.com/diegolamonica/EUCookieLaw/issues/7), [#17](https://github.com/diegolamonica/EUCookieLaw/issues/17) and [#23](https://github.com/diegolamonica/EUCookieLaw/issues/23))
* **NEW:** Now is possible to check almost in every HTML element ( Implicitly resolved issue [#6](https://github.com/diegolamonica/EUCookieLaw/issues/6))
* **NEW:** The script remembers the user rejection.
* **NEW:** New JavaScript public method `reconsider` to revoke the consent (and the rejection) showing the banner again (Issue [#7](https://github.com/diegolamonica/EUCookieLaw/issues/7))
* **NEW:** \[WP\] Added shortcode for reconsider button (see documentation for further details) (Issue [#7](https://github.com/diegolamonica/EUCookieLaw/issues/7))
* **NEW:** \[WP\] Added shortcode for wrapping contents (see documentation for further details)
* **NEW:** Now the consent on scroll is fired at least after 100px scroll (up or down)
* **IMPROVEMENT:** \[WP\] Made compliant with **WP Super Cache**, **W3 Total Cache**, **Zen Cache** (Issue [#23](https://github.com/diegolamonica/EUCookieLaw/issues/23))
* **IMPROVEMENT:** Javascript has been refactored to improve performance and maintenability
* **IMPROVEMENT:** \[WP\] Admin interface improved
* **IMPROVEMENT:** Some CSS improvements (Issue (Issue [#8](https://github.com/diegolamonica/EUCookieLaw/issues/8))
* **BUGFIX:** Consent on scroll doesn't work propery
* **BUGFIX:** \[WP\] Custom content path not recognized correctly ( Issue [#9](https://github.com/diegolamonica/EUCookieLaw/issues/9))
* **BUGFIX:** Typos where `script` was written as `srcript` on server script (Issue [#16](https://github.com/diegolamonica/EUCookieLaw/issues/16))
* **BUGFIX:** Only first occourrence of the same/similar URL is blocked (Issue [#19](https://github.com/diegolamonica/EUCookieLaw/issues/19))
* **BUGFIX:** Corrected some IE8 weird behavior
* updated translation files
* updated documentation
* updated the version number


= 1.4.1 =
* **BUGFIX:** fixed the javascript that has wrong characters in the script
* Fixed errors in documentation

= 1.4 =
* **NEW:** when you specify a domain starting with a dot (eg. `.google.com`) all the subdomains are valid (eg. `www.google.com` and `sub.domain.google.com`)
* **NEW:** Improved the banner loading (loaded before the DOM Event `load`)
* **NEW:** Optional implicit user agree on page scrolling ([Issue #4](https://github.com/diegolamonica/EUCookieLaw/issues/4)).
* **NEW:** Debugging options
* **NEW:** You can fix the banner on top or bottom of the page.
* **NEW:** The custom CSS (from `EUCookieLawCustom`) will be loaded in conjunction with the default CSS.
* **BUGFIX:** removed the `<![CDATA[ ... ]]>` envelop on script replacement due to some browser incompatibility.
* **BUGFIX:** Custom translations was never read
* updated translation files
* updated documentation
* updated the version number

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

== FAQ ==

* **How can I customize the banner style?**
  Please visit the [official documentation page on GitHub](https://github.com/diegolamonica/EUCookieLaw)