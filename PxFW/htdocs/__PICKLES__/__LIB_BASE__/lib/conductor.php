<?php

#	Copyright (C)Tomoya Koyanagi.
#	LastUpdate : 13:20 2011/06/17

#******************************************************************************************************************
#	conductorを作るための基底クラス
class base_lib_conductor{

	#----------------------------------------------------------------------------
	#	★★ セットアップ ★★
	function setup( &$conf ){

		#--------------------------------------
		#	PICKLESINFOコマンドを調べる
		$PICKLESINFO_COMMAND = $this->get_picklesinfo_string( &$conf );

		#--------------------------------------
		#	PICKLESINFOの処理
		if( $PICKLESINFO_COMMAND == 'configcheck' || $conf->system_exec_mode == 'setup' ){
			$this->exec_picklesinfo( &$conf , 'configcheck' );exit;
		}
		if( $PICKLESINFO_COMMAND == 'libfill' ){
			#	PICKLESINFOサービス：libfill を実行
			#	Pickles Framework 0.2.0 追加
			$this->exec_picklesinfo( &$conf , $PICKLESINFO_COMMAND );exit;
		}
		if( $PICKLESINFO_COMMAND == 'clearcache' ){
			#	PICKLESINFOサービス：libfill を実行
			#	Pickles Framework 0.2.0 追加
			$this->exec_picklesinfo( &$conf , $PICKLESINFO_COMMAND );exit;
		}
		if( $PICKLESINFO_COMMAND == 'mktables' ){
			#	PICKLESINFOサービス：mktables を実行
			#	Pickles Framework 0.3.1 追加
			$this->exec_picklesinfo( &$conf , $PICKLESINFO_COMMAND );exit;
		}
		#	/ PICKLESINFOの処理
		#--------------------------------------


		#--------------------------------------
		#	コンフィグ値を確認、調整する
		$this->check_and_adjust_config( &$conf );

		#--------------------------------------
		#	PHPを調整
		$this->phpsetting( &$conf );

		#--------------------------------------
		#	PICKLESINFOの処理
		if( $PICKLESINFO_COMMAND == 'phpinfo' ){
			#	PICKLESINFOサービス：phpinfo を実行
			#	Pickles Framework 0.6.7 追加
			phpinfo();exit;
		}
		#	/ PICKLESINFOの処理
		#--------------------------------------

		#--------------------------------------
		#	メンテナンスモードだったら、即画面を出す。
		if( $conf->system_exec_mode == 'maintenance' ){
			#	メンテナンスモードの画面を表示して、exit;
			#	ただし、メンテナンスモードの場合にここで必ず終了したくない場合を考慮して、
			#	この行では明示的にexit文は発行しない仕様です。
			$this->system_exec_mode_maintenance( &$conf );

		}

		#----------------------------------------------------------------------------
		#	オブジェクト生成のクラスを作成
		$createObj = &$this->create_object_resources_createobjects( &$conf );

		#--------------------------------------
		#	スタティッククラスをロード
		$createObj->load_static_classes( &$conf );

		#--------------------------------------
		#	【インスタンス生成】エラー文字列
		$errors = &$createObj->create_object_lib_errors( &$conf );
		$errors->setup( &$conf );

		#--------------------------------------
		#	【インスタンス生成】データベースアクセス
		$dbh = &$createObj->create_object_lib_dbh( &$conf );
		$dbh->setup( &$conf , &$errors );

		#--------------------------------------
		#	PICKLESINFOの処理
		if( $PICKLESINFO_COMMAND == 'themes' ){
			#	PICKLESINFOサービス：themes を実行 : PxFW 0.6.7 追加
			$className = $dbh->require_lib( '/resources/picklesinfo/'.$PICKLESINFO_COMMAND.'.php' );
			if( $className ){
				$obj = new $className( &$conf , &$dbh );
				$obj->execute();
			}
			$dbh->close_all();
			exit;
		}
		#	/ PICKLESINFOの処理
		#--------------------------------------

		#--------------------------------------
		#	【インスタンス生成】リクエスト解析
		$req = &$createObj->create_object_lib_req( &$conf );
		$req->setup( &$conf );
		$req->session_start( $req->in( $conf->session_key ) );
			#	セッションを開始

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

		#--------------------------------------
		#	【インスタンス生成】サイト情報
		$site = &$createObj->create_object_lib_site( &$conf );
		$site->setup( &$conf , &$dbh , &$errors , &$req );//PxFW 0.6.4 $req を渡すようになった。

		#--------------------------------------
		#	PICKLESINFOの処理
		if( $PICKLESINFO_COMMAND == 'sitemapdefinition' ){
			#	PICKLESINFOサービス：sitemapdefinition を実行 : PxFW 0.5.10 追加
			$className = $dbh->require_lib( '/resources/picklesinfo/'.$PICKLESINFO_COMMAND.'.php' );
			if( $className ){
				$obj = new $className( &$conf );
				$obj->execute( &$site );
			}
			$dbh->close_all();
			exit;
		}
		#	/ PICKLESINFOの処理
		#--------------------------------------

		#--------------------------------------
		#	クエリにlang,CTが指定されていたら、そこだけ書き換え
		if( strlen( $req->in('CT') ) ){
			$user->setct( $req->in('CT') );
			$req->addgene( 'CT' , $req->in('CT') );
		}
		if( strlen( $req->in('LANG') ) ){
			#	ここで初めて自然言語を決定
			$user->setlanguage( $req->in('LANG') );
			$req->addgene( 'LANG' , $req->in('LANG') );
		}
		if( strlen( $req->in('THEME') ) ){
			$user->settheme( $req->in('THEME') );
			if( ( $req->in('THEME') == $user->gettheme() && $user->gettheme() != $conf->default_theme_id ) || is_null( $user->gettheme() ) ){
				#	PicklesFramework 0.3.7 追加
				#	・テーマIDが有効なとき以外は引き継がない。
				#	・採用されたテーマIDがデフォルトの場合は引き継がない。
				#	・テーマID null が採用された場合は、引き継ぐ。
				#	　$user->gettheme() が null を返すのは、
				#	　テーマID null が採用された場合のみ。
				$req->addgene( 'THEME' , $req->in('THEME') );
			}
		}
		if( strlen( $req->in('OUTLINE') ) ){
			$req->addgene( 'OUTLINE' , $req->in('OUTLINE') );
		}
		if( strlen( $PICKLESINFO_COMMAND ) && strlen( $req->in('PICKLESINFO') ) ){
			#	Pickles Framework 0.2.1 追加の処理
			$req->addgene( 'PICKLESINFO' , $req->in('PICKLESINFO') );
		}

		#--------------------------------------
		#	サイトマップを作成する。
		ignore_user_abort(true);//←PxFramework 0.6.11
		$site->setsitemap( $user->getlanguage() );
		ignore_user_abort(false);//←PxFramework 0.6.11
		$req->parse_p();
		$req->parse_pv( &$site );

		#	PICKLESINFOの処理
		if( $PICKLESINFO_COMMAND == 'sitemap' ){
			#	PICKLESINFOサービス：sitemap を実行 : PxFW 0.6.7 追加
			$className = $dbh->require_lib( '/resources/picklesinfo/'.$PICKLESINFO_COMMAND.'.php' );
			if( $className ){
				$obj = new $className( &$conf , &$req , &$site );
				$obj->execute();
			}
			$dbh->close_all();
			exit;
		}elseif( $PICKLESINFO_COMMAND == 'pageinfo' ){
			#	PICKLESINFOサービス：pageinfo を実行 : PxFW 0.6.7 追加
			$className = $dbh->require_lib( '/resources/picklesinfo/'.$PICKLESINFO_COMMAND.'.php' );
			if( $className ){
				$obj = new $className( &$conf , &$req , &$dbh , &$site );
				$obj->execute();
			}
			$dbh->close_all();
			exit;
		}elseif( $PICKLESINFO_COMMAND == 'themeinfo' ){
			#	PICKLESINFOサービス：themeinfo を実行 : PxFW 0.6.7 追加
			$className = $dbh->require_lib( '/resources/picklesinfo/'.$PICKLESINFO_COMMAND.'.php' );
			if( $className ){
				$obj = new $className( &$conf , &$dbh , &$user );
				$obj->execute();
			}
			$dbh->close_all();
			exit;
		}
		#	/ PICKLESINFOの処理

		#--------------------------------------
		#	ログイン
		$ttl = null;#(TRY_TO_LOGIN)
		if( empty( $conf->try_to_login ) ){
			$ttl = 1;
		}elseif( $req->in( $conf->try_to_login ) ){
			$ttl = 1;
		}else{
			$ttl = 0;
		}
		$user->login( $ttl );

		if( $user->is_login() ){
			$user->save_t_lastlogin( time() );//最後にログインした時刻を記録
		}

		if( $user->get_enduserclass() == 'human' && !$user->is_enable_cookie() && ( ( $conf->allow_login_without_cookies && $user->is_login() ) || ( $conf->allow_login_without_cookies && $conf->session_always_addgene_if_without_cookies ) ) ){
			#	常にセッションIDを持ちまわる設定の反映。
			$req->addgene( $conf->session_key , $req->getsessionid() );
		}
		#	/ ログイン
		#--------------------------------------

		#--------------------------------------
		#	【インスタンス生成】追加設定オブジェクト
		$custom = &$createObj->create_object_lib_additional_objects( &$conf , &$req , &$dbh , &$user , &$site , &$errors );
			//	Pickles Framework 0.3.4 以降、$theme よりも先に生成されるように仕様変更。

		#--------------------------------------
		#	【インスタンス生成】デザインテーマ
		$theme = &$createObj->create_object_lib_theme( &$conf , &$req , &$user );
		$theme->setup( &$conf , &$req , &$dbh , &$user , &$site , &$errors , &$custom );

		#	$theme を $custom に持たせる。PxFW 0.6.8 追加
		$custom->set_theme( &$theme );

		#	DBエラーのコールバック関数を設定
		$dbh->set_eventhdl_connection_error( array( &$theme , 'fatalerrorend' ) );
		$dbh->set_eventhdl_query_error( array( &$theme , 'fatalerrorend' ) );//Pickles Framework 0.1.11 errorend() から fatalerrorend() に変更


		#	追加の処理を施す。
		$this->setup_additional( &$conf , &$req , &$dbh , &$user , &$site , &$errors , &$theme , &$custom );


		if( $ttl && strlen( $req->in('ID') ) && strlen( $req->in('PW') ) && $user->is_login() ){
			#	ログインに成功した直後の場合、
			#	同じ画面(ページID)にリダイレクトする。
			#	Pickles Framework 0.4.3 で追加されました。
			#	PxFW 0.6.4 : $user->is_login() を条件に加えた。
			@header( 'Location: '.$theme->href( $req->p() ) );
				#	↑リダイレクトが使えない端末だったら、あきらめる。
		}
		unset( $ttl );


		#	アプリケーションを開始
		return	$this->appstart( &$conf , &$req , &$dbh , &$user , &$site , &$errors , &$theme , &$custom );
	}

