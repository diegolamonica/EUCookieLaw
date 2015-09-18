<?php
/**
 * EUCookieLaw: EUCookieLaw a complete solution to accomplish european law requirements about cookie consent
 * @link https://github.com/diegolamonica/EUCookieLaw/
 * @author Diego La Monica (diegolamonica) <diego.lamonica@gmail.com>
 * @copyright 2015 Diego La Monica <http://diegolamonica.info>
 * @license http://www.gnu.org/licenses/lgpl-3.0-standalone.html GNU Lesser General Public License
 * @note This program is distributed in the hope that it will be useful - WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */


Class EUCookieLaw {

	static $initialized = false;

	const TEXTDOMAIN = 'EUCookieLaw';
	const CUSTOMDOMAIN = 'EUCookieLawCustom';
	const MENU_SLUG = 'EUCookieLaw';
	const VERSION = '2.6.2';
	const CSS = 'EUCookieLaw_css';
	const CUSTOMCSS = 'EUCookieLaw_css_custom';
	const JS = 'EUCookieLaw_js';
	const WPJS = 'wpEUCookieLaw_js';

	const OPT_TITLE = 'eucookie_law_title';
	const OPT_MESSAGE = 'eucookie_law_description';
	const OPT_AGREE = 'eucookie_law_agree';
	const OPT_DISAGREE = 'eucookie_law_disagree';
	const OPT_SCROLL_PX = 'eucookie_law_min_scroll';

	const OPT_TITLE_TAG = 'eucookie_law_title_tag';
	const OPT_3RDPDOMAINS = 'eucookie_law_3rdparty_domain';
	const OPT_LOOKINSCRIPTS = 'eucookie_law_inscript';
	const OPT_LOOKINTAGS = 'eucookie_law_lookintags';
	const OPT_RELOAD = 'eucookie_law_reload';
	const OPT_ENABLED = 'eucookie_law_enabled';
	const OPT_ENABLEDONLOGIN = 'eucookie_law_enabled_on_login';
	const OPT_BOT_AS_HUMANS = 'eucookie_law_bot_as_humans';

	const OPT_DEFAULT_LOOKINTAGS = 'script|iframe|img|embed|param';
	const OPT_AGREEONSCROLL = 'eucookie_law_agree_on_scroll';
	const OPT_AGREEONCLICK = 'eucookie_law_agree_on_click';
	const OPT_FIXED_ON = 'eucookie_law_banner_fixed_on';
	const OPT_BANNER_STYLE = 'eucookie_law_banner_style';
	const OPT_REMEMBER_CHOICE = 'eucookie_law_remember_choice';

	const OPT_COOKIE_EXPIRES = 'eucookie_law_banner_cookie_expires';
	const OPT_WHITELIST_COOKIES = 'eucookie_law_whitelist_cookies';
	const OPT_DEBUG = 'eucookie_law_debug';
	const OPT_LOGFILE = 'eucookie_law_logfile';

	const OPT_DEBUG_VERBOSITY = 'eucookie_law_debug_verbosity';

	const OPT_ENGINE = 'eucookie_law_engine';
	const OPT_DEFAULT_IFRAME_SRC = 'default_iframe_src';
	const OPT_DEFAULT_SCRIPT_SRC = 'default_script_src';
	const OPT_DEFAULT_IMAGE_SRC = 'default_image_src';



	const OPT_UNAPPLY_ON_URL = 'eucookielaw_unapply_on_url';

	const COOKIE_NAME = '__eucookielaw';

	const ERR_MSG_CHECK_PERMS_OR_DIY = 'Check your permissions or put this data into the file <code>%s</code>:';
	const ERR_MSG_FILE_UPDATED = 'File <code>%s</code> updated!';

	const WPC_FILE_NOT_FOUND = 0; # Only for clean WP installations
	const WPC_NOT_SAME_VERSION = 1;
	const WPC_FILE_ORIGINAL = 2;
	const WPC_FILE_THE_SAME = 3;

	private $PLUGIN_DIRECTORY;

	private $showMergeButton = false;

	public function EUCookieLaw() {
		self::$initialized      = true;
		$this->PLUGIN_DIRECTORY = dirname( __FILE__ );

		add_action( 'plugins_loaded', array( $this, 'loadTranslations' ) );

		add_action( 'init', array( $this, 'init' ), - 10 );

		if ( is_admin() ) {

			if ( ( isset( $_GET['write'] ) || count( $_POST ) > 0 ) && isset( $_GET['page'] ) && substr( $_GET['page'], 0, strlen( __CLASS__ ) ) == __CLASS__ ) {
				add_action( 'admin_notices', array( $this, 'writeConfig' ) );
			}

			add_action( 'admin_notices', array( $this, 'notifyDifferences' ) );
			add_action( 'admin_notices', array( $this, 'notifyDebug' ) );

			require_once 'editor.php';
			new EUCookieLawTinyMCE();

			add_filter( 'admin_menu', array( $this, 'admin' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'adminScripts' ) );
		} else {
			add_action( 'wp_enqueue_scripts', array( $this, 'script' ) );
			if ( get_option( self::OPT_ENABLEDONLOGIN, 'y' ) == 'y' && $this->isLoginPage() ) {
				add_action( 'login_enqueue_scripts', array( $this, 'script' ) );
			}
		}

		add_action('w3tc_pgcache_flush', array($this, 'pgCacheFlush'));
	}

	public function loadTranslations() {

		load_plugin_textdomain( __CLASS__, false, basename( dirname( __FILE__ ) ) . '/languages/' );

		load_plugin_textdomain( self::CUSTOMDOMAIN, false, 'EUCookieLawCustom/' );
	}

	private function checkWPConfigFile( $wpConfigTemplate ) {
		$wpConfigFile = ABSPATH . 'wp-config.php';
		$response     = self::WPC_FILE_THE_SAME;

		if ( file_exists( WP_CONTENT_DIR . 'cache/' ) ) {
			if ( ! file_exists( $wpConfigFile ) ) {
				$response = self::WPC_FILE_NOT_FOUND;
			} else {

				$fileContent = @file_get_contents( $wpConfigFile );

				if ( strpos( $fileContent, $wpConfigTemplate ) === false ) {
					/*
					 * wp-config file does not have EUCookieLaw Cache settings
					 * Checking for recognizable data.
					 */
					$blockStartEnd = preg_quote( '--- EUCookieLaw ---', '#' );
					if ( preg_match( '#' . $blockStartEnd . '(.*?)' . $blockStartEnd . '#ims', $fileContent, $items ) ) {
						preg_match( '#Version:\s([0-9.]+)#', $items[1], $versionInfo );
						if ( $versionInfo[1] !== self::VERSION ) {
							$response = self::WPC_NOT_SAME_VERSION;
						}

					} else {

						$response = self::WPC_FILE_ORIGINAL;

					}

				}

			}
		}

		return $response;

	}

	private function getTemplateFile( $file, $apply = true ) {
		$replacements = array(
			'VERSION'    => self::VERSION,
			'DIR'        => dirname( __FILE__ ),
			'WP_CONTENT' => WP_CONTENT_DIR,
			'CACHE_DIR'  => WP_CONTENT_DIR . '/cache',
			'W3TC_DIR'   => WP_CONTENT_DIR . '/cache/page_enhanced',
			'ZENC_DIR'   => WP_CONTENT_DIR . '/cache/zencache',
			'ABSPATH'    => ABSPATH
		);

		if ( ! $template = @file_get_contents( dirname( __FILE__ ) . '/templates/' . $file ) ) {
			error_log( "unable to read $file" );

			return false;

		} else {

			if ( $apply ) {
				foreach ( $replacements as $key => $value ) {
					$template = str_replace( '%%' . $key . '%%', $value, $template );

				}
			}

			return $template;
		}

	}

	private function notifyMessage( $message, $type = 'updated', $code = '' ) {
		?>
		<div class="<?php echo $type ?>">
			<p>
				<?php echo $message; ?>
			</p>
			<?php
			if ( ! empty( $code ) ) {
				?>
				<pre><?php echo htmlspecialchars( $code ); ?></pre>
			<?php
			}
			?>
		</div>
	<?php
	}


	private function updateWPConfig() {

		$advancedCacheTemplate = $this->getTemplateFile( 'wp-config.fragment.php' );

		$config = file( ABSPATH . 'wp-config.php' );

		$newWPConfig          = array();
		$contentBlockStartEnd = '# --- EUCookieLaw ---';
		$ignore               = false;
		$nested               = false;
		# print_r($config);
		$applyAtLine   = 0;
		$addToWPConfig = array();
		foreach ( $config as $line => $row ) {
			# We should put our fragment before the first require content
			if ( ! $ignore && ! $nested && preg_match( '#^\s*require_once#', $row ) ) {


				$addToWPConfig[] = "$contentBlockStartEnd\n";
				$addToWPConfig[] = "# Added by EUCookieLaw\n";
				$addToWPConfig[] = "# Version: " . self::VERSION . "\n";
				$addToWPConfig[] = $advancedCacheTemplate . "\n";
				$addToWPConfig[] = '# End additions by EUCookieLaw' . "\n";
				$addToWPConfig[] = '# --- EUCookieLaw ---' . "\n";

				$newWPConfig[] = implode( "", $addToWPConfig );
				$newWPConfig[] = $row;

				$nested      = true;
				$applyAtLine = $line + 1;
			} else {
				if ( preg_match( '#' . preg_quote( $contentBlockStartEnd, '#' ) . '#', $row ) ) {

					if ( $ignore ) {
						# error_log("Ending Ignoring rows");
						$ignore = false;
					} else {
						# error_log("Starting Ignoring rows");
						$ignore = true;
					}

				} else {

					if ( ! $ignore ) {
						# error_log("writing original row");
						$newWPConfig[] = $row;
					} else {
						# error_log("Ignoring rows");
					}
				}
			}
		}


		if ( ! @file_put_contents( ABSPATH . 'wp-config.php', implode( "", $newWPConfig ) ) ) {
			$this->notifyMessage( sprintf(
				__( self::ERR_MSG_CHECK_PERMS_OR_DIY, self::TEXTDOMAIN ),
				ABSPATH . 'wp-config.php' ), 'error',
				"# Insert at Line #$applyAtLine:\n" . implode( "", $addToWPConfig )
			);
		} else {
			$this->notifyMessage( sprintf(
					__( self::ERR_MSG_FILE_UPDATED, self::TEXTDOMAIN ), ABSPATH . 'wp-config.php' )
			);
		}

	}

	private function updateHtaccess( $directory ) {
		$template = $this->getTemplateFile( 'htaccess.fragment.txt' );

		if ( $htaccess = @file_get_contents( $directory . '/.htaccess' ) ) {

			if ( ( strpos( $htaccess, $template ) !== false ) || @file_put_contents( $directory . '/.htaccess', $htaccess . $template ) ) {

				$this->notifyMessage( sprintf(
					__( self::ERR_MSG_FILE_UPDATED, self::TEXTDOMAIN ),
					$directory . '/.htaccess'
				) );

				$htaccess .= $template;
			}

		}

		if ( strpos( $htaccess, $template ) === false ) {

			if ( ! is_bool( $htaccess ) ) {
				$template = $htaccess . $template;
			}

			$this->notifyMessage(
				sprintf(
					__( self::ERR_MSG_CHECK_PERMS_OR_DIY, self::TEXTDOMAIN ), $directory . '/.htaccess' ),
				'error',
				$template );
		}

	}

	private function writeFileForCachePlugin( $directory ) {

		$phpFile = $directory . '/EUCookieCache.php';

		if(file_exists($phpFile.'.old')) @unlink($phpFile.'.old');

		$template = $this->getTemplateFile( 'EUCookieCache.php' );
		if ( ! @file_put_contents( $phpFile, $template ) ) {
			$this->notifyMessage(
				sprintf(
					__( 'Unable to write the file <code>%s</code>', self::TEXTDOMAIN ) . '<br />' .
					__( self::ERR_MSG_CHECK_PERMS_OR_DIY, self::TEXTDOMAIN ),
					$phpFile
				),
				'error', $template );
		} else {

			$this->notifyMessage( sprintf(
				__( self::ERR_MSG_FILE_UPDATED, self::TEXTDOMAIN ),
				$phpFile
			) );
		}

		# Updating .htaccess file
		$this->updateHtaccess( $directory );

	}

	public function pgCacheFlush(){

		add_action('w3_redirect', array($this, 'pubUpdateCacheDirectory' ));
	}

	public function pubUpdateCacheDirectory(){

		$this->updateCacheDirectory();

	}

	private function updateCacheDirectory() {


		# Needed only for W3TC
		if ( file_exists( WP_CONTENT_DIR . '/cache/page_enhanced' ) ) {

			$this->writeFileForCachePlugin( WP_CONTENT_DIR . '/cache/page_enhanced' );

		}

		/*
		 * Not needed for ZenCache and WP Super Cache
		 */
	}

	private function hasCache() {
		return ( file_exists( WP_CONTENT_DIR . '/cache' ) && is_dir( WP_CONTENT_DIR . '/cache' ) );
	}

	private function updateIniFile() {

		if ( $this->hasCache() ) {

			$domains = get_option( self::OPT_3RDPDOMAINS );
			$iniFile = array(

				self::OPT_3RDPDOMAINS        => preg_replace( "#\r?\n#", ";", $domains ),
				self::OPT_AGREE              => get_option( self::OPT_AGREE ),
				self::OPT_COOKIE_EXPIRES     => get_option( self::OPT_COOKIE_EXPIRES ),
				self::OPT_DEBUG              => ( get_option( self::OPT_DEBUG ) !== 'n' ) ? 'y' : 'n',
				self::OPT_DEBUG_VERBOSITY    => ( get_option( self::OPT_DEBUG_VERBOSITY, '99')),
				self::OPT_LOGFILE            => ( get_option( self::OPT_DEBUG ) === 'file' ) ? WP_PLUGIN_DIR . '/' . self::CUSTOMDOMAIN . '/log.txt' : '',
				self::OPT_DISAGREE           => get_option( self::OPT_DISAGREE ),
				self::OPT_ENABLED            => get_option( self::OPT_ENABLED ),
				self::OPT_FIXED_ON           => get_option( self::OPT_FIXED_ON ),
				self::OPT_LOOKINSCRIPTS      => get_option( self::OPT_LOOKINSCRIPTS ),
				self::OPT_LOOKINTAGS         => get_option( self::OPT_LOOKINTAGS ),
				self::OPT_MESSAGE            => get_option( self::OPT_MESSAGE ),
				self::OPT_TITLE              => get_option( self::OPT_TITLE ),
				self::OPT_TITLE_TAG          => get_option( self::OPT_TITLE_TAG ),
				self::OPT_WHITELIST_COOKIES  => get_option( self::OPT_WHITELIST_COOKIES ),
				self::OPT_BOT_AS_HUMANS      => get_option( self::OPT_BOT_AS_HUMANS ),
				self::OPT_BANNER_STYLE       => get_option( self::OPT_BANNER_STYLE, '' ),
				self::OPT_ENGINE             => get_option( self::OPT_ENGINE, 'regexp' ),
				self::OPT_DEFAULT_IFRAME_SRC => get_option( self::OPT_DEFAULT_IFRAME_SRC, 'about:blank' ),
				self::OPT_DEFAULT_SCRIPT_SRC => get_option( self::OPT_DEFAULT_SCRIPT_SRC, 'about:blank' ),
				self::OPT_DEFAULT_IMAGE_SRC  => get_option( self::OPT_DEFAULT_IMAGE_SRC, 'about:blank' ),
				self::OPT_UNAPPLY_ON_URL     => get_option( self::OPT_UNAPPLY_ON_URL, ''),

			);
			$file    = WP_CONTENT_DIR . '/cache/eucookielaw.ini';
			$config  = '';
			foreach ( $iniFile as $key => $value ) {
				$config .= $key . '="' . str_replace( '"', '""', $value ) . "\"\n";
			}

			if ( @file_put_contents( $file, $config ) ) {
				$this->notifyMessage( sprintf(
						__( self::ERR_MSG_FILE_UPDATED, self::TEXTDOMAIN ),
						$file )
				);
			} else {
				$this->notifyMessage(
					sprintf(
						__( 'Error writing configuration file <code>%s</code>!', self::TEXTDOMAIN ) . '<br />' .
						__( self::ERR_MSG_CHECK_PERMS_OR_DIY, self::TEXTDOMAIN ),
						WP_CONTENT_DIR . '/cache/eucookielaw.ini'
					),
					'error', $config
				);
			}
		}
	}

	public function writeConfig() {

		$this->updateOptions();
		if ( $this->hasCache() ) {
			$this->updateWPConfig();
			$this->updateCacheDirectory();
			$this->updateIniFile();
		}

	}

	public function notifyDebug() {
		if ( get_option( self::OPT_DEBUG, 'n' ) !== 'n' ) {
			$this->notifyMessage( __( "Warning: EUCookieLaw debug is enabled. Disable it once you've done your tests" ), "updated alert" );
		}
	}

	public function notifyDifferences() {

		$this->showMergeButton = false;

		if ( in_array(
			$this->checkWPConfigFile( $this->getTemplateFile( 'wp-config.fragment.php' ) ),
			array( self::WPC_NOT_SAME_VERSION, self::WPC_FILE_ORIGINAL )
		)
		) {
			$this->showMergeButton = true;
			$this->notifyMessage(
				sprintf(
					__( "EUCookieLaw would not work with your cache until you will not <a href='%s'>go to the settings page</a> and execute the <strong>%s</strong> action (in the sidebar).", self::TEXTDOMAIN ),

					admin_url( 'admin.php?page=' . __CLASS__ . '-messages' ),
					__( 'Merge with cache plugin', self::TEXTDOMAIN )
				), 'error'
			);
		}

	}

	private function isLoginPage() {
		return in_array(
			$GLOBALS['pagenow'],
			array( 'wp-login.php', 'wp-register.php' )
		);
	}

	public function init() {

		$enabled = get_option( self::OPT_ENABLED, 'y' );
		if ( $this->isLoginPage() && get_option( self::OPT_ENABLEDONLOGIN, 'y' ) == 'n' ) {
			$enabled = 'n';
		}
		if ( $enabled == 'n' || is_admin() ) {

			if ( ! defined( 'EUCOOKIELAW_DISABLED' ) ) {
				define( 'EUCOOKIELAW_DISABLED', true );
			}

		} else {

			define( 'EUCOOKIELAW_FORCE_AS_CACHE', true );
			require $this->PLUGIN_DIRECTORY . '/eucookielaw-cache.php';
		}

		add_shortcode( 'EUCookieLawReconsider', array( $this, 'reconsider' ) );
		add_filter( 'the_content', array( $this, 'block' ), - 1 );
	}

	public function reconsider( $atts ) {

		$label = '';
		extract( shortcode_atts( array(
			'label' => 'Reconsider'
		), $atts ) );

		$url = preg_replace( '#(\?|&)__eucookielaw=([^&]+)(&?(.*))#', '$1$4', $_SERVER['REQUEST_URI'] );
		$url = preg_replace( '#(\?|&)$#', '', $url );
		$url .= ( preg_match( '#\?#', $url ) ? '&' : '?' ) . '__eucookielaw=reconsider';

		return '<a class="btn btn-warning eucookielaw-reconsider-button" rel="nofollow" href="' . $url . '" onclick="(new EUCookieLaw()).reconsider(); return false;">' . __( $label, self::CUSTOMDOMAIN ) . '</a>';
	}

	public function block( $content ) {
		$content = preg_replace( '#(\r?\n)?\[EUCookieLawBlock\](\r?\n)?#', '<!-- EUCookieLaw:start -->', $content );
		$content = preg_replace( '#(\r?\n)?\[/EUCookieLawBlock\](\r?\n)?#', '<!-- EUCookieLaw:end -->', $content );

		return $content;

	}

	public function adminScripts() {

		wp_enqueue_script( __CLASS__, plugin_dir_url( __FILE__ ) . 'EUCookieLaw-admin.js', array( 'jquery' ), self::VERSION, true );
	}

	public function ignoredURL(){
		$enabled    = get_option( self::OPT_ENABLED, 'n' );

		if($enabled == 'y') {

			$urls = get_option(self::OPT_UNAPPLY_ON_URL, '');
			if($urls !== ''){

				$ru = $_SERVER['REQUEST_URI'];

				$url = explode("\n", $urls);
				foreach($url as $u){

					$u = explode("*", $u);
					foreach($u as $index => $value)
						$u[$index] = preg_quote($value, '/');

					$u = implode(".*", $u);

					if( preg_match("/^" . $u . "$/", $ru)) return true;

				}

			};

		}

		return false;

	}

	public function script() {
		$enabled    = get_option( self::OPT_ENABLED, 'n' );
		$hasEnabled = get_option( self::OPT_ENABLED, false );
		$hasTitle   = get_option( self::OPT_TITLE, false );

		if( $this->ignoredURL() ){
			$enabled = 'n';
		}else{

			if ( ! $hasEnabled && $hasTitle ) {
				$enabled = 'y';
			}
		}

		if ( $enabled == 'y' ) {
			wp_register_script( self::JS, plugins_url( '/EUCookieLaw.js', __FILE__ ), array(), self::VERSION, false );
			wp_register_script( self::WPJS, plugins_url( '/wpEUCookieLaw.js', __FILE__ ), array( self::JS ), self::VERSION, false );
			wp_register_style( self::CSS, plugins_url( '/eucookielaw.css', __FILE__ ), array(), self::VERSION, 'screen' );

			$customCSSURL = WP_PLUGIN_URL . '/' . self::CUSTOMDOMAIN . '/eucookielaw.css';
			if ( file_exists( WP_PLUGIN_DIR . '/' . self::CUSTOMDOMAIN . '/eucookielaw.css' ) ) {
				error_log( "Custom CSS '$customCSSURL' correctly attacched to the page!" );
				wp_register_style( self::CUSTOMCSS, $customCSSURL, array( self::CSS ), self::VERSION, 'screen' );
			} else {
				if ( get_option( self::OPT_DEBUG, 'n' ) == 'y' ) {
					error_log( "Custom CSS '$customCSSURL' does not exists or not reachable!" );
				}
			}

			$bannerTitle    = get_option( self::OPT_TITLE, 'Banner title' );
			$bannerMessage  = get_option( self::OPT_MESSAGE, 'Banner message' );
			$bannerAgree    = get_option( self::OPT_AGREE, 'I agree' );
			$bannerDisagree = get_option( self::OPT_DISAGREE, 'I disagree' );
			$titleTag       = get_option( self::OPT_TITLE_TAG, 'h1' );
			$agreeOnScroll  = get_option( self::OPT_AGREEONSCROLL, 'n' );
			$agreeOnClick   = get_option( self::OPT_AGREEONCLICK, 'n' );
			$fixedOn        = get_option( self::OPT_FIXED_ON, 'top' );
			$cookieDuration = get_option( self::OPT_COOKIE_EXPIRES, '365' );
			$debug          = get_option( self::OPT_DEBUG, 'n' );
			$reload         = get_option( self::OPT_RELOAD, 'y' );
			$rememberChoice = get_option( self::OPT_REMEMBER_CHOICE, 'y' );

			$whitelist = get_option( self::OPT_WHITELIST_COOKIES, array() );
			$style     = get_option( self::OPT_BANNER_STYLE, '' );
			$minScroll = get_option( self::OPT_SCROLL_PX, '100' );
			// Localize the script with new data
			# echo "The css style is: $style"; exit();


			$configuration = array(
				'showBanner'    => true,
				'reload'        => ( $reload == 'y' ),
				'debug'         => ( $debug == 'y' ),
				'bannerTitle'   => htmlspecialchars( __( $bannerTitle, self::CUSTOMDOMAIN ) ),
				'message'       => htmlspecialchars( __( $bannerMessage, self::CUSTOMDOMAIN ) ),
				'agreeLabel'    => htmlspecialchars( __( $bannerAgree, self::CUSTOMDOMAIN ) ),
				'disagreeLabel' => htmlspecialchars( __( $bannerDisagree, self::CUSTOMDOMAIN ) ),
				'tag'           => $titleTag,
				'agreeOnScroll' => ( $agreeOnScroll == 'y' ),
				'agreeOnClick'  => ( $agreeOnClick == 'y' ),
				'fixOn'         => $fixedOn,
				'duration'      => $cookieDuration,
				'remember'      => ( $rememberChoice == 'y' ),
				'cookieList'    => $whitelist,
				'classes'       => $style,
				'minScroll'     => $minScroll,
				//	'id'            => 'eucookielaw-in-html',

			);

			wp_localize_script( self::JS, 'euCookieLawConfig', $configuration );

			wp_enqueue_style( self::CSS );
			if ( file_exists( WP_PLUGIN_DIR . '/' . self::CUSTOMDOMAIN . '/eucookielaw.css' ) ) {
				wp_enqueue_style( self::CUSTOMCSS );
			}

			wp_enqueue_script( self::WPJS );
		}

	}

	public function admin() {
		add_menu_page(
			"EU Cookie Law", "EU Cookie Law",
			'activate_plugins',
			self::MENU_SLUG,
			array( $this, 'about' ) );
		add_submenu_page( self::MENU_SLUG, "All you need to know about EUCookieLaw", __( "About", self::TEXTDOMAIN ), "activate_plugins", self::MENU_SLUG, array(
			$this,
			'about'
		) );
		add_submenu_page( self::MENU_SLUG, __( "EUCookieLaw Settings", self::TEXTDOMAIN ), __( "Settings", self::TEXTDOMAIN ), "activate_plugins", self::MENU_SLUG . '-settings', array(
			$this,
			'settings'
		) );
		add_submenu_page( self::MENU_SLUG, __( "EUCookieLaw Tools", self::TEXTDOMAIN ), __( "Tools", self::TEXTDOMAIN ), "activate_plugins", self::MENU_SLUG . '-tools', array(
			$this,
			'tools'
		) );

	}

	public function about() {

		$screen = WP_Screen::get();
		add_meta_box(
			'eucookielaw-about' . $screen->id,
			__( 'About EUCookieLaw', self::TEXTDOMAIN ),
			array( $this, 'aboutPlugin' ),
			$screen, 'normal', 'high'
		);

		add_meta_box(
			'eucookielaw-css' . $screen->id,
			__( 'CSS Cookie Banner Customization', self::TEXTDOMAIN ),
			array( $this, 'customizeAspect' ),
			$screen, 'normal', 'high'
		);
		add_meta_box(
			'eucookielaw-donation' . $screen->id,
			__( 'Donation', self::TEXTDOMAIN ),
			array( $this, 'donations' ),
			$screen, 'side', 'high'
		);

		$this->buildScreen( $screen );

	}

	private function buildScreen( $screen ) {
		add_screen_option( 'layout_columns', array( 'max' => 2, 'default' => 2 ) );
		?>
		<div class="wrap">
			<h2>EUCookieLaw</h2>

			<div id="poststuff">
				<form name="post" method="post" novalidate="novalidate">
					<div id="post-body"
					     class="metabox-holder columns-<?php echo 1 == get_current_screen()->get_columns() ? '1' : '2'; ?>">
						<div id="postbox-container-1" class="postbox-container">
							<?php do_meta_boxes( $screen, 'side', $screen ); ?>
						</div>
						<div id="postbox-container-2" class="postbox-container">
							<?php do_meta_boxes( $screen, 'normal', $screen ); ?>
						</div>
					</div>
				</form>
			</div>
		</div>
	<?php
	}

	public function aboutPlugin() {
		?>
		<p>
			EUROPA websites must follow the Commission's guidelines on <a
				href="http://ec.europa.eu/ipg/basics/legal/data_protection/index_en.htm">privacy and data
				protection</a>
			and inform users that cookies are not being used to gather information unnecessarily.
		</p>
		<p>
			The <a href="http://eur-lex.europa.eu/LexUriServ/LexUriServ.do?uri=CELEX:32002L0058:EN:HTML">ePrivacy
				directive</a> –
			more specifically Article 5(3) – requires prior informed consent for storage for access to information
			stored on a user's
			terminal equipment. In other words, you must ask users if they agree to most cookies and similar
			technologies (e.g. web beacons,
			Flash cookies, etc.) before the site starts to use them.
		</p>
		<p>
			For consent to be valid, it must be informed, specific, freely given and must constitute a real
			indication of the individual's wishes.
		</p>
		<p>
			In this context this plugin lives. On the client side, it alters the default document.cookie behavior to
			disallow cookies to be written, until the user accept the agreement. On the server side it will block
			server generated cookies until the client will accept the agreement.
		</p>

	<?php
	}

	public function customizeAspect() {
		?>
		<p>
			The structure of generated banner is the following:
		</p>

		<div class="highlight highlight-html"><pre>
	&lt;<span class="pl-ent">div</span> <span class="pl-e">class</span>=<span class="pl-s"><span
						class="pl-pds">"</span>eucookielaw-banner<span class="pl-pds">"</span></span> <span
					class="pl-e">id</span>=<span class="pl-s"><span class="pl-pds">"</span>eucookielaw-135<span
						class="pl-pds">"</span></span>&gt;
	  &lt;<span class="pl-ent">div</span> <span class="pl-e">class</span>=<span class="pl-s"><span
						class="pl-pds">"</span>well<span class="pl-pds">"</span></span>&gt;
	    &lt;<span class="pl-ent">h1</span> <span class="pl-e">class</span>=<span class="pl-s"><span
						class="pl-pds">"</span>banner-title<span class="pl-pds">"</span></span>&gt;The banner title&lt;/<span
					class="pl-ent">h1</span>&gt;
	    &lt;<span class="pl-ent">p</span> <span class="pl-e">class</span>=<span class="pl-s"><span
						class="pl-pds">"</span>banner-message<span class="pl-pds">"</span></span>&gt;The banner message&lt;/<span
					class="pl-ent">p</span>&gt;
	    &lt;<span class="pl-ent">p</span> <span class="pl-e">class</span>=<span class="pl-s"><span
						class="pl-pds">"</span>banner-agreement-buttons text-right<span
						class="pl-pds">"</span></span>&gt;
	      &lt;<span class="pl-ent">a</span> <span class="pl-e">class</span>=<span class="pl-s"><span class="pl-pds">"</span>disagree-button btn btn-danger<span
						class="pl-pds">"</span></span> <span class="pl-e">onclick</span>=<span class="pl-s"><span
						class="pl-pds">"</span>(new EUCookieLaw()).reject();<span class="pl-pds">"</span></span>&gt;Disagree&lt;/<span
					class="pl-ent">a</span>&gt;
	      &lt;<span class="pl-ent">a</span> <span class="pl-e">class</span>=<span class="pl-s"><span class="pl-pds">"</span>agree-button btn btn-primary<span
						class="pl-pds">"</span></span> <span class="pl-e">onclick</span>=<span class="pl-s"><span
						class="pl-pds">"</span>(new EUCookieLaw()).enableCookies();<span
						class="pl-pds">"</span></span>&gt;Agree&lt;/<span class="pl-ent">a</span>&gt;
	    &lt;/<span class="pl-ent">p</span>&gt;
	  &lt;/<span class="pl-ent">div</span>&gt;
	&lt;/<span class="pl-ent">div</span>&gt;</pre>
		</div>

		<ul>
			<li><code>.eucookielaw-banner</code> is the banner container it will have a random <code>id</code>
				attribute name that
				starts always with <code>eucookielaw-</code> and then followed by a number between <code>0</code>
				and <code>200</code>.
			</li>
			<li><code>.well</code> is the inner container</li>
			<li><code>h1.banner-title</code> is the banner title</li>
			<li><code>p.banner-message</code> is the banner html message</li>
			<li><code>p.banner-agreement-buttons.text-right</code> is the buttons container for the agree/disagree
				buttons
			</li>
			<li><code>a.disagree-button</code> is the disagree button it implements the CSS classes <code>btn</code>
				and <code>btn-danger</code></li>
			<li><code>a.disagree-button</code> is the agree button it implements the CSS classes <code>btn</code>
				and <code>btn-primary</code></li>
		</ul>
		<p>

			You can make your own CSS to build a custom aspect for the banner. However, if you prefer, you can start
			from the bundled CSS.
		</p>

	<?php
	}

	public function donations() {
		?>
		<p>
			<?php echo sprintf(
				__( "If you find this plugin useful, and since I've noticed that nobody did this script (as is) before of me, " .
				    "I'd like to receive <a href=\"%s\">a donation</a> as thankful for the time You've earned for you, your " .
				    "family and your hobbies! :)", self::TEXTDOMAIN ),
				"https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=me%40diegolamonica%2einfo&lc=IT&item_name=EU%20Cookie%20Law&no_note=0&currency_code=EUR&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHostedGuest" ); ?>
		</p>
		<p>
			<?php _e( 'You can find further informations about this plugin on <a href="https://github.com/diegolamonica/EUCookieLaw/">GitHub</a>', self::TEXTDOMAIN ); ?>
		</p>
		<?php
		$this->displayFBLike();
	}

	public function settings() {

		$screen = WP_Screen::get();
		add_meta_box(
			'eucookielaw-banner' . $screen->id,
			__( 'Banner', self::TEXTDOMAIN ),
			array( $this, 'bannerMetabox' ),
			$screen, 'normal', 'high'
		);
		add_meta_box(
			'eucookielaw-behavior' . $screen->id,
			__( 'Behavior', self::TEXTDOMAIN ),
			array( $this, 'behaviorMetabox' ),
			$screen, 'normal', 'high'
		);

		add_meta_box(
			'eucookielaw-message-support' . $screen->id,
			__( 'Support', self::TEXTDOMAIN ),
			array( $this, 'outputMessagesSupport' ),
			$screen, 'side', 'high'
		);
		add_meta_box(
			'eucookielaw-debug'.$screen->id,
			__( 'Debug', self::TEXTDOMAIN),
			array($this, 'debugMetabox'),
			$screen, 'side', 'high'
		);
		add_meta_box(
			'eucookielaw-donation' . $screen->id,
			__( 'Donation', self::TEXTDOMAIN ),
			array( $this, 'donations' ),
			$screen, 'side', 'high'
		);
		$this->buildScreen( $screen );
	}

	public function tools() {

		$screen = WP_Screen::get();
		add_meta_box(
			'eucookielaw-tools' . $screen->id,
			__( 'Tools', self::TEXTDOMAIN ),
			array( $this, 'toolsMetabox' ),
			$screen, 'normal', 'high'
		);

		add_meta_box(
			'eucookielaw-donation' . $screen->id,
			__( 'Donation', self::TEXTDOMAIN ),
			array( $this, 'donations' ),
			$screen, 'side', 'high'
		);
		$this->buildScreen( $screen );
	}

	private function generateCustomDirectory( $mode ) {

		if ( $mode == 'default' ) {
			$mode = 'update';
		}
		$customDirectory = WP_PLUGIN_DIR . '/' . self::CUSTOMDOMAIN;
		if ( ! file_exists( $customDirectory ) ) {
			if ( mkdir( $customDirectory, 0755 ) ) {
				$this->notifyMessage( sprintf( __( "Custom directory <code>%s</code> created", self::TEXTDOMAIN ), $customDirectory ) );
			} else {
				$this->notifyMessage( sprintf( __( "Unable to create custom directory <code>%s</code>", self::TEXTDOMAIN ), $customDirectory ), 'warning' );
			}
		} else {
			$this->notifyMessage( sprintf( __( "Custom directory <code>%s</code> already exists", self::TEXTDOMAIN ), $customDirectory ) );
		}

		if ( file_exists( $customDirectory ) ) {
			$originalCustomDir = dirname( __FILE__ ) . '/' . self::CUSTOMDOMAIN;
			foreach ( glob( $originalCustomDir . '/*.*' ) as $customFile ) {
				$customFile = basename( $customFile );
				if ( $mode == 'replace' || ! file_exists( $customDirectory . '/' . $customFile ) ) {
					if ( copy( $originalCustomDir . '/' . $customFile, $customDirectory . '/' . $customFile ) ) {
						$this->notifyMessage( sprintf( __( "File <code>%s</code> updated", self::TEXTDOMAIN ), $customFile ) );
					} else {
						$this->notifyMessage( sprintf( __( "Unable create <code>%s</code>. check directory permissions", self::TEXTDOMAIN ), $customFile ), 'warning' );
					}
				} else {
					$this->notifyMessage( sprintf( __( "File <code>%s</code> exists and will not be replaced", self::TEXTDOMAIN ), $customFile ) );
				}
			}

		}

	}

	public function toolsMetabox() {

		$action    = isset( $_GET['action'] ) ? $_GET['action'] : 'none';
		$mode      = isset( $_GET['mode'] ) ? $_GET['mode'] : 'default';
		$screenURL = preg_replace( '#&action=[^&]+&?#', '&', $_SERVER['REQUEST_URI'] );
		$screenURL = preg_replace( '#&mode=[^&]+&?#', '&', $screenURL );
		$screenURL = preg_replace( '#&url=[^&]+&?#', '&', $screenURL );
		$screenURL = preg_replace( '#&+#', '&', $screenURL );
		$screenURL = preg_replace( '#&$#', '', $screenURL );
		?>
		<h4><?php _e( 'Generate Custom Directory', self::TEXTDOMAIN ); ?></h4>
		<p>
			<?php _e( "Using this function you can generate files for customization starting from the default files bundled with this plugin", self::TEXTDOMAIN ); ?>
		</p>
		<ul>
			<li>
				<a href="<?php echo $screenURL ?>&action=mkcustom&mode=update"><?php _e( 'Update', self::TEXTDOMAIN ); ?></a>
				<?php _e( "if you want to generate only the missing files", self::TEXTDOMAIN ); ?>
			</li>
			<li>
				<a href="<?php echo $screenURL ?>&action=mkcustom&mode=replace"><?php _e( 'Rebase', self::TEXTDOMAIN ); ?></a>
				<?php _e( "if you want to regenerate either new and existing files with the ones bundled with the plugin", self::TEXTDOMAIN ); ?>
			</li>
		</ul>
		<?php

		if ( $action == 'mkcustom' ) {
			$this->generateCustomDirectory( $mode );
		}

		?>
		<h4><?php _e( 'Debug log', self::TEXTDOMAIN );?></h4>
		<p>
			<?php _e( "Here you can see what happened on the frontend when user reach your site", self::TEXTDOMAIN ); ?>

		</p>
		<?php
		if ( ! defined( 'EUCOOKIELAW_LOG_FILE' ) ) {
			define( 'EUCOOKIELAW_LOG_FILE', WP_PLUGIN_DIR . '/' . self::CUSTOMDOMAIN . '/log.txt' );
		}
		if ( file_exists( EUCOOKIELAW_LOG_FILE ) ) {

			if ( $action == 'log' && $mode == 'clean' ) {
				if ( unlink( EUCOOKIELAW_LOG_FILE ) ) {
					$this->notifyMessage( __( 'Log file cleaned', self::TEXTDOMAIN ) );
				} else {
					$this->notifyMessage( __( 'Unable to delete log file', self::TEXTDOMAIN ) );
				}

			}
			if ( file_exists( EUCOOKIELAW_LOG_FILE ) ) {
				?>
				<p>
					<a href="<?php echo $screenURL ?>&action=log&mode=clean"><?php _e( "Clear log file" ); ?></a>
				</p>
				<?php
				$logFile = file( EUCOOKIELAW_LOG_FILE );
				echo "<pre>", implode( '', array_filter( $logFile, array( $this, 'parseLogRow' ) ) ), "</pre>";
			}

		} else {
			_e( "Log file not found", self::TEXTDOMAIN );
		}

		?>
		<h4><?php _e( "Site analysis", self::TEXTDOMAIN ); ?></h4>
		<p>
			<?php _e( "Using this powerfull tool you can detect some external URL used on your blog", self::TEXTDOMAIN ); ?>
		</p>
		<p>
			<strong><?php _e( "Beware", self::TEXTDOMAIN ); ?></strong>
			<?php _e( "This functionality is still experimental and its results could be not complete. So if you still encounter that your page is giving you further cookies, then you should make a manual page analisys and detect scripts and elements that are generating the cookies", self::TEXTDOMAIN ); ?>
		</p>
		<p>
			<a href="<?php echo $screenURL ?>&action=scan"><?php _e( "Scan now", self::TEXTDOMAIN ); ?></a>
		</p>
		<?php
		if ( $action == 'scan' ) {
			$siteURL = get_site_url();
			$this->scanURL( $siteURL, $screenURL );
		}
	}

	private function checkCookiesFor( $url ) {
		if ( substr( $url, 0, 2 ) == '//' ) {
			$url = "http:" . $url;
		}
		$response = wp_remote_get( $url, array(
			'httpversion' => '1.1'
		) );

		if ( is_wp_error( $response ) ) {
			print_r( $response );
		} else {

			if ( isset( $response['cookies'] ) ) {
				?>
				<h5><?php _e( "Delivered HTTP Cookies", self::TEXTDOMAIN ); ?></h5>
				<table class="wp-list-table widefat fixed striped posts">
					<thead>
					<tr>
						<th><?php _e( "Name", self::TEXTDOMAIN ); ?></th>
						<th><?php _e( "Value", self::TEXTDOMAIN ); ?></th>
						<th><?php _e( "Expires", self::TEXTDOMAIN ); ?></th>
						<th><?php _e( "Path", self::TEXTDOMAIN ); ?></th>
						<th><?php _e( "Domain", self::TEXTDOMAIN ); ?></th>
					</tr>
					</thead>
					<tbody>

					<?php

					foreach ( $response['cookies'] as $cookie ) {
						?>
						<tr>
							<td><?php echo $cookie->name ?></td>
							<td>
								<?php

								echo $cookie->value;
								?>
							</td>
							<td><?php echo $cookie->expires ?></td>
							<td><?php echo $cookie->path ?></td>
							<td><?php echo $cookie->domain ?></td>
						</tr>
					<?php
					}
					?>
					</tbody>
				</table>

			<?php
			}
			if ( isset( $response['headers'] ) ) {
				?>
				<h5><?php _e( "Delivered Headers", self::TEXTDOMAIN ); ?></h5>
				<table class="wp-list-table widefat fixed striped posts">
					<thead>
					<tr>
						<th><?php _e( "Key", self::TEXTDOMAIN ); ?></th>
						<th><?php _e( "Value", self::TEXTDOMAIN ); ?></th>
					</tr>
					</thead>
					<?php
					foreach ( $response['headers'] as $key => $value ) {
						?>
						<tr>
							<td><?php echo $key;?></td>
							<td><?php
								if ( is_array( $value ) ) {

									echo implode( "<br />", $value );

								} else {
									echo $value;

								}?></td>
						</tr>
					<?php
					}
					?>
				</table>
			<?php
			}
		}
	}

	private function scanURL( $url, $screenURL ) {

		$wp_version = get_bloginfo('version');
		$response = wp_remote_get( $url, array(
			'httpversion' => '1.1',
			'user-agent'  => __CLASS__ . ':' . self::VERSION . '(WordPress/' . $wp_version . '); ' . get_bloginfo( 'url' ),
		) );

		$url = preg_replace( "#.*://#", "", $url );

		if ( preg_match_all( '#([a-z]+:)?//([a-z0-9\.\-]+)[^"\'\s]*#i', $response['body'], $matches ) ) {

			$realURL = array_unique( $matches[0] );
			$matches = array_unique( $matches[2] );

			?>
			<p>
				<?php _e( "Here are the domains found on your site", self::TEXTDOMAIN ); ?>
			</p>
			<table class="wp-list-table widefat fixed striped posts">
				<thead>
				<tr>
					<th><?php _e( "Domain", self::TEXTDOMAIN ); ?></th>
					<th><?php _e( "Action", self::TEXTDOMAIN ); ?></th>
				</tr>
				</thead>
				<tbody>
				<?php
				foreach ( $matches as $index => $match ) {
					if ( $match != $url && strpos( $match, '.' ) !== false ) {
						?>
						<tr>
							<td>
								<?php echo $match; ?>
							</td>
							<td>
								<?php
								$encodedURL = urlencode( $realURL[ $index ] );
								// echo '<br>real: ',$realURL[ $index ],'<br>decoded: ', $_GET['url'] ; # )?'the same ':"not the same ";
								if ( isset( $_GET['url'] ) &&
								     $realURL[ $index ] == $_GET['url']
								) {
									$this->checkCookiesFor( $realURL[ $index ] );

								} else {
									?>
									<a href="<?php echo $screenURL; ?>&action=scan&mode=check&url=<?php echo $encodedURL; ?>">
										<?php echo sprintf( __( "Check if %s produces HTTP Cookies", self::TEXTDOMAIN ), $match ); ?> </a>
								<?php
								}
								?>
							</td>
						</tr>
					<?php
					}
				}
				?>
				</tbody>
			</table>
		<?php

		} else {

			_e( "No url found" );

		}

	}

	public function parseLogRow( $row ) {
		return '<p>' . preg_replace( '#\[([^\s]+)\s([^\s]+)\s@\s([^\s]+)\]#', '<span class="date">$1</span> <span class="time">$2</span> <span class="ip">$3</span> ', $row ) . '</p>';

	}

	private function updateOptions() {
		if ( isset( $_POST['nonce'] ) && wp_verify_nonce( $_POST['nonce'], __CLASS__ ) ) {
			$_POST = stripslashes_deep( $_POST );
			update_option( self::OPT_TITLE, $_POST['banner_title'] );
			update_option( self::OPT_MESSAGE, $_POST['banner_message'] );
			update_option( self::OPT_AGREE, $_POST['banner_agree'] );
			update_option( self::OPT_DISAGREE, $_POST['banner_disagree'] );
			update_option( self::OPT_3RDPDOMAINS, implode( "\n", $_POST['blocked_domains'] ) );
			update_option( self::OPT_LOOKINTAGS, implode("|", $_POST['look_in_tags'] ) );
			update_option( self::OPT_TITLE_TAG, $_POST['tag'] );
			update_option( self::OPT_LOOKINSCRIPTS, $_POST['in_script'] );
			update_option( self::OPT_AGREEONSCROLL, $_POST['agree_on_scroll'] );
			update_option( self::OPT_AGREEONCLICK, $_POST['agree_on_click'] );
			update_option( self::OPT_DEBUG, $_POST['debug'] );
			update_option( self::OPT_DEBUG_VERBOSITY, $_POST['debug_verbosity']);
			update_option( self::OPT_FIXED_ON, $_POST['fix_on'] );
			update_option( self::OPT_COOKIE_EXPIRES, (int) $_POST['duration'] );
			update_option( self::OPT_WHITELIST_COOKIES, implode( ",", $_POST['whitelist'] ) );
			update_option( self::OPT_RELOAD, $_POST['reload'] );
			update_option( self::OPT_ENABLED, $_POST['enabled'] );
			update_option( self::OPT_ENABLEDONLOGIN, $_POST['enabled_on_login'] );
			update_option( self::OPT_BOT_AS_HUMANS, $_POST['bot_as_humans'] );
			update_option( self::OPT_BANNER_STYLE, $_POST['banner_style'] );
			update_option( self::OPT_SCROLL_PX, $_POST['min_scroll'] );
			update_option( self::OPT_ENGINE, $_POST['engine'] );

			update_option( self::OPT_DEFAULT_IFRAME_SRC, $_POST['iframe_default_url'] );
			update_option( self::OPT_DEFAULT_SCRIPT_SRC, $_POST['script_default_url'] );
			update_option( self::OPT_DEFAULT_IMAGE_SRC, $_POST['image_default_url'] );

			update_option( self::OPT_UNAPPLY_ON_URL, implode("\n", $_POST['disallowonurl'] ));

			// Empty the cache if there is one of the known cache plugin
			$allPlugins = get_plugins();

			$cachePlugins = array(
				'ZenCache'       => array('zencache', 'clear'),
				'W3 Total Cache' => 'w3tc_flush_all'
			);

			foreach ( $allPlugins as $pluginPath => $pluginData ) {
				if ( isset( $cachePlugins[ $pluginData['Name'] ] ) && is_plugin_active( $pluginPath ) ) {
					call_user_func( $cachePlugins[ $pluginData['Name'] ] );
				}
			}

			wp_cache_flush();

		}
	}

	public function bannerMetabox() {

		$botAsHumans = get_option( self::OPT_BOT_AS_HUMANS, 'y' );

		$bannerTitle    = get_option( self::OPT_TITLE, 'Banner title' );
		$bannerMessage  = get_option( self::OPT_MESSAGE, 'Banner message' );
		$bannerAgree    = get_option( self::OPT_AGREE, 'I agree' );
		$bannerDisagree = get_option( self::OPT_DISAGREE, 'I disagree' );
		$titleTag       = get_option( self::OPT_TITLE_TAG, 'h1' );
		$agreeOnScroll  = get_option( self::OPT_AGREEONSCROLL, 'n' );
		$agreeOnClick   = get_option( self::OPT_AGREEONCLICK, 'n' );
		$fixedOn        = get_option( self::OPT_FIXED_ON, 'top' );
		$enabled        = get_option( self::OPT_ENABLED, 'y' );
		$enabledOnLogin = get_option( self::OPT_ENABLEDONLOGIN, 'y' );
		$reload         = get_option( self::OPT_RELOAD, 'y' );
		$appliedStyle   = get_option( self::OPT_BANNER_STYLE, '' );
		$minScroll      = get_option( self::OPT_SCROLL_PX, '100' );
		?>
		<table class="form-table">
			<tr>
				<th scope="row"><label><?php _e( "Enabled", self::TEXTDOMAIN ); ?></label></th>
				<td>
					<label>
						<input type="radio" value="y" name="enabled" <?php echo checked( $enabled, 'y' ); ?> />
						<?php _e( 'Yes', self::TEXTDOMAIN ); ?>
					</label>

					<label>
						<input type="radio" value="n" name="enabled" <?php echo checked( $enabled, 'n' ); ?> />
						<?php _e( 'No', self::TEXTDOMAIN ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row"><label><?php _e( "Enabled on login page", self::TEXTDOMAIN ); ?></label></th>
				<td>
					<label>
						<input type="radio" value="y"
						       name="enabled_on_login" <?php echo checked( $enabledOnLogin, 'y' ); ?> />
						<?php _e( 'Yes', self::TEXTDOMAIN ); ?>
					</label>

					<label>
						<input type="radio" value="n"
						       name="enabled_on_login" <?php echo checked( $enabledOnLogin, 'n' ); ?> />
						<?php _e( 'No', self::TEXTDOMAIN ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row"><label><?php _e( "Manage bots as humans", self::TEXTDOMAIN ); ?></label></th>
				<td>
					<p>
						<label>
							<input type="radio" value="y"
							       name="bot_as_humans" <?php echo checked( $botAsHumans, 'y' ); ?> />
							<?php _e( 'Yes', self::TEXTDOMAIN ); ?>
						</label>

						<label>
							<input type="radio" value="n"
							       name="bot_as_humans" <?php echo checked( $botAsHumans, 'n' ); ?> />
							<?php _e( 'No', self::TEXTDOMAIN ); ?>
						</label>
					</p>

					<p>
						<?php _e( "If set to yes the search engines and other automated scannin systems of your site will be threated as they would be normal users", self::TEXTDOMAIN ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="banner_title"><?php _e( "Banner Title", self::TEXTDOMAIN ); ?></label>
				</th>
				<td>
					<input name="banner_title" type="text" id="banner_title"
					       value="<?php echo htmlspecialchars( $bannerTitle ); ?>" class="regular-text">

					<p>
						<strong><?php _e( "Multilingual support", self::TEXTDOMAIN ); ?></strong>
						<?php echo sprintf(
							__( 'If you set <code>%1$s</code> with value <code>%2$s</code> it will be acquired by the custom translation file.', self::TEXTDOMAIN ),
							__( "Banner Title", self::TEXTDOMAIN ),
							"Banner title" );
						?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="tag"><?php _e( "HTML tag for Title", self::TEXTDOMAIN ); ?></label></th>
				<td><input name="tag" type="text" id="tag" value="<?php echo htmlspecialchars( $titleTag ); ?>"
				           class="regular-text"></td>
			</tr>
			<tr>
				<th scope="row"><label
						for="banner_message"><?php _e( "Banner Description", self::TEXTDOMAIN ); ?></label></th>
				<td>
					<textarea name="banner_message" id="banner_message" cols="30" rows="5"
					          class="large-text"><?php echo htmlspecialchars( $bannerMessage ); ?></textarea>

					<p>
						<?php
						echo sprintf(
							__( "In the <code>%s</code> field you can write HTML.", self::TEXTDOMAIN ),
							__( 'Banner Description', self::TEXTDOMAIN )
						);
						?>
					</p>

					<p>
						<strong><?php _e( "Multilingual support", self::TEXTDOMAIN ); ?></strong>
						<?php echo sprintf(
							__( 'If you set <code>%1$s</code> with value <code>%2$s</code> it will be acquired by the custom translation file.', self::TEXTDOMAIN ),
							__( "Banner Description", self::TEXTDOMAIN ),
							"Banner message" );
						?>
					</p>

				</td>
			</tr>
			<tr>
				<th scope="row"><label
						for="banner_agree"><?php _e( "Banner Agree button", self::TEXTDOMAIN ); ?></label></th>
				<td>
					<input name="banner_agree" type="text" id="banner_agree"
					       value="<?php echo htmlspecialchars( $bannerAgree ); ?>" class="regular-text">

					<p>
						<strong><?php _e( "Multilingual support", self::TEXTDOMAIN ); ?></strong>
						<?php echo sprintf(
							__( 'If you set <code>%1$s</code> with value <code>%2$s</code> it will be acquired by the custom translation file.', self::TEXTDOMAIN ),
							__( "Banner Agree button", self::TEXTDOMAIN ),
							"I agree" );
						?>
					</p>

				</td>
			</tr>
			<tr>
				<th scope="row"><label
						for="banner_disagree"><?php _e( "Banner Disagree button", self::TEXTDOMAIN ); ?></label>
				</th>
				<td>
					<input name="banner_disagree" type="text" id="banner_disagree"
					       value="<?php echo htmlspecialchars( $bannerDisagree ); ?>" class="regular-text">

					<p>
						<strong><?php _e( "Multilingual support", self::TEXTDOMAIN ); ?></strong>
						<?php echo sprintf(
							__( 'If you set <code>%1$s</code> with value <code>%2$s</code> it will be acquired by the custom translation file.', self::TEXTDOMAIN ),
							__( "Banner Disagree button", self::TEXTDOMAIN ),
							"I disagree" );
						?>
					</p>

				</td>
			</tr>
			<tr>
				<th scope="row"><label for="fix_on"><?php _e( "Fixed on", self::TEXTDOMAIN ); ?></label></th>
				<td>
					<select name="fix_on" id="fix_on">
						<option
							value="static" <?php echo selected( $fixedOn, 'static' ); ?> ><?php _e( 'Above the contents', self::TEXTDOMAIN ); ?></option>
						<option
							value="top" <?php echo selected( $fixedOn, 'top' ); ?> ><?php _e( 'Top of the page', self::TEXTDOMAIN ); ?></option>
						<option
							value="bottom" <?php echo selected( $fixedOn, 'bottom' ); ?>><?php _e( 'Bottom of the page', self::TEXTDOMAIN ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="fix_on"><?php _e( "Style", self::TEXTDOMAIN ); ?></label></th>
				<?php

				$availableStyles = apply_filters( 'eucookielaw_available_styles',
					array(
						''         => __( 'Default', self::TEXTDOMAIN ),
						'grass'    => __( 'Smothed green style', self::TEXTDOMAIN ),
						'light'    => __( 'Clear and elegant style', self::TEXTDOMAIN ),
						'floating' => __( 'Floating banner', self::TEXTDOMAIN ),
					)
				);
				?>
				<td>
					<select name="banner_style" type="text" id="banner_style">
						<?php
						foreach ( $availableStyles as $style => $description ) {
							?>
							<option
								value="<?php echo $style?>" <?php echo selected( $style, $appliedStyle ); ?> ><?php echo $description; ?></option>
						<?php
						}
						?>

					</select>
				</td>
			</tr>
			<tr>
				<th scope="row"><label><?php _e( "Agree on scroll", self::TEXTDOMAIN ); ?></label></th>
				<td>
					<label>
						<input type="radio" value="y"
						       name="agree_on_scroll" <?php echo checked( $agreeOnScroll, 'y' ); ?> />
						<?php _e( 'Yes', self::TEXTDOMAIN ); ?>
					</label>
					<label>
						<?php echo sprintf( __( "after %s pixels", self::TEXTDOMAIN ),
							'<input type="text" name="min_scroll" size="5" value="' . $minScroll . '" />'
						); ?>

					</label>

					<label>
						<input type="radio" value="n"
						       name="agree_on_scroll" <?php echo checked( $agreeOnScroll, 'n' ); ?> />
						<?php _e( 'No', self::TEXTDOMAIN ); ?>
					</label>


				</td>
			</tr>
			<tr>
				<th scope="row"><label><?php _e( "Agree on click", self::TEXTDOMAIN ); ?></label></th>
				<td>
					<p>
						<label>
							<input type="radio" value="y"
							       name="agree_on_click" <?php echo checked( $agreeOnClick, 'y' ); ?> />
							<?php _e( 'Yes', self::TEXTDOMAIN ); ?>
						</label>

						<label>
							<input type="radio" value="n"
							       name="agree_on_click" <?php echo checked( $agreeOnClick, 'n' ); ?> />
							<?php _e( 'No', self::TEXTDOMAIN ); ?>
						</label>
					</p>

					<p>
						<?php _e( "If enabled, users can click everywhere on the page, outside the banner, to apply their consent", self::TEXTDOMAIN ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label><?php _e( "Reload on consent", self::TEXTDOMAIN ); ?></label></th>
				<td>
					<label>
						<input type="radio" value="y" name="reload" <?php echo checked( $reload, 'y' ); ?> />
						<?php _e( 'Yes', self::TEXTDOMAIN ); ?>
					</label>

					<label>
						<input type="radio" value="n" name="reload" <?php echo checked( $reload, 'n' ); ?> />
						<?php _e( 'No', self::TEXTDOMAIN ); ?>
					</label>
				</td>
			</tr>
		</table>
	<?php
	}

	public function behaviorMetabox() {
		$blockedDomains = preg_split( "#[\n;]#", get_option( self::OPT_3RDPDOMAINS, '' ) );
		$lookInTags     = explode("|", get_option( self::OPT_LOOKINTAGS, self::OPT_DEFAULT_LOOKINTAGS ) );
		$lookInScripts  = get_option( self::OPT_LOOKINSCRIPTS, 'n' );

		$cookieDuration = get_option( self::OPT_COOKIE_EXPIRES, 0 );
		$whitelist      = explode( ",", get_option( self::OPT_WHITELIST_COOKIES, '' ) );

		$disabledOnURL = explode( "\n", get_option(self::OPT_UNAPPLY_ON_URL, '') );

		$iframeDefaultURL = get_option( self::OPT_DEFAULT_IFRAME_SRC, 'about:blank' );
		$scriptDefaultURL = get_option( self::OPT_DEFAULT_SCRIPT_SRC, 'about:blank' );
		$imageDefaultURL = get_option( self::OPT_DEFAULT_IMAGE_SRC, 'about:blank' );


		?>
		<table class="form-table">
			<tr>
				<th scope="row"><label
						for="blocked_domains"><?php _e( "Blocked domains", self::TEXTDOMAIN ); ?></label></th>
				<td id="blocked-urls">
					<?php
					foreach ( $blockedDomains as $domain ) {
						?>
						<div class="eucookie-repeated-section">
							<input name="blocked_domains[]" type="text"
							       value="<?php echo htmlspecialchars( $domain ); ?>" class="regular-text">
						<span>
			                <a href="#" class="button add"> + </a>
			                <a href="#" class=" button remove"> &times; </a>
			            </span>
						</div>
					<?php
					}
					?>
					<p>
						<?php
						echo sprintf(
							__( "In the <code>%s</code> field you must type all the blocked URLs (one per field) without the protocol (eg. <code>www.google.it</code>,<code>placehold.it</code>", self::TEXTDOMAIN ),
							__( "Blocked domains", self::TEXTDOMAIN )
						);
						?>
					</p>

					<p>
						<?php
						echo sprintf(
							__( "Remember that if you put a point (.) on front of URL, then it means the domain and all subdomains (eg. <code>%s</code> means <code>%s</code>, <code>%s</code>, <code>%s</code> and so on)", self::TEXTDOMAIN ),
							'.google.com', 'google.com', 'www.google.com', 'www.maps.google.com' );
						?>
					</p>
					<details>
						<summary><strong>Fast service selection</strong></summary>
						<p>
							<?php do_action( 'eucookielaw_before_service_buttons' ); ?>
							<a class="button"
							   data-eucookielaw-include="google-all"><?php echo _e( "All from Google", self::TEXTDOMAIN ); ?></a>
							<a class="button"
							   data-eucookielaw-include="google-maps"><?php _e( "Google Maps", self::TEXTDOMAIN ); ?></a>
							<a class="button"
							   data-eucookielaw-include="google-fonts"><?php _e( "Google Fonts", self::TEXTDOMAIN ); ?></a>
							<a class="button"
							   data-eucookielaw-include="google-analytics"><?php echo _e( "Google Analytics", self::TEXTDOMAIN ); ?></a>
							<a class="button"
							   data-eucookielaw-include="google-adsense"><?php echo _e( "Google Adsense", self::TEXTDOMAIN ); ?></a>
							<a class="button"
							   data-eucookielaw-include="google-doubleclick"><?php echo _e( "Google Doubleclick", self::TEXTDOMAIN ); ?></a>
							<a class="button"
							   data-eucookielaw-include="addthis"><?php echo _e( "AddThis", self::TEXTDOMAIN ); ?></a>
							<a class="button"
							   data-eucookielaw-include="digg"><?php echo _e( "Digg", self::TEXTDOMAIN ); ?></a>
							<a class="button"
							   data-eucookielaw-include="eventbrite"><?php echo _e( "Eventbrite", self::TEXTDOMAIN ); ?></a>
							<a class="button"
							   data-eucookielaw-include="facebook"><?php echo _e( "Facebook", self::TEXTDOMAIN ); ?></a>
							<a class="button"
							   data-eucookielaw-include="instagram"><?php echo _e( "Instagram", self::TEXTDOMAIN ); ?></a>
							<a class="button"
							   data-eucookielaw-include="linkedin"><?php echo _e( "LinkedIn", self::TEXTDOMAIN ); ?></a>
							<a class="button"
							   data-eucookielaw-include="pinterest"><?php echo _e( "Pinterest", self::TEXTDOMAIN ); ?></a>
							<a class="button"
							   data-eucookielaw-include="twitter"><?php echo _e( "Twitter", self::TEXTDOMAIN ); ?></a>
							<a class="button"
							   data-eucookielaw-include="vimeo"><?php echo _e( "Vimeo", self::TEXTDOMAIN ); ?></a>
							<a class="button"
							   data-eucookielaw-include="google-youtube"><?php echo _e( "Youtube", self::TEXTDOMAIN ); ?></a>
							<?php do_action( 'eucookielaw_after_service_buttons' ); ?>
						</p>
					</details>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label><?php _e( "Search URL only in those tags", self::TEXTDOMAIN ); ?></label>
				</th>
				<td>
					<?php
					foreach ( $lookInTags as $item ) {
						?>
						<div class="eucookie-repeated-section">
							<input name="look_in_tags[]" type="text"
							       value="<?php echo htmlspecialchars( $item ); ?>" class="regular-text">
						<span>
			                <a href="#" class="button add"> + </a>
			                <a href="#" class="button remove"> - </a>
			            </span>
						</div>
					<?php
					}
					?>
					<p>
						<?php
						echo sprintf(
							__( "In the <code>%s</code> field you should report all the tags you want to look into for the given URLs", self::TEXTDOMAIN ),
							__( "Search URL only in those tags", self::TEXTDOMAIN )
						);
						?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label><?php _e( "Look url in <code>script</code> source", self::TEXTDOMAIN ); ?></label></th>
				<td>
					<p>
						<label>
							<input type="radio" value="y"
							       name="in_script" <?php echo checked( $lookInScripts, 'y' ); ?> />
							<?php _e( 'Yes', self::TEXTDOMAIN ); ?>
						</label>

						<label>
							<input type="radio" value="n"
							       name="in_script" <?php echo checked( $lookInScripts, 'n' ); ?> />
							<?php _e( 'No', self::TEXTDOMAIN ); ?>
						</label>
					</p>

					<p>
						<?php _e( "If you enable this option, EUCookieLaw tries to look for the defined rules in the <code>script</code> elements of the page", self::TEXTDOMAIN ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label
						for="iframe_default_url"><?php _e( "Replaced <code>iframe</code>s URL:", self::TEXTDOMAIN ); ?></label>
				</th>
				<td>
					<input name="iframe_default_url" type="text" id="iframe_default_url"
					       value="<?php echo htmlspecialchars( $iframeDefaultURL ); ?>" class="regular-text"><br/>
					<a class="button button-secondary" href="#iframe_default_url"
					   data-set-url="about:blank"><?php _e( "Empty page", self::TEXTDOMAIN ); ?></a>
					<a class="button button-secondary" href="#iframe_default_url"
					   data-set-url="<?php echo plugins_url( 'blocked/default.html', __FILE__ ); ?>"><?php _e( "Default page", self::TEXTDOMAIN ); ?></a>
					<?php
					if ( file_exists( WP_PLUGIN_DIR . '/' . self::CUSTOMDOMAIN . '/blocked' ) && is_dir( WP_PLUGIN_DIR . '/' . self::CUSTOMDOMAIN . '/blocked' ) ) {
						?>
						<a class="button button-secondary" href="#iframe_default_url"
						   data-set-url="<?php echo WP_PLUGIN_URL . '/' . self::CUSTOMDOMAIN . '/blocked/default.html' ?>"><?php _e( "Custom page", self::TEXTDOMAIN ); ?></a>
					<?php
					}
					?>
				</td>
			</tr>
			<tr>
				<th scope="row"><label
						for="script_default_url"><?php _e( "Replaced <code>scripts</code>s source:", self::TEXTDOMAIN ); ?></label>
				</th>
				<td>
					<input name="script_default_url" type="text" id="script_default_url"
					       value="<?php echo htmlspecialchars( $scriptDefaultURL ); ?>" class="regular-text"><br/>
					<a class="button button-secondary" href="#script_default_url"
					   data-set-url="about:blank"><?php _e( "Empty page", self::TEXTDOMAIN ); ?></a>
					<a class="button button-secondary" href="#script_default_url"
					   data-set-url="<?php echo plugins_url( 'blocked/empty.js', __FILE__ ); ?>"><?php _e( "Empty script", self::TEXTDOMAIN ); ?></a>
					<a class="button button-secondary" href="#script_default_url"
					   data-set-url="<?php echo plugins_url( 'blocked/default.js', __FILE__ ); ?>"><?php _e( "Default script", self::TEXTDOMAIN ); ?></a>
					<?php
					if ( file_exists( WP_PLUGIN_DIR . '/' . self::CUSTOMDOMAIN . '/blocked' ) && is_dir( WP_PLUGIN_DIR . '/' . self::CUSTOMDOMAIN . '/blocked' ) ) {
						?>
						<a class="button button-secondary" href="#script_default_url"
						   data-set-url="<?php echo WP_PLUGIN_URL . '/' . self::CUSTOMDOMAIN . '/blocked/default.js' ?>"><?php _e( "Custom script", self::TEXTDOMAIN ); ?></a>
					<?php
					}
					?>
				</td>
			</tr>
			<tr>
				<th scope="row"><label
						for="image_default_url"><?php _e( "Replaced <code>img</code>s source:", self::TEXTDOMAIN ); ?></label>
				</th>
				<td>
					<input name="image_default_url" type="text" id="image_default_url"
					       value="<?php echo htmlspecialchars( $imageDefaultURL ); ?>" class="regular-text"><br/>
					<a class="button button-secondary" href="#script_default_url"
					   data-set-url="about:blank"><?php _e( "Empty page", self::TEXTDOMAIN ); ?></a>
					<a class="button button-secondary" href="#image_default_url"
					   data-set-url="<?php echo plugins_url( 'blocked/empty.gif', __FILE__ ); ?>"><?php _e( "Empty image", self::TEXTDOMAIN ); ?></a>
					<a class="button button-secondary" href="#image_default_url"
					   data-set-url="<?php echo plugins_url( 'cookie.png', __FILE__ ); ?>"><?php _e( "Default image", self::TEXTDOMAIN ); ?></a>
					<?php
					if ( file_exists( WP_PLUGIN_DIR . '/' . self::CUSTOMDOMAIN . '/blocked' ) && is_dir( WP_PLUGIN_DIR . '/' . self::CUSTOMDOMAIN . '/blocked' ) ) {
						?>
						<a class="button button-secondary" href="#image_default_url"
						   data-set-url="<?php echo WP_PLUGIN_URL . '/' . self::CUSTOMDOMAIN . '/blocked/cookie.png' ?>"><?php _e( "Custom script", self::TEXTDOMAIN ); ?></a>
					<?php
					}
					?>
				</td>
			</tr>
			<tr>
				<th scope="row"><label
						for="duration"><?php _e( "Cookie duration (in days)", self::TEXTDOMAIN ); ?></label></th>
				<td>
					<input name="duration" type="number" id="duration"
					       value="<?php echo htmlspecialchars( $cookieDuration ); ?>" class="regular-text">

					<p>
						<?php
						_e( "Set it to <strong>0</strong> to generate a session cookie.", self::TEXTDOMAIN );
						?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label><?php _e( "Allowed cookies", self::TEXTDOMAIN ); ?></label>
				</th>
				<td>
					<?php
					foreach ( $whitelist as $item ) {
						?>
						<div class="eucookie-repeated-section">
							<input name="whitelist[]" type="text"
							       value="<?php echo htmlspecialchars( $item ); ?>" class="regular-text">
						<span>
			                <a href="#" class="button add"> + </a>
			                <a href="#" class="button remove"> - </a>
			            </span>
						</div>
					<?php
					}
					?>
					<p>
						<?php
						_e( "The law, allows you to write the technical cookies of your site, so you can write here (one per field) which one are allowed.", self::TEXTDOMAIN );
						?>
					</p>

					<p>
						<?php
						_e( "<strong>Note:</strong> if you want to allow multiple cookies with the same prefix please type the prefix followed by an asterisk (eg. <code>__utm*</code>) ", self::TEXTDOMAIN );
						?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label><?php _e( "Disable if URL is one of the following", self::TEXTDOMAIN ); ?></label>
				</th>
				<td>
					<?php
					foreach ( $disabledOnURL as $item ) {
						?>
						<div class="eucookie-repeated-section">
							<input name="disallowonurl[]" type="text"
							       value="<?php echo htmlspecialchars( $item ); ?>" class="regular-text">
						<span>
			                <a href="#" class="button add"> + </a>
			                <a href="#" class="button remove"> - </a>
			            </span>
						</div>
					<?php
					}
					?>
					<p>
						<?php
						_e( "If your site requires that the plugin must not be enabled on some specific URL then you can use the above fields to set the URL where EUCookieLaw should be disabled. The code accepts wildchar <code>*</code> (eg. <code>/api/*</code> means everything that starts with <code>/api/</code>)", self::TEXTDOMAIN );
						?>
					</p>
				</td>
			</tr>
		</table>
	<?php
	}

	public function debugMetabox(){
		$debugEnabled   = get_option( self::OPT_DEBUG, 'n' );
		$verbosity      = get_option( self::OPT_DEBUG_VERBOSITY, '99');
		?>
		<h3><?php _e( "Enable debug", self::TEXTDOMAIN ); ?></h3>
		<p>
			<label>
				<input type="radio" value="y" name="debug" <?php echo checked( $debugEnabled, 'y' ); ?> />
				<?php _e( "Write on error log", self::TEXTDOMAIN ); ?>
			</label><br />
			<span class="on-debug on-debug-y">
				<?php
			echo sprintf(

				__( "Log file will be written in <code>%s</code>", self::TEXTDOMAIN ),
				ini_get( 'error_log' )
			);
			?><br />
			</span>
			<label>
				<input type="radio" value="file" name="debug" <?php echo checked( $debugEnabled, 'file' ); ?> />
				<?php _e( "Write on log file", self::TEXTDOMAIN ); ?>
			</label><br />
			<span class="on-debug on-debug-file">
				<?php
			echo sprintf(

				__( "Log file will be written in <code>%s</code>", self::TEXTDOMAIN ),
				defined( 'EUCOOKIELAW_LOG_FILE' ) ? EUCOOKIELAW_LOG_FILE : ( WP_PLUGIN_DIR . '/' . self::CUSTOMDOMAIN . '/log.txt' ) );
			?><br />
			</span>

			<label>
				<input type="radio" value="n" name="debug" <?php echo checked( $debugEnabled, 'n' ); ?> />
				<?php _e( 'No', self::TEXTDOMAIN ); ?>
			</label>
		</p>

		<p>
			<?php _e( "When debug is enabled you can see which rules are applied and check for strange behaviors directly in the system error log or in a specific file according to debug verbosity", self::TEXTDOMAIN ) ?>
		</p>
		<div class="on-debug on-debug-file on-debug-y">
			<h3><?php _e("Verbosity", self::TEXTDOMAIN); ?></h3>
			<p>
				<label>
					<input type="radio" value="0" name="debug_verbosity" <?php echo checked( $verbosity, '0' ); ?> />
					<?php _e( 'Silent', self::TEXTDOMAIN ); ?>
				</label>
			</p>
			<p>
				<label>
					<input type="radio" value="10" name="debug_verbosity" <?php echo checked( $verbosity, '10' ); ?> />
					<?php _e( 'Normal', self::TEXTDOMAIN ); ?>
				</label>
			</p>
			<p>
				<label>
					<input type="radio" value="20" name="debug_verbosity" <?php echo checked( $verbosity, '20' ); ?> />
					<?php _e( 'High', self::TEXTDOMAIN ); ?>
				</label>
			</p>
			<p>
				<label>
					<input type="radio" value="99" name="debug_verbosity" <?php echo checked( $verbosity, '99' ); ?> />
					<?php _e( 'Verbose', self::TEXTDOMAIN ); ?>
				</label>
			</p>
		</div>

		<?php
	}

	public function outputMessagesSupport() {
		$engine         = get_option( self::OPT_ENGINE, 'regexp' );
		?>
	    <h3><?php _e( "Render page with", self::TEXTDOMAIN ); ?></h3>
	    <p>
			<label>
				<input type="radio" value="dom" name="engine" <?php echo checked( $engine, 'dom' ); ?> />
				<?php _e( 'DOMDocument', self::TEXTDOMAIN ); ?>
			<?php
			if ( ! class_exists( "DOMDocument" ) ) {
				echo "<strong style='color: red'>",
				__( "Not available", self::TEXTDOMAIN ),
				"</strong>";
			}
			?>
			</label>
	    </p>
	    <p>
			<label>
				<input type="radio" value="regexp" name="engine" <?php echo checked( $engine, 'regexp' ); ?> />
				<?php _e( 'Regular Expression', self::TEXTDOMAIN ); ?>
			</label>
		</p>

		<p>
			<?php
			_e( "It's reccomended to to use DOMDocument to render the page, however, if you encounter problems using this engine you can switch to Regular Expression engine", self::TEXTDOMAIN );
			?>
		</p>

		<p>
			<?php
			_e( "If DOMDocument library is not available on your host then the Regular Expression engine will be used by default", self::TEXTDOMAIN );
			?>
		</p>

		<p>
			<span class="eucookielaw-info-submit"><strong>EUCookieLaw version <?php echo self::VERSION ?></strong></span>
			<input type="hidden" name="nonce" value="<?php echo wp_create_nonce( __CLASS__ ); ?>" />
			<input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e( "Save" ); ?>">
			<a href="#" id="export-settings" class="button button-secondary"><?php _e( "Export settings", self::TEXTDOMAIN ); ?></a>
			<a href="#" id="import-settings" class="button button-secondary"><?php _e( "Import settings", self::TEXTDOMAIN ); ?></a>
			<span class="eucookielaw-info-submit">
					<?php echo sprintf(
				__( "If you find this plugin useful, and since I've noticed that nobody did this script (as is) before of me, " .
				    "I'd like to receive <a href=\"%s\">a donation</a> as thankful for the time You've earned for you, your " .
				    "family and your hobbies! :)", self::TEXTDOMAIN ),
				"https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=me%40diegolamonica%2einfo&lc=IT&item_name=EU%20Cookie%20Law&no_note=0&currency_code=EUR&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHostedGuest" ); ?>

			</span>

			<?php
			if ( $this->showMergeButton ) {
				?>
				<a href="<?php echo $_SERVER['REQUEST_URI'] ?>&write"
				   class="button button-secondary"><?php _e( "Merge with cache plugin", self::TEXTDOMAIN ); ?></a>
			<?php
			}

			if ( defined( 'WP_CACHE' ) && WP_CACHE === true ) {
				?>
				<p>
					<?php _e( "Note that, to ensure the cached contents uses the right settings from EUCookieLaw you need to empty your cache (according to specific cache plugin settings) once you have saved the configuration", self::TEXTDOMAIN ); ?>
				</p>
			<?php
			}
			?>
		</p>

		<?php

	}

	function displayFBLike() {

		?>
		<div id="fb-root"></div>
		<script>(function (d, s, id) {
				var js, fjs = d.getElementsByTagName(s)[0];
				if (d.getElementById(id)) return;
				js = d.createElement(s);
				js.id = id;
				js.src = "//connect.facebook.net/it_IT/sdk.js#xfbml=1&version=v2.3&appId=451493874905248";
				fjs.parentNode.insertBefore(js, fjs);
			}(document, 'script', 'facebook-jssdk'));</script>
		<div class="fb-page" data-href="https://www.facebook.com/UsaEUCookieLaw" data-hide-cover="true"
		     data-show-facepile="true" data-show-posts="true">
			<div class="fb-xfbml-parse-ignore">
				<blockquote cite="https://www.facebook.com/UsaEUCookieLaw">
					<a href="https://www.facebook.com/UsaEUCookieLaw">EUCookieLaw</a>
				</blockquote>
			</div>
		</div>
	<?php
	}
}
