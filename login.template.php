<script src="https://wwcdn.weixin.qq.com/node/open/js/wecom-jssdk-1.3.1.js"></script>
<script>
function clean(){
	jQuery("form#loginform").empty().append('<div id="login-b" style="justify-content: center; display: flex; width: 100%%;"></div>');
}
function qiWeiLogin(){
	const wwLogin = ww.createWWLoginPanel({
			el: '#login-b',
			params: {
					login_type: 'CorpApp',
					appid: '<?php echo \esc_html($options["corpid"]); ?>',
					agentid: '<?php echo \esc_html($options["agentid"]); ?>',
					redirect_uri: '<?php echo \esc_html($url)?>',
					state: 'loginState',
					redirect_type: 'top',
					panel_size:"small",
				},
			onCheckWeComLogin({ isWeComLogin }) {
				},
			onLoginSuccess({ code }) {
				},
			onLoginFail(err) {
				},
		})
}
</script>
