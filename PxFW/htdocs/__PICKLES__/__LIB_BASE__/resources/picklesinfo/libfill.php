<?php

#	Copyright (C)Tomoya Koyanagi.
#	Last Update : 16:53 2010/01/27

#******************************************************************************************************************
#	ベース層ライブラリをスキャンし、パッケージ、プロジェクト層に存在しないファイルを生成する
#	Pickles Framework 0.2.0 追加
class base_resources_picklesinfo_libfill{

	var $conf;

	#----------------------------------------------------------------------------
	#	コンストラクタ
	function base_resources_picklesinfo_libfill( &$conf ){
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

		print	'---------------------------------------'."\n";
		print	'---- PICKLESINFO Service: libfill ----'."\n";
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

		$result = $this->scandir_and_fill();

		print	''."\n";
		print	'Operation done.'."\n";
		print	'bye!'."\n";
		print	''."\n";
		print	'----  / PICKLESINFO Service: libfill --'."\n";
		print	'---------------------------------------'."\n";
		print	"\n";
		return	true;
	}





	#----------------------------------------------------------------------------
	#	ディレクトリをスキャンして、足りないファイルを作成する
	function scandir_and_fill( $gotpath = null ){
		$base_path = @realpath( $this->conf->path_lib_base.$gotpath );
		if( @is_file( $base_path ) ){
			return	$this->fill_file( $gotpath );
		}elseif( @is_dir( $base_path ) ){
			$base_filelist = $this->getfilelist( $base_path );
			foreach( $base_filelist as $filename ){
				if( $filename == '_UPDATELOG_' ){ continue; }
				if( @is_dir( $this->conf->path_lib_base.$gotpath.'/'.$filename ) ){
					if( !@is_dir( $this->conf->path_lib_package.$gotpath.'/'.$filename ) ){
						print	'    [CREATE DIR PACKAGE] '.$this->conf->path_lib_package.$gotpath.'/'.$filename."\n";
						$this->mkdir( $this->conf->path_lib_package.$gotpath.'/'.$filename );
					}
					if( !@is_dir( $this->conf->path_lib_project.$gotpath.'/'.$filename ) ){
						print	'    [CREATE DIR PROJECT] '.$this->conf->path_lib_project.$gotpath.'/'.$filename."\n";
						$this->mkdir( $this->conf->path_lib_project.$gotpath.'/'.$filename );
					}
				}
				$this->scandir_and_fill( $gotpath.'/'.$filename );
			}
		}
		return	true;
	}


	#---------------------------------------------------------------------------
	#	足りないファイルを生成する
	function fill_file( $gotpath ){
		$className = preg_replace( '/\/+/' , '_' , $gotpath );
		$className = preg_replace( '/\.[0-9a-z]+$/si' , '' , $className );
		if( !@is_file( $this->conf->path_lib_package.$gotpath ) ){
			#	パッケージ層
			print	'    [CREATE PACKAGE] '.$this->conf->path_lib_package.$gotpath."\n";
			$SRC = '';
			$SRC .= '<'.'?php'."\n";
			$SRC .= '#	Created: '.date('Y-m-d H:i:s').' - libfill'."\n";
			$SRC .= 'require_once( $conf->path_lib_base.\''.$gotpath.'\' );'."\n";
			$SRC .= 'class package'.$className.' extends base'.$className.'{'."\n";
			$SRC .= '}'."\n";
			$SRC .= '?'.'>';
			$this->savefile( $this->conf->path_lib_package.$gotpath , $SRC );
		}

		if( !@is_file( $this->conf->path_lib_project.$gotpath ) ){
			#	プロジェクト層
			$classNamePROJ = 'project'.$className;
			$classNamePACK = 'package'.$className;
			if( preg_match( '/^project_static_([a-zA-Z0-9][a-zA-Z0-9\_]*)$/' , $classNamePROJ , $preg_result ) ){
				#	PicklesFramework 0.2.2 追記の処理
				$classNamePROJ = $preg_result[1];
			}
			print	'    [CREATE PROJECT] '.$this->conf->path_lib_project.$gotpath."\n";
			$SRC = '';
			$SRC .= '<'.'?php'."\n";
			$SRC .= '#	Created: '.date('Y-m-d H:i:s').' - libfill'."\n";
			$SRC .= 'require_once( $conf->path_lib_package.\''.$gotpath.'\' );'."\n";
			$SRC .= 'class '.$classNamePROJ.' extends '.$classNamePACK.'{'."\n";
			$SRC .= '}'."\n";
			$SRC .= '?'.'>';
			$this->savefile( $this->conf->path_lib_project.$gotpath , $SRC );
		}

		return	true;
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


	#--------------------------------------
	#	ファイルを保存する
	function savefile( $filepath , $CONTENT , $perm = null ){
		#	対象がディレクトリだったら開けません。
		if( @is_dir( $filepath ) ){
			return	false;
		}
		#	ファイルが存在するかどうか確認
		if( @is_file( $filepath ) ){
			#	このメソッドは、ファイルを上書きしてはいけない。
			return	false;
		}
		if( !strlen( $perm ) ){
			$perm = 0775;
		}

		#	開く
		for( $i = 0; $i < 5; $i++ ){
			$res = @fopen( $filepath , 'w' );
			if( $res ){ break; }		#	openに成功したらループを抜ける
			sleep(1);
		}
		if( !is_resource( $res ) ){ return false; }	#	5回挑戦して読み込みが成功しなかった場合、falseを返す
		flock( $res , LOCK_EX );

		#	書き込む
		fwrite( $res , $CONTENT );
		$this->chmod( $filepath , $perm );
		clearstatcache();

		#	閉じる
		flock( $res , LOCK_UN );
		fclose($res);

		return	@is_file( $filepath );
	}


	#--------------------------------------
	#	ディレクトリを作成する
	function mkdir( $dirpath , $perm = null ){
		if( @is_dir( $dirpath ) ){
			#	既にディレクトリがあったら、作成を試みない。
			$this->chmod( $dirpath , $perm );
			return	true;
		}
		$result = @mkdir( $dirpath );
		$this->chmod( $dirpath , $perm );
		return	$result;
	}


	#--------------------------------------
	#	パーミッションを変更する
	function chmod( $filepath , $perm = null ){
		if( !strlen( $perm ) ){
			$perm = 0775;
		}
		return	@chmod( $filepath , $perm );
	}


}



?>