<?php

#	Copyright (C)Tomoya Koyanagi.
#	Last Update : 9:49 2011/02/07

require_once( $conf->path_lib_base.'/resources/viewstyle/mklink.php' );

#******************************************************************************************************************
#	mk_link()関数の表示スタイル管理
class base_resources_viewstyle_mklink_MP extends base_resources_viewstyle_mklink{

	#========================================================================================================================================================
	#	出力するスタイルを定義

	#--------------------------------------
	#	標準リンク
	function style_( $href , $label , $cssclass = '' , $is_active = false , $target = '_self' , $args = array() ){
		$messages = $this->theme->parse_messagestring( $this->site->getpageinfo( $args[0] , 'message' ) );

		#======================================
		#	ターゲットウィンドウ文字列を決める
		if( $target == '_self' || !strlen( $target )){
			$target = '';
		}else{
			$target = ' target="'.htmlspecialchars( $target ).'"';
		}

		$option = $this->theme->parseoption( $args[1] );

		$att_cssclass = array();
		if( strlen( $cssclass ) ){ array_push( $att_cssclass , $cssclass ); }
		if( $is_active ){ array_push( $att_cssclass , $this->theme->classname_of_activelink ); }
		if( count( $att_cssclass ) ){ $att_cssclass = ' class="'.htmlspecialchars( implode( ' ' , $att_cssclass ) ).'"'; }
		else{ $att_cssclass = ''; }

		if( $option['cssstyle'] ){
			$att_cssstyle = ' style="'.htmlspecialchars( $option['cssstyle'] ).'"';
		}
		if( $option['accesskey'] ){
			$accesskey = ' accesskey="'.htmlspecialchars( $option['accesskey'] ).'"';
		}

		if( $this->site->getpageinfo( $args[0] , 'srcpath' ) == '(blank)' ){//PxFW 0.7.0 追加の処理
			return	'<span'.$label4title.$att_cssclass.$cssstyle.$onclick.$otherAttr.'>'.$label.'</span>';
		}
		return	'<a href="'.htmlspecialchars($href).'"'.$target.$accesskey.$att_cssclass.$att_cssstyle.'>'.$label.'</a>';
	}

	#--------------------------------------
	#	サイト内リンク
	function style_inside( $href , $label , $cssclass = '' , $is_active = false , $target = '_self' , $args = array() ){
		$messages = $this->theme->parse_messagestring( $this->site->getpageinfo( $args[0] , 'message' ) );

		#======================================
		#	ターゲットウィンドウ文字列を決める
		if( $target == '_self' || !strlen( $target )){
			$target = '';
		}else{
			$target = ' target="'.htmlspecialchars( $target ).'"';
		}

		$option = $this->theme->parseoption( $args[1] );

		$att_cssclass = array();
		if( strlen( $cssclass ) ){ array_push( $att_cssclass , $cssclass ); }
		if( $is_active ){ array_push( $att_cssclass , $this->theme->classname_of_activelink ); }
		if( count( $att_cssclass ) ){ $att_cssclass = ' class="'.htmlspecialchars( implode( ' ' , $att_cssclass ) ).'"'; }
		else{ $att_cssclass = ''; }

		if( $option['cssstyle'] ){
			$cssstyle = ' style="'.htmlspecialchars( $option['cssstyle'] ).'"';
		}
		if( $option['accesskey'] ){
			$accesskey = ' accesskey="'.htmlspecialchars( $option['accesskey'] ).'"';
		}

		if( $this->site->getpageinfo( $args[0] , 'srcpath' ) == '(blank)' ){//PxFW 0.7.0 追加の処理
			return	'<span'.$label4title.$att_cssclass.$cssstyle.$onclick.$otherAttr.'>'.$label.'</span>';
		}
		$point_img = '<font color="'.htmlspecialchars( $this->theme->getcolor('KEY') ).'">・</font> ';
		return	$point_img.'<a href="'.htmlspecialchars($href).'"'.$target.$accesskey.$att_cssclass.$cssstyle.'>'.$label.'</a>';
	}

