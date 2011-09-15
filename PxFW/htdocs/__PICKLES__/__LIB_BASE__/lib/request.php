<?php

#	Copyright (C)Tomoya Koyanagi.
#	LastUpdate : 15:40 2010/07/21

#----------------------------------------------------------------------------
#	リクエスト情報を扱うクラス
class base_lib_request{

	var $conf;

	var $localconf_pkey = 'P';

	var $in;
	var $pElm;
	var $pvalue;
	var $gene;

	var $flg_ssl = false;//SSLか否かのフラグ
	var $flg_cmd = false;//コマンドラインから実行しているかフラグ

	var $urlmap = null; //PxFW 0.7.0 : $conductor から参照する可能性があるため、記憶することにした。

	function setup( &$conf ){
		$this->conf = &$conf;

		$this->in = array();
		$this->parse_input();
		$this->pElm = array();
		$this->pvalue = array();
		$this->gene = array();
	}

	#******************************************************************************************************************
	#	【 *  リクエスト加工/解析系  * 】

	#---------------------------------------------------------------------------
	#	$_POSTと$_GETで受け取った情報を、ハッシュ$inに結合する。
	function parse_input(){
		if( !array_key_exists( 'REMOTE_ADDR' , $_SERVER ) ){//←PicklesFramework0.2.4 修正
			#--------------------------------------
			#	コマンドラインからの実行か否か判断
			$this->flg_cmd = true;//コマンドラインから実行しているかフラグ
			if( is_array( $_SERVER['argv'] ) && count( $_SERVER['argv'] ) ){
				foreach( $_SERVER['argv'] as $argv_line ){
					foreach( explode( '&' , $argv_line ) as $argv_unit ){
						preg_match( '/^(.*?)=(.*)$/ism' , $argv_unit , $argv_preg_result );
						if( array_key_exists( 1 , $argv_preg_result ) && strlen( $argv_preg_result[1] ) ){
							$_GET[urldecode($argv_preg_result[1])] = urldecode($argv_preg_result[2]);
						}else{
							//↓PicklesFramework0.2.4 追記
							$_GET[$argv_unit] = '';
						}
					}
				}
				unset( $argv_line , $argv_preg_result );
			}
		}

		if( array_key_exists( 'HTTPS' , $_SERVER ) && !is_null( $_SERVER['HTTPS'] ) ){//←PicklesFramework0.2.4 修正
			#--------------------------------------
			#	SSL通信が有効か否か判断
			$this->flg_ssl = true;
		}

		if( ini_get('magic_quotes_gpc') ){
			#	PHPINIのmagic_quotes_gpc設定がOnだったら、
			#	エスケープ文字を削除。
			foreach( array_keys( $_GET ) as $Line ){
				$_GET[$Line] = text::stripslashes( $_GET[$Line] );
			}
			foreach( array_keys( $_POST ) as $Line ){
				$_POST[$Line] = text::stripslashes( $_POST[$Line] );
			}
		}

		$_GET = text::convert_encoding( $_GET );
		$_POST = text::convert_encoding( $_POST );
		$in = array_merge( $_GET , $_POST );
		$in = $this->input_default_convert( $in );//PxFW 0.6.1 別メソッドに分離

		if( is_array( $_FILES ) ){
			$FILES_KEYS = array_keys( $_FILES );
			foreach($FILES_KEYS as $Line){
				$_FILES[$Line]['name'] = text::convert_encoding( $_FILES[$Line]['name'] );
				$_FILES[$Line]['name'] = mb_convert_kana( $_FILES[$Line]['name'] , 'KV' , mb_internal_encoding() );
				$in[$Line] = $_FILES[$Line];
			}
		}

		$this->in = $in;
		unset($in);
		return	true;
	}

