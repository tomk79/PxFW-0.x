<?php

#	Copyright (C)Tomoya Koyanagi.
#	LastUpdate : 9:18 2011/07/22

#******************************************************************************************************************
#	UTF-8で編集してください。
#	HTMLドキュメントのテーマクラス
class base_lib_theme{

	var $conf;
	var $req;
	var $dbh;
	var $user;
	var $site;
	var $errors;
	var $custom;

	var $menulist_global = array();
		# グローバルメニューとして表示するページIDを登録する。
	var $menulist_shoulder = array();
		# ショルダーメニューとして表示するページIDを登録する。

	var $dateformatlist = array(
		'date'=>'Y/m/d(D)' ,
		'datetime'=>'Y/m/d(D) H:i:s' ,
		'time'=>'H:i:s' ,
		'YmdHis'=>'Y/m/d H:i:s' ,
		'YmdHi'=>'Y/m/d H:i' ,
		'Ymd'=>'Y/m/d' ,
		'Ym'=>'Y/m' ,
	);
		#	日付や時刻の表示フォーマット定義。
		#	Pickles Framework 0.5.8 で追加。
		#	PHP の date() 関数に、フォーマットとして渡せる形式で格納する。
		#	( この値は setup() で初期化しなおされます。 )

	var $default_contenttype = 'text/html';

	var $classname_of_activelink = 'active';
		#	アクティブなリンクに設定するCSSクラス名。

//	var $root;//PxFW 0.6.2 廃止
	var $src = array(
		/* HTMLのコンテンツ部分のソース */
		'body'=>'',					#	★コンテンツが作成したソースすべてを格納。最終的にクライアントへ出力されるソースになる。
		'breadcrumb'=>'',			#	パンくず部分のソース
		'localnavigator'=>'',		#	ローカルナビゲーションのソース
		'side1'=>'',				#	サイドバー(ナビ部分)に追記するソース
		'side2'=>'',				#	サイドバー(ナビ部分)に追記するソース
		'onloadscript'=>'',			#	onloadで動作するJavaScriptソース
		'css'=>'',					#	スタイルシートソース
		'additional_header'=>'',	#	<head>セクションに追加するソース
	);

	var $localconf_full_window_width = '98%';
	var $localconf_filename_default = '';
	var $localconf_outputencoding = null;

	var $message = array();

	var $selectcontent = array();

	#	★スタイルオブジェクト系
	var $mklink;
	var $mkhx;
	var $ifmodule;
	var $view_outline;

	#	★JavaScript関連オブジェクト系
	var $js_rollover;

	#	★その他機能別記憶領域
	var $autoindex_memodata = null;
		# mk_autoindex() が一時的に挿入する置換文字列。
		# PxFW 0.6.12 追加
	var $includes_memodata = null;
		# include_page() などが一時的に挿入する置換文字列。
		# PxFW 0.7.2 追加

	var $relatedlink = array();
		# パブリッシュツールに知らせる関連リンクの一覧。
		# PxFW 0.7.2 追加

	#----------------------------------------------------------------------------
	#	$themeオブジェクトのセットアップ
	function setup( &$conf , &$req , &$dbh , &$user , &$site , &$errors , &$custom ){
		$this->conf = &$conf;
		$this->req = &$req;
		$this->dbh = &$dbh;
		$this->user = &$user;
		$this->site = &$site;
		$this->errors = &$errors;
		$this->custom = &$custom;	# Pickles Framework 0.3.4 以降、setup() は $custom を受け取るようになりました。

		#	設定調整
		if( strlen( $this->conf->default_filename ) ){
			#	デフォルトのファイル名が設定されていたら上書き。
			$this->localconf_filename_default = $this->conf->default_filename;
		}

		#	ビューオブジェクトを生成
		$this->view_outline = &$this->styleobjectfactory( 'outline' );

		/* ドキュメントルートまでのパス */
//		$this->root = $this->conf->url_root;//PxFW 0.6.2 廃止

		/* 標準の色情報をセット */
		$this->setcolor_defaultsetup();

		/* 出力文字コードを初期化 */
		$this->set_output_encoding();

		$this->message = $this->parse_messagestring();

		/* ロールオーバーのJSオブジェクトを作成 */
		$class_rollover = $this->dbh->require_lib( '/resources/jsctrl/rollover.php' );
		if( class_exists( $class_rollover ) ){
			$this->js_rollover = new $class_rollover();
		}

		//	日付フォーマットをセット
		if( $this->user->getlanguage() == 'ja' ){
			$this->set_dateformat( 'date'     , 'Y年n月j日(D)' );
			$this->set_dateformat( 'datetime' , 'Y年n月j日(D) G時i分s秒' );
			$this->set_dateformat( 'time'     , 'G時i分s秒' );
			$this->set_dateformat( 'YmdHis'   , 'Y年m月d日 H時i分s秒' );
			$this->set_dateformat( 'YmdHi'    , 'Y年m月d日 H時i分' );
			$this->set_dateformat( 'Ymd'      , 'Y年m月d日' );
			$this->set_dateformat( 'Ym'       , 'Y年m月' );
		}

		$this->setup_subaction();
	}


	#----------------------------------------------------------------------------
	#	カスタムオブジェクトを受け取る
	function set_custom_object( &$custom ){//古いメソッド
		#	$customは$conductor内部で$themeよりも後に生成されるという都合上、
		#	setup()とは別に、このメソッドで$themeに渡されます。
		$this->custom = &$custom;
		#	Pickles Framework 0.3.4 ： このメソッドは使用されなくなりました。
		#	$custom が $theme よりも前に生成されるように仕様が変更され、
		#	$this->setup() が $custom を受け取るようになったためです。
		#	現在このメソッドは、単に過去への互換性のためだけに残されています。
		#	このメソッドは、将来削除される予定です。
	}


	#----------------------------------------------------------------------------
	#	セットアップ拡張用メソッド
	function setup_subaction(){
		#	$theme のセットアップに、追加の拡張処理を加える場合は、
		#	このメソッドを上書きして、ここに記述してください。
		#	基本的なセットアップ処理 setup() を生かしたまま拡張することができます。
		return	true;
	}


	#	テーマスタイル系オブジェクトを生成して返す。
	function &styleobjectfactory( $objname ){

		//↓PDA, MP のテーマ層ライブラリがロードされない不具合を修正。
		//  Pickles Framework 0.4.9 (11:19 2008/09/13)
		$lib_localpath = '/resources/viewstyle/'.$objname.'.php';
		$lib_localpath = preg_replace( '/^\/*/' , '/' , $lib_localpath );
		$lib_localpath = preg_replace( '/\/+/' , '/' , $lib_localpath );
		$classname_body = str_replace( '/' , '_' , text::trimext( $lib_localpath ) );

		$rootpath_tmp = $this->conf->path_theme_collection_dir.$this->user->gettheme().'/'.$this->user->get_ct().'/lib';

		if( isolated::require_once_with_conf( $rootpath_tmp.$lib_localpath , &$this->conf ) ){
			#	対象のファイルを見つけたら、
			#	パスをセットしてswitchを抜ける。
			if( class_exists( 'theme'.$classname_body ) ){
				#	クラスがちゃんと存在したら。
				$class_name = 'theme'.$classname_body;
				return	new $class_name( &$this->conf , &$this->user , &$this->site , &$this->req , &$this->dbh , &$this , &$this->errors , &$this->custom );
			}
		}
			//↑PDA, MP のテーマ層ライブラリがロードされない不具合を修正。
			//  Pickles Framework 0.4.9 (11:19 2008/09/13)

		$class_name = $this->dbh->require_lib( '/resources/viewstyle/'.$objname.'_'.$this->user->get_ct().'.php' , 'THEME' , $this->user->get_ct() , $this->user->gettheme() );
		if( class_exists( $class_name ) ){
			return	new $class_name( &$this->conf , &$this->user , &$this->site , &$this->req , &$this->dbh , &$this , &$this->errors , &$this->custom );
		}

		$class_name = $this->dbh->require_lib( '/resources/viewstyle/'.$objname.'.php' , 'THEME' , $this->user->get_ct() , $this->user->gettheme() );
		if( class_exists( $class_name ) ){
			return	new $class_name( &$this->conf , &$this->user , &$this->site , &$this->req , &$this->dbh , &$this , &$this->errors , &$this->custom );
		}
		return	false;
	}


	#----------------------------------------------------------------------------
	#	ソースをセットする
	function setsrc( $content , $type = 'body' ){
		if( !is_string( $content ) ){
			$this->src[$type] = 'Error on setsrc(): '.gettype( $content ).'型の値を受け取りました。';
			return	false;
		}

#		#	Pickles Framework 0.5.7 : キーが定義されているかどうかチェックしないことにした。
#		if( !is_null( $this->src[$type] ) ){
#			$this->src[$type] = $content;
#			return	true;
#		}
#		return	false;

		$this->src[$type] = $content;
		return	true;
	}
	#----------------------------------------------------------------------------
	#	ソースを追加する
	function putsrc( $content , $type = 'body' ){
		#	Pickles Framework 0.2.3 追加 - 19:28 2008/01/10 TomK
		#	Pickles Framework 0.5.10 修正 - キーが定義されているかどうかチェックしないことにした。(0.5.7の修正漏れ)
		if( !is_string( $content ) ){
			$this->src[$type] .= 'Error on putsrc(): '.gettype( $content ).'型の値を受け取りました。';
			return	false;
		}
		if( !strlen( $this->src[$type] ) ){
			$this->src[$type] = '';
		}
		$this->src[$type] .= $content;
		return	true;
	}
	#----------------------------------------------------------------------------
	#	ソースを取得する
	function getsrc( $type = 'body' ){
		return	$this->src[$type];
	}


	#----------------------------------------------------------------------------
	#	カレントページのタイトルを取得する
	function gettitle( $type = 'document' ){
		if( !strlen( $type ) ){ $type = ''; }
		$type = strtolower( $type );
		$this_pid = $this->site->getpageinfo( $this->req->p() , 'id' ).'';
		if( $type == 'document' || $type == '' ){
			return	$this->site->getpageinfo( $this_pid , 'title' );
		}elseif( $type == 'page' ){
			return	$this->site->getpageinfo( $this_pid , 'title_page' );
		}elseif( $type == 'breadcrumb' ){
			return	$this->site->getpageinfo( $this_pid , 'title_breadcrumb' );
		}elseif( $type == 'label' ){
			return	$this->site->getpageinfo( $this_pid , 'title_label' );
		}elseif( $type == 'category' ){
			return	$this->site->getpageinfo( $this->site->getpageinfo( $this_pid , 'cattitleby' ) , 'title' );
		}elseif( $type == 'site' ){
			return	$this->site->gettitle();
		}
		$this->errors->error_log( '$theme->gettitle(): ['.$type.'] is unknown type.' , __FILE__ , __LINE__ );
		return	'** Title Unknown **';
	}


	#----------------------------------------------------------------------------
	#	カレントページのメタナビゲータタグを自動生成する
	function autocreate_metanavigation( $indent = '		' ){
		$RTN = '';
		if( strlen( $this->req->p() ) ){
			$RTN .= $indent.'<link rel="start" href="'.htmlspecialchars( $this->href('') ).'" title="HOME" />'."\n";
		}
		$RTN .= text::boolfilter( strlen( $this->site->getpageinfo( 'help' , 'id' ) ) , $indent.'<link rel="help" href="'.htmlspecialchars( $this->href( 'help' ) ).'" title="HELP" />'."\n" );
		$RTN .= text::boolfilter( strlen( $this->site->getpageinfo( 'glossary' , 'id' ) ) , $indent.'<link rel="glossary" href="'.htmlspecialchars( $this->href( 'glossary' ) ).'" title="glossary" />'."\n" );
		$RTN .= text::boolfilter( strlen( $this->site->getpageinfo( 'copyright' , 'id' ) ) , $indent.'<link rel="copyright" href="'.htmlspecialchars( $this->href( 'copyright' ) ).'" title="copyright" />'."\n" );
		$RTN .= text::boolfilter( strlen( $this->site->getpageinfo( 'author' , 'id' ) ) , $indent.'<link rel="author" href="'.htmlspecialchars( $this->href( 'author' ) ).'" title="author" />'."\n" );
		$RTN .= text::boolfilter( strlen( $this->site->getpageinfo( 'search' , 'id' ) ) , $indent.'<link rel="search" href="'.htmlspecialchars( $this->href( 'search' ) ).'" title="search" />'."\n" );
		if( strlen( $this->req->p() ) ){
			$RTN .= $indent.'<link rel="up" href="'.htmlspecialchars( $this->href( $this->site->get_parent( $this->req->p() ) ) ).'" title="Up" />'."\n";
		}
		$pid_prev= $this->site->get_prev( $this->req->p() );
		if( !is_null( $pid_prev ) ){
			$RTN .= $indent.'<link rel="prev" href="'.htmlspecialchars( $this->href( $pid_prev ) ).'" title="Up" />'."\n";
		}
		$pid_next= $this->site->get_next( $this->req->p() );
		if( !is_null( $pid_next ) ){
			$RTN .= $indent.'<link rel="next" href="'.htmlspecialchars( $this->href( $pid_next ) ).'" title="Up" />'."\n";
		}
		return	$RTN;
	}

	#----------------------------------------------------------------------------
	#	このページが、ログインが必要か、不要か判断する。
	function is_closed( $pid = null ){
		if( is_null( $pid ) ){ $pid = $this->req->p(); }
		if( $this->site->getpageinfo( $pid , 'closed' ) ){
			return	true;
		}else{
			return	false;
		}
	}

	#----------------------------------------------------------------------------
	#	このページへアクセスするために必要な権限値を取得
	function getsecuritylevel( $pid = null ){
		if( is_null( $pid ) ){ $pid = $this->req->p(); }
		$RTN = intval( $this->site->getpageinfo( $pid , 'closed' ) );
		if( !$RTN ){
			return	0;
		}
		return	$RTN;
	}

	#----------------------------------------------------------------------------
	#	コンテンツ領域の最小幅
	function contents_min_width(){
		#	20:28 2007/06/11 追加されました。TomK
		#	コンテンツ領域の最小幅は、
		#	テーマが独自に設定でき、テーマによって変化し得ます。
		#	リキッドデザインを採用したテーマは、
		#	クライアントのウィンドウサイズによって、コンテンツ領域の幅が変化します。
		#	この場合でも、最低限確保されるべきコンテンツ幅として、ここに定義してください。
		#	この値は、INT型で返りますが、単位はピクセルです。
		#	コンテンツ領域の最小幅は、裏返せば、
		#	「コンテンツ側が使用できるスペースの最大幅」となります。
		#	ここで返される値のコンテンツ領域を確保するのは、テーマの責任となりますが、
		#	この幅以内でデザインするのは、コンテンツ側の責任となります。
		#	この値は、外部から動的に書き換えられては困る値です。
		#	そのため、プロパティとして持たず、セッターメソッドも持たず、
		#	contents_min_width()が、固定値として実装します。
		#	この値を変更する方法は、テーマクラスがオーバーライドする以外にありません。
		return	550;
	}//function contents_min_width();

	#----------------------------------------------------------------------------
	#	文字情報を一元管理するためのメソッド。
	#	$KEY によって返す文字列を決める。
	#	コンテンツレベルで凍結させたくない部分を持ちたい場合に、
	#	ここで管理し、$KEY で呼び出す。
	function ifmodule( $module_name ){
		#	ifmoduleを起動
		if( !is_object( $this->ifmodule ) ){
			$this->ifmodule = &$this->styleobjectfactory( 'ifmodule' );
		}
		return	$this->ifmodule->get_module( func_get_args() );
	}//function ifmodule();

	#----------------------------------------------------------------------------
	#	外部ソースをインクルードする(ServerSideInclude)
	#	PxFW 0.7.2 追加
	function ssi( $path_incfile ){ return $this->include_resource( $path_incfile ); }
	function include_resource( $path_incfile ){
		//	パブリッシュツール(PxCrawlerなど)による静的パブリッシュを前提としたSSI処理機能。
		//	ブラウザで確認した場合は、インクルードを解決したソースを出力し、
		//	パブリッシュツールに対しては、ApacheのSSIタグを出力する。
		//	インクルード対象はリソースファイル。
		//	ページをインクルードする場合は $theme->ssi() を使用する。

		if( !strlen( $path_incfile ) ){ return false; }
		$RTN = '';
		$path_incfile = $this->resource( $path_incfile );
		if( $this->user->is_publishtool() ){
			#	パブリッシュツールだったら、SSIタグを出力する。
			$RTN .= $this->mk_static_ssi( $path_incfile );
			$this->add_relatedlink( $path_incfile );
		}else{
			$path_inc = $path_incfile;
			if( $this->dbh->is_file( $_SERVER['DOCUMENT_ROOT'].$path_inc ) && $this->dbh->is_readable( $_SERVER['DOCUMENT_ROOT'].$path_inc ) ){
				$RTN .= $this->dbh->file_get_contents( $_SERVER['DOCUMENT_ROOT'].$path_inc );
				$RTN = text::convert_encoding($RTN);//文字コードを内部エンコードに合わせて変換
			}
		}
		return	$RTN;
	}//function include_resource();

	#----------------------------------------------------------------------------
	#	ページをインクルードする
	#	PxFW 0.7.2 追加
	function include_page( $pid ){
		//	パブリッシュツール(PxCrawlerなど)による静的パブリッシュを前提としたSSI処理機能。
		//	ブラウザで確認した場合は、インクルードを解決したソースを出力し、
		//	パブリッシュツールに対しては、ApacheのSSIタグを出力する。
		//	インクルード対象はページとなる。
		//	リソースをインクルードする場合は $theme->ssi() を使用する。

		static $num = 0;

		if( !is_array( $this->includes_memodata ) ){
			$this->includes_memodata = array();
		}
		if( is_null( $this->includes_memodata[$pid] ) ){
			$this->includes_memodata[$pid] = '[__includes_'.md5( time::microtime() ).'_'.($num++).'__]';
		}
		return $this->includes_memodata[$pid];
	}//function include_page()

