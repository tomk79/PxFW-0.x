<?php

#	Copyright (C)Tomoya Koyanagi.
#	LastUpdate : 9:35 2011/02/07

#******************************************************************************************************************
#	ユーザ情報のクラス
class base_lib_user{

	var $conf;
	var $req;
	var $dbh;
	var $errors;
		#	基本オブジェクト群

	var $UA;
		#	ユーザエージェント解析オブジェクト

	var $user_id = null;
	var $user_cd = null;
		#	ユーザを識別するためのユニークなキー。
		#	ログインに成功した場合に動的にセットされる。
		#	$user_idには必ずログインIDがセットされる。
		#	$user_cdは、ユーザ情報をDBで管理しているか、
		#	ファイルで管理しているかによって、値が変わる。
		#		DB管理の場合：$user_cd = ユーザマスタテーブルのidカラム(オートインクリメント)値。
		#		ファイル管理の場合：$user_cd = ログインID文字列。($user_idと同じ)
		#	これらの値は、$user->getusercd()、$user->getuserid()を通じて取り出せる。

	var $user_name = null;
	var $user_email = null;

	var $localconf_logging_expire = 1800;
		#	ログイン状態が切れるまでの時間の初期値(秒)。
		#	Configに$logging_expire値がセットされている場合は、そちらが有効。
	var $localconf_default_lang = 'ja';
	var $localconf_allow_lang;
	var $UserProfile = array();
	var $ProjectData = array();
	var $authority = array();
	var $localconf_userinfo_encoding = 'utf-8';

	var $login = null;
		#	ログインしているかどうかを示すフラグ。
		#	true = ログイン済み
		#	false = ログインに失敗
		#	null = ログインを試みていない

	var $lang = 'ja';
	var $localconf_path_userdir;
	var $localconf_info_projectid;
	var $localconf_seckey = 'PX';

	var $loginerror = false;
		#	ログインを試みて、失敗したか成功したかを格納するフラグ

	var $label_login_id = 'Login ID';
		#	ログインIDの呼び名。
		#	$user->get_label_login_id() から取得する。
		#	Pickles Framework 0.1.4 で追加。

	#----------------------------------------------------------------------------
	#	オブジェクトセットアップ
	function setup( &$conf , &$req , &$dbh , &$errors ){
		#--------------------------------------
		#	メンバオブジェクトを登録
		$this->conf = &$conf;
		$this->req = &$req;
		$this->dbh = &$dbh;
		$this->errors = &$errors;

		$this->localconf_allow_lang = array( 'ja' => true , 'en' => true );	#	サポートする自然言語

		#--------------------------------------
		#	$this->login は、ログインしているかどうかのbool。
		#	認証していればtrue、認証に失敗している場合はfalse、
		#	デフォルトのnullは、ログインを試みていないことを表す。
		$this->login = null;

		#--------------------------------------
		#	$confの設定値をロード
		$this->localconf_path_userdir = &$conf->path_userdir;
		$this->localconf_info_projectid = &$conf->info_projectid;
		if( strlen( $conf->seckey ) ){
			$this->localconf_seckey = &$conf->seckey;
		}
		if( is_array( $conf->allow_lang ) && count( $conf->allow_lang ) ){
			$this->localconf_allow_lang = $conf->allow_lang;
		}
		if( $this->localconf_allow_lang[$conf->default_lang] ){
			$this->localconf_default_lang = strtolower( $conf->default_lang );
			$this->lang = $this->localconf_default_lang;
		}
		if( is_int( $conf->logging_expire ) ){
			#	ログイン状態の有効期限を上書き
			$this->localconf_logging_expire = $conf->logging_expire;
		}

		#	テーマIDの初期化
		$this->settheme();

		#--------------------------------------
		#	権限値の器
		$this->authority = array();
		$this->authority['registed'] = false;	#	登録の有無
		$this->authority['authlevel'] = 0;		#	閲覧権限レベル値
		$this->authority['options'] = array();	#	権限オプション
		if( $req->is_cmd() ){
			if( intval( $conf->cmd_default_authlevel ) ){
				$this->user_id = '';
				$this->user_cd = 0;
				$this->login = true;
				$this->authority['registed'] = true;
				$this->authority['authlevel'] = intval( $conf->cmd_default_authlevel );
				$this->authority['options'] = array();
					#	コマンドライン起動時のデフォルト値
					#	21:30 2007/11/21 Pickles Framework 0.2.0
			}
		}

	}

	#--------------------------------------
	#	ユーザ編集オブジェクトを生成して返す。
	#	PxFW 0.6.5 追加
	function &factory_usereditor(){
		$className = $this->dbh->require_lib( '/resources/user/usereditor.php' );
		if( !$className ){
			$this->errors->error_log( 'Faild to load library [/resources/user/usereditor.php]' , __FILE__ , __LINE__ );
			return	false;
		}
		$obj = new $className( &$this->conf , &$this->dbh , &$this , &$this->errors );
		return	$obj;
	}

	#--------------------------------------
	#	ユーザエージェント解析オブジェクトを生成して返す。
	function &create_object_resources_useragent(){
		$className = $this->dbh->require_lib( '/resources/user/useragent.php' );
		if( !$className ){
			$this->errors->error_log( 'Faild to load library [/resources/user/useragent.php]' , __FILE__ , __LINE__ );
			return	false;
		}
		$obj = new $className( &$this->conf , &$this->req , &$this->dbh );
		return	$obj;
	}

