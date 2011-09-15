<?php

#	Copyright (C)Tomoya Koyanagi.
#	Last Update : 4:39 2011/01/30

#******************************************************************************************************************
#	サイトマップを出力
#		Pickles Framework 0.6.7 で追加
class base_resources_picklesinfo_sitemap{

	var $conf;
	var $req;
	var $site;

	#----------------------------------------------------------------------------
	#	コンストラクタ
	function base_resources_picklesinfo_sitemap( &$conf , &$req , &$site ){
		$this->conf = &$conf;
		$this->req = &$req;
		$this->site = &$site;
	}

	#----------------------------------------------------------------------------
	#	サイトマップを表示する
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
		<title>Pickles Framework : Sitemap</title>
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
		<h1>Sitemap</h1>
<?php }else{
			#	コマンドラインへの応答
			print '---- Pickles Framework ----'."\n";
			print '-- Sitemap'."\n";
		}

		$RTN = '';

		$Definition = $this->site->get_sitemap_definition();
		$pagelist = $this->site->sitemap;
		if( !is_null( $_SERVER['REMOTE_ADDR'] ) ){
			#--------------------------------------
			#	WEB ACCESS
			$RTN .= '<p class="ttr">'.count($pagelist).' page(s)</p>'."\n";
			$RTN .= '<table class="deftable">'."\n";
			$RTN .= '	<thead>'."\n";
			$RTN .= '		<tr>'."\n";
			foreach( $Definition as $id=>$row ){
				$RTN .= '			<th>'.htmlspecialchars( $id ).'</th>'."\n";
			}
			$RTN .= '			<th></th>'."\n";
			$RTN .= '		</tr>'."\n";
			$RTN .= '	</thead>'."\n";
			$RTN .= '	<tfoot>'."\n";
			$RTN .= '		<tr>'."\n";
			foreach( $Definition as $id=>$row ){
				$RTN .= '			<th>'.htmlspecialchars( $id ).'</th>'."\n";
			}
			$RTN .= '			<th></th>'."\n";
			$RTN .= '		</tr>'."\n";
			$RTN .= '	</tfoot>'."\n";
			$RTN .= '	<tbody>'."\n";
			foreach( $pagelist as $pageinfo ){
				$RTN .= '	<tr>'."\n";
				foreach( $Definition as $id=>$row ){
					if( $id == 'id' ){
						$RTN .= '		<th><a href="'.htmlspecialchars( $this->conf->url_action ).'?PICKLESINFO=pageinfo&amp;'.htmlspecialchars($this->req->pkey()).'='.htmlspecialchars($pageinfo[$id]).'">';
						if( strlen( $pageinfo[$id] ) ){
							$RTN .= '<code>'.htmlspecialchars( $pageinfo[$id] ).'</code>';
						}else{
							$RTN .= '(toppage)';
						}
						$RTN .= '</a></th>'."\n";
					}elseif( $row['rules']['type'] == 'path' ){
						$RTN .= '		<td>'.htmlspecialchars( preg_replace( '/\/'.preg_quote($pageinfo['id']).'$/s' , '' , $pageinfo[$id] ) ).'</td>'."\n";
					}else{
						$RTN .= '		<td>'.htmlspecialchars( $pageinfo[$id] ).'</td>'."\n";
					}
				}
				$RTN .= '		<td><a href="'.htmlspecialchars( $this->conf->url_action ).'?PICKLESINFO=pageinfo&amp;'.htmlspecialchars($this->req->pkey()).'='.htmlspecialchars($pageinfo['id']).'">pageinfo</a></td>'."\n";
				$RTN .= '	</tr>'."\n";
			}
			$RTN .= '	</tbody>'."\n";
			$RTN .= '</table>'."\n";
		}else{
			#--------------------------------------
			#	COMMAND LINE
			$RTN .= ''."\n";
			$RTN .= ''.count($pagelist).' page(s)'."\n";
			$RTN .= ''."\n";
			foreach( $pagelist as $pageinfo ){
				$RTN .= '[-- '.$pageinfo['id'].' --]'."\n";
				foreach( $Definition as $id=>$row ){
					if( $row['rules']['type'] == 'path' ){
						$RTN .= '    '.$id.' = '.preg_replace( '/\/'.preg_quote($pageinfo['id']).'$/s' , '' , $pageinfo[$id] ).''."\n";
					}else{
						$RTN .= '    '.$id.' = '.$pageinfo[$id].''."\n";
					}
				}
			}
			$RTN .= ''."\n";
		}

		print	$RTN;
		if( !is_null( $_SERVER['REMOTE_ADDR'] ) ){
			#	ウェブアクセスへの応答
?>	</body>
</html>
<?php }
		return	true;
	}

}

?>