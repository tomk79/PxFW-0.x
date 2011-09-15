<?php

#	Copyright (C)Tomoya Koyanagi.
#	Last Update : 0:28 2008/02/09

require_once( $conf->path_lib_base.'/resources/viewstyle/mkhx.php' );

#******************************************************************************************************************
#	mk_hx()関数の表示スタイル管理
class base_resources_viewstyle_mkhx_MP extends base_resources_viewstyle_mkhx{

	#========================================================================================================================================================
	#	出力するスタイルを定義

	#--------------------------------------
	#	標準の見出し
	function style_( $title , $hx = 2 , $args = array() ){
		if( $hx <= 2 ){
			$RTN .= '<div>';
			$RTN .= '<font size="+1">';
			$RTN .= '<font color="'.htmlspecialchars( $this->theme->getcolor('KEY') ).'">■</font> ';
			$RTN .= ''.$title.'';
			$RTN .= '</font>';
			$RTN .= '</div>';
			$RTN .= '<font size="1"><br /></font>';
			return	$RTN;
		}elseif( $hx == 3 ){
			return	'<div><font color="'.htmlspecialchars( $this->theme->getcolor('KEY') ).'">*</font> '.$title.'</div><font size="1"><br /></font>';
		}elseif( $hx == 4 ){
			return	'<div><font color="'.htmlspecialchars( $this->theme->getcolor('KEYT') ).'">*</font> '.$title.'</div><font size="1"><br /></font>';
		}
		return	'<div>'.$title.'</div><font size="1"><br /></font>';
	}

	#--------------------------------------
	#	プレーン
	function style_plain( $title , $hx = 2 , $args = array() ){
		return	'<div><font size="+1">'.$title.'</font></div><font size="1"><br /></font>';
	}

}

?>