	#----------------------------------------------------------------------------
	#	ページ内のインクルードを解決する
	#	PxFW 0.7.2 追加
	function apply_includes( $CONTENT ){
		foreach( $this->includes_memodata as $pid=>$metastr ){
			$INCSRC = '';

			$parsed_pid = $pid;
			$parsed_params = null;
			if( preg_match( '/^(.*?)\#(.*)$/' , $parsed_pid , $matched ) ){
				$parsed_pid = $matched[1];
			}
			if( preg_match( '/^(.*?)\?(.*)$/' , $parsed_pid , $matched ) ){
				$parsed_pid = $matched[1];
				$parsed_params = $matched[2];
			}
			if( strlen($this->req->urlmap[$parsed_pid]) ){
				$parsed_pid = $this->req->urlmap[$parsed_pid];
			}
			if( preg_match( '/^\//' , $parsed_pid ) ){
				$parsed_pid = preg_replace( '/\/si'.preg_quote($this->conf->default_filename,'/').'$/' , '' , $parsed_pid );
				$parsed_pid = preg_replace( '/\..*$/si' , '' , $parsed_pid );
				$parsed_pid = preg_replace( '/\//si' , '.' , $parsed_pid );
				$parsed_pid = preg_replace( '/^\.+/si' , '' , $parsed_pid );
				$parsed_pid = preg_replace( '/\.+$/si' , '' , $parsed_pid );
			}
			unset($matched);

			$path_incfile = $this->href( $parsed_pid , array('additionalquery'=>$parsed_params) );
			if( $this->user->is_publishtool() ){
				#	パブリッシュツールだったら、SSIタグを出力する。
				$INCSRC = $this->mk_static_ssi( $path_incfile );
				$this->add_relatedlink( $path_incfile );
			}else{
				if( $this->dbh->is_unix() ){
					$tmp_gene_CT = $this->req->gene_elm('CT');
					$this->req->addgene('CT',$this->user->get_ct());
					$tmp_gene = $this->req->gene('an');
					if( !strlen( $tmp_gene_CT ) ){
						$this->req->delgene('CT');
					}
					$tmp_cmd = escapeshellcmd(($this->conf->path_phpcommand?$this->conf->path_phpcommand:'php')).' '.escapeshellcmd($_SERVER['SCRIPT_FILENAME']).' '.escapeshellarg('P='.$parsed_pid.(strlen($parsed_params)?'&'.escapeshellcmd($parsed_params):'').(strlen($tmp_gene)?'&'.$tmp_gene:''));
					$INCSRC = $this->dbh->get_cmd_stdout( $tmp_cmd );
					unset($tmp_cmd,$tmp_gene,$tmp_gene_CT);
				}
				if( !strlen($INCSRC) ){
					$url = 'http'.($this->req->is_ssl()?'s':'').'://'.$_SERVER['HTTP_HOST'].$path_incfile;
					$className = $this->dbh->require_lib( '/resources/PxHTTPAccess.php' );
					$httpaccess = new $className();
					$httpaccess->clear_request_header();//初期化
					if( strlen( $this->conf->authinfo['SELF']['id'] ) ){//基本認証情報があったら
						$httpaccess->set_auth_type( $this->conf->authinfo['SELF']['type'] );
						$httpaccess->set_auth_user( $this->conf->authinfo['SELF']['id'] );
						$httpaccess->set_auth_pw( $this->conf->authinfo['SELF']['passwd'] );
					}
					$httpaccess->set_user_agent( $_SERVER['HTTP_USER_AGENT'] );
					$httpaccess->set_url( $url );
					$httpaccess->set_method( 'GET' );

					$INCSRC = $httpaccess->get_http_contents(null);//ダウンロードを実行する
				}
				$INCSRC = text::convert_encoding( $INCSRC );//文字コードを内部エンコードに合わせて変換
			}
			$CONTENT = preg_replace( '/'.preg_quote($metastr,'/').'/si' , $INCSRC , $CONTENT );
		}
		return $CONTENT;
	}//apply_includes();

	#----------------------------------------------------------------------------
	#	X-PXFW-RELATEDLINK にURLを追加する
	function add_relatedlink( $url ){
		array_push( $this->relatedlink , $url );
		return true;
	}// function add_relatedlink();

	#----------------------------------------------------------------------------
	#	インクルードタグを作成(パブリッシュ後用)
	#	PxFW 0.7.2 追加
	function mk_static_ssi( $path ){
		return '<!--#include virtual="'.htmlspecialchars( $path ).'" -->';
	}//mk_static_ssi()

	#--------------------------------------------------------------------------------------------------------------------------------------------------------

	#----------------------------------------------------------------------------
	#	★$this->src にセットされた内容を、テンプレートに包んで返す。
	#	このメソッドが、条件によって、view_html_document_XXXXXXXX()メソッドを選んで実行します。
	function htmltemplate( $CONTENT = null ){
		$this->set_breadcrumb( $this->site->getpageinfo( $this->req->p() , 'path' ) );

		if( strlen( $CONTENT ) ){
			$this->setsrc( $CONTENT );
		}else{
			$CONTENT = $this->getsrc();
		}

		$outline_name = $this->get_outline_name();//PxFW 0.6.3 : 選定ロジックを外部化

		if( is_array( $this->autoindex_memodata ) ){ $CONTENT = $this->apply_autoindex( $CONTENT ); }
			# autoindex機能。 PxFW 0.6.12 追加

		$CONTENT = $this->view_outline->get_src( $CONTENT , $outline_name );

		if( is_array( $this->includes_memodata ) ){ $CONTENT = $this->apply_includes( $CONTENT ); }
			# include機能。 PxFW 0.7.2 追加

		return	$CONTENT;
	}

	#----------------------------------------------------------------------------
	#	採用されるアウトライン名を決める//PxFW 0.6.3 : 追加
	function get_outline_name(){
		$this->message = $this->parse_messagestring();//←Pickles Framework 0.5.2 追加

		$outline_name = '';
		$tmp_outline_list = array(
			$this->req->in('OUTLINE') ,
			$this->site->getpageinfo( $this->req->p() , 'outline' ) ,//PxFW 0.6.2 追加
			$this->getmessage('OUTLINE')
		);
		foreach( $tmp_outline_list as $tmp_outline_str ){
			if( strlen( $tmp_outline_str ) && $this->view_outline->is_style_exists( $tmp_outline_str ) ){
				$outline_name = $tmp_outline_str;
				break;
			}
		}
		return $outline_name;
	}

	#----------------------------------------------------------------------------
	#	★ログインフォームを表示して終了
	function pleaselogin(){
		while( @ob_end_clean() );
		$loginerror = null;
		if( $this->user->is_login_error() ){
			$loginerror = $this->user->get_label_login_id().'またはパスワードが違います。';
		}

		$RTN = '';
		if( $this->conf->user_auth_method == 'basic' ){
			#--------------------------------------
			#	Pickles Framework 0.3.6 で追加された
			#	新しい設定項目に対応する処理。
			$basic_auth_realm = $this->site->gettitle();
			$basic_auth_realm = preg_replace( '/["\']|\r\n|\r|\n/' , ' ' , $basic_auth_realm );
			header( 'Status: 401 Authorization Required' );
			header( 'WWW-Authenticate: Basic realm="'.addslashes( $basic_auth_realm ).'"' );//realmはサイトタイトルから取得するようにした。
			$RTN .= $this->mk_hx('認証に失敗しました。')."\n";
			$RTN .= '<p class="ttr">'."\n";
			$RTN .= '	'.htmlspecialchars( $this->user->get_label_login_id() ).'とパスワードを正しく入力してください。<br />'."\n";
			$RTN .= '</p>'."\n";
			$RTN .= '<form action="'.htmlspecialchars( $this->act() ).'" method="post" target="_top">'."\n";
			$RTN .= '	'.$this->mk_form_defvalues()."\n";
			if( strlen( $this->conf->try_to_login ) ){
				$RTN .= '	'.$this->mk_formelm_hidden( $this->conf->try_to_login , '1' )."\n";
			}
			$RTN .= '	<p class="ttr AlignC"><input type="submit" value="再試行" /></p>'."\n";
			$RTN .= '</form>'."\n";

		}else{
			#--------------------------------------
			#	通常の認証方法ならこちら。
			$RTN .= $this->mk_hx('ログインしてください。')."\n";
			if( $loginerror ){
				#	エラーがあれば表示
				$RTN .= '<div class="ttr error">'.$loginerror.'</div>'."\n";
			}
			$RTN .= '<form action="'.htmlspecialchars( $this->act() ).'" method="post" target="_top">'."\n";
			$RTN .= '	<div class="ttr">'.htmlspecialchars( $this->user->get_label_login_id() ).':</div>'."\n";
			$RTN .= '	<div class="ttr"><input type="text" name="ID" value="'.htmlspecialchars( $this->req->in('ID') ).'" /></div>'."\n";
			$RTN .= '	<div class="ttr">パスワード:</div>'."\n";
			$RTN .= '	<div class="ttr"><input type="password" name="PW" value="" /></div>'."\n";
			$RTN .= '	<div>';
			$RTN .= $this->mk_form_defvalues();
			if( strlen( $this->conf->try_to_login ) ){
				$RTN .= $this->mk_formelm_hidden( $this->conf->try_to_login , '1' );
			}
			$RTN .= '</div>'."\n";
			$RTN .= '	<p class="ttr AlignC"><input type="submit" name="submit" value="ログイン" /></p>'."\n";
			$RTN .= '</form>'."\n";
		}

		$this->setsrc( $RTN );
		return	$this->print_and_exit();
	}

	#----------------------------------------------------------------------------
	#	★ログアウトする
	function logout(){
		while( @ob_end_clean() );
		if( $this->user->logout() ){
			$RTN .= '<p class="ttr">'."\n";
			$RTN .= '	正常にログアウトしました。<br />'."\n";
			$RTN .= '</p>'."\n";
		}else{
			$RTN .= '<p class="ttr">'."\n";
			$RTN .= '	ログアウトに失敗しました。<br />'."\n";
			$RTN .= '</p>'."\n";
		}
		$RTN .= '<form action="'.htmlspecialchars( $this->act('') ).'" method="post" target="_top">'."\n";
		$RTN .= '	<div>'.$this->mk_form_defvalues('').'</div>'."\n";
		$RTN .= '	<p class="ttr AlignC"><input type="submit" value="戻る" /></p>'."\n";
		$RTN .= '</form>'."\n";

		$this->setsrc( $RTN );
		return	$this->print_and_exit();
	}

	#----------------------------------------------------------------------------
	#	★アクセス可能時間外であることを通知して終了
	function out_of_servicetime(){
		while( @ob_end_clean() );

		$RTN = '';
		$RTN .= '<p class="ttr">'."\n";
		$RTN .= '	只今の時間は、ご利用いただけません。<br />'."\n";
		$RTN .= '	しばらくしてから、もう一度アクセスしてください。<br />'."\n";
		$RTN .= '</p>'."\n";

		$this->setsrc( $RTN );
		return	$this->print_and_exit();
	}

	#----------------------------------------------------------------------------
	#	★アクセス許可が無いことを通知して終了
	function printforbidden( $MSG = null ){
		@header( 'HTTP/1.1 403 Forbidden' );
		@header( 'Status: 403 Forbidden' );
		while( @ob_end_clean() );

		#--------------------------------------
		#	ページ情報をリセット
		$title_label = $this->site->getpageinfo( $this->site->getpageinfo( $this->req->p() , 'id' ).'' , 'title_label' );

		$this_pid = 'forbidden';
		$this_pid_num = null;
		while( $this->site->getpageinfo( $this_pid.$this_pid_num , 'id' ) == $this_pid.$this_pid_num ){ $this_pid_num++; }
		$this_pid = $this_pid.$this_pid_num;
		$this->req->setin( $this->req->pkey() , $this_pid );
		$this->req->parse_p();
		$this->req->parse_pv( &$this->site );

		$this->site->setpageinfoall(
			$this_pid ,
			array(
				'title' => '403 Forbidden' ,
				'title_page' => '403 Forbidden' ,
				'title_breadcrumb' => '403 Forbidden' ,
				'title_label' => $title_label ,
				'path' => '' ,
				'cattitleby' => '' ,
			)
		);
		#	/ ページ情報をリセット
		#--------------------------------------

		$RTN = '';
		$RTN .= '<p class="ttr">'."\n";
		$RTN .= '	このページにアクセスするための許可がありません。<br />'."\n";
		if( strlen( $MSG ) ){
			$RTN .= '	'.htmlspecialchars( $MSG ).'<br />'."\n";
		}
		$RTN .= '</p>'."\n";
		$RTN .= '<ul class="none">'."\n";
		$RTN .= '	<li class="ttr">'.$this->mk_link( '' , array( 'style'=>'inside','active'=>false ) ).'</li>'."\n";
		$RTN .= '</ul>'."\n";

		$this->setsrc( $RTN );
		return	$this->print_and_exit();
	}

	#----------------------------------------------------------------------------
	#	★ページがないことを通知して終了
	function printnotfound(){
		@header( 'HTTP/1.1 404 Not Found' );
		@header( 'Status: 404 Not Found' );
		while( @ob_end_clean() );

		#--------------------------------------
		#	ページ情報をリセット
		$title_label = $this->site->getpageinfo( $this->site->getpageinfo( $this->req->p() , 'id' ).'' , 'title_label' );

		$this_pid = 'notfound';
		$this_pid_num = null;
		while( $this->site->getpageinfo( $this_pid.$this_pid_num , 'id' ) == $this_pid.$this_pid_num ){ $this_pid_num++; }
		$this_pid = $this_pid.$this_pid_num;
		$this->req->setin( $this->req->pkey() , $this_pid );
		$this->req->parse_p();
		$this->req->parse_pv( &$this->site );

		$this->site->setpageinfoall(
			$this_pid ,
			array(
				'title' => '404 Not Found' ,
				'title_page' => '404 Not Found' ,
				'title_breadcrumb' => '404 Not Found' ,
				'title_label' => $title_label ,
				'path' => '' ,
				'cattitleby' => '' ,
			)
		);
		#	/ ページ情報をリセット
		#--------------------------------------

		$RTN = '';
		$RTN .= '<p class="ttr">'."\n";
		$RTN .= '	お探しのページはサーバ上にありません。<br />'."\n";
		$RTN .= '</p>'."\n";
		$RTN .= '<ul>'."\n";
		$RTN .= '	<li class="ttr">URLに間違いがないか、タイプミスがないか、もう一度お確かめください。</li>'."\n";
		$RTN .= '	<li class="ttr">お探しのページは削除されたか、移動した可能性があります。</li>'."\n";
		$RTN .= '</ul>'."\n";
		$RTN .= '<ul class="none">'."\n";
		$RTN .= '	<li class="ttr">'.$this->mk_link( '' , array( 'style'=>'inside','active'=>false ) ).'</li>'."\n";
		$RTN .= '</ul>'."\n";

		$this->setsrc( $RTN );
		return	$this->print_and_exit();
	}

	#----------------------------------------------------------------------------
	#	★エラーの内容を表示して終了する
	function errorend( $error_message , $FILE = null , $LINE = null ){
		while( @ob_end_clean() );

		$RTN = '';
		$RTN .= '<h2>エラーが発生しました。</h2>'."\n";
		$RTN .= '<p class="ttr error">'.htmlspecialchars( $error_message ).'</p>'."\n";
		if( $this->conf->debug_mode ){
			$RTN .= '<p class="ttr">'.htmlspecialchars( $FILE ).' Line: '.htmlspecialchars( $LINE ).'</p>'."\n";

			#	デバッグトレース
			$RTN .= '<h3>Debug Back Trace</h3>'."\n";
			$debug = @debug_backtrace();
			if( is_array( $debug ) && count( $debug ) ){
				$RTN .= '<dl>'."\n";
				foreach( array_keys( $debug ) as $KEY ){
					$RTN .= '<dt class="ttr">'.htmlspecialchars( $KEY ).'</dt>'."\n";
					$RTN .= '<dd class="ttr">'."\n";
					$RTN .= '	<strong>file</strong> = '.htmlspecialchars( $debug[$KEY]['file'] ).' Line: '.htmlspecialchars( $debug[$KEY]['line'] ).'<br />'."\n";
					$RTN .= '	<strong>function</strong> = '.htmlspecialchars( $debug[$KEY]['function'] ).'<br />'."\n";
					$RTN .= '	<strong>class</strong> = '.htmlspecialchars( $debug[$KEY]['class'] ).'<br />'."\n";
					$RTN .= '	<strong>type</strong> = '.htmlspecialchars( $debug[$KEY]['type'] ).'<br />'."\n";
					$RTN .= '	<strong>args</strong> = '.count( $debug[$KEY]['args'] ).' items<br />'."\n";
					$RTN .= '</dd>'."\n";
				}
				$RTN .= '</dl>'."\n";
			}
		}
		$RTN .= '<ul class="none">'."\n";
		$RTN .= '	<li class="ttr">'.$this->mk_link( '' , array( 'style'=>'inside','active'=>false ) ).'</li>'."\n";
		$RTN .= '</ul>'."\n";

		$this->setsrc( $RTN );
		return	$this->print_and_exit();
	}

	#----------------------------------------------------------------------------
	#	★エラーの内容を表示して終了する
	function fatalerrorend( $error_message , $FILE = null , $LINE = null ){
		while( @ob_end_clean() );

		$RTN = '';
		if( $this->conf->debug_mode ){
			$RTN .= '<h2>致命的なエラーが発生しました。</h2>'."\n";
			$RTN .= '<p class="ttr">'."\n";
			$RTN .= '	ご迷惑をお掛けしてしまい、申し訳ございません。<br />'."\n";
			$RTN .= '	しばらくしてから、もう一度お試しください。<br />'."\n";
			$RTN .= '</p>'."\n";
			$RTN .= '<p class="ttr error">'.htmlspecialchars( $error_message ).'</p>'."\n";
				#	デバッグモードが有効なとき以外は、
				#	エラーメッセージを標準出力しない。
			$RTN .= '<p class="ttr">'.htmlspecialchars( $FILE ).' Line: '.htmlspecialchars( $LINE ).'</p>'."\n";

			#	デバッグトレース
			$RTN .= '<h3>Debug Back Trace</h3>'."\n";
			$debug = @debug_backtrace();
			if( is_array( $debug ) && count( $debug ) ){
				$RTN .= '<dl>'."\n";
				foreach( array_keys( $debug ) as $KEY ){
					$RTN .= '<dt class="ttr">'.htmlspecialchars( $KEY ).'</dt>'."\n";
					$RTN .= '<dd class="ttr">'."\n";
					$RTN .= '	<strong>file</strong> = '.htmlspecialchars( $debug[$KEY]['file'] ).' Line: '.htmlspecialchars( $debug[$KEY]['line'] ).'<br />'."\n";
					$RTN .= '	<strong>function</strong> = '.htmlspecialchars( $debug[$KEY]['function'] ).'<br />'."\n";
					$RTN .= '	<strong>class</strong> = '.htmlspecialchars( $debug[$KEY]['class'] ).'<br />'."\n";
					$RTN .= '	<strong>type</strong> = '.htmlspecialchars( $debug[$KEY]['type'] ).'<br />'."\n";
					$RTN .= '	<strong>args</strong> = '.count( $debug[$KEY]['args'] ).' items<br />'."\n";
					$RTN .= '</dd>'."\n";
				}
				$RTN .= '</dl>'."\n";
			}
		}else{
			$RTN .= '<h2>現在、サーバが込み合っております。</h2>'."\n";
			$RTN .= '<p class="ttr">'."\n";
			$RTN .= '	ご迷惑をお掛けしてしまい、申し訳ございません。<br />'."\n";
			$RTN .= '	しばらくしてから、もう一度お試しください。<br />'."\n";
			$RTN .= '</p>'."\n";
		}

		$RTN .= '<ul class="none">'."\n";
		$RTN .= '	<li class="ttr">'.$this->mk_link( '' , array( 'style'=>'inside','active'=>false ) ).'</li>'."\n";
		$RTN .= '</ul>'."\n";

		$this->setsrc( $RTN );
		return	$this->print_and_exit();
	}

