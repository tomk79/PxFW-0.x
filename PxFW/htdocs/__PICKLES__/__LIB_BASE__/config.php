<?php

#	Copyright (C)Tomoya Koyanagi.
#	LastUpdate : 19:51 2011/06/19

#******************************************************************************************************************
#	設定値
class base_config{
	#--------------------------------------
	#	実行モード設定
	#	[ 指定値 ]
	#		null = 通常モード
	#		'setup' = セットアップ検証モード。コンフィグの設定値チェックなど。
	#		'maintenance' = 常にメンテナンス中画面を表示する。何か突発的な緊急の問題が発生した場合に使用します。
	var $system_exec_mode = null;

	#--------------------------------------
	#	デバッグモード設定
	#	※開発環境でのみ有効
	var $debug_mode = false;
	var $debug_mode_print_exec_microtime = true;
		//	$themeのprint_and_exit()が、exit;を実行する前に、実行にかかったmicrotimeを標準出力します。
		//	debug_mode が true の場合のみ有効
	var $allow_picklesinfo_service = false;
		//	PICKLESINFOサービスを有効にするか？
		//	このサービスを有効にすると、
		//	PicklesFrameworkの内部設定などを手軽に確認できるなど、
		//	デバッグに役立ちます。
		//		EX: http://www.sample.jp/xxx.php?PICKLESINFO=xxxx
		//			PICKLESINFO=setupmode => セットアップモードを実行
		//	ただし、この機能を有効にすることには、セキュリティ上の問題があります。
		//	実運用中のプロジェクトでは、有効にしないでください。

	#--------------------------------------
	#	アクセス制限
	var $allow_client_ip = array();
		#	【クライアントIP制限】
		#	アクセスを許可するクライアントIPアドレスを配列で列挙。
		#	全てのIPを許可する場合は、nullまたは要素を持たない配列とする。
	var $out_of_servicetime = array();
		#	【サービス時間外設定】
		#	夜間のメンテナンス時間など、
		#	サービス停止の時間帯を設定します。
		#	ここに設定された時間にアクセスしたユーザに対して、
		#	通常のコンテンツを出力しません。

	#--------------------------------------
	#	ログイン設定
	var $allow_login_without_cookies = false;
		#	クッキーなしのログインを許可するか。
		#	これをtrueにすると、ユーザIDとセッションIDを
		#	$req->gene()で持ちまわるようになります。
		#	パスワードは、セッション上に保存されます。
	var $allow_login_with_device_id = false;
		#	端末IDによる認証を許可するか。
		#	これをtrueにすると、非ログイン状況化において端末IDを認識した場合に、
		#	ユーザ情報を検索してログインを試みます。
		#	ユーザ情報をファイル管理している場合は、
		#	ユーザ情報検索に非常に大きな負荷がかかるため、
		#	false に設定することをお勧めします。
	var $try_to_login = 'TRYTOLOGIN';
		#	クエリに、このキーが含まれない場合、
		#	ID、パスワード、端末IDなどを受け取ってもログインしません。
		#	この値をnullに設定した場合、
		#	ID+パスワードまたは端末IDを検知したら、自動的にログインを試みます。
	var $user_auth_method = 'default';
		#	ユーザ認証方法を設定します。
		#	Pickles Framework 0.3.6 で追加されました。
		#	'default' は規定値です。HTMLの認証画面を表示して、パスワードの入力を求めます。
		#	'basic' は、基本認証にログインしたユーザのIDとパスワードを、ユーザデータベースと照合するモードです。
		#	この何れかが設定できます。
	var $user_keep_userid_on_session = false;
		#	ログインユーザのユーザIDをセッションに保持するか否か。
		#	これをtrueにした場合、ユーザIDとパスワードのセットをセッションに保持します。
		#	falseにした場合、パスワードのみセッションに保持し、ユーザIDはクッキーに保持します。
	var $session_key = 'PXSID';
		#	セッションのキーとなる文字列。
	var $session_expire = 1800;
		#	セッションの有効期限。(単位：秒)
	var $session_cache_limiter = 'nocache';
		#	セッションキャッシュリミッタ。
		#	PHPのsession_cache_limiter()に渡されます。
	var $session_save_path = null;
		#	セッションファイルの保存先パス。
		#	PHPのデフォルトを使用する場合は null を設定。
	var $session_always_addgene_if_without_cookies = false;
		#	クッキーが無効の環境に対して、常にセッションIDを持ちまわるか否か。
		#	trueの場合、必ずセッションキーをURLパラメータで持ちまわります。
		#	falseの場合、ログイン中のみ持ちまわります。
		#	allow_login_without_cookies がfalseの場合、
		#	および、クライアントがクッキーに対応している場合は無効。
	var $seckey = 'PX';
		#	ユーザパスワードの暗号化キー( crypt関数に渡される )。
		#	省略時はuser_idを使用。固定文字列[ MD5 ]を指定すると、md5()関数により暗号化される。
	var $logging_expire = 1800;
		#	ログイン状態を保持する時間。ログイン状態の有効期限。


