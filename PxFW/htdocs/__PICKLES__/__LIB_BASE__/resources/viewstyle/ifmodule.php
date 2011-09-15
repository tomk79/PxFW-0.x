<?php

#	Copyright (C)Tomoya Koyanagi.
#	Last Update : 17:39 2007/03/17

#******************************************************************************************************************
#	文字情報の一元管理
class base_resources_viewstyle_ifmodule{

	var $conf;
	var $user;
	var $site;
	var $req;
	var $dbh;
	var $theme;
	var $errors;
	var $custom;

	#----------------------------------------------------------------------------
	#	コンストラクタ
	function base_resources_viewstyle_ifmodule( &$conf , &$user , &$site , &$req , &$dbh , &$theme , &$errors , &$custom ){
		$this->conf = &$conf;
		$this->user = &$user;
		$this->site = &$site;
		$this->req = &$req;
		$this->dbh = &$dbh;
		$this->theme = &$theme;
		$this->errors = &$errors;
		$this->custom = &$custom;
	}

	#--------------------------------------
	#	文字情報を取得する
	function get_module( $arg_list = array() ){
		if( !is_array( $arg_list ) || !count( $arg_list ) ){
			$arg_list = array( '' );
		}
		$key = array_shift( $arg_list );
		$key = preg_replace( '/[^a-zA-Z0-9_-]/' , '' , $key );

		if( $this->is_module_exists( $key ) ){
			return	eval( 'return	$this->module_'.$key.'( $arg_list );' );
		}

		$this->errors->error_log( '[ '.$key.' ]に該当するifmoduleは登録されていません。' );
		if( $this->conf->debug_mode ){
			return	'<div style="background-color:#ff0000;color:#ffffff;padding:4px;"><strong>DebugMessage:</strong> [ '.htmlspecialchars($key).' ]に該当するifmoduleは登録されていません。</div>';
		}
		return	'';
	}

	#--------------------------------------
	#	インターフェイスモジュールが登録されているか調べる
	function is_module_exists( $key ){
		if( method_exists( $this , 'module_'.$key ) ){
			return	true;
		}
		return	false;
	}

	#--------------------------------------
	#	出力する文字情報を定義。
	#	define_string_XXXXXXX() の形式で、必要分だけ定義してください。
	#	ここで定義したメソッドは、直接コールせず、
	#	get_module()を通じてコールするようにしてください。
	function module_( $args ){
		$RTN = '';
		$RTN .= '<p class="ttr">'."\n";
		$RTN .= '	一元化する文字列を、ここ $theme->ifmodule() でカスタマイズしてください。実際には、class project_resources_viewstyle_ifmodule に文字列を登録します。<br />'."\n";
		$RTN .= '	文字列は、そのままの状態で出力されるので、HTMLシンタックスを意識した書式にしてください。<br />'."\n";
		$RTN .= '</p>'."\n";
		return	$RTN;
	}

}

?>