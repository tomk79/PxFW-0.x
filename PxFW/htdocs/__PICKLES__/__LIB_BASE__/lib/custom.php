<?php

#	Copyright (C)Tomoya Koyanagi.
#	LastUpdate : 9:40 2010/04/23

#******************************************************************************************************************
#	追加設定オブジェクトクラス(PxFW0.6.2 で新設)
class base_lib_custom{

	var $conf;
	var $req;
	var $dbh;
	var $user;
	var $site;
	var $errors;
	var $theme;//←PxFW 0.6.9 追加

	#----------------------------------------------------------------------------
	#	セットアップ
	function setup( &$conf , &$req , &$dbh , &$user , &$site , &$errors ){
		$this->conf   = &$conf;
		$this->req    = &$req;
		$this->dbh    = &$dbh;
		$this->user   = &$user;
		$this->site   = &$site;
		$this->errors = &$errors;

		//	セットアップ拡張メソッドをコール
		$this->setup_subaction();

		return true;
	}

	#----------------------------------------------------------------------------
	#	セットアップ拡張
	function setup_subaction(){
		#	用途に応じて拡張してください。
		return true;
	}

	#----------------------------------------------------------------------------
	#	テーマを受け取る
	#	PxFW 0.6.9 追加
	function set_theme( &$theme ){
		$this->theme = &$theme;
		return true;
	}

}

?>