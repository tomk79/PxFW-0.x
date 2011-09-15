<?php

#	Copyright (C)Tomoya Koyanagi.
#	LastUpdate : 23:15 2011/05/05

require_once( $conf->path_lib_base.'/resources/contentsoperator/html.php' );

#******************************************************************************************************************
#	Conductorが読み込んだWikiファイルのを処理する
class base_resources_contentsoperator_wiki extends base_resources_contentsoperator_html{
	#--------------------------------------
	#	このクラスの実装は暫定的な実験段階にあります。
	#	将来の拡張により、
	#	メソッド名やWIKI文法仕様などが、
	#	変更される可能性があります。
	#--------------------------------------

	var $execute_type = 'wiki';//PxFW 0.7.2 追加

	var $typeLock = null;
	var $contents_config = array();
		#	コンテンツ書式設定 - Pickles Framework 0.1.8 追加

	#--------------------------------------
	#	コンテンツを実行して、ソースを返す。
	function execute_contents( $content_file_path , $path_cache ){
		#--------------------------------------
		#	キャッシュの有無、有効期限を評価
		if( $this->debug_mode || !$this->dbh->is_file( $path_cache ) || $this->dbh->comp_filemtime( $content_file_path , $path_cache ) ){
			#	キャッシュが存在しないか、最終更新日がキャッシュよりコンテンツの方が新しい場合には、
			#	キャッシュファイルを無効とみなし、再度パース。
			#	HTMLをパースしてキャッシュを作成→保存
			$ORIGINAL_HTML_SRC = $this->dbh->file_get_contents( $content_file_path );
			$ORIGINAL_HTML_SRC = preg_replace( '/^(?:\r\n|\r|\n)+/' , '' , $ORIGINAL_HTML_SRC );
			$ORIGINAL_HTML_SRC = preg_replace( '/(?:\r\n|\r|\n)+$/' , '' , $ORIGINAL_HTML_SRC );
			$ORIGINAL_HTML_SRC_LIST = preg_split( '/\r\n|\r|\n/ism' , $ORIGINAL_HTML_SRC );
			if( !$this->dbh->is_dir( dirname( $path_cache ) ) ){
				$this->dbh->mkdirall( dirname( $path_cache ) );
			}


			#	コンテンツを処理
			$CACHE_SRC = $this->process_src( $ORIGINAL_HTML_SRC_LIST );

			ignore_user_abort(true);//←PxFramework 0.6.11
			$this->dbh->file_overwrite( $path_cache , $CACHE_SRC );
			ignore_user_abort(false);//←PxFramework 0.6.11

			unset( $parser , $parsed_html , $ORIGINAL_HTML_SRC , $CACHE_SRC );
		}

		ignore_user_abort(true);//←PxFramework 0.6.11
		$this->theme->cache_all_resources();//ローカルリソースを全てキャッシュ(Pickles Framework 0.3.3)
		ignore_user_abort(false);//←PxFramework 0.6.11

		$RTN = isolated::getrequiredreturn_once( $path_cache , &$this->conf , &$this->req , &$this->dbh , &$this->user , &$this->site , &$this->errors , &$this->theme , &$this->custom , $this->conf->contents_start_str , $this->conf->contents_end_str );
		return	$RTN;

	}

	#--------------------------------------
	#	コンテンツを処理してソースを返す
	function process_src( $ORIGINAL_HTML_SRC_LIST ){

		#	コンテンツを解析して、DOM(じゃないけど)ツリーを作成
		$contentTree = $this->parse_content_src( $ORIGINAL_HTML_SRC_LIST );

		#	ツリーからマークアップされたソースを再構成
		$CONTENT_SRC = $this->exec_tree( $contentTree );
		unset($contentTree);//もう使わないので開放


		#	Pickles Framework 0.1.8 → ページャー機能対応
		$RTN = '';
		$RTN .= $CONTENT_SRC;
		unset($CONTENT_SRC);//もう使わないので開放
		$RTN .= '<'.'?php'."\n";
		$RTN .= '	if( !is_array( $SRC_PAGE_LIST ) ){ $SRC_PAGE_LIST = array(); }'."\n";
		$RTN .= '	array_push( $SRC_PAGE_LIST , @ob_get_clean() );'."\n";
		$RTN .= '	ob_start();'."\n";
		$RTN .= '?'.'>'."\n";
		$RTN .= '<'.'?php'."\n";
		$RTN .= '	#コンテンツ出力'."\n";
		$RTN .= '	$pager_disallowed_ct = array('."\n";
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
		$RTN .= '		$PREV = \'\';'."\n";
		$RTN .= '		$PREV .= \'<div class="ttr AlignC">\'."\\n";'."\n";
		$RTN .= '		if( strlen( $pager_info[\'prev\'] ) ){'."\n";
		$RTN .= '			$PREV .= \'	[\'.$theme->mk_link( \':\'.$pager_info[\'prev\'] , array( \'label\'=>\'前へ\' ) ).\']\'."\\n";'."\n";
		$RTN .= '		}'."\n";
		$RTN .= '		$PREV .= \'</div>\'."\\n";'."\n";
		$RTN .= ''."\n";
		$RTN .= '		$NEXT = \'\';'."\n";
		$RTN .= '		$NEXT .= \'<div class="ttr AlignC">\'."\\n";'."\n";
		$RTN .= '		if( strlen( $pager_info[\'next\'] ) ){'."\n";
		$RTN .= '			$NEXT .= \'	[\'.$theme->mk_link( \':\'.$pager_info[\'next\'] , array( \'label\'=>\'次へ\' ) ).\']\'."\\n";'."\n";
		$RTN .= '		}'."\n";
		$RTN .= '		$NEXT .= \'</div>\'."\\n";'."\n";
		$RTN .= ''."\n";
		$RTN .= ''."\n";
		$RTN .= '		$PAGER = \'\';'."\n";
		$RTN .= '		$PAGER .= \'<ul>\'."\\n";'."\n";
		$RTN .= '		for( $i = 1; $i <= count($SRC_PAGE_LIST); $i++ ){'."\n";
		$RTN .= '			if( $pnum != $i ){'."\n";
		$RTN .= '				$PAGER .= \'	<li class="ttr">\'.$theme->mk_link( \':\'.$i , array( \'label\'=>\'Page \'.$i ) ).\'</li>\'."\\n";'."\n";
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

		return	$RTN;
	}