	#----------------------------------------------------------------------------
	#	コンフィグ値を確認、調整する
	function check_and_adjust_config( &$conf ){
		#--------------------------------------
		#	時間調整

		$conf->time = time();

		$PICKLESINFO_COMMAND = $this->get_picklesinfo_string( &$conf );	#	PICKLESINFOコマンドを調べる
		if( strlen( $PICKLESINFO_COMMAND ) ){
			if( preg_match( '/^now([0-9]+)\.([0-9]+)\.([0-9]+)(?:\_([0-9]+)(?:\.([0-9]+)(?:\.([0-9]+))?)?)?$/' , $PICKLESINFO_COMMAND , $preg_result ) ){
				#	日付を偽装する
				if( !strlen( $preg_result[4] ) ){ $preg_result[4] = date( 'H' ); }
				if( !strlen( $preg_result[5] ) ){ $preg_result[5] = date( 'i' ); }
				if( !strlen( $preg_result[6] ) ){ $preg_result[6] = date( 's' ); }
				$conf->time = mktime(
					intval( $preg_result[4] ) ,
					intval( $preg_result[5] ) ,
					intval( $preg_result[6] ) ,
					intval( $preg_result[2] ) ,
					intval( $preg_result[3] ) ,
					intval( $preg_result[1] )
				);
			}
		}

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
			if( $conf->path_lib_base.'/lib/conductor.php' != $this->realpath( __FILE__ ) ){ print 'Error: Check config value at $path_lib_base';exit; }
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
		if( is_callable('mb_detect_order') ){
			//PxFW 0.6.7 追加
			@ini_set( 'mbstring.detect_order' , 'UTF-8,SJIS-win,eucJP-win,SJIS,EUC-JP,JIS,ASCII' );
			@mb_detect_order( 'UTF-8,SJIS-win,eucJP-win,SJIS,EUC-JP,JIS,ASCII' );
		}

		#--------------------------------------
		#	ドキュメントルートへカレントディレクトリを移動する。
		chdir( realpath( $conf->path_docroot ) );

		return	true;
	}