	#----------------------------------------------------------------------------
	#	★拡大画像を表示
	function enlargedimage( $url_enlargedimage = null , $option = array() ){
		while( @ob_end_clean() );

		$option = $this->parseoption( $option );

		$RTN = '';
		$RTN .= '<div style="overflow:auto; text-align:center; "><a href="javascript:history.go(-1);">'.$this->mk_img( $url_enlargedimage ).'</a></div>'."\n";

		$SRC = $this->view_outline->get_src( $RTN , 'header' );
		return	$this->print_and_exit( $SRC );
	}

	#----------------------------------------------------------------------------
	#	★リダイレクトする
	function redirect( $pid = null , $additional = null , $option = array() ){
		while( @ob_end_clean() );

		$option = $this->parseoption( $option );

		if( !is_null( $additional ) ){
			$option['additionalquery'] = $additional;
		}

		#--------------------------------------
		#	リダイレクト先を決定
		$redirect = $this->href( $pid , $option );
		if( strlen( $pid ) ){
			$this->req->setin( $this->req->pkey() , $pid );
		}elseif( strlen( $this->req->in($this->req->pkey()) ) ){
		}else{
			$this->req->setin( $this->req->pkey() , '' );
		}
		#	/リダイレクト先を決定
		#--------------------------------------

		@header( 'Location: '.$redirect );
		$FIN = '';
		$FIN .= '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">'."\n";
		$FIN .= '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ja">'."\n";
		$FIN .= '	<head>'."\n";
		$FIN .= '		<meta http-equiv="content-type" content="'.htmlspecialchars( $this->default_contenttype ).';charset='.htmlspecialchars( $this->get_output_encoding() ).'" />'."\n";
		$FIN .= '		<title>'.htmlspecialchars( $this->site->gettitle() ).'</title>'."\n";
		$FIN .= '		<meta http-equiv="refresh" content="0;url='.htmlspecialchars( $redirect ).'" />'."\n";
		$FIN .= '	</head>'."\n";
		$FIN .= '	<body>'."\n";
		$FIN .= '		<p class="ttr">'."\n";
		$FIN .= '			処理を完了しました。<br />'."\n";
		$FIN .= '			[<a href="'.htmlspecialchars( $redirect ).'">次へ</a>]<br />'."\n";
		$FIN .= '		</p>'."\n";
		$FIN .= '	</body>'."\n";
		$FIN .= '</html>'."\n";
		return	$this->print_and_exit( $FIN );
	}

	#----------------------------------------------------------------------------
	#	★ファイルをダウンロードする
	function download( $bin , $option = array() ){
		if( is_bool( $bin ) ){ $bin = 'bool( '.text::data2text( $bin ).' )'; }
		if( is_resource( $bin ) ){ $bin = 'A Resource.'; }
		if( is_array( $bin ) ){ $bin = 'An Array.'; }
		if( is_object( $bin ) ){ $bin = 'An Object.'; }
		if( !strlen( $bin ) ){ $bin = ''; }

		$option = $this->parseoption( $option );

		#	出力バッファをすべてクリア
		while( @ob_end_clean() );

		if( strtoupper( $this->user->get_browser_name() ) == 'MSIE' ){
			#	MSIE対策
			#	→こんな問題 http://support.microsoft.com/kb/323308/ja
			@header( 'Cache-Control: public' );
			@header( 'Pragma: public' );
		}

		if( strlen( $option['content-type'] ) ){
			$contenttype = $option['content-type'];
		}else{
			$contenttype = 'x-download/download';
		}
		if( strlen( $contenttype ) ){
			if( strlen( $option['charset'] ) ){
				$contenttype .= '; charset='.$option['charset'];
			}
			@header( 'Content-type: '.$contenttype );
		}

		if( strlen( $bin ) ){
			#	ダウンロードの容量
			@header( 'Content-Length: '.strlen( $bin ) );
		}

		if( strlen( $option['filename'] ) ){
			#	ダウンロードファイル名
			@header( 'Content-Disposition: attachment; filename='.$option['filename'] );
		}
		$this->set_output_encoding( null );
		return	$this->print_and_exit( $bin );
	}

	#----------------------------------------------------------------------------
	#	★ディスクに保存されたファイルを標準出力する
	function flush_file( $filepath , $option = array() ){
		#--------------------------------------
		#	$filepath => 出力するファイルのパス
		#	$option => オプションを示す連想配列
		#		'content-type'=>Content-type ヘッダー文字列。(第二引数よりも弱い。ほか関数との互換性のため実装)
		#		'charset'=>Content-type ヘッダー文字列に、文字コード文字列を追加
		#		'filename'=>ダウンロードさせるファイル名。
		#--------------------------------------

		$option = $this->parseoption( $option );

		if( !$this->dbh->is_file( $filepath ) ){
			#	対象のファイルがなければfalseを返す。
			return	false;
		}
		if( !$this->dbh->is_readable( $filepath ) ){
			#	対象のファイルに読み込み権限がなければfalseを返す。
			return	false;
		}

		#	絶対パスに変換
		$filepath = @realpath( $filepath );

		#	出力バッファをすべてクリア
		while( @ob_end_clean() );

		if( strtoupper( $this->user->get_browser_name() ) == 'MSIE' ){
			#	MSIE対策
			#	→こんな問題 http://support.microsoft.com/kb/323308/ja
			@header( 'Cache-Control: public' );
			@header( 'Pragma: public' );
		}

		if( strlen( $option['content-type'] ) ){
			$contenttype = $option['content-type'];
		}else{
			$contenttype = 'x-download/download';
		}
		if( strlen( $contenttype ) ){
			if( strlen( $option['charset'] ) ){
				$contenttype .= '; charset='.$option['charset'];
			}
			@header( 'Content-type: '.$contenttype );
		}

		#	ダウンロードの容量
		@header( 'Content-Length: '.filesize( $filepath ) );

		if( strlen( $option['filename'] ) ){
			#	ダウンロードファイル名
			@header( 'Content-Disposition: attachment; filename='.$option['filename'] );
		}

		#	ファイルを出力
		if( !@readfile( $filepath ) ){
			$this->errors->error_log( 'Disable to readfile( [ '.$filepath.' ] )' , __FILE__ , __LINE__ );
			return	false;
		}

		if( $option['delete'] ){
			#	deleteオプションが指定されていたら、
			#	ダウンロード後のファイルを削除する。
			$this->dbh->rmdir( $filepath );
		}

		$this->set_output_encoding( null );
		return	$this->print_and_exit( '' );
	}//flush_file()

	#--------------------------------------------------------------------------------------------------------------------------------------------------------

	#----------------------------------------------------------------------------
	#	タイトルタグを出力
	function mk_title(){
		#	Pickles Framework 0.5.10 : ページ名に含まれる改行を削除するようになった。
		$sitetitle = $this->site->gettitle();
		$pagetitle = preg_replace( '/\r|\n|\r\n/' , '' , $this->gettitle() );
		if( strlen( $this->req->po() ) ){
			return	'<title>'.htmlspecialchars( $pagetitle ).' ：'.htmlspecialchars( $sitetitle ).'</title>';
		}else{
			return	'<title>'.htmlspecialchars( $sitetitle ).'</title>';
		}
	}

	#----------------------------------------------------------------------------
	#	水平線のソースを返す
	function hr( $option = array() ){
		return	$this->mk_hr( $option );
	}
	function mk_hr( $option = array() ){
		return	$this->view_mk_hr( $option );
	}

	#----------------------------------------------------------------------------
	#	水平線のソースを返す(デザイン部分)
	function view_mk_hr( $option = array() ){
		$option = $this->parseoption( $option );

		#	CSSクラス
		$cssclass = ' class="defhr"';
		if( strlen( $option['cssclass'] ) ){
			$cssclass = ' class="'.htmlspecialchars( $option['cssclass'] ).'"';
		}
		#	スタイルシート
		$cssstyle = '';
		if( strlen( $option['cssstyle'] ) ){
			$cssstyle = ' style="'.htmlspecialchars( $option['cssstyle'] ).'"';
		}
		#	色
		$color = '';
		if( strlen( $option['color'] ) ){
			$color = ' color="'.htmlspecialchars( $option['color'] ).'"';
		}
		#	太さ
		$size = '';
		if( strlen( $option['size'] ) ){
			$size = ' size="'.htmlspecialchars( $option['size'] ).'"';
		}
		#	長さ
		$width = '';
		if( strlen( $option['width'] ) ){
			$size = ' width="'.htmlspecialchars( $option['width'] ).'"';
		}
		return	'<hr'.$cssclass.$cssstyle.$color.$size.$width.' />';
	}

	#----------------------------------------------------------------------------
	#	ページの先頭へ戻るリンクのソースを返す
	function back2top( $option = array() ){
		return	$this->mk_back2top( $option );
	}//back2top()
	function mk_back2top( $option = array() ){
		return	$this->view_mk_back2top( $option );
	}//mk_back2top()

	#----------------------------------------------------------------------------
	#	ページの先頭へ戻るリンクのソースを返す(デザイン部分)
	function view_mk_back2top( $option = array() ){
		$RTN = '';
		$RTN .= '<div class="back2top"><ul><li><a href="#pagetop">ページの先頭へ戻る</a></li></ul></div>'."\n";
		return	$RTN;
	}//view_mk_back2top()

	#----------------------------------------------------------------------------
	#	子階層メニューのソースを返す
	function mk_childlist( $option = array() ){
		$option = $this->parseoption( $option );
		$pid = $this->req->p();
		if( is_string( $option['pid'] ) ){ $pid = $option['pid']; }//PxFW 0.6.7 トップページを指定できない不具合を修正

		$get_children_option = array();
		if( $option['all'] ){
			$get_children_option['all'] = true;
		}

		$childlist = $this->site->get_children( $pid , $get_children_option );
		if( !is_array( $childlist ) || !count( $childlist ) ){ return ''; }

		$RTN = '';
		$RTN .= '<ul>'."\n";
		foreach( $childlist as $child_pid ){
			$RTN .= '	<li class="ttr">'.$this->mk_link( $child_pid ).'</li>'."\n";
		}
		$RTN .= '</ul>'."\n";
		return	$RTN;
	}//mk_childlist()

	#----------------------------------------------------------------------------
	#	同階層メニューのソースを返す
	#	Pickles Framework 0.5.0 追加
	function mk_broslist( $option = array() ){
		$option = $this->parseoption( $option );
		$pid = $this->req->p();
		if( is_string( $option['pid'] ) ){ $pid = $option['pid']; }//PxFW 0.6.7 トップページを指定できない不具合を修正

		$get_bros_option = array();
		if( $option['all'] ){
			$get_bros_option['all'] = true;
		}

		$broslist = $this->site->get_bros( $pid , $get_bros_option );
		if( !is_array( $broslist ) || !count( $broslist ) ){ return ''; }

		$RTN = '';
		$RTN .= '<ul>'."\n";
		foreach( $broslist as $bros_pid ){
			$RTN .= '	<li class="ttr">'.$this->mk_link( $bros_pid ).'</li>'."\n";
		}
		$RTN .= '</ul>'."\n";
		return	$RTN;
	}//mk_broslist()

	#----------------------------------------------------------------------------
	#	ページ内の目次を自動生成する
	#	PxFW 0.6.12 追加
	function mk_autoindex(){
		if( !is_array( $this->autoindex_memodata ) ){
			$this->autoindex_memodata = array();
			$this->autoindex_memodata['metastr'] = '[__autoindex_'.md5( time::microtime() ).'__]';
		}
		return $this->autoindex_memodata['metastr'];
	}//mk_autoindex();

	#----------------------------------------------------------------------------
	#	ページ内の目次をソースに反映する
	#	PxFW 0.6.12 追加
	function apply_autoindex( $CONTENT ){
		$tmpCONT = $CONTENT;
		$CONTENT = '';
		$index = array();
		$indexCounter = array();
		$i = 0;
		while( 1 ){
			if( !preg_match( '/^(.*?)(<\!\-\-(?:.*?)\-\->|<script(?:\s.*?)?>(?:.*?)<\/script>|<h([2-6])(?:\s.*?)?>(.*?)<\/h\3>)(.*)$/is' , $tmpCONT , $matched ) ){
				$CONTENT .= $tmpCONT;
				break;
			}
			$i ++;
			$tmp = array();
			$tmp['label'] = $matched[4];
			$tmp['label'] = strip_tags( $tmp['label'] );//ラベルからHTMLタグを除去
			$tmp['anch'] = 'hash_'.md5($tmp['label']);
			if($indexCounter[$tmp['anch']]){
				$indexCounter[$tmp['anch']] ++;
				$tmp['anch'] = 'hash_'.$indexCounter[$tmp['anch']].'_'.md5($tmp['label']);
			}else{
				$indexCounter[$tmp['anch']] = 1;
			}
			$tmp['headlevel'] = intval($matched[3]);
			if( $tmp['headlevel'] ){# 引っかかったのが見出しの場合
				array_push( $index , $tmp );
			}

			$CONTENT .= $matched[1];
			if( $tmp['headlevel'] ){# 引っかかったのが見出しの場合
				#$CONTENT .= $this->back2top();
				$CONTENT .= '<span';
				$CONTENT .= ' id="'.htmlspecialchars($tmp['anch']).'"';
				$CONTENT .= '></span>';
			}
			$CONTENT .= $matched[2];
			$tmpCONT = $matched[5];
		}

		$anchorlinks = '';
		$topheadlevel = 2;
		$headlevel = $topheadlevel;
		if( count( $index ) ){
			$anchorlinks .= '<div class="anchorlinks">'."\n";
			if( $this->user->get_language() == 'ja' ){
				$anchorlinks .= $this->mk_hx('目次');
			}else{
				$anchorlinks .= $this->mk_hx('Index');
			}
			foreach( $index as $key=>$row ){
				$csa = $row['headlevel'] - $headlevel;
				$nextLevel = $index[$key+1]['headlevel'];
				$nsa = null;
				if( strlen( $nextLevel ) ){
					$nsa = $nextLevel - $row['headlevel'];
				}
				$headlevel = $row['headlevel'];
				if( $csa>0 ){
					#	いま下がるとき
					if( $key == 0 ){
						$anchorlinks .= '<ul><li>';
					}
					for( $i = $csa; $i>0; $i -- ){
						$anchorlinks .= '<ul><li>';
					}
				}elseif( $csa<0 ){
					#	いま上がるとき
					if( $key == 0 ){
						$anchorlinks .= '<ul><li>';
					}
					for( $i = $csa; $i<0; $i ++ ){
						$anchorlinks .= '</li></ul>';
					}
					$anchorlinks .= '</li><li>';
				}else{
					#	いま現状維持
					if( $key == 0 ){
						$anchorlinks .= '<ul>';
					}
					$anchorlinks .= '<li>';
				}
				$anchorlinks .= '<a href="#'.htmlspecialchars($row['anch']).'">'.($row['label']).'</a>';
				if( is_null($nsa) ){
					break;
				}elseif( $nsa>0 ){
					#	つぎ下がるとき
#					for( $i = $nsa; $i>0; $i -- ){
#						$anchorlinks .= '</li></ul></li>';
#					}
				}elseif( $nsa<0 ){
					#	つぎ上がるとき
					for( $i = $nsa; $i<0; $i ++ ){
//						$anchorlinks .= '</li></ul>'."\n";
					}
				}else{
					#	つぎ現状維持
					$anchorlinks .= '</li>'."\n";
				}
			}
			while($headlevel >= $topheadlevel){
				$anchorlinks .= '</li></ul>'."\n";
				$headlevel --;
			}
			$anchorlinks .= '</div><!-- / .anchorlinks -->'."\n";
		}

		$CONTENT = preg_replace( '/'.preg_quote($this->autoindex_memodata['metastr'],'/').'/si' , $anchorlinks , $CONTENT );
		return $CONTENT;
	}//apply_autoindex();

	#----------------------------------------------------------------------------
	#	見出しのソースを返す
	function hx( $title , $hx = 2 , $option = array() ){
		return	$this->mk_hx( $title , $hx , $option );
	}//hx();
	function mk_hx( $title , $hx = 2 , $option = array() ){
		$default = 2;
		$max = 6;
		$hx_g = $hx;
		unset( $hx );

		#--------------------------------------
		#	見出し番号を決定
		if( !strlen( $hx_g ) ){ $hx_g = $default; }
		$hx_g = intval($hx_g);
		if( !$hx_g ){ $hx = $default; }
		if( $hx_g < 0 )			{ $hx = ( $default - floor( $hx_g ) ); }
		elseif( $hx_g > $max )	{ $hx = $max; }
		else					{ $hx = $hx_g; }
		if( !$hx ){ $hx = $default; }
		#	/ 見出し番号を決定
		#--------------------------------------

		$option = $this->parseoption( $option );

		if( !$option['allow_html'] ){
			$title = htmlspecialchars( $title );
		}

		$args = func_get_args();
		return	$this->view_mk_hx( $title , $hx , $option['style'] , $args );
	}

