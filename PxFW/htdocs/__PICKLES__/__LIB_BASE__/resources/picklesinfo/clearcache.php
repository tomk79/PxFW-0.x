<?php

#	Copyright (C)Tomoya Koyanagi.
#	Last Update : 11:55 2010/11/15

#******************************************************************************************************************
#	プロジェクトのキャッシュディレクトリを空にする
#	Pickles Framework 0.2.0 追加
class base_resources_picklesinfo_clearcache{

	var $conf;
	var $counter = array();//PxFW 0.6.12 追加

	#----------------------------------------------------------------------------
	#	コンストラクタ
	function base_resources_picklesinfo_clearcache( &$conf ){
		$this->conf = &$conf;
	}


	#----------------------------------------------------------------------------
	#	処理の実行開始
	function execute(){
		if( strlen( $this->conf->php_mb_internal_encoding ) ){
			#	内部エンコード調整
			mb_internal_encoding( $this->conf->php_mb_internal_encoding );
		}

		$this->counter = array(//PxFW 0.6.12 : 処理した数を数えるようになった。
			'file'=>0,
			'dir'=>0,
			'failed'=>0,
			'passed'=>0,
		);

		while( @ob_end_clean() );
		@header( 'Content-type: text/plain; charset='.mb_internal_encoding() );

		print	'------------------------------------------------------------------------------'."\n";
		print	'---- PICKLESINFO Service: clearcache ----'."\n";
		print	''."\n";

		$path_cache_dir = null;
		if( strlen( $this->conf->path_cache_dir ) ){
			$path_cache_dir = @realpath( $this->conf->path_cache_dir );
		}
		print	'Cache Directory:    '.$path_cache_dir."\n";

		$path_localresourcecache_dir = null;
		if( strlen( $this->conf->path_docroot ) && strlen( $this->conf->url_localresource ) ){
			$path_localresourcecache_dir = @realpath( $this->conf->path_docroot.'/'.$this->conf->url_localresource );
		}
		print	'Local Resource Cache Directory: '.$path_localresourcecache_dir."\n";

		$path_themeresourcecache_dir = null;
		if( strlen( $this->conf->path_docroot ) && strlen( $this->conf->url_themeresource ) ){
			$path_themeresourcecache_dir = @realpath( $this->conf->path_docroot.'/'.$this->conf->url_themeresource );
		}
		print	'Theme Resource Cache Directory: '.$path_themeresourcecache_dir."\n";

		print	'------------------------------------------------------------------------------'."\n";
		print	'[---- cleaning: Cache Directory ----]'."\n";
		if( !strlen( $path_cache_dir ) ){
			print	'[ERROR] Directory is NOT available.'."\n";
		}elseif( !@is_dir( $path_cache_dir ) ){
			print	'[ERROR] Directory does NOT Exist.'."\n";
		}else{
			$this->empty_directory( $path_cache_dir );
		}
		print	''."\n";

		print	'------------------------------------------------------------------------------'."\n";
		print	'[---- cleaning: Local Resource Cache Directory ----]'."\n";//Pickles Framework 0.2.1 追加
		if( !strlen( $path_localresourcecache_dir ) ){
			print	'[ERROR] Directory is NOT available.'."\n";
		}elseif( !@is_dir( $path_localresourcecache_dir ) ){
			print	'[ERROR] Directory does NOT Exist.'."\n";
		}else{
			$this->empty_directory( $path_localresourcecache_dir );
		}
		print	''."\n";

		print	'------------------------------------------------------------------------------'."\n";
		print	'[---- cleaning: Theme Resource Cache Directory ----]'."\n";//Pickles Framework 0.3.3 追加
		if( !strlen( $path_themeresourcecache_dir ) ){
			print	'[ERROR] Directory is NOT available.'."\n";
		}elseif( !@is_dir( $path_themeresourcecache_dir ) ){
			print	'[ERROR] Directory does NOT Exist.'."\n";
		}else{
			$this->empty_directory( $path_themeresourcecache_dir );
		}
		print	''."\n";

		print	''."\n";
		print	'Operation done.'."\n";
		print	''."\n";
		print	'[results...]'."\n";
		print	'file(s) = '.intval($this->counter['file']).';'."\n";
		print	'dir(s)  = '.intval($this->counter['dir']).';'."\n";
		print	'failed  = '.intval($this->counter['failed']).';'."\n";
		print	'passed  = '.intval($this->counter['passed']).';'."\n";
		print	''."\n";
		print	'bye!'."\n";
		print	''."\n";
		print	'----  / PICKLESINFO Service: clearcache --'."\n";
		print	'------------------------------------------------------------------------------'."\n";
		print	"\n";
		return	true;
	}