	#--------------------------------------
	#	ログインする
	function login( $try_flg = null ){
		if( !is_null( $this->login ) ){ return $this->is_login(); }		# ←すでにログインの処理が終わっていたら、これ以上処理を進めない。
		if( is_null( $this->req->in() ) ){
			$this->errors->error_log( 'リクエスト情報がセットされていません。' , __FILE__ , __LINE__ );
			#	リクエスト情報が渡ってきていなければ、ログアウト処理
			$this->private_do_logout();
			return	$this->is_login();
		}

		if( $this->conf->user_auth_method == 'basic' ){
			#	Pickles Framework 0.3.6 で追加された、
			#	新しい設定項目に対応する処理。
			#	ベーシック認証で認証したユーザID/PWを
			#	Pickles Framework のユーザテーブルと照合するモード。
			$this->private_do_login( $_SERVER['PHP_AUTH_USER'] , $this->crypt_user_password( $_SERVER['PHP_AUTH_PW'] , $_SERVER['PHP_AUTH_USER'] ) );

		}elseif( !$this->conf->user_keep_userid_on_session && strlen( $this->req->getcookie('ID') ) && strlen( $this->req->getsession('PW') ) && strlen( $this->req->getsession('EXPIRE') ) ){
			#	IDがクッキーに、PWがセッションに存在する場合
			#	かつ、user_keep_userid_on_session設定値がfalseの場合
			if( $this->req->getsession( 'EXPIRE' ) < $this->conf->time ){
				$this->private_do_logout();
				return	$this->is_login();
			}
			$this->private_do_login( $this->req->getcookie('ID') , $this->req->getsession('PW') );

		}elseif( $this->conf->user_keep_userid_on_session && strlen( $this->req->getsession('ID') ) && strlen( $this->req->getsession('PW') ) && strlen( $this->req->getsession('EXPIRE') ) ){
			#	IDがセッションに、PWがセッションに存在する場合
			#	かつ、user_keep_userid_on_session設定値がtrueの場合
			if( $this->req->getsession( 'EXPIRE' ) < $this->conf->time ){
				$this->private_do_logout();
				return	$this->is_login();
			}
			$this->private_do_login( $this->req->getsession('ID') , $this->req->getsession('PW') );

		}elseif( !$this->conf->user_keep_userid_on_session && $this->conf->allow_login_without_cookies && strlen( $this->req->in('ID') ) && strlen( $this->req->getsession('PW') ) && strlen( $this->req->getsession('EXPIRE') ) ){
			#	IDがリクエストに、PWがセッションに存在する場合
			#	かつ、user_keep_userid_on_session設定値がfalseの場合
			#	(クッキーを使用しないログインが許可されている場合のみ有効)
			if( $this->req->getsession( 'EXPIRE' ) < $this->conf->time ){
				$this->private_do_logout();
				return	$this->is_login();
			}
			$this->private_do_login( $this->req->in('ID') , $this->req->getsession('PW') );

		}elseif( $try_flg ){
			#	明示的にログインを試みているフラグが渡された場合

			if( strlen( $this->req->in('ID') ) || strlen( $this->req->in('PW') ) ){
				#	IDとパスワードがリクエストに存在する場合
				#		→新たにログインを試みたユーザであると判断。
				$this->private_do_login( $this->req->in('ID') , $this->crypt_user_password( $this->req->in('PW') , $this->req->in('ID') ) );

			}elseif( $this->conf->allow_login_with_device_id && strlen( $this->get_device_id() ) ){
				#	コンフィグ allow_login_with_device_id 設定値がtrueで、
				#	端末IDを認識できたとき。
				$device_id_found_flg = false;
				if( is_array( $this->conf->rdb_usertable ) ){
					#	【 DB版 】
					#	$this->conf->rdb_usertable が配列だった場合、
					#	ユーザ情報をDB管理しているとみなす。
					$sql = 'SELECT user_id,user_pw FROM :D:tableName WHERE device_id = :S:device_id AND del_flg = 0;';
					$bindData = array(
						'tableName'=>$this->conf->rdb_usertable['master'],
						'device_id'=>$this->get_device_id(),
					);
					$sql = $this->dbh->bind( $sql , $bindData );
					$res = $this->dbh->sendquery( $sql );
					$value = $this->dbh->getval();
					if( count( $value ) ){
						$device_id_found_flg = true;
						$this->private_do_login( $value[0]['user_id'] , $value[0]['user_pw'] );
					}
				}else{
					#	【 ファイル版 】
					$target_user_list = $this->dbh->getfilelist( $this->conf->path_userdir );
					foreach( $target_user_list as $user_id ){
						if( $user_id == '..' || $user_id == '.' || $user_id == '@_SYSTEM' ){ continue; }
						$user_device_id = '';	//初期化
						if( !$this->dbh->is_file( $this->conf->path_userdir.'/'.$user_id.'/device.txt' ) ){
							continue;
						}
						$user_device_id = $this->dbh->file_get_contents( $this->conf->path_userdir.'/'.$user_id.'/device.txt' );
						if( $user_device_id == $this->get_device_id() ){
							#	一致する端末IDを見つけたら
							$device_id_found_flg = true;
							$user_pw = $this->dbh->file_get_contents( $this->conf->path_userdir.'/'.$user_id.'/pw.txt' );
							$this->private_do_login( $user_id , $user_pw );
							break;
						}
					}
				}

				if( !$device_id_found_flg ){
					#	該当する端末IDが登録されていなければ
					$this->set_login_error();
				}


			}
		}
		return	$this->is_login();
	}
	#--------------------------------------
	#	ログアウトする
	function logout(){
		return	$this->private_do_logout();
	}
	#--------------------------------------
	#	【プライベート】明示的にログイン
	function private_do_login( $in_id = null , $in_pw = null){
		if( is_null( $this->dbh ) ){
			$this->errors->error_log( 'データベースアクセスオブジェクトがメンバーに設置されていません。データベースアクセスオブジェクトは null です。' , __FILE__ , __LINE__ );
			return false;
		}
		if( !is_object( $this->dbh ) ){
			$this->errors->error_log( 'データベースアクセスオブジェクトがメンバーに設置されていません。データベースアクセスオブジェクトはオブジェクト型ではありません。' , __FILE__ , __LINE__ );
			return false;
		}
		if( !is_null( $this->login ) ){
			return $this->login;
		}

		#	パスワードチェック開始
		$user_basicinfo = $this->private_get_user_basicinfo( $in_id );
		if( $user_basicinfo['user_pw'] == $in_pw ){
			#--------------------------------------
			#	パスワードが一致した場合
			if( $this->conf->user_keep_userid_on_session ){
				#	IDをセッションに持つ場合
				$this->req->setsession( 'ID' , $in_id );
			}else{
				#	IDをクッキーに持つ場合
				$this->req->setcookie( 'ID' , $in_id );
			}
			if( !$this->is_enable_cookie() && $this->conf->allow_login_without_cookies ){
				#	クッキーが使えないときは
				#	※ただし、コンフィグ allow_login_without_cookies が許可している場合のみ。
				if( !$this->conf->user_keep_userid_on_session ){
					#	IDをセッションに持つ場合は持ちまわらない。
					$this->req->addgene( 'ID' , $in_id );
				}
				$this->req->addgene( $this->conf->session_key , $this->req->getsessionid() );
			}
			$this->user_id = $in_id;
			$this->user_cd = $user_basicinfo['user_cd'];
			$this->user_name = $user_basicinfo['user_name'];
			$this->user_email = $user_basicinfo['user_email'];
			$this->login = true;
			$this->req->setsession( 'PW' , $user_basicinfo['user_pw'] );
			$this->req->setsession( 'EXPIRE' , ( $this->conf->time + $this->localconf_logging_expire ) );
			unset( $user_basicinfo );
			$this->setinfo();
			$this->set_custominfo();//Pickles Framework 0.3.7 追加
			return	true;

		}else{
			#--------------------------------------
			#	パスワードが一致しない場合
			$this->set_login_error();
			$this->private_do_logout();
			return	false;

		}
		return	false;
	}

	function set_login_error(){
		$this->loginerror = true;
	}
	function clear_login_error(){
		$this->loginerror = false;
	}
	function is_login_error(){
		return	$this->loginerror;
	}

