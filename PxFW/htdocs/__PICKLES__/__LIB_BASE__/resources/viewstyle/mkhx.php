<?php

#	Copyright (C)Tomoya Koyanagi.
#	Last Update : 19:25 2009/06/11

#******************************************************************************************************************
#	mk_hx()関数の表示スタイル管理
class base_resources_viewstyle_mkhx{

	var $conf;
	var $user;
	var $site;
	var $req;
	var $dbh;
	var $theme;
	var $errors;
	var $custom;

	var $pointernum = 0;	#	get_src()が呼ばれた回数

	#----------------------------------------------------------------------------
	#	コンストラクタ
	function base_resources_viewstyle_mkhx( &$conf , &$user , &$site , &$req , &$dbh , &$theme , &$errors , &$custom ){
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
	#	見出しスタイル情報を取得する
	function get_src( $title , $hx = 2 , $style = '' , $args = array() ){
		#	このメソッドが呼ばれた回数
		if( !$this->pointernum ){ $this->pointernum = 0; }
		$this->pointernum ++;

		#	値の調整
		list( $title , $hx , $style , $args ) = $this->preprocessor( $title , $hx , $style , $args );
		$style = strtolower( $style );
		$style = preg_replace( '/[^a-zA-Z0-9_-]/' , '' , $style );
		if( !strlen( $style ) ){ $style = ''; }

		#	スタイルを探す
		if( $this->is_style_exists( $style ) ){
			return	eval( 'return	$this->style_'.$style.'( $title , $hx , $args );' );
		}
		return	$this->style_( $title , $hx , $args );
	}

	#--------------------------------------
	#	見出しスタイルが登録されているか調べる
	function is_style_exists( $style ){
		if( method_exists( $this , 'style_'.$style ) ){
			return	true;
		}
		return	false;
	}

	#--------------------------------------
	#	get_src()が受け取った値を事前加工する
	function preprocessor( $title , $hx = 2 , $style = '' , $args = array() ){
		return	array( $title , $hx , $style , $args );
	}


	#========================================================================================================================================================
	#	出力するスタイルを定義

	#--------------------------------------
	#	標準の見出し
	function style_( $title , $hx = 2 , $args = array() ){
		$option = $this->theme->parseoption( $args[2] );
		$att = '';
		if( strlen( $option['id'] ) ){
			$att .= ' id="'.htmlspecialchars($option['id']).'"';//PxFW 0.6.2 追加
		}
		if( strlen( $option['cssclass'] ) ){
			$att .= ' class="'.htmlspecialchars($option['cssclass']).'"';
		}
		if( strlen( $option['cssstyle'] ) ){
			$att .= ' style="'.htmlspecialchars($option['cssstyle']).'"';
		}

		return	'<h'.$hx.$att.'>'.$title.'</h'.$hx.'>';
	}

	#--------------------------------------
	#	プレーン
	function style_plain( $title , $hx = 2 , $args = array() ){
		$option = $this->theme->parseoption( $args[2] );
		$att = '';
		if( strlen( $option['id'] ) ){
			$att .= ' id="'.htmlspecialchars($option['id']).'"';//PxFW 0.6.2 追加
		}
		if( strlen( $option['cssclass'] ) ){
			$att .= ' class="'.htmlspecialchars($option['cssclass']).'"';
		}
		if( strlen( $option['cssstyle'] ) ){
			$att .= ' style="'.htmlspecialchars($option['cssstyle']).'"';
		}

		return	'<h'.$hx.$att.'>'.$title.'</h'.$hx.'>';
	}

}

?>