	#----------------------------------------------------------------------------
	#	入力値に対する標準的な変換事項
	function input_default_convert( $in ){
		#	PxFW 0.6.1 追加。0:04 2009/05/30
		$is_callable_mb_check_encoding = is_callable( 'mb_check_encoding' );
		foreach( $in as $key=>$val ){
			#	URLパラメータを加工
			if( is_array( $val ) ){
				#	配列なら
				$in[$key] = $this->input_default_convert( $in[$key] );
			}elseif( is_string( $in[$key] ) ){
				#	文字列なら
				$in[$key] = mb_convert_kana( $in[$key] , 'KV' , mb_internal_encoding() );
					//半角カナは全角に統一
				$in[$key] = preg_replace( '/\r\n|\r|\n/' , "\n" , $in[$key] );
					//改行コードはLFに統一
				if( $is_callable_mb_check_encoding ){
					#	PxFW 0.6.6 : 追加
					#	不正なバイトコードのチェック
					if( !mb_check_encoding( $key , mb_internal_encoding() ) ){
						#	キーの中に見つけたらパラメータごと削除
						unset( $in[$key] );
					}
					if( !mb_check_encoding( $in[$key] , mb_internal_encoding() ) ){
						#	値の中に見つけたら false に置き換える
						$in[$key] = false;
					}
				}
			}
		}
		return	$in;
	}

	#----------------------------------------------------------------------------
	#	リクエスト情報を得る
	function in( $key = null ){
		if( is_null( $key ) ){ return $this->in; }
		if( !array_key_exists( $key , $this->in ) ){
			#	Pickles Framework 0.2.4 追記
			return	null;
		}
		return	$this->in[$key];
	}
	#----------------------------------------------------------------------------
	#	リクエスト情報を追加する
	function setin( $key , $value = null ){
		$this->in[$key] = $value;
		if( is_null( $this->in[$key] ) ){
			#	PxFW 0.6.7 : $value に null を受け取ったら要素を削除するようにした。
			unset( $this->in[$key] );
		}
		return true;#	PxFW 0.6.7 : 常に true を返すようになった。
	}
	#----------------------------------------------------------------------------
	#	加工する前のリクエスト情報を得る
	function in_original( $key = null ){
		if( $key === null )	{ return null; }
		if( !is_null( $_GET[$key] ) )   { return $_GET[$key];   }
		if( !is_null( $_POST[$key] ) )  { return $_POST[$key];  }
		if( !is_null( $_FILES[$key] ) ) { return $_FILES[$key]; }
		return	$this->in( $key );
	}

	#---------------------------------------------------------------------------
	#	ページロケーション情報を分解し、$pElmに格納する。
	function parse_p(){
		$key = $this->pkey();
		$PATH_INFO = $_SERVER['PATH_INFO'];

		#--------------------------------------
		#	まず、静的URLを採用。(優先度最低)
		$pstring = $PATH_INFO;

		if( strlen( $this->in($key) ) ){
			#--------------------------------------
			#	動的URLの指定があれば優先
			$pstring = $this->in($key);
			$pstring = preg_replace( '/\.|\.\//' , '/' , $pstring );
		}

		if( @is_file( $this->conf->path_cache_dir.'/sitemap/urlmap.cache' ) ){
			#--------------------------------------
			#	URLマップの採用合否反転( ヒットしたなら最優先 )

			#	※$conf->enable_urlmap オプションは、PxFW 0.7.0 で廃止されました。

			$this->urlmap = @include( $this->conf->path_cache_dir.'/sitemap/urlmap.cache' );
			if( array_key_exists( $PATH_INFO , $this->urlmap ) ){// PxFW 0.6.7 : 条件式を変更(トップページにURLマップを使用できない不具合の修正)
				$PATH_INFO = preg_replace( '/\./' , '/' , $this->urlmap[$PATH_INFO] );
				$pstring = $PATH_INFO;
			}
//			unset( $this->urlmap );
		}

		#	この時点では、
		#	$pstringはpElmをスラッシュで区切った文字列です。

		$pstring = preg_replace('/^\/+/ism' , '' , $pstring);	//先頭のスラッシュを削除
		$pstring = preg_replace('/\/+$/ism' , '' , $pstring);	//最後のスラッシュを削除
		$pElm = explode( '/' , $pstring );
		$this->pElm = array();//reset
		foreach( $pElm as $Line ){
			if( preg_match( '/\./' , $Line ) ){
				$tmp_basename = explode('.',$Line);
				$tmp_defoname = explode('.',$this->conf->default_filename);
				if( $tmp_basename[0] != $tmp_defoname[0] ){
					#	デフォルトのファイル名と、
					#	最初のドットまでの文字列が異なる場合に限り、
					#	ファイル名をページIDの一部として捉える。
					#	PxFW 0.6.7 仕様変更。
					array_push( $this->pElm , $tmp_basename[0] );
				}
				break;
			}
			array_push( $this->pElm , $Line );
		}

		$this->setin( $this->pkey() , implode( '.' , $this->pElm ) );

		return	true;
	}
	#---------------------------------------------------------------------------
	#	ページロケーション情報のキーを返す。
	function pkey(){
		return	$this->localconf_pkey;
	}

