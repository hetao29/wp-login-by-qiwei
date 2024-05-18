<?php

require(dirname(__FILE__)."/../../../wp-load.php");
if ( !class_exists( 'LoginByQiweiCallack' ) ) {
	class LoginByQiweiCallack{
		/**
		 * https://developer.work.weixin.qq.com/document/path/91039
		 */
		public static $cache_key="LoginByQiweiCallack::gettoken";
		public static function gettoken(){
			$qiwei_option= get_option('wp_login_enterprise_wechat_option');
			$corpid = $qiwei_option['corpid']??'';
			$corpsecret = $qiwei_option['corpsecret']??'';
			$key = self::$cache_key."::$corpid::$corpsecret";
			$access_token = get_transient( $key);
			if( false === $access_token) {
				$url = "https://qyapi.weixin.qq.com/cgi-bin/gettoken?". http_build_query([
					'corpid'=>$corpid,
					'corpsecret'=>$corpsecret,
				]);
				$ret = json_decode(wp_remote_retrieve_body(wp_remote_get($url)));
				$access_token = $ret->access_token ?? false;
				set_transient( $key, $access_token, 60*60 );
			}
			return $access_token;
		}
		/**
		 * https://developer.work.weixin.qq.com/document/path/98176
		 */
		public static function getuserinfo($code){
			$access_token = self::gettoken();
			$url = "https://qyapi.weixin.qq.com/cgi-bin/auth/getuserinfo?".http_build_query([
				'access_token'=>$access_token,'code'=>$code,
			]);
			return json_decode(wp_remote_retrieve_body(wp_remote_get($url)));
		}
	}
}

if ( ! wp_verify_nonce( $_REQUEST['_wpnonce']??"", 'nonce' ) ) {
	wp_redirect(wp_login_url("",true));
}else{
	$code = sanitize_text_field($_REQUEST['code']??"");
	$userinfo = LoginByQiweiCallack::getuserinfo($code);
	$username = $userinfo->userid ?? "";
	if(!empty($username)){
		$user = get_user_by('login', $username );
		if ( $user ){
			wp_set_current_user ( $user->ID );
			wp_set_auth_cookie  ( $user->ID);
			wp_redirect(admin_url());
		}else{
			$qiwei_option= get_option('wp_login_enterprise_wechat_option');
			if(!empty($qiwei_option['auto_register']) && $qiwei_option['auto_register']=="on"){
				$userdata = array(
					'user_login' =>  $username,
					'user_pass'  =>  NULL // When creating an user, `user_pass` is expected.
				);
				if(!empty($qiwei_option['auto_register_role'])){
					$userdata['role'] = $qiwei_option['auto_register_role'];
				}
				//https://developer.wordpress.org/reference/functions/wp_insert_user/
				$user_id = wp_insert_user( $userdata ) ;
				if ( ! is_wp_error( $user_id ) ) {
					wp_set_current_user ( $user->ID );
					wp_set_auth_cookie  ( $user->ID);
					wp_redirect(admin_url());
				}else{
					wp_redirect(wp_login_url("",true));
				}
			}else{
				wp_redirect(wp_login_url("",true));
			}
		}
	}else{
		wp_redirect(wp_login_url("",true));
	}
}