	#--------------------------------------
	#	PHPの設定
	#	[文字コード設定]
	var $php_default_charset = 'UTF-8';
	var $php_mb_internal_encoding = 'UTF-8';
	var $php_mb_http_input = 'UTF-8';
	var $php_mb_http_output = 'UTF-8';

	#--------------------------------------
	#	プロジェクトプロパティ設定
	var $info_projectid = '';			//	プロジェクトID
	var $info_sitetitle = '';			//	サイトタイトル
	var $info_copyright = array( 'copy'=>'' , 'since_from'=>'now' , 'since_to'=>'now' );
										//	著作権情報
	var $show_invisiblepage = false;	//	非公開のものを表示するか否か（Pickles Framework 0.5.2 公開日が未来のもの、終了日が過去のものには影響しないように仕様を変更した。
//	var $enable_urlmap = true;			//	URLマップを使用する。(サイトマップにて指定されたパスによるアクセスを解決する) (PxFW 0.6.2 デフォルトが true に変更されました; PxFW 0.7.0 廃止されました)
	var $flg_staticurl = true;			//	スタティックなURLを使用する(通常はtrueにしてください)
	var $default_theme_id = 'default';	//	デフォルトのテーマID。
	var $authinfo = array(				//	認証情報(PxFW 0.7.2) 基本認証などの情報。
		'SELF'=>array(					//		←自分の認証情報
			'type'=>null,				//		←basic|digest
			'id'=>null,
			'passwd'=>null
		)
	);

	#--------------------------------------
	#	内部パス設定
	var $path_root;						//	このフレームワークが触ることができる領域の最上階層
	var $path_docroot;					//	ドキュメントルートの内部パス。ウェブに公開されるパス。
	var $path_lib_base;					//	ベース層ライブラリのパス
	var $path_lib_package;				//	パッケージ層ライブラリのパス
	var $path_lib_project;				//	プロジェクト層ライブラリのパス
	var $path_userdir;					//	ユーザデータを格納するディレクトリ
		#	ユーザ情報の認証にデータベースを使用する場合は、
		#	$rdb_usertable にテーブル名を登録する
	var $path_projectroot;				//	プロジェクトのホームディレクトリ
	var $path_contents_dir;				//	コンテンツディレクトリ
	var $path_sitemap_dir;				//	サイトマップディレクトリ
	var $path_theme_collection_dir;		//	テーマコレクションディレクトリ
	var $path_ramdata_dir;				//	RAMデータディレクトリ
	var $path_romdata_dir;				//	ROMデータディレクトリ
	var $path_system_dir;				//	システムデータディレクトリ
	var $path_cache_dir;				//	キャッシュディレクトリ
	var $path_common_log_dir;			//	汎用ログディレクトリ(Pickles Framework 0.1.3 追加)
	var $path_phpcommand = 'php';		//	PHPコマンドのパス(コマンドラインからフレームワークを実行する際に使用)

