<?php

#	Copyright (C)Tomoya Koyanagi.
#	LastUpdate : 2:29 2011/05/29

require_once( $conf->path_lib_base.'/resources/htmlparser.php' );

#******************************************************************************************************************
#	Conductorが読み込んだスタティックHTMLのコンテンツの相対パスを処理する
class base_resources_contentsoperator_html extends base_resources_htmlparser{

	var $conf;
	var $req;
	var $dbh;
	var $user;
	var $site;
	var $errors;
	var $theme;
	var $custom;

	var $contents_config = array();
		#	コンテンツ書式設定 - Pickles Framework 0.1.8 追加

	var $debug_mode = false;
		#	デバッグ用
		#	※デバッグ終了後は必ずfalseに戻してください。

	var $execute_type = 'html';//PxFW 0.7.2 追加

	#----------------------------------------------------------------------------
	#	コンストラクタ
	function base_resources_contentsoperator_html( &$conf , &$req , &$dbh , &$user , &$site , &$errors , &$theme , &$custom , $execute_type = null ){
		$this->conf = &$conf;
		$this->req = &$req;
		$this->dbh = &$dbh;
		$this->user = &$user;
		$this->site = &$site;
		$this->errors = &$errors;
		$this->theme = &$theme;
		$this->custom = &$custom;

		if( strlen( $execute_type ) ){
			//PxFW 0.7.2 追加
			$this->execute_type = $execute_type;
		}

		if( $this->conf->debug_mode ){
			#	PicklesFrameworkのデバッグモードが有効なら、
			#	このオブジェクト内のデバッグモードも有効にする。
			$this->debug_mode = true;
		}
	}

	#########################################################################################################################################################
	#	HTMLの書き出し
	function publish( $parsed_html = null , $option = null ){
		if( !is_array( $parsed_html ) ){ $parsed_html = $this->parsed_html; }
		if( !count( $parsed_html ) ){ return	$this->original_string; }
		if( !is_array( $parsed_html ) ){ return	false; }

		$RTN = '';

		foreach( $parsed_html as $Line ){

			$enable_singleclose = preg_match( $this->pattern_allow_selfclose , $Line['tag'] );

			if( strlen( $Line['str_prev'] ) ){
				if( preg_match( '/\?'.'>$/si' ,  $RTN ) && preg_match( '/^(?:\r\n|\r|\n)/si' , $Line['str_prev'] ) ){
					#	Picles Framework 0.5.1 追加
					$RTN .= '<'.'?php print "\n"; ?'.'>';
				}
				$RTN .= $Line['str_prev'];
			}

			if( strlen( $Line['commentout'] ) ){
				#	コメント行はそのまま書き出しておしまい。
				if( $this->get_conf('print_comment') ){
					$RTN .= '<'.'!'.'--'.$Line['commentout'].'--'.'>';
				}
				if( strlen( $Line['str_next'] ) ){
					if( preg_match( '/\?'.'>$/si' ,  $RTN ) && preg_match( '/^(?:\r\n|\r|\n)/si' , $Line['str_next'] ) ){
						#	Picles Framework 0.5.1 追加
						$RTN .= '<'.'?php print "\n"; ?'.'>';
					}
					$RTN .= $Line['str_next'];
				}
				continue;
			}
			if( strlen( $Line['cdata'] ) ){
				#	CDATA行はそのまま書き出しておしまい。(PxFW 0.6.5 追加)
				$RTN .= '<![CDATA['.$Line['cdata'].']]>';
				if( strlen( $Line['str_next'] ) ){ $RTN .= $Line['str_next']; }
				continue;
			}
			if( strlen( $Line['php_script'] ) ){
				#	PHPスクリプトはそのまま書き出しておしまい。
				$RTN .= '<'.'?php '."\n".$Line['php_script']."\n".' ?'.'>';
				if( strlen( $Line['str_next'] ) ){
					if( preg_match( '/\?'.'>$/si' ,  $RTN ) && preg_match( '/^(?:\r\n|\r|\n)/si' , $Line['str_next'] ) ){
						#	Picles Framework 0.5.1 追加
						$RTN .= '<'.'?php print "\n"; ?'.'>';
					}
					$RTN .= $Line['str_next'];
				}
				continue;
			}
			if( !strlen( $Line['tag'] ) ){
				#	タグ情報が空ならスキップ
				if( strlen( $Line['str_next'] ) ){
					if( preg_match( '/\?'.'>$/si' ,  $RTN ) && preg_match( '/^(?:\r\n|\r|\n)/si' , $Line['str_next'] ) ){
						#	Picles Framework 0.5.1 追加
						$RTN .= '<'.'?php print "\n"; ?'.'>';
					}
					$RTN .= $Line['str_next'];
				}
				continue;
			}

			$Line['tag'] = preg_replace('/^px\:/si','',$Line['tag']);
				//【取り急ぎ簡易実装】PxFW 0.6.12
				//PxFWのカスタムタグはすべて<px:***>にしていくようにする方針であるが、
				//しばらくの間、px: は付けても付けなくても動作するようにしておく。
				//23:04 2011/01/05

			if( method_exists( $this , 'tag_'.strtolower( $Line['tag'] ) ) ){
				#--------------------------------------
				#	カスタムタグの実装があれば、そちらに任せる
				$taginfo = $Line;
				unset( $taginfo['str_prev'] );
				unset( $taginfo['str_next'] );
				$RTN .= eval( 'return	$this->tag_'.strtolower( $Line['tag'] ).'( $taginfo , $option );' );
				if( strlen($Line['str_next']) ){
					if( preg_match( '/\?'.'>$/si' ,  $RTN ) && preg_match( '/^(?:\r\n|\r|\n)/si' , $Line['str_next'] ) ){
						#	Picles Framework 0.5.1 追加
						$RTN .= '<'.'?php print "\n"; ?'.'>';
					}
					$RTN .= $Line['str_next'];
				}
				continue;
				#	/ カスタムタグの実装があれば、そちらに任せる
				#--------------------------------------
			}

			$RTN .= '<'.$Line['tag'].'';

			#--------------------------------------
			#	属性部分
			$RTN .= $this->publish_attribute( $Line['tag'] , $Line['attribute'] , $Line['attribute_str'] , $option );
			#	/属性部分
			#--------------------------------------

			if( !strlen($Line['content_str']) && !count($Line['content']) && $enable_singleclose && !preg_match( '/^!/' , $Line['tag'] ) ){
				$RTN .= ' /';
			}
			$RTN .= '>';

			if( !strlen($Line['content_str']) && !count($Line['content']) && $enable_singleclose ){
				#	タグにはさまれた部分がない場合
				if( strlen($Line['str_next']) ){
					if( preg_match( '/\?'.'>$/si' ,  $RTN ) && preg_match( '/^(?:\r\n|\r|\n)/si' , $Line['str_next'] ) ){
						#	Picles Framework 0.5.1 追加
						$RTN .= '<'.'?php print "\n"; ?'.'>';
					}
					$RTN .= $Line['str_next'];
				}
				continue;
			}

			#--------------------------------------
			#	タグにはさまれた部分
			if( is_array( $Line['content'] ) && count( $Line['content'] ) ){
				$RTN .= $this->publish( $Line['content'] );
			}elseif( strlen( $Line['content_str'] ) ){
				$RTN .= $Line['content_str'];
			}
			#	/タグにはさまれた部分
			#--------------------------------------

			$RTN .= '</'.$Line['tag'].'>';

			if( strlen($Line['str_next']) ){
				if( preg_match( '/\?'.'>$/si' ,  $RTN ) && preg_match( '/^(?:\r\n|\r|\n)/si' , $Line['str_next'] ) ){
					#	Picles Framework 0.5.1 追加
					$RTN .= '<'.'?php print "\n"; ?'.'>';
				}
				$RTN .= $Line['str_next'];
			}
		}
		return	$RTN;
	}

