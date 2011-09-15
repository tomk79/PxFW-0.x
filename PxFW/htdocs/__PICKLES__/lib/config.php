<?php

#	基底クラスをロード
require_once( './__PICKLES__/__LIB_BASE__'.'/config.php' );
require_once( './__PICKLES__/__LIB_PACKAGE__'.'/config.php' );
class project_config extends package_config{

	#--------------------------------------
	#	実行モード設定
	#	[ 指定値 ]
	#		null = 通常モード
	#		'setup' = セットアップ検証モード→ファイル、データベースの一切の書き込みを行わない。
	var $system_exec_mode = null;
		//	※未実装の機能です。実際には有効になりません。

	#--------------------------------------
	#	デバッグモード設定
	#	※開発環境でのみ有効
	var $debug_mode = false;
	var $debug_mode_print_exec_microtime = true;
		//	$themeのprint_and_exit()が、exit;を実行する前に、実行にかかったmicrotimeを標準出力します。
		//	debug_mode が true の場合のみ有効
	var $allow_picklesinfo_service = true;
		//	PICKLESINFOサービスを有効にするか？
		//	このサービスを有効にすると、
		//	PicklesFrameworkの内部設定などを手軽に確認できるなど、
		//	デバッグに役立ちます。
		//		EX: http://www.sample.jp/xxx.php?PICKLESINFO=xxxx
		//			PICKLESINFO=setupmode => セットアップモードを実行
		//	ただし、この機能を有効にすることには、セキュリティ上の問題があります。
		//	実運用中のプロジェクトでは、有効にしないでください。

	#--------------------------------------
	#	文字コード設定
	var $php_default_charset = 'UTF-8';
	var $php_mb_internal_encoding = 'UTF-8';
	var $php_mb_http_input = 'UTF-8';
	var $php_mb_http_output = 'UTF-8';

	#--------------------------------------
	#	プロジェクトプロパティ設定
	var $info_projectid = 'pxfw';			//	プロジェクトID
	var $info_sitetitle = 'Pickles Skeleton (Github)';			//	サイトタイトル
	var	$info_copyright = array( 'copy'=>'Tomoya Koyanagi' , 'since_from'=>'' , 'since_to'=>'now' );		//	著作権情報
	var	$seckey = 'PX';					//	ユーザパスワードの暗号化キー( crypt関数に渡される )
	var $show_invisiblepage = false;	//	公開日が未来のもの、終了日が過去のもの、非公開のものを表示するか否か
	var $enable_urlmap = true;			//	URLマップを使用する。(サイトマップにて指定されたパスによるアクセスを解決する)
	var $logging_expire = 1800;
		//	ログイン状態を保持する時間
	var $flg_staticurl = true;
		//	スタティックなURLを使用する(通常はtrueにしてください)
	var $allow_cancel_customtheme = true;
		//	テーマのキャンセルを許可するフラグ。
	var $generate_localresourcepath_by_pid = false;
		//	ローカルリソースのパスをページIDから生成するフラグ。

	#--------------------------------------
	#	内部パス設定
	var $path_root = '.';		//	このフレームワークが触ることができる領域の最上階層
	var $path_lib_base = './__PICKLES__/__LIB_BASE__';		//	ベース層ライブラリのパス
	var $path_lib_package = './__PICKLES__/__LIB_PACKAGE__';		//	パッケージ層ライブラリのパス
	var $path_lib_project = './__PICKLES__/lib';		//	プロジェクト層ライブラリのパス
	var $path_docroot = '.';		//	ドキュメントルートの内部パス。ウェブに公開されるパス。
	var $path_projectroot = './__PICKLES__';		//	プロジェクトのホームディレクトリ
	var $path_contents_dir = '.';		//	コンテンツディレクトリ
	var $path_sitemap_dir = './__PICKLES__/sitemap';		//	サイトマップディレクトリ
	var $path_romdata_dir = './__PICKLES__/romdata';		//	ROMデータディレクトリ
	var $path_ramdata_dir = './__PICKLES__/__LIB_PROJECT__/pxfw/ramdata';		//	RAMデータディレクトリ
	var $path_theme_collection_dir = './__PICKLES__/theme';		//	テーマコレクションディレクトリ
	var $path_system_dir = './__PICKLES__/__LIB_PROJECT__/pxfw/system';		//	システムディレクトリ
	var $path_cache_dir = './__PICKLES__/__LIB_PROJECT__/pxfw/cache';		//	キャッシュディレクトリ
	var $path_common_log_dir = './__PICKLES__/__LIB_PROJECT__/pxfw/log/common';		//	汎用ログディレクトリ
	var $path_userdir = './__PICKLES__/__USER__';		//	ユーザデータを格納するディレクトリ
		#	ユーザ情報の認証にデータベースを使用する場合は、
		#	$rdb_usertable にテーブル名を登録する

	#--------------------------------------
	#	外部パス設定
	var $url_action = '';
	var $url_domain = 'localhost:30000';
	var $url_root = '';
	var $url_sitetop = 'http://localhost:30000/';
	var $url_resource = '/common';
	var $url_localresource = '/cache/cont.items';
	var $url_themeresource = '/cache/theme.items';

	#--------------------------------------
	#	言語設定
	var $allow_lang = array(
		'ja'=>true ,
		'en'=>true ,
		'kr'=>true ,
		'ch'=>true ,
	);
	var $default_lang = 'ja';	//	デフォルトの自然言語

	#--------------------------------------
	#	メールアドレスの設定
	var $email = array(
		'info'=>'',
		'support'=>'',
		'admin'=>'',
		'error'=>'',
	);