	#--------------------------------------
	#	【プライベート】ユーザの基本情報を得る
	#	ユーザが存在しない場合には、nullを返す。
	function private_get_user_basicinfo( $user_id ){
		#	ここで受け取る $user_id は、必ず文字列のユーザID。
		#	なぜなら、この時点で、user_cd は不明であるはずだから。

		#	Pickles Framework 0.3.7
		#	第二引数に $user_pw = null を受け取っていたが、
		#	使っていないので削除した。

		if( is_array( $this->conf->rdb_usertable ) ){
			#	$this->conf->rdb_usertable が配列だった場合、
			#	ユーザ情報をDB管理しているとみなす。
			$sql = 'SELECT user_cd,user_pw,user_name,user_email FROM :D:tableName WHERE user_id = :S:user_id AND del_flg = 0;';
			$bindData = array(
				'tableName'=>$this->conf->rdb_usertable['master'],
				'user_id'=>$user_id,
			);
			$sql = $this->dbh->bind( $sql , $bindData );
			$res = $this->dbh->sendquery( $sql );
			$value = $this->dbh->getval();
			if( !is_array( $value ) ){
				return	null;
			}
			return	$value[0];
		}else{
			$path_base = &$this->conf->path_userdir;

			$RTN = array();
			if( !$this->dbh->is_dir( $path_base.'/'.$user_id ) ){
				return	null;
			}
			$RTN['user_cd'] = $user_id;
			$RTN['user_pw'] = $this->dbh->file_get_contents( $path_base.'/'.$user_id.'/pw.txt' );
			$RTN['user_name'] = $this->dbh->file_get_contents( $path_base.'/'.$user_id.'/name.txt' );
			$RTN['user_email'] = $this->dbh->file_get_contents( $path_base.'/'.$user_id.'/email.txt' );
			return	$RTN;
		}
	}
	#--------------------------------------
	#	【プライベート】明示的にログアウト
	function private_do_logout(){
		$this->login = false;
		$this->UserProfile = array();
		$this->ProjectData = array();
		$this->req->delcookie( 'ID' );
		$this->req->delgene( 'ID' );
		$this->req->delgene( $this->conf->session_key );
		$this->req->delsession( 'PW' );
		$this->req->delsession( 'EXPIRE' );
		$result = $this->is_login();
		if( !$result ){
			return	true;
		}else{
			return	false;
		}
	}

	#--------------------------------------
	#	現在のユーザがログインしているかどうかを返す
	function is_login(){
		if( $this->login && !is_null( $this->user_id ) && !is_null( $this->user_cd ) ){
			return true;
		}
		return	false;
	}

	#--------------------------------------
	#	現在のユーザがゲストユーザかどうかを返す
	function is_guest(){
		if( !$this->is_login() ){ return true; }
		if( $this->getuserid() == 'guest' ){ return true; }
		return	false;
	}

	#--------------------------------------
	#	ユーザの持っている権限レベルを返す
	function get_auth_level(){ return $this->getauthlevel(); }//←PxFW 0.6.3 追加：エイリアス
	function getauthlevel(){
		return	intval( $this->authority['authlevel'] );
	}
	#--------------------------------------
	#	ユーザの持っている権限オプションを返す
	function get_auth_options( $key = null ){ return $this->getauthoptions( $key ); }//←PxFW 0.6.3 追加：エイリアス
	function getauthoptions( $key = null ){
		if( is_null( $key ) ){
			return	$this->authority['options'];
		}
		return	$this->authority['options'][$key];
	}

	#--------------------------------------
	#	現在のログインユーザがプロジェクトに登録しているかどうか
	function is_registed(){
		static $RTN = null;
		if( !$this->is_login() ){ return false; }	#	ログインしてないとだめ
		if( !is_null( $RTN ) ){ return $RTN; }

		$user_cd = $this->getusercd();
		$project_id = $this->conf->info_projectid;

		$RTN = false;
		if( is_array( $this->conf->rdb_usertable ) ){
			#	$this->conf->rdb_usertable が配列だった場合、
			#	ユーザ情報をDB管理しているとみなす。
			$sql = 'SELECT registed_flg FROM :D:tableName WHERE user_cd = :N:user_cd AND project_id = :S:project_id;';
			$bindData = array(
				'tableName'=>$this->conf->rdb_usertable['project_status'],
				'user_cd'=>$user_cd,
				'project_id'=>$project_id,
			);
			$sql = $this->dbh->bind( $sql , $bindData );
			$res = $this->dbh->sendquery( $sql );
			$value = $this->dbh->getval();
			if( $value[0]['registed_flg'] ){
				$RTN = true;
			}
			return	$RTN;
		}else{
			if( $this->dbh->is_dir( $this->localconf_path_userdir.'/'.$user_cd.'/projectdata/'.$project_id ) ){
				$RTN = true;
			}
			return	$RTN;
		}
		return	false;
	}


	#--------------------------------------
	#	現在のユーザが最後にログインした時間を調べる
	function get_time_lastlogin(){
		if( !$this->is_login() ){ return false; }
		$maxvalue = 0;
		if( is_array( $this->conf->rdb_usertable ) ){
			#	【DB版】
			#	$this->conf->rdb_usertable が配列だった場合、
			#	ユーザ情報をDB管理しているとみなす。
			$sql = 'SELECT lastlogin_date FROM :D:tableName WHERE user_cd = :N:user_cd AND project_id = :S:project_id;';
			$bindData = array(
				'tableName'=>$this->conf->rdb_usertable['project_status'],
				'user_cd'=>$this->getusercd(),
				'project_id'=>$this->conf->info_projectid,
			);
			$res = $this->dbh->sendquery( $sql );
			$value = $this->dbh->getval();
			if( strlen( $value[0]['lastlogin_date'] ) ){
				return	intval( $value[0]['lastlogin_date'] );
			}

			#	$this->conf->rdb_usertable が配列だった場合、
			#	ユーザ情報をDB管理しているとみなす。
			$sql = 'SELECT lastlogin_date FROM :D:tableName WHERE user_cd = :N:user_cd AND del_flg = 0;';
			$bindData = array(
				'tableName'=>$this->conf->rdb_usertable['master'],
				'user_cd'=>$this->getusercd(),
			);
			$res = $this->dbh->sendquery( $sql );
			$value = $this->dbh->getval();
			if( preg_match( '/^[0-9]+$/ism' , $value[0]['lastlogin_date'] ) ){
				return	intval( $value[0]['lastlogin_date'] );
			}elseif( strlen( $value[0]['lastlogin_date'] ) ){
				return	$this->dbh->datetime2int( $value[0]['lastlogin_date'] );
			}

			return	0;
		}else{
			#	【ファイル版】
			$usersdir = $this->localconf_path_userdir.'/'.$this->getusercd();
			if( !$this->dbh->is_dir( $usersdir ) ){ return false; }

			$projectlist = $this->dbh->getfilelist( $usersdir.'/projectdata' );
			foreach( $projectlist as $project_id ){
				if( $project_id == '.' || $project_id == '..' ){ continue; }
				if( !$this->dbh->is_file( $usersdir.'/projectdata/'.$project_id.'/T_lastlogin.txt' ) ){ continue; }
				$MEMO = intval( $this->dbh->file_get_contents( $usersdir.'/projectdata/'.$project_id.'/T_lastlogin.txt' ) );
				if( $MEMO > $maxvalue ){
					$maxvalue = $MEMO;
				}
			}
			if( $this->dbh->is_file( $usersdir.'/T_lastlogin.txt' ) ){
				$csvcont = $this->dbh->read_csv( $usersdir.'/T_lastlogin.txt' , null , null , null , mb_internal_encoding() );
				if( !is_array( $csvcont ) ){
					$csvcont = array();
				}
				foreach( $csvcont as $Line ){
					$MEMO = intval( $Line[1] );
					if( $MEMO > $maxvalue ){
						$maxvalue = $MEMO;
					}
				}
			}
			return	$maxvalue;
		}
		return	0;
	}