	#########################################################################################################################################################
	#	コンテンツを解析して、結果できたソースを返す
	function get_contents_result( $content_file_path , $option = array() ){
		if( !$this->dbh->is_file( $content_file_path ) ){
			return	'Contents File ['.$content_file_path.'] is not exists.';
		}
		$content_file_path = @realpath($content_file_path);

		#--------------------------------------
		#	スタティックなHTMLを解析し、リンクや画像ソースなどのパスを整形する

		$path_cache = null;

		if( strlen( $option['cache_file_path'] ) ){
			#	オプション項目 cache_file_path が指定されていたら、
			#	指定値を最優先。
			#	このオプションは、PicklesFrameworkの通常フロー以外からの利用を想定した動作です。
			#	例えば、コンテンツが、自分自身の範疇において、
			#	独自にコンテンツ管理領域とコンテンツファイルを管理したい場合などに役立ちます。
			#	この機能を使用するには、必ず一度ディスクに保存する必要があります。
			#	キャッシュをディスクに保存せず、結果のソースだけを取り出す方法は提供されません。
			$path_cache = $option['cache_file_path'];
			if( $this->dbh->is_dir( $path_cache ) ){
				$errorMessage = '指定されたパスは、ディレクトリです。['.$path_cache.']';
				$this->errors->error_log( $errorMessage );
				return	'<p class="ttr error">'.htmlspecialchars( $errorMessage ).'</p>';
			}
			if( $this->dbh->is_file( $path_cache ) && !$this->dbh->is_writable( $path_cache ) ){
				$errorMessage = '指定されたパスには、書き込み権限がありません。['.$path_cache.']';
				$this->errors->error_log( $errorMessage );
				return	'<p class="ttr error">'.htmlspecialchars( $errorMessage ).'</p>';
			}
			if( !$this->dbh->is_file( $path_cache ) && $this->dbh->is_dir( dirname( $path_cache ) ) && !$this->dbh->is_writable( dirname( $path_cache ) ) ){
				$errorMessage = '指定されたパスは存在しませんが、上位階層に書き込み権限がないため、続行できません。['.$path_cache.']';
				$this->errors->error_log( $errorMessage );
				return	'<p class="ttr error">'.htmlspecialchars( $errorMessage ).'</p>';
			}
			if( !$this->dbh->is_file( $path_cache ) && !$this->dbh->is_dir( dirname( $path_cache ) ) ){
				$errorMessage = '指定されたパスも、上位階層も存在しません。キャッシュ保存先ディレクトリは、事前に生成されている必要があります。['.$path_cache.']';
				$this->errors->error_log( $errorMessage );
				return	'<p class="ttr error">'.htmlspecialchars( $errorMessage ).'</p>';
			}
		}else{
			if( $this->dbh->is_dir( $this->conf->path_cache_dir ) ){
				#	コンテンツキャッシュパスの設定が有効だった場合
				if( !@is_writable( $this->conf->path_cache_dir ) ){
					$errorMessage = 'コンテンツキャッシュディレクトリに書き込み権限が与えられていません。';
					$this->errors->error_log( $errorMessage );
					return	'<p class="ttr error">'.htmlspecialchars( $errorMessage ).'</p>';
				}
				$this->dbh->mkdir( $this->conf->path_cache_dir.'/contents' );

				if( $this->dbh->is_dir( $this->conf->path_contents_dir ) && preg_match( '/^'.preg_quote( @realpath( $this->conf->path_contents_dir ) , '/' ).'(.*)$/s' , $content_file_path , $preg_result ) ){
					$content_file_localpath = $preg_result[1];
				}elseif( $this->dbh->is_dir( $this->conf->path_theme_collection_dir ) && preg_match( '/^'.preg_quote( @realpath( $this->conf->path_theme_collection_dir ) , '/' ).'(.*)$/s' , $content_file_path , $preg_result ) ){
					$content_file_localpath = 'theme:'.$preg_result[1];
				}else{
					$content_file_localpath = $content_file_path;
				}
				unset( $preg_result );

				$path_cache = $this->conf->path_cache_dir.'/contents/'.urlencode( str_replace( '\\' , '/' , $content_file_localpath ) ).'.'.urlencode( $this->execute_type ).'.php';
					//	↑PxFW 0.7.2 パスに $this->execute_type を含むように修正。サイトマップからオペレータを切り替えた場合に、重複してしまう不具合に対応。

			}
			if( !$this->dbh->is_dir( dirname( $path_cache ) ) ){
				#	キャッシュディレクトリが存在しない場合、
				#	コンテンツのitemsディレクトリ内にキャッシュ領域を作成
				$cont_info = $this->theme->get_selectcontent_info();//コンテンツの情報を得る
				$path_cache = dirname( $content_file_path ).'/'.$cont_info['FileName'].'.items/cache/contents.cache.php';
			}
		}

		if( !$this->dbh->is_dir( dirname( $path_cache ) ) ){
			$errorMessage = 'コンテンツキャッシュディレクトリが存在せず、作成を試みましたが、失敗しました。';
			$this->errors->error_log( $errorMessage );
			return	'<p class="ttr error">'.htmlspecialchars( $errorMessage ).'</p>';
		}

		#--------------------------------------
		#	コンテンツを実行して、ソースを返す。
		return	$this->execute_contents( $content_file_path , $path_cache );
	}


	#--------------------------------------
	#	コンテンツを実行して、ソースを返す。
	function execute_contents( $content_file_path , $path_cache ){
		#--------------------------------------
		#	キャッシュの有無、有効期限を評価
		if( $this->debug_mode || !$this->dbh->is_file( $path_cache ) || $this->dbh->comp_filemtime( $content_file_path , $path_cache ) ){
			#	キャッシュが存在しないか、最終更新日がキャッシュよりコンテンツの方が新しい場合には、
			#	キャッシュファイルを無効とみなし、再度パース。
			#	HTMLをパースしてキャッシュを作成→保存
			$ORIGINAL_HTML_SRC = isolated::getrequiredreturn_static( $content_file_path , &$this->conf , &$this->req , &$this->dbh , &$this->user , &$this->site , &$this->errors , &$this->theme , &$this->custom , $this->conf->contents_start_str , $this->conf->contents_end_str , $this->conf->enable_contents_preprocessor );
			if( !$this->dbh->is_dir( dirname( $path_cache ) ) ){
				$this->dbh->mkdirall( dirname( $path_cache ) );
			}
			$parsed_html = $this->html_parse( $ORIGINAL_HTML_SRC );
			unset( $ORIGINAL_HTML_SRC );//もう使わないので開放

			$CACHE_SRC = $this->publish( $parsed_html );
			unset( $parsed_html );//もう使わないので開放

			#	Pickles Framework 0.1.8 → ページャー機能対応
			$RTN = '';
			$RTN .= $CACHE_SRC;
			unset( $CACHE_SRC );//もう使わないので開放
			$RTN .= '<'.'?php'."\n";
			$RTN .= '	if( !is_array( $SRC_PAGE_LIST ) ){ $SRC_PAGE_LIST = array(); }'."\n";
			$RTN .= '	array_push( $SRC_PAGE_LIST , @ob_get_clean() );'."\n";
			$RTN .= '	ob_start();'."\n";
			$RTN .= '?'.'>';
			$RTN .= '<'.'?php'."\n";
			$RTN .= '	#コンテンツ出力'."\n";
			$RTN .= '	$pager_disallowed_ct = array('."\n";
			$RTN .= '		#	この配列は、ページャー機能を無効にする'."\n";
			$RTN .= '		#	クライアントタイプの一覧です。'."\n";
			foreach( $this->contents_config as $key=>$val ){
				if( !preg_match( '/^pager\.disallowed_ct\.([a-zA-Z0-9]+)$/is' , $key , $preg_result ) ){
					continue;
				}
				$RTN .= '		'.text::data2text( trim( strtoupper($preg_result[1]) ) ).'=>'.text::data2text( trim( $val ) ).' ,'."\n";
			}

			$RTN .= '	);'."\n";
			$RTN .= '	if( !$pager_disallowed_ct[$user->get_ct()] && count($SRC_PAGE_LIST) > 1 ){'."\n";
			$RTN .= '		#	複数ページに別れていたら'."\n";
			$RTN .= '		$pnum = intval( $req->pvelm() );'."\n";
			$RTN .= '		if( $pnum < 1 ){'."\n";
			$RTN .= '			$pnum = 1 ;'."\n";
			$RTN .= '		}'."\n";
			$RTN .= '		$pager_info = $dbh->get_pager_info( count($SRC_PAGE_LIST) , $pnum , 1 );'."\n";
			$RTN .= ''."\n";
			$RTN .= '		for( $i = 1; $i <= count( $SRC_PAGE_LIST ); $i ++ ){'."\n";//PxFW 0.7.1 : ページに linkto が指定されている場合に動作しない不具合を修正。
			$RTN .= '			$site->setpageinfoall('."\n";
			$RTN .= '				\':\'.$i ,'."\n";
			$RTN .= '				array('."\n";
			$RTN .= '					\'title\'=>$site->getpageinfo( \':\' , \'title\' ),'."\n";
			$RTN .= '					\'title_page\'=>$site->getpageinfo( \':\' , \'title_page\' ),'."\n";
			$RTN .= '					\'title_link\'=>$site->getpageinfo( \':\' , \'title_link\' ),'."\n";
			$RTN .= '					\'title_breadcrumb\'=>$site->getpageinfo( \':\' , \'title_breadcrumb\' ),'."\n";
			$RTN .= '					\'linkto\'=>$theme->act(\'/\'.preg_replace( '.text::data2text('/\./si').' , '.text::data2text('/').' , $req->po() ).\'/\'.$i.\'.html\'),'."\n";
			$RTN .= '				)'."\n";
			$RTN .= '			);'."\n";
			$RTN .= '		}'."\n";
			$RTN .= ''."\n";
			$RTN .= '		$PREV = \'\';'."\n";
			$RTN .= '		$PREV .= \'<div class="ttr AlignC">\'."\\n";'."\n";
			$RTN .= '		if( strlen( $pager_info[\'prev\'] ) ){'."\n";
			$RTN .= '			$PREV .= \'	[\'.$theme->mk_link( \':\'.($pager_info[\'prev\']!=1?$pager_info[\'prev\']:\'\') , array( \'label\'=>\'前へ\' ) ).\']\'."\\n";'."\n";
			$RTN .= '		}'."\n";
			$RTN .= '		$PREV .= \'</div>\'."\\n";'."\n";
			$RTN .= ''."\n";
			$RTN .= '		$NEXT = \'\';'."\n";
			$RTN .= '		$NEXT .= \'<div class="ttr AlignC">\'."\\n";'."\n";
			$RTN .= '		if( strlen( $pager_info[\'next\'] ) ){'."\n";
			$RTN .= '			$NEXT .= \'	[\'.$theme->mk_link( \':\'.($pager_info[\'next\']!=1?$pager_info[\'next\']:\'\') , array( \'label\'=>\'次へ\' ) ).\']\'."\\n";'."\n";
			$RTN .= '		}'."\n";
			$RTN .= '		$NEXT .= \'</div>\'."\\n";'."\n";
			$RTN .= ''."\n";
			$RTN .= ''."\n";
			$RTN .= '		$PAGER = \'\';'."\n";
			$RTN .= '		$PAGER .= \'<ul>\'."\\n";'."\n";
			$RTN .= '		for( $i = 1; $i <= count($SRC_PAGE_LIST); $i++ ){'."\n";
			$RTN .= '			$tmp_i = $i;'."\n";
			$RTN .= '			if( $tmp_i == 1 ){ $tmp_i = \'\'; }'."\n";
			$RTN .= '			if( $pnum != $i ){'."\n";
			$RTN .= '				$PAGER .= \'	<li class="ttr">\'.$theme->mk_link( \':\'.$tmp_i , array( \'label\'=>\'Page \'.$i ) ).\'</li>\'."\\n";'."\n";
			$RTN .= '			}else{'."\n";
			$RTN .= '				$PAGER .= \'	<li class="ttr"><strong>Page \'.$i.\'</strong></li>\'."\\n";'."\n";
			$RTN .= '			}'."\n";
			$RTN .= '		}'."\n";
			$RTN .= '		$PAGER .= \'</ul>\'."\\n";'."\n";
			$RTN .= ''."\n";
			$RTN .= '		if( strlen( $pager_info[\'prev\'] ) ){'."\n";
			$RTN .= '			print $PREV;'."\n";
			$RTN .= '		}'."\n";
			$RTN .= '		print $SRC_PAGE_LIST[$pnum-1];'."\n";
			$RTN .= '		if( strlen( $pager_info[\'next\'] ) ){'."\n";
			$RTN .= '			print $NEXT;'."\n";
			$RTN .= '		}'."\n";
			$RTN .= '		print $PAGER;'."\n";
			$RTN .= ''."\n";
			$RTN .= '	}else{'."\n";
			$RTN .= '		#	1ページだったら'."\n";
			$RTN .= '		print implode( "\n" , $SRC_PAGE_LIST );'."\n";
			$RTN .= ''."\n";
			$RTN .= '	}'."\n";
			$RTN .= '?'.'>';

			#--------------------------------------
			#	コンテンツキャッシュを保存する
			ignore_user_abort(true);//←PxFramework 0.6.11
			$this->dbh->file_overwrite( $path_cache , $RTN );
			ignore_user_abort(false);//←PxFramework 0.6.11

			#	使い終わった変数を開放
			unset( $RTN );
			unset( $parser );
		}

		ignore_user_abort(true);//←PxFramework 0.6.11
		$this->theme->cache_all_resources();//ローカルリソースを全てキャッシュ(Pickles Framework 0.3.3)
			#	↑Pickles Framework 0.5.4 : ひとつのコンテンツを使いまわした場合にリソースの一部がキャッシュされない不具合が見つかったため、if文の外に出した。
		ignore_user_abort(false);//←PxFramework 0.6.11

		$RTN = isolated::getrequiredreturn_once( $path_cache , &$this->conf , &$this->req , &$this->dbh , &$this->user , &$this->site , &$this->errors , &$this->theme , &$this->custom , $this->conf->contents_start_str , $this->conf->contents_end_str );
		return	$RTN;
	}