	#--------------------------------------
	#	コンテンツ設定
	var $contents_start_str = '<!--BODYSTART-->';
	var $contents_end_str = '<!--BODYEND-->';
		#	コンテンツ解析時に、有効とする範囲を示すコメント文字列。
		#	$contents_start_str から $contents_end_str までを有効なコンテンツとみなし、
		#	それ以外の領域は、破棄される。
		#	コンテンツ中にこれらの文字列が存在しない場合は、全部を有効範囲として処理される。
	var $auto_notfound = true;
		#	Pickles Framework 0.5.1 追加
		#	存在しないページIDにアクセスした際に、自動的にNotFound画面を表示するフラグ。
		#	false に設定される場合、トップページで pv() を受け取れる。
	var $enable_contents_preprocessor = false;
		#	Pickles Framework 0.5.2 追加
		#	コンテンツのプリプロセッサ機能を有効/無効にする。
	var $allow_flush_content_without_pages = true;
		#	PxFW 0.7.0 追加
		#	ページが存在しなくてコンテンツが存在する場合、
		#	コンテンツ単体で出力するフラグ。

	#--------------------------------------
	#	システム設定
	var $writeprotect = array();
	var $readprotect = array();
		#	システムに対して、
		#	書き込み/読み込みを禁止するディレクトリを指定します。
		#	この設定は、$dbhが参照し、判断します。

	var $use_sitemapcache_flg = true;
		#	サイトマップキャッシュを使用するか否かを設定します。
		#	この設定がtrueの場合、クラス base_lib_site は、
		#	アクセスがあった言語のサイトマップを、透過的にキャッシュし、
		#	次回以降、同じ言語でのアクセス時には、
		#	CSVの解析を行わず、キャッシュからサイトマップをロードします。
		#	キャッシュは、自動的に更新されることはありません。
		#	サイトマップを更新した際に、手動で削除する必要があります。
		#	この設定をfalseにすると、
		#	サイトマップキャッシュの生成を行わず、
		#	たとえキャッシュされていても、CSVから都度ロードするようになります。

	#--------------------------------------
	#	接続先データベースの設定
	var $rdb = array(
		'type'=>'SQLite' ,
		'version'=>'' ,
		'server'=>'' ,
		'user'=>'' ,
		'passwd'=>'' ,
		'name'=>'./__PICKLES__/__LIB_PROJECT__/pxfw/common.db' ,
		'port'=>'' ,
		'charset'=>'UTF-8' ,
		'sessionmode'=>''
	);
		#	type = RDBアプリケーションの種類。'MySQL' or 'PostgreSQL' or 'SQLite' or 'Oracle'
		#	version = 'type'のバージョン
		#	server = DBサーバのアドレス
		#	user = DBへのログインユーザ名
		#	passwd = DBへのログインパスワード
		#	name = データベース名(SQLiteを選択した場合はデータベースファイルのパス)
		#	port = DB接続のポート番号
		#	charset = DBのデフォルト文字セット
		#	sessionmode = セッションモード(Oracle使用時のみ)

	var $rdb_usertable = array(
		'master'=>'px_user_master' ,
		'property'=>'px_user_property' ,
		'project_authoptions'=>'px_user_project_authoptions' ,
		'project_authgroup'=>'px_user_project_authgroup' ,
		'project_status'=>'px_user_project_status' ,
		'project_datas'=>'px_user_project_datas' ,
		'group'=>'px_user_group' ,
		'group_authoptions'=>'px_user_group_authoptions' ,
	);
		#	ユーザ情報をRDBで管理する場合に、連想配列で登録。
		#	RDBを使用しない場合は、nullを指定。
		#	この値が、is_array() でtrueを返すと、RDBモードとして探しにいっちゃいます。
		#	次の6つのキーで連想配列をつくり、それぞれ、テーブル名を指定してください。
		#		master = ユーザマスタテーブル
		#		property = ユーザの詳細情報テーブル
		#		project_authoptions = 権限テーブル
		#		project_authgroup = 所属グループテーブル
		#		project_status = ユーザのステータスを格納するテーブル
		#		project_datas = アプリケーションが設定する、ユーザ個人に紐づくデータを格納するテーブル
		#		group = ユーザグループマスタテーブル
		#		group_authoptions = ユーザグループ権限テーブル

	#--------------------------------------
	#	ログ保存先のディレクトリ設定
//	var $errors_log_path = './__PICKLES__/__LIB_PROJECT__/pxfw/log/errorlog';
		#	$path_projectroot からの相対パス指定
	var $errors_log_rotate = null;	#	エラーログのローテーション設定。
//	var $access_log_path = './__PICKLES__/__LIB_PROJECT__/pxfw/log/accesslog';
		#	$path_projectroot からの相対パス指定
	var $access_log_rotate = null;	#	アクセスログのローテーション設定。

		#	ローテーション設定値は、
		#		null => ローテーションしない
		#		'hourly' => 毎時間ローテーション。年月日時をファイル名の末尾に付加(xxxxlog_20060807_24.log)
		#		'daily' => 毎日ローテーション。年月日をファイル名の末尾に付加(xxxxlog_20060807.log)
		#		'monthly' => 毎月ローテーション。年月をファイル名の末尾に付加(xxxxlog_200608.log)
		#		'yearly' => 毎年ローテーション。年をファイル名の末尾に付加(xxxxlog_2006.log)
		#	ローテーションという名前になっていますが、
		#	現仕様では残念ながらローテーションはしません。
		#	ファイル名に時間を表す文字を追加し、
		#	1ファイル当たりの容量を減らしているに過ぎません。

	#--------------------------------------
	#	その他の値
	var $time = 0;	//	システムが呼び出された時間
		#	これらの値は、クラス base_lib_conductor が、動的に設定上書きします。

}

?>