	#--------------------------------------
	#	パスワードの変更を保存する
	function dao_change_password( $user_cd = null , $newpw = null ){
		if( is_null( $user_cd ) ){ $user_cd = $this->getusercd(); }
		if( !strlen( $user_cd ) ){ return false; }
		if( is_null( $newpw ) ){ return false; }

		if( is_array( $this->conf->rdb_usertable ) ){
			#	【 DB版 】
			#	$this->conf->rdb_usertable が配列だった場合、
			#	ユーザ情報をDB管理しているとみなす。

			#	↓先にユーザIDを調べて、crypt_user_password() に渡さないといけない。
			#	  Pickles Framework 0.4.3 追加
			$sql = '';
			$sql .= 'SELECT user_id FROM :D:tableName'."\n";
			$sql .= 'WHERE user_cd = :N:user_cd;'."\n";

			$bindData = array(
				'tableName'=>$this->conf->rdb_usertable['master'],
				'user_cd'=>$user_cd,
			);
			$sql = $this->dbh->bind( $sql , $bindData );
			$res = $this->dbh->sendquery( $sql );
			$value = $this->dbh->getval();

			$pwstring = $this->crypt_user_password( $newpw , $value[0]['user_id'] );

			$sql = '';
			$sql .= 'UPDATE :D:tableName'."\n";
			$sql .= 'SET'."\n";
			$sql .= '	user_pw = :S:user_pw,'."\n";
			$sql .= '	lastupdate_date = :S:lastupdate_date'."\n";
			$sql .= 'WHERE user_cd = :N:user_cd;'."\n";

			$bindData = array(
				'tableName'=>$this->conf->rdb_usertable['master'],
				'user_cd'=>$user_cd,
				'user_pw'=>$pwstring,
				'lastupdate_date'=>$this->dbh->int2datetime( $this->conf->time ),
			);
			$sql = $this->dbh->bind( $sql , $bindData );
			$res = $this->dbh->sendquery( $sql );
			if( !$res ){
				$this->dbh->rollback();
				return false;
			}
			$value = $this->dbh->getval();
			$this->dbh->commit();
			$this->req->setsession( 'PW' , $pwstring );
			return	true;

		}else{
			#	【 ファイル版 】
			$pwstring = $this->crypt_user_password( $newpw , $user_cd );//ファイル管理の場合は、$user_cd と $user_id は同じ意味。
			$results = $this->dbh->file_overwrite( $this->localconf_path_userdir.'/'.$user_cd.'/pw.txt' , $pwstring );
			if( !$results ){
				return false;
			}
			$this->req->setsession( 'PW' , $pwstring );
			return	true;
		}
		return	true;
	}


	#--------------------------------------
	#	ユーザパスワードを暗号化して返す
	function crypt_user_password( $password , $user_id = null ){
		$seckey = $this->localconf_seckey;
		if( !strlen( $seckey ) ){
			#	$seckey が空白だったら、
			#	ユーザIDをキーとして使用する。
			if( strlen( $user_id ) ){
				$seckey = $user_id;
			}elseif( strlen( $this->req->in('ID') ) ){
				$seckey = $this->req->in('ID');
			}else{
				$seckey = $this->getuserid();
			}
		}elseif( $seckey == 'MD5' ){
			#	$seckey が[ MD5 ]だったら、
			#	md5エンコードする。
			return	md5( $password );
		}
		return	crypt( $password , $seckey );
	}


	#******************************************************************************************************************

	#--------------------------------------
	#	ユーザ情報をメンバーにセット
	function setinfo(){
		if( !$this->is_login() ){ return false; }

		$user_cd = $this->getusercd();

		if( is_array( $this->conf->rdb_usertable ) ){
			#	【 DB版 】
			#	$this->conf->rdb_usertable が配列だった場合、
			#	ユーザ情報をDB管理しているとみなす。

			#	ログインしていたら、諸情報をセット

			#--------------------------------------
			#	ユーザプロパティの設定
			$sql = 'SELECT keystr,valstr FROM :D:tableName WHERE user_cd = :N:user_cd;';
			$bindData = array(
				'tableName'=>$this->conf->rdb_usertable['property'] ,
				'user_cd'=>$user_cd ,
			);
			$sql = $this->dbh->bind( $sql , $bindData );
			$res = $this->dbh->sendquery( $sql );
			$value = $this->dbh->getval();

			if( is_array( $value ) ){
				foreach( $value as $Line ){
					$this->UserProfile[$Line['keystr']] = text::convert_encoding( $Line['valstr'] );
				}
			}

			#--------------------------------------
			#	ユーザ権限値をセットする。
			if( !$this->set_authority( $user_cd ) ){
				return	false;
			}

			return	true;

		}else{
			#	【 ファイル版 】
			#	ログインしていたら、諸情報をセット

			#--------------------------------------
			#	ユーザプロパティの設定
			if( $this->dbh->is_file( $this->localconf_path_userdir.'/'.$user_cd.'/info.csv' ) ){
				$user_info = $this->dbh->read_csv( $this->localconf_path_userdir.'/'.$user_cd.'/info.csv' , null , null , null , $this->localconf_userinfo_encoding );
				foreach( $user_info as $Line ){
					$this->UserProfile[$Line[0]] = text::convert_encoding( $Line[1] );
				}
			}

			#--------------------------------------
			#	ユーザ権限値をセットする。
			if( !$this->set_authority( $user_cd ) ){
				return	false;
			}

			return	true;
		}
		return	null;
	}

