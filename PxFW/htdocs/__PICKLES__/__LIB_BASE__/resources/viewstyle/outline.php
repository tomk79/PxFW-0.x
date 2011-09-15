<?php

#	Copyright (C)Tomoya Koyanagi.
#	Last Update : 0:57 2011/05/24

#******************************************************************************************************************
#	基本スタイルの管理
class base_resources_viewstyle_outline{

	var $conf;
	var $user;
	var $site;
	var $req;
	var $dbh;
	var $theme;
	var $errors;
	var $custom;

	var $pointernum = 0;	#	get_src()が呼ばれた回数

	#	色の設定
	var $colors_keydd = '#666666';
	var $colors_keyd = '#999999';
	var $colors_key = '#cccccc';
	var $colors_keyt = '#dddddd';
	var $colors_keytt = '#eeeeee';
	var $colors_subdd = '#888888';
	var $colors_subd = '#aaaaaa';
	var $colors_sub = '#dddddd';
	var $colors_subt = '#eeeeee';
	var $colors_subtt = '#f6f6f6';
	var $colors_bg = '#ffffff';
	var $colors_text = '#333333';
	var $colors_link = '#0033ff';
	var $colors_link_hover = '#0066ff';
	var $colors_link_active = '#ff6600';
	var $colors_link_active_hover = '#ff9900';
	var $colors_error = '#ff0000';
	var $colors_notes = '#666666';
	var $colors_attention = '#666666';
	var $colors_alert = '#663333';
	var $colors_caution = '#dd0000';
	var $colors_warning = '#ff0000';
	var $colors_must = '#ff0000';



