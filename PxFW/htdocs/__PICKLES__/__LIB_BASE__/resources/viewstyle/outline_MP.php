<?php

#	Copyright (C)Tomoya Koyanagi.
#	Last Update : 23:10 2011/04/10

#******************************************************************************************************************
#	基本スタイルの管理

require_once( $conf->path_lib_base.'/resources/viewstyle/outline.php' );

class base_resources_viewstyle_outline_MP extends base_resources_viewstyle_outline{


	#----------------------------------------------------------------------------
	#	★標準のテンプレート
	function style_( $CONTENT = null ){
		if( is_null( $CONTENT ) ){ $CONTENT = $this->theme->getsrc(); }

		$gmenu = $this->theme->get_menulist( 'global' );
		$smenu = $this->theme->get_menulist( 'shoulder' );

		$this->theme->set_breadcrumb( $this->site->getpageinfo( $this->req->p() , 'path' ) );

		$RTN = '';
		$RTN .= '<div align="center"><strong>'.htmlspecialchars( $this->site->gettitle() ).'</strong></div>'."\n";
		$RTN .= '<hr size="1" color="#999999" />'."\n";
		$RTN .= '<div><font size="+1">'.htmlspecialchars( $this->theme->gettitle('page') ).'</font></div>'."\n";
		$RTN .= '<hr size="1" color="#999999" />'."\n";
		$RTN .= '<div>'."\n";
		$RTN .= $CONTENT."\n";
		$RTN .= '</div>'."\n";
		$RTN .= '<font size="1"><br /></font>'."\n";
		$RTN .= '<div><font size="1">'.$this->theme->getsrc('breadcrumb').'</font></div>'."\n";//パンくず
		$RTN .= '<hr size="1" color="#999999" />'."\n";

		$RTN .= '<div>'."\n";
		if( count( $gmenu ) ){
			foreach( $gmenu as $gmenu_pid ){
				$RTN .= ''.$this->theme->mk_link($gmenu_pid,array('style'=>'inside')).'<br />'."\n";
			}
		}
		if( count( $smenu ) ){
			foreach( $smenu as $smenu_pid ){
				$RTN .= ''.$this->theme->mk_link($smenu_pid,array('style'=>'inside')).'<br />'."\n";
			}
		}
		if( $this->user->is_login() ){
			$RTN .= $this->theme->mk_link( 'logout' ).'<br />'."\n";
		}
		$RTN .= '</div>'."\n";
		$RTN .= '<div align="center"><font size="1">'.$this->site->getcopyright('print',$this->user->get_ct()).'</font></div>'."\n";

		$this->theme->setsrc($RTN);
		return	$this->style_plain($RTN);
	}


	#--------------------------------------
	#	インクルードファイル用スタイル(PxFW 0.7.2 追加)
	function style_include( $SRC_CONTENTS ){
		if( is_null( $SRC_CONTENTS ) ){ $SRC_CONTENTS = $this->theme->getsrc(); }
		$this->theme->set_output_encoding( 'Shift_JIS' );

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

		$RTN = preg_replace( '/<br(.*?) \/>/i' , '<br$1>' , $RTN );
		$RTN = preg_replace( '/<img(.*?) \/>/i' , '<img$1>' , $RTN );
		$RTN = preg_replace( '/<hr(.*?) \/>/i' , '<hr$1>' , $RTN );
		$RTN = preg_replace( '/<meta(.*?) \/>/i' , '<meta$1>' , $RTN );
		$RTN = preg_replace( '/<link(.*?) \/>/i' , '<link$1>' , $RTN );
		$RTN = preg_replace( '/<p(.*?)>/i' , '<div>' , $RTN );
		$RTN = preg_replace( '/<\/(h[1-6])>/i' , '</$1><font size="1"><br></font>' , $RTN );
		$RTN = preg_replace( '/<\/p>/i' , '</div><font size="1"><br></font>' , $RTN );
		$RTN = preg_replace( '/<\/ul>/i' , '</ul><font size="1"><br></font>' , $RTN );
		$RTN = preg_replace( '/<\/ol>/i' , '</ol><font size="1"><br></font>' , $RTN );
		$RTN = preg_replace( '/<\/blockquote>/i' , '</blockquote><font size="1"><br></font>' , $RTN );

		return	$RTN;
	}