	#--------------------------------------
	#	コンテンツを解析して、DOM(じゃないけど)ツリーを作成
	function parse_content_src( $ORIGINAL_HTML_SRC_LIST ){

		$SRCMEMO = '';
		$lastIndent = null;
		$last_type = null;
		$current_type = null;
		$contentTree = array();

		foreach( $ORIGINAL_HTML_SRC_LIST as $SRC ){
			$SRC = preg_replace( '/\r\n|\r|\n/' , '' , $SRC );

			#	インデントを計測
			$indent = $lastIndent;

			#	暫定的に削除
			if( $this->typeLock == 'html' && $last_type == 'html' ){
				#	HTMLモードのときはインデントを無視
			}else{
				$indent = strlen( preg_replace( '/^([ \t]*).*$/' , "$1" , $SRC ) );
				$SRC = preg_replace( '/^[ \t]+/' , '' , $SRC );
			}

			if( preg_match( '/^--[ \t]/' , $SRC ) ){
				#	ハイフンハイフンスペース(またはタブ)で始まったらコメント扱い。
				#	SQL風に。
				continue;
			}

			list( $SRC , $current_type ) = $this->get_type_of_row( $SRC , $last_type );
			if( ( $last_type != $current_type && !is_null( $last_type ) ) || ( !is_null( $lastIndent ) && $lastIndent !== $indent ) && $this->typeLock != 'html' ){
				#	前行と異なる文法またはインデントだったら
				array_push( $contentTree , array( 'src'=>$SRCMEMO , 'type'=>$last_type , 'indent'=>$lastIndent ) );
				$lastIndent = $indent;	//初期化
				$SRCMEMO = '';	//初期化
			}

			$SRCMEMO .= $SRC;
			$last_type = $current_type;
			$lastIndent = $indent;

		}

		#	最後の行を追加
		array_push( $contentTree , array( 'src'=>$SRCMEMO , 'type'=>$last_type ) );

		return	$contentTree;
	}

	#--------------------------------------
	#	ツリーからマークアップされたソースを再構成
	function exec_tree( $contentTree ){
		$RTN = '';
		$lastIndent = 0;
		foreach( $contentTree as $Line ){
			$cindent = intval( $Line['indent'] ) - $lastIndent;

			if( $cindent > 0 ){
				for( $i = $cindent; $i > 0 ; $i-- ){
					$RTN .= '<div class="indent">'."\n";
				}
			}elseif( $cindent < 0 ){
				for( $i = $cindent; $i < 0 ; $i++ ){
					$RTN .= '</div>'."\n";
				}
			}

			if( method_exists( $this , 'mksrc_'.$Line['type'].'' ) ){
				$RTN .= eval( 'return	$this->mksrc_'.$Line['type'].'( $Line[\'src\'] );' );
			}

			$lastIndent = intval( $Line['indent'] );
		}
		return	$RTN;
	}


	#	行を調べて、種類を問う
	function get_type_of_row( $line , $last_type = null ){
		if( !is_null( $this->typeLock ) ){
			$MEMO_typeLock = $this->typeLock;
			return	array( eval( 'return $this->check_'.$this->typeLock.'( $line );' ) , $MEMO_typeLock );
		}

		if( !strlen( $line ) ){
			return	array( '' , 'spacer' );
		}

		$syntaxList = array(
			'html',//HTMLシンタックスの解釈
			'config',
			'hr',
			'ifmodule',
			'page_separator',
			'autoindex',//PxFW 0.6.12 追加
			'childlist',
			'broslist',
			'spacer',
			'h4',
			'h3',
			'h2',
			'back2top',//Pickles Framework 0.4.4 追加
			'blockquote',
			'sourcecode',
			'li',
			'ol',
			'li_annotation',//Pickles Framework 0.5.4 追加
			'dl',//Pickles Framework 0.3.8 追加
			'table',
			'p',
		);

		foreach( $syntaxList as $synLine ){
			if( !method_exists( $this , 'check_'.$synLine ) ){ continue; }

			if( $RTN = eval( 'return $this->check_'.$synLine.'( $line );' ) ){
				return	array( $RTN , $synLine );
			}

		}

		return	array( $line."\n" , 'p' );
	}



	#==================================================================================================================
	#	シンタックス別の処理

	#--------------------------------------
	#	設定(config)の処理

