<?php

#	Copyright (C)Tomoya Koyanagi.
#	Last Update : 0:02 2010/02/04

#******************************************************************************************************************
#	テーマの情報を出力
#		Pickles Framework 0.6.7 で追加
class base_resources_picklesinfo_themeinfo{

	var $conf;
	var $dbh;
	var $user;

	#----------------------------------------------------------------------------
	#	コンストラクタ
	function base_resources_picklesinfo_themeinfo( &$conf , &$dbh , &$user ){
		$this->conf = &$conf;
		$this->dbh = &$dbh;
		$this->user = &$user;
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
		<title>Pickles Framework : Theme info [<?php print htmlspecialchars( $this->user->get_theme() ); ?>] for [<?php print htmlspecialchars( $this->user->get_ct() ); ?>]</title>
		<style type="text/css">
			#logo	{font-weight:bold; font-size:24px; border-bottom:1px solid #999999; font-family:"Helvetica","Arial"; }
			h1		{font-weight:bold; font-size:18px; background-color:#f3f3f3; padding:5px; border-left:3px solid #666666; font-family:"Helvetica","Arial"; }
			.error	{color:#ff0000; }
			table.deftable			{empty-cells:show; margin:0px 0px 24px 0px; border-collapse:collapse; table-layout: fixed; }
			table.deftable caption	{}
			table.deftable tr		{}
			table.deftable tr th	{font-size:13px; empty-cells:show; border:1px solid #cccccc; font-weight:normal; background-color:#eeeeee; color:#333333; padding:3px 3px 3px 3px; text-align:left; width:20%; }
			table.deftable tr td	{font-size:13px; empty-cells:show; border:1px solid #cccccc; font-weight:normal; background-color:#ffffff; color:#333333; padding:3px 3px 3px 3px; text-align:left; width:80%; }
			table.deftable thead th	{font-size:13px; background-color:#bbbbbb; }
			table.deftable thead td	{font-size:13px; background-color:#bbbbbb; }
			table.deftable tfoot th	{font-size:13px; background-color:#bbbbbb; }
			table.deftable tfoot td	{font-size:13px; background-color:#bbbbbb; }
		</style>
	</head>
	<body>
		<div id="logo">Pickles Framework</div>
		<h1>Theme info [<?php print htmlspecialchars( $this->user->get_theme() ); ?>] for [<?php print htmlspecialchars( $this->user->get_ct() ); ?>]</h1>
<?php }else{
			#	コマンドラインへの応答
			print '---- Pickles Framework ----'."\n";
			print '-- Theme info ['.$this->user->get_theme().'] for ['.$this->user->get_ct().']'."\n";
		}

		$RTN = '';

		if( !is_null( $_SERVER['REMOTE_ADDR'] ) ){
			#--------------------------------------
			#	WEB ACCESS
			$RTN .= '<p class="ttr"><a href="'.htmlspecialchars( $this->conf->url_action ).'?THEME='.htmlspecialchars( $this->user->get_theme() ).'">Select this theme</a>.</p>'."\n";
			$RTN .= '<table class="deftable" width="100%">'."\n";
			$RTN .= '	<tr>'."\n";
			$RTN .= '		<th>Theme ID</th>'."\n";
			$RTN .= '		<td>'.htmlspecialchars( $this->user->get_theme() ).'</td>'."\n";
			$RTN .= '	</tr>'."\n";
			$RTN .= '	<tr>'."\n";
			$RTN .= '		<th>CT</th>'."\n";
			$RTN .= '		<td>'.htmlspecialchars( $this->user->get_ct() ).'</td>'."\n";
			$RTN .= '	</tr>'."\n";
			$RTN .= '	<tr>'."\n";
			$RTN .= '		<th>Theme Directory</th>'."\n";
			$path_theme_dir = $this->conf->path_theme_collection_dir.$this->user->get_theme().'/'.$this->user->get_ct();
			if( !strlen( $this->user->get_theme() ) ){
				$RTN .= '		<td>(none)</td>'."\n";
			}elseif( is_dir( $path_theme_dir ) ){
				$RTN .= '		<td>'.htmlspecialchars( realpath( $path_theme_dir ) ).'</td>'."\n";
			}else{
				$RTN .= '		<td>'.htmlspecialchars( $this->dbh->get_realpath( $path_theme_dir ) ).'</td>'."\n";
			}
			$RTN .= '	</tr>'."\n";
			$RTN .= '</table>'."\n";
			$RTN .= '<h2>Theme resources</h2>'."\n";
			$RTN .= $this->mk_dirtree( $path_theme_dir.'/public.items' );
			$RTN .= '<h2>Libraries</h2>'."\n";
			$RTN .= $this->mk_dirtree( $path_theme_dir.'/lib' );
			$RTN .= '<h2>Theme Contents</h2>'."\n";
			$RTN .= $this->mk_dirtree( $path_theme_dir.'/contents' );
			$RTN .= '<p class="ttr"><a href="'.htmlspecialchars( $this->conf->url_action ).'?PICKLESINFO=themes">Show all themes</a></p>'."\n";

		}else{
			#--------------------------------------
			#	COMMAND LINE
			$RTN .= ''."\n";
			$RTN .= '** Theme ID'."\n";
			$RTN .= '    '.$this->user->get_theme().''."\n";
			$RTN .= '** CT'."\n";
			$RTN .= '    '.$this->user->get_ct().''."\n";
			$RTN .= '** Theme Directory'."\n";
			$path_theme_dir = $this->conf->path_theme_collection_dir.$this->user->get_theme().'/'.$this->user->get_ct();
			if( !strlen( $this->user->get_theme() ) ){
				$RTN .= '    (none)'."\n";
			}elseif( is_dir( $path_theme_dir ) ){
				$RTN .= '    '.realpath( $path_theme_dir ).''."\n";
			}else{
				$RTN .= '    '.$this->dbh->get_realpath( $path_theme_dir ).''."\n";
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

	#--------------------------------------
	#	ディレクトリツリーを作成する
	function mk_dirtree( $basedir , $localpath = null ){
		if( !is_dir( $basedir ) ){
			return '<p class="ttr">No directory.</p>'."\n";
		}
		$RTN = '';
		$filelist = $this->dbh->getfilelist( $basedir.$localpath );
		if( is_array( $filelist ) && count( $filelist ) ){
			$RTN .= '<ul>'."\n";
			foreach( $filelist as $basename ){
				if( is_dir( $basedir.$localpath.'/'.$basename ) ){
					$RTN .= '<li style="list-style-type:square;"><span style="font-weight:bold;">'.htmlspecialchars( $basename ).'</span>';
					$RTN .= $this->mk_dirtree( $basedir , $localpath.'/'.$basename );
					$RTN .= '</li>'."\n";
				}elseif( is_file( $basedir.$localpath.'/'.$basename ) ){
					$RTN .= '<li style="list-style-type:circle;"><span>'.htmlspecialchars( $basename ).'</span></li>'."\n";
				}
			}
			$RTN .= '</ul>'."\n";
		}else{
			if( !strlen( $localpath ) ){
				return '<p class="ttr">No Item.</p>'."\n";
			}
		}
		return $RTN;
	}

}

?>