	#----------------------------------------------------------------------------
	#	見出しのソースを返す(デザイン部分)
	function view_mk_hx( $title , $hx = 2 , $style = '' , $args = array() ){
		#	このメソッドが呼ばれた回数
		static	$pointernum;
		if( !$pointernum ){ $pointernum = 0; }
		$pointernum ++;

		if( !is_object( $this->mkhx ) ){
			#	mkhxオブジェクトが存在しなければ、作成する
			$this->mkhx = &$this->styleobjectfactory( 'mkhx' );
		}

		return	$this->mkhx->get_src( $title , $hx , $style , $args );

	}



	#----------------------------------------------------------------------------
	#	サイト外へのテキストリンクを生成し、ソースを返す
	function mk_outerlink( $target_url = null , $label = null , $option = array() ){
		$args = func_get_args();

		$option = $this->parseoption( $option );
		$args[2] = $option;

		#	現在のページが、リンク先のページの下層に当たるかどうかを決める
		$is_active = false;

		#	ラベル(表示リンク名)
		if( strlen( $label ) )				{ $option['label'] = $label; }
		if( strlen( $option['label'] ) )	{ $label = $option['label']; }
		if( !strlen( $label ) )				{ $label = $target_url; }
		if( !$option['allow_html'] ){
			$label = htmlspecialchars( $label );
		}

		if( is_null( $option['style'] ) ){
			$option['style'] = 'outside';
		}

		#	リンク先
		$href = $this->href( $target_url , $option );

		#	ターゲットウィンドウ
		if( strlen( $option['target'] ) ){
			$target = $option['target'];
		}else{
			$target = '_blank';
		}

		#	CSSクラスを決定
		$cssclass = '';
		if( strlen( $option['cssclass'] ) ){
			$cssclass = $option['cssclass'];
		}

		$args[1] = $args[2];
		unset( $args[2] );

		return	$this->view_mk_link( $href , $label , $option['style'] , $cssclass , $is_active , $target , $args );
	}
	#----------------------------------------------------------------------------
	#	サイト内へのテキストリンクを生成し、ソースを返す
	function link( $target_pid = null , $option = array() ){
		return	$this->mk_link( $target_pid , $option );
	}
	function mk_link( $target_pid = null , $option = array() ){
		$args = func_get_args();

		#	先頭のコロンを、現在のページID(po)に変換
		$target_pid = preg_replace( '/^:/' , $this->req->po().'.' , $target_pid );
		$args[0] = $target_pid;

		$target_pid_original = $target_pid;	#	元の値をメモ

		#	シャープがあったら、ページ内アンカーとして処理
		$anch = null;
		if( strpos( $target_pid , '#' ) ){
			list( $target_pid , $anch ) = explode( '#' , $target_pid , 2 );
			unset( $anch );
		}

		$additionalquery = null;
		if( !is_null( $option['additionalquery'] ) ){
			$additionalquery = $option['additionalquery'];
		}elseif( !is_null( $option['param'] ) ){
			$additionalquery = $option['param'];//←PxFW 0.6.10 追加
		}
		if( strlen( $additionalquery ) ){
			$tmp_additionalquery_ary = $this->parseoption( $additionalquery );//←PxFW 0.6.10 追加 : 連想配列でも受け付けるようになった。
			$tmp_additionalquery_ary2 = array();
			foreach( $tmp_additionalquery_ary as $key=>$val ){
				array_push( $tmp_additionalquery_ary2 , urlencode($key).'='.urlencode($val) );
			}
			$additionalquery = implode( '&' , $tmp_additionalquery_ary2 );
			unset( $tmp_additionalquery_ary , $tmp_additionalquery_ary2 );
		}

		#	ハテナがあったら、additionalqueryとして処理
		if( strpos( $target_pid , '?' ) ){
			#	Pickles Framework 0.1.10 追加
			$additionalquery_memo = null;
			list( $target_pid , $additionalquery_memo ) = explode( '?' , $target_pid , 2 );
			unset( $additionalquery_memo );
				#	メモ：mk_link() は、このリンク先URLの計算を href() に一存しているので、
				#		ここではハテナ以降の値は単に削って捨ててよい。
		}

		$option = $this->parseoption( $option );
		$args[1] = $option;

		#	リンク先
		$href = $this->href( $target_pid_original , $option );

		#	末尾のドットを削除
		$target_pid = preg_replace( '/\.+$/' , '' , $target_pid );
		#	先頭のドットを削除//Pickles Framework 0.5.1 追加
		$target_pid = preg_replace( '/^\.+/' , '' , $target_pid );

		#	カレントページかどうかを決定
		$PIDElements = explode( '.' , $target_pid );
		$target_body_pid = $PIDElements[0];

		#	現在のページが、リンク先のページの下層に当たるかどうかを決める
		$is_active = false;
		if( $option['active'] == 'yes' || $option['active'] == 'true' || $option['active'] === true ){
			$is_active = true;
		}elseif( $option['active'] == 'no' || $option['active'] == 'false' || $option['active'] === false ){
			$is_active = false;
		}elseif( $href === $target_pid_original ){
			//	PxFW 0.6.4 追加 : サイト外リンクだったらアクティブにしない
			$is_active = false;
		}elseif( $option['active'] == 'self' ){
			if( $this->site->getpageinfo( $target_pid , 'id' ) == $this->req->p() ){
				#	現在のページを指していた場合にtrue
				$is_active = true;
			}
		}elseif( $option['active'] == 'follow' || !strlen( $option['active'] ) ){
			#	上位の階層を見る場合
			if( !strlen( $this->site->getpageinfo( $target_pid , 'id' ) ) ){
				//	リンク先がトップページの場合に限り、上位階層を見ない。(PxFW 0.6.2 追加の処理)
				if( !strlen( $this->req->po() ) && strlen( $target_pid ) ){
					//	PxFW 0.6.5 : トップページでリソースなどにリンクした場合にアクティブにならないようにした。
					$is_active = false;
				}elseif( $this->site->getpageinfo( $target_pid , 'id' ) == $this->site->getpageinfo( $this->req->p() , 'id' ) ){
					//	現在のページを指していた場合にtrue
					$is_active = true;
				}
			}else{
				//その他のページ用のデフォルト処理
				$MEMO = explode( '/' , $this->site->getpageinfo( $this->req->p() , 'path' ) );
				foreach( $MEMO as $Line ){
					if( strlen( $Line ) && $Line == $target_pid ){
						$is_active = true;
						break;
					}
				}
			}
		}

		#--------------------------------------
		#	ラベル(表示リンク名)
		if( strlen( $option['label'] ) ){
			$label = $option['label'];
			if( !$option['allow_html'] ){
				#	HTMLの使用が、明示的に許可されていなければ、
				#	HTMLエンティティ変換を施す。
				$label = text::text2html( $label );
					//PxFW 0.5.10 : 改行も反映するようになった。
					//PxFW 0.6.0 : オプション allow_html が、ラベルを指定された場合にだけ効くように修正。
			}
		}elseif( strlen( $this->site->getpageinfo( $target_pid , 'title_label' ) ) ){
			//PxFW 0.6.0 : サイトマップからとる場合はHTMLエンティティを変換するようになった。
			$label = text::text2html( $this->site->getpageinfo( $target_pid , 'title_label' ) );
		}else{
			//PxFW 0.6.0 : サイトマップからとる場合はHTMLエンティティを変換するようになった。
			$label = text::text2html( $this->site->getpageinfo( $target_pid , 'title' ) );
		}
		if( !strlen( $label ) && $target_pid == 'logout' ){
			$label = 'Logout';
		}
		if( !strlen( $label ) ){
			if( preg_match( '/^https?\:\/\//i' , $target_pid ) ){
				$label = $target_pid_original;
			}else{
				$label = 'Title Unknown';
			}
		}
		#	/ ラベル(表示リンク名)
		#--------------------------------------

		#	ターゲットウィンドウ
		$target = null;//PxFW 0.6.2 指定がない場合に、_self をセットする仕様を改め、 null にした。
		if( strlen( $option['target'] ) ){
			$target = $option['target'];
		}elseif( preg_match( '/^(?:http|https):\/\/'.preg_quote($this->conf->url_domain,'/').preg_quote($this->conf->url_action,'/').'/si' , $href ) ){
			#	サイト内リンクだと思しき絶対URLのリンクは、デフォルトで _blank にしない。
			$target = null;
		}elseif( preg_match( '/^(?:http|https|ftp):\/\//si' , $href ) ){
			$target = '_blank';
		}

		#	CSSクラスを決定
		$cssclass = '';
		if( strlen( $option['cssclass'] ) ){
			$cssclass = $option['cssclass'];
		}
		return	$this->view_mk_link( $href , $label , $option['style'] , $cssclass , $is_active , $target , $args );
	}

	#----------------------------------------------------------------------------
	#	サイト内へのテキストリンクを生成し、ソースを返す(デザイン部分)
	#	自動サイト内リンクのデザインを決めるのはココ。
	function view_mk_link( $href , $label , $style = '' , $cssclass = '' , $is_active = false , $target = '_self' , $args = array() ){
		#	このメソッドが呼ばれた回数
		static	$pointernum;
		if( !$pointernum ){ $pointernum = 0; }
		$pointernum ++;

		if( !is_object( $this->mklink ) ){
			#	mklinkオブジェクトが存在しなければ、作成する
			$this->mklink = &$this->styleobjectfactory( 'mklink' );
		}

		return	$this->mklink->get_src( $href , $label , $style , $cssclass , $is_active , $target , $args );
	}

	#----------------------------------------------------------------------------
	#	ページャーを生成する //←PxFW 0.6.3：追加
	function mk_pager( $total_count , $current_page_num , $display_per_page = 10 , $option = array() ){
		if( !strlen( $current_page_num ) || $current_page_num < 1 ){ $current_page_num = 1; }
		$pagerinfo = $this->dbh->get_pager_info( $total_count , $current_page_num , $display_per_page , $option );
		$href = ':${num}';
		if( strlen( $option['href'] ) ){
			$href = $option['href'];
		}
		$href_first = $href;
		if( strlen( $option['href_first'] ) ){//PxFW 0.7.2 追加のオプション
			$href_first = $option['href_first'];
		}

		$RTN = '';
		$RTN .= '<div class="unit_pager">'."\n";
		$RTN .= '	<ul>'."\n";
		$RTN .= '		';
		if( $pagerinfo['first'] ){
			$RTN .= '<li class="ttr">'.$this->mk_link( str_replace( '${num}' , $pagerinfo['first'] , $href_first ) , array('label'=>'<<最初','active'=>false) ).'</li>';
		}else{
			$RTN .= '<li class="ttr">'.htmlspecialchars('<<最初').'</li>';
		}
		if( $pagerinfo['prev'] ){
			$href_prev = $href;
			if( $pagerinfo['prev'] == 1 ){
				$href_prev = $href_first;
			}
			$RTN .= '<li class="ttr">'.$this->mk_link( str_replace( '${num}' , $pagerinfo['prev'] , $href_prev ) , array('label'=>'<前へ','active'=>false) ).'</li>';
		}else{
			$RTN .= '<li class="ttr">'.htmlspecialchars('<前へ').'</li>';
		}
		for( $i = $pagerinfo['index_start']; $i <= $pagerinfo['index_end']; $i ++ ){
			$href_nums = $href;
			if( $i == 1 ){
				$href_nums = $href_first;
			}
			$current_href = str_replace( '${num}' , $i , $href_nums );
			if( $pagerinfo['current'] == $i ){
				$RTN .= '<li class="ttr"><strong>'.intval($i).'</strong></li>';
			}else{
				$RTN .= '<li class="ttr">'.$this->mk_link( $current_href , array('label'=>intval($i),'active'=>false) ).'</li>';
			}
		}
		if( $pagerinfo['next'] ){
			$RTN .= '<li class="ttr">'.$this->mk_link( str_replace( '${num}' , $pagerinfo['next'] , $href ) , array('label'=>'次へ>','active'=>false) ).'</li>';
		}else{
			$RTN .= '<li class="ttr">'.htmlspecialchars('次へ>').'</li>';
		}
		if( $pagerinfo['last'] ){
			$RTN .= '<li class="ttr">'.$this->mk_link( str_replace( '${num}' , $pagerinfo['last'] , $href ) , array('label'=>'最後>>','active'=>false) ).'</li>';
		}else{
			$RTN .= '<li class="ttr">'.htmlspecialchars('最後>>').'</li>';
		}
		$RTN .= "\n";
		$RTN .= '	</ul>'."\n";
		$RTN .= '</div>'."\n";
		return $RTN;
	}

	#----------------------------------------------------------------------------
	#	パンくずのソースを生成し、$src['breadcrumb']に格納する
	function set_breadcrumb( $Category = null , $sep = ' &gt; ' , $option = array() ){
		#	Pickles Framework 0.5.10 : ページタイトルの改行を画面に反映しないようになった。
		#	Pickles Framework 0.6.8 : ulタグで表現されるようになった。
		$option = $this->parseoption( $option );
		$RTN = '';

		if( !strlen( $this->site->getpageinfo( $this->req->p() , 'id' ) ) ){
			$RTN .= '<li>'.htmlspecialchars( $this->site->getpageinfo('','title_breadcrumb') ).'</li>';
			$this->setsrc( '<ul>'.$RTN.'</ul>' , 'breadcrumb' );
			return	true;
		}else{
			$RTN .= '<li>'.$this->mk_link( '' , array( 'active'=>'no' ) ).'</li>';
		}

		$Cat = explode( '/' , $Category );

		foreach( $Cat as $Line ){
			if( !strlen( $Line ) ){ continue; }
			if( !strlen( $this->site->getpageinfo( $Line , 'title_breadcrumb' ) ) ){ continue; }

			if( $this->href( $this->site->getpageinfo( $Line , 'id' ) ) != $this->href( $this->site->getpageinfo( $this->req->p() , 'id' ) ) ){//← PxFW 0.6.5 : 分岐ロジックを変更した ←PxFW 0.7.2 : $this->href() を通して、リンク先として評価するようにした。(エイリアスに配慮)
				$RTN .= '<li>'.$sep.$this->mk_link( $Line , array( 'active'=>'no' , 'allow_html'=>true , 'label'=>htmlspecialchars( $this->site->getpageinfo( $Line , 'title_breadcrumb' ) ) ) ).'</li>';
			}else{
				$RTN .= '<li>'.$sep.'<strong>'.htmlspecialchars( $this->site->getpageinfo( $Line , 'title_breadcrumb' ) ).'</strong></li>';
			}
		}

		$this->setsrc( '<ul>'.$RTN.'</ul>' , 'breadcrumb' );
		return	true;
	}

	#----------------------------------------------------------------------------
	#	分割レイアウトを作成
	function mk_splitedfield( $args , $option = array() ){
		$option = $this->parseoption( $option );

		$fieldwidth_atone = floor( 100/count( $args ) );

		$RTN = '';
		$RTN .= '<table border="0" cellpadding="0" cellspacing="0" width="100%">'."\n";
		$RTN .= '	<tr>'."\n";
		$count = 0;
		foreach( $args as $Line){
			$RTN .= '		<td align="left" valign="top" width="'.$fieldwidth_atone.'%">'.$Line.'</td>'."\n";
			if( !is_null($args[$count +1]) ){
				$RTN .= '		<td align="left" valign="top" width="10">'.$this->spacer(10,1).'</td>'."\n";
			}
			$count ++;
		}
		$RTN .= '	</tr>'."\n";
		$RTN .= '</table>'."\n";
		return	$RTN;
	}


	#----------------------------------------------------------------------------
	#	スタイルシートを書き出す
	function mk_css(){

		#	$this->css をCSSオブジェクトとして定義しました。
		#	mk_cssは、このオブジェクトに置き換えられます。
		#	2:45 2006/04/17：やっぱりやめました。スタイルシートは、普通にリソースに持つことにします。

/**
		$fs = $this->set_fontsize_on_css(12,13);
		$deflineheight = '150%';

		$RTN .= '			body,table,tr,td,th,ul,ol,li,h1,h2,h3,h4,h5,h6'."\n";
		$RTN .= '				{color:'.$this->getcolor('TEXT').'; '.$this->getdefaultfontname_on_css().'}'."\n";

		#	リンクスタイル
		$RTN .= '			a						{color:'.$this->getcolor('LINK').'; text-decoration:none; }'."\n";
		$RTN .= '			a:hover					{color:'.$this->getcolor('LINK_HOVER').'; text-decoration:underline; }'."\n";
		$RTN .= '			a.'.$this->classname_of_activelink.'				{color:'.$this->getcolor('LINK_CURRENT').'; text-decoration:underline; font-weight:bold; }'."\n";
		$RTN .= '			a.'.$this->classname_of_activelink.':hover			{color:'.$this->getcolor('LINK_CURRENT_HOVER').'; text-decoration:none; font-weight:bold; }'."\n";
		$RTN .= '			a.plain					{color:'.$this->getcolor('TEXT').'; text-decoration:none; }'."\n";
		$RTN .= '			a.plain:hover			{color:'.$this->getcolor('LINK_HOVER').'; text-decoration:none; }'."\n";

		#	見出し・タイトルスタイル
		$RTN .= '			h1,h2,h3,h4,h5,h6		{font-size:'.$fs[12].'; line-height:'.$deflineheight.'; padding:0px 0px 0px 0px; margin:0px 0px 0.5em 0px; }'."\n";
		$RTN .= '			#ptitle h1				{font-size:'.$fs[16].'; font-weight:bold; padding-left:10px; border-bottom:1px #666666 solid; margin:10px 0px 0.5em 0px; display:block; }'."\n";
		$RTN .= '			#ptitle .summary		{font-size:'.$fs[11].'; margin:0px 10px 12px 10px; background-color:#eeeeee; padding:4px; display:block; }'."\n";

		$RTN .= $this->print_css_textsize( null , 12 , 13 , null , array( 'indent'=>'			' , 'line-height'=>$deflineheight ) );
		$RTN .= '			.notes					{color:'.$this->getcolor('NOTES').'; }'."\n";
		$RTN .= '			.error					{color:'.$this->getcolor('ERROR').'; }'."\n";
		$RTN .= '			.attention				{color:'.$this->getcolor('ATTENTION').'; }'."\n";
		$RTN .= '			.alert					{color:'.$this->getcolor('ALERT').'; }'."\n";
		$RTN .= '			.caution				{color:'.$this->getcolor('CAUTION').'; }'."\n";
		$RTN .= '			.warning				{color:'.$this->getcolor('WARNING').'; }'."\n";

		$RTN .= '			.must					{color:'.$this->getcolor('MUST').'; }'."\n";
/**/

		return	$RTN;
	}