	#---------------------------------------------------------------------------
	#	$in['P']の値から、ページIDを取得
	function p(){
		if( !is_array( $this->pElm ) ){ $this->pElm = array(); }
		$RTN = implode( '.' , $this->pElm );
		$RTN = preg_replace( '/\.*$/' , '' , $RTN );
		return	$RTN;
	}
	#---------------------------------------------------------------------------
	#	$pElmの値を取得
	function pelm( $pnum = 0 ){
		if( $pnum < 0 ){
			#	負の値を受けたら、後ろから数える
			$pnum = count( $this->pElm ) + $pnum;
		}
		return	$this->pElm[$pnum];
	}

	#---------------------------------------------------------------------------
	#	$pvalueの値をセット
	function parse_pv( &$site ){
		if( !is_object( $site ) ){ return false; }
		$RTN = array();
		$original_pElm = array();
		$pvalue = array();
		$pid = '';

		$original_pElm = $this->pElm;

		while( count( $original_pElm ) ){
			$pid = implode( '.' , $original_pElm );
			if( $site->getpageinfo( $pid , 'id' ) == $pid ){
				#	存在するが表示できないページを考慮 Pickles Framework 0.2.1
				if(
					( strlen( $site->getpageinfo( $pid , 'release_date' ) ) && $site->getpageinfo( $pid , 'release_date' ) > $this->conf->time )	#	公開日が未来だったら
					|| ( strlen( $site->getpageinfo( $pid , 'close_date' ) ) && $site->getpageinfo( $pid , 'close_date' ) < $this->conf->time )		#	終了日が過去だったら
					|| ( !$site->getpageinfo( $pid , 'public_flg' ) && !$this->conf->show_invisiblepage )											#	公開フラグがオフ、かつ、$conf->show_invisiblepage フラグがオフなら
						//	(Pickles Framework 0.5.2 : show_invisiblepage の有効範囲を $public_flg だけに限定)
				){
				}else{
					break;
				}

			}

			#	ページが存在しなければ
			array_unshift( $pvalue , array_pop( $original_pElm ) );
		}
		$this->pvalue = $pvalue;
		$this->original_pElm = $original_pElm;
		if( !count( $this->original_pElm ) ){
			$this->original_pElm = array( '' );
		}
		return	$pvalue;
	}

	#---------------------------------------------------------------------------
	#	$pvalueの値を取得
	function pv(){
		if( !is_array( $this->pvalue ) ){ $this->pvalue = array(); }
		$RTN = implode( '.' , $this->pvalue );
		$RTN = preg_replace( '/\.*$/' , '' , $RTN );
		return	$RTN;
	}
	#---------------------------------------------------------------------------
	#	$pvalueの値を取得
	function pvelm( $pnum = 0 ){
		if( $pnum < 0 ){
			#	負の値を受けたら、後ろから数える
			$pnum = count( $this->pvalue ) + $pnum;
		}
		return	$this->pvalue[$pnum];
	}

	#---------------------------------------------------------------------------
	#	$original_pElmの値を取得
	function po(){
		if( !is_array( $this->original_pElm ) ){ $this->original_pElm = array(); }
		$RTN = implode( '.' , $this->original_pElm );
		$RTN = preg_replace( '/\.*$/' , '' , $RTN );
		return	$RTN;
	}
	#---------------------------------------------------------------------------
	#	$pElmの値を取得(サイトマップに登録された状態)
	function poelm( $pnum = 0 ){
		if( $pnum < 0 ){
			#	負の値を受けたら、後ろから数える
			$pnum = count( $this->original_pElm ) + $pnum;
		}
		return	$this->original_pElm[$pnum];
	}



	#******************************************************************************************************************
	#	【 *  URL作成系  * 】