	#--------------------------------------
	#	ユーザ権限値をセットする。
	function set_authority( $user_cd ){
		if( is_array( $this->conf->rdb_usertable ) ){
			#	【 DB版 】
			#	$this->conf->rdb_usertable が配列だった場合、
			#	ユーザ情報をDB管理しているとみなす。

			#--------------------------------------
			#	権限値の設定
			$sql = 'SELECT registed_flg,authlevel FROM :D:tableName WHERE user_cd = :N:user_cd AND project_id = :S:project_id;';
			$bindData = array(
				'tableName'=>$this->conf->rdb_usertable['project_status'],
				'user_cd'=>$user_cd,
				'project_id'=>$this->conf->info_projectid,
			);
			$sql = $this->dbh->bind( $sql , $bindData );
			$res = $this->dbh->sendquery( $sql );
			$value = $this->dbh->getval();
			if( $value[0]['registed_flg'] ){
				#	一般ユーザ(プロジェクトに登録済み)

				#	権限レベルの読み込み
				$this->authority['registed'] = true;
				$this->authority['authlevel'] = 2;
				if( strlen( $value[0]['authlevel'] ) && intval( $value[0]['authlevel'] ) > 2 ){
					$this->authority['authlevel'] = intval( $value[0]['authlevel'] );
				}

				#	権限オプションの読み込み
				$this->authority['options'] = array();
				$sql = 'SELECT keystr,valstr FROM :D:tableName WHERE user_cd = :N:user_cd AND project_id = :S:project_id;';
				$bindData = array(
					'tableName'=>$this->conf->rdb_usertable['project_authoptions'],
					'user_cd'=>$user_cd,
					'project_id'=>$this->conf->info_projectid,
				);
				$sql = $this->dbh->bind( $sql , $bindData );
				$res = $this->dbh->sendquery( $sql );
				$value = $this->dbh->getval();
				if( is_array( $value ) ){
					foreach( $value as $Line ){
						if( !strlen( $Line['valstr'] ) ){ continue; }
						$this->authority['options'][$Line['keystr']] = text::convert_encoding( $Line['valstr'] );
					}
				}

				#	所属グループ一覧の読み込み
				$this->authority['authgroup'] = array();
				$sql = 'SELECT * FROM :D:tableName WHERE user_cd = :N:user_cd AND project_id = :S:project_id AND active_flg = 1;';
				$bindData = array(
					'tableName'=>$this->conf->rdb_usertable['project_authgroup'],
					'project_id'=>$this->conf->info_projectid,
					'user_cd'=>$user_cd,
				);
				$sql = $this->dbh->bind( $sql , $bindData );
				$res = $this->dbh->sendquery( $sql );
				$value = $this->dbh->getval();

				$this->authority['authgroup'] = array();
				if( is_array( $value ) ){
					foreach( $value as $Line ){
						$this->authority['authgroup'][$Line['group_cd']] = true;
					}
				}

				#	所属グループの権限オプションを反映
				foreach( $this->authority['authgroup'] as $group_cd=>$is_member ){
					if( !$is_member ){ continue; }
					$sql = 'SELECT * FROM :D:tableName WHERE group_cd = :N:group_cd;';
					$bindData = array(
						'tableName'=>$this->conf->rdb_usertable['group_authoptions'],
						'group_cd'=>$group_cd,
					);
					$sql = $this->dbh->bind( $sql , $bindData );
					$res = $this->dbh->sendquery( $sql );
					$value = $this->dbh->getval();

					if( is_array( $value ) ){
						foreach( $value as $Line ){
							if( !$Line['valstr'] ){ continue; }
							$this->authority['options'][$Line['keystr']] = text::convert_encoding( $Line['valstr'] );
						}
					}
				}

			}else{
				#	一般ユーザ(ログインしているが、プロジェクトに未登録)
				$this->authority['registed'] = false;
				$this->authority['authlevel'] = 1;
			}
			return	true;

		}else{
			#	【 ファイル版 】

			#--------------------------------------
			#	権限値の設定
			if( strlen( $this->localconf_info_projectid ) && $this->dbh->is_dir( $this->localconf_path_userdir.'/'.$user_cd.'/projectdata/'.$this->localconf_info_projectid ) ){
				#	一般ユーザ(プロジェクトに登録済み)
				$this->authority['registed'] = true;
				$this->authority['authlevel'] = 2;

				if( $this->dbh->is_dir( $this->localconf_path_userdir.'/'.$user_cd.'/projectdata/'.$this->localconf_info_projectid.'/auth' ) ){
					#	権限レベルの読み込み
					if( $this->dbh->is_file( $this->localconf_path_userdir.'/'.$user_cd.'/projectdata/'.$this->localconf_info_projectid.'/auth/authlevel.int' ) ){
						$this->authority['authlevel'] = intval( $this->dbh->file_get_contents( $this->localconf_path_userdir.'/'.$user_cd.'/projectdata/'.$this->localconf_info_projectid.'/auth/authlevel.int' ) );
					}
					#	権限オプションの読み込み
					$this->authority['options'] = array();
					if( $this->dbh->is_file( $this->localconf_path_userdir.'/'.$user_cd.'/projectdata/'.$this->localconf_info_projectid.'/auth/authoptions.array' ) ){
						$this->authority['options'] = @include( $this->localconf_path_userdir.'/'.$user_cd.'/projectdata/'.$this->localconf_info_projectid.'/auth/authoptions.array' );
					}

					#	所属グループ一覧の読み込み
					$this->authority['authgroup'] = array();
					$project_id = $this->conf->info_projectid;
					if( $this->dbh->is_file( $this->conf->path_userdir.'/'.$user_cd.'/projectdata/'.$project_id.'/auth/authgroup.array' ) ){
						$this->authority['authgroup'] = @include( $this->conf->path_userdir.'/'.$user_cd.'/projectdata/'.$project_id.'/auth/authgroup.array' );
					}

					#	所属グループの権限オプションを反映
					foreach( $this->authority['authgroup'] as $group_cd=>$is_member ){
						if( !$is_member ){ continue; }
						$saveTargetPath = $this->conf->path_userdir.'/@_SYSTEM/authgroup'.'/authgroup_define.array';
						$group_define = @include( $saveTargetPath );
						if( !is_array( $group_define[$project_id][$group_cd] ) ){
							#	グループCDが未定義だったらダメ。
							continue;
						}
						$group_options = $group_define[$project_id][$group_cd]['authoptions'];
						unset( $group_define );
						if( !is_array( $group_options ) ){
							continue;
						}
						foreach( $group_options as $key=>$val ){
							if( !$val ){ continue; }
							$this->authority['options'][$key] = $val;
						}
					}

				}

			}else{
				#	一般ユーザ(ログインしているが、プロジェクトに未登録)
				$this->authority['registed'] = false;
				$this->authority['authlevel'] = 1;
			}

			return	true;
		}
		return	null;
	}


	#--------------------------------------
	#	通常のログイン処理が終わった後に、カスタムユーザ情報をセットする。
	#	Pickles Framework 0.3.7 追加
	function set_custominfo(){
		#	独自拡張のために確保された領域です。
		#	このメソッドは、ログイン処理の最後の処理として、
		#	$this->private_do_login() が最後にコールします。
		return	true;
	}

