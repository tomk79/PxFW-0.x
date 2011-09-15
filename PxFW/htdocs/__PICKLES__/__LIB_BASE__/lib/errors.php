<?php

#	Copyright (C)Tomoya Koyanagi.
#	LastUpdate : 3:05 2009/05/29

#******************************************************************************************************************
#	エラー文字列を格納するモデル
class base_lib_errors{

	var $conf;

	#----------------------------------------------------------------------------
	#	セットアップ
	function setup( &$conf ){
		$this->conf = &$conf;
	}


	#----------------------------------------------------------------------------
	#	エラーメールを発行しつつ、エラーログに残す。
	function error_mail_and_log( $message = null , $file = null , $line = null , $option = array() ){
		$debug = @debug_backtrace();
		if( is_null( $file ) || is_null( $line ) ){
			$file = $debug[0]['file'];
			$line = $debug[0]['line'];
		}

		$result['mail']	= $this->error_log( $message , $file , $line , $option );
		$result['log']	= $this->error_mail( $message , $file , $line , $option );
		foreach( $result as $resLine ){
			if( !$resLine ){
				return	false;
			}
		}
		return	true;
	}

	#----------------------------------------------------------------------------
	#	エラーログに書き出し
	function error_log( $message = null , $file = null , $line = null , $option = array() ){
		$debug = @debug_backtrace();
		if( is_null( $file ) || is_null( $line ) ){
			$file = $debug[0]['file'];
			$line = $debug[0]['line'];
		}

		if( $this->conf->system_exec_mode == 'setup' ){
			#	セットアップモードが有効なときは、
			#	ファイルなどにエラーを出力せず、画面に表示する。
			print	'<pre style="color:#ff0000; background-color:#ffffff; padding:3px;">'.htmlspecialchars( $ETDW ).' as File:'.htmlspecialchars($file).' Line: '.htmlspecialchars($line).'</pre>'."\n";
			return	true;
		}

		$time = time();
		if( is_int( $this->conf->time ) ){
			$time = $this->conf->time;
		}

		$message = preg_replace( '/\r\n|\r|\n|\t/' , ' ' , $message );

		$ETDW = '';
		$ETDW .= date( 'Y-m-d H:i:s' , $time );//PxFW 0.6.1 日付のフォーマットを変えた
		$ETDW .= '	'.$message;
		$ETDW .= '	'.$file;
		$ETDW .= '	'.$line;

		$path_logfile = $this->get_errorlog_path();
		if( !strlen( $path_logfile ) ){
			#	ログ保存の設定がされていなければ、保存しない。
			#	(保存しなくても、trueとする仕様です)
			return	true;
		}

		if( !@error_log( $ETDW."\n" , 3 , $path_logfile ) ){
			#	設定されたログファイルに書き込みが出来なかった場合
			error_log( '***** Faild to save errorlog to [ '.$path_logfile.' ]'."\n" , 0 );
			error_log( $ETDW."\n" , 0 );
		}

		clearstatcache();
		if( @is_file( $path_logfile ) ){
			$perm = $this->conf->dbh_file_default_permission;
			if( is_null( $perm ) ){ $perm = 766; }
			@chmod( $path_logfile , $perm );
		}
		return	true;
	}

	#----------------------------------------------------------------------------
	#	エラーメールを発行する
	function error_mail( $message = null , $file = null , $line = null , $option = array() ){
		$debug = @debug_backtrace();
		if( is_null( $file ) || is_null( $line ) ){
			$file = $debug[0]['file'];
			$line = $debug[0]['line'];
		}

		if( $this->conf->system_exec_mode == 'setup' ){
			#	セットアップモードが有効なときは、
			#	メールは送信せずに、画面に表示する。
			print	'<pre style="color:#ff0000; background-color:#ffffff; padding:3px;">'.htmlspecialchars( $ETDW ).' as File:'.htmlspecialchars($file).' Line: '.htmlspecialchars($line).'</pre>'."\n";
			return	true;
		}

		if( !strlen( $this->conf->email['error'] ) ){
			#	エラーメールの宛先が未指定ならエラー
			$this->error_log( '[ error_mail() エラーメールのあて先が未指定です ] '.$message , $file , $line , $option );
			return	false;
		}

		isolated::require_once_with_conf( $this->conf->path_lib_project.'/resources/mail.php' , &$this->conf );
		if( !class_exists( 'project_resources_mail' ) ){
			#	メールクラスが未定義ならエラー
			$this->error_log( 'error_mail() class [project_resources_mail] is NOT exists. : '.$message , __FILE__ , __LINE__ );
			return	false;
		}

		#	ドメイン名指定
		$domain = $this->conf->url_domain;
		if( !strlen( $domain ) ){
			$domain = $_SERVER['SERVER_NAME'];
		}

		#	エラーメールの発送
		$objMail = new project_resources_mail( &$this->conf , &$this );

		$objMail->setfrom( 'pickles@'.$domain );
		$objMail->putto( $this->conf->email['error'] );

		#	サブジェクトを作成
		$objMail->setsubject( 'Error on '.$domain );

		#	本文を作成
		$body = '';
		$body .= '[ Error on '.$domain.' ]'."\n";
		$body .= '--------------------------------------'."\n";
		$body .= $message."\n";
		$body .= '--------------------------------------'."\n";
		$body .= ''."\n";
		$body .= ''."\n";
		$body .= ''."\n";
		$objMail->setbody( $body );
		unset( $body );

		#	エラーメールを送信
		if( !$objMail->send() ){
			#	メール送信に失敗したならエラー
			$this->error_log( '[Failed to send errormail] '.$message , __FILE__ , __LINE__ );
			return	false;
		}

		return	true;
	}


	#----------------------------------------------------------------------------
	#	エラーログの吐き出し先のパスを作成する
	function get_errorlog_path(){
		#	Log保存先ディレクトリを検証。なければfalseを返す。
		if( !strlen( $this->conf->errors_log_path ) ){ return false; }
		$savetodir = @realpath( $this->conf->errors_log_path );
		if( !strlen( $savetodir ) ){ return	false; }
		if( !@is_dir( $savetodir ) ){ return	false; }

		$time = time();
		if( is_int( $this->conf->time ) )	{ $time = $this->conf->time; }

		$filename = 'errors.log';
		switch( $this->conf->errors_log_rotate ){
			case 'yearly':
				$filename = 'errors_'. date( 'Y' , $time ).'.log';break;
			case 'monthly':
				$filename = 'errors_'. date( 'Ym' , $time ).'.log';break;
			case 'daily':
				$filename = 'errors_'. date( 'Ymd' , $time ).'.log';break;
			case 'hourly':
				$filename = 'errors_'. date( 'Ymd_H' , $time ).'.log';break;
		}

		return	$savetodir.'/'.$filename;
	}

}

?>