	#----------------------------------------------------------------------------
	#	コンストラクタ
	function base_resources_viewstyle_outline( &$conf , &$user , &$site , &$req , &$dbh , &$theme , &$errors , &$custom ){
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
	#	アウトラインスタイルソースを取得する
	function get_src( $SRC_CONTENTS , $style = '' ){

		#	値の調整
		list( $SRC_CONTENTS , $style ) = $this->preprocessor( $SRC_CONTENTS , $style );

		#	開始タグと終了タグを追加
		$SRC_CONTENTS = $this->conf->contents_start_str."\n".$SRC_CONTENTS."\n".$this->conf->contents_end_str."\n";

		$style = strtolower( $style );
		$style = preg_replace( '/[^a-zA-Z0-9_-]/' , '' , $style );
		if( !strlen( $style ) ){ $style = ''; }

		#	スタイルを探す
		if( $this->is_style_exists( $style ) ){
			return	eval( 'return	$this->style_'.$style.'( $SRC_CONTENTS );' );
		}
		return	$this->style_( $SRC_CONTENTS );
	}

	#--------------------------------------
	#	アウトラインスタイルが登録されているか調べる
	function is_style_exists( $style ){
		if( method_exists( $this , 'style_'.$style ) ){
			return	true;
		}
		return	false;
	}


	#--------------------------------------
	#	get_src()が受け取った値を事前加工する
	function preprocessor( $SRC_CONTENTS , $style = '' ){
		return	array( $SRC_CONTENTS , $style );
	}


	#========================================================================================================================================================
	#	出力するスタイルを定義

	#--------------------------------------
	#	標準のスタイル
	function style_( $SRC_CONTENTS ){
		if( is_null( $SRC_CONTENTS ) ){ $SRC_CONTENTS = $this->theme->getsrc(); }

		$gmenu = $this->theme->get_menulist( 'global' );
		$smenu = $this->theme->get_menulist( 'shoulder' );

		$RTN = '';

		#--------------------------------------
		#	上段
		$RTN .= '		<div id="blockTop">'."\n";
		$RTN .= '			<div id="logo"><a href="'.htmlspecialchars( $this->theme->href('') ).'" title="'.htmlspecialchars( $this->site->gettitle() ).'"><strong>'.htmlspecialchars( $this->site->gettitle() ).'</strong></a></div>'."\n";
		$RTN .= '			<div class="auralinfo"><a href="#main">本文へスキップ</a></div>'."\n";
		if( is_array( $gmenu ) && count( $gmenu ) ){
			$RTN .= '			<div class="globalmenu">'."\n";
			$RTN .= '				<ul>'."\n";
			foreach( $gmenu as $gmenu_pid ){
				if( !$this->site->is_visiblepage( $gmenu_pid ) ){ continue; }
				$RTN .= '					<li>'.$this->theme->mk_link($gmenu_pid).'</li>'."\n";
			}
			$RTN .= '				</ul>'."\n";
			$RTN .= '			</div>'."\n";
		}
		if( is_array( $smenu ) && count( $smenu ) ){
			$RTN .= '			<div class="shouldermenu">'."\n";
			$RTN .= '				<ul>'."\n";
			foreach( $smenu as $smenu_pid ){
				if( !$this->site->is_visiblepage( $smenu_pid ) ){ continue; }
				$RTN .= '					<li>'.$this->theme->mk_link($smenu_pid).'</li>'."\n";
			}
			$RTN .= '				</ul>'."\n";
			$RTN .= '			</div>'."\n";
		}
		$RTN .= '			<div class="breadcrumb"><span class="auralinfo">参照中のページ：</span>'.$this->theme->getsrc('breadcrumb').'</div>'."\n";
		$RTN .= '		</div>'."\n";

		#--------------------------------------
		#	中段
		$RTN .= '		<div id="blockMiddle">'."\n";

		$RTN .= '			<div id="sidebarLeft">'."\n";
		$RTN .= '			</div>'."\n";
		$RTN .= '			<div id="main">'."\n";
		$RTN .= '				<div id="ptitle">'."\n";
		if( $this->site->getpageinfo( $this->req->p() , 'id' ) != $this->site->getpageinfo( $this->req->p() , 'cattitleby' ) && strlen( $this->site->getpageinfo( $this->req->p() , 'cattitleby' ) ) ){
			$RTN .= '					<div class="ctitle">'.text::text2html( trim( $this->theme->gettitle( 'category' ) ) ).'</div>'."\n";
		}
		$RTN .= '					<div class="ptitle"><h1>'.text::text2html( trim( $this->theme->gettitle('page') ) ).'</h1></div>'."\n";
		if( strlen( $this->site->getpageinfo( $this->req->p() , 'summary' ) ) ){
			$RTN .= '					<div class="summary"><p class="ttr">'.text::text2html( trim( $this->site->getpageinfo( $this->req->p() , 'summary' ) ) ).'</p></div>'."\n";
		}
		$RTN .= '				</div>'."\n";

		$RTN .= '				<div id="content">'."\n";
		$RTN .= $SRC_CONTENTS."\n";
		$RTN .= '				</div>'."\n";
		$RTN .= $this->theme->mk_back2top()."\n";
		$RTN .= '			</div>'."\n";

		$RTN .= '			<div id="sidebarRight">'."\n";

		$pid_current_on_breadcrumb = $this->site->getpageinfo( $this->req->p() , 'id' );
			//PxFW 0.6.4 : トップページに pvelm値 を指定した時に、ナビゲーション上のトップページがアクティブにならないことがある不具合を修正。
		if( strlen( $pid_current_on_breadcrumb ) ){
			$RTN .= '			<div class="link2parentpage">'.$this->theme->mk_link( $this->site->get_parent( $pid_current_on_breadcrumb ) , array('active'=>false) ).'</div>'."\n";
		}
		#	ローカルナビゲーション作成
		$broslist = $this->site->get_bros( $pid_current_on_breadcrumb );
		if( is_array( $broslist ) && count( $broslist ) ){
			$RTN .= '			<ul class="topictree">';
			foreach( $broslist as $bros ){
				$RTN .= '<li>'.$this->theme->mk_link( $bros ,array('active'=>'follow'));
				if( $bros == $pid_current_on_breadcrumb ){
					$children = $this->site->get_children( $pid_current_on_breadcrumb );
					if( count( $children ) ){
						$RTN .= '<ul>';
						foreach( $children as $child ){
							$RTN .= '<li>'.$this->theme->mk_link( $child ,array('active'=>'follow'));
							$RTN .= '</li>';
						}
						$RTN .= '</ul>';
					}
				}
				$RTN .= '</li>';
			}
			$RTN .= '</ul>'."\n";
		}
		#	/ ローカルナビゲーション作成

		$RTN .= '			</div>'."\n";
		$RTN .= '		</div>'."\n";

		#--------------------------------------
		#	下段
		$RTN .= '		<div id="blockBottom">'."\n";
		$RTN .= '			<div class="breadcrumb"><span class="auralinfo">参照中のページ：</span>'.$this->theme->getsrc( 'breadcrumb' ).'</div>'."\n";

		if( is_array( $gmenu ) && count( $gmenu ) ){
			$RTN .= '			<div class="globalmenu">'."\n";
			$RTN .= '				<ul>'."\n";
			foreach( $gmenu as $gmenu_pid ){
				if( !$this->site->is_visiblepage( $gmenu_pid ) ){ continue; }
				$RTN .= '					<li>'.$this->theme->mk_link( $gmenu_pid ).'</li>'."\n";
			}
			$RTN .= '				</ul>'."\n";
			$RTN .= '			</div>'."\n";
		}
		$FOOTMENU = '';
		if( is_array( $smenu ) && count( $smenu ) ){
			foreach( $smenu as $smenu_pid ){
				if( !$this->site->is_visiblepage( $smenu_pid ) ){ continue; }
				$FOOTMENU .= '						<li>'.$this->theme->mk_link( $smenu_pid ).'</li>'."\n";
			}
		}
		if( $this->user->is_login() ){
			$FOOTMENU .= '						<li>'.$this->theme->mk_link( 'logout' , array( 'active'=>false ) ).'</li>'."\n";
		}
		if( strlen( $FOOTMENU ) ){
			$RTN .= '				<div class="shouldermenu">'."\n";
			$RTN .= '					<ul>'."\n";
			$RTN .= $FOOTMENU."\n";
			$RTN .= '					</ul>'."\n";
			$RTN .= '				</div>'."\n";
		}
		unset( $FOOTMENU );
		$RTN .= '			<address class="copyright">'.$this->site->getcopyright('print').'</address>'."\n";//← $site->getcopyright() はHTMLを返すので、htmlspecialchars() をかけてはいけない。
		$RTN .= '		</div>'."\n";

		$this->theme->setsrc($RTN);
		return	$this->style_header( $RTN );
	}






	#--------------------------------------
	#	ポップアップウィンドウのスタイル
	function style_popup( $SRC_CONTENTS ){
		if( is_null( $SRC_CONTENTS ) ){ $SRC_CONTENTS = $this->theme->getsrc(); }

		$RTN = '';

		#--------------------------------------
		#	上段
		$RTN .= '		<div id="blockTop">'."\n";
		$RTN .= '			<div id="logo"><strong>'.htmlspecialchars( $this->site->gettitle() ).'</strong></div>'."\n";
		$RTN .= '		</div>'."\n";

		#--------------------------------------
		#	中段
		$RTN .= '		<div id="blockMiddle">'."\n";

		$RTN .= '			<div id="ptitle">'."\n";
		if( $this->site->getpageinfo( $this->req->p() , 'id' ) != $this->site->getpageinfo( $this->req->p() , 'cattitleby' ) && strlen( $this->site->getpageinfo( $this->req->p() , 'cattitleby' ) ) ){
			$RTN .= '				<div class="ctitle"><div>'.htmlspecialchars( $this->theme->gettitle( 'category' ) ).'</div></div>'."\n";
		}
		$RTN .= '				<div class="ptitle"><h1>'.htmlspecialchars( $this->theme->gettitle('page') ).'</h1></div>'."\n";
		if( strlen( $this->site->getpageinfo( $this->req->p() , 'summary' ) ) ){
			$RTN .= '				<div class="summary"><p class="ttr">'.htmlspecialchars( $this->site->getpageinfo( $this->req->p() , 'summary' ) ).'</p></div>'."\n";
		}
		$RTN .= '			</div>'."\n";

		$RTN .= '			<div id="content">'."\n";
		$RTN .= $SRC_CONTENTS."\n";
		$RTN .= '			</div>'."\n";
		$RTN .= $this->theme->mk_back2top()."\n";

		$RTN .= '		</div>'."\n";

		#--------------------------------------
		#	下段
		$RTN .= '		<div id="blockBottom">'."\n";
		$RTN .= '			<p class="fst AlignC">'."\n";
		$RTN .= '				[ <a href="javascript:window.close();">Close this window</a> ]<br />'."\n";
		$RTN .= '			</p>'."\n";
		$RTN .= '			<address class="copyright">'.$this->site->getcopyright('print').'</address>'."\n";
		$RTN .= '		</div>'."\n";

		$this->theme->setsrc($RTN);
		return	$this->style_header( $RTN );
	}



	#--------------------------------------
	#	プレーンなスタイル
	function style_plain( $SRC_CONTENTS ){
		#	PxFW 0.6.2 div#content で囲うようにした。
		$RTN = '';
		$RTN .= '<div id="content">'."\n";
		$RTN .= $SRC_CONTENTS."\n";
		$RTN .= '</div>'."\n";
		return	$this->style_header( $RTN );
	}

	#--------------------------------------
	#	インクルードファイル用スタイル(PxFW 0.7.2 追加)
	function style_include( $SRC_CONTENTS ){
		if( is_null( $SRC_CONTENTS ) ){ $SRC_CONTENTS = $this->theme->getsrc(); }

		$RTN = '';
		$RTN .= $SRC_CONTENTS;

		#	必要部分を抜き出し
		$src_start_str = $this->conf->contents_start_str;
		$src__end__str = $this->conf->contents_end_str;
		if( strlen( $src_start_str ) && strlen( $src__end__str ) ){
			$preg_ptn = '/'.preg_quote( $src_start_str , '/' ).'(.*)'.preg_quote( $src__end__str , '/' ).'/s';
			if( preg_match( $preg_ptn , $RTN , $results ) ){
				$RTN = $results[1];
			}
		}

		return	$RTN;
	}



	#--------------------------------------
	#	<body>セクションを囲う周囲のソース(主に<head>セクション)を定義
	function style_header( $SRC_CONTENTS ){
		if( is_null( $SRC_CONTENTS ) ){ $SRC_CONTENTS = $this->theme->getsrc(); }

		$RTN = '';
//		$RTN .= '<'.'?xml version="1.0" encoding="'.htmlspecialchars( $this->theme->get_output_encoding() ).'"?'.'>'."\n";//15:09 2008/08/11 削除。IE6を標準モードにするため。
		$RTN .= '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">'."\n";
		$RTN .= '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="'.htmlspecialchars( $this->user->getlanguage() ).'">'."\n";
		$RTN .= '	<head>'."\n";
		$RTN .= '		<meta http-equiv="content-type" content="'.htmlspecialchars( $this->theme->default_contenttype ).';charset='.htmlspecialchars( $this->theme->get_output_encoding() ).'" />'."\n";
		$RTN .= '		<meta http-equiv="content-script-type" content="text/javascript" />'."\n";
		$RTN .= '		<meta http-equiv="content-style-type" content="text/css" />'."\n";
		$RTN .= '		<meta name="copyright" content="'.$this->site->getcopyright('full').'" />'."\n";
		$RTN .= '		<meta name="author" content="" />'."\n";
		$RTN .= '		<meta name="keywords" content="'.htmlspecialchars( $this->site->getpageinfo( $this->req->p() , 'keywords' ) ).'" />'."\n";
		$RTN .= '		<meta name="description" content="'.htmlspecialchars( $this->site->getpageinfo( $this->req->p() , 'description' ) ).'" />'."\n";
		$RTN .= '		'.$this->theme->mk_title()."\n";
		#--------------------------------------
		#	Pickles CSS の読み込み
		#	23:54 2008/08/12 Pickles Framework 0.4.4 で、media type を1ファイルに統合。
		if( $this->theme->resource_exists('/common/css/pickles.css') ){
			$RTN .= '		<link rel="stylesheet" href="'.htmlspecialchars( $this->theme->resource('/common/css/pickles.css') ).'" type="text/css" media="all" />'."\n";
		}
		#	/ Pickles CSS の読み込み
		#--------------------------------------
		#--------------------------------------
		#	アウトライン部分のCSSの読み込み
		#	21:43 2010/12/23 Pickles Framework 0.6.12 で追加。PxCSSから分離したアウトライン部分のCSSを自動ロード。
		if( $this->theme->resource_exists('/common/css/outline.css') ){
			$RTN .= '		<link rel="stylesheet" href="'.htmlspecialchars( $this->theme->resource('/common/css/outline.css') ).'" type="text/css" media="all" />'."\n";
		}
		#	/ プロジェクト固有のモジュール定義CSSの読み込み
		#--------------------------------------
		#--------------------------------------
		#	プロジェクト固有のモジュール定義CSSの読み込み
		#	11:02 2009/01/23 Pickles Framework 0.5.7 で追加。デフォルトのパスを /common/css/modules.css に定め、自動ロードするようになった。
		if( $this->theme->resource_exists('/common/css/modules.css') ){
			$RTN .= '		<link rel="stylesheet" href="'.htmlspecialchars( $this->theme->resource('/common/css/modules.css') ).'" type="text/css" media="all" />'."\n";
		}
		#	/ プロジェクト固有のモジュール定義CSSの読み込み
		#--------------------------------------
		if( $this->theme->resource_exists('/common/js/Px.js') ){//Pickles Framework 0.5.2 追加
			$RTN .= '		<script type="text/javascript" src="'.htmlspecialchars( $this->theme->resource('/common/js/Px.js') ).'"></script>'."\n";
		}
		if( $this->theme->resource_exists('/common/js/PxResetLayout.js') ){//Pickles Framework 0.3.8 追加。Firefox対策
			$RTN .= '		<script type="text/javascript" src="'.htmlspecialchars( $this->theme->resource('/common/js/PxResetLayout.js') ).'"></script>'."\n";
		}

		if($this->theme->meta['iconpath']){
			$RTN .= '		<link rel="icon" href="'.$this->theme->meta['iconpath'].'" type="image/png" />'."\n";
		}
		$MEMO_MK_CSS = $this->theme->mk_css();//テーマが希望するCSS
		$MEMO_GETSRC_CSS = $this->theme->getsrc( 'css' );//(主に)コンテンツが希望するCSS
		if( strlen( $MEMO_MK_CSS ) || strlen( $MEMO_GETSRC_CSS ) ){
			$RTN .= '		<style type="text/css">'."\n";
			$RTN .= $MEMO_MK_CSS;
			$RTN .= $MEMO_GETSRC_CSS;
			$RTN .= '		</style>'."\n";
		}
		unset( $MEMO_MK_CSS , $MEMO_GETSRC_CSS );
		$RTN .= '		<script type="text/javascript">'."\n";
		$RTN .= '		function onLoadScript(){'."\n";
		if( $this->theme->resource_exists('/common/js/PxResetLayout.js') ){//Pickles Framework 0.3.8 追加。Firefox対策
			$RTN .= '			PxResetLayout();'."\n";
		}
		$RTN .= $this->theme->getsrc('onloadscript');
		$RTN .= '		}'."\n";
		$RTN .= '		</script>'."\n";
		$RTN .= $this->theme->autocreate_metanavigation();
		$RTN .= $this->theme->getsrc('additional_header');
		$RTN .= '	</head>'."\n";
		$RTN .= '	<body onload="onLoadScript();">'."\n";
		$RTN .= '		<div id="pagetop"></div>'."\n";
		$RTN .= '		<div id="outline">'."\n";
		$RTN .= $SRC_CONTENTS."\n";
		$RTN .= '		</div>'."\n";
		$RTN .= '	</body>'."\n";
		$RTN .= '</html>'."\n";
		return	$RTN;
	}

}

?>