	#--------------------------------------
	#	ログインユーザのユーザコードを取得
	function get_user_cd(){ return $this->getusercd(); }//←PxFW 0.6.3 追加：エイリアス
	function getusercd(){
		if( $this->is_login() ){
			return	$this->user_cd;
		}
		return	null;
	}
	#--------------------------------------
	#	ログインユーザのユーザIDを取得
	function get_user_id(){ return $this->getuserid(); }//←PxFW 0.6.3 追加：エイリアス
	function getuserid(){
		if( $this->is_login() ){
			return	$this->user_id;
		}
		return	null;
	}
	function set_user_id( $user_id ){ return $this->setuserid( $user_id ); }//←PxFW 0.6.3 追加：エイリアス
	function setuserid( $user_id ){
		#	$user_idをセットする際には、
		#	プロフィール情報などを一旦クリアする
		$this->UserProfile = array();
		$this->ProjectData = array();
		$this->authority = array();
		$this->authority['registed'] = false;	#	登録の有無
		$this->authority['authlevel'] = 0;		#	閲覧権限レベル値
		$this->authority['options'] = array();	#	権限オプション
		$this->user_name = '';
		$this->user_email = '';

		$this->user_id = $user_id;
		return	true;
	}


	#--------------------------------------
	#	ユーザのプロフィールを返す
	function get_profile( $key ){ return $this->getprofile( $key ); }//←PxFW 0.6.3 追加：エイリアス
	function getprofile( $key ){
		if( strlen( $this->UserProfile[$key] ) ){
			return	$this->UserProfile[$key];
		}
		return	'Unknown';
	}
	function set_profile( $key , $val ){ return $this->setprofile( $key , $val ); }//←PxFW 0.6.3 追加：エイリアス
	function setprofile( $key , $val ){
		$this->UserProfile[$key] = $val;
		return	true;
	}
	#--------------------------------------
	#	ユーザ名を返す
	function get_name(){ return $this->getname(); }//←PxFW 0.6.3 追加：エイリアス
	function getname(){
		return	$this->user_name;
	}
	#--------------------------------------
	#	メールアドレスを返す
	function get_email(){ return $this->getemail(); }//←PxFW 0.6.3 追加：エイリアス
	function getemail(){
		return	$this->user_email;
	}

	#--------------------------------------
	#	自然言語(lang)を操作
	function set_language( $lang = null ){ return $this->setlanguage( $lang ); }//←PxFW 0.6.3 追加：エイリアス
	function setlanguage( $lang = null ){
		if( strlen( $lang ) && $this->localconf_allow_lang[strtolower( $lang )] ){
			$this->lang = strtolower( $lang );
		}else{
			$this->lang = $this->localconf_default_lang;
		}
		return	true;
	}
	function get_language(){ return $this->getlanguage(); }//←PxFW 0.6.3 追加：エイリアス
	function getlanguage(){
		if( strlen( $this->lang ) ){
			return	$this->lang;
		}else{
			return	$this->localconf_default_lang;
		}
	}

	#--------------------------------------
	#	テーマIDの入出力
	function set_theme( $themeid = null ){ return $this->settheme( $themeid ); }//←PxFW 0.6.3 追加：エイリアス
	function settheme( $themeid = null ){
		$themeid = preg_replace( '/\\/|\\\\/' , '' , $themeid );
		#--------------------------------------
		#	まず初期化
		if( strlen( $this->conf->default_theme_id ) ){
			$this->conf->theme_id = $this->conf->default_theme_id;
		}else{
			$this->conf->theme_id = 'default';
		}
		#--------------------------------------
		#	指定を反映
		if( $themeid == 'null' ){
			#	【Pickles Framework 0.3.7 追加】
			#	$conf->theme_id が 'null' の場合の処理を修正。
			#	※注意：テーマID 'null' は、予約語です。
			if( $this->conf->allow_cancel_customtheme ){
				#	'null' が許可された場合、
				#	選択したテーマは null にする。
				#	(文字列じゃなくて、null)
				#	不許可の場合は、デフォルトのままにする。
				$this->conf->theme_id = null;
			}
		}elseif( strlen( $themeid ) ){
			if( strlen( $this->get_ct() ) && $this->dbh->is_dir( $this->conf->path_theme_collection_dir.'/'.$themeid.'/'.$this->get_ct() ) ){
				#	【Pickles Framework 0.3.7 追加】
				#	テーマが存在しない場合は通さないようにした。
				$this->conf->theme_id = $themeid;
			}
		}
		return	true;#【Pickles Framework 0.3.7 追加】常に true を返すようにした。
	}
	function get_theme(){ return $this->gettheme(); }//←PxFW 0.6.3 追加：エイリアス
	function gettheme(){
		return	$this->conf->theme_id;
	}

	#--------------------------------------
	#	CTを操作
	function set_ct( $CT = null ){ return $this->setct( $CT ); }//←PxFW 0.6.3 追加：エイリアス
	function setct( $CT = null ){
		if( is_null( $CT ) ){
			$CT = $this->UA->get_ct();
		}
		$CT = preg_replace( '/\\/|\\\\/' , '' , $CT );
		$this->conf->CT = $CT;
		return	true;
	}
	function get_ct()	{ return $this->conf->CT; }
	function is_pc()	{ if( $this->conf->CT == 'PC' )			{ return true; }else{ return false; } }
	function is_sb()	{ if( $this->conf->CT == 'SB' )			{ return true; }else{ return false; } }
	function is_mp()	{ if( $this->conf->CT == 'MP' )			{ return true; }else{ return false; } }
	function is_pda()	{ if( $this->conf->CT == 'PDA' )		{ return true; }else{ return false; } }
	function is_print()	{ if( $this->conf->CT == 'PRINT' )		{ return true; }else{ return false; } }
	function is_server(){ if( $this->conf->CT == 'SERVER' )		{ return true; }else{ return false; } }

	#--------------------------------------
	#	ユーザエージェントを解析
	function parse_useragent( $HTTP_USER_AGENT = null , $OTHER_PARAMS = null ){
		$this->UA = &$this->create_object_resources_useragent();
		if( $this->UA === false ){ return false; }
		$this->UA->parse_useragent( $HTTP_USER_AGENT , $OTHER_PARAMS );
		$this->setct();
		return	true;
	}
	function get_useragent(){ return $this->UA->get_useragent(); }// Pickles Framework 0.1.12 追加
	function get_browser_name(){ return $this->UA->get_browser_name(); }
	function get_browser_version( $digit = null ){ return $this->UA->get_browser_version( $digit ); }
	function get_browser_version_string(){ return $this->UA->get_browser_version_string(); }
	function get_browser_group(){ return $this->UA->get_browser_group(); }
	function get_os_name(){ return $this->UA->get_os_name(); }
	function get_os_version(){ return $this->UA->get_os_version(); }
	function get_enduserclass(){ return $this->UA->get_enduserclass(); }
	function get_device_id(){ return $this->UA->get_device_id(); }
	function get_device_spec( $key = null ){ return $this->UA->get_device_spec( $key ); }// Pickles Framework 0.1.2 追加
	function is_enable_cookie(){ return $this->UA->is_enable_cookie(); }
	function is_enable_http_referer(){ return $this->UA->is_enable_http_referer(); }

	#--------------------------------------
	#	ユーザ情報の定義
	function get_userinfo_definition(){
		#	このメソッドは、ユーザプロパティ情報の構造を定義します。
		#	必要に応じて拡張して使用してください。
		$RTN = array();
		return	$RTN;
	}

