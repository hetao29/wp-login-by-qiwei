<?php
/**
 * Plugin Name:       Login Integration For Enterprise Wechat
 * Plugin URI:        https://github.com/hetao29/wp-login-integration-for-enterprise-wechat
 * Description:       Login in wordpress by enterprise wechat account
 * Version:           1.1.0
 * Requires PHP:      7.0
 * Requires at least: 5.0
 * Tested up to:      6.5
 * Author URI:        https://github.com/hetao29/
 * License:           GNU General Public License v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:	      login-integration-for-enterprise-wechat
 * Domain Path:       /languages
 *
 * @package           LoginByQiwei
 */
if ( ! defined( 'WPINC' ) ) {
	die;
}
if ( !class_exists( 'WpLoginIntegrationForEnterpriseWechat' ) ) {

	class WpLoginIntegrationForEnterpriseWechat{
		var $setting_page = 'wp_login_enterprise_wechat_setting'; //注册选项 设置显示在哪个页面
		var $setting_section = 'wp_login_enterprise_wechat_option_section';
		var $option_key = "wp_login_enterprise_wechat_option";
		var $text_domain="login-integration-for-enterprise-wechat";
		var $options = [];

		function __construct(){
			$this->options= get_option($this->option_key);
			if(!empty($this->options['agentid']) && !empty($this->options['corpid']) && !empty($this->options['corpsecret'])){
				if(!empty($this->options['disable_system_login']) && $this->options['disable_system_login']=="on"){
					remove_filter('authenticate', 'wp_authenticate_username_password', 20, 3);
					add_action('login_init', array( $this, 'login_init' ) );
				}else{
					add_action( 'login_footer', array($this,'login_enqueue_scripts' ));
					add_action( 'login_form', array($this,'login_form' ));
				}
			}
			add_action('admin_init',array($this,'register_setting'));
			add_action('admin_menu', array($this,'register_setting_menu'));
			add_action('plugins_loaded', [ $this, 'plugins_loaded' ] );
			add_action('plugin_action_links_' . plugin_basename( __FILE__ ), function( $links ) {
					array_unshift(
						$links,
						sprintf( '<a href="%s">%s</a>', admin_url( 'options-general.php?page='.$this->setting_page), __( 'Settings', $this->text_domain ) )
					);
					return $links;
				}
			);
		}
		function login_enqueue_scripts() {
			$nonce = wp_create_nonce( 'nonce' );
			$url = esc_url( plugin_dir_url( __FILE__ ) . "wp-login-integration-for-enterprise-wechat-callback.php?_wpnonce=$nonce" );
			set_query_var('url',$url);
			set_query_var("options",$this->options);
			load_template(plugin_dir_path( __FILE__ ) . '/login.template.php', true, false);
		}
		function login_form() {
			if($this->isQiyeWeixin()){
				echo sprintf('<p style="padding-bottom: 10px;"><a style="cursor: pointer;text-decoration: underline;" href="%s">%s</a></p>',
					esc_attr($this->get_login_url()),
					esc_html__("Login with Enterprise Wechat",$this->text_domain)
				);
			}else{
				echo sprintf('<p style="padding-bottom: 10px;"><a style="cursor: pointer;text-decoration: underline;" onclick="javascript:clean();qiWeiLogin();void(0);" id="login-a">%s</a></p>',
					esc_html__("Login with Enterprise Wechat",$this->text_domain)
				);
			}
		}
		function plugins_loaded() {
			load_plugin_textdomain( $this->text_domain, false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
		}
		function login_init(){
			if($this->isQiyeWeixin() && !isset($_GET['loggedout'])){ // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				wp_redirect($this->get_login_url());
			}else{
				$nonce = wp_create_nonce( 'nonce' );
				$url = esc_url( plugin_dir_url( __FILE__ ) . "wp-login-integration-for-enterprise-wechat-callback.php?_wpnonce=$nonce" );
				set_query_var('url',$url);
				set_query_var("options",$this->options);
				login_header();
				echo '<div id="login-b" style="justify-content: center; display: flex; width: 100%;"></div>';
				load_template(plugin_dir_path( __FILE__ ) . '/login.template.php', true, false);
				login_footer();
				echo ("<script>qiWeiLogin();</script>");
			}
			if(!is_user_logged_in() || !isset( $_GET['action'])){ exit; }// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}
		function register_setting_menu(){
			add_options_page(
				__('Setting of Login By Qiwei',$this->text_domain),
				__('Login By Qiwei',$this->text_domain),
				'manage_options',
				$this->setting_page,
				array($this,'register_setting_page')#回调方法名称， 主要是在这里面设置页面
			);
		}
		function register_setting_page(){
			if (!current_user_can('manage_options')) {
				return;
			}
?>
    <div class="wrap">
<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
	    <form action="options.php" method="post">
<?php
			#输出必要的字段
			settings_fields($this->setting_page);

			#输出显示的区域
			do_settings_sections($this->setting_page);

			#输出按钮
			submit_button();
?>

	    </form>
    </div>
<?php
		}
		function register_setting(){
			//注册一个选项，用于装载所有插件设置项
			register_setting($this->setting_page,$this->option_key);
			//设置字段
			add_settings_section(
				$this->setting_section,
				sprintf(
					'%s <a target="_blank" href="%s">%s</a>',
					esc_html__('Please set each parameter here. For specific meanings, please refer to Qiwei',$this->text_domain),
					'https://developer.work.weixin.qq.com/document/path/98151',
					esc_html__('Login instructions',$this->text_domain)
				),
				array($this,'setting_section_function'),
				$this->setting_page
			);

			add_settings_field(
				'corpid/appid',
				sprintf('<span style="color:#EE3F4D">*</span>%s<a style="text-decoration: none;" href="%s" target="_blank"><span class="dashicons dashicons-editor-help" style="font-size: 18px;"></span></a>',esc_html__('CorpId/AppId',$this->text_domain),'https://developer.work.weixin.qq.com/document/path/91022'),
				array($this,'setting_corpid'),
				$this->setting_page,
				$this->setting_section
			);
			add_settings_field(
				'agentid',
				sprintf('<span style="color:#EE3F4D">*</span>%s<a style="text-decoration: none;" href="%s" target="_blank"><span class="dashicons dashicons-editor-help" style="font-size: 18px;"></span></a>',esc_html__('AgentId',$this->text_domain),'https://developer.work.weixin.qq.com/document/path/91022'),
				array($this,'setting_agentid'),
				$this->setting_page,
				$this->setting_section,
			);
			add_settings_field(
				'corpsecret',
				sprintf('<span style="color:#EE3F4D">*</span>%s<a style="text-decoration: none;" href="https://developer.work.weixin.qq.com/document/path/91039" target="_blank"><span class="dashicons dashicons-editor-help" style="font-size: 18px;"></span></a>',esc_html__('CorpSecrect',$this->text_domain),'https://developer.work.weixin.qq.com/document/path/91039'),
				array($this,'setting_corpsecret'),
				$this->setting_page,
				$this->setting_section,
			);
			add_settings_field(
				'auto_register',
				esc_html__('Auto Register',$this->text_domain),
				array($this,'setting_auto_register'),
				$this->setting_page,
				$this->setting_section,
			);
			add_settings_field(
				'auto_register_role',
				esc_html__('Default Role of New User',$this->text_domain),
				array($this,'setting_auto_register_role'),
				$this->setting_page,
				$this->setting_section,
			);
			add_settings_field(
				'disable_system_login',
				esc_html__('Disable Default Login',$this->text_domain),
				array($this,'setting_disable_system_login'),
				$this->setting_page,
				$this->setting_section,
			);
		}
		function setting_section_function(){
		}
		function setting_auto_register(){
			echo sprintf('<input type="checkbox" %s name="'.$this->option_key.'[auto_register]"/>',!empty($this->options['auto_register']) ? 'checked' : '');
		}
		function setting_disable_system_login(){
			echo sprintf('<input type="checkbox" %s name="'.$this->option_key.'[disable_system_login]" id="disable_system_login"/><label for="disable_system_login" style="font-weight:bold;color:#EE3F4D">%s</label>',
				!empty($this->options['disable_system_login']) ? 'checked' : '',
				esc_html__('Warning, This will override the default login, You should diable/remove the plugin if you cannot login by Qiwei.',$this->text_domain),
			);
		}
		function setting_auto_register_role(){
			$editable_roles = get_editable_roles();
			echo '<select name="'.$this->option_key.'[auto_register_role]">';
			foreach ($editable_roles as $role => $details) {
				echo sprintf("<option %s value='%s'>%s</option>",
					($this->options['auto_register_role']??'') == $role ? 'selected':'',
					esc_attr($role),
					esc_html(translate_user_role($details['name']))
				);
			}
			echo '</select>';
		}
		function setting_corpid(){
			echo sprintf('<input type="text" name="'.$this->option_key.'[corpid]" value="%s"/>',esc_attr($this->options['corpid']??''));
		}
		function setting_corpsecret(){
			echo sprintf('<input type="text" style="width:60%%" name="'.$this->option_key.'[corpsecret]" value="%s"/>',esc_attr($this->options['corpsecret']??''));
		}
		function setting_agentid(){
			echo sprintf('<input type="text" name="'.$this->option_key.'[agentid]" value="%s"/>',esc_attr($this->options['agentid']??''));
		}
		function isQiyeWeixin(){
			$ua = !empty($_SERVER['HTTP_USER_AGENT'])?$_SERVER['HTTP_USER_AGENT']:"";
			if(preg_match("/(MicroMessenger)/i",$ua) && preg_match("/(wxwork)/i",$ua)){
				return true;
			}
			return false;
		}
		function get_login_url(){
			$nonce = wp_create_nonce( 'nonce' );
			$url = esc_url( plugin_dir_url( __FILE__ ) . "wp-login-integration-for-enterprise-wechat-callback.php?_wpnonce=$nonce" );
			set_query_var('url',$url);
			return "https://open.weixin.qq.com/connect/oauth2/authorize?".http_build_query([
				"appid"=>$this->options['corpid'],
				"redirect_uri"=>urlencode($url),
				"response_type"=>"code",
				"scope"=>"snsapi_base",
				"state"=>"STATE",
				"agentid"=>$this->options['agentid'],
			])."#wechat_redirect";
		}
	}

	$WpLoginIntegrationForEnterpriseWechat = new WpLoginIntegrationForEnterpriseWechat;
}