	#	判定
	function check_config( $line ){
		#	Pickles Framework 0.1.8 → 追加
		if( preg_match( '/^CONFIG>(.*?)\=(.*)$/is' , $line , $result ) ){
			return	$line."\n";
		}
		return	false;
	}
	#	ソース作成
	function mksrc_config( $SRC ){
		#	Pickles Framework 0.1.8 → 追加
		foreach( preg_split( '/\r\n|\r|\n/' , $SRC ) as $line ){
			preg_match( '/^CONFIG>(.*?)\=(.*)$/is' , $line , $result );
			$result[1] = strtolower( trim( $result[1] ) );
			if( !strlen($result[1]) ){
				continue;
			}

			$result[2] = trim( $result[2] );
			if( $result[1] == 'image.clickable' || preg_match( '/^pager\.disallowed_ct\.([a-zA-Z0-9]+)$/is' , $result[1] ) ){
				#	bool 型の設定項目 (Pickles Framework 0.5.4 追加)
				if( !$result[2] || strtolower( $result[2] ) == 'false' ){
					$result[2] = false;
				}else{
					$result[2] = true;
				}
			}

			$this->contents_config[$result[1]] = $result[2];
		}
		return	'';
	}


	#--------------------------------------
	#	ページの先頭へ戻る(back2top)の処理

	#	判定
	function check_back2top( $line ){
		#	Pickles Framework 0.4.4 追加
		if( preg_match( '/^>>>back2top$/is' , $line , $result ) ){
			return	$line."\n";
		}
		return	false;
	}
	#	ソース作成
	function mksrc_back2top( $SRC ){
		#	Pickles Framework 0.4.4 追加
		$RTN = '';
		$SRC = trim( $SRC );
		foreach( preg_split( '/\r\n|\r|\n/' , $SRC ) as $line ){
			if( !strlen( $line ) ){ continue; }
			$RTN .= '<'.'?php'."\n";
			$RTN .= '	#	back2top()'."\n";
			$RTN .= '	print $theme->mk_back2top()."\n";'."\n";
			$RTN .= ' ?'.'>'."\n";
		}
		return	$RTN;
	}


	#--------------------------------------
	#	段落(p)の処理

	#	判定
	function check_p( $line ){
		#	注意：ここに送られる $line には、改行は含まれていません。
		$line = preg_replace( '/^\\\\/' , '' , $line );
			//	↑1:57 2008/02/08 Pickles Framework 0.2.5 追加
			//	　ブロック要素のエスケープ処理
		return	$line."\n";
	}
	#	ソース作成
	function mksrc_p( $SRC ){
		$RTN = $SRC;
		$RTN = htmlspecialchars( $RTN );
		$RTN = preg_replace( '/\r\n|\r|\n/' , '<br />'."\n" , $RTN );
		$RTN = $this->common_strong( $RTN );
		$RTN = $this->common_code( $RTN );
		$RTN = $this->common_q( $RTN );
		$RTN = $this->common_link( $RTN );
		$RTN = $this->common_image( $RTN );
		$RTN = '<p class="ttr">'.$RTN.'</p>'."\n";
		return	$RTN;
	}


	#--------------------------------------
	#	水平線(hr)の処理

	#	判定
	function check_hr( $line ){
		#	Pickles Framework 0.1.8 → 追加
		if( trim( $line ) == '---' ){
			return	$line;
		}
		return	false;
	}
	#	ソース作成
	function mksrc_hr( $SRC ){
		#	Pickles Framework 0.1.8 → 追加
		$RTN = '<hr />'."\n";
		return	$RTN;
	}


	#--------------------------------------
	#	改ページ(page_separator)の処理