	#--------------------------------------
	#	下層へのリンク
	function style_under( $href , $label , $cssclass = '' , $is_active = false , $target = '_self' , $args = array() ){
		$messages = $this->theme->parse_messagestring( $this->site->getpageinfo( $args[0] , 'message' ) );

		#======================================
		#	ターゲットウィンドウ文字列を決める
		if( $target == '_self' || !strlen( $target )){
			$target = '';
		}else{
			$target = ' target="'.htmlspecialchars( $target ).'"';
		}

		$option = $this->theme->parseoption( $args[1] );

		$att_cssclass = array();
		if( strlen( $cssclass ) ){ array_push( $att_cssclass , $cssclass ); }
		if( $is_active ){ array_push( $att_cssclass , $this->theme->classname_of_activelink ); }
		if( count( $att_cssclass ) ){ $att_cssclass = ' class="'.htmlspecialchars( implode( ' ' , $att_cssclass ) ).'"'; }
		else{ $att_cssclass = ''; }

		if( $option['cssstyle'] ){
			$cssstyle = ' style="'.htmlspecialchars( $option['cssstyle'] ).'"';
		}
		if( $option['accesskey'] ){
			$accesskey = ' accesskey="'.htmlspecialchars( $option['accesskey'] ).'"';
		}

		if( $this->site->getpageinfo( $args[0] , 'srcpath' ) == '(blank)' ){//PxFW 0.7.0 追加の処理
			return	'<span'.$label4title.$att_cssclass.$cssstyle.$onclick.$otherAttr.'>'.$label.'</span>';
		}
		$point_img = '<font color="'.htmlspecialchars( $this->theme->getcolor('KEY') ).'">・</font> ';
		return	$point_img.'<a href="'.htmlspecialchars($href).'"'.$target.$accesskey.$att_cssclass.$cssstyle.'>'.$label.'</a>';
	}

	#--------------------------------------
	#	カテゴリ外リンク
	function style_outside( $href , $label , $cssclass = '' , $is_active = false , $target = '_self' , $args = array() ){
		$messages = $this->theme->parse_messagestring( $this->site->getpageinfo( $args[0] , 'message' ) );

		#======================================
		#	ターゲットウィンドウ文字列を決める
		if( $target == '_self' || !strlen( $target )){
			$target = '';
		}else{
			$target = ' target="'.htmlspecialchars( $target ).'"';
		}

		$option = $this->theme->parseoption( $args[1] );

		$att_cssclass = array();
		if( strlen( $cssclass ) ){ array_push( $att_cssclass , $cssclass ); }
		if( $is_active ){ array_push( $att_cssclass , $this->theme->classname_of_activelink ); }
		if( count( $att_cssclass ) ){ $att_cssclass = ' class="'.htmlspecialchars( implode( ' ' , $att_cssclass ) ).'"'; }
		else{ $att_cssclass = ''; }

		if( $option['cssstyle'] ){
			$cssstyle = ' style="'.htmlspecialchars( $option['cssstyle'] ).'"';
		}
		if( $option['accesskey'] ){
			$accesskey = ' accesskey="'.htmlspecialchars( $option['accesskey'] ).'"';
		}

		if( $this->site->getpageinfo( $args[0] , 'srcpath' ) == '(blank)' ){//PxFW 0.7.0 追加の処理
			return	'<span'.$label4title.$att_cssclass.$cssstyle.$onclick.$otherAttr.'>'.$label.'</span>';
		}
		$point_img = '<font color="'.htmlspecialchars( $this->theme->getcolor('KEY') ).'">&gt;</font> ';
		return	$point_img.'<a href="'.htmlspecialchars($href).'"'.$target.$accesskey.$att_cssclass.$cssstyle.'>'.$label.'</a>';
	}

	#--------------------------------------
	#	サイト外リンク
	function style_exit( $href , $label , $cssclass = '' , $is_active = false , $target = '_self' , $args = array() ){
		$messages = $this->theme->parse_messagestring( $this->site->getpageinfo( $args[0] , 'message' ) );

		#======================================
		#	ターゲットウィンドウ文字列を決める
		if( $target == '_self' || !strlen( $target )){
			$target = '';
		}else{
			$target = ' target="'.htmlspecialchars( $target ).'"';
		}

		$option = $this->theme->parseoption( $args[1] );

		$att_cssclass = array();
		if( strlen( $cssclass ) ){ array_push( $att_cssclass , $cssclass ); }
		if( $is_active ){ array_push( $att_cssclass , $this->theme->classname_of_activelink ); }
		if( count( $att_cssclass ) ){ $att_cssclass = ' class="'.htmlspecialchars( implode( ' ' , $att_cssclass ) ).'"'; }
		else{ $att_cssclass = ''; }

		if( $option['cssstyle'] ){
			$cssstyle = ' style="'.htmlspecialchars( $option['cssstyle'] ).'"';
		}
		if( $option['accesskey'] ){
			$accesskey = ' accesskey="'.htmlspecialchars( $option['accesskey'] ).'"';
		}

		if( $this->site->getpageinfo( $args[0] , 'srcpath' ) == '(blank)' ){//PxFW 0.7.0 追加の処理
			return	'<span'.$label4title.$att_cssclass.$cssstyle.$onclick.$otherAttr.'>'.$label.'</span>';
		}
		$point_img = '<font color="'.htmlspecialchars( $this->theme->getcolor('KEY') ).'">→</font> ';
		return	$point_img.'<a href="'.htmlspecialchars($href).'"'.$target.$accesskey.$att_cssclass.$cssstyle.'>'.$label.'</a>';
	}

