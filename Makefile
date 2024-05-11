pot:
	xgettext -o languages/login-by-qiwei.pot -k__ -k_e  -kesc_html__ -n --sort-by-file *.php
zh_CN:
	msgfmt -o languages/login-by-qiwei-zh_CN.mo languages/login-by-qiwei-zh_CN.pot
