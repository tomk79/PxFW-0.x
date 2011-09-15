<?php

#	Copyright (C)Tomoya Koyanagi.
#	Last Update : 0:35 2011/07/03

#******************************************************************************************************************
#	ページの情報を出力
#		Pickles Framework 0.6.7 で追加
class base_resources_picklesinfo_pageinfo{

	var $conf;
	var $req;
	var $dbh;
	var $site;

	#----------------------------------------------------------------------------
	#	コンストラクタ
	function base_resources_picklesinfo_pageinfo( &$conf , &$req , &$dbh , &$site ){
		$this->conf = &$conf;
		$this->req = &$req;
		$this->dbh = &$dbh;
		$this->site = &$site;
	}

	#----------------------------------------------------------------------------
	#	ページの情報を表示する
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
		<title>Pickles Framework : pageinfo : [<?php print htmlspecialchars( $this->req->po() ); ?>]</title>
		<style type="text/css">
			#logo		{ font-weight:bold; font-size:24px; border-bottom:1px solid #999999; font-family:"Helvetica","Arial"; }
			.unit		{}
			.main		{ float:left;  width:70%; }
			.sidebarR	{ float:right; width:27%; }
			h1			{ font-weight:bold; font-size:18px; background-color:#f3f3f3; padding:5px; border-left:3px solid #666666; font-family:"Helvetica","Arial"; }
			.error		{ color:#ff0000; }
			dt{
				border-left:3px solid #999999;
				padding-left:10px;
				font-weight:bold;
			}
			blockquote.sourcecode{
				background-color:#eeeeee;
				width:auto;
				overflow:auto;
				max-height:300px;
				margin-left:10px;
				margin-right:0px;
				padding:12px;
			}
			code{color:#0000dd;}
			table.deftable			{ empty-cells:show; margin:0px 0px 24px 0px; border-collapse:collapse; width:100%; table-layout: fixed; }
			table.deftable caption	{}
			table.deftable tr		{}
			table.deftable tr th	{ font-size:13px; empty-cells:show; border:1px solid #cccccc; font-weight:normal; background-color:#eeeeee; color:#333333; padding:3px 3px 3px 3px; text-align:left; width:20%; }
			table.deftable tr td	{ font-size:13px; empty-cells:show; border:1px solid #cccccc; font-weight:normal; background-color:#ffffff; color:#333333; padding:3px 3px 3px 3px; text-align:left; width:80%; }
			table.deftable thead th	{ font-size:13px; background-color:#bbbbbb; }
			table.deftable thead td	{ font-size:13px; background-color:#bbbbbb; }
			table.deftable tfoot th	{ font-size:13px; background-color:#bbbbbb; }
			table.deftable tfoot td	{ font-size:13px; background-color:#bbbbbb; }
		</style>
	</head>
	<body>
		<div id="logo">Pickles Framework</div>
		<h1>pageinfo : [<?php print htmlspecialchars( $this->req->po() ); ?>]</h1>
<?php }else{
			#	コマンドラインへの応答
			print '---- Pickles Framework ----'."\n";
			print '-- pageinfo of ['.htmlspecialchars( $this->req->po() ).']'."\n";
		}

		$RTN = '';

		$Definition = $this->site->get_sitemap_definition();
		if(!is_array($Definition)){ $Definition = array(); }
		$parent = $this->site->get_parent();
		$childlist = $this->site->get_children(null,array('all'=>true));
		$broslist = $this->site->get_bros(null,array('all'=>true));
		if( !is_null( $_SERVER['REMOTE_ADDR'] ) ){
			#--------------------------------------
			#	WEB ACCESS
			$RTN .= '<div class="unit">'."\n";
			$RTN .= '<div class="main">'."\n";
			$RTN .= '<h2>pageinfo</h2>'."\n";
			$RTN .= '<p class="ttr"><a href="'.htmlspecialchars( $this->conf->url_action ).'?'.htmlspecialchars($this->req->pkey()).'='.htmlspecialchars($this->req->po()).'">access this page</a></p>'."\n";
			$RTN .= '<table class="deftable">'."\n";
			$RTN .= '	<thead>'."\n";
			$RTN .= '		<tr>'."\n";
			$RTN .= '			<th>&nbsp;</th>'."\n";
			$RTN .= '			<th>name</th>'."\n";
			$RTN .= '			<th>value</th>'."\n";
			$RTN .= '		</tr>'."\n";
			$RTN .= '	</thead>'."\n";
			$RTN .= '	<tfoot>'."\n";
			$RTN .= '		<tr>'."\n";
			$RTN .= '			<th>&nbsp;</th>'."\n";
			$RTN .= '			<th>name</th>'."\n";
			$RTN .= '			<th>value</th>'."\n";
			$RTN .= '		</tr>'."\n";
			$RTN .= '	</tfoot>'."\n";
			$RTN .= '	<tbody>'."\n";
			foreach( $Definition as $id=>$row ){
				$pid = $this->site->getpageinfo( $this->req->po() , 'id' );
				$RTN .= '	<tr>'."\n";
				$RTN .= '		<th>'.htmlspecialchars( $id ).'</th>'."\n";
				$RTN .= '		<td>'.htmlspecialchars( $row['label'] ).'</td>'."\n";
				$RTN .= '		<td>'."\n";
				if( $row['rules']['type'] == 'path' ){
					$RTN .= '			'.htmlspecialchars( preg_replace( '/\/'.preg_quote($pid).'$/s' , '' , $this->site->sitemap[$pid][$id] ) ).''."\n";
				}elseif( is_null( $this->site->sitemap[$pid][$id] ) ){
					$RTN .= '			<code>null</code>'."\n";
				}elseif( is_bool( $this->site->sitemap[$pid][$id] ) ){
					if( $this->site->sitemap[$pid][$id] ){
						$RTN .= '			bool(<code>true</code>)'."\n";
					}else{
						$RTN .= '			bool(<code>false</code>)'."\n";
					}
				}elseif( is_int( $this->site->sitemap[$pid][$id] ) ){
					$RTN .= '			int(<code>'.text::text2html( $this->site->sitemap[$pid][$id] ).'</code>)'."\n";
				}else{
					$RTN .= '			'.text::text2html( $this->site->sitemap[$pid][$id] ).''."\n";
				}
				$RTN .= '		</td>'."\n";
				$RTN .= '	</tr>'."\n";
			}
			$RTN .= '	</tbody>'."\n";
			$RTN .= '</table>'."\n";

			$RTN .= '<h2>Contents</h2>'."\n";
			$RTN .= '<h3>main</h3>'."\n";

			$srcpath = $this->site->sitemap[$pid]['srcpath'].'/p_'.$this->req->poelm().'.php';
			if( @is_file( $this->conf->path_contents_dir.$this->site->sitemap[$pid]['srcpath'] ) ){
				#--------------------------------------
				#	コンテンツのパスが指示されている場合
				$srcpath = $this->site->sitemap[$pid]['srcpath'];
			}elseif( @is_dir( $this->conf->path_contents_dir.$this->site->sitemap[$pid]['srcpath'] ) ){
				#--------------------------------------
				#	コンテンツのパスをページIDから自動判別する場合
				$tmp_itemlist = $this->dbh->getfilelist( $this->conf->path_contents_dir.$this->site->sitemap[$pid]['srcpath'] );
				if(!is_array($tmp_itemlist)){$tmp_itemlist=array();}
				foreach( $tmp_itemlist as $tmp_basename ){
					if( !is_file( $this->conf->path_contents_dir.$this->site->sitemap[$pid]['srcpath'].'/'.$tmp_basename ) ){ continue; }
					if( preg_match( '/^p_'.preg_quote( $this->req->poelm() , '/' ).'\.[a-zA-Z0-9\_\-]+$/s' , $tmp_basename ) ){
						$srcpath = $this->site->sitemap[$pid]['srcpath'].'/'.$tmp_basename;
						break;
					}
				}
				unset( $tmp_itemlist );
				unset( $tmp_basename );
			}
			$RTN .= '<dl>'."\n";
			$srcpathinfo = $this->dbh->pathinfo( $srcpath );
			if( $srcpathinfo['dirname'] == '/' || $srcpathinfo['dirname'] == '\\' ){ $srcpathinfo['dirname'] = ''; }
			$RTN .= '	<dt class="ttr">local path</dt>'."\n";
			$RTN .= '		<dd class="ttr"><code>'.htmlspecialchars( $srcpath ).'</code></dd>'."\n";
			$RTN .= '	<dt class="ttr">real path</dt>'."\n";
			if( file_exists( $this->conf->path_contents_dir.$srcpath ) ){
				$RTN .= '		<dd class="ttr"><code>'.htmlspecialchars( realpath( $this->conf->path_contents_dir.$srcpath ) ).'</code></dd>'."\n";
			}else{
				$RTN .= '		<dd class="ttr"><code>'.htmlspecialchars( $this->dbh->get_realpath( $this->conf->path_contents_dir.$srcpath ) ).'</code></dd>'."\n";
			}
			$RTN .= '	<dt class="ttr">workdir local path</dt>'."\n";
			$RTN .= '		<dd class="ttr"><code>'.htmlspecialchars( $srcpathinfo['dirname'].'/'.$srcpathinfo['filename'].'.items' ).'</code></dd>'."\n";
			$RTN .= '	<dt class="ttr">workdir real path</dt>'."\n";
			if( file_exists( $this->conf->path_contents_dir.$srcpathinfo['dirname'].'/'.$srcpathinfo['filename'].'.items' ) ){
				$RTN .= '		<dd class="ttr"><code>'.htmlspecialchars( realpath( $this->conf->path_contents_dir.$srcpathinfo['dirname'].'/'.$srcpathinfo['filename'].'.items' ) ).'</code></dd>'."\n";
			}else{
				$RTN .= '		<dd class="ttr"><code>'.htmlspecialchars( $this->dbh->get_realpath( $this->conf->path_contents_dir.$srcpathinfo['dirname'].'/'.$srcpathinfo['filename'].'.items' ) ).'</code></dd>'."\n";
			}
			$RTN .= '	<dt class="ttr">exetype</dt>'."\n";
			if( strlen( $this->site->sitemap[$pid]['exetype'] ) ){
				$RTN .= '		<dd class="ttr">'.htmlspecialchars( $this->site->sitemap[$pid]['exetype'] ).'</dd>'."\n";
			}else{
				$RTN .= '		<dd class="ttr">'.htmlspecialchars( $this->dbh->get_extension( $srcpathinfo['basename'] ) ).'</dd>'."\n";
			}
			if( is_file( $this->conf->path_contents_dir.$srcpath ) ){
				$RTN .= '	<dt class="ttr">filesize</dt>'."\n";
				$RTN .= '		<dd class="ttr">'.filesize( $this->conf->path_contents_dir.$srcpath ).' byte(s)</dd>'."\n";
				$RTN .= '	<dt class="ttr">file source</dt>'."\n";
				if( is_readable( $this->conf->path_contents_dir.$srcpath ) ){
					$RTN .= '		<dd><blockquote class="sourcecode"><pre class="ttr">'.text::text2html( $this->dbh->file_get_contents( $this->conf->path_contents_dir.$srcpath ) ).'</pre></blockquote></dd>'."\n";
				}else{
					$RTN .= '		<dd class="ttr error">file is NOT readable.</dd>'."\n";
				}
			}else{
				$RTN .= '	<dt class="ttr">file source</dt>'."\n";
				$RTN .= '		<dd class="ttr error">Content file NOT exists.</dd>'."\n";
			}
			$RTN .= '	<dt class="ttr">local resources</dt>'."\n";
			$RTN .= '		<dd class="ttr">'.$this->mk_localresource_list( $this->conf->path_contents_dir.$srcpathinfo['dirname'].'/'.$srcpathinfo['filename'].'.items' ).'</dd>'."\n";
			$RTN .= '</dl>'."\n";
			$tmp_itemlist = $this->dbh->getfilelist( $this->conf->path_contents_dir.$srcpathinfo['dirname'] );
			$ary_contentfiles = array();
			if(!is_array($tmp_itemlist)){$tmp_itemlist=array();}
			foreach( $tmp_itemlist as $tmp_basename ){
				if( !is_file( $this->conf->path_contents_dir.$srcpathinfo['dirname'].'/'.$tmp_basename ) ){ continue; }
				if( $srcpathinfo['basename'] == $tmp_basename ){ continue; }
				if( preg_match( '/^'.preg_quote($srcpathinfo['filename'],'/').'\@([A-Z0-9]+)\.[a-zA-Z0-9\_\-]+$/s' , $tmp_basename , $tmp_matched ) ){
					array_push( $ary_contentfiles , array( 'basename'=>$tmp_basename , 'CT'=>$tmp_matched[1] ) );
				}
			}
			unset( $tmp_itemlist );
			unset( $tmp_basename );
			if(!is_array($ary_contentfiles)){$ary_contentfiles=array();}
			foreach( $ary_contentfiles as $row ){
				$RTN .= '<h3>@'.htmlspecialchars( $row['CT'] ).'</h3>'."\n";
				$RTN .= '<dl>'."\n";
				$srcpathinfo2 = $this->dbh->pathinfo( $srcpathinfo['dirname'] );
				if( $srcpathinfo2['dirname'] == '/' || $srcpathinfo2['dirname'] == '\\' ){ $srcpathinfo2['dirname'] = ''; }
				$RTN .= '	<dt class="ttr">local path</dt>'."\n";
				$RTN .= '		<dd class="ttr"><code>'.htmlspecialchars( $srcpathinfo['dirname'].'/'.$row['basename'] ).'</code></dd>'."\n";
				$RTN .= '	<dt class="ttr">real path</dt>'."\n";
				if( file_exists( $this->conf->path_contents_dir.$srcpathinfo['dirname'].'/'.$row['basename'] ) ){
					$RTN .= '		<dd class="ttr"><code>'.htmlspecialchars( realpath( $this->conf->path_contents_dir.$srcpathinfo['dirname'].'/'.$row['basename'] ) ).'</code></dd>'."\n";
				}else{
					$RTN .= '		<dd class="ttr"><code>'.htmlspecialchars( $this->conf->path_contents_dir.$srcpathinfo['dirname'].'/'.$row['basename'] ).'</code></dd>'."\n";
				}
				$RTN .= '	<dt class="ttr">filesize</dt>'."\n";
				$RTN .= '		<dd class="ttr">'.filesize( $this->conf->path_contents_dir.$srcpathinfo['dirname'].'/'.$row['basename'] ).' byte(s)</dd>'."\n";
				$RTN .= '	<dt class="ttr">file source</dt>'."\n";
				if( is_readable( $this->conf->path_contents_dir.$srcpathinfo['dirname'].'/'.$row['basename'] ) ){
					$RTN .= '		<dd><blockquote class="sourcecode"><pre class="ttr">'.text::text2html( $this->dbh->file_get_contents( $this->conf->path_contents_dir.$srcpathinfo['dirname'].'/'.$row['basename'] ) ).'</pre></blockquote></dd>'."\n";
				}else{
					$RTN .= '		<dd class="ttr error">file is NOT readable.</dd>'."\n";
				}
				$RTN .= '</dl>'."\n";
			}

			$theme_list = $this->dbh->getfilelist( $this->conf->path_theme_collection_dir );
			if( !is_array( $theme_list ) ){ $theme_list = array(); }
			foreach( $theme_list as $theme_id ){
				$ct_list = $this->dbh->getfilelist( $this->conf->path_theme_collection_dir.$theme_id );
				if( !is_array( $ct_list ) ){ $ct_list = array(); }
				foreach( $ct_list as $ct ){
					if( is_dir( $this->conf->path_theme_collection_dir.$theme_id.'/'.$ct.'/contents'.$srcpathinfo['dirname'] ) ){
						$content_filelist = $this->dbh->getfilelist( $this->conf->path_theme_collection_dir.$theme_id.'/'.$ct.'/contents'.$srcpathinfo['dirname'] );
						if(!is_array($content_filelist)){$content_filelist=array();}
						foreach( $content_filelist as $basename ){
							if( !preg_match( '/^'.preg_quote($srcpathinfo['filename'],'/').'\.[a-zA-Z0-9]+$/s' , $basename ) ){
								continue;
							}
							$RTN .= '<h3>Theme <a href="'.htmlspecialchars( $this->conf->url_action ).'?PICKLESINFO=themeinfo&amp;THEME='.htmlspecialchars($theme_id).'&amp;CT='.htmlspecialchars($ct).'">['.htmlspecialchars( $theme_id ).'] for ['.htmlspecialchars( $ct ).']</a></h3>'."\n";
							$RTN .= '<dl>'."\n";
							$RTN .= '	<dt class="ttr">realpath</dt>'."\n";
							$RTN .= '		<dd class="ttr">'.htmlspecialchars( realpath( $this->conf->path_theme_collection_dir.$theme_id.'/'.$ct.'/contents'.$srcpathinfo['dirname'].'/'.$basename ) ).'</dd>'."\n";
							$RTN .= '	<dt class="ttr">filesize</dt>'."\n";
							$RTN .= '		<dd class="ttr">'.filesize( $this->conf->path_theme_collection_dir.$theme_id.'/'.$ct.'/contents'.$srcpathinfo['dirname'].'/'.$basename ).' byte(s)</dd>'."\n";
							$RTN .= '	<dt class="ttr">file source</dt>'."\n";
							if( is_readable( $this->conf->path_theme_collection_dir.$theme_id.'/'.$ct.'/contents'.$srcpathinfo['dirname'].'/'.$basename ) ){
								$RTN .= '		<dd><blockquote class="sourcecode"><pre class="ttr">'.text::text2html( $this->dbh->file_get_contents( $this->conf->path_theme_collection_dir.$theme_id.'/'.$ct.'/contents'.$srcpathinfo['dirname'].'/'.$basename ) ).'</pre></blockquote></dd>'."\n";
							}else{
								$RTN .= '		<dd class="ttr error">file is NOT readable.</dd>'."\n";
							}
							$RTN .= '</dl>'."\n";
							break;
						}
					}
				}
			}

			$RTN .= '</div>'."\n";
			$RTN .= '<div class="sidebarR">'."\n";
			$RTN .= '<h2>parent</h2>'."\n";
			if( !is_null( $parent ) ){
				if( $parent === '' ){
					$RTN .= '<p class="ttr"><a href="'.htmlspecialchars( $this->conf->url_action ).'?PICKLESINFO=pageinfo&amp;'.htmlspecialchars($this->req->pkey()).'='.htmlspecialchars($parent).'">(toppage)</a></p>'."\n";
				}else{
					$RTN .= '<p class="ttr"><code><a href="'.htmlspecialchars( $this->conf->url_action ).'?PICKLESINFO=pageinfo&amp;'.htmlspecialchars($this->req->pkey()).'='.htmlspecialchars($parent).'">'.htmlspecialchars( $parent ).'</a></code></p>'."\n";
				}
			}else{
				$RTN .= '<p class="ttr">no parent.</p>'."\n";
			}
			$RTN .= '<h2>bros list</h2>'."\n";
			if( count( $broslist ) ){
				$RTN .= '<ul>'."\n";
				if(!is_array($broslist)){$broslist=array();}
				foreach( $broslist as $row ){
					$LABEL = '<code>'.$row.'</code>';
					if( $row === '' ){
						$LABEL = '(toppage)';
					}
					$STYLE = '';
					if( $row === $this->req->po() ){
						$STYLE = ' style="font-weight:bold;"';
					}
					$RTN .= '<li class="ttr"><a href="'.htmlspecialchars( $this->conf->url_action ).'?PICKLESINFO=pageinfo&amp;'.htmlspecialchars($this->req->pkey()).'='.htmlspecialchars($row).'"'.$STYLE.'>'.$LABEL.'</a></li>'."\n";
				}
				$RTN .= '</ul>'."\n";
			}else{
				$RTN .= '<p class="ttr">no bros.</p>'."\n";
			}
			$RTN .= '<h2>child list</h2>'."\n";
			if( count( $childlist ) ){
				$RTN .= '<ul>'."\n";
				if(!is_array($childlist)){$childlist=array();}
				foreach( $childlist as $row ){
					if( $row === '' ){
						$RTN .= '<li class="ttr"><a href="'.htmlspecialchars( $this->conf->url_action ).'?PICKLESINFO=pageinfo&amp;'.htmlspecialchars($this->req->pkey()).'='.htmlspecialchars($row).'">(toppage)</a></li>'."\n";
					}else{
						$RTN .= '<li class="ttr"><a href="'.htmlspecialchars( $this->conf->url_action ).'?PICKLESINFO=pageinfo&amp;'.htmlspecialchars($this->req->pkey()).'='.htmlspecialchars($row).'"><code>'.$row.'</code></a></li>'."\n";
					}
				}
				$RTN .= '</ul>'."\n";
			}else{
				$RTN .= '<p class="ttr">no child.</p>'."\n";
			}
			$RTN .= '</div>'."\n";
			$RTN .= '<div style="clear:both;"></div>'."\n";
			$RTN .= '</div>'."\n";
			$RTN .= '<hr />'."\n";
			$RTN .= '<p class="ttr"><a href="'.htmlspecialchars( $this->conf->url_action ).'?PICKLESINFO=sitemap">Show sitemap</a>.</p>'."\n";
		}else{
			#--------------------------------------
			#	COMMAND LINE
			$RTN .= ''."\n";
			if(!is_array($Definition)){$Definition=array();}
			foreach( $Definition as $id=>$row ){
				$RTN .= '[-- '.$id.' ('.$row['label'].') --]'."\n";
				$pid = $this->site->getpageinfo( $this->req->po() , 'id' );
				if( $row['rules']['type'] == 'path' ){
					$RTN .= '    '.preg_replace( '/\/'.preg_quote($this->req->po()).'$/s' , '' , $this->site->sitemap[$pid][$id] )."\n";
				}else{
					$RTN .= '    '.$this->site->sitemap[$pid][$id]."\n";
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



	#--------------------------------------
	#	ローカルリソースの一覧を作成する
	function mk_localresource_list( $path_workdir , $localpath = null ){
		if( !is_dir( $path_workdir ) ){
			return 'Directory NOT exists.';
		}
		$itemlist = $this->dbh->getfilelist( $path_workdir.$localpath );
		if( !count( $itemlist ) ){
			if( !strlen( $localpath ) ){
				return 'No Item.';
			}else{
				return '';
			}
		}
		$RTN = '';
		$RTN .= '<ul>'."\n";
		if(!is_array($itemlist)){$itemlist=array();}
		foreach( $itemlist as $basename ){
			if( is_dir( $path_workdir.$localpath.'/'.$basename ) ){
				if( $localpath.'/'.$basename == '/resources' ){
					$RTN .= '<li class="ttr" style="list-style-type:square;"><span style="font-weight:bold; text-decoration:underline; color:#666600;">'.htmlspecialchars( $basename ).'</span>'.$this->mk_localresource_list( $path_workdir , $localpath.'/'.$basename ).'</li>'."\n";
				}else{
					$RTN .= '<li class="ttr" style="list-style-type:square;"><span style="font-weight:bold;">'.htmlspecialchars( $basename ).$this->mk_localresource_list( $path_workdir , $localpath.'/'.$basename ).'</span></li>'."\n";
				}
			}else{
				$RTN .= '<li class="ttr" style="list-style-type:circle;"><span style="font-weight:normal;">'.htmlspecialchars( $basename ).'</span></li>'."\n";
			}
		}
		$RTN .= '</ul>'."\n";
		return $RTN;
	}


}

?>