	#--------------------------------------
	#	属性を書き出す
	function publish_attribute( $tag , $dat , $dat_str , $option = null ){
		if( $tag == '!DOCTYPE' ){
			return	preg_replace( '/[ ]*\/$/' , '' , $dat_str );
		}
		$written = array();
		$RTN = '';

		#--------------------------------------
		#	タグ名毎の属性の順序などを整理
		#	※省略した場合は、元のソースにかかれた通りの順序で復元します。
		if( !is_null( $dat['background'] ) ){
			$RTN .= ' background="<'.'?php print htmlspecialchars( $this->theme->resource( '.$this->data2text_apply_vars( text::html2text( $dat['background'] ) ).' ) ); ?'.'>"';
			$written['background'] = true;
		}
		if( !is_null( $dat['style'] ) ){
			$RTN .= '<'.'?php ob_start(); ?'.'>'.$this->replace_url_in_css( text::html2text( $dat['style'] ) ).'<'.'?php print \' style="\'.htmlspecialchars( ob_get_clean() ).\'"\'; ?'.'>';
			$written['style'] = true;
		}
		#	/タグ名毎の属性の順序などを整理
		#--------------------------------------

		if( count( $dat ) ){
			$keys = array_keys( $dat );
			foreach( $keys as $Line_att ){
				if( $written[$Line_att] ){ continue; }
				$RTN .= ' '.$Line_att;
				if( !is_null( $dat[$Line_att] ) ){
					$RTN .= '="<'.'?php print '.$this->data2text_apply_vars( $dat[$Line_att] , true ).'; ?'.'>"';
				}
			}
		}
		return	$RTN;
	}





	#--------------------------------------------------------------------------------------------------------------------------------------------------------
	#	カスタムタグの実装

	#--------------------------------------
	#	<config />
	function tag_config( $taginfo , $option ){
		#	Pickles Framework 0.1.8 → 追加

		$type = strtolower( trim( $taginfo['attribute']['type'] ) );
		switch( $type ){
			case 'contents':
			case 'content':
			default:
				$type = 'content';
				break;
		}

		if( $type == 'content' ){
			#	コンテンツに対する設定
			$key = strtolower( trim( $taginfo['attribute']['name'] ) );
			if( !strlen( $key ) ){
				return	'';
			}

			$taginfo['attribute']['value'] = trim( $taginfo['attribute']['value'] );
			if( $key == 'image.clickable' || preg_match( '/^pager\.disallowed_ct\.([a-zA-Z0-9]+)$/is' , $key ) ){
				#	bool 型の設定項目 (Pickles Framework 0.5.4 追加)
				if( !$taginfo['attribute']['value'] || strtolower( $taginfo['attribute']['value'] ) == 'false' ){
					$taginfo['attribute']['value'] = false;
				}else{
					$taginfo['attribute']['value'] = true;
				}
			}

			$this->contents_config[$key] = $taginfo['attribute']['value'];
		}
		return	'';

	}

	#--------------------------------------
	#	<changingpage />
	function tag_changingpage( $taginfo , $option ){
		#	Pickles Framework 0.1.8 → 追加

		$allow_ct = '';
			#	改ページするか否かの条件式（CASE文）

		if( strlen( $taginfo['attribute']['ct'] ) ){
			$ct_list = explode( '|' , $taginfo['attribute']['ct'] );
			foreach( $ct_list as $ct_name ){
				$ct_name = strtoupper( trim( $ct_name ) );
				$allow_ct .= 'case '.text::data2text($ct_name).': ';
			}
		}

		$PAGERSRC = '';
		$PAGERSRC .= '<'.'?php'."\n";
		$PAGERSRC .= '	# changing page'."\n";
		$PAGERSRC .= '	if( !is_array( $SRC_PAGE_LIST ) ){ $SRC_PAGE_LIST = array(); }'."\n";
		$PAGERSRC .= '	array_push( $SRC_PAGE_LIST , @ob_get_clean() );'."\n";
		$PAGERSRC .= '	ob_start();'."\n";
		$PAGERSRC .= '?'.'>';

		if( strlen( $allow_ct ) ){
			$RTN .= '<'.'?php'."\n";
			$RTN .= 'switch( $user->get_ct() ){'."\n";
			$RTN .= '	'.$allow_ct."\n";
			$RTN .= '	?'.'>'.$PAGERSRC.'<'.'?php'."\n";
			$RTN .= '	break;'."\n";
			$RTN .= 'default:'."\n";
			$RTN .= '	break;'."\n";
			$RTN .= '}'."\n";
			$RTN .= ' ?'.'>';

#			$RTN .= '<div style="page-break-before:always;"></div>'."\n";//印刷時の改ページ
		}else{
			$RTN .= $PAGERSRC;
		}
		return	$RTN;

	}

	#--------------------------------------
	#	<autoindex />
	function tag_autoindex( $taginfo , $option ){
		#	Pickles Framework 0.6.12 追加
		$RTN = '';
		$RTN .= '<'.'?php'."\n";
		$RTN .= '	# autoindex'."\n";
		$RTN .= '	print $theme->mk_autoindex();'."\n";
		$RTN .= '?'.'>';
		return	$RTN;
	}

	#--------------------------------------
	#	<include />
	function tag_include( $taginfo , $option ){
		#	Pickles Framework 0.7.2 追加
		$type = $taginfo['attribute']['type'];
		if( !strlen($type) ){
			$type = 'resource';
		}
		$src  = $taginfo['attribute']['src'];
		$RTN = '';
		$RTN .= '<'.'?php'."\n";
		$RTN .= '	# include'."\n";
		if( $type == 'page' ){
			$RTN .= '	print $theme->include_page( '.text::data2text( $src ).' );'."\n";
		}elseif( $type == 'resource' ){
			$RTN .= '	print $theme->include_resource( '.text::data2text( $src ).' );'."\n";
		}
		$RTN .= '?'.'>';
		return	$RTN;
	}

	#--------------------------------------
	#	<childlist />
	function tag_childlist( $taginfo , $option ){
		#	Pickles Framework 0.3.9 追加

		$RTN = '';
		$RTN .= '<'.'?php'."\n";
		$RTN .= '	# childlist'."\n";
		$RTN .= '	print $theme->mk_childlist(array(';
		if( is_array( $taginfo['attribute'] ) && count( $taginfo['attribute'] ) ){
			foreach( $taginfo['attribute'] as $attKey=>$attVal ){
				$RTN .= text::data2text( $attKey ).'=>'.$this->data2text_apply_vars( $attVal ).',';
			}
		}
		$RTN .= '));'."\n";
		$RTN .= '?'.'>';
		return	$RTN;

	}

	#--------------------------------------
	#	<broslist />
	function tag_broslist( $taginfo , $option ){
		#	Pickles Framework 0.5.0 追加

		$RTN = '';
		$RTN .= '<'.'?php'."\n";
		$RTN .= '	# broslist'."\n";
		$RTN .= '	print $theme->mk_broslist(array(';
		if( is_array( $taginfo['attribute'] ) && count( $taginfo['attribute'] ) ){
			foreach( $taginfo['attribute'] as $attKey=>$attVal ){
				$RTN .= text::data2text( $attKey ).'=>'.$this->data2text_apply_vars( $attVal ).',';
			}
		}
		$RTN .= '));'."\n";
		$RTN .= '?'.'>';
		return	$RTN;

	}

	#--------------------------------------
	#	<back2top />
	function tag_back2top( $taginfo , $option ){
		#	Pickles Framework 0.4.4 追加

		$RTN = '';
		$RTN .= '<'.'?php'."\n";
		$RTN .= '	# back2top'."\n";
		$RTN .= '	print $theme->mk_back2top();'."\n";
		$RTN .= '?'.'>';
		return	$RTN;

	}

	#--------------------------------------
	#	<script />
	function tag_script( $taginfo , $option ){
		if( strtolower( $taginfo['attribute']['language'] ) == 'php' ){
			#	PHPタグだったら特殊処理
			$RTN = '';
			$RTN .= '<'.'?php'."\n";
			$RTN .= $taginfo['content_str']."\n";
			$RTN .= '?'.'>';
			return	$RTN;
		}

		$RTN = '';
		$RTN .= '<script';
		if( strlen( $taginfo['attribute']['type'] ) ){
			$RTN .= ' type="<'.'?php print '.$this->data2text_apply_vars( $taginfo['attribute']['type'] , true ).'; ?'.'>"';
			unset( $taginfo['attribute']['type'] );
		}
		if( strlen( $taginfo['attribute']['language'] ) ){
			$RTN .= ' language="<'.'?php print '.$this->data2text_apply_vars( $taginfo['attribute']['language'] , true ).'; ?'.'>"';
			unset( $taginfo['attribute']['language'] );
		}
		if( strlen( $taginfo['attribute']['src'] ) ){
			$RTN .= ' src="<'.'?php print htmlspecialchars( $theme->resource( '.$this->data2text_apply_vars( text::htmlspecialchars_decode( $taginfo['attribute']['src'] ) ).' ) ); ?'.'>"';//←PxFW 0.6.6 : 変数を埋め込めるようになった。
			unset( $taginfo['attribute']['src'] );
		}
		if( is_array( $taginfo['attribute'] ) ){
			foreach( $taginfo['attribute'] as $key=>$val ){
				$RTN .= ' '.( $key ).'="<'.'?php print '.$this->data2text_apply_vars( $val , true ).'; ?'.'>"';
			}
		}
		$RTN .= '>';
		if( strlen( $taginfo['content_str'] ) ){
			$RTN .= $taginfo['content_str'];
		}
		$RTN .= '</script>';
		return	$RTN;
	}