	#----------------------------------------------------------------------------
	#	ディレクトリを空っぽにする。
	function empty_directory( $path ){
		if( !@is_dir( $path ) ){
			return	false;
		}
		$filelist = $this->getfilelist( $path );
		if( !is_array( $filelist ) ){ $filelist = array(); }
		foreach( $filelist as $filename ){
			if( $filename == '.' || $filename == '..' ){ continue; }
			$this->rmdir( $path.'/'.$filename );
		}
		return	true;
	}


	#----------------------------------------------------------------------------
	#	ディレクトリを中身ごと完全に削除する
	function rmdir( $path ){
		#	このメソッドは、ファイルやシンボリックリンクも削除します。
		#	シンボリックリンクは、その先を追わず、
		#	シンボリックリンク本体のみを削除します。

		if( $this->conf->system_exec_mode == 'setup' ){
			#	セットアップモードが有効なとき
			return	false;
		}

		$path = @realpath( $path );
		if( $path === false ){ return false; }
		if( @is_file( $path ) || is_link( $path ) ){
			#	ファイルまたはシンボリックリンクの場合の処理
			if( preg_match('/^\./',basename($path)) ){
				//PxFW 0.6.12 追加：ドットで始まるファイルは消さない。
				print '[PASS] config -FILE ['.$path.']'."\n";
				$this->counter['passed'] ++;
				return true;
			}
			$result = @unlink( $path );
			if( $result ){
				print '[COMPLETE] delete -FILE ['.$path.']'."\n";
				$this->counter['file'] ++;
			}else{
				print '[FAILED] delete -FILE ['.$path.']'."\n";
				$this->counter['failed'] ++;
			}
			return	$result;

		}elseif( @is_dir( $path ) ){
			#	ディレクトリの処理
			if( preg_match('/^\./',basename($path)) ){
				//PxFW 0.6.12 追加：ドットで始まるディレクトリは消さない。
				print '[PASS] config -DIR ['.$path.']'."\n";
				$this->counter['passed'] ++;
				return true;
			}
			$flist = $this->getfilelist( $path );
			if( !is_array( $flist ) ){ $flist = array(); }
			foreach( $flist as $Line ){
				if( $Line == '.' || $Line == '..' ){ continue; }
				$this->rmdir( $path.'/'.$Line );
			}
			$result = @rmdir( $path );
			if( $result ){
				print '[COMPLETE] delete -DIR ['.$path.']'."\n";
				$this->counter['dir'] ++;
			}else{
				print '[FAILED] delete -DIR ['.$path.']'."\n";
				$this->counter['failed'] ++;
			}
			return	$result;

		}

		return	false;
	}


	#---------------------------------------------------------------------------
	#	ディレクトリにあるファイル名のリストを配列で返す。
	function getfilelist($path){
		$path = @realpath($path);
		if( $path === false ){ return false; }
		if( !@file_exists( $path ) ){ return false; }
		if( !@is_dir( $path ) ){ return false; }

		$RTN = array();
		$dr = opendir($path);
		while( ( $ent = readdir( $dr ) ) !== false ){
			#	CurrentDirとParentDirは含めない
			if( $ent == '.' || $ent == '..' ){ continue; }
			array_push( $RTN , $ent );
		}
		closedir($dr);
		return	$RTN;
	}

}

?>