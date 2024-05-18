pot:
	xgettext -o languages/login-integration-for-enterprise-wechat.pot -k__ -k_e  -kesc_html__ -n --sort-by-file *.php
zh_CN:
	msgfmt -o languages/login-integration-for-enterprise-wechat-zh_CN.mo languages/login-integration-for-enterprise-wechat-zh_CN.pot