	#--------------------------------------
	#	<style />
	function tag_style( $taginfo , $option ){
		$RTN = '';
		$RTN .= '<'.'?php ob_start(); ?'.'>'."\n";
		$RTN .= '<style';
		if( strlen( $taginfo['attribute']['type'] ) ){
			$RTN .= ' type="<'.'?php print '.$this->data2text_apply_vars( $taginfo['attribute']['type'] , true ).'; ?'.'>"';
			unset( $taginfo['attribute']['type'] );
		}
		if( strlen( $taginfo['attribute']['language'] ) ){
			$RTN .= ' language="<'.'?php print '.$this->data2text_apply_vars( $taginfo['attribute']['language'] , true ).'; ?'.'>"';
			unset( $taginfo['attribute']['language'] );
		}
		if( strlen( $taginfo['attribute']['src'] ) ){
			$RTN .= ' src="<'.'?php print htmlspecialchars( $theme->resource( '.$this->data2text_apply_vars( $taginfo['attribute']['src'] ).' ) ); ?'.'>"';
			unset( $taginfo['attribute']['src'] );
		}
		if( strlen( $taginfo['attribute']['media'] ) ){
			$RTN .= ' media="<'.'?php print '.$this->data2text_apply_vars( $taginfo['attribute']['media'] , true ).'; ?'.'>"';
			unset( $taginfo['attribute']['media'] );
		}
		if( is_array( $taginfo['attribute'] ) ){
			foreach( $taginfo['attribute'] as $key=>$val ){
				$RTN .= ' '.( $key ).'="<'.'?php print '.$this->data2text_apply_vars( $val , true ).'; ?'.'>"';
			}
		}
		$RTN .= '>'."\n";
		$RTN .= $this->replace_url_in_css( $taginfo['content_str'] )."\n";
		$RTN .= '</style>'."\n";
		$RTN .= '<'.'?php $theme->setsrc( $theme->getsrc(\'additional_header\').@ob_get_clean() , \'additional_header\' ); ?'.'>';
		return	$RTN;
	}

	#--------------------------------------
	#	<link />
	function tag_link( $taginfo , $option ){
		$RTN = '';
		$RTN .= '<'.'?php ob_start(); ?'.'>'."\n";
		$RTN .= '<link';
		$attList = array(
			'rel',
			'href',
			'media',
			'type',
			'language',
			'title',
		);
		foreach( $attList as $attName ){
			if( strlen( $taginfo['attribute'][$attName] ) ){
				if( $attName == 'href' ){
					if( strtolower( $taginfo['attribute']['rel'] ) == 'stylesheet' ){
						#	スタイルシートの場合、href属性はリソースをさすため、resource()からパスを変換。
						$RTN .= ' '.($attName).'="<'.'?php print htmlspecialchars( $theme->resource( '.$this->data2text_apply_vars( text::htmlspecialchars_decode( $taginfo['attribute'][$attName] ) ).' ) ); ?'.'>"';//←PxFW 0.6.6 : 変数を埋め込めるようになった。
					}else{
						#	でなければ、ウェブページ扱いなので、href()を使う。
						$RTN .= ' '.($attName).'="<'.'?php print htmlspecialchars( $theme->href( '.$this->data2text_apply_vars( text::htmlspecialchars_decode( $taginfo['attribute'][$attName] ) ).' ) ); ?'.'>"';//←PxFW 0.6.6 : 変数を埋め込めるようになった。
					}
				}else{
					$RTN .= ' '.($attName).'="<'.'?php print '.$this->data2text_apply_vars( $taginfo['attribute'][$attName] , true ).'; ?'.'>"';
				}
			}
		}
		$RTN .= ' />'."\n";
		$RTN .= '<'.'?php $theme->setsrc( $theme->getsrc(\'additional_header\').@ob_get_clean() , \'additional_header\' ); ?'.'>';
		return	$RTN;
	}

	#--------------------------------------
	#	<a />
	function tag_a( $taginfo , $option ){

		#--------------------------------------
		#	タグにはさまれた部分
		$tag_content = '';
		$mk_tag_content_src = '';
		$is_content_as_valiable = false;
		if( is_array( $taginfo['content'] ) && count( $taginfo['content'] ) ){
			$tag_content = $this->publish( $taginfo['content'] );
			$is_content_as_valiable = true;
			$mk_tag_content_src .= ' ob_start(); ';
			$mk_tag_content_src .= '?'.'>'.$tag_content.'<'.'?php ';
			$mk_tag_content_src .= '$TAG_A_LABEL = ob_get_clean(); ';
		}elseif( strlen( $taginfo['content_str'] ) ){
			$is_content_as_valiable = true;
			$tag_content = $taginfo['content_str'];
			$mk_tag_content_src .= ' ob_start(); ';
			$mk_tag_content_src .= '?'.'>'.$taginfo['content_str'].'<'.'?php ';;
			$mk_tag_content_src .= '$TAG_A_LABEL = ob_get_clean(); ';
		}
		#	/タグにはさまれた部分
		#--------------------------------------

		if( is_null( $taginfo['attribute']['href'] ) && ( strlen( $taginfo['attribute']['name'] ) || strlen( $taginfo['attribute']['id'] ) ) ){
			#	アンカーだったらこの処理
			#	アンカーは、リンクを生成するものではないため、
			#	$theme->mk_link()を通さない。
			#	Pickles Framework 0.5.2 : id属性を捨てないように修正。
			#	Pickles Framework 0.5.10 : innerHTML を捨てないように修正。
			$RTN = '';
			$RTN .= '<a';
			if( !is_null( $taginfo['attribute']['name'] ) ){
				$RTN .= ' name="<'.'?php print '.$this->data2text_apply_vars( $taginfo['attribute']['name'] , true ).'; ?'.'>"';
			}
			if( !is_null( $taginfo['attribute']['id'] ) ){
				$RTN .= ' id="<'.'?php print '.$this->data2text_apply_vars( $taginfo['attribute']['id'] , true ).'; ?'.'>"';
			}
			$RTN .= '>';
			if( $is_content_as_valiable ){
				$RTN .= '<'.'?php ';
				$RTN .= $mk_tag_content_src;
				$RTN .= ' print $TAG_A_LABEL; ';
				$RTN .= '?'.'>';
			}
			if( $is_content_as_valiable ){
				$RTN .= '<'.'?php ';
				$RTN .= ' unset( $TAG_A_LABEL );';
				$RTN .= '?'.'>';
			}
			$RTN .= '</a>';
			return	$RTN;
		}

		$allow_html = true;
		if( strlen( $taginfo['attribute']['allow_html'] ) ){
			if( strtolower( $taginfo['attribute']['allow_html'] ) == 'false' || strtolower( $taginfo['attribute']['allow_html'] ) == '0' ){
				$allow_html = false;
			}elseif( strtolower( $taginfo['attribute']['allow_html'] ) == 'true' || strtolower( $taginfo['attribute']['allow_html'] ) == '1' ){
				$allow_html = true;
			}
		}

		$mk_link_att = $taginfo['attribute'];
		$mk_link_att['cssclass'] = $taginfo['attribute']['class'];
		unset( $mk_link_att['class'] );
		$mk_link_att['cssstyle'] = $taginfo['attribute']['style'];
		unset( $mk_link_att['style'] );
		$mk_link_att['style'] = $taginfo['attribute']['tstyle'];
		unset( $mk_link_att['tstyle'] );

		$mk_link_att['label'] = $taginfo['content_str'];
		$mk_link_att['allow_html'] = $allow_html;

		foreach( $mk_link_att as $key=>$val ){
			if( is_null( $mk_link_att[$key] ) ){
				unset( $mk_link_att[$key] );
			}
		}

		$mk_link_option = array();
		$is_cssstyles = false;
		foreach( $mk_link_att as $fin_key=>$fin_val ){
			if( $is_content_as_valiable && $fin_key == 'label' ){
				array_push( $mk_link_option , text::data2text($fin_key).'=>$TAG_A_LABEL' );
			}elseif( $fin_key == 'cssstyle' ){
				#	Pickles Framework 0.5.10 : スタイルシート内の url() を解析するように修正。
				$is_cssstyles = true;
				array_push( $mk_link_option , text::data2text($fin_key).'=>$TAG_A_CSSSTYLE' );
			}elseif( strpos( $fin_key , 'on' ) === 0 ){
				#	属性名が on から始まっていた場合。
				#	Pickles Framework 0.5.10 : JSの htmlspecialchars() は $theme->mk_link() が担当するので、戻してから渡す。
				array_push( $mk_link_option , text::data2text($fin_key).'=>'.$this->data2text_apply_vars(text::html2text($fin_val)) );
			}else{
				array_push( $mk_link_option , text::data2text($fin_key).'=>'.$this->data2text_apply_vars($fin_val) );
			}
		}
		$mk_link_option = implode( ' , ' , $mk_link_option );

		$RTN = '';
		$RTN .= '<'.'?php ';
		if( $is_cssstyles ){
			$RTN .= 'ob_start(); ?'.'>'.$this->replace_url_in_css( text::html2text( $taginfo['attribute']['style'] ) ).'<'.'?php $TAG_A_CSSSTYLE = ob_get_clean();';
		}
		if( $is_content_as_valiable ){
			$RTN .= $mk_tag_content_src;
		}
		$RTN .= 'print $theme->mk_link( ';
		$RTN .= $this->data2text_apply_vars( text::htmlspecialchars_decode( $taginfo['attribute']['href'] ) );//←PxFW 0.6.6 : 変数を埋め込めるようになった。
		$RTN .= ' , array( ';
		$RTN .= $mk_link_option;
		$RTN .= ' ) );';
		if( $is_content_as_valiable ){
			$RTN .= ' unset( $TAG_A_LABEL );';
		}
		if( $is_cssstyles ){
			$RTN .= ' unset( $TAG_A_CSSSTYLE ); ';
		}
		$RTN .= ' ?'.'>';
		return	$RTN;
	}