	#----------------------------------------------------------------------------
	#	フォントサイズのCSS上の指定値を計算する
	function print_css_textsize( $name = 'ttr' , $normalsize = 13 , $bsr_default = 16 , $sizelist = null , $option = array() ){
		$option = $this->parseoption( $option );
		$fs = $this->set_fontsize_on_css( $normalsize , $bsr_default );
		if( is_null( $sizelist ) ){
			$sizelist = array(
				'll'=>16 ,
				'l'=>14 ,
				''=>12 ,
				's'=>11 ,
				'ss'=>10 ,
			);
		}
		if( strlen( $option['line-height'] ) ){
			$lineheight = 'line-height:'.$option['line-height'].'; ';
		}
		$RTN .= $option['indent'].	'.ttrll				{font-size:'.$fs[$sizelist['ll']].'; '.$lineheight.'}'."\n";
		$RTN .= $option['indent'].	'.ttrl				{font-size:'.$fs[$sizelist['l']].'; '.$lineheight.'}'."\n";
		$RTN .= $option['indent'].	'.ttr				{font-size:'.$fs[$sizelist['']].'; '.$lineheight.'}'."\n";
		$RTN .= $option['indent'].	'.ttrs				{font-size:'.$fs[$sizelist['s']].'; '.$lineheight.'}'."\n";
		$RTN .= $option['indent'].	'.ttrss				{font-size:'.$fs[$sizelist['ss']].'; '.$lineheight.'}'."\n";
		$RTN .= $option['indent'].	'.fstll				{font-size:'.$sizelist['ll'].'px; '.$lineheight.'}'."\n";
		$RTN .= $option['indent'].	'.fstl				{font-size:'.$sizelist['l'].'px; '.$lineheight.'}'."\n";
		$RTN .= $option['indent'].	'.fst				{font-size:'.$sizelist[''].'px; '.$lineheight.'}'."\n";
		$RTN .= $option['indent'].	'.fsts				{font-size:'.$sizelist['s'].'px; '.$lineheight.'}'."\n";
		$RTN .= $option['indent'].	'.fstss				{font-size:'.$sizelist['ss'].'px; '.$lineheight.'}'."\n";
		return	$RTN;
	}



	#----------------------------------------------------------------------------
	#	フォントサイズのCSS上の指定値を計算する
	function set_fontsize_on_css( $normalsize = 12 , $bsr_default = 16 ){
		#	$normalsize = .ttrの表示ピクセル数
		#	$bsr_default = ブラウザ固有の標準文字サイズピクセル数

		if( !$bsr_default ){
			$bsr_default = 16;
		}

		if( $this->user->get_browser_name() == 'MSIE' && $this->user->get_os_name() == 'Windows' ){
			for($i = 6; $i < 24; $i++){
				$RTN[$i] = math::rounddown( (($i/$bsr_default)*(1/12*$normalsize)*100) , 3).'%';
			}
		}else{
			for($i = 6; $i < 24; $i++){
				$RTN[$i] = math::rounddown( $i*(1/12*$normalsize) , 3).'px';
			}
		}
		$this->fontsize = $RTN;
		return	$RTN;
	}

	#----------------------------------------------------------------------------
	#	標準フォントフェイスの指定
	function getdefaultfontname_on_css(){
		if( $this->user->get_browser_group() == 'Seamonkey' || ( $this->user->get_browser_name() == 'Mozilla' && $this->user->get_browser_version() < 5 && $this->user->get_os_name() == 'Windows' ) ){
			return	'font-family:"MS PGOTHIC","Osaka","MS UI GOTHIC"; ';
		}else{
			return	'font-family:"ＭＳ Ｐゴシック","MS PGOTHIC","Osaka","MS UI GOTHIC"; ';
		}
	}


	#----------------------------------------------------------------------------
	#	ドキュメントルートまでのパスを得る
	function root(){
		return	$this->conf->url_root;
	}

	#--------------------------------------
	#	サイト内リンク先のパスを得る
	function href( $target_pid , $option = array() ){
		#---------------------------------------------------------------------------------
		#	このメソッドは、<a>タグなどに指定するリンク先のパスを返します。
		#	act()メソッドからPHP本体のパスを取得し、p要素、付加引数をつけて返します。
		#	<form>のaction属性に指定する文字列の場合は、このメソッドを使用せず、
		#	act()メソッドから直接取得してください。
		#	$target_pidには、「pid.subpid.subsubpid」と、ドット区切りで指定してください。
		#---------------------------------------------------------------------------------

		if( !strlen( $target_pid ) ){ $target_pid = ''; }

		#	先頭のコロンを、現在のページID(po)に変換
		$target_pid = preg_replace( '/^:/' , $this->req->po().'.' , $target_pid );

		$target_pid_original = $target_pid;	#	元の値をメモ

		$option = $this->parseoption( $option );

		$additionalquery = null;
		if( !is_null( $option['additionalquery'] ) ){
			$additionalquery = $option['additionalquery'];
		}elseif( !is_null( $option['param'] ) ){
			$additionalquery = $option['param'];//←PxFW 0.6.10 追加
		}
		if( strlen( $additionalquery ) ){
			$tmp_additionalquery_ary = $this->parseoption( $additionalquery );//←PxFW 0.6.10 追加 : 連想配列でも受け付けるようになった。
			$tmp_additionalquery_ary2 = array();
			foreach( $tmp_additionalquery_ary as $key=>$val ){
				array_push( $tmp_additionalquery_ary2 , urlencode($key).'='.urlencode($val) );
			}
			$additionalquery = implode( '&' , $tmp_additionalquery_ary2 );
			unset( $tmp_additionalquery_ary , $tmp_additionalquery_ary2 );
		}

		#	シャープがあったら、ページ内アンカーとして処理
		$anch = null;
		if( strpos( $target_pid , '#' ) ){
			list( $target_pid , $anch ) = explode( '#' , $target_pid , 2 );
		}

		#	ハテナがあったら、additionalqueryとして処理
		if( strpos( $target_pid , '?' ) ){
			#	Pickles Framework 0.1.10 追加
			list( $target_pid , $tmp_additionalquery_memo ) = explode( '?' , $target_pid , 2 );
			$tmp_ary = array();
			if( strlen( $additionalquery ) ){
				array_push( $tmp_ary , $additionalquery );
			}
			if( strlen( $tmp_additionalquery_memo ) ){
				array_push( $tmp_ary , $tmp_additionalquery_memo );
			}
			if( count( $tmp_ary ) ){
				$additionalquery = implode( '&' , $tmp_ary );
			}
			unset( $tmp_ary );
			unset( $tmp_additionalquery_memo );
		}

		#----------------------------------------------------------------------------
		#	対象ページにリンク先/保存先の指定がある場合は、そっちを優先
		$memo_linkto = $this->site->getpageinfo( $target_pid , 'linkto' );
		if( strlen( $memo_linkto ) && preg_match( '/^pid\:/' , $memo_linkto ) ){
			$memo_linkto = preg_replace( '/^pid\:/' , '' , $memo_linkto );
			$memo_linkto2 = $memo_linkto;
			list( $memo_linkto2 ) = explode( '#' , $memo_linkto2 );
			list( $memo_linkto2 ) = explode( '?' , $memo_linkto2 );
			if( preg_match( '/^[a-zA-Z0-9\_\.\-]*$/' , $memo_linkto2 ) ){
				#	PxFW 0.6.8 追加：サイトマップツリー内ショートカット機能
				#	(ただし、サイトマップ内での無限ループ対策は考慮されないので気をつけること。)
				#	同じ実装が act() にもあるので注意！
				return	$this->href( $memo_linkto , $option );
			}
			unset( $memo_linkto2 );
		}
		unset( $memo_linkto );
		#	/ 対象ページにリンク先/保存先の指定がある場合は、そっちを優先
		#----------------------------------------------------------------------------

		#----------------------------------------------------------------------------
		#	PHP本体のパスを取得
		$RTN = $this->act( $target_pid , $option );

		#	スラッシュがあったら、ファイル名として処理
		#	ただし、先頭がスラッシュだった場合は別
		if( strpos( $target_pid , '/' ) > 0 ){
			list( $target_pid , $filename ) = explode( '/' , $target_pid , 2 );
		}
		#	末尾のドットを削除
		$target_pid = preg_replace( '/\.+$/' , '' , $target_pid );
		#	先頭のドットを削除//Pickles Framework 0.5.1 追加
		$target_pid = preg_replace( '/^\.+/' , '' , $target_pid );

		#--------------------------------------
		#	引数を作っておく
		$fin_additionalquery = array();
		if( strlen( $additionalquery ) ){
			array_push( $fin_additionalquery , $additionalquery );
		}

		#	ページIDを付加
		#	act()メソッドは付加しないため。
		if( !$this->conf->flg_staticurl ){
			array_push( $fin_additionalquery , urlencode( $this->req->pkey() ).'='.urlencode( $target_pid ) );
		}

		#	geneを付加
		#	act()メソッドは付加しないため。
		if( !is_array( $option['gene_deltemp'] ) ){ $option['gene_deltemp'] = array(); }
		$option['gene_deltemp'] = array_merge( $option['gene_deltemp'] , array_keys( $this->parseoption( implode( '&' , $fin_additionalquery ) ) ) );
			//↑PxFW 0.6.3 追加：additionalquery されたパラメータは、自動的に gene から一時削除するようになった。
			//↑PxFW 0.6.6 ：array_merge() されていなかった不具合を修正。
		$gene_memo = null;
		if( count( $option['gene_deltemp'] ) ){
			$gene_memo = $this->req->gene_deltemp( $option['gene_deltemp'] , 'an' );
		}else{
			$gene_memo = $this->req->gene( 'an' );
		}
		if( strlen( $gene_memo ) ){
			array_push( $fin_additionalquery , $gene_memo );
		}
		$src_fin_additionalquery = null;
		if( count( $fin_additionalquery ) ){
			$src_fin_additionalquery = implode( '&' , $fin_additionalquery );
		}
		#	/ 引数を作っておく
		#--------------------------------------

		#----------------------------------------------------------------------------
		#	サイトマップに登録されていない場合
		if( !strlen( $this->site->getpageinfo( $target_pid , 'id' ) ) && strlen( $target_pid_original ) && $target_pid != 'logout' ){
			#	[Pickles Framework 0.4.1] : バージョン 0.3.8 で発生していたバグを修正。
			#	サイト外のURLを指定した場合に、GETパラメータが捨てられてしまう現象が0.3.8以降存在していた。
			#	リソースへのリンクであるかどうか、評価しないとダメ。
			if( strpos( $target_pid_original , 'res:' ) === 0 ){
				#	リソースへのパスだったら。
				#	※$RTN は、$this->act() が返した値そのまま。
				if( strlen( $src_fin_additionalquery ) ){ $RTN = implode( '?' , array( $RTN , $src_fin_additionalquery ) ); }#	URLパラメータを付加
				if( strlen( $anch ) ){ $RTN = implode( '#' , array( $RTN , $anch ) ); }	#	アンカーを付加
				return	$RTN;
			}
			#	※$target_pid_original は、(先頭のコロンだけ展開されている点を除いて)自分が受け取った値そのまま。

			if( strpos( $target_pid_original , '/' ) === 0 ){
				#	スラッシュから始まっていたら。
				#	※$RTN は、$this->act() が返した値そのまま。
				if( strlen( $src_fin_additionalquery ) ){ $RTN = implode( '?' , array( $RTN , $src_fin_additionalquery ) ); }#	URLパラメータを付加
				if( strlen( $anch ) ){ $RTN = implode( '#' , array( $RTN , $anch ) ); }	#	アンカーを付加
				return	$RTN;
			}

			if( strpos( $target_pid_original , 'root://' ) === 0 ){
				#	root://から始まっていたら。
				#	※$RTN は、$this->act() が返した値そのまま。
				if( strlen( $src_fin_additionalquery ) ){ $RTN = implode( '?' , array( $RTN , $src_fin_additionalquery ) ); }#	URLパラメータを付加
				if( strlen( $anch ) ){ $RTN = implode( '#' , array( $RTN , $anch ) ); }	#	アンカーを付加
				return	$RTN;
			}

			if( !$this->conf->auto_notfound && preg_match( '/^[a-zA-Z0-9\.\_\-]*$/' , $target_pid ) ){
				#	↑Pickles Framework 0.5.1 で追加の分岐
				#	　ページIDに使える文字だけで構成されていたら、信じてみる。
			}else{
				return	$target_pid_original;
			}
		}
		#	/ サイトマップに登録されていない場合
		#----------------------------------------------------------------------------

		#----------------------------------------------------------------------------
		#	対象ページにリンク先/保存先の指定がある場合は、そっちを優先
		$memo_linkto = $this->site->getpageinfo( $target_pid , 'linkto' );
		if( strlen( $memo_linkto ) && !strpos( $memo_linkto , '/' ) !== 0 ){
			if( preg_match( '/^(?:(?:http|https|ftp):\/\/|javascript:)/i' , $memo_linkto ) ){
				if( strlen( $src_fin_additionalquery ) ){ $RTN = implode( '?' , array( $RTN , $src_fin_additionalquery ) ); }#	URLパラメータを付加
				if( strlen( $anch ) ){ $RTN = implode( '#' , array( $RTN , $anch ) ); }	#	アンカーを付加
				return	$RTN;
			}
			if( preg_match( '/^root:\/+/i' , $memo_linkto ) ){
				if( strlen( $src_fin_additionalquery ) ){ $RTN = implode( '?' , array( $RTN , $src_fin_additionalquery ) ); }#	URLパラメータを付加
				if( strlen( $anch ) ){ $RTN = implode( '#' , array( $RTN , $anch ) ); }	#	アンカーを付加
				return	$RTN;
			}
			if( !strlen( $this->site->getpageinfo( $target_pid , 'srcpath' ) ) ){
				if( strlen( $src_fin_additionalquery ) ){ $RTN = implode( '?' , array( $RTN , $src_fin_additionalquery ) ); }#	URLパラメータを付加
				if( strlen( $anch ) ){ $RTN = implode( '#' , array( $RTN , $anch ) ); }	#	アンカーを付加
				return	$RTN;
			}
		}
		unset( $memo_linkto );
		#	/ 対象ページにリンク先/保存先の指定がある場合は、そっちを優先
		#----------------------------------------------------------------------------

		#	URLパラメータを付加
		if( strlen( $src_fin_additionalquery ) ){ $RTN = implode( '?' , array( $RTN , $src_fin_additionalquery ) ); }
		#	アンカーを付加
		if( strlen( $anch ) ){ $RTN = implode( '#' , array( $RTN , $anch ) ); }

		return	$RTN;
	}//href()

