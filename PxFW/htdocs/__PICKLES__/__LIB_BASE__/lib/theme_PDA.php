<?php

#	Copyright (C)Tomoya Koyanagi.
#	LastUpdate : 1:55 2008/11/20

require_once( $conf->path_lib_project.'/lib/theme.php' );

#******************************************************************************************************************
#	HTMLドキュメントのクラス
class base_lib_theme_PDA extends project_lib_theme{

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
			#	新しい設定項目に対応する処理。
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
			$RTN .= '	<input type="submit" name="submit" value="ログイン" />'."\n";
			$RTN .= '</form>'."\n";

		}
		$this->setsrc( $RTN );
		return	$this->print_and_exit();
	}


	#----------------------------------------------------------------------------
	#	分割レイアウトを作成
	function mk_splitedfield( $args , $option = null ){
		if( is_array( $option ) ){
			$opt = $option;
		}else{
			$opt = $this->parseOption($option,'&','=');
		}

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
		$this->set_fontsize_on_css(12);
		$fs = $this->fontsize;
		$RTN .= '			body,table,tr,td,th,ul,ol,li,h1,h2,h3,h4,h5,h6'."\n";
		$RTN .= '				{color:'.$this->getcolor('TEXT').'; '.$this->getdefaultfontname_on_css().'}'."\n";
		$RTN .= '			input,textarea,select,option'."\n";
		$RTN .= '				{color:'.$this->getcolor('TEXT').'; '.$this->getdefaultfontname_on_css().'}'."\n";
		$RTN .= '			a						{color:'.$this->getcolor('LINK').'; text-decoration:none; }'."\n";
		$RTN .= '			a:hover					{color:'.$this->getcolor('LINK_HOVER').'; text-decoration:underline; }'."\n";
		$RTN .= '			a.'.$this->classname_of_activelink.'				{color:'.$this->getcolor('LINK_CURRENT').'; text-decoration:underline; font-weight:bold; }'."\n";
		$RTN .= '			a.'.$this->classname_of_activelink.':hover			{color:'.$this->getcolor('LINK_CURRENT_HOVER').'; text-decoration:none; font-weight:bold; }'."\n";
		$RTN .= '			a.plain					{color:'.$this->getcolor('TEXT').'; text-decoration:none; }'."\n";
		$RTN .= '			a.plain:hover			{color:'.$this->getcolor('LINK_HOVER').'; text-decoration:none; }'."\n";
		$RTN .= '			table,td,th				{empty-cells:show; }'."\n";
		$RTN .= '			p						{padding:0px 0px 0px 0px; margin:0px 0px 1.5em 0px; }'."\n";
		$RTN .= '			.p						{padding:0px 0px 0px 0px; margin:0px 0px 1.5em 0px; display:block; }'."\n";
		$RTN .= '			blockquote				{font-size:'.$fs[12].'; line-height:150%; background-color:#eeeeee; color:'.$this->getcolor('TEXT').'; border:1px solid '.$this->getcolor('BG').'; padding:9px; }'."\n";
		$RTN .= '			form					{display:inline; }'."\n";
		$RTN .= '			h1,h2,h3,h4,h5,h6		{font-size:'.$fs[12].'; line-height:150%; padding:0px 0px 0px 0px; margin:0px 0px 0.5em 0px; }'."\n";
		$RTN .= '			.ptitle					{margin:0px 0px 1em 0px; }'."\n";
		$RTN .= '			.ttrll					{font-size:'.$fs[16].'; line-height:150%; }'."\n";
		$RTN .= '			.ttrl					{font-size:'.$fs[14].'; line-height:150%; }'."\n";
		$RTN .= '			.ttr					{font-size:'.$fs[12].'; line-height:150%; }'."\n";
		$RTN .= '			.ttrs					{font-size:'.$fs[11].'; line-height:150%; }'."\n";
		$RTN .= '			.ttrss					{font-size:'.$fs[10].'; line-height:150%; }'."\n";
		$RTN .= '			.fstll					{font-size:16px; line-height:150%; }'."\n";
		$RTN .= '			.fstl					{font-size:14px; line-height:150%; }'."\n";
		$RTN .= '			.fst					{font-size:12px; line-height:150%; }'."\n";
		$RTN .= '			.fsts					{font-size:10px; line-height:150%; }'."\n";
		$RTN .= '			.fstss					{font-size:9px; line-height:150%; }'."\n";
		$RTN .= '			.breadcrumb					{font-size:'.$fs[11].'; background-color:#dddddd; padding:4px; }'."\n";
		$RTN .= '			.contentmargin			{padding-left:5px; padding-right:5px; }'."\n";
		$RTN .= '			.inputitems				{width:100%; }'."\n";
		$RTN .= '			.attention				{font-size:'.$fs[10].'; line-height:150%; color:'.$this->getcolor('ATTENTION').'; }'."\n";
		$RTN .= '			.error					{font-size:'.$fs[12].'; line-height:150%; color:'.$this->getcolor('ERROR').'; }'."\n";
		$RTN .= '			.must					{color:'.$this->getcolor('MUST').'; }'."\n";
		$RTN .= '			table.deftable			{empty-cells:show; margin:0px 0px 24px 0px; border-top:1px solid #CCCCCC; border-left:1px solid #CCCCCC; border-bottom:0px none '.$this->getcolor('BG').'; border-right:0px none '.$this->getcolor('BG').'; }'."\n";
		$RTN .= '			table.deftable tr		{}'."\n";
		$RTN .= '			table.deftable tr th	{empty-cells:show; font-weight:normal; background-color:#eeeeee; color:'.$this->getcolor('TEXT').'; padding:3px 3px 3px 3px; border-bottom:1px solid #CCCCCC; border-right:1px solid #CCCCCC; border-top:0px none '.$this->getcolor('BG').'; border-left:0px none '.$this->getcolor('BG').'; }'."\n";
		$RTN .= '			table.deftable tr td	{empty-cells:show; font-weight:normal; background-color:'.$this->getcolor('BG').'; color:'.$this->getcolor('TEXT').'; padding:3px 3px 3px 3px; border-bottom:1px solid #CCCCCC; border-right:1px solid #CCCCCC; border-top:0px none #FFFFFF; border-left:0px none '.$this->getcolor('BG').'; }'."\n";
		$RTN .= '			.defhr,.defsep			{height:0px; border: 0px solid '.$this->getcolor('BG').'; border-bottom: 1px dashed #999999; margin-top:5px; margin-bottom:5px; }'."\n";

		return	$RTN;
	}

	#----------------------------------------------------------------------------
	#	フォントサイズのCSS上の指定値を計算する
	function set_fontsize_on_css( $normalsize = 12 ){
		$UA = $this->user->UA;
		#	$normalsize には、標準の文字サイズのときの
		#	見た目の大きさを数値型(単位はpxになる)で指定してください。
		if( $UA['BSR'] == 'MSIE' && $UA['OS'] == 'Windows' ){
			for( $i = 6; $i < 24; $i++ ){
				$RTN[$i] = math::rounddown( (($i/16)*(1/12*$normalsize)*100) , 3 ).'%';
			}
		}else{
			for( $i = 6; $i < 24; $i++ ){
				$RTN[$i] = math::rounddown( $i*(1/12*$normalsize) , 3 ).'px';
			}
		}
		$this->fontsize = $RTN;
		return	$RTN;
	}

}

?>