	#--------------------------------------
	#	ログ保存先のディレクトリ設定
	var $errors_log_path;			#	絶対パス指定
	var $errors_log_rotate = null;	#	エラーログのローテーション設定。
	var $access_log_path;			#	絶対パス指定
	var $access_log_rotate = null;	#	アクセスログのローテーション設定。
		#	ローテーション設定値は、
		#		null => ローテーションしない
		#		'hourly' => 毎時ローテーション。年月日時をファイル名の末尾に付加(xxxxlog_20060807_24.log)
		#		'daily' => 毎日ローテーション。年月日をファイル名の末尾に付加(xxxxlog_20060807.log)
		#		'monthly' => 毎月ローテーション。年月をファイル名の末尾に付加(xxxxlog_200608.log)
		#		'yearly' => 毎年ローテーション。年をファイル名の末尾に付加(xxxxlog_2006.log)

	#--------------------------------------
	#	外部パス設定
	var $url_action = '';
	var $url_domain = '';
	var $url_root = '';
	var $url_sitetop = '';
	var $url_resource = '/common';
	var $url_localresource = '/cache/cont.items';
	var $url_themeresource = '/cache/theme.items';
		#	PxFW 0.7.2 デフォルト値変更：/cache の中にまとめた。

	var $generate_localresourcepath_by_pid = false;
		#	23:39 2008/02/18 Pickles Framework 0.2.7 追加
		#	コンテンツリソースのパスをページIDから生成するフラグ。

	var $default_filename = 'index.html';
		#	デフォルトのファイル名。
		#	省略すると、http://www.xxx.jp/index.php/abc/ というようなURLとなるが、
		#	この値を、例えば「index.html」に設定すると、
		#	http://www.xxx.jp/index.php/abc/index.html のようなURLとして解釈する。
		#	PxFW 0.6.7 : デフォルト値を null から 'index.html' に変更した。

	#--------------------------------------
	#	言語設定
	var $allow_lang = array( 'ja'=>true , 'en'=>true );
	var $default_lang = 'ja';

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
	var $contents_start_str = '';
	var $contents_end_str = '';
		#	コンテンツ解析時に、有効とする範囲を示すコメント文字列。
		#	$contents_start_str から $contents_end_str までを有効なコンテンツとみなし、
		#	それ以外の領域は、破棄される。
		#	コンテンツ中にこれらの文字列が存在しない場合は、全部を有効範囲として処理される。
		#	PxFW 0.7.2 : デフォルト値だった <!--BODYSTART--> と <!--BODYEND--> を、それぞれ空白文字に変更。
	var $auto_notfound = true;
		#	Pickles Framework 0.5.1 追加
		#	存在しないページIDにアクセスした際に、自動的にNotFound画面を表示するフラグ。
		#	false に設定される場合、トップページで pv() を受け取れる。
	var $enable_contents_preprocessor = false;
		#	Pickles Framework 0.5.2 追加
		#	コンテンツのプリプロセッサ機能を有効/無効にする。
	var $allow_flush_content_without_pages = false;
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

//	var $use_sitemapcache_flg = true;//PxFW 0.7.0 廃止
		#	サイトマップキャッシュを使用するか否かを設定します。
		#	この設定がtrueの場合、クラス base_lib_site は、
		#	アクセスがあった言語のサイトマップを、透過的にキャッシュし、
		#	次回以降、同じ言語でのアクセス時には、
		#	CSVの解析を行わず、キャッシュからサイトマップをロードします。
		#	この設定をfalseにすると、
		#	サイトマップキャッシュの生成を行わず、
		#	また、キャッシュされていても、CSVから都度ロードするようになります。

	var $dbh_file_default_permission = 0775;
	var $dbh_dir_default_permission = 0775;
		#	ファイル作成時、ディレクトリ作成時の、
		#	デフォルトのパーミッション設定。

	var $dbh_heavy_query_limit = 0.5;
		#	Heavy Queryと判断されるまでの秒数。
		#	intまたはfloatを指定した場合のみ、有効となります。
		#	デフォルトは0.5秒です。

