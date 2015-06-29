# EUCookieLaw

  EUROPA websites must follow the Commission's guidelines on [privacy and data protection](http://ec.europa.eu/ipg/basics/legal/data_protection/index_en.htm) and inform 
  users that cookies are not being used to gather information unnecessarily.
   
  The [ePrivacy directive](http://eur-lex.europa.eu/LexUriServ/LexUriServ.do?uri=CELEX:32002L0058:EN:HTML) – more specifically Article 5(3) – requires prior informed consent for storage for access to information stored on a user's terminal equipment. 
  In other words, you must ask users if they agree to most cookies and similar technologies (e.g. web beacons, Flash cookies, etc.) before the site starts to use them.

  For consent to be valid, it must be informed, specific, freely given and must constitute a real indication of the individual's wishes.

In this context this class lives.
It simply alters the default `document.cookie` behavior to disallow cookies to be written on the client side, until the user accept the agreement.

# How to use

If you want to use it as wordpress plugin then skip the **Client side** and the **Server side** sections

## Client side

Download the script file `EUCookieLaw.js` 

Add this code in your HTML `head` section (better if before all others JavaScripts)
```html
<script src="EUCookieLaw.js"></script>
<script>
    new EUCookieLaw({
        message: 'In base ad una direttiva europea sulla privacy e la protezione dei dati personali, è necessario il tuo consenso prima di conservare i cookie nel tuo browser. Me lo consenti?'
    });
</script>
```

If the user accepts the agreement then EUCookieLaw will store a cookie for itself (to remember that the user accepted the agreement) named `__eucookielaw` with `true` as value,
that lives during the current session.  

### Customize the behavior
the `EUCookieLaw` initialization expect an Object with the following properties:

* `showBanner` (`boolean` default `false`) if you want to show a banner at the top of your page you need to set this 
  option to `true`. 

* `reload` (`boolean` default `false`)  if `true` the page will be refreshed after the user accepts the agreement. This is useful is used 
  in conjunction with the server side part.

* `message` is the message used by the default confirmation dialog. In the case of `showBanner`, the `message` can be an 
  HTML content.

* `debug` (`boolean` default `false`)  if `true` will show in browser console some useful informations about script execution.

* `cookieEnabled` (`boolean` default `false`)  set to `true` to not show the banner. However this setting will change 
  once the user take a choice.

* `cookieRejected` (`boolean` default `false`)  set to `true` to not show the banner. However this setting will change 
  once the user take a choice.

* `duration` (`integer` default `0`) the number of days you want the cookie will expire. If `0`, it will produce a 
  session cookie. 

* `remember` (`boolean` default `true`) if seted to `true`, the user rejection will be remember through the current session 
  else the choice will be valid only for the current page.   

* `path` (`string` defualt `/`) defines the path where the consent cookie will be valid.

* `cookieList` (`array` default `[]`) the list of techincal cookies the user cannot choose to reject. If some script try 
  to write one of the listed cookie it will be accepted.  
  **TIP:** You can use the `*` wildchar as suffix to intend all the cookies that starts with a specific value (eg. `__umt*` will mean `__umta`, `__umtc` and so on).
   
* `blacklist` (`array` default `[]`) if some script try to inject HTML into the page trhough the `document.write` it will be allowed only if
  in the code is not present something that points to one of the `blacklist`ed domain.

#### Options available only if `showBanner` is `true` 

* `id` (`string` default `boolean` `false`) if not `false` the banner box will not be created and the script will assume
  that the banner is the one referred by the `id`. 
  **NOTE:** do not set the hash (`#`) before the id (eg. **OK** `id: 'my-box'` **NO** `id: '#my-banner'`)
  
* `tag` if defined the script will use it as predefined tag for title content of the banner. 
   If not defined the banner title will not be shown.
   
* `bannerTitle` will be the banner title

* `agreeLabel` the agree button label. Default is `I agree`

* `disagreeLabel` the disagreement button label. Default is an empty string. If not given the disagree button will not be shown.

* `fixOn` it defines if the banner is fixed on top or bottom, default value, if not defined or empty, is `top`. Allowed values are `top`, `bottom` or `static`.

* `showAgreement` is the callback method that will show the dialog with the user agreement about the cookie usage. If you 
  use a synchronous mode to show a dialog (eg. `confirm` method) the `showAgreement` must return `true` if the user have 
  accepted the agreement, in all other cases (user rejected the agreement or in asynchronous mode) it must return `false`.

* `agreeOnScroll` if `true`, when the user will scroll the page, then the agreement is implicitly accepted. The default value is `false`.

* `agreeOnClick` if `true`, the user express its conesnt by clicking wherever on the page (but outside the banner).

Once `EUCookieLaw` is initialized, you can access some useful methods in your JavaScript:

* `enableCookies` enables the site to store cookies

* `reject` reject the cookies agreement

* `isRejected` if the user have rejected the request to store cookie

* `isCookieEnabled` if the site can store cookies

* `reconsider` allows the user to review again the banner and take a new choice.
  To invoke this function from everywhere in your policy page you can create a link or a button with the following code:  
```html
<a href="#" onclick="(new EUCookieLaw()).reconsider(); return false;">Reconsider my choice</a>
```

#### Custom agreement example

Synchronous mode ([see demo](http://diegolamonica.info/demo/cookielaw/demo1.html)):

```html
<script src="EUCookieLaw.js"></script>
<script>
    function myCustomAgreement(){
        if(!eu.isRejected()) {
            if (confirm('do you agree?')) {
                return true;
            }
            eu.reject();
        }
        return false;
    }

    new EUCookieLaw({
        showAgreement: myCustomAgreement
    });
</script>
```

Asynchronous mode ([see demo](http://diegolamonica.info/demo/cookielaw/demo2.html)): 

```html
<script src="EUCookieLaw.js"></script>
<script>

    function showDialog(){
        /*
         * Your custom dialog activator goes here
         */
     }
    function myCustomAgreement(){
        /* show some HTML-made dialog box */
        showDialog();
        return false;
    }

    new EUCookieLaw({
        showAgreement: myCustomAgreement
    });
</script>
```

With agreement banner ([see demo](http://diegolamonica.info/demo/cookielaw/demo3.html)): 
```html
<script src="EUCookieLaw.js"></script>
<script>

    new EUCookieLaw({
        message: "La legge prevede l'autorizzazione all'utilizzo dei cookie. Me la vuoi dare per favore?",
        showBanner: true,
        bannerTitle: 'Autorizzazione alla conservazione dei cookie',
        agreeLabel: 'Do il mio consenso',
        disagreeLabel: 'Nego il consenso',
        tag: 'h1'
    });
</script>
```

## Server Side

The server-side script intercept the output buffer and will remove the sent cookies when user has not yet approved the
agreement.

Then you should include the file `eucookielaw-header.php` as the first operation on your server.
This will ensure you that any of your script or CMS like Drupal, Joomla or whatever you are using, is able to 
write a cookie if the user doesn't given his consensus.

```php
// This must be the first line of code of your main, always called, file.
require_once 'eucookielaw-header.php'; 
```

However if the server already detected that the user agreed the cookie law agreement the 
script does not override the built-in function.

Further if you want to block some javascript elements you can do it by adding a `data-eucookielaw="block"` attribute to the `script` elements.
 
### Server side constants
If you want to block specific domains you can define in your script (before including `eucookielaw-header.php`) two constants:

* `EUCOOKIELAW_DISALLOWED_DOMAINS` a semicolon (`;`) separated list of URLs disallowed since the user does not accept the agreement.  
  Each space before and/or after each URL will be removed.
  **Note:** if the domain start by a dot (eg. `.google.com`) then all the related subdomains will be included in the temporary blacklist.
  
* `EUCOOKIELAW_LOOK_IN_TAGS` a pipe (`|`) separated list of tags where to search for the domains to block.   
  If not specified, the deafault tags are `iframe`. `script`, `link`.
  
* `EUCOOKIELAW_LOOK_IN_SCRIPTS` a boolean value, if `true` the URLs defined in `EUCOOKIELAW_DISALLOWED_DOMAINS` will be searched in the `<script>...</script>` tags too.

* `EUCOOKIELAW_SEARCHBOT_AS_HUMAN` if `true` the search engines will be threated as humans (same contents, to avoid accidental [cloacking](https://en.wikipedia.org/wiki/Cloaking) contents).

* `EUCOOKIELAW_ALLOWED_COOKIES` the list (**must be a comma separated value**) of techincal cookies that the server is allowed to generate and that will not removed from headers.  
  **TIP \#1:** You can use the `*` wildchar as suffix to intend all the cookies that starts with a specific value (eg. `__umt*` will mean `__umta`, `__umtc` and so on).  
  **TIP \#2:** If you use just `*` all cookies generated by your Web Site are allowed.


* `EUCOOKIELAW_AUTOSTART` if you want to invoke late the `ob_start` then you should define this constant to `true`. 
  **NOTE:** If you set this option to `true` you need to invoke lately by your own the `buffering` class method. 

* `EUCOOKIELAW_DISABLED` a boolean value, if `true` the class `EUCookieLawHeader` will not be instantiated when you include
  the `eucookielaw-cache.php` in your PHP scripts.
  
* `EUCOOKIELAW_DEBUG` a boolean value, if `true` the HTML output will report before each replacement the rule applied and at the beginning of the file it will show all the applied rules.  
  **Important** do not keep it enabled on production environment.  
  **Note:** in the beginning of your HTML file you can see `<!-- (EUCookieLaw Debug Enabled) -->` message followed by some other details. 
  Those messages are useful to understand what exactly is happening in your site.

* `EUCOOKIELAW_BANNER_ADDITIONAL_CLASS` a string where to define the custom classes applied to the banner. 

* `EUCOOKIELAW_BANNER_TITLE` the title to show on the banner.

* `EUCOOKIELAW_BANNER_DESCRIPTION` the description to show into the banner.

* `EUCOOKIELAW_BANNER_AGREE_BUTTON` the label on the agree button

* `EUCOOKIELAW_BANNER_DISAGREE_BUTTON` the label on the disagree button. If not defined or defined as empty string then the disagree button will not be shown on the page.

* `EUCOOKIELAW_BANNER_AGREE_LINK` the link to apply the consent. To let the script to manage by its own the consent, this link should contain the argument **`__eucookielaw=agree`**.  
  this mean that if the link is `http://example.com/my-page.html?arg1=a&arg2=b` then you should append the suggested argument as follows: `http://example.com/my-page.html?arg1=a&arg2=b&__eucookielaw=agree`.
   
* `EUCOOKIELAW_BANNER_DISAGREE_LINK` the link to reject the consent. To let the script to manage by its own the rejection, this link should contain the argument **`__eucookielaw=disagree`**.  
  this mean that if the link is `http://example.com/my-page.html?arg1=a&arg2=b` then you should append the suggested argument as follows: `http://example.com/my-page.html?arg1=a&arg2=b&__eucookielaw=disagree`.

### How to manage by your own the rejection

While WordPress has its own shortcode to manage the rejection button, in the standalone version you should produce a link with a specific argument into the querystring: `__eucookielaw=reconsider`.


## Using EUCookieLaw into WordPress

The plugin is available on [WordPress plugin directory](http://wordpress.org/plugins/eucookielaw/).

If you want install from this repository, just download the zip and install it in your WordPress.
The plugin actually supports translation in both Italian (by translation file) and English (default). 

The plugin is compliant (also read as **has been tested**) with **WP Super Cache**, **W3 Total Cache** and **Zen Cache** plugins to serve the right contents when the user has not yet approved the agreement.

### Shortcodes

Actually EUCookieLaw supports two shortcodes 
#### `EUCookieLawReconsider`
 
The purpose of this shortcode is to produce a button that allow user to choose again whether to consent or not the cookie policy.

It will show a link with `btn` and `btn-warning` classes and text defined in the `label` attribute.
If you don't define the `label` attribute the default value is `Reconsider`.

**Example:** `[EUCookieLawReconsider label="I want take another choice"]` 

#### `EUCookieLawBlock`
The purpose of this shortcode is to wrap contents into a post and make it available once the user agreed the policy.

**Example:** 
```html
[EUCookieLawBlock]
    <p>
        This content is blocked until user consent
    </p>
[EUCookieLawBlock]
```
### How to make the banner title and message translate in the proper language.

I've implemented the custom text-domain files ( `EUCookieLawCustom-it_IT.po` / `EUCookieLawCustom-it_IT.po` ).  
Remember that to get custom translations properly work, **you need to move the `EUCookieLawCustom` directory at the `plugins` directory level**.

To be more clear the custom directory will be: **`wp-content/plugins/EUCookieLawCustom`**
 
Then take the file default and you have to put 4 strings in your translation file:

* `Banner title`
* `Banner description`
* `I agree`
* `I disagree`
* `Reconsider`

Remember to put the above text in the plugin settings page (default behavior) and to produce the translation files 
(starting from the `default.po` located in the `EUCookieLawCustom` directory).

You can see a production example on my [personal WebSite](http://diegolamonica.info).

### Create a detailed policy privacy page

To ensure your site is law compliant, you should have a page where you describe to your user which are the third-party cookies, 
which is their purpose and how to disable them. And yes! Don't forget to put the link in the banner!

## CSS Cookie Banner Customization
The structure of generated banner (with the default heading tag settings) is the following:

```html
<div class="eucookielaw-banner fixedon-top" id="eucookielaw-135">
  <div class="well">
    <h1 class="banner-title">The banner title</h1>
    <p class="banner-message">The banner message</p>
    <p class="banner-agreement-buttons text-right">
      <a class="disagree-button btn btn-danger" onclick="(new EUCookieLaw()).reject();">Disagree</a> 
      <a class="agree-button btn btn-primary" onclick="(new EUCookieLaw()).enableCookies();">Agree</a>
    </p>
  </div>
</div>
```

* `.eucookielaw-banner` is the banner container it will have a random `id` attribute name that 
starts always with `eucookielaw-` and then followed by a number between `0` and `200`.
* `.well` is the inner container
* `h1.banner-title` is the banner title
* `p.banner-message` is the banner html message
* `p.banner-agreement-buttons.text-right` is the buttons container for the agree/disagree buttons
* `a.disagree-button` is the disagree button it implements the CSS classes `btn` and `btn-danger`
* `a.disagree-button` is the agree button it implements the CSS classes `btn` and `btn-primary`

You can make your own CSS to build a custom aspect for the banner. 
However, if you prefer, you can start from the bundled CSS.  

**NOTE:** If you are using the script as WordPress plugin, the custom CSS must be located in the directory `wp-content/plugins/EUCookieLawCustom/` 
and must be named `eucookielaw.css`. Then it will be read in conjunction with the default plugin CSS.

# Contribute

I'd like to translate this plugin in all european languages, but I'm limited to the Italian and English too.

If you want to get involved in this plugin development, then fork the repository, translate in your language and make a pull request!

# Donations
If you find this script useful, and since I've noticed that nobody did this script before of me, 
I'd like to receive [a donation](https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=me%40diegolamonica%2einfo&lc=IT&item_name=EU%20Cookie%20Law&no_note=0&currency_code=EUR&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHostedGuest).   :)

# Changelog

## 2.1.1
* **IMPROVEMENT**: If not defined the `EUCOOKIELAW_BANNER_DISAGREE_BUTTON` the disagree button will not be shown on the page.
* **IMPROVEMENT**: Removed the session/local storage in favor of technical session cookie for storing the user rejection
* **IMPROVEMENT**: Improved the way to detect if the cookie is approved or rejected
* **IMPROVEMENT**: Uniformed the way to write the technical cookie `__eucookielaw`
* **IMPROVEMENT**: Improved the way how the banner is removed
* **IMPROVEMENT**: Updated missing pieces in documentation.
* **IMPROVEMENT**: Optimized behavior when asked reload of contents after consent.
* **BUGFIX**: Resolved an [anicient related firefox issue](https://bugzilla.mozilla.org/show_bug.cgi?id=356558)
* updated the minor version number
* updated documentation

## 2.1.0
* **BUGFIX**: when PHP does not have gzdecode the method is implemented on needs.
* **BUGFIX**: Internet Explorer and some mobile Browser does not recognize the `instance` variable as `EUCookieLaw` object causing a bad banner behavior.
* **BUGFIX**: \[WP\] NextGenGallery has some weird behavior sometimes (skipped to load the locker if it is a NGG URL.
* **IMPROVEMENT**: \[WP\] The plugin now tries to write into `wp-config.php` only if there is another cache plugin enabled on the site.
* **IMPROVEMENT**: EUCookieLaw related PHP Warnings threated as required

## 2.0.2
* **CRITICAL**: 
Most of WordPress sites uses a FTP settings for writing files. Used native `file_get_contents` and `file_put_contents` 
to write data into some files for a better user experience.

## 2.0
* **NEW:** [\WP\] Full compliant with any cache plugin (actually successfully tested with **WP Super Cache**, **W3 Total Cache**, **Zen Cache**)
* **NEW:** The banner is now visible either with and without javascript enabled.
* **NEW:** User consent whenever he clicks on an element of the page (Issue [#12](https://github.com/diegolamonica/EUCookieLaw/issues/12))
* **NEW:** You can list the allowed cookies before consent (aka *Technical Cookies*). This solves the issue [#15](https://github.com/diegolamonica/EUCookieLaw/issues/15)
* **NEW:** Now Google Analytics is able to write cookies via JavaScript (if configured) (Issue [#15](https://github.com/diegolamonica/EUCookieLaw/issues/15))
* **NEW:** \[WP\] You can enable/disable the banner on frontend (Issue [#20](https://github.com/diegolamonica/EUCookieLaw/issues/20))
* **NEW:** \[WP\] You can enable/disable the banner on the login page (Issue [#21](https://github.com/diegolamonica/EUCookieLaw/issues/21))
* **NEW:** You can set the "reload on scroll" (Issue [#26](https://github.com/diegolamonica/EUCookieLaw/issues/26))
* **NEW:** \[WP\] Added the WPML XML Configuration File for a better WPML compatibility.
* **IMPROVEMENT:** \[WP\] Lack of documentation on certain admin fields (Issue [#27](https://github.com/diegolamonica/EUCookieLaw/issues/27))
* **IMPROVEMENT:** Most of PHP Code was completely refactored from the ground to improve performance and readability.
* **BUGFIX:** \[WP\] NextGenGallery conflict resolved (Issue [#31](https://github.com/diegolamonica/EUCookieLaw/issues/31))
* **BUGFIX:** \[WP\] QuickAdsense conflict resolved (Issue [#36](https://github.com/diegolamonica/EUCookieLaw/issues/36) and  [#32](https://github.com/diegolamonica/EUCookieLaw/issues/32) )
* **BUGFIX:** \[WP\] Revolution Slider conflict resolved (Issue [#37](https://github.com/diegolamonica/EUCookieLaw/issues/37))
* **BUGFIX:** Page URL changes after reload (Issue [#38](https://github.com/diegolamonica/EUCookieLaw/issues/38))
* **BUGFIX:** Scroll on tablet does not work  (Issue [#40](https://github.com/diegolamonica/EUCookieLaw/issues/40))
* **BUGFIX:** Invalid Calling Object in Internet Explorer 9 and Safari was resolved  (Issue [#41](https://github.com/diegolamonica/EUCookieLaw/issues/41))
* updated translation files
* updated documentation
* updated the version number

## 1.5

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

## 1.4.1
* **BUGFIX:** fixed the javascript that has wrong characters in the script

## 1.4
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

## 1.3.1
* **BUGFIX:** the default text for disagree button when not given was `Disagree` instead it should be empty.
* **BUGFIX:** whatever is the name of the plugin directory the directory for the customizations (translations and CSS) must be `/wp-content/plugins/EUCookieLawCustom/`.
* updated documentation
* updated the version number

## 1.3
* Updated the eucookielaw-header.php,
  * **NEW:** now the disallowed domains trims the spaces on each domain. It means that is allowed to write `domain1.com ; domain2.com` and they will be correctly interpreted as `domain1.com` and `domain2.com`
* **NEW:** If not defined the disagee label text then the button is not shown. Useful for informative non-restrictive cookie policy.
* **BUGFIX:** the cookie `__eucookielaw` setted by javascript is defined at root domain level.
* updated documentation
* updated the version number

## 1.2
* Updated the eucookielaw-header.php,
  * **NEW:** now the search of url is performed in `<script>...</script>` tags too.
  * **BUGFIX:** some translations strings were broken.
* updated translation files
* updated documentation
* updated the version number

## 1.1
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

## 1.0
* First release

# Who is using EUCookieLaw

Several sites are using EUCookieLaw as WordPress plugin (actually there are more than 100 installs).
If you want to let users know your experience in using EUCookieLaw, there is a [Facebook page](https://www.facebook.com/UsaEUCookieLaw) 
where you can share your thoughts and experience.