	#--------------------------------------
	#	$gene 情報を取得
	function gene( $type = 'a' ){
		$type = strtolower( $type );
		switch( $type ){
			case 'a':
			case 'ah':
			case 'an'://Pickles Framework 0.4.0 追加
			case 'form':
			case 'array'://Pickles Framework 0.4.0 追加
				break;
			default:
				$type = 'a';
				break;
		}

		if( $type == 'array' ){
			return	$this->gene;
		}

		$RTN = array();
		foreach( $this->gene as $key=>$val ){
			if( $type == 'form' ){
				array_push( $RTN , '<input type="hidden" name="'.htmlspecialchars( $key ).'" value="'.htmlspecialchars( $val ).'" />' );
			}else{
				array_push( $RTN , urlencode( $key ).'='.urlencode( $val ) );
			}
		}
		if( $type == 'form' ){
			return	implode( '' , $RTN );
		}
		$RTN = implode( '&' , $RTN );
		if( $type == 'a' ){
			$RTN = '&'.$RTN;
		}elseif( $type == 'ah' ){
			$RTN = '?'.$RTN;
		}elseif( $type == 'an' ){
			#	何も付けない
		}

		return	$RTN;
	}
	#--------------------------------------
	#	$gene から、指定したキーの要素の値を得る
	function gene_elm( $gene_key ){
		return	$this->gene[$gene_key];
	}
	#--------------------------------------
	#	$gene に、値を追加
	function addgene( $key , $val ){
		if( $key == 'PW' ){ return false; }
			//パスワードは引き回せません。Pickles Framework 0.1.11 追記
		$this->gene[$key] = $val;
		return	true;
	}
	#--------------------------------------
	#	$gene から、値を削除
	function delgene( $key ){
		unset( $this->gene[$key] );
		return	true;
	}
	#--------------------------------------
	#	$gene から、値を一時的に削除して取得
	function gene_deltemp( $delkeys , $type = 'a' ){
		#	Pickles Framework 0.4.0 : 実装しなおした。
		$gene_memo = $this->gene;//←geneをバックアップ
		if( !is_array( $delkeys ) ){ $delkeys = array(); }
		foreach( $delkeys as $key ){
			$this->delgene( $key );
		}
		$RTN = $this->gene( $type );
		$this->gene = $gene_memo;//←バックアップからgeneを復元
		return	$RTN;

	}

	#******************************************************************************************************************
	#	【 *  クッキー処理系  * 】

	#--------------------------------------
	#	クッキー情報を取得
	function getcookie( $key ){
		return	$_COOKIE[$key];
	}
	#--------------------------------------
	#	クッキー情報をセット
	function setcookie( $key , $val = null , $expire = null , $path = null , $domain = null , $secure = false ){
		if( is_null( $path ) ){
			$path = $this->conf->url_root;
			if( !strlen( $path ) ){ $path = '/'; }
		}
		if( !@setcookie( $key , $val , $expire , $path , $domain , $secure ) ){
			return	false;
		}

		$_COOKIE[$key] = $val;//現在の処理からも呼び出せるように
		return	true;
	}
	#--------------------------------------
	#	クッキー情報を削除
	function delcookie( $key ){
		if( !@setcookie( $key , null ) ){
			return	false;
		}
		unset( $_COOKIE[$key] );
		return	true;
	}

	#******************************************************************************************************************
	#	【 *  セッション処理系  * 】

