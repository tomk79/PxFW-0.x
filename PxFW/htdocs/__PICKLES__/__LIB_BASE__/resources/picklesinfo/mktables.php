<?php

#	Copyright (C)Tomoya Koyanagi.
#	Last Update : 15:38 2010/07/21

#******************************************************************************************************************
#	ユーザ情報テーブルを作成する。
#	Pickles Framework 0.3.1 追加
class base_resources_picklesinfo_mktables{

	#	このクラスは、base_resources_user_usereditor::create_user_tables() を実行し、
	#	PicklesFramework標準のユーザ情報テーブル群を作成します。

	var $conf;

	#----------------------------------------------------------------------------
	#	コンストラクタ
	function base_resources_picklesinfo_mktables( &$conf ){
		$this->conf = &$conf;
	}


	#----------------------------------------------------------------------------
	#	処理の実行開始
	function execute(){
		if( strlen( $this->conf->php_mb_internal_encoding ) ){
			#	内部エンコード調整
			mb_internal_encoding( $this->conf->php_mb_internal_encoding );
		}

		while( @ob_end_clean() );
		@header( 'Content-type: text/plain; charset='.mb_internal_encoding() );

		print	'----------------------------------------'."\n";
		print	'---- PICKLESINFO Service: mktables ----'."\n";
		print	''."\n";

		print	'BaseLayer:    '.$this->conf->path_lib_base."\n";
		if( !@is_dir( $this->conf->path_lib_base ) ){
			print	'[ERROR] Directory NOT Exists.'."\n";
			return	false;
		}
		print	'PackageLayer: '.$this->conf->path_lib_package."\n";
		if( !@is_dir( $this->conf->path_lib_package ) ){
			print	'[ERROR] Directory NOT Exists.'."\n";
			return	false;
		}
		print	'ProjectLayer: '.$this->conf->path_lib_project."\n";
		if( !@is_dir( $this->conf->path_lib_project ) ){
			print	'[ERROR] Directory NOT Exists.'."\n";
			return	false;
		}
		print	'---------------------------------------'."\n";

		$conf = &$this->conf;

		#--------------------------------------
		#	コンフィグ値を確認、調整する
		$this->check_and_adjust_config( &$conf );

		#--------------------------------------
		#	PHPを調整
		$this->phpsetting( &$conf );

		#----------------------------------------------------------------------------
		#	オブジェクト生成のクラスを作成
		$createObj = &$this->create_object_resources_createObjects( &$conf );

		#--------------------------------------
		#	スタティッククラスをロード
		$createObj->load_static_classes( &$conf );
		print	'static classes is loaded.'."\n";

		#--------------------------------------
		#	【インスタンス生成】エラー文字列
		$errors = &$createObj->create_object_lib_errors( &$conf );
		$errors->setup( &$conf );
		print	'$errors is created.'."\n";

		#--------------------------------------
		#	【インスタンス生成】データベースアクセス
		$dbh = &$createObj->create_object_lib_dbh( &$conf );
		$dbh->setup( &$conf , &$errors );
		print	'$dbh is created.'."\n";

		#	DBエラーのコールバック関数を設定
		$func_print_error = create_function( '$msg , $FILE=null , $LINE=null' , 'print trim($msg)."\\n"; flush();' );
		$dbh->set_eventhdl_connection_error( $func_print_error );
		$dbh->set_eventhdl_query_error( $func_print_error );

		#--------------------------------------
		#	【インスタンス生成】リクエスト解析
		$req = &$createObj->create_object_lib_req( &$conf );
		$req->setup( &$conf );
		$req->session_start( $req->in( $conf->session_key ) );
			#	セッションを開始
		print	'$req is created.'."\n";

		#--------------------------------------
		#	【インスタンス生成】ユーザ情報
		$user = &$createObj->create_object_lib_user( &$conf );
		$user->setup( &$conf , &$req , &$dbh , &$errors );
		$user->parse_useragent();
			#	ユーザエージェント情報を解析
		$user->setlanguage();
			#	デフォルト言語を設定
			#	$confに記載がある場合は、その設定を有効に、
			#	ない場合は、jaをデフォルトにする。
		print	'$user is created.'."\n";
		print	''."\n";

		print	'******--------------------------------------'."\n";
		print	'** create user tables....'."\n";
		if( !$this->mk_user_tables( &$conf , &$dbh , &$user , &$errors ) ){
			return	$this->quit( &$dbh , false );
		}
		print	'** / create user tables: done.'."\n";
		print	'******--------------------------------------'."\n";
		print	''."\n";

		print	'******--------------------------------------'."\n";
		print	'** create common project tables....'."\n";
		if( !$this->mk_common_project_tables( &$conf , &$dbh , &$errors ) ){
			return	$this->quit( &$dbh , false );
		}
		print	'** / create common project tables: done.'."\n";
		print	'******--------------------------------------'."\n";
		print	''."\n";
		print	''."\n";

		return	$this->quit( &$dbh , true );
	}
	function quit( &$dbh , $result = true ){
		$result = (bool)$result;
		if( $dbh->close_all() ){
			print	'$dbh closed all.'."\n";
		}else{
			print	'$dbh closed all - FAILED.'."\n";
		}
		print	''."\n";

		if( $result ){
			print	'Operation done.'."\n";
		}else{
			print	'Operation FAILED.'."\n";
		}

		print	'bye!'."\n";
		print	''."\n";
		print	'----  / PICKLESINFO Service: mktables --'."\n";
		print	'----------------------------------------'."\n";
		print	"\n";

		return	$result;
	}


