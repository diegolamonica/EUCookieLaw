<?php
/*
 * Plugin Name: EUCookieLaw
 * Plugin URI: https://github.com/diegolamonica/EUCookieLaw
 * Description: A simple WP solution to the European Cookie Law Issue
 * Author: Diego La Monica
 * Version: 20150530
 * Author URI: http://diegolamonica.info
 * Text Domain: EUCookieLaw
 * Domain Path: /languages
*/

Class EUCookieLaw{

	static $initialized = false;

	const TEXTDOMAIN    = 'EUCookieLaw';
	const CUSTOMDOMAIN  = 'EUCookieLawCustom';
	const MENU_SLUG	    = 'EUCookieLaw';
	const VERSION       = '20150530';
	const CSS           = 'EUCookieLaw_css';
	const JS            = 'EUCookieLaw_js';
	const WPJS          = 'wpEUCookieLaw_js';

	const OPT_TITLE     = 'eucookie_law_title';
	const OPT_MESSAGE   = 'eucookie_law_description';
	const OPT_AGREE     = 'eucookie_law_agree';
	const OPT_DISAGREE  = 'eucookie_law_disagree';

	private $PLUGIN_DIRECTORY;

	public function EUCookieLaw(){
		self::$initialized = true;
		$this->PLUGIN_DIRECTORY =  dirname(__FILE__);

		add_action( 'plugins_loaded',       array($this, 'loadTranslations') );
		add_action('init',                  array($this,'init'));
		add_action('wp_enqueue_scripts',    array($this, 'script') );
		add_action('login_enqueue_scripts', array($this, 'script') );
		add_filter('admin_menu',            array($this, 'admin'));

	}

	public function loadTranslations(){
		error_log('loading translations');
		$locale = get_locale();

		load_plugin_textdomain( __CLASS__, FALSE, basename( dirname( __FILE__ ) ) . '/languages/' );
		load_plugin_textdomain( self::CUSTOMDOMAIN, FALSE, basename( dirname( __FILE__ ) ) .'Custom/' );
	}

	public function init(){
		require $this->PLUGIN_DIRECTORY . '/eucookielaw-header.php';
	}

	public function script(){
		wp_register_script(self::JS, plugins_url('/EUCookieLaw.js', __FILE__) , array(), self::VERSION, false);
		wp_register_script(self::WPJS, plugins_url('/wpEUCookieLaw.js', __FILE__) , array(self::JS), self::VERSION, false);
		wp_register_style(self::CSS, plugins_url('/eucookielaw.css', __FILE__), array(), self::VERSION, 'screen');

		$bannerTitle    = get_option(self::OPT_TITLE, 'Banner title');
		$bannerMessage  = get_option(self::OPT_MESSAGE, 'Banner message');
		$bannerAgree    = get_option(self::OPT_AGREE, 'I agree');
		$bannerDisagree = get_option(self::OPT_DISAGREE, 'I disagree') ;

		// Localize the script with new data
		$configuration = array(
			'showBanner'    => true,
			'reload'        => true,
			'bannerTitle'   => __($bannerTitle, self::CUSTOMDOMAIN ),
			'message'       => __($bannerMessage, self::CUSTOMDOMAIN),
			'agreeLabel'    => __($bannerAgree, self::CUSTOMDOMAIN),
			'disagreeLabel' => __($bannerDisagree, self::CUSTOMDOMAIN)
		);
		wp_localize_script(self::JS, 'euCookieLawConfig', $configuration );

		wp_enqueue_style(self::CSS);
		wp_enqueue_script(self::WPJS);
	}

	public function admin(){
		add_menu_page(
			"EU Cookie Law", "EU Cookie Law",
			'read',
			self::MENU_SLUG,
			array($this, 'about'));
		add_submenu_page(self::MENU_SLUG, "All you need to know about EUCookieLaw", "About", "read", self::MENU_SLUG, array($this, 'about'));
		add_submenu_page(self::MENU_SLUG, "The output banner management", "Banner", "read", self::MENU_SLUG.'-messages', array($this, 'messages'));

	}

	public function about(){

		$screen = WP_Screen::get();
		add_meta_box(
			'eucookielaw-about' . $screen->id,
			__('About EUCookieLaw', self::TEXTDOMAIN),
			array($this, 'aboutPlugin'),
			$screen, 'normal',	'high'
		);

		add_meta_box(
			'eucookielaw-css' . $screen->id,
			__('CSS Cookie Banner Customization', self::TEXTDOMAIN),
			array($this, 'customizeAspect'),
			$screen, 'normal',	'high'
		);
		add_meta_box(
			'eucookielaw-donation' . $screen->id,
			__('Donation', self::TEXTDOMAIN),
			array($this, 'donations'),
			$screen, 'side',	'high'
		);

		$this->buildScreen($screen);

	}

	private function buildScreen($screen){
		add_screen_option('layout_columns', array('max' => 2, 'default' => 2) );
		?>
		<div class="wrap">
			<h2>About EUCookieLaw</h2>
			<div id="poststuff">
				<form name="post" method="post" novalidate="novalidate">
					<div id="post-body" class="metabox-holder columns-<?php echo 1 == get_current_screen()->get_columns() ? '1' : '2'; ?>">
						<div id="postbox-container-1" class="postbox-container">
							<?php do_meta_boxes($screen, 'side', $screen); ?>
						</div>
						<div id="postbox-container-2" class="postbox-container">
							<?php do_meta_boxes($screen, 'normal', $screen); ?>
						</div>
					</div>
				</form>
			</div>
		</div>
	<?php
	}

	public function aboutPlugin(){
		?>
		<p>
			EUROPA websites must follow the Commission's guidelines on <a href="http://ec.europa.eu/ipg/basics/legal/data_protection/index_en.htm">privacy and data protection</a>
			and inform users that cookies are not being used to gather information unnecessarily.
		</p>
		<p>
			The <a href="http://eur-lex.europa.eu/LexUriServ/LexUriServ.do?uri=CELEX:32002L0058:EN:HTML">ePrivacy directive</a> –
			more specifically Article 5(3) – requires prior informed consent for storage for access to information stored on a user's
			terminal equipment. In other words, you must ask users if they agree to most cookies and similar technologies (e.g. web beacons,
			Flash cookies, etc.) before the site starts to use them.
		</p>
		<p>
			For consent to be valid, it must be informed, specific, freely given and must constitute a real indication of the individual's wishes.
		</p>
		<p>
			In this context this plugin lives. On the client side, it alters the default document.cookie behavior to disallow cookies to be written, until the user accept the agreement. On the server side it will block server generated cookies until the client will accept the agreement.
		</p>

	<?php
	}

	public function customizeAspect(){
		?>
		<p>
			The structure of generated banner is the following:
		</p>

		<div class="highlight highlight-html"><pre>
		&lt;<span class="pl-ent">div</span> <span class="pl-e">class</span>=<span class="pl-s"><span class="pl-pds">"</span>eucookielaw-banner<span class="pl-pds">"</span></span> <span class="pl-e">id</span>=<span class="pl-s"><span class="pl-pds">"</span>eucookielaw-135<span class="pl-pds">"</span></span>&gt;
		  &lt;<span class="pl-ent">div</span> <span class="pl-e">class</span>=<span class="pl-s"><span class="pl-pds">"</span>well<span class="pl-pds">"</span></span>&gt;
		    &lt;<span class="pl-ent">h1</span> <span class="pl-e">class</span>=<span class="pl-s"><span class="pl-pds">"</span>banner-title<span class="pl-pds">"</span></span>&gt;The banner title&lt;/<span class="pl-ent">h1</span>&gt;
		    &lt;<span class="pl-ent">p</span> <span class="pl-e">class</span>=<span class="pl-s"><span class="pl-pds">"</span>banner-message<span class="pl-pds">"</span></span>&gt;The banner message&lt;/<span class="pl-ent">p</span>&gt;
		    &lt;<span class="pl-ent">p</span> <span class="pl-e">class</span>=<span class="pl-s"><span class="pl-pds">"</span>banner-agreement-buttons text-right<span class="pl-pds">"</span></span>&gt;
		      &lt;<span class="pl-ent">a</span> <span class="pl-e">class</span>=<span class="pl-s"><span class="pl-pds">"</span>disagree-button btn btn-danger<span class="pl-pds">"</span></span> <span class="pl-e">onclick</span>=<span class="pl-s"><span class="pl-pds">"</span>(new EUCookieLaw()).reject();<span class="pl-pds">"</span></span>&gt;Disagree&lt;/<span class="pl-ent">a</span>&gt;
		      &lt;<span class="pl-ent">a</span> <span class="pl-e">class</span>=<span class="pl-s"><span class="pl-pds">"</span>agree-button btn btn-primary<span class="pl-pds">"</span></span> <span class="pl-e">onclick</span>=<span class="pl-s"><span class="pl-pds">"</span>(new EUCookieLaw()).enableCookies();<span class="pl-pds">"</span></span>&gt;Agree&lt;/<span class="pl-ent">a</span>&gt;
		    &lt;/<span class="pl-ent">p</span>&gt;
		  &lt;/<span class="pl-ent">div</span>&gt;
		&lt;/<span class="pl-ent">div</span>&gt;</pre></div>

		<ul>
			<li><code>.eucookielaw-banner</code> is the banner container it will have a random <code>id</code> attribute name that
				starts always with <code>eucookielaw-</code> and then followed by a number between <code>0</code> and <code>200</code>.</li>
			<li><code>.well</code> is the inner container</li>
			<li><code>h1.banner-title</code> is the banner title</li>
			<li><code>p.banner-message</code> is the banner html message</li>
			<li><code>p.banner-agreement-buttons.text-right</code> is the buttons container for the agree/disagree buttons</li>
			<li><code>a.disagree-button</code> is the disagree button it implements the CSS classes <code>btn</code> and <code>btn-danger</code></li>
			<li><code>a.disagree-button</code> is the agree button it implements the CSS classes <code>btn</code> and <code>btn-primary</code></li>
		</ul>
		<p>

			You can make your own CSS to build a custom aspect for the banner. However, if you prefer, you can start from the bundled CSS.
		</p>

	<?php
	}

	public function donations(){
		?>
		<p>
			<?php echo sprintf(
				__("If you find this plugin useful, and since I've noticed that nobody did this script (as is) before of me, " .
				   "I'd like to receive <a href=\"%s\">a donation</a> as thankful for the time You've earned for you, your ".
				   "family and your hobbies! :)", self::TEXTDOMAIN),
					"https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=me%40diegolamonica%2einfo&lc=IT&item_name=EU%20Cookie%20Law&no_note=0&currency_code=EUR&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHostedGuest"); ?>
		</p>
		<p>
			<?php _e('You can find further informations about this plugin on <a href="https://github.com/diegolamonica/EUCookieLaw/">GitHub</a>', self::TEXTDOMAIN); ?>
		</p>
	<?php
	}

	public function messages(){

		$screen = WP_Screen::get();
		add_meta_box(
			'eucookielaw-message' . $screen->id,
			__('EUCookieLaw messages', self::TEXTDOMAIN),
			array($this, 'outputMessages'),
			$screen, 'normal',	'high'
		);
		add_meta_box(
			'eucookielaw-message-support' . $screen->id,
			__('Support', self::TEXTDOMAIN),
			array($this, 'outputMessagesSupport'),
			$screen, 'side',	'high'
		);
		$this->buildScreen($screen);
	}

	public function outputMessages(){

		if(isset($_POST['nonce']) && wp_verify_nonce($_POST['nonce'], __CLASS__)){
			update_option(self::OPT_TITLE, $_POST['banner_title']);
			update_option(self::OPT_MESSAGE, $_POST['banner_message']);
			update_option(self::OPT_AGREE, $_POST['banner_agree']);
			update_option(self::OPT_DISAGREE, $_POST['banner_disagree']);

		}
		$bannerTitle    = get_option(self::OPT_TITLE, 'Banner title');
		$bannerMessage  = get_option(self::OPT_MESSAGE, 'Banner message');
		$bannerAgree    = get_option(self::OPT_AGREE, 'I agree');
		$bannerDisagree = get_option(self::OPT_DISAGREE, 'I disagree');

		?>
		<table class="form-table">
			<tr>
				<th scope="row"><label for="banner_title"><?php _e("Banner Title", self::TEXTDOMAIN); ?></label></th>
				<td><input name="banner_title" type="text" id="banner_title" value="<?php echo $bannerTitle; ?>" class="regular-text"></td>
			</tr>
			<tr>
				<th scope="row"><label for="banner_message"><?php _e("Banner Description", self::TEXTDOMAIN); ?></label></th>
				<td><textarea name="banner_message" id="banner_message" cols="30" rows="5" class="large-text"><?php echo htmlspecialchars( $bannerMessage ); ?></textarea>
			</tr>
			<tr>
				<th scope="row"><label for="banner_agree"><?php _e("Banner Agree button", self::TEXTDOMAIN); ?></label></th>
				<td><input name="banner_agree" type="text" id="banner_agree" value="<?php echo $bannerAgree; ?>" class="regular-text"></td>
			</tr>
			<tr>
				<th scope="row"><label for="banner_disagree"><?php _e("Banner Disagree button", self::TEXTDOMAIN); ?></label></th>
				<td><input name="banner_disagree" type="text" id="banner_disagree" value="<?php echo $bannerDisagree; ?>" class="regular-text"></td>
			</tr>
		</table>
		<p>
			<input type="hidden" name="nonce" value="<?php echo wp_create_nonce(__CLASS__); ?>" />
			<input type="submit" name="submit" id="submit" class="button button-primary" value="Salva le modifiche">
		</p>
	<?php
	}

	public function outputMessagesSupport(){
		?>
		<p>
			<?php _e("In the <code>Banner description</code> field you can write HTML.", self::TEXTDOMAIN); ?>
		</p>
	<?php
	}
}
if(!EUCookieLaw::$initialized) {
	new EUCookieLaw();
}