	#--------------------------------------
	#	セッションを開始
	#	※もともと base_lib_user にあったメソッドを移動して来ました。
	function session_start( $sid = null , $session_name = null , $expire = null , $cache_limiter = null ){
		#--------------------------------------
		#	有効期限
		if( !strlen( $expire ) ){
			#	セッション有効期限のデフォルト値
			$expire = $this->conf->session_expire;
		}
		if( !strlen( $expire ) ){
			#	メソッド内のデフォルト値
			$expire = 1800;#(30分)
		}

		#--------------------------------------
		#	セッション名(キー)
		if( !strlen( $session_name ) ){
			#	セッション名のデフォルト値
			$session_name = $this->conf->session_key;
		}
		if( !strlen( $session_name ) ){
			#	メソッド内のデフォルト値
			$session_name = 'PXSID';
		}

		#--------------------------------------
		#	キャッシュリミッタ
		if( !strlen( $cache_limiter ) ){
			#	キャッシュリミットのデフォルト値
			$cache_limiter = $this->conf->session_cache_limiter;
		}
		if( !strlen( $cache_limiter ) ){
			#	メソッド内のデフォルト値
			$cache_limiter = 'nocache';
		}

		session_name( $session_name );
		session_cache_limiter( $cache_limiter );
		session_cache_expire( intval($expire/60) );

		if( intval( ini_get( 'session.gc_maxlifetime' ) ) < $expire + 10 ){
			#	ガベージコレクションの生存期間が
			#	$expireよりも短い場合は、上書きする。
			#	バッファは固定値で10秒。
			ini_set( 'session.gc_maxlifetime' , $expire + 10 );
		}

		if( strlen( $conf->session_save_path ) ){
			#	セッションファイルを保存するパスを指定。
			#	設定オブジェクトに値がある場合のみ設定する。
			session_save_path( $conf->session_save_path );
		}

		#	有効範囲(パス)を決定
		$path = '/';
		if( strlen( $this->conf->url_root ) ){
			$path = $this->conf->url_root;
		}

		session_set_cookie_params( 0 , $path );
			//  セッションクッキー自体の寿命は定めない(=0) PxFW 0.6.8 変更
			//  そのかわり、SESSION_LAST_MODIFIED を新設し、自分で寿命を管理する。

		if( strlen( $sid ) ){
			#	セッションIDに指定があれば、有効にする。
			session_id( $sid );
		}

		#--------------------------------------
		#	セッションを開始
		$RTN = @session_start();

		#--------------------------------------
		#	セッションの有効期限を評価
		if( strlen( $this->getsession( 'SESSION_LAST_MODIFIED' ) ) && intval( $this->getsession( 'SESSION_LAST_MODIFIED' ) ) < intval( time() - $expire ) ){
			#	セッションの有効期限が切れていたら、セッションキーを再発行。
			#	SESSION_LAST_MODIFIED => PxFW 0.6.8 追加
			if( is_callable('session_regenerate_id') ){
				@session_regenerate_id( true );
			}
		}
		$this->setsession( 'SESSION_LAST_MODIFIED' , time() );
		return $RTN;
	}

	#--------------------------------------
	#	セッションIDを取得
	function getsessionid(){
		return session_id();
	}
	#--------------------------------------
	#	セッション情報を取得
	function getsession( $key = null ){
		if( is_null( $key ) ){ return	$_SESSION; }
		return	$_SESSION[$key];
	}
	#--------------------------------------
	#	セッション情報をセット
	function setsession( $key , $val = null ){
		$_SESSION[$key] = $val;
		return	true;
	}
	#--------------------------------------
	#	セッション情報を削除
	function delsession( $key ){
		unset( $_SESSION[$key] );
		return	true;
	}

	#--------------------------------------
	#	セッションにアップロードされたファイルを保存
	function save_uploadfile( $key , $ulfileinfo ){
		#	base64でエンコードして、バイナリデータを持ちます
		$fileinfo = array();
		$fileinfo['name'] = $ulfileinfo['name'];
		$fileinfo['type'] = $ulfileinfo['type'];

		if( $ulfileinfo['content'] ){
			$fileinfo['content'] = base64_encode( $ulfileinfo['content'] );
		}else{
			$filepath = '';
			if( @is_file( $ulfileinfo['tmp_name'] ) ){
				$filepath = $ulfileinfo['tmp_name'];
			}elseif( @is_file( $ulfileinfo['path'] ) ){
				$filepath = $ulfileinfo['path'];
			}
			$fileinfo['content'] = base64_encode( file_get_contents( $filepath ) );
		}
		$_SESSION['FILE'][$key] = $fileinfo;
		return	false;
	}
	#--------------------------------------
	#	セッションに保存されたファイル情報を取得
	function get_uploadfile( $key , $option = array() ){
		$RTN = $_SESSION['FILE'][$key];
		if( is_null( $RTN ) ){
			return	null;
		}
		$RTN['content'] = base64_decode( $RTN['content'] );
		return	$RTN;
	}
	#--------------------------------------
	#	セッションに保存されたファイル情報の一覧を取得
	function get_uploadfile_list(){
		return	array_keys( $_SESSION['FILE'] );
	}
	#--------------------------------------
	#	セッションに保存されたファイルを削除
	function delete_uploadfile( $key ){
		unset( $_SESSION['FILE'][$key] );
		return	true;
	}
	#--------------------------------------
	#	セッションに保存されたファイルを全て削除
	function delete_uploadfile_all(){
		return	$this->delsession( 'FILE' );
	}



	#******************************************************************************************************************
	#	【 *  その他  * 】

	#--------------------------------------
	#	SSL通信か確認する。
	function is_ssl(){
		if( !$this->flg_ssl ){ return false; }
		return	true;
	}

