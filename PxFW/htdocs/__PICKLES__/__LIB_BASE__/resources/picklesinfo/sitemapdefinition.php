<?php

#	Copyright (C)Tomoya Koyanagi.
#	Last Update : 4:39 2011/01/30

#******************************************************************************************************************
#	サイトマップ定義を出力
#		Pickles Framework 0.5.10 で追加
class base_resources_picklesinfo_sitemapdefinition{

	var $conf;

	#----------------------------------------------------------------------------
	#	コンストラクタ
	function base_resources_picklesinfo_sitemapdefinition( &$conf ){
		$this->conf = &$conf;
	}

	#----------------------------------------------------------------------------
	#	sitemapdefinition を実行する
	function execute( &$site ){
		#	Pickles Framework 0.2.1 で追加

		if( strlen( $this->conf->php_mb_internal_encoding ) ){
			#	内部エンコード調整
			mb_internal_encoding( $this->conf->php_mb_internal_encoding );
		}

#$_SERVER['REMOTE_ADDR'] = null;
#header('Content-type: text/plain');

		if( !is_null( $_SERVER['REMOTE_ADDR'] ) ){
			#	ウェブアクセスへの応答
			header( 'Content-type: text/html; charset='.mb_internal_encoding() );
			print	'<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">'."\n";
			?><html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ja">
	<head>
		<meta http-equiv="content-type" content="text/html;charset=<?php print htmlspecialchars( mb_internal_encoding() ); ?>" />
		<title>Pickles Framework : Sitemap Definition mode</title>
		<style type="text/css">
			#logo	{font-weight:bold; font-size:24px; border-bottom:1px solid #999999; font-family:"Helvetica","Arial"; }
			h1		{font-weight:bold; font-size:18px; background-color:#f3f3f3; padding:5px; border-left:3px solid #666666; font-family:"Helvetica","Arial"; }
			.error	{color:#ff0000; }
			table.deftable			{empty-cells:show; margin:0px 0px 24px 0px; border-collapse:collapse; width:100%; table-layout: fixed; }
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
		<h1>Sitemap Definition</h1>
<?php }else{
			#	コマンドラインへの応答
			print '---- Pickles Framework ----'."\n";
			print '-- Sitemap Definition'."\n";
		}

		$RTN = '';

		$Definition = $site->get_sitemap_definition();
		if( !is_null( $_SERVER['REMOTE_ADDR'] ) ){
			#--------------------------------------
			#	WEB ACCESS
			$RTN .= '<table class="deftable">'."\n";
			$RTN .= '	<thead>'."\n";
			$RTN .= '		<tr>'."\n";
			$RTN .= '			<th style="width:5%;">&nbsp;</th>'."\n";
			$RTN .= '			<th style="width:10%;">ID</th>'."\n";
			$RTN .= '			<th style="width:30%;">Label</th>'."\n";
			$RTN .= '			<th style="width:20%;">type</th>'."\n";
			$RTN .= '			<th style="width:25%;">preg</th>'."\n";
			$RTN .= '			<th style="width:5%;">notnull</th>'."\n";
			$RTN .= '			<th style="width:5%;">lang</th>'."\n";
			$RTN .= '		</tr>'."\n";
			$RTN .= '	</thead>'."\n";
			$RTN .= '	<tfoot>'."\n";
			$RTN .= '		<tr>'."\n";
			$RTN .= '			<th style="width:5%;">&nbsp;</th>'."\n";
			$RTN .= '			<th style="width:10%;">ID</th>'."\n";
			$RTN .= '			<th style="width:30%;">Label</th>'."\n";
			$RTN .= '			<th style="width:20%;">type</th>'."\n";
			$RTN .= '			<th style="width:25%;">preg</th>'."\n";
			$RTN .= '			<th style="width:5%;">notnull</th>'."\n";
			$RTN .= '			<th style="width:5%;">lang</th>'."\n";
			$RTN .= '		</tr>'."\n";
			$RTN .= '	</tfoot>'."\n";
			$RTN .= '	<tbody>'."\n";
			$i = 'A';
			foreach( $Definition as $id=>$row ){
				$RTN .= '	<tr>'."\n";
				$RTN .= '		<th>'.$i++.'</th>'."\n";
				$RTN .= '		<th>'.htmlspecialchars( $id ).'</th>'."\n";
				$RTN .= '		<td>'.htmlspecialchars( $row['label'] ).'</td>'."\n";
				$RTN .= '		<td>'."\n";
				$RTN .= '			'.htmlspecialchars( $row['rules']['type'] ).''."\n";
				if( $row['rules']['type'] == 'select' ){
					$RTN .= '		<dl>'."\n";
					foreach( $row['rules']['cont'] as $key=>$val ){
						if( strlen( $key ) ){
							$RTN .= '		<dt>'.htmlspecialchars($key).'</dt>'."\n";
						}else{
							$RTN .= '		<dt><span style="font-style:italic;">(no index)</span></dt>'."\n";
						}
						$RTN .= '		<dd>'.htmlspecialchars($val).'</dd>'."\n";
					}
					$RTN .= '		</dl>'."\n";
				}
				$RTN .= '		</td>'."\n";
				$RTN .= '		<td>'.htmlspecialchars( $row['rules']['preg'] ).'</td>'."\n";
				$RTN .= '		<td>'."\n";
				if( $row['rules']['notnull'] ){
					$RTN .= '			<div>true</div>'."\n";
				}else{
					$RTN .= '			<div>---</div>'."\n";
				}
				$RTN .= '		</td>'."\n";
				$RTN .= '		<td>'."\n";
				if( $row['rules']['lang'] ){
					$RTN .= '			<div>true</div>'."\n";
				}else{
					$RTN .= '			<div>---</div>'."\n";
				}
				$RTN .= '		</td>'."\n";
				$RTN .= '	</tr>'."\n";
			}
			$RTN .= '	</tbody>'."\n";
			$RTN .= '</table>'."\n";
		}else{
			#--------------------------------------
			#	COMMAND LINE
			$RTN .= ''."\n";
			$i = 'A';
			foreach( $Definition as $id=>$row ){
				$RTN .= '[-- '.$i++.' : '.$id.' --]'."\n";
				$RTN .= '    '.$row['label'].'  ('.$row['rules']['type'].')'."\n";
				if( $row['rules']['lang'] ){
					$RTN .= '       lang: true'."\n";
				}else{
					$RTN .= '       lang: ---'."\n";
				}
				$RTN .= ''."\n";
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