	#--------------------------------------
	#	PHP本体のパスを得る
	function act( $target_pid = null , $option = array() ){
		#---------------------------------------------------------------------------------
		#	このメソッドは、シンプルなPHP本体へのパスを返します。
		#	動的URLが許可されている場合は、p要素を付加しません。
		#	<form>のaction属性に指定する場合にのみ使用し、通常(<a>タグの場合など)は、
		#	href()メソッドを使用してください。
		#	
		#	$target_pid が指定されていた場合は、そのページへのURLを、
		#	nullが渡った場合は、現在のページへのURLを返します。
		#	戻り値のURLは、その後ろにすぐ「?XXX=XXX」を続けられる形です。
		#	ただし、ページ内アンカー(#xxx)がついている場合は、これを残します。
		#	URLパラメータを付加しなければならない場合は、
		#	呼び出し元側の責任において、#xxx を削除してから渡してください。
		#	
		#	$target_pidには、「pid.subpid.subsubpid」と、ドット区切りで指定してください。
		#---------------------------------------------------------------------------------

		$RTN = '';

		$option = $this->parseoption( $option );

		if( is_null( $target_pid ) ){
			$target_pid = $this->req->p();
		}

		#	先頭のコロンを、現在のページID(po)に変換
		if( substr( $target_pid , 0 , 1 ) == ':' ){
			$target_pid = preg_replace( '/^\:/' , $this->req->po().'.' , $target_pid );
		}

		$target_pid_original = $target_pid;	#	元の値をメモ

		#	シャープがあったら、ページ内アンカーとして処理
		$anch = null;
		if( strpos( $target_pid , '#' ) ){
			list( $target_pid , $anch ) = explode( '#' , $target_pid , 2 );
		}
		#	ハテナがあったら、additionalqueryとして処理
		$additionalquery = null;
		if( strpos( $target_pid , '?' ) ){
			#	Pickles Framework 0.1.10 追加
			list( $target_pid , $additionalquery ) = explode( '?' , $target_pid , 2 );
			unset( $additionalquery );//act() はこれを使わないので、消す。
		}

		#	スラッシュがあったら、ファイル名として処理
		#	ただし、先頭がスラッシュだった場合は別
		if( strpos( $target_pid , '/' ) > 0 ){
			list( $target_pid , $filename ) = explode( '/' , $target_pid , 2 );
		}
		#	末尾のドットを削除
		$target_pid = preg_replace( '/\.+$/' , '' , $target_pid );
		#	先頭のドットを削除//Pickles Framework 0.5.1 追加
		$target_pid = preg_replace( '/^\.+/' , '' , $target_pid );

		#----------------------------------------------------------------------------
		#	サイトマップに登録されていない場合
		if( !strlen( $this->site->getpageinfo( $target_pid , 'id' ) ) && strlen( $target_pid_original ) && $target_pid != 'logout' ){

			#----------------------------------------------------------------------------
			#	対象ページがres:で始まっていたら
			#	Pickles Framework 0.3.8 追加
			if( strpos( $target_pid_original , 'res:' ) === 0 ){
				#	リソースのパスを返すようにする。
				$resource_option = array();
				if( strlen( $option['protocol'] ) ){
					//Pickles Framework 0.4.0 : 追加。
					$resource_option['protocol'] = $option['protocol'];
				}
				$RTN = $this->resource( preg_replace( '/^res:/i' , '' , $target_pid_original ) , $resource_option );
				return	$RTN;
			}

			#----------------------------------------------------------------------------
			#	対象ページがスラッシュで始まっていたら
			if( strpos( $target_pid_original , '/' ) === 0 ){
#				return	$this->root().$target_pid_original;//23:17 2009/03/03 Pickles Framework 0.5.9 修正
				$RTN = $this->conf->url_action.$target_pid;
				return	$RTN;
			}

			#----------------------------------------------------------------------------
			#	対象ページがroot://で始まっていたら
			if( strpos( $target_pid_original , 'root://' ) === 0 ){
				$target_pid_original = preg_replace( '/^root:\/\/+/i' , '/' , $target_pid_original );
				return	$this->root().$target_pid_original;
			}


			#	サイトマップに掲載されていなくて、
			#	パスの直接指定でもなさそうだったら、
			#	無加工のまま、そのまま返す。
			if( !$this->conf->auto_notfound && preg_match( '/^[a-zA-Z0-9\.\_\-]*$/' , $target_pid ) ){
				#	↑Pickles Framework 0.5.1 で追加の分岐
				#	　ページIDに使える文字だけで構成されていたら、信じてみる。
			}else{
				return	$target_pid_original;
			}
		}
		#	/ サイトマップに登録されていない場合
		#----------------------------------------------------------------------------

		#----------------------------------------------------------------------------
		#	対象ページにリンク先/保存先の指定がある場合は、そっちを優先
		$memo_linkto = null;
		if( $target_pid === $this->site->getpageinfo( $target_pid , 'id' ) ){
			$memo_linkto = $this->site->getpageinfo( $target_pid , 'linkto' );
		}
		if( strlen( $memo_linkto ) ){
			if( preg_match( '/^(?:(?:http|https|ftp):\/\/|javascript:|mailto:)/i' , $memo_linkto ) ){
				#	プロトコルから始まっていたら
				#	メールアドレス指定だったら
				#	JavaScript指定だったら
				return	$memo_linkto;
			}
			if( strpos( $memo_linkto , 'root://' ) === 0 ){
				#	ルートからの相対パスの指定だったら
				$memo_linkto = preg_replace( '/^root:\/+/i' , '/' , $memo_linkto );
				return	$this->root().$memo_linkto;
			}
			if( strpos( $memo_linkto , 'pid:' ) === 0 ){
				$memo_linkto = preg_replace( '/^pid\:/' , '' , $memo_linkto );
			}
			$memo_linkto2 = $memo_linkto;
			list( $memo_linkto2 ) = explode( '#' , $memo_linkto2 );
			list( $memo_linkto2 ) = explode( '?' , $memo_linkto2 );
			if( preg_match( '/^[a-zA-Z0-9\_\.\-]*$/' , $memo_linkto2 ) ){
				#	PxFW 0.6.8 追加：サイトマップツリー内ショートカット機能
				#	(ただし、サイトマップ内での無限ループ対策は考慮されないので気をつけること。)
				#	同じ実装が href() にもあるので注意！
				return	$this->act( $memo_linkto , $option );
			}
			unset( $memo_linkto2 );
			if( !strlen( $this->site->getpageinfo( $target_pid , 'srcpath' ) ) ){
				#	ソースコンテンツの指定がなければ
				return	$memo_linkto;
			}
		}
		#	/ 対象ページにリンク先/保存先の指定がある場合は、そっちを優先
		#----------------------------------------------------------------------------

		if( $option['protocol'] ){
			$RTN .= $option['protocol'].'://'.$this->conf->url_domain;
		}
		$RTN .= $this->conf->url_action;

		if( !$this->conf->flg_staticurl ){
			#	動的URLを許す場合
			if( !preg_match('/.*\/$/',$RTN) ){
				$RTN .= '/';
			}
		}elseif( preg_match( '/^\/+/i' , $memo_linkto ) ){
			#	URLマップオプションが有効で、かつ、リンク先指定がルート相対パスだったら。
			return	$RTN.$memo_linkto;
		}else{
			#	静的URLを偽装する場合
			$target_pid = preg_replace( '/\./' ,'/' , $target_pid );
			$target_pid_list = explode( '/' , $target_pid );
			$target_pid = '';
			foreach( $target_pid_list as $Line ){
				$target_pid .= '/'.urlencode( $Line );
			}

			$RTN .= $target_pid;
			if( strlen( $target_pid ) && !preg_match( '/.*\/$/' , $target_pid ) ){
				$RTN .= '/';
			}
		}

		#--------------------------------------
		#	ダウンロードコンテンツの場合、ファイル名を付加
		#	条件が曖昧で不完全なので・・・、Pickles Framework 0.5.10 で廃止
		#if( is_null( $option['filename'] ) && strlen( $this->site->getpageinfo( $target_pid , 'srcpath' ) ) ){
		#	$srcpath = $this->site->getpageinfo( $target_pid , 'srcpath' );
		#	$pathinfo = pathinfo( $srcpath );
		#	if( strlen( $pathinfo['extension'] ) && !preg_match( '/^(?:php|html?|wiki)$/i' , $pathinfo['extension'] ) ){
		#		$option['filename'] = $pathinfo['basename'];
		#	}
		#}
		#	/ ダウンロードコンテンツの場合、ファイル名を付加
		#--------------------------------------

		#	ファイル名を付加
		if( strlen( $option['filename'] ) ){
			#	オプションに、ファイル名が指示された場合、付加。
			$RTN .= $option['filename'];
		}elseif( strlen( $filename ) ){
			#	PIDに、ファイル名が指示された場合、付加。
			$RTN .= $filename;
		}elseif( strlen( $this->localconf_filename_default ) && $this->conf->flg_staticurl ){
			#	デフォルトのファイル名がある場合、付加。
			#	デフォルトファイル名は、スタティックなURLの場合のみ可
			$RTN .= $this->localconf_filename_default;
		}elseif( !$this->conf->flg_staticurl ){
			#	動的URLの場合、最後のスラッシュは削除
			$RTN = preg_replace( '/\/+$/' , '' , $RTN );
		}

		#	ページ内アンカーを付加
		if( strlen( $anch ) ){
			#	ページ内アンカーの指示がある場合、付加。
			$RTN .= '#'.$anch;
		}

		return	$RTN;
	}//act()

	#--------------------------------------
	#	<img />タグを作成する
	function img( $resource_localpath , $option = array() ){
		return	$this->mk_img( $resource_localpath , $option );
	}//img()
	function mk_img( $resource_localpath , $option = array() ){
		$option = $this->parseoption( $option );
		$src = $this->resource( $resource_localpath );
		$rollover = null;

		if( strlen( $option['id'] ) )		{ $id = ' id="'.htmlspecialchars( $option['id'] ).'"'; }//Pickles Framework 0.5.10 追加
		if( strlen( $option['width'] ) )	{ $width = ' width="'.htmlspecialchars( $option['width'] ).'"'; }
		if( strlen( $option['height'] ) )	{ $height = ' height="'.htmlspecialchars( $option['height'] ).'"'; }
		if( strlen( $option['align'] ) )	{ $opt_align = ' align="'.htmlspecialchars( $option['align'] ).'"'; }
		if( strlen( $option['border'] ) )	{ $border = ' border="'.htmlspecialchars( $option['border'] ).'"'; }//Pickles Framework 0.1.4 border属性は、明記しない限り自動付加しないように変更
		if( strlen( $option['name'] ) ){
			#	name属性に指定があれば。
			$name = ' name="'.htmlspecialchars( $option['name'] ).'"';
			if( strlen( $option['rollover'] ) ){
				#	さらにロールオーバーするなら
				$rollover = $this->js_rollover->link( $option['rollover'] , $option['name'] );
			}
		}elseif( strlen( $option['rollover'] ) ){
			#	ロールオーバーするならname属性は必須なので。
			$rollover = $this->js_rollover->link( $option['rollover'] );
			$name = ' name="'.htmlspecialchars( $this->js_rollover->get_last_imagename() ).'"';
		}

		if( strlen( $option['class'] ) ) { $opt_class  = ' class="'.htmlspecialchars( $option['class'] ).'"'; }
		if( strlen( $option['style'] ) ) { $opt_style  = ' style="'.htmlspecialchars( $option['style'] ).'"'; }
		if( strlen( $option['usemap'] ) ){ $opt_usemap = ' usemap="'.htmlspecialchars( $option['usemap'] ).'"'; }//Pickles Framework 0.2.10 追加

		$opt_js_src = '';
		if( $option['onclick'] ){//Pickles Framework 0.5.10 追加
			$opt_js_src .= ' onclick="'.htmlspecialchars( $option['onclick'] ).'"';
		}
		if( $option['onmouseover'] ){//Pickles Framework 0.5.10 追加
			$opt_js_src .= ' onmouseover="'.htmlspecialchars( $option['onmouseover'] ).'"';
			$rollover = null;//共存できない
		}
		if( $option['onmouseout'] ){//Pickles Framework 0.5.10 追加
			$opt_js_src .= ' onmouseout="'.htmlspecialchars( $option['onmouseout'] ).'"';
			$rollover = null;//共存できない
		}
		if( $option['onmousedown'] ){//Pickles Framework 0.5.10 追加
			$opt_js_src .= ' onmousedown="'.htmlspecialchars( $option['onmousedown'] ).'"';
		}
		if( $option['onmouseup'] ){//Pickles Framework 0.5.10 追加
			$opt_js_src .= ' onmouseup="'.htmlspecialchars( $option['onmouseup'] ).'"';
		}
		if( $option['ondblclick'] ){//Pickles Framework 0.5.10 追加
			$opt_js_src .= ' ondblclick="'.htmlspecialchars( $option['ondblclick'] ).'"';
		}
		if( $option['onmousemove'] ){//Pickles Framework 0.5.10 追加
			$opt_js_src .= ' onmousemove="'.htmlspecialchars( $option['onmousemove'] ).'"';
		}
		if( $option['onkeypress'] ){//Pickles Framework 0.5.10 追加
			$opt_js_src .= ' onkeypress="'.htmlspecialchars( $option['onkeypress'] ).'"';
		}
		if( $option['onkeydown'] ){//Pickles Framework 0.5.10 追加
			$opt_js_src .= ' onkeydown="'.htmlspecialchars( $option['onkeydown'] ).'"';
		}
		if( $option['onkeyup'] ){//Pickles Framework 0.5.10 追加
			$opt_js_src .= ' onkeyup="'.htmlspecialchars( $option['onkeyup'] ).'"';
		}

		#--------------------------------------
		#	画像タグ完成
		if( strlen( $option['href'] ) || strlen( $option['hrefresource'] ) ){
			#	<a>タグで包む場合は、$rolloverは<a>タグに書かれる方が望ましい
			$RTN = '<img src="'.htmlspecialchars( $src ).'"'.$id.$name.$width.$height.$border.' alt="'.htmlspecialchars( $option['alt'] ).'"'.$opt_align.$opt_class.$opt_style.$opt_js_src.$opt_usemap.' />';
		}else{
			#	<a>タグで包まない場合は、仕方がないので<img>タグに$rolloverを書く。
			$RTN = '<img src="'.htmlspecialchars( $src ).'"'.$id.$name.$width.$height.$border.' alt="'.htmlspecialchars( $option['alt'] ).'"'.$opt_align.$opt_class.$opt_style.$opt_js_src.$rollover.$opt_usemap.' />';
			#	そしてそれで完成。
			return	$RTN;
		}

		#--------------------------------------
		#	リンクの解釈
		if( !is_null( $option['href'] ) ){
			#	リンク先の指定があったら、
			#	リンクしてあげる。

			#	デフォルトターゲットはなし
			$target = '';
			if( strlen( $option['target'] ) ){
				#	ターゲットウィンドウ
				$target = ' target="'.htmlspecialchars( $option['target'] ).'"';
			}
			$RTN = '<a href="'.htmlspecialchars( $this->href($option['href']) ).'"'.$target.$rollover.'>'.$RTN.'</a>';

		}elseif( !is_null( $option['hrefresource'] ) ){
			#	リソースへリンクするなら、
			#	そっちへリンクしてあげる。
			#	※hrefと共存不可。共に指定された場合、hrefを優先

			#	デフォルトターゲットは_blank
			$target = ' onclick="window.open(this.href,\'_blank\');return false;"';
			if( strlen( $option['target'] ) ){
				#	ターゲットウィンドウ
				$target = ' onclick="window.open(this.href,\''.htmlspecialchars( $option['target'] ).'\');return false;"';
			}
			$RTN = '<a href="'.htmlspecialchars( $this->resource($option['hrefresource']) ).'"'.$target.$rollover.'>'.$RTN.'</a>';

		}

		return	$RTN;
	}//mk_img()