	var $path_commands = array();
		#	UNIXコマンドのパス設定。
		#	array( {$コマンド名}=>{$コマンドパス} );
		#	の形式で、設定する。
		#	(PHPのコマンドは、$conf->path_phpcommand に用意されているため、ここには含めない)
		#	Pickles Framework 0.1.3 で追加。

	var $fs_encoding = null;
		#	ファイルシステムが使用するデフォルトの文字コード設定。
		#	デフォルトは null。(nullを指定すると無視されます)
		#	PxFW 0.6.4 で追加されました。

	var $allow_cancel_customtheme = false;
		#	カスタムテーマのロードをキャンセルできる機能(index.php?THEME=null)を
		#	有効(=true)または無効(=false)にする。
		#	Pickles Framework 0.1.8 で追加されました。

	var $cmd_default_authlevel = 9;
		#	コマンドラインから起動した場合の、
		#	デフォルトの権限レベル。
		#	21:28 2007/11/21 Pickles Framework 0.2.0 追加

	var $enable_externalurl = false;
		#	EXTERNALURL機能の有効/無効を切り替える。
		#	PxFW 0.6.10 追加。

	#--------------------------------------
	#	接続先データベースの設定
	var $rdb = array( 'type'=>'' , 'version'=>'' , 'server'=>'' , 'user'=>'' , 'passwd'=>'' , 'name'=>'' , 'port'=>'' , 'charset'=>'' , 'sessionmode'=>'' );
		#	type = RDBアプリケーションの種類。'MySQL' or 'PostgreSQL' or 'SQLite' or 'Oracle'
		#	version = 'type'のバージョン
		#	server = DBサーバのアドレス
		#	user = DBへのログインユーザ名
		#	passwd = DBへのログインパスワード
		#	name = データベース名(SQLiteの場合はDBファイルのパス)
		#	port = DB接続のポート番号
		#	charset = DBのデフォルト文字セット
		#	sessionmode = セッションモード(Oracle使用時のみ)
	var $rdb_usertable = null;
		#	ユーザ情報をRDBで管理する場合に、配列で登録。
		#	RDBを使用しない場合は、nullを指定。
		#	この値が、is_array() でtrueを返すと、RDBモードとして探しにいっちゃいます。
		#	次の8つのキーで連想配列をつくり、それぞれ、テーブル名を指定してください。
		#		master = ユーザマスタテーブル
		#		property = ユーザの詳細情報テーブル
		#		project_authoptions = ユーザの権限オプションテーブル
		#		project_authgroup = 所属グループテーブル
		#		project_status = ユーザのステータスを格納するテーブル
		#		project_datas = アプリケーションが設定する、ユーザ個人に紐づくデータを格納するテーブル
		#		group = ユーザグループのマスタテーブル
		#		group_authoptions = ユーザグループの権限オプションテーブル


	#--------------------------------------
	#	動的に設定される値
	#	【注意】これらの値は、PicklesFrameworkが動的に設定する項目です。
	#			設定ファイル(config.php)上では、ブランクのままにしておいてください。
	var $time = 0;	//	システムが呼び出された時刻
	var $T1 = 0;	//	システムが呼び出された時刻
		#	これらの値は、クラス base_lib_conductor が、動的に設定します。
		#	($time、$T1ともに、現在時刻 time() で上書き設定されます。)
		#	MEMO: $T1は将来的に廃止にし、$timeに統合したい方針です。
	var $microtime = 0;	//	システムが呼び出された時刻(マイクロ秒単位)
		#	Pickles Framework 0.5.0 追加
		#	※それ以前のバージョンでも、$conductor によってセットされていたが、正式に追加。
	var $CT = null;
		#	クライアント端末の種別を表す文字列。
		#	この値は、クラス base_lib_user が、動的に設定します。
		#	ex. PC, MP, PDA など
	var $theme_id = null;
		#	選択されたテーマのID。
		#	この値は、クラス base_lib_user が、動的に設定します。

}

?>