	#--------------------------------------
	#	<area />
	function tag_area( $taginfo , $option ){
		#	Pickles Framework 0.5.10 : 追加

		$RTN = '';
		$RTN .= '<'.'?php'."\n";
		$RTN .= '$TAG_AREA_HREF = $theme->href( '.$this->data2text_apply_vars( text::htmlspecialchars_decode( $taginfo['attribute']['href'] ) ).' );'."\n";//←PxFW 0.6.6 : 変数を埋め込めるようになった。

		$RTN .= 'if( strlen( '.text::data2text( $taginfo['attribute']['target'] ).' ) ){'."\n";
		$RTN .= '    $TAG_AREA_TARGET = '.$this->data2text_apply_vars( text::html2text($taginfo['attribute']['target']) ).';'."\n";
		$RTN .= '}elseif( preg_match( \'/^(?:http|https):\\/\\/\'.preg_quote($conf->url_domain,\'/\').preg_quote($conf->url_action,\'/\').\'/si\' , $TAG_AREA_HREF ) ){'."\n";
		$RTN .= '    $TAG_AREA_TARGET = \'_self\';'."\n";
		$RTN .= '}elseif( preg_match( \'/^(?:http|https|ftp):\\/\\//si\' , $TAG_AREA_HREF ) ){'."\n";
		$RTN .= '    $TAG_AREA_TARGET = \'_blank\';'."\n";
		$RTN .= '}else{'."\n";
		$RTN .= '    $TAG_AREA_TARGET = \'_self\';'."\n";
		$RTN .= '}'."\n";

		$RTN .= '$TAG_AREA_ONCLICK = ( '.$this->data2text_apply_vars( text::html2text($taginfo['attribute']['onclick']) ).' );'."\n";
		$RTN .= 'if( $TAG_AREA_TARGET == \'_self\' ){'."\n";
		$RTN .= '}elseif( $TAG_AREA_TARGET == \'_top\' ){'."\n";
		$RTN .= '    $TAG_AREA_ONCLICK .= \'window.top.location.href = this.href;return false;\';'."\n";
		$RTN .= '}elseif( $TAG_AREA_TARGET == \'_blank\' ){'."\n";
		$RTN .= '    $TAG_AREA_ONCLICK .= \'window.open(this.href);return false;\';'."\n";
		$RTN .= '}else{'."\n";
		$RTN .= '    $TAG_AREA_ONCLICK .= \'window.open(this.href,\'.text::data2jstext( $TAG_AREA_TARGET ).\');return false;\';'."\n";
		$RTN .= '}'."\n";

		unset( $taginfo['attribute']['href'] );
		unset( $taginfo['attribute']['target'] );
		unset( $taginfo['attribute']['onclick'] );

		$RTN .= '?'.'>';
		$RTN .= '<area';
		$RTN .= ' href="<'.'?php print htmlspecialchars( $TAG_AREA_HREF ); ?'.'>"';
		$RTN .= '<'.'?php if( strlen( $TAG_AREA_ONCLICK ) ){ print \' onclick="\'.htmlspecialchars( $TAG_AREA_ONCLICK ).\'"\'; } ?'.'>';
		if( !is_array( $taginfo['attribute'] ) ){ $taginfo['attribute'] = array(); }
		foreach( $taginfo['attribute'] as $key=>$val ){
			$RTN .= ' '.$key.'="<'.'?php print '.$this->data2text_apply_vars( $val , true ).'; ?'.'>"';
		}
		$RTN .= '<'.'?php'."\n";
		$RTN .= ' unset( $TAG_AREA_HREF );'."\n";
		$RTN .= ' unset( $TAG_AREA_TARGET );'."\n";
		$RTN .= ' unset( $TAG_AREA_ONCLICK );'."\n";
		$RTN .= '?'.'>';
		$RTN .= ' />';
		return	$RTN;
	}



	#--------------------------------------
	#	<img />
	function tag_img( $taginfo , $option ){

		$mk_img_att = $taginfo['attribute'];
		if( !is_array($mk_img_att) ){
			$mk_img_att = array();
		}

		foreach( array_keys( $mk_img_att ) as $key ){
			if( is_null( $mk_img_att[$key] ) ){
				unset( $mk_img_att[$key] );
			}
		}

		if( $this->contents_config['image.clickable'] ){
			#	Pickles Framework 0.5.4 : 追加
			if( !strlen( $mk_img_att['hrefresource'] ) ){
				$mk_img_att['hrefresource'] = $taginfo['attribute']['src'];
			}
		}

		$RTN = '';
		$RTN .= '<'.'?php ';
		$RTN .= 'print $theme->mk_img( ';
		$RTN .= $this->data2text_apply_vars( text::htmlspecialchars_decode( $taginfo['attribute']['src'] ) );//←PxWF 0.6.6 : 変数を埋め込めるようになった。
		$RTN .= ' , array(';
		foreach( $mk_img_att as $attKey=>$attVal ){
			$RTN .= text::data2text($attKey).'=>'.$this->data2text_apply_vars( $attVal ).',';
		}
		$RTN .= ') );';
		$RTN .= ' ?'.'>';

		return	$RTN;
	}

	#--------------------------------------
	#	<embed />
	function tag_embed( $taginfo , $option ){
		#	Pickles Framework 0.2.8 追加

		$mk_link_att = $taginfo['attribute'];
		if( !is_array($mk_link_att) ){
			$mk_link_att = array();
		}

		foreach( array_keys( $mk_link_att ) as $key ){
			if( is_null( $mk_link_att[$key] ) ){
				unset( $mk_link_att[$key] );
			}
		}

		$RTN = '';
		$RTN .= '<'.'?php ';
		$RTN .= 'print \'<embed\';';
		foreach( $mk_link_att as $attkey=>$attvalue ){
			if( strtolower( $attkey ) == 'src' ){
				$RTN .= 'print \' \'.'.text::data2text($attkey).'.\'="\'.htmlspecialchars( $theme->resource( ';
				$RTN .= $this->data2text_apply_vars( $attvalue );
				$RTN .= ' ) ).\'"\'; ';
			}else{
				$RTN .= 'print \' \'.'.text::data2text($attkey).'.\'="\'.'.$this->data2text_apply_vars( $attvalue , true ).'.\'"\'; ';
			}
		}
		$RTN .= 'print \'></embed>\'';
		$RTN .= ' ?'.'>';

		return	$RTN;
	}

	#--------------------------------------
	#	<param />
	function tag_param( $taginfo , $option ){
		#	Pickles Framework 0.2.8 追加

		$mk_link_att = $taginfo['attribute'];
		if( !is_array($mk_link_att) ){
			$mk_link_att = array();
		}

		foreach( array_keys( $mk_link_att ) as $key ){
			if( is_null( $mk_link_att[$key] ) ){
				unset( $mk_link_att[$key] );
			}
		}

		$is_resources = false;
		if( strtolower( $taginfo['attribute']['name'] ) == 'movie' ){
			$is_resources = true;
		}

		$RTN = '';
		$RTN .= '<'.'?php ';
		$RTN .= 'print \'<param\';';
		foreach( $mk_link_att as $attkey=>$attvalue ){
			if( $is_resources && strtolower( $attkey ) == 'value' ){
				$RTN .= 'print \' \'.'.text::data2text($attkey).'.\'="\'.htmlspecialchars( $theme->resource( ';
				$RTN .= $this->data2text_apply_vars( $attvalue );
				$RTN .= ' ) ).\'"\'; ';
			}else{
				$RTN .= 'print \' \'.'.text::data2text($attkey).'.\'="\'.'.$this->data2text_apply_vars( $attvalue , true ).'.\'"\'; ';
			}
		}
		$RTN .= 'print \' />\'';
		$RTN .= ' ?'.'>';

		return	$RTN;
	}

	#--------------------------------------
	#	<spacer />
	function tag_spacer( $taginfo , $option ){

		$mk_link_att = $taginfo['attribute'];
		if( !is_array($mk_link_att) ){
			$mk_link_att = array();
		}

		foreach( array_keys( $mk_link_att ) as $key ){
			if( is_null( $mk_link_att[$key] ) ){
				unset( $mk_link_att[$key] );
			}
		}

		$RTN = '';
		$RTN .= '<'.'?php ';
		$RTN .= 'print $theme->spacer( ';
		$RTN .= text::data2text( $taginfo['attribute']['width'] );
		$RTN .= ' , ';
		$RTN .= text::data2text( $taginfo['attribute']['height'] );
		$RTN .= ' );';
		$RTN .= ' ?'.'>';

		return	$RTN;
	}

