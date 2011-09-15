<?php

#	Copyright (C)Tomoya Koyanagi.
#	Last Update : 2:10 2008/09/19

require_once( $conf->path_lib_base.'/resources/viewstyle/mkhx.php' );

#******************************************************************************************************************
#	mk_hx()関数の表示スタイル管理
class base_resources_viewstyle_mkhx_PDA extends base_resources_viewstyle_mkhx{


	#========================================================================================================================================================
	#	出力するスタイルを定義

	#--------------------------------------
	#	標準の見出し
	function style_( $title , $hx = 2 , $args = array() ){
		if( $hx <= 2 ){
			$RTN .= '<h'.$hx.' style="margin-bottom:1em; padding:3px 5px 3px 5px; background-color:#dddddd; color:#333333; ">';
			$RTN .= '<span style="color:'.htmlspecialchars( $this->theme->getcolor('KEY') ).';">■</span> ';
			$RTN .= ''.$title.'';
			$RTN .= '</h'.$hx.'>';
			return	$RTN;
		}elseif( $hx == 3 ){
			return	'<h'.$hx.' style="border-bottom:1px dashed '.htmlspecialchars( $this->theme->getcolor('KEY') ).';">* '.$title.'</h'.$hx.'>';
		}elseif( $hx == 4 ){
			return	'<h'.$hx.'>* '.$title.'</h'.$hx.'>';
		}
		return	'<h'.$hx.'>'.$title.'</h'.$hx.'>';
	}

	#--------------------------------------
	#	プレーン
	function style_plain( $title , $hx = 2 , $args = array() ){
		return	'<h'.$hx.'>'.$title.'</h'.$hx.'>';
	}

}



?>