	#	判定
	function check_page_separator( $line ){
		#	Pickles Framework 0.1.8 → 追加
		$line = trim( $line );
		if( preg_match( '/^'.preg_quote( '>>>' , '/' ).'(?: |\t|　)*changing(?: |\t|　)*page(?: |\t|　)*(?:\|(.*))?$/is' , $line , $result ) ){
			#	>>>changing page|ct=PC|MP|PDA
			#	このマーカー書式は仮の定義。
			return	'>>>changing page|'.trim($result[1])."\n";
		}
		return	false;
	}
	#	ソース作成
	function mksrc_page_separator( $SRC ){
		#	Pickles Framework 0.1.8 → 追加

		$SRC = preg_replace( '/^(.*?)(?:\r\n|\r|\n)/' , "$1" , $SRC );
			#	1行目だけ抜き出し

		$allow_ct = '';
			#	改ページするか否かの条件式（CASE文）

		$option_str = preg_replace( '/^'.preg_quote('>>>changing page').'\|(.*)$/is' , "$1" , $SRC );
			#	オプション部分を取り出す

		$MEMO = $this->theme->parseoption( $option_str );
		if( !is_array( $MEMO ) ){ $MEMO = array(); }
		$option = array();
		foreach( $MEMO as $key=>$val ){
			$option[strtolower(trim($key))] = trim($val);
		}
		unset($MEMO);
		if( strlen( $option['ct'] ) ){
			$ct_list = explode( '|' , $option['ct'] );
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
		$PAGERSRC .= '?'.'>'."\n";

		if( strlen( $allow_ct ) ){
			$RTN .= '<'.'?php'."\n";
			$RTN .= 'switch( $user->get_ct() ){'."\n";
			$RTN .= '	'.$allow_ct."\n";
			$RTN .= '	?'.'>'.$PAGERSRC.'<'.'?php'."\n";
			$RTN .= '	break;'."\n";
			$RTN .= 'default:'."\n";
			$RTN .= '	break;'."\n";
			$RTN .= '}'."\n";
			$RTN .= ' ?'.'>'."\n";

#			$RTN .= '<div style="page-break-before:always;"></div>'."\n";//印刷時の改ページ
		}else{
			$RTN .= $PAGERSRC;
		}
		return	$RTN;
	}


	#--------------------------------------
	#	ページ内目次自動生成(autoindex)の処理
	#	PxFW 0.6.12 追加

	#	判定
	function check_autoindex( $line ){
		#	PxFW 0.6.12 追加
		$line = trim( $line );
		if( preg_match( '/^'.preg_quote( '>>>' , '/' ).'(?: |\t|　)*autoindex(?: |\t|　)*(?:\|(.*))?$/is' , $line , $result ) ){
			#	ex. >>>autoindex|pid=XXX
			return	'>>>autoindex|'.trim($result[1])."\n";
		}
		return	false;
	}
	#	ソース作成
	function mksrc_autoindex( $SRC ){
		#	PxFW 0.6.12 追加

		$SRC = preg_replace( '/^(.*?)(?:\r\n|\r|\n)/' , "$1" , $SRC );
			#	1行目だけ抜き出し

		$allow_ct = '';
			#	改ページするか否かの条件式（CASE文）

		$option_str = preg_replace( '/^'.preg_quote('>>>autoindex').'\|(.*)$/is' , "$1" , $SRC );
			#	オプション部分を取り出す

		$RTN = '';
		$RTN .= '<'.'?php'."\n";
		$RTN .= '	# autoindex'."\n";
		$RTN .= '	print $theme->mk_autoindex();'."\n";
		$RTN .= '?'.'>'."\n";
		return	$RTN;
	}

	#--------------------------------------
	#	子階層リンクの一覧(childlist)の処理
	#	Pickles Framework 0.3.9 追加

	#	判定
	function check_childlist( $line ){
		#	Pickles Framework 0.3.9 追加
		$line = trim( $line );
		if( preg_match( '/^'.preg_quote( '>>>' , '/' ).'(?: |\t|　)*childlist(?: |\t|　)*(?:\|(.*))?$/is' , $line , $result ) ){
			#	ex. >>>childlist|pid=XXX
			return	'>>>childlist|'.trim($result[1])."\n";
		}
		return	false;
	}
	#	ソース作成
	function mksrc_childlist( $SRC ){
		#	Pickles Framework 0.3.9 追加

		$SRC = preg_replace( '/^(.*?)(?:\r\n|\r|\n)/' , "$1" , $SRC );
			#	1行目だけ抜き出し

		$allow_ct = '';
			#	改ページするか否かの条件式（CASE文）

		$option_str = preg_replace( '/^'.preg_quote('>>>childlist').'\|(.*)$/is' , "$1" , $SRC );
			#	オプション部分を取り出す

		$RTN = '';
		$RTN .= '<'.'?php'."\n";
		$RTN .= '	# childlist'."\n";
		$RTN .= '	print $theme->mk_childlist(';
		if( strlen( $option_str ) ){
			$RTN .= text::data2text( $option_str );
		}
		$RTN .= ');'."\n";
		$RTN .= '?'.'>'."\n";
		return	$RTN;
	}


	#--------------------------------------
	#	同階層リンクの一覧(broslist)の処理
	#	Pickles Framework 0.5.0 追加

	#	判定
	function check_broslist( $line ){
		#	Pickles Framework 0.3.9 追加
		$line = trim( $line );
		if( preg_match( '/^'.preg_quote( '>>>' , '/' ).'(?: |\t|　)*broslist(?: |\t|　)*(?:\|(.*))?$/is' , $line , $result ) ){
			#	ex. >>>broslist|pid=XXX
			return	'>>>broslist|'.trim($result[1])."\n";
		}
		return	false;
	}
	#	ソース作成
	function mksrc_broslist( $SRC ){
		#	Pickles Framework 0.3.9 追加

		$SRC = preg_replace( '/^(.*?)(?:\r\n|\r|\n)/' , "$1" , $SRC );
			#	1行目だけ抜き出し

		$allow_ct = '';
			#	改ページするか否かの条件式（CASE文）

		$option_str = preg_replace( '/^'.preg_quote('>>>broslist').'\|(.*)$/is' , "$1" , $SRC );
			#	オプション部分を取り出す

		$RTN = '';
		$RTN .= '<'.'?php'."\n";
		$RTN .= '	# broslist'."\n";
		$RTN .= '	print $theme->mk_broslist(';
		if( strlen( $option_str ) ){
			$RTN .= text::data2text( $option_str );
		}
		$RTN .= ');'."\n";
		$RTN .= '?'.'>'."\n";
		return	$RTN;
	}


	#--------------------------------------
	#	ifmoduleの処理

	#	判定
	function check_ifmodule( $line ){
		if( preg_match( '/^IFMODULE>(.*)$/i' , $line , $result ) ){
			return	trim($result[1]);
		}
		return	false;
	}
	#	ソース作成
	function mksrc_ifmodule( $SRC ){
		if( !strlen( $SRC ) ){
			return	'';
		}
		$arglist = explode( '|' , $SRC );
		$module_name = array_shift( &$arglist );
		$module_name = trim($module_name);
		if(!strlen($module_name)){
			return	'';
		}

		foreach( array_keys( $arglist ) as $num ){
			$arglist[$num] = text::data2text( trim($arglist[$num]) );
		}

		$RTN = '';
		$RTN .= '<'.'?php ';
		$RTN .= 'print $theme->ifmodule(';
		$RTN .= ''.text::data2text($module_name).'';
		$RTN .= ','.implode(',',$arglist).'';
		$RTN .= ');';
		$RTN .= ' ?'.'>'."\n";

		return	$RTN;
	}


	#--------------------------------------
	#	h2の処理

	#	判定
	function check_h2( $line ){
		if( preg_match( '/^【(.*)】$/' , $line , $result ) ){
			#	【見出し】
			return	trim($result[1]);
		}elseif( preg_match( '/^\[\-\-(.*)\-\-\]$/' , $line , $result ) ){
			#	[--見出し--]
			return	trim($result[1]);
		}
		return	false;
	}
	#	ソース作成
	function mksrc_h2( $SRC ){
		$hx = 2;
		$SRC = 'htmlspecialchars( '.text::data2text( $SRC ).' )';

		#	Pickles Framework 0.4.0 : text::data2text() が正しく動作していなかった不具合を修正。
		$RTN = '';
		while( preg_match( '/^(.*?)'.preg_quote('[[','/').'(.*?)(?:\|(.*?))?'.preg_quote(']]','/').'(.*)$/si' , $SRC , $preg_matched ) ){
			$RTN  .= $preg_matched[1];
			$href  = eval( 'return \''.$preg_matched[2].'\';' );
			$label = eval( 'return \''.$preg_matched[3].'\';' );
			$SRC   = $preg_matched[4];

			$RTN .= '\' ).'.'$theme->mk_link( '.text::data2text($href).' , array(\'label\'=>'.text::data2text($label).') )'.'.htmlspecialchars( \'';
		}
		$RTN .= $SRC;

		$RTN = '<'.'?php print $theme->mk_hx( '.$RTN.' , '.intval($hx).' , array(\'allow_html\'=>true) ); ?'.'>'."\n";
		return	$RTN;
	}

	#--------------------------------------
	#	h3の処理

	#	判定
	function check_h3( $line ){
		if( preg_match( '/^■(.*)$/' , $line , $result ) ){
			#	■見出し
			return	trim($result[1]);
		}
		return	false;
	}
	#	ソース作成
	function mksrc_h3( $SRC ){
		$hx = 3;
		$SRC = 'htmlspecialchars( '.text::data2text( $SRC ).' )';

		#	Pickles Framework 0.4.0 : text::data2text() が正しく動作していなかった不具合を修正。
		$RTN = '';
		while( preg_match( '/^(.*?)'.preg_quote('[[','/').'(.*?)(?:\|(.*?))?'.preg_quote(']]','/').'(.*)$/si' , $SRC , $preg_matched ) ){
			$RTN  .= $preg_matched[1];
			$href  = eval( 'return \''.$preg_matched[2].'\';' );
			$label = eval( 'return \''.$preg_matched[3].'\';' );
			$SRC   = $preg_matched[4];

			$RTN .= '\' ).'.'$theme->mk_link( '.text::data2text($href).' , array(\'label\'=>'.text::data2text($label).') )'.'.htmlspecialchars( \'';
		}
		$RTN .= $SRC;

		$RTN = '<'.'?php print $theme->mk_hx( '.$RTN.' , '.intval($hx).' , array(\'allow_html\'=>true) ); ?'.'>'."\n";
		return	$RTN;
	}

	#--------------------------------------
	#	h4の処理

	#	判定
	function check_h4( $line ){
		if( preg_match( '/^□(.*)$/' , $line , $result ) ){
			#	□見出し
			return	trim($result[1]);
		}
		return	false;
	}
	#	ソース作成
	function mksrc_h4( $SRC ){
		$hx = 4;
		$SRC = 'htmlspecialchars( '.text::data2text( $SRC ).' )';

		#	Pickles Framework 0.4.0 : text::data2text() が正しく動作していなかった不具合を修正。
		$RTN = '';
		while( preg_match( '/^(.*?)'.preg_quote('[[','/').'(.*?)(?:\|(.*?))?'.preg_quote(']]','/').'(.*)$/si' , $SRC , $preg_matched ) ){
			$RTN  .= $preg_matched[1];
			$href  = eval( 'return \''.$preg_matched[2].'\';' );
			$label = eval( 'return \''.$preg_matched[3].'\';' );
			$SRC   = $preg_matched[4];

			$RTN .= '\' ).'.'$theme->mk_link( '.text::data2text($href).' , array(\'label\'=>'.text::data2text($label).') )'.'.htmlspecialchars( \'';
		}
		$RTN .= $SRC;

		$RTN = '<'.'?php print $theme->mk_hx( '.$RTN.' , '.intval($hx).' , array(\'allow_html\'=>true) ); ?'.'>'."\n";
		return	$RTN;
	}

	#--------------------------------------
	#	blockquoteの処理

	#	判定
	function check_blockquote( $line ){
		if( preg_match( '/^>(.*)$/' , $line , $result ) ){
			return	trim($result[1])."\n";
		}
		return	false;
	}
	#	ソース作成
	function mksrc_blockquote( $SRC ){
		$RTN = $SRC;
		$RTN = htmlspecialchars( $RTN );
		$RTN = preg_replace( '/\r\n|\r|\n/' , '<br />'."\n" , $RTN );
		$RTN = $this->common_link( $RTN );
		$RTN = '<blockquote><div class="ttr">'.$RTN.'</div></blockquote>'."\n";
		return	$RTN;
	}


	#--------------------------------------
	#	sourcecodeの処理

	#	判定
	function check_sourcecode( $line ){
		if( preg_match( '/^CODE> ?(.*)$/' , $line , $result ) ){
			return	$result[1]."\n";
		}
		return	false;
	}
	#	ソース作成
	function mksrc_sourcecode( $SRC ){
		$RTN = $SRC;
		$RTN = htmlspecialchars( $RTN );
		$RTN = '<blockquote class="sourcecode"><pre class="ttr">'.$RTN.'</pre></blockquote>'."\n";
		return	$RTN;
	}


	#--------------------------------------
	#	リストの処理

	#	判定
	function check_li( $line ){
		if( preg_match( '/^・(.*)$/' , $line , $result ) ){
			return	trim($result[1])."\n";
		}
		return	false;
	}
	#	ソース作成
	function mksrc_li( $SRC ){
		$list = preg_split( '/\r\n|\r|\n/' , $SRC );

		$RTN = '';
		$RTN .= '<ul>'."\n";
		foreach( $list as $LINE ){
			if( !strlen($LINE) ){continue;}
			$LINE = htmlspecialchars( $LINE );
			$LINE = $this->common_strong( $LINE );
			$LINE = $this->common_code( $LINE );
			$LINE = $this->common_q( $LINE );
			$LINE = $this->common_link( $LINE );
			$LINE = $this->common_image( $LINE );
			$RTN .= '	<li class="ttr">'.$LINE.'</li>'."\n";
		}
		$RTN .= '</ul>'."\n";

		return	$RTN;
	}


	#--------------------------------------
	#	注釈リストの処理(Pickles Framework 0.5.4 追加)

	#	判定
	function check_li_annotation( $line ){
		if( preg_match( '/^※(.*)$/' , $line , $result ) ){
			return	trim($result[1])."\n";
		}
		return	false;
	}
	#	ソース作成
	function mksrc_li_annotation( $SRC ){
		$list = preg_split( '/\r\n|\r|\n/' , $SRC );

		$RTN = '';
		$RTN .= '<ul class="annotation">'."\n";
		foreach( $list as $LINE ){
			if( !strlen($LINE) ){continue;}
			$LINE = htmlspecialchars( $LINE );
			$LINE = $this->common_strong( $LINE );
			$LINE = $this->common_code( $LINE );
			$LINE = $this->common_q( $LINE );
			$LINE = $this->common_link( $LINE );
			$LINE = $this->common_image( $LINE );
			$RTN .= '	<li class="ttr">※'.$LINE.'</li>'."\n";
		}
		$RTN .= '</ul>'."\n";

		return	$RTN;
	}


	#--------------------------------------
	#	数字付きリストの処理

	#	判定
	function check_ol( $line ){
		if( preg_match( '/^@(.*)$/' , $line , $result ) ){
			return	trim($result[1])."\n";
		}
		return	false;
	}
	#	ソース作成
	function mksrc_ol( $SRC ){
		$list = preg_split( '/\r\n|\r|\n/' , $SRC );

		$RTN = '';
		$RTN .= '<ol>'."\n";
		foreach( $list as $LINE ){
			if( !strlen($LINE) ){continue;}
			$LINE = htmlspecialchars( $LINE );
			$LINE = $this->common_strong( $LINE );
			$LINE = $this->common_code( $LINE );
			$LINE = $this->common_q( $LINE );
			$LINE = $this->common_link( $LINE );
			$LINE = $this->common_image( $LINE );
			$RTN .= '	<li class="ttr">'.$LINE.'</li>'."\n";
		}
		$RTN .= '</ol>'."\n";

		return	$RTN;
	}

	#--------------------------------------
	#	定義リストの処理(Pickles Framework 0.3.8 追加)

	#	判定
	function check_dl( $line ){
		#	Pickles Framework 0.3.8 追加
		if( preg_match( '/^\:(.*?)\|(.*)$/' , $line , $result ) ){
			return	':'.trim($result[1]).'|'.trim($result[2])."\n";
		}
		return	false;
	}
	#	ソース作成
	function mksrc_dl( $SRC ){
		#	Pickles Framework 0.3.8 追加
		$list = preg_split( '/\r\n|\r|\n/' , $SRC );

		$RTN = '';
		$RTN .= '<dl>'."\n";
		foreach( $list as $LINE ){
			if( !strlen( $LINE ) ){ continue; }
			preg_match( '/^\:(.*)$/' , $LINE , $result );
			$DT = '';
			$DD = $result[1];
			while( preg_match( '/^(.*?)(\[\[|\[img\:|\[image\:|\[画像\:|\|)(.*)$/' , $DD , $result ) ){
				$DT .= $result[1];
				$DD = $result[3];
				switch( $result[2] ){
					case '[[';
						preg_match( '/^(.*?\]\])(.*)$/' , $DD , $result );
						$DT .= '[['.$result[1];
						$DD = $result[2];
						break;
					case '[img:';
					case '[image:';
					case '[画像:';
						preg_match( '/^(.*?\])(.*)$/' , $DD , $result );
						$DT .= '[img:'.$result[1];
						$DD = $result[2];
						break;
					case '|';
						break 2;
				}
			}
//			preg_match( '/^\:(.*?)\|(.*)$/' , $LINE , $result );

			$DT = htmlspecialchars( $DT );
			$DT = $this->common_strong( $DT );
			$DT = $this->common_code( $DT );
			$DT = $this->common_q( $DT );
			$DT = $this->common_link( $DT );
			$DT = $this->common_image( $DT );
			$RTN .= '	<dt class="ttr">'.$DT.'</dt>'."\n";//Pickles Framework 0.5.9 : DT内でもインライン文法が使えるようになった。

			$DD = htmlspecialchars( $DD );
			$DD = $this->common_strong( $DD );
			$DD = $this->common_code( $DD );
			$DD = $this->common_q( $DD );
			$DD = $this->common_link( $DD );
			$DD = $this->common_image( $DD );
			$RTN .= '		<dd class="ttr">'.$DD.'</dd>'."\n";
		}
		$RTN .= '</dl>'."\n";

		return	$RTN;
	}

	#--------------------------------------
	#	<html>

	#	判定
	function check_html( $line ){
//		$line = trim($line);
		if( $this->typeLock != 'html' && preg_match( '/^<html>$/i' , $line , $result ) ){
			$this->typeLock = 'html';
			return	'<!-- Start of HTML mode -->'."\n";
		}
		if( $this->typeLock == 'html' && preg_match( '/^<\/html>$/i' , ltrim( $line ) , $result ) ){
			$this->typeLock = null;
			return	'<!-- End of HTML mode -->'."\n";
		}
		if( $this->typeLock == 'html' ){
			return	$line."\n";
		}
		return	false;
	}
	#	ソース作成
	function mksrc_html( $SRC ){
		$SRC = $this->publish( $this->html_parse( $SRC ) );
		return	$SRC;
	}



	#--------------------------------------
	#	テーブル

	#	判定
	function check_table( $line ){
		if( $this->typeLock != 'table' && preg_match( '/^TABLE>$/i' , trim( $line ) , $result ) ){
			$this->typeLock = 'table';
			return	'TABLE_START'."\n";
		}
		if( $this->typeLock == 'table' && preg_match( '/^<TABLE$/i' , trim( $line ) , $result ) ){
			$this->typeLock = null;
			return	'TABLE_END';
		}
		if( $this->typeLock == 'table' ){
			return	trim( $line )."\n";
		}
		return	false;
	}
	#	ソース作成
	function mksrc_table( $SRC ){
		$list = preg_split( '/\r\n|\r|\n/' , $SRC );

		$contentList = array();

		$maxCellCount = 0;
		$TYPE = null;

		$TROWS = array();

		$cellLock = null;
		$num = -1;
		foreach( $list as $LINE ){
			$num ++;

			if( $cellLock ){
				#--------------------------------------
				#	前のセルの続きだったら。

				$CELLMEMO = trim($LINE);
				$CELLMEMO = preg_replace( '/\|$/' , '' , $CELLMEMO );
				$ROWLIST = split( '\|' , $CELLMEMO );
				for( $i = 0; !is_null( $ROWLIST[$i] ); $i++ ){
					$CELL = $ROWLIST[$i];
					if( !$i ){
						$TROWS[count($TROWS)-1] .= "\n".$CELL;
						continue;
					}
					array_push( $TROWS , trim( $CELL ) );
				}

				if( !preg_match( '/^(?:TABLE_START|TABLE_END|CAPTION\:|THEAD\:|TFOOT\:|\|)/i' , $list[$num+1] ) ){
					#	次の行が続きっぽかったら、ここまでで終了して、次へ継続。
					$cellLock = true;
					continue;
				}

				if( $maxCellCount < count($TROWS) ){
					$maxCellCount = count($TROWS);
				}

				array_push( $contentList , array( 'src'=>$TROWS , 'type'=>strtolower($TYPE) ) );
				$cellLock = null;
				continue;

				#	/ 前のセルの続きだったら。
				#--------------------------------------
			}

			$LINE = trim($LINE);
			if( $LINE == 'TABLE_START' ){
				array_push( $contentList , array( 'src'=>'<table class="deftable">' , 'type'=>'static' ) );
				continue;
			}
			if( $LINE == 'TABLE_END' ){
				array_push( $contentList , array( 'src'=>'</table>' , 'type'=>'static' ) );
				$cellLock = null;
				break;
			}
			if( preg_match( '/^CAPTION:(.*)$/i' , $LINE , $result ) ){
				array_push( $contentList , array( 'src'=>$result[1] , 'type'=>'caption' ) );
				continue;
			}
			if( preg_match( '/^(?:(THEAD|TFOOT):)?(\|.*)$/i' , $LINE , $result ) ){
				$TYPE = $result[1];
				if( !strlen( $TYPE ) ){
					$TYPE = 'row';
				}
				$CELLMEMO = trim($result[2]);
				$CELLMEMO = preg_replace( '/^\|/' , '' , $CELLMEMO );
				$CELLMEMO = preg_replace( '/\|$/' , '' , $CELLMEMO );

				$TROWS = array();//初期化
				$ROWLIST = split( '\|' , $CELLMEMO );
				for( $i = 0; !is_null( $ROWLIST[$i] ); $i++ ){
					$CELL = $ROWLIST[$i];
					array_push( $TROWS , trim( $CELL ) );
				}

				if( !preg_match( '/^(?:TABLE_START|TABLE_END|CAPTION\:|THEAD\:|TFOOT\:|\|)/i' , $list[$num+1] ) ){
					#	次の行が続きっぽかったら、ここまでで終了して、次へ継続。
					$cellLock = true;
					continue;
				}

				if( $maxCellCount < count($TROWS) ){
					$maxCellCount = count($TROWS);
				}

				array_push( $contentList , array( 'src'=>$TROWS , 'type'=>strtolower($TYPE) ) );
				continue;

			}


		}

		#--------------------------------------
		#	パースしたテーブルを描画
		$RTN = '';
		foreach( $contentList as $Line ){

			if( $Line['type'] == 'static' ){
				$RTN .= $Line['src']."\n";
				continue;

			}elseif( $Line['type'] == 'caption' ){
				$RTN .= '<caption>'.htmlspecialchars($Line['src']).'</caption>'."\n";
				continue;

			}elseif( $Line['type'] == 'thead' || $Line['type'] == 'tfoot' || $Line['type'] == 'row' ){
				$CELLMEMO = '';
				$CELLTAGNAME = 'td';
				if( $Line['type'] == 'thead' ){
					$CELLTAGNAME = 'th';
				}elseif( $Line['type'] == 'tfoot' ){
					$CELLTAGNAME = 'th';
				}

				for( $i = 0; $i < $maxCellCount; $i ++ ){
					$MEMO = $Line['src'][$i];
					$MEMO = htmlspecialchars( $MEMO );
					$MEMO = preg_replace( '/\r\n|\r|\n/' , '<br />' , $MEMO );
					$MEMO = $this->common_strong( $MEMO );
					$MEMO = $this->common_code( $MEMO );
					$MEMO = $this->common_q( $MEMO );
					$MEMO = $this->common_link( $MEMO );
					$MEMO = $this->common_image( $MEMO );
					$CELLMEMO .= '		<'.$CELLTAGNAME.'>'.$MEMO.'</'.$CELLTAGNAME.'>'."\n";
				}

				if( $Line['type'] == 'thead' ){
					$RTN .= '	<thead>'."\n";
					$RTN .= '	<tr>'."\n";
					$RTN .= $CELLMEMO;
					$RTN .= '	</tr>'."\n";
					$RTN .= '	</thead>'."\n";
				}elseif( $Line['type'] == 'tfoot' ){
					$RTN .= '	<tfoot>'."\n";
					$RTN .= '	<tr>'."\n";
					$RTN .= $CELLMEMO;
					$RTN .= '	</tr>'."\n";
					$RTN .= '	</tfoot>'."\n";
				}else{
					$RTN .= '	<tr>'."\n";
					$RTN .= $CELLMEMO;
					$RTN .= '	</tr>'."\n";
				}

			}

		}

		return	$RTN;
	}







	#==================================================================================================================
	#	その他、インラインの共通変換処理
	#	※引数 $SRC には、HTMLエンティティ変換された文字列を受け取ります。
	#	※引数 $SRC には、改行 "<br />\n" が含まれる可能性があります。


	#--------------------------------------
	#	リンクの変換
	function common_link( $SRC ){
		#	Pickles Framework 0.4.0 : text::data2text() が正しく動作していなかった不具合を修正。
		$RTN = '';
		while( preg_match( '/^(.*?)'.preg_quote('[[','/').'(.*?)(?:\|(.*?))?'.preg_quote(']]','/').'(.*)$/si' , $SRC , $preg_matched ) ){
			$RTN  .= $preg_matched[1];
			$href  = text::htmlspecialchars_decode( $preg_matched[2] );
			$label = text::htmlspecialchars_decode( $preg_matched[3] );
			$SRC   = $preg_matched[4];

			$RTN .= '<'.'?php print $theme->mk_link( '.$this->data2text_apply_vars( $href ).' , array(\'label\'=>'.text::data2text( $label ).') ); ?'.'>';
				#	PxFW 0.6.6 : $href に 変数を埋め込めるようにした。
		}
		$RTN .= $SRC;
		return	$RTN;
	}

	#--------------------------------------
	#	強調（<strong>タグ）の変換
	function common_strong( $SRC ){
		$SRC = preg_replace( '/'.preg_quote(htmlspecialchars('<strong>'),'/').'(.*?)'.preg_quote(htmlspecialchars('</strong>'),'/').'/i' , '<strong>'."$1".'</strong>' , $SRC );
		return	$SRC;
	}

	#--------------------------------------
	#	インラインコード（<code>タグ）の変換
	function common_code( $SRC ){
		$SRC = preg_replace( '/'.preg_quote(htmlspecialchars('<code>'),'/').'(.*?)'.preg_quote(htmlspecialchars('</code>'),'/').'/i' , '<code>'."$1".'</code>' , $SRC );
		return	$SRC;
	}

	#--------------------------------------
	#	インライン引用（<q>タグ）の変換
	function common_q( $SRC ){
		#	Pickles Framework 0.3.8 追加
		$SRC = preg_replace( '/'.preg_quote(htmlspecialchars('<q>'),'/').'(.*?)'.preg_quote(htmlspecialchars('</q>'),'/').'/i' , '<q>'."$1".'</q>' , $SRC );
		return	$SRC;
	}

	#--------------------------------------
	#	画像の変換
	function common_image( $SRC ){
		#	Pickles Framework 0.4.0 : text::data2text() が正しく動作していなかった不具合を修正。
		$RTN = '';
		while( preg_match( '/^(.*?)'.preg_quote('[','/').'(?:画像|image|img)'.preg_quote(':','/').'(.*?)(?:\|(.*?))?'.preg_quote(']','/').'(.*)$/si' , $SRC , $preg_matched ) ){
			$RTN   .= $preg_matched[1];
			$imgsrc = text::htmlspecialchars_decode( $preg_matched[2] );
			$alt    = text::htmlspecialchars_decode( $preg_matched[3] );
			$SRC    = $preg_matched[4];

			$RTN .= '<'.'?php ';
			$RTN .= 'print $theme->mk_img( '.$this->data2text_apply_vars( $imgsrc ).' , array(\'alt\'=>'.text::data2text( $alt ).'';
				#	PxFW 0.6.6 : $imgsrc に 変数を埋め込めるようにした。
			if( $this->contents_config['image.clickable'] ){
				#	PxFW 0.5.4 : 追加 (挙動を設定できるようになった)
				#	PxFW 0.6.6 : $imgsrc に 変数を埋め込めるようにした。
				$RTN .= ',\'hrefresource\'=>'.$this->data2text_apply_vars( $imgsrc ).'';
			}
			$RTN .= ') );';
			$RTN .= ' ?'.'>';
		}
		$RTN .= $SRC;
		return	$RTN;
	}

}

?>