	#--------------------------------------
	#	コマンドラインによる実行か確認する。
	function is_cmd(){
		if( !$this->flg_cmd ){ return false; }
		return	true;
	}

	#--------------------------------------
	#	DBHをメンバーに加える
	function putdbh( &$dbh ){
		if(!is_object($dbh)){return	false;}
		$this->dbh = &$dbh;
		return	true;
	}

	#----------------------------------------------------------------------------
	#	アクセスログを保存する
	function save_accesslog( &$dbh , &$user ){
		#	エラーログ保存先の設定を検証
		if( is_null( $this->conf->access_log_path ) )	{ return	false; }
		if( !strlen( $this->conf->access_log_path ) )	{ return	false; }

		#	Log保存先ディレクトリを検証。なければfalseを返す。
		$savetodir = @realpath( $this->conf->access_log_path );
		if( !@is_dir( $savetodir ) ){ return false; }
		if( !@is_writable( $savetodir ) ){ return false; }

		#--------------------------------------
		#	保存する文字列
		$accesslog = $this->accesslog_format( &$dbh , &$user );
		$ETDW = '';
		foreach( $accesslog as $Line ){
			$ETDW .= $Line['value'].'	';
		}

		$ETDW .= "\n";
		#	/ 保存する文字列
		#--------------------------------------

		#--------------------------------------
		#	アクセスログをファイルに保存
		if( strlen( $ETDW ) ){
			$filename = 'access.log';
			switch( $this->conf->access_log_rotate ){
				case 'yearly':
					$filename = 'access_'. date( 'Y' , time() ).'.log';break;
				case 'monthly':
					$filename = 'access_'. date( 'Ym' , time() ).'.log';break;
				case 'daily':
					$filename = 'access_'. date( 'Ymd' , time() ).'.log';break;
				case 'hourly':
					$filename = 'access_'. date( 'Ymd_H' , time() ).'.log';break;
			}

			$dbh->savefile_push( $savetodir.'/'.$filename , $ETDW );
		}

		return	true;
	}

	#----------------------------------------------------------------------------
	#	アクセスログのフォーマット
	function accesslog_format( &$dbh , &$user ){
		$RTN = array();
		array_push( $RTN , array( 'name'=>'time'			, 'value'=>date( 'Y-m-d H:i:s' , time() ) ) );//PxFW 0.6.1 日付のフォーマットを変えた
		array_push( $RTN , array( 'name'=>'session_id'		, 'value'=>session_id() ) );
		array_push( $RTN , array( 'name'=>'user_id'			, 'value'=>$user->getuserid() ) );
		array_push( $RTN , array( 'name'=>'path_info'		, 'value'=>$_SERVER['PATH_INFO'] ) );
		array_push( $RTN , array( 'name'=>'po'				, 'value'=>$this->po() ) );
		array_push( $RTN , array( 'name'=>'pv'				, 'value'=>$this->pv() ) );
		array_push( $RTN , array( 'name'=>'http_user_agent'	, 'value'=>$_SERVER['HTTP_USER_AGENT'] ) );
		array_push( $RTN , array( 'name'=>'client_type'		, 'value'=>$user->get_ct().'/'.$user->get_enduserclass() ) );
		array_push( $RTN , array( 'name'=>'bsr'				, 'value'=>$user->UA->get_browser_name() ) );
		array_push( $RTN , array( 'name'=>'bsrv'			, 'value'=>$user->UA->get_browser_version() ) );
		array_push( $RTN , array( 'name'=>'http_referer'	, 'value'=>$_SERVER['HTTP_REFERER'] ) );
		array_push( $RTN , array( 'name'=>'remote_addr'		, 'value'=>$_SERVER['REMOTE_ADDR'] ) );
		array_push( $RTN , array( 'name'=>'appspeed'		, 'value'=>round( time::microtime() - $this->conf->microtime , 4 ) ) );

		$memo = '';
		foreach( array_keys( $_GET ) as $Line ){
			$memo .= '&'.urlencode( mb_strimwidth( $Line , 0 , 20 ) ).'='.urlencode( mb_strimwidth( $_GET[$Line] , 0 , 20 ) );
		}
		array_push( $RTN , array( 'name'=>'_GET' , 'value'=>mb_strimwidth( $memo , 0 , 256 ) ) );

		return	$RTN;
	}

}

?>