	#--------------------------------------
	#	<hx />
	function tag_h1( $taginfo , $option ){ return $this->tag_hx( $taginfo , $option , 1 ); }
	function tag_h2( $taginfo , $option ){ return $this->tag_hx( $taginfo , $option , 2 ); }
	function tag_h3( $taginfo , $option ){ return $this->tag_hx( $taginfo , $option , 3 ); }
	function tag_h4( $taginfo , $option ){ return $this->tag_hx( $taginfo , $option , 4 ); }
	function tag_h5( $taginfo , $option ){ return $this->tag_hx( $taginfo , $option , 5 ); }
	function tag_h6( $taginfo , $option ){ return $this->tag_hx( $taginfo , $option , 6 ); }
	function tag_hx( $taginfo , $option , $hx = null ){

		$allow_html = true;
		if( strlen( $taginfo['attribute']['allow_html'] ) ){
			if( strtolower( $taginfo['attribute']['allow_html'] ) == 'true' || strtolower( $taginfo['attribute']['allow_html'] ) == '1' ){
				$allow_html = true;
			}elseif( strtolower( $taginfo['attribute']['allow_html'] ) == 'false' || strtolower( $taginfo['attribute']['allow_html'] ) == '0' ){
				$allow_html = false;
			}
		}

		#--------------------------------------
		#	タグにはさまれた部分
		$tag_content = '';
		$mk_tag_content_src = '';
		$is_content_as_valiable = false;
		if( is_array( $taginfo['content'] ) && count( $taginfo['content'] ) ){
			$tag_content = $this->publish( $taginfo['content'] );
			$is_content_as_valiable = true;
			$mk_tag_content_src .= ' ob_start(); ';
			$mk_tag_content_src .= '?'.'>'.$tag_content.'<'.'?php ';
			$mk_tag_content_src .= '$TAG_HX_LABEL = ob_get_clean(); ';
		}elseif( strlen( $taginfo['content_str'] ) ){
			$tag_content = $taginfo['content_str'];
		}
		#	/タグにはさまれた部分
		#--------------------------------------


		$mk_hx_att = $taginfo['attribute'];
		$mk_hx_att['cssclass'] = $taginfo['attribute']['class'];
		unset( $mk_hx_att['class'] );
		$mk_hx_att['cssstyle'] = $taginfo['attribute']['style'];
		unset( $mk_hx_att['style'] );
		$mk_hx_att['style'] = $taginfo['attribute']['tstyle'];
		unset( $mk_hx_att['tstyle'] );

		$mk_hx_att['allow_html'] = $allow_html;

		foreach( array_keys( $mk_hx_att ) as $key ){
			if( is_null( $mk_hx_att[$key] ) ){
				unset( $mk_hx_att[$key] );
			}
		}

		$RTN = '';
		$RTN .= '<'.'?php ';
		if( $is_content_as_valiable ){
			$RTN .= $mk_tag_content_src;
		}
		$RTN .= 'print $theme->mk_hx( ';
		if( $is_content_as_valiable ){
			$RTN .= ' $TAG_HX_LABEL ';
		}else{
			$RTN .= text::data2text( $taginfo['content_str'] );
		}
		$RTN .= ' , ';
		$RTN .= text::data2text( $hx );
		$RTN .= ' , array(';
		foreach( $mk_hx_att as $attKey=>$attVal ){
			$RTN .= text::data2text( $attKey ).'=>'.$this->data2text_apply_vars( $attVal ).',';
		}
		$RTN .= ') );';
		if( $is_content_as_valiable ){
			$RTN .= ' unset($TAG_HX_LABEL);';
		}
		$RTN .= ' ?'.'>';

		return	$RTN;
	}

	#--------------------------------------
	#	<hr />
	function tag_hr( $taginfo , $option ){
		#	Pickles Framework 0.1.3 追加

		$mk_link_att = $taginfo['attribute'];
		$mk_link_att['cssclass'] = $taginfo['attribute']['class'];
		unset( $mk_link_att['class'] );
		$mk_link_att['cssstyle'] = $taginfo['attribute']['style'];
		unset( $mk_link_att['style'] );
		$mk_link_att['style'] = $taginfo['attribute']['tstyle'];
		unset( $mk_link_att['tstyle'] );

		foreach( array_keys( $mk_link_att ) as $key ){
			if( is_null( $mk_link_att[$key] ) ){
				unset( $mk_link_att[$key] );
			}
		}

		$RTN = '';
		$RTN .= '<'.'?php ';
		$RTN .= 'print $theme->mk_hr( array(';
		foreach( $mk_link_att as $attKey=>$attVal ){
			$RTN .= text::data2text( $attKey ).'=>'.$this->data2text_apply_vars( $attVal ).',';
		}
		$RTN .= ') );';
		$RTN .= ' ?'.'>';

		return	$RTN;
	}

	#--------------------------------------
	#	<form />
	function tag_form( $taginfo ){

		if( !is_array( $taginfo['attribute'] ) ){
			$taginfo['attribute'] = array();
		}
		if( !strlen( $taginfo['attribute']['action'] ) ){
			$taginfo['attribute']['action'] = ':';
		}
		if( !strlen( $taginfo['attribute']['method'] ) ){
			$taginfo['attribute']['method'] = 'get';
		}

		$ATTSRC = '';
		foreach( array_keys( $taginfo['attribute'] ) as $att_key ){
			if( $att_key == 'action' ){
				$ATTSRC .= ' '.( $att_key ).'="<'.'?php print htmlspecialchars( $theme->act( '.$this->data2text_apply_vars( text::htmlspecialchars_decode( $taginfo['attribute'][$att_key] ) ).' ) ); ?'.'>"';//←PxWF 0.6.6 : 変数を埋め込めるようになった。
			}else{
				$ATTSRC .= ' '.( $att_key ).'="<'.'?php print '.$this->data2text_apply_vars( $taginfo['attribute'][$att_key] , true ).'; ?'.'>"';
			}
			continue;
		}

		$RTN = '';
		$RTN .= '<form'.$ATTSRC.'>';

		#--------------------------------------
		#	タグにはさまれた部分
		if( is_array( $taginfo['content'] ) && count( $taginfo['content'] ) ){
			$RTN .= $this->publish( $taginfo['content'] );
		}elseif( strlen( $taginfo['content_str'] ) ){
			$RTN .= $taginfo['content_str'];
		}
		#	/タグにはさまれた部分
		#--------------------------------------

		$RTN .= '<div class="inline"><'.'?php print $theme->mk_form_defvalues( '.text::data2text( $taginfo['attribute']['action'] ).' ); ?'.'></div>';
		$RTN .= '</form>';

		return	$RTN;
	}

	#--------------------------------------
	#	<input />
	function tag_input( $taginfo ){

		if( !is_array( $taginfo['attribute'] ) ){
			$taginfo['attribute'] = array();
		}

		if( $taginfo['attribute']['name'] == $this->req->pkey() ){
			#	$req->pkey()は、<form>タグの拡張で出力しているので、
			#	直接書かれた$req->pkey()の情報は無条件に消してしまう。
			return	'';
		}

		$ATTSRC = '';
		foreach( array_keys( $taginfo['attribute'] ) as $att_key ){
			if( strtolower( $taginfo['attribute']['type'] ) == 'image' && strtolower( $att_key ) == 'src' ){
				#	Pickles Framework 0.5.10 : <input type="image"> のsrc属性の解釈に対応
				$ATTSRC .= ' '.$att_key.'="<'.'?php print htmlspecialchars( $theme->resource( '.text::data2text( text::html2text( $taginfo['attribute'][$att_key] ) ).' ) ) ?'.'>"';
			}else{
				$ATTSRC .= ' '.$att_key.'="'.$taginfo['attribute'][$att_key].'"';
			}
		}

		$RTN = '';
		$RTN .= '<input'.$ATTSRC.' />';

		return	$RTN;
	}


	#--------------------------------------
	#	カスタムタグ：<ifmodule /> (インターフェイスモジュール)
	function tag_ifmodule( $taginfo ){


		if( !is_array( $taginfo['attribute'] ) ){
			$taginfo['attribute'] = array();
		}

		$ifmodule_name = $taginfo['attribute']['name'];
		unset( $taginfo['attribute']['name'] );
		if( !strlen( $ifmodule_name ) ){
			$ifmodule_name = '';
		}

		$valueList = array();
		$attList = $taginfo['attribute'];
		$maxNum = 0;
		foreach( $attList as $key=>$value ){
			if( preg_match( '/^opt([0-9])+/i' , $key , $pregVal ) ){
				$newKey = intval( $pregVal[1][0] );
				$valueList[$newKey] = $value;
				unset( $taginfo['attribute'][$key] );
				if( $maxNum < $newKey ){
					$maxNum = intval( $newKey );
				}
				continue;
			}
		}

		#--------------------------------------
		#	タグにはさまれた部分
		$innerHTML = null;
		if( is_array( $taginfo['content'] ) && count( $taginfo['content'] ) ){
			$innerHTML = $this->publish( $taginfo['content'] );
		}elseif( strlen( $taginfo['content_str'] ) ){
			$innerHTML = $taginfo['content_str'];
		}
		if( !is_null( $innerHTML ) ){
			$valueList[1] = $innerHTML;
		}
		#	/タグにはさまれた部分
		#--------------------------------------

		$RTN = '';
		if( !is_null( $innerHTML ) ){
			$RTN .= '<'.'?php ';
			$RTN .= ' ob_start();';
			$RTN .= ' ?'.'>';
			$RTN .= $innerHTML;
			$RTN .= '<'.'?php ';
			$RTN .= 'print $theme->ifmodule( ';
			$RTN .= $this->data2text_apply_vars( $ifmodule_name );
			$RTN .= ' , ob_get_clean()';

			for( $i = 2; $i <= $maxNum; $i++ ){
				$RTN .= ' , '.$this->data2text_apply_vars( $valueList[$i] );
			}

			$RTN .= ' );';
			$RTN .= ' ?'.'>';
		}else{
			$RTN .= '<'.'?php ';
			$RTN .= 'print $theme->ifmodule( ';
			$RTN .= $this->data2text_apply_vars( $ifmodule_name );

			for( $i = 1; $i <= $maxNum; $i++ ){
				$RTN .= ' , '.$this->data2text_apply_vars( $valueList[$i] );
			}

			$RTN .= ' );';
			$RTN .= ' ?'.'>';
		}
		return	$RTN;
	}


	#--------------------------------------
	#	カスタムタグ：<setsrc /> ($theme->setsrc() にソースをセットする)
	#	Pickles Framework 0.5.7 追加
	function tag_setsrc( $taginfo ){

		if( !is_array( $taginfo['attribute'] ) ){
			$taginfo['attribute'] = array();
		}

		$src_type = $taginfo['attribute']['type'];
		unset( $taginfo['attribute']['type'] );
		if( !strlen( $src_type ) ){
			$src_type = '';
		}

		#--------------------------------------
		#	タグにはさまれた部分
		$innerHTML = null;
		if( is_array( $taginfo['content'] ) && count( $taginfo['content'] ) ){
			$innerHTML = $this->publish( $taginfo['content'] );
		}elseif( strlen( $taginfo['content_str'] ) ){
			$innerHTML = $taginfo['content_str'];
		}
		#	/タグにはさまれた部分
		#--------------------------------------

		$RTN = '';
		$RTN .= '<'.'?php ';
		$RTN .= ' ob_start();';
		$RTN .= ' ?'.'>';
		$RTN .= $innerHTML;
		$RTN .= '<'.'?php ';
		switch( strtolower( $taginfo['attribute']['reset'] ) ){
			case 'true':
			case 'yes':
			case 'y':
			case '1':
				$RTN .= '$theme->setsrc( ';
				$RTN .= 'ob_get_clean()';
				$RTN .= ' , '.$this->data2text_apply_vars($src_type);
				$RTN .= ' );';
				break;
			default:
				$RTN .= '$theme->putsrc( ';
				$RTN .= 'ob_get_clean()';
				$RTN .= ' , '.$this->data2text_apply_vars($src_type);
				$RTN .= ' );';
				break;
		}
		$RTN .= ' ?'.'>';
		return	$RTN;
	}