	#--------------------------------------
	#	リソースファイルのルートディレクトリを得る
	#	※表から見たパスです。
	function resource( $resource_localpath = null , $option = array() ){
		#	$option
		#		'mode' => 'exists' | 'internalpath' | 'localpath_cache' (PxFW >= 0.6.11) | 'realpath_cache' (PxFW >= 0.6.11)
		#		'contpath' => コンテンツのパス
		#		'protocol' => 'http' (PxFW >= 0.4.0) | 'https' (PxFW >= 0.4.0)

		$cont_info = $this->get_selectcontent_info();

		$option = $this->parseoption( $option );
		$contpath = $this->get_contfilepath();
		if( strlen( $option['contpath'] ) ){
			$contpath = $option['contpath'];
		}
		$cachefile_localpath = null;

		if( preg_match( '/^(?:(?:http|https|ftp):\/\/)/i' , $resource_localpath ) ){
			#	絶対パスだったら、そのまま返す。
			if( strtolower( $option['mode'] ) == 'exists' ){
				#	ウェブリソースは存在を確認できないため、
				#	無条件にfalseを返す。
				return	false;
			}
			return	$resource_localpath;
		}

		#	シャープがあったら、ページ内アンカーとして処理
		#	Pickles Framework 0.4.1 追加
		$anch = null;
		if( strpos( $resource_localpath , '#' ) ){
			list( $resource_localpath , $anch ) = explode( '#' , $resource_localpath , 2 );
		}

		#	ハテナがあったら、additionalqueryとして処理
		#	Pickles Framework 0.4.1 追加
		$additionalquery = null;
		if( strpos( $resource_localpath , '?' ) ){
			list( $resource_localpath , $additionalquery ) = explode( '?' , $resource_localpath , 2 );
		}

		#	グローバルリソースか、ローカルリソースかの判断
		#	パスの先頭に[L://]または[S://]または[G://]または[T://]をつけることで表現
		#	デフォルトはグローバル
		$is_local = false;
		$is_theme = false;
		$is_sys = false;
		if( preg_match( '/^L(?:ocal)?\:\/\//i' , $resource_localpath ) ){
			#	LまたはLocalで始まっていたら(大文字小文字を区別しない)
			$is_local = true;
			$is_theme = false;
			$is_sys = false;
			$resource_localpath = preg_replace( '/^L(?:ocal)?\:\/\//i' , '' , $resource_localpath );
		}elseif( preg_match( '/^T(?:heme)?\:\/\//i' , $resource_localpath ) ){
			#	TまたはThemeで始まっていたら(大文字小文字を区別しない)
			$is_local = true;
			$is_theme = true;
			$is_sys = false;
			$resource_localpath = preg_replace( '/^T(?:heme)?\:\/\//i' , '' , $resource_localpath );
		}elseif( preg_match( '/^S(?:ys(?:tem)?)?\:\/\//i' , $resource_localpath ) ){
			#	SまたはSysまたはSystemで始まっていたら(大文字小文字を区別しない)
			$is_local = true;
			$is_theme = false;
			$is_sys = true;
			$resource_localpath = preg_replace( '/^S(?:ys(?:tem)?)?\:\/\//i' , '' , $resource_localpath );
		}elseif( preg_match( '/^G(?:lobal)?\:\/\//i' , $resource_localpath ) ){
			#	GまたはGlobalで始まっていたら(大文字小文字を区別しない)
			$is_local = false;
			$is_theme = false;
			$is_sys = false;
			$resource_localpath = preg_replace( '/^G(?:lobal)?\:\/\//i' , '' , $resource_localpath );
		}elseif( preg_match( '/^(?:\\\\|\/)+(?:common(?:\/|$))?/i' , $resource_localpath ) ){
			#	スラッシュまたはバックスラッシュ/commonで始まっていたら(大文字小文字を区別しない)
			$is_local = false;
			$is_theme = false;
			$is_sys = false;
			if( strlen( $this->conf->url_resource ) ){
				//	↑commonのパスを設定しない場合、共有リソースの管理を行わない、ことにした。PxFW 0.7.2
				$resource_localpath = preg_replace( '/^(?:\\\\|\/)+common(\/|$)/i' , '$1' , $resource_localpath );	#先頭のcommonを削除
			}
		}else{
			#	スラッシュ以外で始まっていたら
			$is_local = true;
			$is_sys = false;
			$resource_localpath = preg_replace( '/^(?:'.preg_quote($cont_info['FileName'],'/').'|contents)\.items(?:\\\\|\/)+resources(?:\\\\|\/)+/i' , '' , $resource_localpath );
				#先頭のcontents.items/resources/を削除
				#	(↑この1行は 23:43 2007/07/27 Pickles Framework 0.1.4 で追加の処理)
				#PxFW 0.6.9 (0:32 2010/05/31) 「contents.items」の代わりに「コンテンツのファイル名.items」も使えるようになった。DW対策。
				#	だけど「コンテンツのファイル名.items」はすごく非推奨。ファイルを移動したときとかには成立しなくなる可能性があるため。
		}

		if( $is_local ){
			#--------------------------------------
			#	ローカルリソースまたはテーマリソースへのパス

			$dirname = dirname( $cont_info['localpath_content'] );
			if( $dirname == '/' || $dirname == '\\' ){
				$dirname = '';
			}

			$cachefile_localpath = $this->get_contresource_cache_localpath();//←PxFW 0.6.10 コンテンツ専用リソースのローカルパスの算出ロジックを外部化した。

			if( !$is_theme ){
				#	コンテンツローカルリソース
				$resource_dir = $this->conf->url_localresource;
				$resource_dir = preg_replace( '/^(?:\\\\|\/)+/' , '' , $resource_dir );	#先頭のスラッシュを削除
				$outernal_path = $this->root().'/'.$resource_dir.$cachefile_localpath;
				if( $is_sys ){
					$outernal_path .= '/system';
				}else{
					$outernal_path .= '/resources';
				}
			}else{
				#	テーマリソース
				$resource_dir = $this->conf->url_themeresource.'/'.$this->user->gettheme().'/'.$this->user->get_ct();
				$resource_dir = preg_replace( '/^(?:\\\\|\/)+/' , '' , $resource_dir );	#先頭のスラッシュを削除
				$outernal_path = $this->root().'/'.$resource_dir;
			}

			if( $is_theme ){
				$localpath_cache = '/'.$this->user->gettheme().'/'.$this->user->get_ct().'/'.$resource_localpath;
				$path_public = $this->conf->path_docroot.$this->conf->url_themeresource.$localpath_cache;
				$path_origin = $this->conf->path_theme_collection_dir.$this->user->gettheme().'/'.$this->user->get_ct().'/public.items/'.$resource_localpath;
			}elseif( $is_sys ){
				$localpath_cache = $cachefile_localpath.'/system/'.$resource_localpath;
				$path_public = $this->conf->path_docroot.$this->conf->url_localresource.$localpath_cache;
				$path_origin = $this->conf->path_contents_dir.$dirname.'/'.$cont_info['FileName'].'.items/system/'.$resource_localpath;
			}else{
				$localpath_cache = $cachefile_localpath.'/resources/'.$resource_localpath;
				$path_public = $this->conf->path_docroot.$this->conf->url_localresource.$localpath_cache;
				$path_origin = $this->conf->path_contents_dir.$dirname.'/'.$cont_info['FileName'].'.items/resources/'.$resource_localpath;
			}

			if( strtolower( $option['mode'] ) == 'exists' ){
				#	ここで、リソースの内部パスが決まるので、
				#	existsモードなら、真偽を返しちゃう。
				return	$this->dbh->file_exists( $path_origin );
			}elseif( strtolower( $option['mode'] ) == 'internalpath' ){
				#	ここで、リソースの内部パスが決まるので、
				#	internalpath(内部パス)モードなら、そのまま返しちゃう。
				return	$path_origin;
			}elseif( $option['mode'] == 'realpath_cache' ){
				#	キャッシュのパス (PxFW >= 0.6.11)
				return $this->dbh->get_realpath($path_public);
			}elseif( $option['mode'] == 'localpath_cache' ){
				#	キャッシュのローカルパス (PxFW >= 0.6.11)
				#	キャッシュフォルダに設定された共通のパス以降を返す。
				return $localpath_cache;
			}


			#--------------------------------------
			#	公開フォルダにコピーされていなかった場合の処理
			set_time_limit(0);//←Pickles Framework 0.6.11
			ignore_user_abort(true);//←PxFramework 0.6.11
			if( !$this->dbh->file_exists( $path_public ) || ( $this->dbh->file_exists( $path_public ) && $this->dbh->file_exists( $path_origin ) && $this->dbh->comp_filemtime( $path_origin , $path_public ) ) ){
				#	公開フォルダ(ローカルリソースキャッシュディレクトリ)に
				#	コピーされていなかったら、コピーする。
				#	Pickles Framework 0.5.1 : 対象がディレクトリでもキャッシュできるようにした。
				$dirpath = dirname( $path_public );
				if( !$this->dbh->is_dir( $dirpath ) ){
					if( !$this->dbh->mkdirall( $dirpath ) ){
						$this->errors->error_log( 'ディレクトリ['.$dirpath.']の作成に失敗しました。' );
					}
				}
				if( !$this->dbh->copyall( $path_origin , $path_public ) ){
					$this->errors->error_log( '['.$path_origin.']から['.$path_public.']へのコピーに失敗しました。' );
				}
			}
			ignore_user_abort(false);//←PxFramework 0.6.11
			set_time_limit(30);//←Pickles Framework 0.6.11
			#	/ 公開フォルダにコピーされていなかった場合の処理
			#--------------------------------------

		}else{
			#--------------------------------------
			#	グローバルリソースへのパス
			$resource_dir = $this->conf->url_resource;	//←[/common]とかが入っているコンフィグ項目
			if( preg_match( '/^(?:\\\\|\/)/' , $resource_dir ) ){
				#	スラッシュ(またはバックスラッシュ)から始まる場合
				$resource_dir = preg_replace( '/^(?:\\\\|\/)+/' , '' , $resource_dir );	#先頭のスラッシュを削除
				$outernal_path = $this->root().'/'.$resource_dir;

				switch( strtolower( $option['mode'] ) ){
					case 'exists':
					case 'internalpath':
					case 'realpath_cache':
					case 'localpath_cache':
						$resource_localpath = preg_replace( '/^(?:\\\\|\/)+/' , '' , $resource_localpath );	#先頭のスラッシュを削除
						if( strlen( $resource_localpath )){ $resource_localpath = '/'.$resource_localpath; }
						$internal_path = $this->conf->path_docroot.$resource_dir.$resource_localpath;
						if( strtolower( $option['mode'] ) == 'internalpath' ){
							#	内部パスが欲しい
							return	$internal_path;
						}elseif( strtolower( $option['mode'] ) == 'realpath_cache' ){
							#	キャッシュのパスが欲しい (PxFW >= 0.6.11)
							#	共有リソースは直接実体を参照するので、
							#	internalpath と同じ値を返す。
							return	$internal_path;
						}elseif( strtolower( $option['mode'] ) == 'localpath_cache' ){
							#	キャッシュのローカルパスが欲しい (PxFW >= 0.6.11)
							return	$resource_localpath;
						}else{
							#	存在を確認したい
							return	$this->dbh->file_exists( $internal_path );
						}
						break;
				}

			}else{
				#	スラッシュから始まらない場合、
				#	プロジェクト外のウェブリソースディレクトリと判断
				switch( strtolower( $option['mode'] ) ){
					case 'exists':
					case 'internalpath':
					case 'realpath_cache':
					case 'localpath_cache':
						#	ウェブリソースは存在を確認できないため、
						#	無条件にfalseを返す。
						return	false;
						break;
				}
				$outernal_path = $resource_dir;
			}
		}

		$resource_localpath = preg_replace( '/^(?:\\\\|\/)+/' , '' , $resource_localpath );	#先頭のスラッシュを削除
		if( strlen( $resource_localpath )){ $resource_localpath = '/'.$resource_localpath; }

		$RTN = '';
		if( $option['protocol'] ){
			#	Pickles Framework 0.4.0 : $option['protocol'] に対応。
			$RTN .= $option['protocol'].'://'.$this->conf->url_domain;
		}
		$RTN .= $outernal_path.$resource_localpath;

		if( strlen( $additionalquery ) ){
			#	Pickles Framework 0.4.1 : 追加
			$RTN = implode( '?' , array( $RTN , $additionalquery ) );
		}
		if( strlen( $anch ) ){
			#	Pickles Framework 0.4.1 : 追加
			$RTN = implode( '#' , array( $RTN , $anch ) );
		}

		return	$RTN;
	}
	#--------------------------------------
	#	リソースファイルが存在するかどうかを調べる
	function resource_exists( $resource_localpath = null , $option = array() ){
		$option = $this->parseoption( $option );
		$option['mode'] = 'exists';
		return	$this->resource( $resource_localpath , $option );
			#	判断が複雑なので、
			#	resource()に統合したい。
	}

	#--------------------------------------
	#	カレントコンテンツの専用リソースキャッシュディレクトリのローカルパスを返す
	#	PxFW 0.6.10 追加
	function get_contresource_cache_localpath(){
		static $RTN;
		if( !is_null( $RTN ) ){
			return $RTN;
		}
		$cont_info = $this->get_selectcontent_info();
		$dirname = dirname( $cont_info['localpath_content'] );
		if( $dirname == '/' || $dirname == '\\' ){
			$dirname = '';
		}

		if( !$this->conf->generate_localresourcepath_by_pid ){
			#	通常通りの処理。
			$RTN = $dirname.'/'.$cont_info['FileName'].'.items';
		}else{
			#	Pickles Framework 0.2.7 追加の設定に対する処理。
			#	ローカルリソースキャッシュのパスを、
			#	コンテンツではなくサイトマップから生成する。
			$tmp_pid = $this->site->getpageinfo( $this->req->p() , 'id' );//Pickles Framework 0.5.1 : $req->p() を直接じゃなくて、存在するpidにしてから使うことにした。
			$tmp_linkto = null;
			if( is_string( $tmp_pid ) ){
				#	Pickles Framework 0.5.2 : $tmp_pid が存在しないページIDだった場合にエラーが発生する不具合を修正。
				$tmp_linkto = $this->site->getpageinfo( $tmp_pid , 'linkto' );
			}
			if( strlen( $tmp_linkto ) && preg_match( '/^\//' , $tmp_linkto ) ){
				#	Pickles Framework 0.5.2 : strlen( $tmp_linkto ) を条件に追加。
				$tmp_filepath = $tmp_linkto;
			}elseif( !strlen( $tmp_pid ) ){
				#	Pickles Framework 0.5.2 : トップページだった場合の軽量化処置を追加。
				if( strlen( $this->conf->default_filename ) ){
					$tmp_filepath = '/'.$this->conf->default_filename;
				}else{
					$tmp_filepath = '/'.'index.html';
				}
			}else{
				$tmp_dirname = preg_replace( '/\.+/' , '/' , $tmp_pid );
				$tmp_filename = $this->conf->default_filename;//←Pickles Framework 0.5.2 : 設定値を反映するようにした。
				if( !strlen( $tmp_filename ) ){
					$tmp_filename = 'index.html';
				}
				if( strlen( $this->conf->default_filename ) ){
					$tmp_filename = $this->conf->default_filename;
				}
				$tmp_filepath = '';
				if( strlen( $tmp_dirname ) ){
					#	Pickles Framework 0.4.5：トップページで cont.items//index.items となっていた不具合に対する修正。
					$tmp_filepath .= '/'.$tmp_dirname;
				}
				$tmp_filepath .= '/'.$tmp_filename;
				unset( $tmp_dirname );
				unset( $tmp_filename );
			}
			$RTN = text::trimext( $tmp_filepath ).'.items';
			unset( $tmp_filepath );
			unset( $tmp_linkto );
		}
		return $RTN;
	}

	#--------------------------------------
	#	全部のリソースファイルをキャッシュする
	function cache_all_resources( $scope = 'local' , $path = null ){
		#	カレントスコープに設置されている、
		#	ローカルリソースまたはテーマリソースの全てをキャッシュする。
		#	Pickles Framework 0.3.3 追加

		switch( strtolower( $scope ) ){
			case 'l':
			case 'local':
				$scope = 'local';
				break;
			case 's':
			case 'system':
				$scope = 'system';
				break;
			case 't':
			case 'theme':
				$scope = 'theme';
				break;
			default:
				return false;
				break;
		}

		if( strlen( $path ) && !preg_match( '/^\//si' , $path ) ){
			#	$path はスラッシュで始まらないとダメ
			return	false;
		}

		if( $scope == 'local' || $scope == 'system' ){
			#	ローカル(またはシステム)
			$path_info = $this->parse_contentspath( $this->get_contfilepath() );
			$path_base = $path_info['path_workdir'];
			if( $scope == 'local' ){
				$path_base .= '/resources';
			}else{
				$path_base .= '/system';
			}
			$path_base .= $path;

		}elseif( $scope == 'theme' ){
			#	テーマ
			$path_base = $this->conf->path_theme_collection_dir.$this->user->gettheme().'/'.$this->user->get_ct().'/public.items'.$path;

		}

		if( !$this->dbh->is_dir( $path_base ) ){
			return	false;
		}

		$item_list = $this->dbh->getfilelist( $path_base );
		foreach( $item_list as $basename ){
			if( $this->dbh->is_file( $path_base.'/'.$basename ) ){
				#	ファイルならキャッシュする
				if( $scope == 'local' ){
					$this->resource( 'L:/'.$path.'/'.$basename );
				}elseif( $scope == 'system' ){
					$this->resource( 'S:/'.$path.'/'.$basename );
				}elseif( $scope == 'theme' ){
					$this->resource( 'T:/'.$path.'/'.$basename );
				}
			}else{
				#	ディレクトリなら再帰処理
				$this->cache_all_resources( $scope , $path.'/'.$basename );
			}
		}

		return	true;
	}

	#--------------------------------------
	#	フォームのhidden要素を書き出す
	function mk_formelm_hidden( $key = null , $val = null , $option = array() ){
		$option = $this->parseoption( $option );
		if( $key == $this->req->pkey() ){
			if( $this->conf->flg_staticurl ){
				#	スタティックURLが有効なら、この値は不要と判断する。
				return	'';
			}
			#	先頭のコロンを、現在のページID(po)に変換
			$val = preg_replace( '/^:/' , $this->req->po().'.' , $val );

		}
		if( !strlen( $key ) ){
			#	キーが空っぽなら、この値は不要と判断する。
			return	'';
		}
		return	'<input type="hidden" name="'.htmlspecialchars( $key ).'" value="'.htmlspecialchars( $val ).'" />';
	}

	#--------------------------------------
	#	フォームに標準的に挿入すべき値を、hiddenタグに入れて返す。
	function mk_form_defvalues( $pid = null , $option = array() ){
		$option = $this->parseoption( $option );

		#	先頭のコロンを、現在のページID(po)に変換
		$pid = preg_replace( '/^:/' , $this->req->po().'.' , $pid );

		#	送信先のセット
		if( $this->conf->flg_staticurl ){
			#	スタティックURLが有効なら、この値は不要と判断する。
			$SRCP = '';
		}else{
			if( is_null( $pid ) ){
				$pid = $this->req->p();
			}
			$SRCP = '<input type="hidden" name="'.htmlspecialchars( $this->req->pkey() ).'" value="'.htmlspecialchars( $pid ).'" />';
		}

		#	GENEの取得
		if( is_array( $option['gene_deltemp'] ) ){
			$SRCGENE = $this->req->gene_deltemp( $option['gene_deltemp'] , 'form' );
		}else{
			$SRCGENE = $this->req->gene( 'form' );
		}

		return	$SRCP.$SRCGENE;
	}

	#--------------------------------------
	#	日付選択インターフェイスを作る
	function mk_form_select_date( $mode = 'input' , $option = array() ){
		#	18:32 2007/09/28 Pickles Framework 0.1.9 追加

		#	カラム名を決定
		$name = array();
		if( !strlen($option['prefix']) ){
			$option['prefix'] = 'selectdate';
		}
		$name['y'] = $option['prefix'].'_date_y';
		$name['m'] = $option['prefix'].'_date_m';
		$name['d'] = $option['prefix'].'_date_d';
		$name['h'] = $option['prefix'].'_date_h';
		$name['i'] = $option['prefix'].'_date_i';
		$name['s'] = $option['prefix'].'_date_s';

		#	デフォルト値を決定
		$int_now = time();
		if( strlen( $option['default'] ) ){
			if( preg_match( '/^[0-9]+$/is' , $option['default'] ) ){
				$int_now = intval( $option['default'] );
			}else{
				$int_now = time::datetime2int( $option['default'] );
			}
		}
		if( strlen( $this->req->in( $name['y'] ) ) || strlen( $this->req->in( $name['m'] ) ) || strlen( $this->req->in( $name['d'] ) ) || strlen( $this->req->in( $name['h'] ) ) || strlen( $this->req->in( $name['i'] ) ) || strlen( $this->req->in( $name['s'] ) ) ){
			#	実際に選択された値で、デフォルト値を上書き
			$int_now = mktime(
				intval( $this->req->in( $name['h'] ) ) ,
				intval( $this->req->in( $name['i'] ) ) ,
				intval( $this->req->in( $name['s'] ) ) ,
				intval( $this->req->in( $name['m'] ) ) ,
				intval( $this->req->in( $name['d'] ) ) ,
				intval( $this->req->in( $name['y'] ) )
			);
		}
		if( $mode == 'get_int' ){
			#	INT値を返すモード
			return	$int_now;
		}elseif( $mode == 'get_datetime' ){
			#	datetime値を返すモード
			return	time::int2datetime( $int_now );
		}

		$dateinfo = getdate( $int_now );

		$selected = array();
		$selected['y'] = intval( $dateinfo['year'] );
		$selected['m'] = intval( $dateinfo['mon'] );
		$selected['d'] = intval( $dateinfo['mday'] );
		$selected['h'] = intval( $dateinfo['hours'] );
		$selected['i'] = intval( $dateinfo['minutes'] );
		$selected['s'] = intval( $dateinfo['seconds'] );
		unset($dateinfo);

		#--------------------------------------
		#	レイアウトを決定
		$layout = $option['layout'];
		if( !strlen( $layout ) ){
			$layout = '[Y]/[M]/[D] [H]:[I]:[S]';
		}

		if( $mode == 'confirm' ){
			#	確認用出力モード
			$RTN = $layout;
			$RTN = preg_replace( '/'.preg_quote('[Y]','/').'/i' , intval( $this->req->in( $name['y'] ) ) , $RTN );
			$RTN = preg_replace( '/'.preg_quote('[M]','/').'/i' , intval( $this->req->in( $name['m'] ) ) , $RTN );
			$RTN = preg_replace( '/'.preg_quote('[D]','/').'/i' , intval( $this->req->in( $name['d'] ) ) , $RTN );
			$RTN = preg_replace( '/'.preg_quote('[H]','/').'/i' , intval( $this->req->in( $name['h'] ) ) , $RTN );
			$RTN = preg_replace( '/'.preg_quote('[I]','/').'/i' , intval( $this->req->in( $name['i'] ) ) , $RTN );
			$RTN = preg_replace( '/'.preg_quote('[S]','/').'/i' , intval( $this->req->in( $name['s'] ) ) , $RTN );
			return	$RTN;
		}elseif( $mode == 'hidden' ){
			#	hiddenタグ出力モード
			$RTN = '';
			$RTN .= '<input type="hidden" name="'.htmlspecialchars( $name['y'] ).'" value="'.intval( $this->req->in( $name['y'] ) ).'" />';
			$RTN .= '<input type="hidden" name="'.htmlspecialchars( $name['m'] ).'" value="'.intval( $this->req->in( $name['m'] ) ).'" />';
			$RTN .= '<input type="hidden" name="'.htmlspecialchars( $name['d'] ).'" value="'.intval( $this->req->in( $name['d'] ) ).'" />';
			$RTN .= '<input type="hidden" name="'.htmlspecialchars( $name['h'] ).'" value="'.intval( $this->req->in( $name['h'] ) ).'" />';
			$RTN .= '<input type="hidden" name="'.htmlspecialchars( $name['i'] ).'" value="'.intval( $this->req->in( $name['i'] ) ).'" />';
			$RTN .= '<input type="hidden" name="'.htmlspecialchars( $name['s'] ).'" value="'.intval( $this->req->in( $name['s'] ) ).'" />';
			return	$RTN;
		}


		#	年
		$SRC['y'] = '';
		$SRC['y'] .= '<select name="'.htmlspecialchars($name['y']).'">';
		$c = array( intval( $selected['y'] ) =>' selected="selected"' );
		$max_year = date('Y');
		if( strlen( $option['max_year'] ) ){
			#	Pickles Framework 0.5.5 : $option['max_year'] を追加。
			$max_year = intval($option['max_year']);
		}
		$min_year = 1970;
		if( strlen( $option['min_year'] ) ){
			#	Pickles Framework 0.5.5 : $option['min_year'] を追加。
			$min_year = intval($option['min_year']);
		}
		for( $i = $min_year; $i <= $max_year; $i ++ ){
			$i = intval( $i );
			$SRC['y'] .= '<option value="'.intval( $i ).'"'.$c[$i].'>'.intval( $i ).'</option>';
		}
		$SRC['y'] .= '</select>';

		#	月
		$SRC['m'] = '';
		$SRC['m'] .= '<select name="'.htmlspecialchars($name['m']).'">';
		$c = array( intval( $selected['m'] ) =>' selected="selected"' );
		for( $i = 1; $i <= 12; $i ++ ){
			$i = intval( $i );
			$SRC['m'] .= '<option value="'.intval( $i ).'"'.$c[$i].'>'.intval($i).'</option>';
		}
		$SRC['m'] .= '</select>';

		#	日
		$SRC['d'] = '';
		$SRC['d'] .= '<select name="'.htmlspecialchars($name['d']).'">';
		$c = array( intval( $selected['d'] ) =>' selected="selected"' );
		for( $i = 1; $i <= 31; $i ++ ){
			$i = intval( $i );
			$SRC['d'] .= '<option value="'.intval( $i ).'"'.$c[$i].'>'.intval($i).'</option>';
		}
		$SRC['d'] .= '</select>';

		#	時
		$SRC['h'] = '';
		$SRC['h'] .= '<select name="'.htmlspecialchars($name['h']).'">';
		$c = array( intval( $selected['h'] ) =>' selected="selected"' );
		for( $i = 0; $i <= 23; $i ++ ){
			$i = intval( $i );
			$SRC['h'] .= '<option value="'.intval( $i ).'"'.$c[$i].'>'.intval($i).'</option>';
		}
		$SRC['h'] .= '</select>';

		#	分
		$SRC['i'] = '';
		$SRC['i'] .= '<select name="'.htmlspecialchars($name['i']).'">';
		$c = array( intval( $selected['i'] ) =>' selected="selected"' );
		for( $i = 0; $i <= 59; $i ++ ){
			$i = intval( $i );
			$SRC['i'] .= '<option value="'.intval( $i ).'"'.$c[$i].'>'.intval($i).'</option>';
		}
		$SRC['i'] .= '</select>';

		#	秒
		$SRC['s'] = '';
		$SRC['s'] .= '<select name="'.htmlspecialchars($name['s']).'">';
		$c = array( intval( $selected['s'] ) =>' selected="selected"' );
		for( $i = 0; $i <= 59; $i ++ ){
			$i = intval( $i );
			$SRC['s'] .= '<option value="'.intval( $i ).'"'.$c[$i].'>'.intval($i).'</option>';
		}
		$SRC['s'] .= '</select>';

		$RTN = $layout;
		$RTN = preg_replace( '/'.preg_quote('[Y]','/').'/i' , $SRC['y'] , $RTN );
		$RTN = preg_replace( '/'.preg_quote('[M]','/').'/i' , $SRC['m'] , $RTN );
		$RTN = preg_replace( '/'.preg_quote('[D]','/').'/i' , $SRC['d'] , $RTN );
		$RTN = preg_replace( '/'.preg_quote('[H]','/').'/i' , $SRC['h'] , $RTN );
		$RTN = preg_replace( '/'.preg_quote('[I]','/').'/i' , $SRC['i'] , $RTN );
		$RTN = preg_replace( '/'.preg_quote('[S]','/').'/i' , $SRC['s'] , $RTN );
		return	$RTN;
	}



