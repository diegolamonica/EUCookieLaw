# EUCookieLaw

  EUROPA websites must follow the Commission's guidelines on [privacy and data protection](http://ec.europa.eu/ipg/basics/legal/data_protection/index_en.htm) and inform 
  users that cookies are not being used to gather information unnecessarily.
   
  The [ePrivacy directive](http://eur-lex.europa.eu/LexUriServ/LexUriServ.do?uri=CELEX:32002L0058:EN:HTML) – more specifically Article 5(3) – requires prior informed consent for storage for access to information stored on a user's terminal equipment. 
  In other words, you must ask users if they agree to most cookies and similar technologies (e.g. web beacons, Flash cookies, etc.) before the site starts to use them.

  For consent to be valid, it must be informed, specific, freely given and must constitute a real indication of the individual's wishes.

In this context this class lives.
It simply alters the default `document.cookie` behavior to disallow cookies to be written on the client side, until the user accept the agreement.

## How to use

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

* `message` is the message used by the default confirmation dialog. In the case of `showBanner`, the `message` can be an HTML content.
* `showAgreement` is the callback method that will show the dialog with the user agreement about the cookie usage. If you 
  use a syncronous mode to show a dialog (eg. `confirm` method) the `showAgreement` must return `true` if the user have 
  accepted the agreement, in all other cases (user rejected the agreement or in asyncronous mode) it must return `false`.
* `showBanner` (`boolean`)if you want to show a banner at the top of your page you need to set tis option to `true`. 
* `bannerTitle` (only if `showBanner` is `true`) the banner title
* `agreeLabel` (only if `showBanner` is `true`) the agree button label. Default is `I agree`
* `disagreeLabel` (only if `showBanner` is `true`) the disagreement button label. Default is `Disagree`
* `reload` if `true` the page will be refreshed after the user accepts the agreement. This is useful is used in 
  conjunction with the server side part.

Once `UECookieLaw` is initialized, you can access some useful methods in your JavaScript:

* `enableCookies` enables the site to store cookies
* `reject` reject the cookies agreement
* `isRejected` if the user have rejected the request to store cookie
* `isCookieEnabled` if the site can store cookies

#### Custom agreement example

In the sync mode ([see demo](http://diegolamonica.info/demo/cookielaw/demo1.html)):
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

Async mode ([see demo](http://diegolamonica.info/demo/cookielaw/demo2.html)): 
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
        disagreeLabel: 'Nego il consenso'
    });
</script>
```

## Server Side

The server-side script intercept the output buffer and will remove the sent cookies when user has not yet approved the
agreement.

Then you should include the file `eucookielaw-header.php` as the first operation on your server.
This will ensure you that any of your script or CMS like Wordpress, Drupal, Joomla or whatever you are using, is able to 
write a cookie if the user doesn't given his consensus.

```php
// This must be the first line of code of your main, always called, file.
require_once 'eucookielaw-header.php'; 
```

However if the server already detected that the user agreed the cookie law agreement the 
script does not override the built-in function.

## CSS Cookie Banner Customization
The structure of generated banner is the following:

```html
<div class="eucookielaw-banner" id="eucookielaw-135">
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

# Donations
If you find this script useful, and since I've noticed that nobody did this script before of me, 
I'd like to receive [a donation](https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=me%40diegolamonica%2einfo&lc=IT&item_name=EU%20Cookie%20Law&no_note=0&currency_code=EUR&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHostedGuest).   :)