	#----------------------------------------------------------------------------
	#	★テンプレートの外側
	function style_header( $CONTENT = null ){
		if( is_null( $CONTENT ) ){ $CONTENT = $this->theme->getsrc(); }
		$this->theme->set_output_encoding( 'Shift_JIS' );

		$RTN = '';
		$RTN .= '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">'."\n";
		$RTN .= '<html>'."\n";
		$RTN .= '	<head>'."\n";
		$RTN .= '		<meta http-equiv="content-type" content="text/html;charset='.htmlspecialchars( $this->theme->get_output_encoding() ).'" />'."\n";
		$RTN .= '		<meta name="keywords" content="'.htmlspecialchars( text::hankana( $this->site->getpageinfo( $this->req->p() , 'keywords' ) ) ).'" />'."\n";
		$RTN .= '		<meta name="description" content="'.htmlspecialchars( text::hankana( $this->site->getpageinfo( $this->req->p() , 'description' ) ) ).'" />'."\n";
		$RTN .= '		<meta name="copyright" content="'.$this->site->getcopyright('full',$this->user->get_ct()).'" />'."\n";
		$RTN .= '		'.$this->theme->mk_title()."\n";
		$CSSSRC = $this->theme->mk_css();
		if( strlen( $CSSSRC ) ){
			$RTN .= '		<style type="text/css">'."\n";
			$RTN .= '		<!--'."\n";
			$RTN .= $CSSSRC;
			$RTN .= '		-->'."\n";
			$RTN .= '		</style>'."\n";
		}
		$RTN .= '	</head>'."\n";
		$RTN .= '	<body text="'.htmlspecialchars( $this->theme->getcolor('TEXT') ).'" link="'.htmlspecialchars( $this->theme->getcolor('LINK') ).'" bgcolor="'.htmlspecialchars( $this->theme->getcolor('BG') ).'">'."\n";
		$RTN .= '		<a id="pagetop" name="pagetop"></a>'."\n";
		$RTN .= $CONTENT."\n";
		$RTN .= '	</body>'."\n";
		$RTN .= '</html>'."\n";

		#--------------------------------------
		#	半角カナに変換
		$className = $this->dbh->require_lib( '/resources/html2hankana.php' );
		if( $className ){
			$html2hankana = new $className();
			$html2hankana->html_parse( $RTN );
			$RTN = $html2hankana->publish();
		}
		#	/ 半角カナに変換
		#--------------------------------------

		$RTN = preg_replace( '/<br(.*?) \/>/i' , '<br$1>' , $RTN );
		$RTN = preg_replace( '/<img(.*?) \/>/i' , '<img$1>' , $RTN );
		$RTN = preg_replace( '/<hr(.*?) \/>/i' , '<hr$1>' , $RTN );
		$RTN = preg_replace( '/<meta(.*?) \/>/i' , '<meta$1>' , $RTN );
		$RTN = preg_replace( '/<link(.*?) \/>/i' , '<link$1>' , $RTN );
		$RTN = preg_replace( '/<p(.*?)>/i' , '<div>' , $RTN );
		$RTN = preg_replace( '/<\/(h[1-6])>/i' , '</$1><font size="1"><br></font>' , $RTN );
		$RTN = preg_replace( '/<\/p>/i' , '</div><font size="1"><br></font>' , $RTN );
		$RTN = preg_replace( '/<\/ul>/i' , '</ul><font size="1"><br></font>' , $RTN );
		$RTN = preg_replace( '/<\/ol>/i' , '</ol><font size="1"><br></font>' , $RTN );
		$RTN = preg_replace( '/<\/blockquote>/i' , '</blockquote><font size="1"><br></font>' , $RTN );

		return	$RTN;
	}

}

?>