	#--------------------------------------
	#	カスタムタグ：<time />
	function tag_time( $taginfo ){

		if( !is_array( $taginfo['attribute'] ) ){
			$taginfo['attribute'] = array();
		}

		$att_ontime = $taginfo['attribute']['ontime'];
		$att_open = $taginfo['attribute']['open'];
		$att_close = $taginfo['attribute']['close'];

		$ifcont = array();
		if( strlen( $att_ontime ) ){
			array_push( $ifcont , 'time::is_ontime( '.$this->data2text_apply_vars( $att_ontime ).' )' );
		}
		if( strlen( $att_open ) ){
			array_push( $ifcont , 'time::datetime2int( '.$this->data2text_apply_vars( $att_open ).' ) <= $conf->time' );
		}
		if( strlen( $att_close ) ){
			array_push( $ifcont , 'time::datetime2int( '.$this->data2text_apply_vars( $att_close ).' ) >= $conf->time' );
		}

		$RTN = '';
		$RTN .= '<'.'?php ';
		$RTN .= 'if( ';
		if( count( $ifcont ) ){
			$RTN .= implode( ' && ' , $ifcont );
		}else{
			$RTN .= 'true';
		}
		$RTN .= ' ){ ?'.'>';

		#--------------------------------------
		#	タグにはさまれた部分
		if( is_array( $taginfo['content'] ) && count( $taginfo['content'] ) ){
			$RTN .= $this->publish( $taginfo['content'] );
		}elseif( strlen( $taginfo['content_str'] ) ){
			$RTN .= $taginfo['content_str'];
		}
		#	/タグにはさまれた部分
		#--------------------------------------

		$RTN .= '<'.'?php } ?'.'>';

		return	$RTN;
	}

	#--------------------------------------
	#	カスタムタグ：<ifct />
	function tag_ifct( $taginfo ){

		if( !is_array( $taginfo['attribute'] ) ){
			$taginfo['attribute'] = array();
		}

		$att_is = $taginfo['attribute']['is'];
		$att_not = $taginfo['attribute']['not'];

		$ifcont = array();
		if( strlen( $att_is ) ){
			$CTList = explode( '|' , $att_is );
			$MEMO = array();
			foreach( $CTList as $CT ){
				array_push( $MEMO , 'strtoupper( $user->get_ct() ) == strtoupper( '.$this->data2text_apply_vars( trim( $CT ) ).' )' );
			}
			array_push( $ifcont , '('.implode( ' || ' , $MEMO ).')' );
		}
		if( strlen( $att_not ) ){
			$CTList = explode( '|' , $att_not );
			$MEMO = array();
			foreach( $CTList as $CT ){
				array_push( $MEMO , 'strtoupper( $user->get_ct() ) != strtoupper( '.$this->data2text_apply_vars( trim( $CT ) ).' )' );
			}
			array_push( $ifcont , '('.implode( ' && ' , $MEMO ).')' );
		}

		$RTN = '';
		$RTN .= '<'.'?php ';
		$RTN .= 'if( ';
		if( count( $ifcont ) ){
			$RTN .= implode( ' && ' , $ifcont );
		}else{
			$RTN .= 'true';
		}
		$RTN .= ' ){ ?'.'>';

		#--------------------------------------
		#	タグにはさまれた部分
		if( is_array( $taginfo['content'] ) && count( $taginfo['content'] ) ){
			$RTN .= $this->publish( $taginfo['content'] );
		}elseif( strlen( $taginfo['content_str'] ) ){
			$RTN .= $taginfo['content_str'];
		}
		#	/タグにはさまれた部分
		#--------------------------------------

		$RTN .= '<'.'?php } ?'.'>';

		return	$RTN;
	}

	#--------------------------------------
	#	カスタムタグ：<iflang />
	function tag_iflang( $taginfo ){

		if( !is_array( $taginfo['attribute'] ) ){
			$taginfo['attribute'] = array();
		}

		$att_is = $taginfo['attribute']['is'];
		$att_not = $taginfo['attribute']['not'];

		$ifcont = array();
		if( strlen( $att_is ) ){
			$langList = explode( '|' , $att_is );
			$MEMO = array();
			foreach( $langList as $lang ){
				array_push( $MEMO , 'strtolower( $user->getlanguage() ) == strtolower( '.$this->data2text_apply_vars( trim( $lang ) ).' )' );
			}
			array_push( $ifcont , '('.implode( ' || ' , $MEMO ).')' );
		}
		if( strlen( $att_not ) ){
			$langList = explode( '|' , $att_not );
			$MEMO = array();
			foreach( $langList as $lang ){
				array_push( $MEMO , 'strtolower( $user->getlanguage() ) != strtolower( '.$this->data2text_apply_vars( trim( $lang ) ).' )' );
			}
			array_push( $ifcont , '('.implode( ' && ' , $MEMO ).')' );
		}

		$RTN = '';
		$RTN .= '<'.'?php ';
		$RTN .= 'if( ';
		if( count( $ifcont ) ){
			$RTN .= implode( ' && ' , $ifcont );
		}else{
			$RTN .= 'true';
		}
		$RTN .= ' ){ ?'.'>';

		#--------------------------------------
		#	タグにはさまれた部分
		if( is_array( $taginfo['content'] ) && count( $taginfo['content'] ) ){
			$RTN .= $this->publish( $taginfo['content'] );
		}elseif( strlen( $taginfo['content_str'] ) ){
			$RTN .= $taginfo['content_str'];
		}
		#	/タグにはさまれた部分
		#--------------------------------------

		$RTN .= '<'.'?php } ?'.'>';

		return	$RTN;
	}

	#--------------------------------------
	#	カスタムタグ：<islogin />
	function tag_islogin( $taginfo ){

		$RTN = '';
		$RTN .= '<'.'?php if( $user->is_login() ){ ?'.'>';

		#--------------------------------------
		#	タグにはさまれた部分
		if( is_array( $taginfo['content'] ) && count( $taginfo['content'] ) ){
			$RTN .= $this->publish( $taginfo['content'] );
		}elseif( strlen( $taginfo['content_str'] ) ){
			$RTN .= $taginfo['content_str'];
		}
		#	/タグにはさまれた部分
		#--------------------------------------

		$RTN .= '<'.'?php } ?'.'>';

		return	$RTN;
	}

	#--------------------------------------
	#	カスタムタグ：<isnotlogin />
	function tag_isnotlogin( $taginfo ){

		$RTN = '';
		$RTN .= '<'.'?php if( !$user->is_login() ){ ?'.'>';

		#--------------------------------------
		#	タグにはさまれた部分
		if( is_array( $taginfo['content'] ) && count( $taginfo['content'] ) ){
			$RTN .= $this->publish( $taginfo['content'] );
		}elseif( strlen( $taginfo['content_str'] ) ){
			$RTN .= $taginfo['content_str'];
		}
		#	/タグにはさまれた部分
		#--------------------------------------

		$RTN .= '<'.'?php } ?'.'>';

		return	$RTN;
	}

	#--------------------------------------
	#	カスタムタグ：<pleaselogin />
	function tag_pleaselogin( $taginfo ){

		$RTN = '';
		$RTN .= '<'.'?php return $theme->pleaselogin(); ?'.'>';

		return	$RTN;
	}

