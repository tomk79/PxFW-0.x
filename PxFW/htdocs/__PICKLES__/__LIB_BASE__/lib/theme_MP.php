<?php

#	Copyright (C)Tomoya Koyanagi.
#	LastUpdate : 12:31 2010/04/15

require_once( $conf->path_lib_project.'/lib/theme.php' );

#******************************************************************************************************************
#	HTMLドキュメントのクラス
class base_lib_theme_MP extends project_lib_theme{


	#----------------------------------------------------------------------------
	#	パンくずのソースを生成し、$src['breadcrumb']に格納する
	function set_breadcrumb( $Category = null , $sep = ' &gt; ' , $option = array() ){
		#	Pickles Framework 0.5.10 : ページタイトルの改行を画面に反映しないようになった。
		#	Pickles Framework 0.6.8 : PC版がulタグになったので、MPで上書くようにした。
		$option = $this->parseoption( $option );
		$RTN = '';

		if( !strlen( $this->site->getpageinfo( $this->req->p() , 'id' ) ) ){
			$this->setsrc( $this->site->getpageinfo('','title_breadcrumb') , 'breadcrumb' );
			return	true;
		}else{
			$RTN .= $this->mk_link( '' , array( 'active'=>'no' ) );
		}

		$Cat = explode( '/' , $Category );

		foreach( $Cat as $Line ){
			if( !strlen( $Line ) ){ continue; }
			if( !strlen( $this->site->getpageinfo( $Line , 'title_breadcrumb' ) ) ){ continue; }

			if( $this->site->getpageinfo( $Line , 'id' ) != $this->site->getpageinfo( $this->req->p() , 'id' ) ){//← PxFW 0.6.5 : 分岐ロジックを変更した
				$RTN .= $sep.$this->mk_link( $Line , array( 'active'=>'no' , 'allow_html'=>true , 'label'=>htmlspecialchars( $this->site->getpageinfo( $Line , 'title_breadcrumb' ) ) ) );
			}else{
				$RTN .= $sep.htmlspecialchars( $this->site->getpageinfo( $Line , 'title_breadcrumb' ) );
			}
		}

		$this->setsrc( $RTN , 'breadcrumb' );
		return	true;
	}

	#----------------------------------------------------------------------------
	#	★ログインフォームを表示して終了
	function pleaselogin(){
		while( @ob_end_clean() );
		$loginerror = null;
		if( $this->user->is_login_error() ){
			$loginerror = $this->user->get_label_login_id().'またはパスワードが違います。';
		}

		$RTN = '';
		if( $this->conf->user_auth_method == 'basic' ){
			#--------------------------------------
			#	Pickles Framework 0.3.6 で追加された
			#	新しい設定項目に対応する処理。(Pickles Framework 0.3.7 で修正)
			$basic_auth_realm = $this->site->gettitle();
			$basic_auth_realm = preg_replace( '/["\']|\r\n|\r|\n/' , ' ' , $basic_auth_realm );
			header( 'Status: 401 Authorization Required' );
			header( 'WWW-Authenticate: Basic realm="'.addslashes( $basic_auth_realm ).'"' );//realmはサイトタイトルから取得するようにした。
			$RTN .= $this->mk_hx('認証に失敗しました。')."\n";
			$RTN .= '<p class="ttr">'."\n";
			$RTN .= '	'.htmlspecialchars( $this->user->get_label_login_id() ).'とパスワードを正しく入力してください。<br />'."\n";
			$RTN .= '</p>'."\n";
			$RTN .= '<form action="'.htmlspecialchars( $this->act() ).'" method="post" target="_top">'."\n";
			$RTN .= '	'.$this->mk_form_defvalues()."\n";
			if( strlen( $this->conf->try_to_login ) ){
				$RTN .= '	'.$this->mk_formelm_hidden( $this->conf->try_to_login , '1' )."\n";
			}
			$RTN .= '	<input type="submit" name="submit" value="再試行" />'."\n";
			$RTN .= '</form>'."\n";

		}else{
			$RTN .= '<h1>ログインしてください。</h1>'."\n";
			if( strlen( $loginerror ) ){
				#	エラーがあれば表示
				$RTN .= '<div class="error" align="center">'.htmlspecialchars( $loginerror ).'</div>'."\n";
			}

			$RTN .= '<form action="'.htmlspecialchars( $this->act() ).'" method="post" name="login" target="_top">'."\n";
			$RTN .= '	<div class="ttr">'.htmlspecialchars( $this->user->get_label_login_id() ).':</div>'."\n";
			$RTN .= '	<div class="ttr"><input type="text" name="ID" value="'.htmlspecialchars( $this->req->in('ID') ).'" /></div>'."\n";
			$RTN .= '	<div class="ttr">パスワード:</div>'."\n";
			$RTN .= '	<div class="ttr"><input type="password" name="PW" value="" /></div>'."\n";
			$RTN .= '	'.$this->mk_form_defvalues()."\n";
			if( strlen( $this->conf->try_to_login ) ){
				$RTN .= '	'.$this->mk_formelm_hidden( $this->conf->try_to_login , '1' )."\n";
			}
			$RTN .= '	<input type="submit" name="submit" value="ログイン">'."\n";
			$RTN .= '</form>'."\n";

		}

		$this->setsrc( $RTN );
		return	$this->print_and_exit();
	}