	#--------------------------------------
	#	スペーサーを作る
	function spacer( $width = 10 , $height = 10 ){
		#	Pickles Framework 0.1.11
		#		・デフォルトに10が入るようにした
		$src_width = ' width="10"';
		if( strlen( $width ) ){
			$src_width = ' width="'.htmlspecialchars($width).'"';
		}
		$src_height = ' height="10"';
		if( strlen( $height ) ){
			$src_height = ' height="'.htmlspecialchars($height).'"';
		}
		return	'<img src="'.htmlspecialchars( $this->resource('/img/spacer.gif') ).'"'.$src_width.$src_height.' alt="" />';
	}

	#----------------------------------------------------------------------------
	#	テーマに渡されるメッセージを $site から取り出し、キーと値の一覧を返す
	function parse_messagestring( $msg = null ){
		if( is_null( $msg ) ){
			$msg = $this->site->get_theme_message( $this->req->p() );
		}
		if( is_array( $msg ) ){
			#	配列だったらそのまま返す。
			return	$msg;
		}
		$RTN = array();
		$varlist = explode( '&' , $msg );
		foreach( $varlist as $Line ){
			if( !strlen( $Line ) ){ continue; }
			list( $key , $val ) = explode( '=' , $Line );
			$RTN[urldecode( $key )] = urldecode( $val );
		}
		return	$RTN;
	}
	#----------------------------------------------------------------------------
	#	テーマにメッセージを渡す
	function setmessage( $key , $value = null ){
		if( !is_string( $key ) || !strlen( $key ) ){ return false; }

		if( is_null( $value ) ){
			#	null値が渡ったら、項目を削除
			unset( $this->message[$key] );
			return	true;
		}elseif( is_string( $value ) ){
			#	string値が渡ったら、項目をセット
			$this->message[$key] = $value;
			return	true;
		}

		return	false;
	}
	#----------------------------------------------------------------------------
	#	テーマに渡されたメッセージを取得
	function getmessage( $key = null ){
		if( !is_string( $key ) ){ return $this->message; }
		return	$this->message[$key];
	}

	#--------------------------------------
	#	オプションを分解して、連想配列で返す。
	function parseoption( $Val , $sep1 = '&' , $sep2 = '=' ){
		if( is_array( $Val ) ){ return $Val; }
		if( !is_string( $Val ) ){ return array(); }

		$list1 = explode( $sep1 , $Val );
		$RTN = array();
		foreach( $list1 as $Line ){
			if( !strlen( $Line ) ){ continue; }
			$list2 = explode( $sep2 , $Line );
			if( $sep1 == '&' ){
				#	&区切りの場合、URLデコードする
				$RTN[urldecode( $list2[0] )] = urldecode( $list2[1] );
			}else{
				$RTN[$list2[0]] = $list2[1];
			}
		}
		return	$RTN;
	}

	#--------------------------------------
	#	コンテンツファイルのローカルパスをセット/ゲット
	function set_selectcontent_info( $selectcontent ){
		$this->selectcontent = $selectcontent;
		return	true;
	}
	function get_selectcontent_info(){
		return	$this->selectcontent;
	}
	function get_contfilepath(){
		return	$this->selectcontent['localpath_content'];
	}



	#┏------------------------------------------------------------------------┓
	#	日付や時刻の表示フォーマット管理
	#		Pickles Framework 0.5.8 : 追加

	#----------------------------------------------------------------------------
	#	日付表示フォーマットを取得する
	function dateformat( $type = 'date' , $timestamp = null ){
		#	Pickles Framework 0.5.8 : 追加
		if( !strlen( $type ) ){
			$type = 'date';
		}
		if( !strlen( $timestamp ) ){
			$timestamp = $this->conf->time;
			if( !strlen( $timestamp ) ){
				$timestamp = time();
			}
			$timestamp = intval( $timestamp );
		}
		if( !is_int( $timestamp ) ){
			$timestamp = time::datetime2int( $timestamp );
		}
		$format = $this->dateformatlist[$type];
		if( !strlen( $format ) ){
			return	null;
		}
		$RTN = date( $format , $timestamp );
		return	$RTN;
	}
	#----------------------------------------------------------------------------
	#	日付表示フォーマットをセットする
	function set_dateformat( $type , $format ){
		#	Pickles Framework 0.5.8 : 追加
		$this->dateformatlist[$type] = $format;
		return	true;
	}

	#┏------------------------------------------------------------------------┓
	#	カラーマネジメント

	#----------------------------------------------------------------------------
	#	色情報をセットする
	function setcolor_defaultsetup(){
		#--------------------------------------
		#	動的に色を設定する必要がある場合のみ使用。
		#	そうでない場合は、メンバーとして登録してください。
		#--------------------------------------
		return	true;
	}

	#----------------------------------------------------------------------------
	#	色情報を単品でセットする
	function setcolor( $name , $value ){
		if( !is_object( $this->view_outline ) ){
			#	$view_outlineがまだ作成されていなければ、登録できない。
			return	false;
		}
		eval( '$this->view_outline->colors_'.strtolower($name).' = $value;' );
		return	true;
	}

	#----------------------------------------------------------------------------
	#	色情報を取得する
	function getcolor( $name = 'key' ){
		if( !is_object( $this->view_outline ) ){
			#	$view_outlineがまだ作成されていなければ、取得できない。
			return	false;
		}

		if( is_string( $name ) ){
			$RTN = eval( 'return $this->view_outline->colors_'.strtolower( $name ).';' );
			if( strlen( $RTN ) ){
				return	$RTN;
			}else{
				return	'#000000';
			}
		}

		return	$this->view_outline->colors_key;
	}

	#	/ カラーマネジメント
	#┗------------------------------------------------------------------------┛



	#--------------------------------------
	#	itemsディレクトリのパスを得る
	function get_path_items(){
		#	Pickles Framework 0.5.10 : 追加
		$contetpath = $this->parse_contentspath();
		return	$contetpath['path_workdir'];
	}

	#--------------------------------------
	#	コンテンツファイルのローカルパス文字列からパス情報を分解して返す
	function parse_contentspath( $pathstr = null , $theme = null ){
		if( is_null( $pathstr ) ){
			#	初期値
			#	Pickles Framework 0.3.3 以降、$pathstr は省略可能です。
			$pathstr = $this->get_contfilepath();
		}

		#	コンテンツが格納されているディレクトリのリアルパス
		if( strlen( $theme ) ){
			#	$theme は、 'CT:default' のような形式で指定。
			list( $theme_ct , $theme_id ) = explode( ':' , $theme );
			if( strlen( $theme_ct ) && strlen( $theme_id ) ){
				#	クライアントタイプとテーマIDが
				#	どちらも指定されていたら。
				$RTN['pathroot'] = $this->conf->path_theme_collection_dir.$theme_id.'/'.$theme_ct.'/contents';

				#	テーマ名
				$RTN['theme'] = $theme_id;
				#	CT名
				$RTN['filename_ct'] = $theme_ct;

			}
		}
		if( !strlen( $RTN['pathroot'] ) ){
			#	テーマが指定されていなければ、
			#	標準のcontentsディレクトリをセット
			$RTN['pathroot'] = $this->conf->path_contents_dir;
		}

		if( $this->dbh->is_dir( $RTN['pathroot'] ) ){
			$RTN['pathroot'] = text::realpath( $RTN['pathroot'] ).'/';
		}

		#	第一引数で受け取ったパス
		$RTN['file_localpath'] = $pathstr;

		#	第一引数で指示されたファイルのリアルパス
		$RTN['file_realpath'] = $this->dbh->get_realpath( $RTN['pathroot'].$RTN['file_localpath'] );

		#	第一引数で指示されたファイルの、ファイル単体の名前
		$RTN['basename'] = $this->dbh->get_basename( $RTN['file_realpath'] );

		#	ファイル単体の名前のうち、ファイル名部分
		$RTN['filename'] = $this->dbh->get_filename( $RTN['file_realpath'] );

		#	ファイル単体の名前のうち、ファイル名部分を、本体とクライアントタイプに分割
		list( $filename_body , $filename_ct ) = explode( '@' , $RTN['filename'] );
		$RTN['filename_body'] = $filename_body;
		if( strlen( $filename_ct ) ){ $RTN['filename_ct'] = $filename_ct; }

		#	ファイル単体の名前のうち、拡張子部分
		$RTN['extension'] = $this->dbh->get_extension( $RTN['file_realpath'] );

		#	第一引数で指示されたファイルが格納されているディレクトリへのパス
		$RTN['dir_localpath'] = dirname( $RTN['file_localpath'] );
		if( $RTN['dir_localpath'] == '/' || $RTN['dir_localpath'] == '\\' ){ $RTN['dir_localpath'] = ''; }
		else{ $RTN['dir_localpath'] .= '/'; }

		#	第一引数で指示されたファイルが格納されているディレクトリへのリアルパス
		$RTN['dir_realpath'] = $this->dbh->get_realpath( $RTN['pathroot'].'/'.$RTN['dir_localpath'] ).'/';

		#	作業フォルダへのローカルパス(PxFW 0.6.10 追加)
		$RTN['localpath_workdir'] = $RTN['dir_localpath'].$RTN['filename'].'.items'.'/';

		#	作業フォルダへのフルパス
		$RTN['path_workdir'] = $this->dbh->get_realpath( $this->conf->path_contents_dir.$RTN['dir_localpath'].$RTN['filename'].'.items' ).'/';

		#	作業キャッシュフォルダへのフルパス(PxFW 0.6.10追加)
		$RTN['path_workdir_cache'] = $this->dbh->get_realpath( $this->conf->path_cache_dir.'cont.items/'.$RTN['dir_localpath'].$RTN['filename'].'.items' ).'/';

		return	$RTN;
	}


	#----------------------------------------------------------------------------
	#	出力ソースのエンコーディングをセットする
	function set_output_encoding( $encoding = null ){
		if( strlen( $encoding ) ){
			#	エンコーディングが指定されていたら、
			#	文字コードをセット。
			$this->localconf_outputencoding = $encoding;
			@ini_set( 'mbstring.http_output' , $encoding );
		}else{
			#	エンコーディングが指定されていなければ、
			#	コンフィグに指定のエンコーディングでフォーマット。
			if( !strlen( $this->conf->php_mb_http_output ) ){
				#	指定がなければ内部エンコーディングに
				$this->conf->php_mb_http_output = mb_internal_encoding();
			}
			$this->localconf_outputencoding = null;
			if( strtolower( $this->conf->php_mb_http_output ) != strtolower( mb_internal_encoding() ) ){
				#	内部エンコードと出力エンコードが異なった場合、
				#	$this->localconf_outputencodingに値をいれ、
				#	出力時に変換を行わせる。
				$this->localconf_outputencoding = $this->conf->php_mb_http_output;
			}
			@ini_set( 'mbstring.http_output' , $this->conf->php_mb_http_output );
		}
		return	true;
	}


	#----------------------------------------------------------------------------
	#	出力ソースのエンコーディングを取得する
	function get_output_encoding(){
		if( strlen( $this->localconf_outputencoding ) ){
			#	出力ソースに指定があればその値を返す。
			return	$this->localconf_outputencoding;
		}
		#	指定がなければ、出力は内部エンコードになるため、
		#	mb_internal_encoding()値を返す。
		return	mb_internal_encoding();
	}


	#--------------------------------------
	#	メニューリストを取得する
	function get_menulist( $type = 'global' ){
		#	Pickles Framework 0.1.0 追加。
		$type = strtolower( $type );
		switch( $type ){
			case 'global':
			case 'shoulder':
				$TMP = eval( 'return $this->menulist_'.$type.';' );
				if( is_array( $TMP ) ){
					$RTN = array();
					foreach( $TMP as $TMPLine ){
						if( !$this->site->is_visiblepage( $TMPLine ) ){ continue; }//Pickles Framework 0.5.2 : 評価ロジック追加
						array_push( $RTN , $TMPLine );
					}
					return	$RTN;
					break;
				}
			default:
				return	array();
				break;
		}
		return	false;
	}

	#----------------------------------------------------------------------------
	#	プロジェクトに属する諸情報を表示する
	function projectinfo(){
		$classFilePath = '/resources/picklesinfo/configcheck.php';
		$className = $this->dbh->require_lib( $classFilePath );
		if( !$className ){
			$this->errors->error_log( 'FAILD to load class ['.$classFilePath.']' , __FILE__ , __LINE__ );
			return	'<p class="ttr error">FAILD to load class ['.htmlspecialchars( $classFilePath ).'].</p>';
		}
		$obj = new $className( &$this->conf );
		$RTN = '';
		ob_start();
		$obj->configcheck();
		$RTN .= ob_get_clean();

		$RTN = preg_replace( '/^.*?<body.*?>(.*)$/is' , '\1' , $RTN );
		$RTN = preg_replace( '/^(.*)<\/body>.*?$/is' , '\1' , $RTN );

		return	$RTN;
	}

	#----------------------------------------------------------------------------
	#	スクリプトを終了する。
	function print_and_exit( $src = null , $option = array() ){
		$option = $this->parseoption( $option );
		if( is_null( $src ) ){
			$src = $this->htmltemplate( $this->getsrc() );
		}

		if( strlen( $this->localconf_outputencoding ) ){
			#	出力時のエンコーディングが指定されていたら、
			#	エンコードする。
			$src = text::convert_encoding( $src , $this->localconf_outputencoding );
			@header( 'Content-type: '.$this->default_contenttype.'; charset='.$this->localconf_outputencoding );
		}

		if( count( $this->relatedlink ) ){
			#	PxFW 0.7.2 追加
			#	追加のファイルパスをパブリッシュツールに知らせる。
			#	カンマ区切りで複数指定される場合がある。
			@header( 'X-PXFW-RELATEDLINK: '.implode( ',' , $this->relatedlink ) );
		}

		if( $this->conf->debug_mode && $this->conf->debug_mode_print_exec_microtime ){
			#	マイクロ秒の出力
			$microtimeSRC = '<div style="color:#dddddd; background-color:#999999; padding:3px;">debug_mode: time: '.( time::microtime() - $this->conf->microtime ).'</div>';
				//Pickles Framework 0.4.8 : <div>タグで囲うようにした。(バリデータ対策)
				//PxFW 0.6.5 : 着色して見た目を整形。デバッグモードであることを表示するようになった。
			$src = preg_replace( '/('.preg_quote( '</body>' , '/' ).')/i' , $microtimeSRC.'$1' , $src );
		}
		print	$src;

		#	アクセスログ保存
		$this->req->save_accesslog( &$this->dbh , &$this->user );

		$this->dbh->close_all();
		exit;
	}

}

?>