	#--------------------------------------
	#	編集権限オプションの定義
	function get_authoptions_definition(){
		#	このメソッドは、編集権限オプション情報の構造を定義します。
		#	必要に応じて拡張して使用してください。
		$RTN = array();
		return	$RTN;
	}

	#--------------------------------------
	#	ログインユーザが所属しているグループ名の一覧を得る
	function get_authgroup(){
		static	$RTN = null;
		if( is_array( $RTN ) ){ return $RTN; }

		if( !$this->is_login() ){ return array(); }

		$user_cd = $this->getusercd();
		if( !strlen( $user_cd ) ){
			return	array();
		}

		if( is_array( $this->conf->rdb_usertable ) ){
			#	【DB版】
			#	$this->conf->rdb_usertable が配列だった場合、
			#	ユーザ情報をDB管理しているとみなす。

			$sql = 'SELECT group_cd FROM :D:tableName WHERE user_cd = :N:user_cd AND project_id = :S:project_id AND active_flg = 1;';
			$bindData = array(
				'tableName'=>$this->conf->rdb_usertable['project_authgroup'],
				'user_cd'=>$user_cd,
				'project_id'=>$this->conf->info_projectid,
			);
			$sql = $this->dbh->bind( $sql , $bindData );
			$res = $this->dbh->sendquery( $sql );
			$value = $this->dbh->getval();
			if( is_array( $value ) ){
				foreach( $value as $Line ){
					$RTN[$Line['group_cd']] = true;
				}
			}
		}else{
			#	【ファイル版】
			if( strlen( $this->localconf_info_projectid ) && $this->dbh->is_file( $this->localconf_path_userdir.'/'.$user_cd.'/projectdata/'.$this->localconf_info_projectid.'/auth/authgroup.array' ) ){
				$RTN = @include( $this->localconf_path_userdir.'/'.$user_cd.'/projectdata/'.$this->localconf_info_projectid.'/auth/authgroup.array' );
			}
		}
		if( !is_array( $RTN ) ){
			$RTN = array();
		}
		return	$RTN;
	}

	#--------------------------------------
	#	ログインユーザが指定されたグループに所属しているかどうかを得る
	function is_assigned2authgroup( $authgroup_cd ){
		if( !strlen( $authgroup_cd ) ){ return	false; }
		$authgrouplist = $this->get_authgroup();
		if( $authgrouplist[$authgroup_cd] ){ return	true; }
		return	false;
	}

	#--------------------------------------
	#	編集グループの定義(一覧)
	function get_authgroup_definition(){
		static	$RTN = null;
		if( is_array( $RTN ) ){
			return	$RTN;
		}

		$RTN = array();
		if( is_array( $this->conf->rdb_usertable ) ){
			#	【 DB版 】
			#	$this->conf->rdb_usertable が配列だった場合、
			#	ユーザ情報をDB管理しているとみなす。

			$sql = 'SELECT * FROM :D:tableName WHERE del_flg = 0;';
			$bindData = array(
				'tableName'=>$this->conf->rdb_usertable['group'],
			);
			$sql = $this->dbh->bind( $sql , $bindData );
			$res = $this->dbh->sendquery( $sql );
			$RTN = $this->dbh->getval();
			return	$RTN;

		}else{
			#	【 ファイル版 】
			$path_define_file = $this->conf->path_userdir.'/@_SYSTEM/authgroup/authgroup_define.array';
			if( $this->dbh->is_file( $path_define_file ) ){
				$RTN = @include( $path_define_file );
			}
			if( !is_array( $RTN ) ){
				$RTN = array();
			}

			return	$RTN[$this->conf->info_projectid];
		}
		return	array();
	}


	#--------------------------------------
	#	ログインユーザのプロジェクト関連データを保存
	function set_prjdata( $key , $value ){
		if( !$this->is_login() ){ return false; }
		if( preg_match( '/[^a-zA-Z0-9_\.]/' , $key ) ){ return false; }

		if( is_array( $this->conf->rdb_usertable ) ){
			#	$this->conf->rdb_usertable が配列だった場合、
			#	ユーザ情報をDB管理しているとみなす。
			//	一旦、全部削除
			$sql = 'DELETE FROM :D:tableName WHERE user_cd = :N:user_cd AND project_id = :S:project_id AND dataspace = :S:dataspace;';
			$bindData = array(
				'tableName'=>$this->conf->rdb_usertable['project_datas'],
				'user_cd'=>$this->getusercd(),
				'project_id'=>$this->conf->info_projectid,
				'dataspace'=>$key,
			);
			$sql = $this->dbh->bind( $sql , $bindData );
			$res = $this->dbh->sendquery( $sql );

			if( is_array( $value ) ){
				foreach( array_keys( $value ) as $Line ){
					$sql = 'INSERT INTO '.$this->conf->rdb_usertable['project_datas'].' values( '.text::data2text( $this->getusercd() ).','.text::data2text( $this->conf->info_projectid ).','.text::data2text( $key ).','.text::data2text( $Line ).','.text::data2text( $value[$Line] ).','.text::data2text( $this->conf->time ).','.text::data2text( $this->conf->time ).');';
				}
			}elseif( is_string( $value ) || is_int( $value ) ){
				$sql = 'INSERT INTO '.$this->conf->rdb_usertable['project_datas'].' values( '.text::data2text( $this->getusercd() ).','.text::data2text( $this->conf->info_projectid ).','.text::data2text( $key ).',NULL,'.text::data2text( $value ).','.text::data2text( $this->conf->time ).','.text::data2text( $this->conf->time ).');';
			}else{
				return	false;
			}
			$res = $this->dbh->sendquery( $sql );
			if( $res ){
				$this->dbh->commit();
				return	true;
			}
			return	false;

		}else{
			if( !strlen( $this->conf->info_projectid ) ){ return false; }

			$userdir = $this->localconf_path_userdir.'/'.$this->getuserid();

			$prjdir = $userdir.'/projectdata';
			if( !$this->dbh->is_dir( $prjdir ) ){ $this->dbh->mkdir( $prjdir ); }
			$prjdir = $prjdir.'/'.$this->conf->info_projectid;
			if( !$this->dbh->is_dir( $prjdir ) ){ $this->dbh->mkdir( $prjdir ); }
			$prjdir = $prjdir.'/datas';
			if( !$this->dbh->is_dir( $prjdir ) ){ $this->dbh->mkdir( $prjdir ); }

			$prjfile = $prjdir.'/'.$key.'.array';

			/* データ保存処理の開始 */
			$this->ProjectData[$key] = $value;

			if( $this->dbh->is_dir( $prjfile ) ){ return false; }
			if( is_null( $value ) ){
				if( $this->dbh->is_file( $prjfile ) ){
					return	$this->dbh->rmdir( $prjfile );
				}
				return	true;
			}

			$ETDW = '<'.'?php return '.text::data2text( $value ).'; ?'.'>';
			return	$this->dbh->file_overwrite( $prjfile , $ETDW );
		}
		return	false;
	}
	#--------------------------------------
	#	ログインユーザのプロジェクト関連データを取得
	function get_prjdata( $key ){
		if( !$this->is_login() ){ return false; }
		if( !is_null( $this->ProjectData[$key] ) ){ return $this->ProjectData[$key]; }

		if( is_array( $this->conf->rdb_usertable ) ){
			#	$this->conf->rdb_usertable が配列だった場合、
			#	ユーザ情報をDB管理しているとみなす。
			$sql = 'SELECT keystr,valstr FROM :D:tableName WHERE user_cd = :N:user_cd AND project_id = :S:project_id AND dataspace = :S:dataspace;';
			$bindData = array(
				'tableName'=>$this->conf->rdb_usertable['project_datas'],
				'user_cd'=>$this->getuserid(),
				'project_id'=>$this->conf->info_projectid,
				'dataspace'=>$key,
			);
			$sql = $this->dbh->bind( $sql , $bindData );
			$res = $this->dbh->sendquery( $sql );
			$value = $this->dbh->getval();
			$RTN = array();
			foreach( $value as $Line ){
				$RTN[$Line['keystr']] = $Line['valstr'];
			}
			return	$RTN;
		}else{
			$userdir = $this->localconf_path_userdir.'/'.$this->getuserid();
			$prjdir = $userdir.'/projectdata/'.$this->conf->info_projectid.'/datas';
			$prjfile = $prjdir.'/'.$key.'.array';

			$data = array();
			if( $this->dbh->is_file( $prjfile ) ){
				$data = require( $prjfile );
				$this->ProjectData[$key] = $data;
				return	$data;
			}
		}
		return	null;
	}
	#--------------------------------------
	#	プロジェクトファイルディレクトリのパス
	function get_prjfilepath(){
		if( !$this->is_login() ){ return false; }
		return	$this->localconf_path_userdir.'/'.$this->getuserid().'/projectdata/'.$this->conf->info_projectid.'/files';
	}