	#--------------------------------------
	#	ユーザ情報テーブルを作成する
	function mk_user_tables( &$conf , &$dbh , &$user , &$errors ){
		#--------------------------------------
		#	usereditorを生成
		$className = $dbh->require_lib('/resources/user/usereditor.php');
		if( !$className ){
			print 'disable to load class [usereditor].'."\n";
			return	false;
		}
		$usereditor = new $className( &$conf , &$dbh , &$user , &$errors );
		print	'$usereditor is created.'."\n";

		print	'execute $usereditor->create_user_tables();'."\n";
		if( !$usereditor->create_user_tables() ){
			print 'FAILD to create_user_tables();'."\n";
			return	false;
		}
		print	'done.'."\n";
		return	true;
	}

	#--------------------------------------
	#	そのほか、プロジェクトに関連するテーブルの作成
	function mk_common_project_tables( &$conf , &$dbh , &$errors ){
		#	拡張して使用してください。
		print	'no program.'."\n";
		return	true;
	}





	#----------------------------------------------------------------------------
	#	オブジェクト作成オブジェクト(いわゆるfactory)を作成して返す。
	function &create_object_resources_createObjects( &$conf ){
		if( @is_file( $conf->path_lib_project.'/resources/createObjects.php' ) ){
			include_once( $conf->path_lib_project.'/resources/createObjects.php' );
			if( class_exists( 'project_resources_createObjects' ) ){
				$obj = new project_resources_createObjects();
				return	$obj;
			}
		}
		if( @is_file( $conf->path_lib_package.'/resources/createObjects.php' ) ){
			include_once( $conf->path_lib_package.'/resources/createObjects.php' );
			if( class_exists( 'package_resources_createObjects' ) ){
				$obj = new package_resources_createObjects();
				return	$obj;
			}
		}
		if( @is_file( $conf->path_lib_base.'/resources/createObjects.php' ) ){
			include_once( $conf->path_lib_base.'/resources/createObjects.php' );
			if( class_exists( 'base_resources_createObjects' ) ){
				$obj = new base_resources_createObjects();
				return	$obj;
			}
		}
		return	false;
	}


