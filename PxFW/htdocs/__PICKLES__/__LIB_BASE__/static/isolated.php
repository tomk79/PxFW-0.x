<?php

#	Copyright (C)Tomoya Koyanagi.
#	LastUpdate : 18:59 2007/07/16

#******************************************************************************************************************
#	名前領域を隔離した状態でインクルードなどを行いたい場合に使用する
#	※インスタンス化せず、スタティックに使用してください。
class base_static_isolated{

	#==================================================================================================================
	#	require()、require_once() で読み込んだスクリプトがreturnした情報を返す。
	#	名前の領域を隔離するために使用。

	#--------------------------------------
	#	動的ファイルの読み込み
	#	読み込んで、returnされた値(HTMLソースのコンテンツ部分)を返す。
	function getrequiredreturn_once( $path , &$conf , &$req , &$dbh , &$user , &$site , &$errors , &$theme , &$custom , $src_start_str = null , $src__end__str = null , $preprocessor_flg = false ){

		if( !@is_file( $path ) ){ return false; }
		static $loadcounter = array();
		if( $loadcounter[@realpath($path)] > 0 ){ return false; }
		$loadcounter[@realpath($path)] ++;

		#--------------------------------------
		#	実行前処理(プリプロセッサ)
		#	スクリプトを配列としてロード
		if( $preprocessor_flg ){
			#	$preprocessor_flg の評価は、Pickles Framework 0.1.1 で追加されました。0:32 2007/06/28 TomK
			$gotsrclist = $dbh->file_get_lines( $path );
			if( !is_array( $gotsrclist ) ){ $gotsrclist = array(); }
			foreach( $gotsrclist as $Line ){
				if( preg_match( '/^#pre(?:pro(?:cessor)?)?[ \\t]+.*'.'/i' , $Line ) ){
					$Line = preg_replace( '/^#pre(?:pro(?:cessor)?)?[ \\t]+(.*)/i' , '\\1' , $Line );
					eval( $Line );
				}
			}
			unset( $gotsrclist );
			unset( $Line );
		}
		#	/実行前処理(プリプロセッサ)
		#--------------------------------------

		#--------------------------------------
		#	出力バッファリング
		ob_start();
		$required_return = @include( $path );
		$contents = @ob_get_clean();
		#	/出力バッファリング
		#--------------------------------------

		if( is_int($required_return) ){
			$required_return = '';
		}

		$contents .= $required_return;
		$contents = preg_replace( '/\r\n|\r|\n/' , "\n" , $contents );

		#	必要部分を抜き出し
		if( strlen( $src_start_str ) && strlen( $src__end__str ) ){
			$preg_ptn = '/'.preg_quote( $src_start_str , '/' ).'(.*)'.preg_quote( $src__end__str , '/' ).'/s';
			if( preg_match( $preg_ptn , $contents , $results ) ){
				$contents = $results[1];
			}
		}

		return	$contents;
	}


	#--------------------------------------
	#	静的ファイルの読み込み
	#	読み込んで、コンテンツ部分を返す。
	function getrequiredreturn_static( $path , &$conf , &$req , &$dbh , &$user , &$site , &$errors , &$theme , &$custom , $src_start_str = null , $src__end__str = null ){
		$path = @realpath($path);
		if( !@is_file( $path ) || !$path ){ return false; }

		$contents = file_get_contents( $path );
		$contents = preg_replace( '/\r\n|\r|\n/' , "\n" , $contents );

		#	必要部分を抜き出し
		if( strlen( $src_start_str ) && strlen( $src__end__str ) ){
			$preg_ptn = '/'.preg_quote( $src_start_str , '/' ).'(.*)'.preg_quote( $src__end__str , '/' ).'/s';
			if( preg_match( $preg_ptn , $contents , $results ) ){
				$contents = $results[1];
			}
		}

		return	$contents;
	}

	#--------------------------------------
	#	コンフィグをセットした状態で読み込み
	function require_with_conf( $path , &$conf ){
		if( !@is_file( $path ) ){ return false; }
		return	require( $path );
	}
	function require_once_with_conf( $path , &$conf ){
		if( !@is_file( $path ) ){ return false; }
		return	require_once( $path );
	}
	function include_with_conf( $path , &$conf ){
		if( !@is_file( $path ) ){ return false; }
		return	@include( $path );
	}
	function include_once_with_conf( $path , &$conf ){
		if( !@is_file( $path ) ){ return false; }
		return	@include_once( $path );
	}

}


?>