	#--------------------------------------
	#	カスタムタグ：<pageinfo />
	function tag_pageinfo( $taginfo , $option ){
		#	Pickles Framework 0.5.0 追加
		$current_pid = '';
		if( !strlen( $taginfo['attribute']['pid'] ) ){
			$current_pid = '$req->p()';
		}else{
			$current_pid = $this->data2text_apply_vars( $taginfo['attribute']['pid'] );
		}
		$RTN = '';
		$RTN .= '<'.'?php ';
		if( strtolower( $taginfo['attribute']['name'] ) == '*cattitle' ){
			#	これだけ特別処理
			$RTN .= 'print htmlspecialchars( $site->getpageinfo( $site->getpageinfo( '.$current_pid.' , \'cattitleby\' ) , \'title\' ) );';
		}else{
			#	これが普通の処理
			$RTN .= 'print htmlspecialchars( $site->getpageinfo( '.$current_pid.' , strtolower( '.$this->data2text_apply_vars( $taginfo['attribute']['name'] ).' ) ) );';
		}
		$RTN .= ' ?'.'>';
		return	$RTN;
	}
	#--------------------------------------
	#	カスタムタグ：<setpageinfo />
	function tag_setpageinfo( $taginfo , $option ){
		#	Pickles Framework 0.6.5 追加
		$current_pid = '';
		if( !strlen( $taginfo['attribute']['pid'] ) ){
			$current_pid = '$req->p()';
		}else{
			$current_pid = $this->data2text_apply_vars( $taginfo['attribute']['pid'] );
		}
		unset( $taginfo['attribute']['id'] );
		unset( $taginfo['attribute']['pid'] );

		$sitemap_definition = $this->site->get_sitemap_definition();
		$attr = array();
		foreach( $sitemap_definition as $key=>$val ){
			if( is_null( $taginfo['attribute'][$key] ) ){ continue; }
			$attr[$key] = $taginfo['attribute'][$key];
		}
		if( !strlen( $attr['title'] ) ){//PxFW 0.7.1 タイトルを省略した場合、$po から採るようにした。
			$attr['title'] = $this->site->getpageinfo(':','title');
		}

		$RTN = '';
		$RTN .= '<'.'?php ';
		$RTN .= '$site->setpageinfoall(';
		$RTN .= ' '.$current_pid.' , array(';
		foreach( $attr as $attKey=>$attVal ){
			$RTN .= text::data2text( $attKey ).'=>'.$this->data2text_apply_vars( $attVal ).' ,';
		}
		$RTN .= ') );';
		$RTN .= ' ?'.'>';
		return	$RTN;
	}
	#--------------------------------------
	#	カスタムタグ：<ispageinfo />
	function tag_ispageinfo( $taginfo , $option ){
		#	Pickles Framework 0.5.0 追加
		$current_pid = '';
		if( !strlen( $taginfo['attribute']['pid'] ) ){
			$current_pid = '$req->p()';
		}else{
			$current_pid = $this->data2text_apply_vars( $taginfo['attribute']['pid'] );
		}
		$infoname = strtolower( $taginfo['attribute']['name'] );

		#--------------------------------------
		#	タグにはさまれた部分
		$tag_content = '';
		if( is_array( $taginfo['content'] ) && count( $taginfo['content'] ) ){
			$tag_content = $this->publish( $taginfo['content'] );
		}elseif( strlen( $taginfo['content_str'] ) ){
			$tag_content = $taginfo['content_str'];
		}
		#	/タグにはさまれた部分
		#--------------------------------------

		$sitemap_definition = $this->site->get_sitemap_definition();

		$RTN = '';
		$RTN .= '<'.'?php ';
		$RTN .= 'if( ';
		if( !is_null( $taginfo['attribute']['value'] ) ){
			#--------------------------------------
			#	指定値と一致するか比較する場合
			if( $sitemap_definition[$infoname]['rules']['type'] == 'id' ){
				#	ページIDなら
				#	PxFW 0.6.5 追加の処理
				$RTN .= '$site->getpageinfo( '.$current_pid.' , '.$this->data2text_apply_vars( $infoname ).' ) == $site->getpageinfo( '.$this->data2text_apply_vars( $taginfo['attribute']['value'] ).' , '.$this->data2text_apply_vars( $infoname ).' )';
			}else{
				#	その他一般の値
				$RTN .= '$site->getpageinfo( '.$current_pid.' , '.$this->data2text_apply_vars( $infoname ).' ) == '.$this->data2text_apply_vars( $taginfo['attribute']['value'] ).'';
			}
		}else{
			#--------------------------------------
			#	値が定義されているか調べる場合
			if( $sitemap_definition[$infoname]['rules']['type'] == 'bool' ){
				#	フラグ系なら
				$RTN .= '$site->getpageinfo( '.$current_pid.' , '.$this->data2text_apply_vars( $infoname ).' )';
			}else{
				#	その他一般の値
				$RTN .= 'strlen( $site->getpageinfo( '.$current_pid.' , '.$this->data2text_apply_vars( $infoname ).' ) )';
			}
		}
		$RTN .= ' ){ '."\n";
		$RTN .= ' ?'.'>';
		$RTN .= $tag_content;
		$RTN .= '<'.'?php ';
		$RTN .= ' }';
		$RTN .= ' ?'.'>';
		return	$RTN;
	}
	#--------------------------------------
	#	カスタムタグ：<isnotpageinfo />
	function tag_isnotpageinfo( $taginfo , $option ){
		#	Pickles Framework 0.5.0 追加
		$current_pid = '';
		if( !strlen( $taginfo['attribute']['pid'] ) ){
			$current_pid = '$req->p()';
		}else{
			$current_pid = $this->data2text_apply_vars( $taginfo['attribute']['pid'] );
		}
		$infoname = strtolower( $taginfo['attribute']['name'] );

		#--------------------------------------
		#	タグにはさまれた部分
		$tag_content = '';
		if( is_array( $taginfo['content'] ) && count( $taginfo['content'] ) ){
			$tag_content = $this->publish( $taginfo['content'] );
		}elseif( strlen( $taginfo['content_str'] ) ){
			$tag_content = $taginfo['content_str'];
		}
		#	/タグにはさまれた部分
		#--------------------------------------

		$sitemap_definition = $this->site->get_sitemap_definition();

		$RTN = '';
		$RTN .= '<'.'?php ';
		$RTN .= 'if( ';
		if( $sitemap_definition[$infoname]['rules']['type'] == 'bool' ){
			#	フラグ系なら
			$RTN .= '!$site->getpageinfo( '.$current_pid.' , '.$this->data2text_apply_vars( $infoname ).' )';
		}elseif( $sitemap_definition[$infoname]['rules']['type'] == 'id' ){
			#	ページIDなら
			#	PxFW 0.6.5 追加の処理
			$RTN .= '$site->getpageinfo( '.$current_pid.' , '.$this->data2text_apply_vars( $infoname ).' ) != $site->getpageinfo( '.$this->data2text_apply_vars( $taginfo['attribute']['value'] ).' , '.$this->data2text_apply_vars( $infoname ).' )';
		}else{
			#	その他一般の値
			$RTN .= '!strlen( $site->getpageinfo( '.$current_pid.' , '.$this->data2text_apply_vars( $infoname ).' ) )';
		}
		$RTN .= ' ){ '."\n";
		$RTN .= ' ?'.'>';
		$RTN .= $tag_content;
		$RTN .= '<'.'?php ';
		$RTN .= ' }';
		$RTN .= ' ?'.'>';
		return	$RTN;
	}

	#--------------------------------------
	#	カスタムタグ：<projectinfo />
	function tag_projectinfo( $taginfo , $option ){
		#	Pickles Framework 0.5.0 追加
		$infoname = strtolower( $taginfo['attribute']['name'] );
		if( !strlen( $infoname ) ){
			return	'';
		}
		$RTN = '';
		$RTN .= '<'.'?php ';
		switch( $infoname ){
			case 'sitetitle':
				$RTN .= 'print htmlspecialchars( $conf->info_sitetitle );';
				break;
			case 'url_sitetop':
				$RTN .= 'print htmlspecialchars( $conf->url_sitetop );';
				break;
			case 'copyright':
				$RTN .= 'print $site->getcopyright( \'print\' , $user->get_ct() );';//$site->getcopyright()はHTMLを返す
				break;
			case 'copyright.copy':
				$RTN .= 'print $site->getcopyright( \'copy\' , $user->get_ct() );';//$site->getcopyright()はHTMLを返す
				break;
			case 'copyright.since_from':
				$RTN .= 'print $site->getcopyright( \'since_from\' , $user->get_ct() );';//$site->getcopyright()はHTMLを返す
				break;
			case 'copyright.since_to':
				$RTN .= 'print $site->getcopyright( \'since_to\' , $user->get_ct() );';//$site->getcopyright()はHTMLを返す
				break;
		}
		$RTN .= ' ?'.'>';
		return	$RTN;
	}

	#--------------------------------------
	#	カスタムタグ：<dummytext />
	function tag_dummytext( $taginfo , $option ){
		#	Pickles Framework 0.5.8 追加
		$length = strtolower( $taginfo['attribute']['size'] );
		if( !strlen( $length ) ){
			$length = 50;
		}

		$RTN = '';
		$RTN .= '<'.'?php ';
		$RTN .= ' print test::mk_dummy_text( intval( '.$this->data2text_apply_vars( $length ).' ) ); ';
		$RTN .= ' ?'.'>';
		return	$RTN;
	}

	#--------------------------------------
	#	属性値の中の変数を反映する
	function data2text_apply_vars( $value , $is_htmlspecialchars = false ){
		#	PxFW 0.6.6 : 追加
		if( !is_string( $value ) ){
			return $value;
		}

		#	文字列型
		$RTN = '\''.text::escape_singlequote( $value ).'\'';
		$RTN = preg_replace( '/\r\n|\r|\n/' , '\'."\\n".\'' , $RTN );
		$RTN = preg_replace( '/'.preg_quote('<'.'?','/').'/' , '<\'.\'?' , $RTN );
		$RTN = preg_replace( '/'.preg_quote('?'.'>','/').'/' , '?\'.\'>' , $RTN );
		$RTN = preg_replace( '/'.preg_quote('/'.'*','/').'/' , '/\'.\'*' , $RTN );
		$RTN = preg_replace( '/'.preg_quote('*'.'/','/').'/' , '*\'.\'/' , $RTN );
		$RTN = preg_replace( '/<(scr)(ipt)/i' , '<$1\'.\'$2' , $RTN );
		$RTN = preg_replace( '/\/(scr)(ipt)>/i' , '/$1\'.\'$2>' , $RTN );
		$RTN = preg_replace( '/<(sty)(le)/i' , '<$1\'.\'$2' , $RTN );
		$RTN = preg_replace( '/\/(sty)(le)>/i' , '/$1\'.\'$2>' , $RTN );
		$RTN = preg_replace( '/<\!\-\-/i' , '<\'.\'!\'.\'--' , $RTN );
		$RTN = preg_replace( '/\-\->/i' , '--\'.\'>' , $RTN );

		$STR = $RTN;
		$RTN = '';
		while( 1 ){
			if( !preg_match( '/^(.*?)\{\$([a-zA-Z\_][a-zA-Z0-9\_]+?)\}(.*)$/' , $STR , $matched ) ){
				$RTN .= $STR;
				break;
			}
			$RTN .= $matched[1];
			if( !$is_htmlspecialchars ){
				$RTN .= '\'.$'.$matched[2].'.\'';
			}else{
				$RTN .= '\'.htmlspecialchars( $'.$matched[2].' ).\'';
			}
			$STR = $matched[3];
		}

		return	$RTN;
	}

	#--------------------------------------
	#	CSS文法内のURLを調整し置き換える
	function replace_url_in_css( $CONTENTS ){
		#	Pickles Framework 0.3.3 追加

		$preg_ptn_url = '(url\()\s*((?:"|\')?)(.*?)\3\s*(\))';
		$preg_ptn_import = '(\@import(?:\t| )+)((?:"|\'))(.*?)\3(\s*?(?:[a-zA-Z0-9\,]+\s*?)*(?:\r\n|\r|\n|\;))';


		foreach( array( $preg_ptn_url , $preg_ptn_import ) as $preg_ptn ){
			$RTN = '';
			while( 1 ){
				$is_matched = preg_match( '/^(.*?)'.$preg_ptn.'(.*)$/is' , $CONTENTS , $preg_matched );
				if( !$is_matched ){
					#	もう見つからない場合
					break;
				}

				$RTN .=					$preg_matched[1];
				$PREFIX =				$preg_matched[2];
				$DELIMITTER =			$preg_matched[3];
				$TARGET_PATH_ORIGINAL =	$preg_matched[4];
				$SUFIX =				$preg_matched[5];
				$CONTENTS =				$preg_matched[6];

				if( preg_match( '/\r\n|\r|\n| |\t/s' , $TARGET_PATH_ORIGINAL ) ){ continue; }

				$TARGET_PATH_ORIGINAL = '<'.'?php print ( $theme->resource( '.$this->data2text_apply_vars( $TARGET_PATH_ORIGINAL).' ) ); ?'.'>';
				$RTN .= $PREFIX;
				$RTN .= $DELIMITTER.$TARGET_PATH_ORIGINAL.$DELIMITTER;
				$RTN .= $SUFIX;

			}

			$RTN .= $CONTENTS;
			$CONTENTS = $RTN;
		}

		return	$RTN;
	}

}

?>