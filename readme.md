# EUCookieLaw

  EUROPA websites must follow the Commission's guidelines on [privacy and data protection](http://ec.europa.eu/ipg/basics/legal/data_protection/index_en.htm) and inform 
  users that cookies are not being used to gather information unnecessarily.
   
  The [ePrivacy directive](http://eur-lex.europa.eu/LexUriServ/LexUriServ.do?uri=CELEX:32002L0058:EN:HTML) – more specifically Article 5(3) – requires prior informed consent for storage for access to information stored on a user's terminal equipment. 
  In other words, you must ask users if they agree to most cookies and similar technologies (e.g. web beacons, Flash cookies, etc.) before the site starts to use them.

  For consent to be valid, it must be informed, specific, freely given and must constitute a real indication of the individual's wishes.

In this context this class lives.
It simply alters the default `document.cookie` behavior to disallow cookies to be written on the client side, until the user accept the agreement.

## How to use

The only thing you really need to download is the script file `EUCookieLaw.js` 

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

## Customize the behavior
the `EUCookieLaw` initialization expect an Object with the following properties:
* `message` is the message used by the default confirmation dialog.
* `showAgreement` is the callback method that will show the dialog with the user agreement about the cookie usage. If you use a syncronous mode to show a dialog (eg. `confirm` method) 
  the `showAgreement` must return `true` if the user have accepted the agreement, in all other cases (user rejected the agreement or in asyncronous mode) it must return `false`.   

once `UECookieLaw` is initialized, you can access some useful methods in your JavaScript:

* `enableCookies` enables the site to store cookies
* `isRejected` if the user have rejected the request to store cookie
* `isCookieEnabled` if the site can store cookies

### Custom agreement example

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

# Donations
If you find this script useful, and since I've noticed that nobody did this script before of me, 
I'd like to receive [a donation](https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=me%40diegolamonica%2einfo&lc=IT&item_name=Diego%20La%20Monica&no_note=0&currency_code=EUR&bn=PP%2dDonationsBF%3abtn_donate_SM%2egif%3aNonHostedGuest).   :)