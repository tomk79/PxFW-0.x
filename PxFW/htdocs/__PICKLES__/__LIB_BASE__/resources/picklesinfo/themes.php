<?php

#	Copyright (C)Tomoya Koyanagi.
#	Last Update : 0:02 2010/02/04

#******************************************************************************************************************
#	テーマの一覧を出力
#		Pickles Framework 0.6.7 で追加
class base_resources_picklesinfo_themes{

	var $conf;
	var $dbh;

	#----------------------------------------------------------------------------
	#	コンストラクタ
	function base_resources_picklesinfo_themes( &$conf , &$dbh ){
		$this->conf = &$conf;
		$this->dbh = &$dbh;
	}

	#----------------------------------------------------------------------------
	#	テーマの一覧を表示する
	function execute(){
		#	Pickles Framework 0.6.7 で追加

		if( strlen( $this->conf->php_mb_internal_encoding ) ){
			#	内部エンコード調整
			mb_internal_encoding( $this->conf->php_mb_internal_encoding );
		}

		if( !is_null( $_SERVER['REMOTE_ADDR'] ) ){
			#--------------------------------------
			#	ウェブアクセスへの応答
			header( 'Content-type: text/html; charset='.mb_internal_encoding() );
			print	'<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">'."\n";
			?><html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ja">
	<head>
		<meta http-equiv="content-type" content="text/html;charset=<?php print htmlspecialchars( mb_internal_encoding() ); ?>" />
		<title>Pickles Framework : Themes</title>
		<style type="text/css">
			#logo	{font-weight:bold; font-size:24px; border-bottom:1px solid #999999; font-family:"Helvetica","Arial"; }
			h1		{font-weight:bold; font-size:18px; background-color:#f3f3f3; padding:5px; border-left:3px solid #666666; font-family:"Helvetica","Arial"; }
			.error	{color:#ff0000; }
		</style>
	</head>
	<body>
		<div id="logo">Pickles Framework</div>
		<h1>Themes</h1>
<?php }else{
			#	コマンドラインへの応答
			print '---- Pickles Framework ----'."\n";
			print '-- Themes'."\n";
		}

		$RTN = '';

		$theme_list = $this->dbh->getfilelist( $this->conf->path_theme_collection_dir );
		@sort( $theme_list );

		if( !is_null( $_SERVER['REMOTE_ADDR'] ) ){
			#--------------------------------------
			#	WEB ACCESS

			$RTN .= '<p class="ttr">'.count($theme_list).' theme(s)</p>'."\n";

			if( is_array( $theme_list ) && count( $theme_list ) ){
				$RTN .= '<ul>'."\n";
				foreach( $theme_list as $theme_id ){
					$RTN .= '<li class="ttr">'.htmlspecialchars($theme_id).''."\n";
					$ct_list = $this->dbh->getfilelist( $this->conf->path_theme_collection_dir.$theme_id );
					@sort( $ct_list );
					if( is_array( $ct_list ) && count( $ct_list ) ){
						$RTN .= '<ul>'."\n";
						foreach( $ct_list as $ct_id ){
							$RTN .= '<li class="ttr">'.htmlspecialchars($ct_id).'';
							$RTN .= ' | <a href="'.htmlspecialchars( $this->conf->url_action ).'?PICKLESINFO=themeinfo&amp;THEME='.htmlspecialchars($theme_id).'&amp;CT='.htmlspecialchars($ct_id).'">property</a>';
							$RTN .= ' | <a href="'.htmlspecialchars( $this->conf->url_action ).'?THEME='.htmlspecialchars($theme_id).'&amp;CT='.htmlspecialchars($ct_id).'">select this theme</a>';
							$RTN .= '</li>'."\n";
						}
						$RTN .= '</ul>'."\n";
					}
					$RTN .= '</li>'."\n";
				}
				$RTN .= '</ul>'."\n";
			}
			$RTN .= '<p class="ttr">Or select <a href="'.htmlspecialchars( $this->conf->url_action ).'?THEME=null">theme &quot;null&quot;</a>.</p>'."\n";

		}else{
			#--------------------------------------
			#	COMMAND LINE
			$RTN .= ''."\n";
			$RTN .= ''.count($theme_list).' theme(s)'."\n";
			$RTN .= ''."\n";
			foreach( $theme_list as $theme_id ){
				$RTN .= '** '.htmlspecialchars($theme_id).''."\n";
			}
			$RTN .= ''."\n";
		}

		print	$RTN;
		if( !is_null( $_SERVER['REMOTE_ADDR'] ) ){
			#--------------------------------------
			#	ウェブアクセスへの応答
?>	</body>
</html>
<?php }
		return	true;
	}

}

?>