	#----------------------------------------------------------------------------
	#	オブジェクト作成オブジェクト(いわゆるfactory)を作成して返す。
	function &create_object_resources_createobjects( &$conf ){
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
	#	コンテンツオペレータオブジェクトを作成して返す
	function &create_object_contentsoperator( $execute_type , &$conf , &$req , &$dbh , &$user , &$site , &$errors , &$theme , &$custom ){
		//PxFW 0.6.7 : プロジェクト層にライブラリが存在する必要がなくなった。
		$className = $dbh->require_lib( '/resources/contentsoperator/'.$execute_type.'.php' );
		if( !$className ){ return false; }
		$parser = new $className( &$conf , &$req , &$dbh , &$user , &$site , &$errors , &$theme , &$custom , $execute_type );
			//↑PxFW 0.7.2 引数に $execute_type を追加。
		return	$parser;
	}

	#----------------------------------------------------------------------------
	#	サイトマップのファイルパスのリストを返す
	function setup_sitemap_filelist( &$req , &$dbh ){
		#	Pickles Framework 0.4.0 廃止
		return	false;
	}


	#----------------------------------------------------------------------------
	#	その他追加のセットアップ事項があれば記述
	function setup_additional( &$conf , &$req , &$dbh , &$user , &$site , &$errors , &$theme , &$custom ){
		return	null;
	}
	#	/ ★★ セットアップ ★★
	#----------------------------------------------------------------------------






	#----------------------------------------------------------------------------
	#	★★アプリケーションはここから始まる★★
	function appstart( &$conf , &$req , &$dbh , &$user , &$site , &$errors , &$theme , &$custom ){

		#	IP制限のチェック
		if( !$this->check_allow_client_ip( $conf->allow_client_ip ) ){
			#	許可されたIPアドレスじゃなかったら、forbidden.
			return	$theme->printforbidden();
		}

		#	外部リンクのリダイレクト
		if( strlen( $req->in('EXTERNALURL') ) && $conf->enable_externalurl ){//PxFW 0.6.10 : 新しい設定項目 $conf->enable_externalurl を参照するようになった。
			if( !strlen( $_SERVER['HTTP_REFERER'] ) || preg_match( '/^https?:\/\/'.preg_quote($_SERVER['SERVER_NAME'],'/').'\//si' , $_SERVER['HTTP_REFERER'] ) ){
				return	$this->jump2outersite( $req->in('EXTERNALURL') , &$site , &$theme );
			}
		}

		$my_contdir = $this->realpath( $dbh->getpath_contents( '' ) );

		if( $req->pelm() == 'logout' ){
			#	ログアウトする場合
			return	$theme->logout();
		}
		if( $theme->is_closed() && !$user->is_login() ){
			#	ログインが必要な場合、ログインチェック
			return	$theme->pleaselogin();
		}
		if( $theme->getsecuritylevel() > $user->getauthlevel() ){
			#	権限が満たされているか確認し、
			#	満たされていなければ、Forbidden
			return	$theme->printforbidden();
		}

		if( is_array( $conf->out_of_servicetime ) && count( $conf->out_of_servicetime ) ){
			#	アクセス禁止時間帯の評価
			#	Pickles Framework 0.5.1 : NotFoundより先に処理するようにした。
			foreach( $conf->out_of_servicetime as $out_of_servicetime ){
				if( time::is_ontime( $out_of_servicetime , $conf->time ) ){
					return	$theme->out_of_servicetime();
				}
			}
		}

		$cancel_notfound = false;
		if( $conf->allow_flush_content_without_pages && !@array_key_exists( $_SERVER['PATH_INFO'] , $req->urlmap ) ){
			#	PxFW 0.7.0 : allow_flush_content_without_pages の処理を追加。
			#	コンテンツが存在したら、ページがなくても出力するようにする。
			$tmp_contpath = realpath( $conf->path_contents_dir.$_SERVER['PATH_INFO'] );
			if( @is_file( $tmp_contpath ) ){
				if( !preg_match( '/\.items/' , $_SERVER['PATH_INFO'] ) ){
					$cancel_notfound = true;
					$site->setpageinfo( $req->po() , 'srcpath' , $_SERVER['PATH_INFO'] );
					$tmp_ext = strtolower( preg_replace( '/^.*\.(.+?)$/ism' , "$1" , $_SERVER['PATH_INFO'] ) );
					if( $dbh->require_lib( '/resources/contentsoperator/'.$tmp_ext.'.php' ) || $tmp_ext == 'php' ){
						$site->setpageinfo( $req->po() , 'exetype' , $tmp_ext );
					}else{
						$site->setpageinfo( $req->po() , 'exetype' , 'download' );
						$site->setpageinfo( $req->po() , 'title' , '' );
					}
				}
			}
			unset( $tmp_contpath );
		}

		if( $conf->auto_notfound && !$cancel_notfound && !strlen( $site->getpageinfo( $req->po() , 'id' ) ) && strlen( $req->pelm() ) ){
			#	ページがなかったらNotFound
			#	Pickles Framework 0.5.1 以降、$conf->auto_notfound を評価するようになった。
			$site->delpageinfo( $req->p() );
			return	$theme->printnotfound();
		}

		$content_file_path = '';
		$execute_type = 'php';

		$selectedContentInfo = $this->selectcontent( &$conf , &$req , &$dbh , &$site , &$user );
		if( !is_array( $selectedContentInfo ) ){
			@header( 'HTTP/1.1 404 Not Found' );//Pickles Framework 0.5.10 : ステータス 404 を出力するようになった。
			@header( 'Status: 404 Not Found' );
			$msg = 'コンテンツが存在しません。';
			if( $conf->debug_mode ){
				$msg = 'ページID ['.$req->po().'] に対応するコンテンツ ['.$this->getsrcpathname( &$site , $req->po() ).'] が存在しません。';
			}
			return	$theme->errorend( $msg );
		}
		$content_file_path = $selectedContentInfo['path_content'];
		$theme->set_selectcontent_info( $selectedContentInfo );
		$execute_type = $selectedContentInfo['execute_type'];
		unset( $selectedContentInfo );


		if( strlen( $site->getpageinfo( $req->po() , 'exetype' ) ) ){
			#	ページにexetypeの指定がある場合は、
			#	その指定を優先して決定
			$execute_type = $site->getpageinfo( $req->po() , 'exetype' );
		}

		$FIN = '';
		if( strlen( $dbh->require_lib( '/resources/contentsoperator/'.$execute_type.'.php' ) ) ){//PxFW 0.6.7 : プロジェクト層にライブラリが存在する必要がなくなった。
			#--------------------------------------
			#	コンテンツオペレータをロード
			$parser = &$this->create_object_contentsoperator( $execute_type , &$conf , &$req , &$dbh , &$user , &$site , &$errors , &$theme , &$custom );
			$FIN .= $parser->get_contents_result( $content_file_path );

		}elseif( $execute_type == 'php' ){
			#--------------------------------------
			#	通常のPHPコンテンツの処理
			$FIN .= isolated::getrequiredreturn_once( $content_file_path , &$conf , &$req , &$dbh , &$user , &$site , &$errors , &$theme , &$custom , $conf->contents_start_str , $conf->contents_end_str , $conf->enable_contents_preprocessor );
				#	Pickles Framework 0.5.2 : $conf->enable_contents_preprocessor を参照するように変更。

		}elseif( $execute_type == 'direct' ){
			#--------------------------------------
			#	そのまま出力処理
			$FIN .= isolated::getrequiredreturn_static( $content_file_path , &$conf , &$req , &$dbh , &$user , &$site , &$errors , &$theme , &$custom , $conf->contents_start_str , $conf->contents_end_str );

		}elseif( $execute_type == 'download' ){
			#--------------------------------------
			#	ダウンロード処理
			$pathinfo = pathinfo( $content_file_path );
			switch( strtolower( $pathinfo['extension'] ) ){
				case 'jpeg': case 'jpg': case 'jpe': $contenttype = 'image/jpeg';          break;
				case 'png':                          $contenttype = 'image/png';           break;
				case 'gif':                          $contenttype = 'image/gif';           break;
				case 'html': case 'htm':             $contenttype = 'text/html';           break;//PxFW 0.7.0 追加
				case 'css':                          $contenttype = 'text/css';            break;//PxFW 0.7.0 追加
				case 'js':                           $contenttype = 'text/javascript';     break;//PxFW 0.7.0 追加
				case 'txt':                          $contenttype = 'text/plain';          break;//PxFW 0.7.0 追加
				case 'pdf':                          $contenttype = 'application/pdf';     break;//PxFW 0.7.0 追加
				case 'xml':                          $contenttype = 'application/xml';     break;//PxFW 0.7.0 追加
				case 'xslt':                         $contenttype = 'application/xslt+xml';break;//PxFW 0.7.0 追加
				case 'zip':                          $contenttype = 'application/zip';     break;//PxFW 0.7.0 追加
				default:                             $contenttype = 'x-download/download'; break;
			}
			$theme->flush_file( $content_file_path , array( 'content-type'=>$contenttype , 'filename'=>$site->getpageinfo( $req->po() , 'title' ) ) );

			$ERROR = 'コンテンツのダウンロードが正常に行われませんでした。Contents => ['.$content_file_path.']';
			$errors->error_log( $ERROR , __FILE__ , __LINE__ );
			if( $conf->debug_mode ){
				$FIN = '<p class="ttr error">何らかのエラーが発生しました。base_lib_theme::flush_file()が、想定通りの動きをしませんでした。</p>';
				$FIN = '<p class="ttr error">'.htmlspecialchars( $ERROR ).'</p>';
			}else{
				$FIN = '<p class="ttr">ただいま準備中です。しばらくしてからもう一度お試しください。</p>';
			}
			unset( $ERROR );
		}else{
			$ERROR = '想定外のexetypeです。('.$execute_type.')';
			$errors->error_log( $ERROR , __FILE__ , __LINE__ );
			if( $conf->debug_mode ){
				$FIN = '<p class="ttr error">'.htmlspecialchars( $ERROR ).'</p>';
			}else{
				$FIN = '<p class="ttr">ただいま準備中です。しばらくしてからもう一度お試しください。</p>';
			}
			unset( $ERROR );
		}

		$theme->setsrc( $FIN );

		return	$theme->print_and_exit();

	}

	#----------------------------------------------------------------------------
	#	ページに対応するコンテンツ本体の情報を返す。
	function selectcontent( &$conf , &$req , &$dbh , &$site , &$user ){
		$my_contdir = $this->realpath( $dbh->getpath_contents( '' ) );
		$contchilddir = $this->getsrcpathname( &$site , $req->po() );

		$FileName_array = array();	#	探すファイル名の配列
		$basepath_array = array();	#	コンテンツの格納ディレクトリの配列
		$extention_array = array();	#	ファイル名の後ろにつく文字列(@CT、拡張子)の配列

		if( @is_file( $this->realpath( $my_contdir.$contchilddir ) ) ){
			#	$contchilddirにロードされた値が、ファイル名を含む場合
			#	( = パスの示す先にファイルが存在することをもってそう判断する )
			#	　・格納されているディレクトリまでのパス
			#	　・ファイル名
			#	　・拡張子
			#	を分解して取得する。
			$FileName = basename( $this->realpath( $my_contdir.$contchilddir ) );
			$contchilddir = dirname( $contchilddir );
			if( $contchilddir == '/' || $contchilddir == '\\' ){ $contchilddir = ''; }
			$original_extention = preg_replace( '/^.*\./' , '' , $FileName );//もともとの拡張子をメモ(Pickles Framework 0.5.5)
			$FileName = preg_replace( '/\..*?$/' , '' , $FileName );//拡張子を外し、
			$FileName = preg_replace( '/\@.*?$/' , '' , $FileName );//CTも外す。
			array_push( $FileName_array , $FileName );
		}
		$basepath = $this->realpath( $my_contdir );
		$basepath_theme = $conf->path_theme_collection_dir.'/'.$user->gettheme().'/'.$user->get_ct().'/contents';

		#--------------------------------------
		#	プライオリティ順にコンテンツを探す
		array_push( $FileName_array , preg_replace( '/\./si' , '/' , $req->po() ).'/index' );//PxFW 0.7.2 追加
		array_push( $FileName_array , preg_replace( '/\./si' , '/' , $req->po() ) );//PxFW 0.7.2 追加
		array_push( $FileName_array , 'p_'.$req->poelm() );
		$basepath_array = array( $basepath_theme.$contchilddir , $basepath.$contchilddir );	//	テーマコンテンツ、標準コンテンツ、の順

		$contents_extentions = array_merge( array('php.php') , $dbh->getfilelist( $conf->path_lib_project.'/resources/contentsoperator' ) );
		if( strlen( $original_extention ) ){
			array_push( $extention_array , '@'.$user->get_ct().'.'.$original_extention );//もともとの拡張子を復活(Pickles Framework 0.5.5)
		}
		foreach( $contents_extentions as $extention ){
			$extention = text::trimext( $extention );
			array_push( $extention_array , '@'.$user->get_ct().'.'.$extention );
		}
		if( strlen( $original_extention ) ){
			array_push( $extention_array , '.'.$original_extention );//もともとの拡張子を復活(Pickles Framework 0.5.5)
		}
		foreach( $contents_extentions as $extention ){
			$extention = text::trimext( $extention );
			array_push( $extention_array , '.'.$extention );
		}

		if( $contchilddir == '/' ){
			$contchilddir = '';
		}

		foreach( $FileName_array as $FileName_Line ){
			foreach( $basepath_array as $basepath_Line ){
				foreach( $extention_array as $extention_Line ){
					if( @is_file( $basepath_Line.'/'.$FileName_Line.$extention_Line ) ){
						#	コンテンツを見つけたら返す。
						return	array(
							'path_content'=>$this->realpath( $basepath_Line ).'/'.$FileName_Line.$extention_Line,
							'path_items'=>$this->realpath( $basepath_Line ).'/'.$FileName_Line.'.items/',//Pickles Framework 0.4.8 追加
							'localpath_content'=>$contchilddir.'/'.$FileName_Line.$extention_Line,
							'localpath_items'=>$contchilddir.'/'.$FileName_Line.'.items/',//Pickles Framework 0.4.8 追加
							'execute_type'=>preg_replace( '/^.*\.(.+?)$/ism' , "$1" , $extention_Line ),
							'FileName'=>$FileName_Line,
						);
						break 3;
					}
				}
			}
		}
		#	/ プライオリティ順にコンテンツを探す
		#--------------------------------------

		if( @is_file( $my_contdir.$contchilddir ) ){
			#	ページが指示するコンテンツが存在した場合
			return	array(
				'path_content'=>$my_contdir.$contchilddir,
				'localpath_content'=>$contchilddir,
				'execute_type'=>'download',
				'FileName'=>text::trimext( basename( $contchilddir ) ),
			);
		}
		return	false;
	}

	#----------------------------------------------------------------------------
	#	ページが指定する本体がしまわれているフォルダ名を返す。
	function getsrcpathname( &$site , $pid ){
		return	$site->getpageinfo( $pid , 'srcpath' );
	}

	#----------------------------------------------------------------------------
	#	許可されたIPからのアクセスか検証する
	function check_allow_client_ip( $allow_client_ip ){
		if( !is_array( $allow_client_ip ) || !count( $allow_client_ip ) ){
			#	指定がなければもうOK
			return	true;
		}

		#	UTODO : 現状、IP評価に当たり、IPv6とかは意識していない。
		#	これは将来的な課題。
		#	3桁以下の数値がドット区切りで4つ連なっている場合以外は想定していません。

		$client_ip_parts = explode( '.' , $_SERVER['REMOTE_ADDR'] );
		foreach( $allow_client_ip as $ok_ip ){
			if( preg_match( '/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$/is' , $ok_ip ) ){
				#	固定のIP指定なら、そのまま文字列マッチする
				if( $ok_ip == $_SERVER['REMOTE_ADDR'] ){
					#	完全にマッチする場合は、その時点でOK。
					return	true;
				}
				continue;
			}

			$ok_ip_parts = explode( '.' , $ok_ip );
			$matched = 0;
			for( $i = 0; $i < 4; $i++ ){
				if( $ok_ip_parts[$i] == '*' ){
					#	ワイルドカードは全部にマッチ
					$matched ++;
					continue;
				}
				if( preg_match( '/^[0-9]{1,3}$/is' , $ok_ip_parts[$i] ) ){
					#	固定値の評価
					if( $ok_ip_parts[$i] == $client_ip_parts[$i] ){
						$matched ++;
					}
					continue;
				}
				if( preg_match( '/^([0-9]{1,3})-([0-9]{1,3})$/is' , $ok_ip_parts[$i] , $values ) ){
					#	範囲指定の評価
					if( intval( $values[1] ) <= intval( $client_ip_parts[$i] ) && intval( $values[2] ) >= intval( $client_ip_parts[$i] ) ){
						$matched ++;
					}
					continue;
				}
			}
			if( $matched == 4 ){
				return	true;
			}
		}
		return	false;
	}




	#----------------------------------------------------------------------------
	#	外部サイトへ移動する
	function jump2outersite( $linkto , &$site , &$theme ){
		$RTN = '';
		$RTN .= '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">'."\n";
		$RTN .= '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ja">'."\n";
		$RTN .= '	<head>'."\n";
		$RTN .= '		<meta http-equiv="content-type" content="text/html;charset='.mb_internal_encoding().'" />'."\n";
		$RTN .= '		<meta http-equiv="content-script-type" content="text/javascript" />'."\n";
		$RTN .= '		<meta http-equiv="content-style-type" content="text/css" />'."\n";
		$RTN .= '		<meta http-equiv="refresh" content="0;URL='.htmlspecialchars( $linkto ).'">'."\n";
		$RTN .= '		<title>'.htmlspecialchars( $site->gettitle() ).'</title>'."\n";
		$RTN .= '		<style type="text/css">'."\n";
		$RTN .= '			h1,h2,h3,h4,h5,h6{font-size:14px; }'."\n";
		$RTN .= '			ttr,ttrs,ttrss,ttrl,ttrll{font-size:13px; }'."\n";
		$RTN .= '		</style>'."\n";
		$RTN .= '	</head>'."\n";
		$RTN .= '	<body>'."\n";
		$RTN .= '		<h1>'.htmlspecialchars( $site->gettitle() ).'</h1>'."\n";
		$RTN .= '		<p class="ttr">'."\n";
		$RTN .= '			次のURLに移動しています。<br />'."\n";
		$RTN .= '			<a href="'.htmlspecialchars( $linkto ).'">'.htmlspecialchars( $linkto ).'</a><br />'."\n";
		$RTN .= '		</p>'."\n";
		$RTN .= '		<p class="ttr">'."\n";
		$RTN .= '			自動的に画面が切り替わらない場合は、<a href="'.htmlspecialchars( $linkto ).'">ここ</a>をクリックしてください。<br />'."\n";
		$RTN .= '		</p>'."\n";
		$RTN .= '	</body>'."\n";
		$RTN .= '</html>'."\n";
		return $theme->download( $RTN , array( 'content-type'=>'text/html' ) );
	}


	#----------------------------------------------------------------------------
	#	メンテナンス画面を表示する
	function system_exec_mode_maintenance( &$conf ){
		#	コンフィグの system_exec_modeディレクティブ が 'maintenance' に設定されている
		#	場合に、呼び出されるメソッドです。
		#
		#	このメソッドからは、引数に受けた$conf以外のオブジェクトは一切利用できません。
		#	メンテナンス中であることを告げるシンプルなHTMLを出力するようにしてください。
		#	または、PicklesFramework管理外の、例えば静的なHTMLへリダイレクトする場合は、
		#	このメソッドに、そのURLへリダイレクトするheader()を実装してください。
		#	このメソッドが実行された時点では、PicklesFrameworkはまだ何も処理をしていません。
		#	なので、そのままexit;を実行しても問題ありません。
		#	[ ex ]
		#		header( 'Location: http://www.sample.jp/maintenance.html' );
		#		exit;
		#	[ /ex ]
		#
		#	もし、PicklesFrameworkで管理する画面を出力したい場合は、次のようにしてください。
		#	(ただし、下記の方法は推奨されません)
		#		このメソッドを空白で上書き拡張すると、そのまま次の処理を続行します。
		#		各種基本オブジェクトを作成した後、setup_additional()内などで、
		#		メンテナンス中画面の処理を記述します。
		#		こうすることで、ある程度デザインを統一したメンテナンス中画面を表示できますが、
		#		この時点で、各基本オブジェクトが既にいくつかの処理を実行していることに
		#		注意してください。

		print	'<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">'."\n";
		?>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ja">
	<head>
		<meta http-equiv="content-type" content="text/html;charset=<?php print htmlspecialchars( mb_internal_encoding() ); ?>" />
		<title><?php print htmlspecialchars( $conf->info_sitetitle ) ?></title>
		<style type="text/css">
			#logo	{font-weight:bold; font-size:large; border-bottom:1px solid #999999; }
			h1		{font-weight:bold; font-size:medium; background-color:#f6f6f6; padding:5px; border-left:3px solid #666666; }
			p,div	{font-weight:normal; font-size:x-small; }
		</style>
	</head>
	<body>
		<div id="logo"><?php print htmlspecialchars( $conf->info_sitetitle ) ?></div>
		<h1>只今メンテナンス中です。</h1>
		<p>
			ご迷惑をお掛けしてしまい、誠に申し訳ございません。<br />
			メンテナンス作業終了までの間、しばらくお待ちください。<br />
		</p>
	</body>
</html>
<?php
		exit;	//ここでスクリプトを終了。
	}

	#--------------------------------------
	#	Pickles Info サービス：picklesinfo_nameを実行
	function exec_picklesinfo( &$conf , $picklesinfo_name ){
		#	15:00 2007/11/30
		#	Pickles Framework 0.2.0 追加
		while( @ob_end_clean() );

		$obj = null;
		if( @is_file( $conf->path_lib_project.'/resources/picklesinfo/'.$picklesinfo_name.'.php' ) ){
			require_once( $conf->path_lib_project.'/resources/picklesinfo/'.$picklesinfo_name.'.php' );
			$className = 'project_resources_picklesinfo_'.$picklesinfo_name;
		}elseif( @is_file( $conf->path_lib_package.'/resources/picklesinfo/'.$picklesinfo_name.'.php' ) ){
			require_once( $conf->path_lib_package.'/resources/picklesinfo/'.$picklesinfo_name.'.php' );
			$className = 'package_resources_picklesinfo_'.$picklesinfo_name;
		}elseif( @is_file( $conf->path_lib_base.'/resources/picklesinfo/'.$picklesinfo_name.'.php' ) ){
			require_once( $conf->path_lib_base.'/resources/picklesinfo/'.$picklesinfo_name.'.php' );
			$className = 'base_resources_picklesinfo_'.$picklesinfo_name;
		}
		if( class_exists( $className ) ){
			$obj = new $className( &$conf );
		}
		if( is_object( $obj ) && is_callable( array( $obj , 'execute' ) ) ){
			if( !$obj->execute() ){
				@header( 'Content-type: text/plain; charset='.mb_internal_encoding() );
				print	'[ERROR!] FAILD to execute.'."\n";
			}
		}else{
			@header( 'Content-type: text/plain; charset='.mb_internal_encoding() );
			print	'[ERROR!] PICKLESINFO "'.$picklesinfo_name.'" class does NOT exists or Uncallable.'."\n";
		}

		exit;
	}


	#--------------------------------------
	#	PICKLESINFO文字列を得る - Pickles Framework 0.2.1 追加
	function get_picklesinfo_string( &$conf ){
		static $done_flg = false;
		static $PICKLESINFO_COMMAND = null;

		if( !is_null( $_SERVER['REMOTE_ADDR'] ) && !$conf->allow_picklesinfo_service ){
			#	ウェブアクセスで、かつ、$conf->allow_picklesinfo_serviceが不許可であれば、
			#	Pickles Info サービスは無効です。
			return	null;
		}

		if( $done_flg ){
			#	前回の結果をキャッシュ
			return	$PICKLESINFO_COMMAND;
		}

		$done_flg = true;

		if( strlen( $_GET['PICKLESINFO'] ) )      { $PICKLESINFO_COMMAND = $_GET['PICKLESINFO']; }
		elseif( strlen( $_GET['PX'] ) )           { $PICKLESINFO_COMMAND = $_GET['PX']; $_GET['PICKLESINFO'] = $_GET['PX']; unset($_GET['PX']); }//PxFW 0.6.9 追加, PxFW 0.6.10 修正
		elseif( strlen( $_POST['PICKLESINFO'] ) ) { $PICKLESINFO_COMMAND = $_POST['PICKLESINFO']; }
		elseif( strlen( $_POST['PX'] ) )          { $PICKLESINFO_COMMAND = $_POST['PX']; $_POST['PICKLESINFO'] = $_POST['PX']; unset($_POST['PX']); }//PxFW 0.6.9 追加, PxFW 0.6.10 修正
		elseif( is_array( $_SERVER['argv'] ) && count( $_SERVER['argv'] ) ){
			foreach( $_SERVER['argv'] as $argv_line ){
				foreach( explode( '&' , $argv_line ) as $argv_unit ){
					preg_match( '/^(.*?)=(.*)$/ism' , $argv_unit , $argv_preg_result );
					if( $argv_preg_result[1] == 'PICKLESINFO' || $argv_preg_result[1] == 'PX' ){//PxFW 0.6.9 PXを追加
						$PICKLESINFO_COMMAND = $argv_preg_result[2];
						break 2;
					}
				}
			}
			unset( $argv_line , $argv_unit , $argv_preg_result );
		}
		return	$PICKLESINFO_COMMAND;

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

	#--------------------------------------
	#	オプションを分解して、連想配列で返す。
	function parse_option( $Val , $sep1 = '&' , $sep2 = '=' ){
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

}

?>