	#----------------------------------------------------------------------------
	#	コンフィグ値を確認、調整する
	function check_and_adjust_config( &$conf ){
		#--------------------------------------
		#	時間調整

		$conf->time = time();

		$conf->T1 = $conf->time;//MEMO: この値は将来的に廃止とし、$conf->timeに置き換えたい。
		list( $microtime , $time ) = explode( ' ' , microtime() ); 
		$conf->microtime = ( floatval( $time ) + floatval( $microtime ) );
		unset( $microtime , $time );

		#--------------------------------------
		#	パスの調整(絶対パス変換)
		$conf->path_root = $this->realpath( $conf->path_root );
			if( !@is_dir( $conf->path_root ) ){ print 'Error: Check config value at $path_root';exit; }
		$conf->path_docroot = $this->realpath( $conf->path_docroot );
			if( !@is_dir( $conf->path_docroot ) ){ print 'Error: Check config value at $path_docroot';exit; }
		$conf->path_lib_base = $this->realpath( $conf->path_lib_base );
			if( $conf->path_lib_base.'/resources/picklesinfo/mktables.php' != $this->realpath( __FILE__ ) ){ print 'Error: Check config value at $path_lib_base';exit; }
		$conf->path_lib_package = $this->realpath( $conf->path_lib_package );
			if( !@is_dir( $conf->path_lib_package ) ){ print 'Error: Check config value at $path_lib_package';exit; }
		$conf->path_lib_project = $this->realpath( $conf->path_lib_project );
			if( !@is_dir( $conf->path_lib_project ) ){ print 'Error: Check config value at $path_lib_project';exit; }
		$conf->path_projectroot = $this->realpath( $conf->path_projectroot );
			if( !@is_dir( $conf->path_projectroot ) ){ print 'Error: Check config value at $path_projectroot';exit; }
		$conf->path_contents_dir = $this->realpath( $conf->path_contents_dir );
			if( !@is_dir( $conf->path_contents_dir ) ){ print 'Error: Check config value at $path_contents_dir';exit; }
		$conf->path_sitemap_dir = $this->realpath( $conf->path_sitemap_dir );
			if( !@is_dir( $conf->path_sitemap_dir ) ){ print 'Error: Check config value at $path_sitemap_dir';exit; }
		$conf->path_romdata_dir = $this->realpath( $conf->path_romdata_dir );
			if( !@is_dir( $conf->path_romdata_dir ) ){ print 'Error: Check config value at $path_romdata_dir';exit; }
		$conf->path_ramdata_dir = $this->realpath( $conf->path_ramdata_dir );
			if( !@is_dir( $conf->path_ramdata_dir ) ){ print 'Error: Check config value at $path_ramdata_dir';exit; }
		$conf->path_theme_collection_dir = $this->realpath( $conf->path_theme_collection_dir );
			if( !@is_dir( $conf->path_theme_collection_dir ) ){ print 'Error: Check config value at $path_theme_collection_dir';exit; }
		$conf->path_system_dir = $this->realpath( $conf->path_system_dir );
			if( !@is_dir( $conf->path_system_dir ) ){ print 'Error: Check config value at $path_system_dir';exit; }
		$conf->path_cache_dir = $this->realpath( $conf->path_cache_dir );
			if( !@is_dir( $conf->path_cache_dir ) ){ print 'Error: Check config value at $path_cache_dir';exit; }
		$conf->path_userdir = $this->realpath( $conf->path_userdir );
			if( !@is_dir( $conf->path_userdir ) ){ print 'Error: Check config value at $path_userdir';exit; }
		$conf->path_common_log_dir = $this->realpath( $conf->path_common_log_dir );
		$conf->errors_log_path = $this->realpath( $conf->errors_log_path );
		$conf->access_log_path = $this->realpath( $conf->access_log_path );

		#	スラ止めすることにした。 PxFW 0.6.7 仕様変更。
		$slash = '/';
//		if( realpath('/') != '/' ){ $slash = '\\';/*←Windows*/ }
		$conf->path_root                   .= $slash;
		$conf->path_docroot                .= $slash;
		$conf->path_lib_base               .= $slash;
		$conf->path_lib_package            .= $slash;
		$conf->path_lib_project            .= $slash;
		$conf->path_projectroot            .= $slash;
		$conf->path_contents_dir           .= $slash;
		$conf->path_sitemap_dir            .= $slash;
		$conf->path_romdata_dir            .= $slash;
		$conf->path_ramdata_dir            .= $slash;
		$conf->path_theme_collection_dir   .= $slash;
		$conf->path_system_dir             .= $slash;
		$conf->path_cache_dir              .= $slash;
		$conf->path_userdir                .= $slash;
		if( strlen( $conf->path_common_log_dir ) ){ $conf->path_common_log_dir .= $slash; }
		if( strlen( $conf->errors_log_path     ) ){ $conf->errors_log_path     .= $slash; }
		if( strlen( $conf->access_log_path     ) ){ $conf->access_log_path     .= $slash; }

		if( strlen( $conf->url_action ) && !preg_match( '/^\\//' , $conf->url_action ) ){
			trigger_error('設定値「url_action」は、「/」から始まらなくてはいけません。');
		}
		if( strlen( $conf->url_root ) && !preg_match( '/^\\//' , $conf->url_root ) ){
			trigger_error('設定値「url_root」は、「/」から始まらなくてはいけません。');
		}

		return	true;
	}


	#----------------------------------------------------------------------------
	#	PHPを設定する
	function phpsetting( &$conf ){
		if( !extension_loaded( 'mbstring' ) ){
			trigger_error('mbstringがロードされていません。');
		}

		if( $conf->php_default_charset == 'no value' ){
			@ini_set( 'default_charset' , null );
		}elseif( strlen( $conf->php_default_charset ) ){
			@ini_set( 'default_charset' , $conf->php_default_charset );
		}
		if( strlen( $conf->php_mb_internal_encoding ) ){
			@ini_set( 'mbstring.internal_encoding' , $conf->php_mb_internal_encoding );
		}
		if( strlen( $conf->php_mb_http_input ) ){
			@ini_set( 'mbstring.http_input' , $conf->php_mb_http_input );
		}
		if( strlen( $conf->php_mb_http_output ) ){
			@ini_set( 'mbstring.http_output' , $conf->php_mb_http_output );
		}

		#--------------------------------------
		#	ドキュメントルートへカレントディレクトリを移動する。
		chdir( $conf->path_docroot );

		return	true;
	}

	#--------------------------------------
	#	realpath()のラッパ
	function realpath( $path ){
		#	PicklesFramework 0.2.2 追加
		#	realpath()の動作を、
		#	WindowsでもUNIX系と同じスラッシュ区切りのパスで得る。
		$path = @realpath($path);
		if( !is_string( $path ) ){
			#	string型じゃなかったら（つまり、falseだったら）
			return	$path;
		}
		if( strpos( $path , '/' ) !== 0 ){
			#	Windowsだったら。
			$path = preg_replace( '/^[A-Z]:/' , '' , $path );
			$path = preg_replace( '/\\\\/' , '/' , $path );
		}
		return	$path;
	}

}

?>