	#--------------------------------------
	#	ユーザ属性の内容を明示的に検証
	function check(){
		if( ( !strlen( $this->user_id ) || !strlen( $this->user_cd ) ) && $this->login ){
			#	ログインされているのに、IDまたはCDがセットされていないのは、ルール違反
			return	false;
		}
		return	true;
	}

	#--------------------------------------
	#	ユーザIDが正しい形式か確認する
	function validate_user_id( $user_id ){
		if( !strlen( $user_id ) ){ return false; }
		if( strlen( $user_id ) > 64 ){ return false; }
		if( $user_id == '@_SYSTEM' ){ return false; }//予約語
		if( !preg_match( '/^[a-z0-9_\-\.\@]+$/' , $user_id ) ){ return false; }
		return	true;
	}

	#--------------------------------------
	#	最後にログインした時刻を保存する
	function save_t_lastlogin( $time = null ){
		if( !$this->is_login() ){ return false; }
		if( !$time ){ $time = $this->conf->time; }
		if( !$time ){ $time = time(); }

		if( is_array( $this->conf->rdb_usertable ) ){
			#	【DB版】
			#	$this->conf->rdb_usertable が配列だった場合、
			#	ユーザ情報をDB管理しているとみなす。
			$sql = 'UPDATE :D:tableName SET lastlogin_date = :S:lastlogin_date WHERE user_cd = :N:user_cd AND del_flg = 0;';
			$bindData = array(
				'tableName'=>$this->conf->rdb_usertable['master'],
				'lastlogin_date'=>$this->dbh->int2datetime( $time ),
				'user_cd'=>$this->getusercd(),
			);
			$sql = $this->dbh->bind( $sql , $bindData );
			$res = $this->dbh->sendquery( $sql );

			$sql = 'UPDATE :D:tableName SET lastlogin_date = :S:lastlogin_date WHERE user_cd = :N:user_cd AND project_id = :S:project_id;';
			$bindData = array(
				'tableName'=>$this->conf->rdb_usertable['project_status'],
				'lastlogin_date'=>$this->dbh->int2datetime( $time ),
				'user_cd'=>$this->getusercd(),
				'project_id'=>$this->conf->info_projectid,
			);
			$sql = $this->dbh->bind( $sql , $bindData );
			$res = $this->dbh->sendquery( $sql );
			$this->dbh->commit();
			return	true;

		}else{
			#	【ファイル版】
			$userdir = $this->localconf_path_userdir.'/'.$this->getuserid();
			$project_id = $this->localconf_info_projectid;
			if( strlen( $project_id ) && $this->dbh->is_dir( $userdir.'/projectdata/'.$project_id ) ){
				return	$this->dbh->file_overwrite( $userdir.'/projectdata/'.$project_id.'/T_lastlogin.txt' , $time );

			}elseif( $this->dbh->is_dir( $userdir ) ){
				$load = null;
				if( $this->dbh->is_file( $userdir.'/T_lastlogin.txt' ) ){
					$load = $this->dbh->file_get_lines( $userdir.'/T_lastlogin.txt' );
				}
				if( !is_array( $load ) ){ $load = array(); }
				$ETDW = '';
				$done = false;
				foreach( $load as $Line ){
					$Line = preg_replace( '/(?:\r\n|\r|\n)$/' , '' , $Line );
					list($l,$r) = explode( ',' , $Line , 2 );
					if( $l == $project_id ){
						$ETDW .= $project_id.','.$time."\n";
						$done = true;
					}else{
						$ETDW .= $l.','.$r."\n";
					}
				}
				if( !$done ){
					$ETDW .= $project_id.','.$time."\n";
				}
				return	$this->dbh->file_overwrite( $userdir.'/T_lastlogin.txt' , $ETDW );

			}
		}
		return	true;
	}

	#--------------------------------------
	#	最後にサーバ書き込みした時刻をセッションに保存する
	function save_t_lastaction( $time = null , $limit = null , $option = array() ){
		if( !is_int( $time ) && $time < 1 ){ $time = time(); }
		if( !is_int( $limit ) && $limit < 1 ){ $limit = 3; }

		if( !$this->req->getsession('T_LASTACTION') ){ $this->req->setsession('T_LASTACTION' , 0 ); }
		$lastactiontime = $this->req->getsession('T_LASTACTION');

		if( $lastactiontime > ( $time - $limit ) ){
			#	前回書き込んでから$limit秒経過していなかった場合、
			#	falseを返す。
			return	false;
		}

		$this->req->setsession('T_LASTACTION' , $time );
		return	true;
	}


	#--------------------------------------
	#	ログインIDの呼び名を得る。
	function get_label_login_id(){
		#	$this->label_login_id と同様、 Pickles Framework 0.1.4 で追加。
		return	$this->label_login_id;
	}

	#--------------------------------------
	#	UAがパブリッシュツールか調べる
	#	PxFW 0.7.0 追加
	function is_publishtool(){
		$val = strpos( $_SERVER['HTTP_USER_AGENT'] , 'PicklesCrawler' );
		if( $val !== false && $val >= 0 ){
			return true;
		}
		return false;
	}//is_publishtool()

}

?>