	#--------------------------------------
	#	ヘルプ項目へのリンク
	function style_help( $href , $label , $cssclass = '' , $is_active = false , $target = '_self' , $args = array() ){
		$messages = $this->theme->parse_messagestring( $this->site->getpageinfo( $args[0] , 'message' ) );

		#======================================
		#	ターゲットウィンドウ文字列を決める
		if( $target == '_self' || !strlen( $target )){
			$target = '';
		}else{
			$target = ' target="'.htmlspecialchars( $target ).'"';
		}

		$option = $this->theme->parseoption( $args[1] );

		$att_cssclass = array();
		if( strlen( $cssclass ) ){ array_push( $att_cssclass , $cssclass ); }
		if( $is_active ){ array_push( $att_cssclass , $this->theme->classname_of_activelink ); }
		if( count( $att_cssclass ) ){ $att_cssclass = ' class="'.htmlspecialchars( implode( ' ' , $att_cssclass ) ).'"'; }
		else{ $att_cssclass = ''; }

		if( $option['cssstyle'] ){
			$cssstyle = ' style="'.htmlspecialchars( $option['cssstyle'] ).'"';
		}
		if( $option['accesskey'] ){
			$accesskey = ' accesskey="'.htmlspecialchars( $option['accesskey'] ).'"';
		}

		if( $this->site->getpageinfo( $args[0] , 'srcpath' ) == '(blank)' ){//PxFW 0.7.0 追加の処理
			return	'<span'.$label4title.$att_cssclass.$cssstyle.$onclick.$otherAttr.'>'.$label.'</span>';
		}
		$point_img = '<font color="'.htmlspecialchars( $this->theme->getcolor('KEY') ).'">(?)</font> ';
		return	$point_img.'<a href="'.htmlspecialchars($href).'"'.$target.$accesskey.$att_cssclass.$cssstyle.'>'.$label.'</a>';
	}

	#--------------------------------------
	#	ボタン型のリンク
	function style_button( $href , $label , $cssclass = '' , $is_active = false , $target = '_self' , $args = array() ){
		$messages = $this->theme->parse_messagestring( $this->site->getpageinfo( $args[0] , 'message' ) );

		#======================================
		#	ターゲットウィンドウ文字列を決める
		if( $target == '_self' || !strlen( $target )){
			$target = '';
		}else{
			$target = ' target="'.htmlspecialchars( $target ).'"';
		}

		$option = $this->theme->parseoption( $args[1] );

		$att_cssclass = array();
		if( strlen( $cssclass ) ){ array_push( $att_cssclass , $cssclass ); }
		if( $is_active ){ array_push( $att_cssclass , $this->theme->classname_of_activelink ); }
		if( count( $att_cssclass ) ){ $att_cssclass = ' class="'.htmlspecialchars( implode( ' ' , $att_cssclass ) ).'"'; }
		else{ $att_cssclass = ''; }

		if( $option['cssstyle'] ){
			$cssstyle = ' style="'.htmlspecialchars( $option['cssstyle'] ).'"';
		}
		if( $option['accesskey'] ){
			$accesskey = ' accesskey="'.htmlspecialchars( $option['accesskey'] ).'"';
		}

		if( $this->site->getpageinfo( $args[0] , 'srcpath' ) == '(blank)' ){//PxFW 0.7.0 追加の処理
			return	'<span'.$label4title.$att_cssclass.$cssstyle.$onclick.$otherAttr.'>'.$label.'</span>';
		}
		return	'<a href="'.htmlspecialchars($href).'"'.$target.$accesskey.$att_cssclass.' style="background-color:#aaaaaa; border:1px solid #777777; padding:3px;">'.$label.'</a>';
	}

}

?>