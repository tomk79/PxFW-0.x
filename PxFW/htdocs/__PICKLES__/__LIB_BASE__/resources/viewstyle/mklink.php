<?php

#	Copyright (C)Tomoya Koyanagi.
#	Last Update : 9:47 2011/02/07

#******************************************************************************************************************
#	mk_link()関数の表示スタイル管理
class base_resources_viewstyle_mklink{
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
	function base_resources_viewstyle_mklink( &$conf , &$user , &$site , &$req , &$dbh , &$theme , &$errors , &$custom ){
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
	#	スタイルシート情報を取得する
	function get_src( $href , $label , $style = '' , $cssclass = '' , $is_active = false , $target = '_self' , $args = array() ){
		#	このメソッドが呼ばれた回数
		if( !$this->pointernum ){ $this->pointernum = 0; }
		$this->pointernum ++;

		#	値の調整
		list( $href , $label , $style , $cssclass , $is_active , $target , $args ) = $this->preprocessor( $href , $label , $style , $cssclass , $is_active , $target , $args );
		$style = strtolower( $style );
		$style = preg_replace( '/[^a-zA-Z0-9_-]/' , '' , $style );
		if( !strlen( $style ) ){ $style = ''; }

		#	スタイルを探す
		if( $this->is_style_exists( $style ) ){
			return	eval( 'return	$this->style_'.$style.'( $href , $label , $cssclass , $is_active , $target , $args );' );
		}
		return	$this->style_( $href , $label , $cssclass , $is_active , $target , $args );
	}

	#--------------------------------------
	#	リンクスタイルが登録されているか調べる
	function is_style_exists( $style ){
		if( method_exists( $this , 'style_'.$style ) ){
			return	true;
		}
		return	false;
	}


	#--------------------------------------
	#	get_src()が受け取った値を事前加工する
	function preprocessor( $href , $label , $style = '' , $cssclass = '' , $is_active = false , $target = '_self' , $args = array() ){
		return	array( $href , $label , $style , $cssclass , $is_active , $target , $args );
	}


	#========================================================================================================================================================
	#	出力するスタイルを定義

	#--------------------------------------
	#	標準リンク
	function style_( $href , $label , $cssclass = '' , $is_active = false , $target = '_self' , $args = array() ){
		$messages = $this->theme->parse_messagestring( $this->site->getpageinfo( $args[0] , 'message' ) );
		if( strlen( $this->site->getpageinfo( $args[0] , 'outline' ) ) ){
			$messages['OUTLINE'] = $this->site->getpageinfo( $args[0] , 'outline' );//PxFW 0.6.2 追加
		}
		$onclick = '';
		$option = $this->theme->parseoption( $args[1] );

		$current_outline_name = '';
		$tmp_outline_list = array(
			$this->req->in('OUTLINE') ,
			$this->site->getpageinfo( $this->req->p() , 'outline' ) ,//PxFW 0.6.2 追加
			$this->theme->getmessage('OUTLINE')
		);
		foreach( $tmp_outline_list as $tmp_outline_str ){
			if( strlen( $tmp_outline_str ) && $this->is_style_exists( $tmp_outline_str ) ){
				$current_outline_name = $tmp_outline_str;
				break;
			}
		}

		#======================================
		#	onclickスクリプトを決める
		if( strlen( $args[1]['onclick'] ) ){
			$onclick .= trim( $args[1]['onclick'] );
			$onclick = preg_replace( '/'.preg_quote(';','/').'$/' , '' , $onclick ).';';//PxFW 0.6.2 追加の処理。自動的にセミコロンで閉じるようにした。
		}
		if( $target == '_blank' ){
			#	Pickles Framework 0.2.5 追加 3:01 2008/02/01
			$onclick .= 'window.open(this.href); return false;';
		}elseif( $target == '_top' ){
			#	Pickles Framework 0.5.1 追加 3:14 2008/11/07
			$onclick .= 'window.top.location.href=this.href; return false;';
		}elseif( $target == '_self' ){
			#	Pickles Framework 0.5.6 追加 2:11 2009/01/15
		}elseif( strlen( $target ) ){
			$onclick .= 'window.open(this.href,'.text::data2text($target).'); return false;';
		}elseif( $messages['OUTLINE'] == 'popup' && strpos( $href , '#' ) !== 0 && $current_outline_name != $messages['OUTLINE'] ){
			$onclick .= 'if(window.px&&typeof(px.openPopup)==\'function\'){ px.openPopup( this.href ); }else{ window.open( this.href ); }return false;';
		}
		#	/ onclickスクリプトを決める
		#======================================

		$att_cssclass = array();
		if( strlen( $cssclass ) ){ array_push( $att_cssclass , $cssclass ); }
		if( $is_active ){ array_push( $att_cssclass , $this->theme->classname_of_activelink ); }
		if( count( $att_cssclass ) ){ $att_cssclass = ' class="'.htmlspecialchars( implode( ' ' , $att_cssclass ) ).'"'; }
		else{ $att_cssclass = ''; }

		$cssstyle = '';
		if( strlen( $option['cssstyle'] ) ){
			$cssstyle = ' style="'.htmlspecialchars( $option['cssstyle'] ).'"';
		}

		$label4title = '';
		if( strlen( $option['title'] ) ){
			$label4title = $option['title'];
		}
		if( strlen( $label4title ) ){
			$label4title = ' title="'.htmlspecialchars( $label4title ).'"';
		}
		if( strlen( $onclick ) ){
			$onclick = ' onclick="'.htmlspecialchars( $onclick ).'"';
		}
		$otherAttr = '';//Pickles Framework 0.5.9 追加
		if( is_array( $args[1] ) ){
			foreach( $args[1] as $key=>$val ){
				switch( strtolower( $key ) ){
					case 'href': case 'label': case 'title': case 'style': case 'onclick': case 'class': case 'target': case 'allow_html': case 'active': case 'tstyle': case 'additionalquery': case 'gene_deltemp': case 'protocol': case 'filename': case 'cssstyle': case 'cssclass':
						continue 2;
				}
				$otherAttr .= ' '.htmlspecialchars( $key ).'="'.htmlspecialchars( $val ).'"';
			}
		}
		if( $this->site->getpageinfo( $args[0] , 'srcpath' ) == '(blank)' ){//PxFW 0.7.0 追加の処理
			return	'<span'.$label4title.$att_cssclass.$cssstyle.$onclick.$otherAttr.'>'.$label.'</span>';
		}
		return	'<a href="'.htmlspecialchars($href).'"'.$label4title.$att_cssclass.$cssstyle.$onclick.$otherAttr.'>'.$label.'</a>';
	}

	#--------------------------------------
	#	サイト内リンク
	function style_inside( $href , $label , $cssclass = '' , $is_active = false , $target = '_self' , $args = array() ){
		$messages = $this->theme->parse_messagestring( $this->site->getpageinfo( $args[0] , 'message' ) );
		if( strlen( $this->site->getpageinfo( $args[0] , 'outline' ) ) ){
			$messages['OUTLINE'] = $this->site->getpageinfo( $args[0] , 'outline' );//PxFW 0.6.2 追加
		}
		$onclick = '';
		$option = $this->theme->parseoption( $args[1] );

		$current_outline_name = '';
		$tmp_outline_list = array(
			$this->req->in('OUTLINE') ,
			$this->site->getpageinfo( $this->req->p() , 'outline' ) ,//PxFW 0.6.2 追加
			$this->theme->getmessage('OUTLINE')
		);
		foreach( $tmp_outline_list as $tmp_outline_str ){
			if( strlen( $tmp_outline_str ) && $this->is_style_exists( $tmp_outline_str ) ){
				$current_outline_name = $tmp_outline_str;
				break;
			}
		}

		#======================================
		#	onclickスクリプトを決める
		if( strlen( $args[1]['onclick'] ) ){
			$onclick .= trim( $args[1]['onclick'] );
			$onclick = preg_replace( '/'.preg_quote(';','/').'$/' , '' , $onclick ).';';//PxFW 0.6.2 追加の処理。自動的にセミコロンで閉じるようにした。
		}
		if( $target == '_blank' ){
			#	Pickles Framework 0.2.5 追加 3:01 2008/02/01
			$onclick .= 'window.open(this.href); return false;';
		}elseif( $target == '_top' ){
			#	Pickles Framework 0.5.1 追加 3:14 2008/11/07
			$onclick .= 'window.top.location.href=this.href; return false;';
		}elseif( $target == '_self' ){
			#	Pickles Framework 0.5.6 追加 2:11 2009/01/15
		}elseif( strlen( $target ) ){
			$onclick .= 'window.open(this.href,'.text::data2text($target).'); return false;';
		}elseif( $messages['OUTLINE'] == 'popup' && strpos( $href , '#' ) !== 0 && $current_outline_name != $messages['OUTLINE'] ){
			$onclick .= 'if(window.px&&typeof(px.openPopup)==\'function\'){ px.openPopup( this.href ); }else{ window.open( this.href ); }return false;';
		}
		#	/ onclickスクリプトを決める
		#======================================

		$att_cssclass = array( 'inside' );
		if( strlen( $cssclass ) ){ array_push( $att_cssclass , $cssclass ); }
		if( $is_active ){ array_push( $att_cssclass , $this->theme->classname_of_activelink ); }
		if( count( $att_cssclass ) ){ $att_cssclass = ' class="'.htmlspecialchars( implode( ' ' , $att_cssclass ) ).'"'; }
		else{ $att_cssclass = ''; }

		$cssstyle = '';
		if( $option['cssstyle'] ){
			$cssstyle = ' style="'.htmlspecialchars( $option['cssstyle'] ).'"';
		}

		$label4title = '';
		if( strlen( $option['title'] ) ){
			$label4title = $option['title'];
		}
		if( strlen( $label4title ) ){
			$label4title = ' title="'.htmlspecialchars( $label4title ).'"';
		}

		if( strlen( $onclick ) ){
			$onclick = ' onclick="'.htmlspecialchars( $onclick ).'"';
		}

		$otherAttr = '';//Pickles Framework 0.5.9 追加
		if( is_array( $args[1] ) ){
			foreach( $args[1] as $key=>$val ){
				switch( strtolower( $key ) ){
					case 'href': case 'label': case 'title': case 'style': case 'onclick': case 'class': case 'target': case 'allow_html': case 'active': case 'tstyle': case 'additionalquery': case 'gene_deltemp': case 'protocol': case 'filename': case 'cssstyle': case 'cssclass':
						continue 2;
				}
				$otherAttr .= ' '.htmlspecialchars( $key ).'="'.htmlspecialchars( $val ).'"';
			}
		}
		if( $this->site->getpageinfo( $args[0] , 'srcpath' ) == '(blank)' ){//PxFW 0.7.0 追加の処理
			return	'<span'.$label4title.$att_cssclass.$cssstyle.$onclick.$otherAttr.'>'.$label.'</span>';
		}
		$point_img = '<span style="color:'.htmlspecialchars( $this->theme->getcolor('KEY') ).';">・</span> ';
		return	'<a href="'.htmlspecialchars($href).'"'.$label4title.$att_cssclass.$cssstyle.$onclick.$otherAttr.'>'.$point_img.$label.'</a>';
	}

	#--------------------------------------
	#	下層へのリンク
	function style_under( $href , $label , $cssclass = '' , $is_active = false , $target = '_self' , $args = array() ){
		$messages = $this->theme->parse_messagestring( $this->site->getpageinfo( $args[0] , 'message' ) );
		if( strlen( $this->site->getpageinfo( $args[0] , 'outline' ) ) ){
			$messages['OUTLINE'] = $this->site->getpageinfo( $args[0] , 'outline' );//PxFW 0.6.2 追加
		}
		$onclick = '';
		$option = $this->theme->parseoption( $args[1] );

		$current_outline_name = '';
		$tmp_outline_list = array(
			$this->req->in('OUTLINE') ,
			$this->site->getpageinfo( $this->req->p() , 'outline' ) ,//PxFW 0.6.2 追加
			$this->theme->getmessage('OUTLINE')
		);
		foreach( $tmp_outline_list as $tmp_outline_str ){
			if( strlen( $tmp_outline_str ) && $this->is_style_exists( $tmp_outline_str ) ){
				$current_outline_name = $tmp_outline_str;
				break;
			}
		}

		#======================================
		#	onclickスクリプトを決める
		if( strlen( $args[1]['onclick'] ) ){
			$onclick .= trim( $args[1]['onclick'] );
			$onclick = preg_replace( '/'.preg_quote(';','/').'$/' , '' , $onclick ).';';//PxFW 0.6.2 追加の処理。自動的にセミコロンで閉じるようにした。
		}
		if( $target == '_blank' ){
			#	Pickles Framework 0.2.5 追加 3:01 2008/02/01
			$onclick .= 'window.open(this.href); return false;';
		}elseif( $target == '_top' ){
			#	Pickles Framework 0.5.1 追加 3:14 2008/11/07
			$onclick .= 'window.top.location.href=this.href; return false;';
		}elseif( $target == '_self' ){
			#	Pickles Framework 0.5.6 追加 2:11 2009/01/15
		}elseif( strlen( $target ) ){
			$onclick .= 'window.open(this.href,'.text::data2text($target).'); return false;';
		}elseif( $messages['OUTLINE'] == 'popup' && strpos( $href , '#' ) !== 0 && $current_outline_name != $messages['OUTLINE'] ){
			$onclick .= 'if(window.px&&typeof(px.openPopup)==\'function\'){ px.openPopup( this.href ); }else{ window.open( this.href ); }return false;';
		}
		#	/ onclickスクリプトを決める
		#======================================

		$att_cssclass = array( 'under' );
		if( strlen( $cssclass ) ){ array_push( $att_cssclass , $cssclass ); }
		if( $is_active ){ array_push( $att_cssclass , $this->theme->classname_of_activelink ); }
		if( count( $att_cssclass ) ){ $att_cssclass = ' class="'.htmlspecialchars( implode( ' ' , $att_cssclass ) ).'"'; }
		else{ $att_cssclass = ''; }

		$cssstyle = '';
		if( $option['cssstyle'] ){
			$cssstyle = ' style="'.htmlspecialchars( $option['cssstyle'] ).'"';
		}

		$label4title = '';
		if( strlen( $option['title'] ) ){
			$label4title = $option['title'];
		}
		if( strlen( $label4title ) ){
			$label4title = ' title="'.htmlspecialchars( $label4title ).'"';
		}

		if( strlen( $onclick ) ){
			$onclick = ' onclick="'.htmlspecialchars( $onclick ).'"';
		}

		$otherAttr = '';//Pickles Framework 0.5.9 追加
		if( is_array( $args[1] ) ){
			foreach( $args[1] as $key=>$val ){
				switch( strtolower( $key ) ){
					case 'href': case 'label': case 'title': case 'style': case 'onclick': case 'class': case 'target': case 'allow_html': case 'active': case 'tstyle': case 'additionalquery': case 'gene_deltemp': case 'protocol': case 'filename': case 'cssstyle': case 'cssclass':
						continue 2;
				}
				$otherAttr .= ' '.htmlspecialchars( $key ).'="'.htmlspecialchars( $val ).'"';
			}
		}
		if( $this->site->getpageinfo( $args[0] , 'srcpath' ) == '(blank)' ){//PxFW 0.7.0 追加の処理
			return	'<span'.$label4title.$att_cssclass.$cssstyle.$onclick.$otherAttr.'>'.$label.'</span>';
		}
		$point_img = '<span style="color:'.htmlspecialchars( $this->theme->getcolor('KEY') ).';">・</span> ';
		return	'<a href="'.htmlspecialchars($href).'"'.$label4title.$att_cssclass.$cssstyle.$onclick.$otherAttr.'>'.$point_img.$label.'</a>';
	}

	#--------------------------------------
	#	"次へ"リンク
	#	Pickles Framework 0.5.3 追加
	function style_next( $href , $label , $cssclass = '' , $is_active = false , $target = '_self' , $args = array() ){
		$messages = $this->theme->parse_messagestring( $this->site->getpageinfo( $args[0] , 'message' ) );
		if( strlen( $this->site->getpageinfo( $args[0] , 'outline' ) ) ){
			$messages['OUTLINE'] = $this->site->getpageinfo( $args[0] , 'outline' );//PxFW 0.6.2 追加
		}
		$onclick = '';
		$option = $this->theme->parseoption( $args[1] );

		$current_outline_name = '';
		$tmp_outline_list = array(
			$this->req->in('OUTLINE') ,
			$this->site->getpageinfo( $this->req->p() , 'outline' ) ,//PxFW 0.6.2 追加
			$this->theme->getmessage('OUTLINE')
		);
		foreach( $tmp_outline_list as $tmp_outline_str ){
			if( strlen( $tmp_outline_str ) && $this->is_style_exists( $tmp_outline_str ) ){
				$current_outline_name = $tmp_outline_str;
				break;
			}
		}

		#======================================
		#	onclickスクリプトを決める
		if( strlen( $args[1]['onclick'] ) ){
			$onclick .= trim( $args[1]['onclick'] );
			$onclick = preg_replace( '/'.preg_quote(';','/').'$/' , '' , $onclick ).';';//PxFW 0.6.2 追加の処理。自動的にセミコロンで閉じるようにした。
		}
		if( $target == '_blank' ){
			#	Pickles Framework 0.2.5 追加 3:01 2008/02/01
			$onclick .= 'window.open(this.href); return false;';
		}elseif( $target == '_top' ){
			#	Pickles Framework 0.5.1 追加 3:14 2008/11/07
			$onclick .= 'window.top.location.href=this.href; return false;';
		}elseif( $target == '_self' ){
			#	Pickles Framework 0.5.6 追加 2:11 2009/01/15
		}elseif( strlen( $target ) ){
			$onclick .= 'window.open(this.href,'.text::data2text($target).'); return false;';
		}elseif( $messages['OUTLINE'] == 'popup' && strpos( $href , '#' ) !== 0 && $current_outline_name != $messages['OUTLINE'] ){
			$onclick .= 'if(window.px&&typeof(px.openPopup)==\'function\'){ px.openPopup( this.href ); }else{ window.open( this.href ); }return false;';
		}
		#	/ onclickスクリプトを決める
		#======================================

		$att_cssclass = array( 'next' );
		if( strlen( $cssclass ) ){ array_push( $att_cssclass , $cssclass ); }
		if( $is_active ){ array_push( $att_cssclass , $this->theme->classname_of_activelink ); }
		if( count( $att_cssclass ) ){ $att_cssclass = ' class="'.htmlspecialchars( implode( ' ' , $att_cssclass ) ).'"'; }
		else{ $att_cssclass = ''; }

		$cssstyle = '';
		if( $option['cssstyle'] ){
			$cssstyle = ' style="'.htmlspecialchars( $option['cssstyle'] ).'"';
		}

		$label4title = '';
		if( strlen( $option['title'] ) ){
			$label4title = $option['title'];
		}
		if( strlen( $label4title ) ){
			$label4title = ' title="'.htmlspecialchars( $label4title ).'"';
		}

		if( strlen( $onclick ) ){
			$onclick = ' onclick="'.htmlspecialchars( $onclick ).'"';
		}

		$otherAttr = '';//Pickles Framework 0.5.9 追加
		if( is_array( $args[1] ) ){
			foreach( $args[1] as $key=>$val ){
				switch( strtolower( $key ) ){
					case 'href': case 'label': case 'title': case 'style': case 'onclick': case 'class': case 'target': case 'allow_html': case 'active': case 'tstyle': case 'additionalquery': case 'gene_deltemp': case 'protocol': case 'filename': case 'cssstyle': case 'cssclass':
						continue 2;
				}
				$otherAttr .= ' '.htmlspecialchars( $key ).'="'.htmlspecialchars( $val ).'"';
			}
		}
		if( $this->site->getpageinfo( $args[0] , 'srcpath' ) == '(blank)' ){//PxFW 0.7.0 追加の処理
			return	'<span'.$label4title.$att_cssclass.$cssstyle.$onclick.$otherAttr.'>'.$label.'</span>';
		}
		$point_img = '<span style="color:'.htmlspecialchars( $this->theme->getcolor('KEY') ).';">-&gt;&gt;</span> ';
		return	'<a href="'.htmlspecialchars($href).'"'.$label4title.$att_cssclass.$cssstyle.$onclick.$otherAttr.'>'.$label.$point_img.'</a>';
	}

	#--------------------------------------
	#	"前へ"リンク
	#	Pickles Framework 0.5.3 追加
	function style_prev( $href , $label , $cssclass = '' , $is_active = false , $target = '_self' , $args = array() ){
		$messages = $this->theme->parse_messagestring( $this->site->getpageinfo( $args[0] , 'message' ) );
		if( strlen( $this->site->getpageinfo( $args[0] , 'outline' ) ) ){
			$messages['OUTLINE'] = $this->site->getpageinfo( $args[0] , 'outline' );//PxFW 0.6.2 追加
		}
		$onclick = '';
		$option = $this->theme->parseoption( $args[1] );

		$current_outline_name = '';
		$tmp_outline_list = array(
			$this->req->in('OUTLINE') ,
			$this->site->getpageinfo( $this->req->p() , 'outline' ) ,//PxFW 0.6.2 追加
			$this->theme->getmessage('OUTLINE')
		);
		foreach( $tmp_outline_list as $tmp_outline_str ){
			if( strlen( $tmp_outline_str ) && $this->is_style_exists( $tmp_outline_str ) ){
				$current_outline_name = $tmp_outline_str;
				break;
			}
		}

		#======================================
		#	onclickスクリプトを決める
		if( strlen( $args[1]['onclick'] ) ){
			$onclick .= trim( $args[1]['onclick'] );
			$onclick = preg_replace( '/'.preg_quote(';','/').'$/' , '' , $onclick ).';';//PxFW 0.6.2 追加の処理。自動的にセミコロンで閉じるようにした。
		}
		if( $target == '_blank' ){
			#	Pickles Framework 0.2.5 追加 3:01 2008/02/01
			$onclick .= 'window.open(this.href); return false;';
		}elseif( $target == '_top' ){
			#	Pickles Framework 0.5.1 追加 3:14 2008/11/07
			$onclick .= 'window.top.location.href=this.href; return false;';
		}elseif( $target == '_self' ){
			#	Pickles Framework 0.5.6 追加 2:11 2009/01/15
		}elseif( strlen( $target ) ){
			$onclick .= 'window.open(this.href,'.text::data2text($target).'); return false;';
		}elseif( $messages['OUTLINE'] == 'popup' && strpos( $href , '#' ) !== 0 && $current_outline_name != $messages['OUTLINE'] ){
			$onclick .= 'if(window.px&&typeof(px.openPopup)==\'function\'){ px.openPopup( this.href ); }else{ window.open( this.href ); }return false;';
		}
		#	/ onclickスクリプトを決める
		#======================================

		$att_cssclass = array( 'prev' );
		if( strlen( $cssclass ) ){ array_push( $att_cssclass , $cssclass ); }
		if( $is_active ){ array_push( $att_cssclass , $this->theme->classname_of_activelink ); }
		if( count( $att_cssclass ) ){ $att_cssclass = ' class="'.htmlspecialchars( implode( ' ' , $att_cssclass ) ).'"'; }
		else{ $att_cssclass = ''; }

		$cssstyle = '';
		if( $option['cssstyle'] ){
			$cssstyle = ' style="'.htmlspecialchars( $option['cssstyle'] ).'"';
		}

		$label4title = '';
		if( strlen( $option['title'] ) ){
			$label4title = $option['title'];
		}
		if( strlen( $label4title ) ){
			$label4title = ' title="'.htmlspecialchars( $label4title ).'"';
		}

		if( strlen( $onclick ) ){
			$onclick = ' onclick="'.htmlspecialchars( $onclick ).'"';
		}

		$otherAttr = '';//Pickles Framework 0.5.9 追加
		if( is_array( $args[1] ) ){
			foreach( $args[1] as $key=>$val ){
				switch( strtolower( $key ) ){
					case 'href': case 'label': case 'title': case 'style': case 'onclick': case 'class': case 'target': case 'allow_html': case 'active': case 'tstyle': case 'additionalquery': case 'gene_deltemp': case 'protocol': case 'filename': case 'cssstyle': case 'cssclass':
						continue 2;
				}
				$otherAttr .= ' '.htmlspecialchars( $key ).'="'.htmlspecialchars( $val ).'"';
			}
		}
		if( $this->site->getpageinfo( $args[0] , 'srcpath' ) == '(blank)' ){//PxFW 0.7.0 追加の処理
			return	'<span'.$label4title.$att_cssclass.$cssstyle.$onclick.$otherAttr.'>'.$label.'</span>';
		}
		$point_img = '<span style="color:'.htmlspecialchars( $this->theme->getcolor('KEY') ).';">&lt;&lt;-</span> ';
		return	'<a href="'.htmlspecialchars($href).'"'.$label4title.$att_cssclass.$cssstyle.$onclick.$otherAttr.'>'.$point_img.$label.'</a>';
	}

	#--------------------------------------
	#	カテゴリ外リンク
	function style_outside( $href , $label , $cssclass = '' , $is_active = false , $target = '_self' , $args = array() ){
		$messages = $this->theme->parse_messagestring( $this->site->getpageinfo( $args[0] , 'message' ) );
		if( strlen( $this->site->getpageinfo( $args[0] , 'outline' ) ) ){
			$messages['OUTLINE'] = $this->site->getpageinfo( $args[0] , 'outline' );//PxFW 0.6.2 追加
		}
		$onclick = '';
		$option = $this->theme->parseoption( $args[1] );

		$current_outline_name = '';
		$tmp_outline_list = array(
			$this->req->in('OUTLINE') ,
			$this->site->getpageinfo( $this->req->p() , 'outline' ) ,//PxFW 0.6.2 追加
			$this->theme->getmessage('OUTLINE')
		);
		foreach( $tmp_outline_list as $tmp_outline_str ){
			if( strlen( $tmp_outline_str ) && $this->is_style_exists( $tmp_outline_str ) ){
				$current_outline_name = $tmp_outline_str;
				break;
			}
		}

		#======================================
		#	onclickスクリプトを決める
		if( strlen( $args[1]['onclick'] ) ){
			$onclick .= trim( $args[1]['onclick'] );
			$onclick = preg_replace( '/'.preg_quote(';','/').'$/' , '' , $onclick ).';';//PxFW 0.6.2 追加の処理。自動的にセミコロンで閉じるようにした。
		}
		if( $target == '_blank' ){
			#	Pickles Framework 0.2.5 追加 3:01 2008/02/01
			$onclick .= 'window.open(this.href); return false;';
		}elseif( $target == '_top' ){
			#	Pickles Framework 0.5.1 追加 3:14 2008/11/07
			$onclick .= 'window.top.location.href=this.href; return false;';
		}elseif( $target == '_self' ){
			#	Pickles Framework 0.5.6 追加 2:11 2009/01/15
		}elseif( strlen( $target ) ){
			$onclick .= 'window.open(this.href,'.text::data2text($target).'); return false;';
		}elseif( $messages['OUTLINE'] == 'popup' && strpos( $href , '#' ) !== 0 && $current_outline_name != $messages['OUTLINE'] ){
			$onclick .= 'if(window.px&&typeof(px.openPopup)==\'function\'){ px.openPopup( this.href ); }else{ window.open( this.href ); }return false;';
		}
		#	/ onclickスクリプトを決める
		#======================================

		$att_cssclass = array( 'outside' );
		if( strlen( $cssclass ) ){ array_push( $att_cssclass , $cssclass ); }
		if( $is_active ){ array_push( $att_cssclass , $this->theme->classname_of_activelink ); }
		if( count( $att_cssclass ) ){ $att_cssclass = ' class="'.htmlspecialchars( implode( ' ' , $att_cssclass ) ).'"'; }
		else{ $att_cssclass = ''; }

		$cssstyle = '';
		if( $option['cssstyle'] ){
			$cssstyle = ' style="'.htmlspecialchars( $option['cssstyle'] ).'"';
		}

		$label4title = '';
		if( strlen( $option['title'] ) ){
			$label4title = $option['title'];
		}
		if( strlen( $label4title ) ){
			$label4title = ' title="'.htmlspecialchars( $label4title ).'"';
		}

		if( strlen( $onclick ) ){
			$onclick = ' onclick="'.htmlspecialchars( $onclick ).'"';
		}

		$otherAttr = '';//Pickles Framework 0.5.9 追加
		if( is_array( $args[1] ) ){
			foreach( $args[1] as $key=>$val ){
				switch( strtolower( $key ) ){
					case 'href': case 'label': case 'title': case 'style': case 'onclick': case 'class': case 'target': case 'allow_html': case 'active': case 'tstyle': case 'additionalquery': case 'gene_deltemp': case 'protocol': case 'filename': case 'cssstyle': case 'cssclass':
						continue 2;
				}
				$otherAttr .= ' '.htmlspecialchars( $key ).'="'.htmlspecialchars( $val ).'"';
			}
		}
		if( $this->site->getpageinfo( $args[0] , 'srcpath' ) == '(blank)' ){//PxFW 0.7.0 追加の処理
			return	'<span'.$label4title.$att_cssclass.$cssstyle.$onclick.$otherAttr.'>'.$label.'</span>';
		}
		$point_img = '<span style="color:'.htmlspecialchars( $this->theme->getcolor('KEY') ).';">&gt;</span> ';
		return	'<a href="'.htmlspecialchars($href).'"'.$label4title.$att_cssclass.$cssstyle.$onclick.$otherAttr.'>'.$point_img.$label.'</a>';
	}

	#--------------------------------------
	#	サイト外リンク
	function style_exit( $href , $label , $cssclass = '' , $is_active = false , $target = '_self' , $args = array() ){
		$messages = $this->theme->parse_messagestring( $this->site->getpageinfo( $args[0] , 'message' ) );
		if( strlen( $this->site->getpageinfo( $args[0] , 'outline' ) ) ){
			$messages['OUTLINE'] = $this->site->getpageinfo( $args[0] , 'outline' );//PxFW 0.6.2 追加
		}
		$onclick = '';
		$option = $this->theme->parseoption( $args[1] );

		$current_outline_name = '';
		$tmp_outline_list = array(
			$this->req->in('OUTLINE') ,
			$this->site->getpageinfo( $this->req->p() , 'outline' ) ,//PxFW 0.6.2 追加
			$this->theme->getmessage('OUTLINE')
		);
		foreach( $tmp_outline_list as $tmp_outline_str ){
			if( strlen( $tmp_outline_str ) && $this->is_style_exists( $tmp_outline_str ) ){
				$current_outline_name = $tmp_outline_str;
				break;
			}
		}

		#======================================
		#	onclickスクリプトを決める
		if( strlen( $args[1]['onclick'] ) ){
			$onclick .= trim( $args[1]['onclick'] );
			$onclick = preg_replace( '/'.preg_quote(';','/').'$/' , '' , $onclick ).';';//PxFW 0.6.2 追加の処理。自動的にセミコロンで閉じるようにした。
		}
		if( $target == '_blank' ){
			#	Pickles Framework 0.2.5 追加 3:01 2008/02/01
			$onclick .= 'window.open(this.href); return false;';
		}elseif( $target == '_top' ){
			#	Pickles Framework 0.5.1 追加 3:14 2008/11/07
			$onclick .= 'window.top.location.href=this.href; return false;';
		}elseif( $target == '_self' ){
			#	Pickles Framework 0.5.6 追加 2:11 2009/01/15
		}elseif( strlen( $target ) ){
			$onclick .= 'window.open(this.href,'.text::data2text($target).'); return false;';
		}elseif( $messages['OUTLINE'] == 'popup' && strpos( $href , '#' ) !== 0 && $current_outline_name != $messages['OUTLINE'] ){
			$onclick .= 'if(window.px&&typeof(px.openPopup)==\'function\'){ px.openPopup( this.href ); }else{ window.open( this.href ); }return false;';
		}
		#	/ onclickスクリプトを決める
		#======================================

		$att_cssclass = array( 'exit' );
		if( strlen( $cssclass ) ){ array_push( $att_cssclass , $cssclass ); }
		if( $is_active ){ array_push( $att_cssclass , $this->theme->classname_of_activelink ); }
		if( count( $att_cssclass ) ){ $att_cssclass = ' class="'.htmlspecialchars( implode( ' ' , $att_cssclass ) ).'"'; }
		else{ $att_cssclass = ''; }

		$cssstyle = '';
		if( $option['cssstyle'] ){
			$cssstyle = ' style="'.htmlspecialchars( $option['cssstyle'] ).'"';
		}

		$label4title = '';
		if( strlen( $option['title'] ) ){
			$label4title = $option['title'];
		}
		if( strlen( $label4title ) ){
			$label4title = ' title="'.htmlspecialchars( $label4title ).'"';
		}

		if( strlen( $onclick ) ){
			$onclick = ' onclick="'.htmlspecialchars( $onclick ).'"';
		}

		$otherAttr = '';//Pickles Framework 0.5.9 追加
		if( is_array( $args[1] ) ){
			foreach( $args[1] as $key=>$val ){
				switch( strtolower( $key ) ){
					case 'href': case 'label': case 'title': case 'style': case 'onclick': case 'class': case 'target': case 'allow_html': case 'active': case 'tstyle': case 'additionalquery': case 'gene_deltemp': case 'protocol': case 'filename': case 'cssstyle': case 'cssclass':
						continue 2;
				}
				$otherAttr .= ' '.htmlspecialchars( $key ).'="'.htmlspecialchars( $val ).'"';
			}
		}
		if( $this->site->getpageinfo( $args[0] , 'srcpath' ) == '(blank)' ){//PxFW 0.7.0 追加の処理
			return	'<span'.$label4title.$att_cssclass.$cssstyle.$onclick.$otherAttr.'>'.$label.'</span>';
		}
		$point_img = '<span style="color:'.htmlspecialchars( $this->theme->getcolor('KEY') ).';">→</span> ';
		return	'<a href="'.htmlspecialchars($href).'"'.$label4title.$att_cssclass.$cssstyle.$onclick.$otherAttr.'>'.$point_img.$label.'</a>';
	}

	#--------------------------------------
	#	ヘルプ項目へのリンク
	function style_help( $href , $label , $cssclass = '' , $is_active = false , $target = '_self' , $args = array() ){
		$messages = $this->theme->parse_messagestring( $this->site->getpageinfo( $args[0] , 'message' ) );
		if( strlen( $this->site->getpageinfo( $args[0] , 'outline' ) ) ){
			$messages['OUTLINE'] = $this->site->getpageinfo( $args[0] , 'outline' );//PxFW 0.6.2 追加
		}
		$onclick = '';
		$option = $this->theme->parseoption( $args[1] );

		$current_outline_name = '';
		$tmp_outline_list = array(
			$this->req->in('OUTLINE') ,
			$this->site->getpageinfo( $this->req->p() , 'outline' ) ,//PxFW 0.6.2 追加
			$this->theme->getmessage('OUTLINE')
		);
		foreach( $tmp_outline_list as $tmp_outline_str ){
			if( strlen( $tmp_outline_str ) && $this->is_style_exists( $tmp_outline_str ) ){
				$current_outline_name = $tmp_outline_str;
				break;
			}
		}

		#======================================
		#	onclickスクリプトを決める
		if( strlen( $args[1]['onclick'] ) ){
			$onclick .= trim( $args[1]['onclick'] );
			$onclick = preg_replace( '/'.preg_quote(';','/').'$/' , '' , $onclick ).';';//PxFW 0.6.2 追加の処理。自動的にセミコロンで閉じるようにした。
		}
		if( $target == '_blank' ){
			#	Pickles Framework 0.2.5 追加 3:01 2008/02/01
			$onclick .= 'window.open(this.href); return false;';
		}elseif( $target == '_top' ){
			#	Pickles Framework 0.5.1 追加 3:14 2008/11/07
			$onclick .= 'window.top.location.href=this.href; return false;';
		}elseif( $target == '_self' ){
			#	Pickles Framework 0.5.6 追加 2:11 2009/01/15
		}elseif( strlen( $target ) ){
			$onclick .= 'window.open(this.href,'.text::data2text($target).'); return false;';
		}elseif( $messages['OUTLINE'] == 'popup' && strpos( $href , '#' ) !== 0 && $current_outline_name != $messages['OUTLINE'] ){
			$onclick .= 'if(window.px&&typeof(px.openPopup)==\'function\'){ px.openPopup( this.href ); }else{ window.open( this.href ); }return false;';
		}
		#	/ onclickスクリプトを決める
		#======================================

		$att_cssclass = array( 'help' );
		if( strlen( $cssclass ) ){ array_push( $att_cssclass , $cssclass ); }
		if( $is_active ){ array_push( $att_cssclass , $this->theme->classname_of_activelink ); }
		if( count( $att_cssclass ) ){ $att_cssclass = ' class="'.htmlspecialchars( implode( ' ' , $att_cssclass ) ).'"'; }
		else{ $att_cssclass = ''; }

		$cssstyle = '';
		if( $option['cssstyle'] ){
			$cssstyle = ' style="'.htmlspecialchars( $option['cssstyle'] ).'"';
		}

		$label4title = '';
		if( strlen( $option['title'] ) ){
			$label4title = $option['title'];
		}
		if( strlen( $label4title ) ){
			$label4title = ' title="'.htmlspecialchars( $label4title ).'"';
		}

		if( strlen( $onclick ) ){
			$onclick = ' onclick="'.htmlspecialchars( $onclick ).'"';
		}

		$otherAttr = '';//Pickles Framework 0.5.9 追加
		if( is_array( $args[1] ) ){
			foreach( $args[1] as $key=>$val ){
				switch( strtolower( $key ) ){
					case 'href': case 'label': case 'title': case 'style': case 'onclick': case 'class': case 'target': case 'allow_html': case 'active': case 'tstyle': case 'additionalquery': case 'gene_deltemp': case 'protocol': case 'filename': case 'cssstyle': case 'cssclass':
						continue 2;
				}
				$otherAttr .= ' '.htmlspecialchars( $key ).'="'.htmlspecialchars( $val ).'"';
			}
		}
		if( $this->site->getpageinfo( $args[0] , 'srcpath' ) == '(blank)' ){//PxFW 0.7.0 追加の処理
			return	'<span'.$label4title.$att_cssclass.$cssstyle.$onclick.$otherAttr.'>'.$label.'</span>';
		}
		$point_img = '<span style="color:'.htmlspecialchars( $this->theme->getcolor('KEY') ).';">(?)</span> ';
		return	'<a href="'.htmlspecialchars($href).'"'.$label4title.$att_cssclass.$cssstyle.$onclick.$otherAttr.'>'.$point_img.$label.'</a>';
	}

	#--------------------------------------
	#	ボタン型のリンク
	function style_button( $href , $label , $cssclass = '' , $is_active = false , $target = '_self' , $args = array() ){
		$messages = $this->theme->parse_messagestring( $this->site->getpageinfo( $args[0] , 'message' ) );
		if( strlen( $this->site->getpageinfo( $args[0] , 'outline' ) ) ){
			$messages['OUTLINE'] = $this->site->getpageinfo( $args[0] , 'outline' );//PxFW 0.6.2 追加
		}
		$onclick = '';
		$option = $this->theme->parseoption( $args[1] );

		$current_outline_name = '';
		$tmp_outline_list = array(
			$this->req->in('OUTLINE') ,
			$this->site->getpageinfo( $this->req->p() , 'outline' ) ,//PxFW 0.6.2 追加
			$this->theme->getmessage('OUTLINE')
		);
		foreach( $tmp_outline_list as $tmp_outline_str ){
			if( strlen( $tmp_outline_str ) && $this->is_style_exists( $tmp_outline_str ) ){
				$current_outline_name = $tmp_outline_str;
				break;
			}
		}

		#======================================
		#	onclickスクリプトを決める
		if( strlen( $args[1]['onclick'] ) ){
			$onclick .= trim( $args[1]['onclick'] );
			$onclick = preg_replace( '/'.preg_quote(';','/').'$/' , '' , $onclick ).';';//PxFW 0.6.2 追加の処理。自動的にセミコロンで閉じるようにした。
		}
		if( $target == '_blank' ){
			#	Pickles Framework 0.2.5 追加 3:01 2008/02/01
			$onclick .= 'window.open('.text::data2jstext($href).'); return false;';
		}elseif( $target == '_top' ){
			#	Pickles Framework 0.5.1 追加 3:14 2008/11/07
			$onclick .= 'window.top.location.href='.text::data2jstext($href).'; return false;';
		}elseif( $target == '_self' ){
			#	Pickles Framework 0.5.6 追加 2:11 2009/01/15
			$onclick .= 'window.location.href='.text::data2jstext($href).'; return false;';
		}elseif( strlen( $target ) ){
			$onclick .= 'window.open('.text::data2jstext($href).','.text::data2text($target).'); return false;';
		}elseif( $messages['OUTLINE'] == 'popup' && strpos( $href , '#' ) !== 0 && $current_outline_name != $messages['OUTLINE'] ){
			$onclick .= 'if(window.px&&typeof(px.openPopup)==\'function\'){ px.openPopup( '.text::data2jstext($href).' ); }else{ window.open( '.text::data2jstext($href).' ); }return false;';
		}else{
			$onclick .= 'window.location.href='.text::data2jstext($href).'; return false;';
		}
		#	/ onclickスクリプトを決める
		#======================================

		$att_cssclass = array( 'button' );
		if( strlen( $cssclass ) ){ array_push( $att_cssclass , $cssclass ); }
		if( $is_active ){ array_push( $att_cssclass , $this->theme->classname_of_activelink ); }
		if( count( $att_cssclass ) ){ $att_cssclass = ' class="'.htmlspecialchars( implode( ' ' , $att_cssclass ) ).'"'; }
		else{ $att_cssclass = ''; }

		$cssstyle = '';

		$label4title = '';
		if( strlen( $option['title'] ) ){
			$label4title = $option['title'];
		}
		if( strlen( $label4title ) ){
			$label4title = ' title="'.htmlspecialchars( $label4title ).'"';
		}

		$otherAttr = '';//Pickles Framework 0.5.9 追加
		if( is_array( $args[1] ) ){
			foreach( $args[1] as $key=>$val ){
				switch( strtolower( $key ) ){
					case 'href': case 'label': case 'title': case 'style': case 'onclick': case 'class': case 'target': case 'allow_html': case 'active': case 'tstyle': case 'additionalquery': case 'gene_deltemp': case 'protocol': case 'filename': case 'cssstyle': case 'cssclass':
						continue 2;
				}
				$otherAttr .= ' '.htmlspecialchars( $key ).'="'.htmlspecialchars( $val ).'"';
			}
		}
		if( $this->site->getpageinfo( $args[0] , 'srcpath' ) == '(blank)' ){//PxFW 0.7.0 追加の処理
			return	'<span'.$label4title.$att_cssclass.$cssstyle.$onclick.$otherAttr.'>'.$label.'</span>';
		}
		if( strlen( $onclick ) ){
			$onclick = ' onclick="'.htmlspecialchars( $onclick ).'"';
		}
		return	'<button'.$onclick.$label4title.$att_cssclass.$cssstyle.$otherAttr.'>'.$label.'</button>';
	}

}

?>