	#----------------------------------------------------------------------------
	#	ページャーを生成する //←PxFW 0.6.3：追加
	function mk_pager( $total_count , $current_page_num , $display_per_page = 10 , $option = array() ){
		$pagerinfo = $this->dbh->get_pager_info( $total_count , $current_page_num , $display_per_page , $option );
		$href = ':${num}';
		if( strlen( $option['href'] ) ){
			$href = $option['href'];
		}

		$RTN = '';
		$RTN .= '<div align="center" class="pager">'."\n";
		$RTN .= '<p class="ttr">'."\n";
		if( $pagerinfo['first'] ){
			$RTN .= '	'.$this->mk_link( str_replace( '${num}' , $pagerinfo['first'] , $href ) , array('label'=>'<<最初','active'=>false) ).''."\n";
		}else{
			$RTN .= '	'.htmlspecialchars('<<最初').''."\n";
		}
		if( $pagerinfo['prev'] ){
			$RTN .= '	'.$this->mk_link( str_replace( '${num}' , $pagerinfo['prev'] , $href ) , array('label'=>'<前へ','active'=>false) ).''."\n";
		}else{
			$RTN .= '	'.htmlspecialchars('<前へ').''."\n";
		}
		for( $i = $pagerinfo['index_start']; $i <= $pagerinfo['index_end']; $i ++ ){
			$current_href = str_replace( '${num}' , $i , $href );
			if( $pagerinfo['current'] == $i ){
				$RTN .= '	<strong>'.intval($i).'</strong>'."\n";
			}else{
				$RTN .= '	'.$this->mk_link( $current_href , array('label'=>intval($i),'active'=>false) ).''."\n";
			}
		}
		if( $pagerinfo['next'] ){
			$RTN .= '	'.$this->mk_link( str_replace( '${num}' , $pagerinfo['next'] , $href ) , array('label'=>'次へ>','active'=>false) ).''."\n";
		}else{
			$RTN .= '	'.htmlspecialchars('次へ>').''."\n";
		}
		if( $pagerinfo['last'] ){
			$RTN .= '	'.$this->mk_link( str_replace( '${num}' , $pagerinfo['last'] , $href ) , array('label'=>'最後>>','active'=>false) ).''."\n";
		}else{
			$RTN .= '	'.htmlspecialchars('最後>>').''."\n";
		}
		$RTN .= '</p>'."\n";
		$RTN .= '</div>'."\n";
		return $RTN;
	}

	#--------------------------------------------------------------------------------------------------------------------------------------------------------

	#----------------------------------------------------------------------------
	#	分割レイアウトを作成
	function mk_splitedfield( $args , $option = null ){
		$option = $this->parseoption( $option );

		$fieldcount = count($args);
		$fieldwidth_atone = floor(100/$fieldcount);

		foreach( $args as $Line){
			$RTN .= '<div>'.$Line.'</div>'."\n";
		}
		return	$RTN;
	}

	#----------------------------------------------------------------------------
	#	スタイルシートを書き出す
	function mk_css(){
		return	'';
	}


	#----------------------------------------------------------------------------
	#	ページの先頭へ戻るリンクのソースを返す(デザイン部分)
	#	Pickles Framework 0.4.4 追加
	function view_mk_back2top( $option = array() ){
		$RTN = '';
		$RTN .= '<div align="right"><font size="1"><a href="#outline">↑先頭へ戻る</a></font></div>'."\n";
		return	$RTN;
	}




	#----------------------------------------------------------------------------
	#	★リダイレクトする
	function redirect( $pElm = null , $additional = null , $option = null ){
		static $modeAct_done = null;

		#--------------------------------------
		#	リダイレクト先を決定
		$redirect = $this->href( $pElm , array( 'additionalquery'=>$additional ) );
		if( strlen( $pElm ) ){
			$this->req->setin( $this->req->pkey() , $pElm );
		}elseif( strlen( $this->req->in($this->req->pkey()) ) ){
			#	なし
		}else{
			$this->req->setin( $this->req->pkey() , '' );
		}
		#	/リダイレクト先を決定
		#--------------------------------------

		if( ( $this->user->get_browser_name() != 'CNF' && $this->user->get_browser_name() != 'J-PHONE' && $this->user->get_browser_name() != 'Mozilla' ) ){
			if( !@header( 'Location: '.$redirect ) ){
				$this->errors->error_log( 'header( Location ) operation Faild.' , __FILE__ , __LINE__ );
			}
		}

		if( $this->user->is_mp() && !$modeAct_done ){
			#--------------------------------------
			#	リフレッシュ機能が使用不可だった場合、
			#	&{ReturnToStart}を実行する。
			#	&{ReturnToStart}は、表示処理を最初からやり直す
			#	ものであるとし、宣言されていなかった場合は、
			#	続行ボタンを表示して、ユーザの手動にて次へ送る。
			#--------------------------------------
			$modeAct_done = 1;
		}

		$FIN = '';
		$FIN .= '<'.'!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">'."\n";
		$FIN .= '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ja">'."\n";
		$FIN .= '	<head>'."\n";
		$FIN .= '		<meta http-equiv="content-type" content="'.htmlspecialchars( $this->default_contenttype ).';charset=Shift'.'_JIS" />'."\n";
		$FIN .= '		<title>'.htmlspecialchars( $this->site->gettitle() ).'</title>'."\n";
		$FIN .= '		<meta http-equiv="refresh" content="0;url='.htmlspecialchars( $redirect ).'" />'."\n";
		$FIN .= '	</head>'."\n";
		$FIN .= '	<body>'."\n";
		$FIN .= '処理を完了しました。<br />'."\n";
		if(!$this->user->UA['EnableRefresh']){
			$FIN .= '		<a href="'.htmlspecialchars( $redirect ).'">了解</a>'."\n";
		}
		$FIN .= '	</body>'."\n";
		$FIN .= '</html>';
		$this->set_output_encoding( 'sjis' );
		return	$this->print_and_exit( $FIN );
	}

}

?>