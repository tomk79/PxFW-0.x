<?php

#	Copyright (C)Tomoya Koyanagi.
#	LastUpdate : 9:28 2011/02/16

#******************************************************************************************************************
#	サイト/ページに関するオブジェクトクラス
class base_lib_site{

	var $conf;
	var $dbh;
	var $errors;
	var $req;//PxFW 0.6.4 追加
	var $files_sitemap_src;

	var $localconf_sitemap_csv_encoding = 'UTF-8';

	var $sitemap = array();	#	←サイトマップ配列を格納

	#--------------------------------------
	#	サイトの基本情報
	#		これらの値は、
	#		$confに設定がある場合、
	#		$confの値が優先して上書きされます。
	var $title = '';
	var $copyright = array();
	var $url = '';
	#	/ サイトの基本情報
	#--------------------------------------

	var $sitemap_path_cache = array();//2:02 2009/01/24 Pickles Framework 0.5.7 導入。
		#	大量のページを登録した際に、
		#	get_children() や get_bros() での階層の抽出に時間が掛かるので、
		#	階層ごとに記憶してみることにした。(2:04 2009/01/24)

	function setup( &$conf , &$dbh , &$errors , &$req ){

		/* $confに値があった場合は、そちらを優先 */
		if( strlen( $conf->info_sitetitle ) ){
			$this->title = &$conf->info_sitetitle;
		}
		if( strlen( $conf->url_sitetop ) ){
			$this->url = &$conf->url_sitetop;
		}
		if( !is_null( $conf->info_copyright['copy'] ) ){
			$this->copyright['copy'] = &$conf->info_copyright['copy'];
		}
		if( !is_null( $conf->info_copyright['since_from'] ) ){
			$this->copyright['since_from'] = &$conf->info_copyright['since_from'];
		}
		if( !is_null( $conf->info_copyright['since_to'] ) ){
			$this->copyright['since_to'] = &$conf->info_copyright['since_to'];
		}

		$this->conf = &$conf;
		$this->dbh = &$dbh;
		$this->errors = &$errors;
		$this->req = &$req;//PxFW 0.6.4 $reqを保持するようになった。

		/* サイトマップが存在するパス */
		$this->files_sitemap_src = array();

		$this->setup_subaction();

	}

	#----------------------------------------------------------------------------
	#	セットアップ拡張用メソッド
	function setup_subaction(){
	}

	#--------------------------------------
	#	サイトのタイトルをセット
	function set_title( $title = null ){ return $this->settitle( $title ); }//←PxFW 0.6.3 追加：エイリアス
	function settitle( $title = null ){
		if( !strlen( $title ) ){ return	false; }
		$this->title = $title;
		return	true;
	}
	#--------------------------------------
	#	サイトのタイトルを取得
	function get_title(){ return $this->gettitle(); }//←PxFW 0.6.3 追加：エイリアス
	function gettitle(){
		if( strlen( $this->title ) ){
			return	$this->title;
		}
		return	$this->conf->info_sitetitle;
	}

	#--------------------------------------
	#	サイトの著作権情報をセット
	function set_copyright( $copyright , $since_to = null , $since_from = null ){ return $this->setcopyright( $copyright , $since_to , $since_from ); }//←PxFW 0.6.3 追加：エイリアス
	function setcopyright( $copyright , $since_to = null , $since_from = null ){
		$this->copyright['copy'] = $copyright;
		$this->copyright['since_from'] = $since_from;
		$this->copyright['since_to'] = $since_to;
		return	true;
	}
	#--------------------------------------
	#	サイトの著作権情報を表す文字列を取得
	function get_copyright( $type = null , $CT = 'PC' ){ return $this->getcopyright( $type , $CT ); }//←PxFW 0.6.3 追加：エイリアス
	function getcopyright( $type = null , $CT = 'PC' ){
		$type = strtolower( $type );
		$CT = strtoupper( $CT );

		if( $type == 'copy' ){
			return	htmlspecialchars( $this->copyright['copy'].'' );
		}
		if( $type == 'since_from' ){
			if( $this->copyright['since_from'] == 'now' ){
				#	Pickles Framework 0.5.0 追加の処理
				return	date( 'Y' , $this->conf->time );
			}
			return	htmlspecialchars( $this->copyright['since_from'].'' );
		}
		if( $type == 'since_to' ){
			if( $this->copyright['since_to'] == 'now' ){
				#	Pickles Framework 0.5.0 追加の処理
				return	date( 'Y' , $this->conf->time );
			}
			return	htmlspecialchars( $this->copyright['since_to'].'' );
		}

		if( $CT == 'PC' || $type == 'full' ){
			#	PCの時。または、full指定の時。
			$RTN = '';
			$RTN .= 'Copyright &copy;';
			$since_form = htmlspecialchars( $this->copyright['since_from'] );
			if( $since_form == 'now' ){
				$since_form = date( 'Y' , $this->conf->time );
			}
			$since_to = $this->copyright['since_to'];
			if( $since_to == 'now' ){
				$since_to = date( 'Y' , $this->conf->time );
			}

			if( $since_form && $since_to && ( $since_form != $since_to ) ){
				$RTN .= ''.htmlspecialchars( $since_form ).'-'.htmlspecialchars( $since_to ).' ';
			}elseif($since_to){
				$RTN .= ''.htmlspecialchars( $since_to ).' ';
			}
			$RTN .= ''.htmlspecialchars( $this->copyright['copy'] ).', All rights reserved.';
			return	$RTN;
		}
		if( $CT == 'PDA' ){
			#	PDA標準。
			$RTN = '';
			$RTN .= '&copy;';
			$since_form = htmlspecialchars( $this->copyright['since_from'] );
			if( $since_form == 'now' ){
				$since_form = date( 'Y' , $this->conf->time );
			}
			$since_to = $this->copyright['since_to'];
			if( $since_to == 'now' ){
				$since_to = date( 'Y' , $this->conf->time );
			}

			if( $since_form && $since_to && ( $since_form != $since_to ) ){
				$RTN .= ''.htmlspecialchars( $since_form ).'-'.htmlspecialchars( $since_to ).' ';
			}elseif($since_to){
				$RTN .= ''.htmlspecialchars( $since_to ).' ';
			}
			$RTN .= ''.htmlspecialchars( $this->copyright['copy'] ).'';
			return	$RTN;
		}else{
			#	その他ケータイなど。
			return	'(C)'.htmlspecialchars( $this->copyright['copy'] );
		}
		return	true;
	}

	#--------------------------------------
	#	サイトのURLをセット/ゲット
	function set_url( $URL ){ return $this->seturl( $URL ); }//←PxFW 0.6.3 追加：エイリアス
	function seturl( $URL ){
		if( !strlen( $URL ) ){ return false; }
		$this->url = $URL;
		return	true;
	}
	function get_url(){ return $this->geturl(); }//←PxFW 0.6.3 追加：エイリアス
	function geturl(){
		if( strlen( $this->url ) ){
			return	$this->url;
		}
		return	$this->conf->url_sitetop;
	}

	#----------------------------------------------------------------------------
	#	サイトマップファイルを追加する
#	function putsitemapfile( $path ){
#		#	Pickles Framework 0.4.0 廃止
#		#	Pickles Framework 0.5.7 削除
#		return	false;
#	}

	#----------------------------------------------------------------------------
	#	サイトマップをセットする
	function set_sitemap( $lang = 'ja' , $options = array() ){ return $this->setsitemap( $lang , $options ); }//←PxFW 0.6.3 追加：エイリアス
	function setsitemap( $lang = 'ja' , $options = array() ){
		if( !strlen( $this->localconf_sitemap_csv_encoding ) ){ $this->localconf_sitemap_csv_encoding = 'utf-8'; }
		if( !is_array( $this->sitemap ) ){ $this->sitemap = array(); }

		#--------------------------------------
		#	サイトマップCSVの一覧を取得
		#	Pickles Framework 0.4.0 以降、この処理は自分でするようになりました。
		#	それ以前の古いバージョンでは、putsitemapfile() メソッドを通じて
		#	$conductor にセットしてもらっていました。
		#	putsitemapfile()メソッドは、0.4.0 で廃止されました。
		$this->files_sitemap_src = array();//一旦リセット
		$mapfilelist = $this->dbh->getfilelist( $this->conf->path_sitemap_dir );
		foreach( $mapfilelist as $Line ){
			if( $Line == '.' || $Line == '..' ){ continue; }
			if( !preg_match( '/\.csv$/' , $Line ) ){ continue; }
			array_push( $this->files_sitemap_src , $this->conf->path_sitemap_dir.'/'.$Line );
		}
		unset( $Line );
		#	/ サイトマップCSVの一覧を取得
		#--------------------------------------

		#--------------------------------------
		#	使えるキャッシュがあるか確認
		if( $this->is_sitemapcache( $lang , $options ) ){
			#	サイトマップキャッシュをロードすべきと判断された場合。
			$this->sitemap = @include( $this->conf->path_cache_dir.'/sitemap/sitemap_'.$lang.'.cache' );
			$this->sitemap_path_cache = @include( $this->conf->path_cache_dir.'/sitemap/breadcrumb_'.$lang.'.cache' );
			return $this->sitemap;
		}


		#--------------------------------------
		#	↓↓CSVからサイトマップを起こす↓↓

		$SiteMap_Definition = $this->get_sitemap_definition();
		if( !is_array( $SiteMap_Definition ) ){
			$this->errors->error_log( '$site->get_sitemap_definition() が配列以外の値を返しました。('.gettype($SiteMap_Definition).')' , __FILE__ , __LINE__ );
			$SiteMap_Definition = array();
		}

		#--------------------------------------
		#	公開フラグの番号を調べる
		$counter = 0;
		$num_public_flg = 0;
		foreach( array_keys( $SiteMap_Definition ) as $Line ){
			if( $Line == 'public_flg' ){ $num_public_flg = $counter; break; }
			$counter++;
		}
		#	/ 公開フラグの番号を調べる
		#--------------------------------------

		#:::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
		#	サイトマップをセット
		$done_pid_list = array();
		foreach( $this->files_sitemap_src as $filepath ){
			if( !@file_exists( $filepath ) ){ $this->errors->error_log( 'サイトマップがありません。' , __FILE__ , __LINE__ ); return false; }
			$this->dbh->fclose( $filepath );

			$res_csvfile = @fopen( $filepath , 'r' );//サイトマップの容量が大きかった場合を想定し、$dbh->read_csv() の使用をやめた。Pickles Framework 0.4.9
			while( $SMMEMO = fgetcsv( $res_csvfile , 10000 , ',' , '"' ) ){
				set_time_limit(0);//←Pickles Framework 0.5.7
				$SMMEMO = text::convert_encoding( $SMMEMO , mb_internal_encoding() , $this->localconf_sitemap_csv_encoding.',UTF-8,SJIS-win,eucJP-win,SJIS,EUC-JP' );

				#	PIDに使用できない文字列が含まれていたらスキップ
				#	(ページIDが ゼロ番目 の要素であることは保障されたお約束)
				if( preg_match( '/[^0-9a-zA-Z\.\_\-]/' , $SMMEMO[0] ) && strlen( $SMMEMO[0] ) ){ continue; }
				#	公開フラグが立っていなければ、スキップ
				if( $num_public_flg > 0 && !$SMMEMO[$num_public_flg] && !$this->conf->show_invisiblepage ){ continue; }
				#	すでに処理済ならばスキップ
				if( !is_null( $done_pid_list[$SMMEMO[0]] ) ){
					//	PxFW 0.7.0 追加
					//	ページIDの重複アラート
					$this->errors->error_log( 'サイトマップエラー：ページID['.$SMMEMO[0].']は重複しています。' , __FILE__ , __LINE__ );
					if( $this->conf->debug_mode ){
						print '<div style="background-color:#ff0000; color:#ffffff; padding:5px; border-bottom:1px solid #ff6666;">サイトマップエラー：ページID['.htmlspecialchars($SMMEMO[0]).']は重複しています。</div>'."\n";
					}
					continue;
				}

				$i = 0;
				foreach( $SiteMap_Definition as $DefineKey=>$DefineVal ){
					$this->setpageinfo( $SMMEMO[0] , $DefineKey , text::convert_encoding( $SMMEMO[$i] ) );
					$i++;
				}

				$this->setpageinfo( $SMMEMO[0] , 'csv_filepath' , preg_replace( '/^'.preg_quote( $this->conf->path_sitemap_dir , '/' ).'(?:\/)*'.'/' , '' , $filepath ) );//固定値

				if( is_array($this->sitemap[$SMMEMO[0]]) ){
					ksort($this->sitemap[$SMMEMO[0]]);//Pickles Framework 0.5.7 : 追加
				}

				#	処理済PIDリストに追加
				$done_pid_list[$SMMEMO[0]] = true;

			}
			set_time_limit(60);//←Pickles Framework 0.5.7
			fclose( $res_csvfile );

		}
		#	/サイトマップをセット
		#:::::::::::::::::::::::::::::::::::::::::::::::::::::::::::

		#	固定でセットしたいページ情報をハードコーディングする。
		$this->setsitemap_hardcoding( $SiteMap_Definition );
		#	言語パックが定義されていれば、反映させる。
		$this->setsitemap_languagepack( $lang );
		#	ページ属性が定義されていれば、反映させる。
		//$this->setsitemap_attributes();//PxFW 0.6.9 廃止

		#--------------------------------------
		#	強制変換
		if( is_array( $this->sitemap['logout'] ) ){
			$this->sitemap['logout']['closed'] = '';	#	ログアウト画面はクローズにされていてはだめ
		}

		#	今回作ったサイトマップをキャッシュに保存
		$this->save_cache_sitemap( null , $lang , null );//Pickles Framework 0.5.7 : サイトマップ配列は渡さない(null)ことにした。メモリー節約。
//		$this->save_cache_sitemap( $this->sitemap , $lang );
		return $this->sitemap;
	}

	#----------------------------------------------------------------------------
	#	サイトマップ定義のカラム名からインデックス番号を返す
	#	19:19 2008/02/15 Pickles Framework 0.2.6 追加
	function get_sitemap_definition_index_by_key( $keystr ){
		#	$keystr は文字列です。
		static $defIndex = null;
		if( is_array( $defIndex ) ){
			#	すでに記憶していたら、それを返す。
			if( is_int( $defIndex[$keystr] ) ){
				return	$defIndex[$keystr];
			}
			return	$keystr;
		}

		#--------------------------------------
		#	static の変数 $defIndex に、
		#	一覧をキャッシュする
		$defIndex = array();
		$SiteMap_Definition = array_keys( $this->get_sitemap_definition() );

		$i = 0;
		foreach( $SiteMap_Definition as $defLine ){
			$defIndex[$defLine] = intval( $i );
			$i ++;
		}

		#	キャッシュしたら、もう一回実行
		return	$this->get_sitemap_definition_index_by_key( $keystr );
	}

	#----------------------------------------------------------------------------
	#	サイトマップファイルの定義書(配列)を返す
	#	PxFW 0.7.0 getsitemap_definition() から改名
	#	PxFW 0.7.0 で大幅に並べかえた。
	function get_sitemap_definition(){
		#	制約：$RTN[0]は、必ずページID 'id' でなければなりません。
		$preg_of_pid = '/^[a-zA-Z0-9\_\.\-]*$/';
			//↑Pickles Framework 0.5.2 : 大文字英字を許可した。(でも非推奨)
		$RTN = array(
			'id'=>array('label'=>'ページID','rules'=>array('type'=>'id','preg'=>$preg_of_pid,'notnull'=>true)),
			'srcpath'=>array('label'=>'コンテンツファイルの格納先','rules'=>array('type'=>'text','preg'=>'/^(?:(?:\/[a-z0-9_-]+)*(?:\/[a-z0-9_-]+(?:\.[a-zA-Z0-9_-]+)?)?)?$/')),
			'linkto'=>array('label'=>'ページのリンク先','rules'=>array('type'=>'linkto','preg'=>'/^(?:(?:(?:(?:http:\/\/|https:\/\/|ftp:\/\/)(?:[a-zA-Z0-9]+\.)+[a-zA-Z0-9]+)?(?:(?:\/[a-zA-Z0-9._@-]+)*|\/)?)|(?:mailto:[a-zA-Z0-9-_]+\@(?:[a-zA-Z0-9-_]+\.)+[a-zA-Z0-9]+)|(?:javascript:.*)|(?:root:\/\/+.*))?$/')),
			'title'=>array('label'=>'ページタイトル','rules'=>array('type'=>'text','notnull'=>true,'lang'=>true)),
			'title_breadcrumb'=>array('label'=>'ページタイトル(パン屑)','rules'=>array('type'=>'text','notnull'=>false,'lang'=>true)),
			'title_page'=>array('label'=>'ページタイトル(ページ内表示)','rules'=>array('type'=>'textarea','notnull'=>false,'lang'=>true)),
			'title_label'=>array('label'=>'ページタイトル(リンク表示用)','rules'=>array('type'=>'textarea','notnull'=>false,'lang'=>true)),
			'path'=>array('label'=>'パンくず上のパス','rules'=>array('type'=>'path','preg'=>'/^(?:\/[0-9a-z\._-]+)*$/')),//Pickles Framework 0.5.0 type を text から path に変更
			'public_flg'=>array('label'=>'公開フラグ','rules'=>array('type'=>'bool','cont'=>array('公開する','公開しない'),'notnull'=>true)),
			'closed'=>array('label'=>'アクセス権限レベル','rules'=>array('type'=>'select','notnull'=>true,'cont'=>array(0,1,2,3,4,5,6,7,8,9))),
			'list_flg'=>array('label'=>'一覧表示フラグ','rules'=>array('type'=>'bool','cont'=>array('表示する','表示しない'),'notnull'=>true)),
			'outline'=>array('label'=>'アウトライン','rules'=>array('type'=>'text','notnull'=>false)),//PxFW 0.6.2 追加
			'orderby'=>array('label'=>'表示順','rules'=>array('type'=>'int','notnull'=>false,'preg'=>'/^(?:[1-9][0-9]*)?$/')),
			'release_date'=>array('label'=>'リリース日時','rules'=>array('type'=>'datetime','notnull'=>false)),
			'update_date'=>array('label'=>'最終更新日時','rules'=>array('type'=>'datetime','notnull'=>false)),
			'close_date'=>array('label'=>'クローズ日時','rules'=>array('type'=>'datetime','notnull'=>false)),
			'exetype'=>array('label'=>'実行形式','rules'=>array('type'=>'select','notnull'=>true,'cont'=>array(''=>'成り行き(拡張子により自動選択)','php'=>'PHPとして実行','html'=>'HTMLとして解析','wiki'=>'WIKIシンタックスで解析','txt'=>'通常のテキストファイルとして表示','direct'=>'そのまま採用','download'=>'ダウンロード'))),
			'cattitleby'=>array('label'=>'カテゴリタイトルの元とするページID','rules'=>array('type'=>'text','preg'=>$preg_of_pid)),
			'summary'=>array('label'=>'サマリ','rules'=>array('type'=>'textarea','lang'=>true,'notnull'=>false)),
			'keywords'=>array('label'=>'キーワード','rules'=>array('type'=>'text','lang'=>true,'notnull'=>false)),
			'description'=>array('label'=>'ディスクリプション','rules'=>array('type'=>'text','lang'=>true,'notnull'=>false)),
			'owner'=>array('label'=>'オーナーユーザ','rules'=>array('type'=>'user','notnull'=>false)),
			'ownergroup'=>array('label'=>'オーナーグループ','rules'=>array('type'=>'authgroup','notnull'=>false)),
			'message'=>array('label'=>'$themeへのメッセージ','rules'=>array('type'=>'text','notnull'=>false)),
			'memo'=>array('label'=>'開発者メモ','rules'=>array('type'=>'textarea','notnull'=>false)),
		);
		return	$RTN;
	}
	function getsitemap_definition(){
		#	PxFW 0.7.0 : get_sitemap_definition() に改名。
		#	getsitemap_definition() はエイリアスとしてしばらく残します。
		return $this->get_sitemap_definition();
	}
	#----------------------------------------------------------------------------
	#	サイトマップキャッシュを使うべきか調べる。
	function is_sitemapcache( $lang , $options = array() ){
		if( $options['disable_sitemapcache'] || !$this->dbh->is_file( $this->conf->path_cache_dir.'/sitemap/sitemap_'.$lang.'.cache' ) ){
			return	false;
		}

		#	デフォルト言語が指定されており、
		#	その言語に対応するサイトマップキャッシュがあった場合。
		foreach( $this->files_sitemap_src as $filepath ){
			#	キャッシュより新しいCSVがないか確認
			if( !$this->dbh->is_file( $filepath ) ){ continue; }
			if( $this->dbh->comp_filemtime( $filepath , $this->conf->path_cache_dir.'/sitemap/sitemap_'.$lang.'.cache' ) ){
				return	false;
			}
		}
		#	ページ属性系機能は PxFW 0.6.9 で廃止。
		//$nextpath = $this->conf->path_sitemap_dir.'/sitemap_attributedefine.array';
		//if( $this->dbh->is_file( $nextpath ) && !$this->dbh->comp_filemtime( $this->conf->path_cache_dir.'/sitemap/sitemap_'.$lang.'.cache' , $nextpath ) ){
		//	return	false;
		//}
		//$nextpath = $this->conf->path_sitemap_dir.'/sitemap_attributes.array';
		//if( $this->dbh->is_file( $nextpath ) && !$this->dbh->comp_filemtime( $this->conf->path_cache_dir.'/sitemap/sitemap_'.$lang.'.cache' , $nextpath ) ){
		//	return	false;
		//}
		$nextpath = $this->conf->path_sitemap_dir.'/sitemap_languagepack.array';
		if( $this->dbh->is_file( $nextpath ) && !$this->dbh->comp_filemtime( $this->conf->path_cache_dir.'/sitemap/sitemap_'.$lang.'.cache' , $nextpath ) ){
			return	false;
		}

		return	true;
	}
	#----------------------------------------------------------------------------
	#	サイトマップファイルの定義書(配列)を返す
	function getsitemap_attributes_definition(){
		#	PxFW 0.6.9 廃止。
		$this->errors->error_log( '$site->getsitemap_attributes_definition() は廃止されました。' , __FILE__ , __LINE__ );
		return array();
#		static $RTN = null;
#
#		#	すでに一度読み込んでいればそのまま返す
#		if( is_array( $RTN ) ){ return	$RTN; }
#
#		#	属性定義ファイルがなければ、空っぽの配列を返す
#		if( !$this->dbh->is_file( $this->conf->path_sitemap_dir.'/sitemap_attributedefine.array' ) ){
#			$RTN = array();
#			return	$RTN;
#		}
#
#		$RTN = require( $this->conf->path_sitemap_dir.'/sitemap_attributedefine.array' );
#		if( !is_array( $RTN ) ){ $RTN = array(); }
#		return	$RTN;
	}
	#----------------------------------------------------------------------------
	#	固定でセットしたいページ情報をハードコーディングする。
	function setsitemap_hardcoding( $SiteMap_Definition = null ){
		#	拡張してください。
		if( !is_array( $SiteMap_Definition ) ){ return	null; }
		return	null;
	}
	#----------------------------------------------------------------------------
	#	言語パックの設定を反映させる。
	function setsitemap_languagepack( $lang = 'ja' ){
		#	サイトマップをキャッシュから読むときは、
		#	この処理は通りません。

		if( $this->dbh->is_file( $this->conf->path_sitemap_dir.'/sitemap_languagepack.array' ) ){
			#--------------------------------------
			#	古い仕様
			#	Pickles Framework 0.3.X ではコチラの方法が採用されていました。
			#	互換性のために残します。
			$langpack = require( $this->conf->path_sitemap_dir.'/sitemap_languagepack.array' );
			$pidlist = array_keys( $langpack );
			foreach( $pidlist as $pid ){
				set_time_limit(0);//←Pickles Framework 0.5.7
				if( !is_array( $langpack[$pid][$lang] ) ){ continue; }	#	このpidにlangの情報がなければスキップ
				if( $pid != $this->getpageinfo( $pid , 'id' ) ){ continue; }//存在しないページの言語設定は採用しない。//Pickles Framework 0.5.0 追加。
				$keylist = array_keys( $langpack[$pid][$lang] );
				foreach( $keylist as $key ){
					$this->setpageinfo( $pid , $key , $langpack[$pid][$lang][$key] );
				}
			}
			set_time_limit(60);//←Pickles Framework 0.5.7
			#	/ 古い仕様
			#--------------------------------------
		}

		#--------------------------------------
		#	言語別サイトマップCSVの一覧を取得
		$lang_files_sitemap_src = array();//一旦リセット
		$mapfilelist = $this->dbh->getfilelist( $this->conf->path_sitemap_dir );
		foreach( $mapfilelist as $Line ){
			set_time_limit(0);//←Pickles Framework 0.5.7
			if( $Line == '.' || $Line == '..' ){ continue; }
			if( !preg_match( '/\.csv\.'.preg_quote($lang,'/').'$/' , $Line ) ){ continue; }
			array_push( $lang_files_sitemap_src , $this->conf->path_sitemap_dir.'/'.$Line );
		}
		set_time_limit(60);//←Pickles Framework 0.5.7
		unset($Line);
		if( !count( $lang_files_sitemap_src ) ){
			return	true;
		}
		#	/ 言語別サイトマップCSVの一覧を取得
		#--------------------------------------

		#--------------------------------------
		#	↓↓CSVからサイトマップを起こす↓↓

		$SiteMap_Definition = $this->get_sitemap_definition();
		if( !is_array( $SiteMap_Definition ) ){
			$this->errors->error_log( '$site->get_sitemap_definition() が配列以外の値を返しました。('.gettype($SiteMap_Definition).')' , __FILE__ , __LINE__ );
			return false;
		}

		#:::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
		#	サイトマップをセット
		$done_pid_list = array();
		foreach( $lang_files_sitemap_src as $filepath ){
			if( !@file_exists( $filepath ) ){ $this->errors->error_log( 'サイトマップがありません。' , __FILE__ , __LINE__ ); return false; }
			$this->dbh->fclose( $filepath );

			$res_csvfile = @fopen( $filepath , 'r' );//サイトマップの容量が大きかった場合を想定し、$dbh->read_csv() の使用をやめた。Pickles Framework 0.4.9
			while( $SMMEMO = fgetcsv( $res_csvfile , 10000 , ',' , '"' ) ){
				set_time_limit(0);//←Pickles Framework 0.5.7
				$SMMEMO = text::convert_encoding( $SMMEMO , mb_internal_encoding() , $this->localconf_sitemap_csv_encoding.',UTF-8,SJIS,EUC-JP' );

				#	PIDが存在しなかったらスキップ
				#	(ページIDが ゼロ番目 の要素であることは保障されたお約束)
				if( $this->getpageinfo( $SMMEMO[0] , 'id' ) != $SMMEMO[0] ){ continue; }
				#	すでに処理済ならばスキップ
				if( $done_pid_list[$SMMEMO[0]] ){ continue; }

				$i = 0;
				foreach( $SiteMap_Definition as $key=>$val ){
					if( $val['rules']['lang'] ){//←言語パックによる変更が許された項目のみ反映
						if( strlen( $SMMEMO[$i] ) ){
							#	値が空白なら反映しない。
							$this->setpageinfo( $SMMEMO[0] , $key , text::convert_encoding( $SMMEMO[$i] ) );
						}
					}
					$i++;
				}

				#	処理済PIDリストに追加
				$done_pid_list[$SMMEMO[0]] = true;

			}
			set_time_limit(60);//←Pickles Framework 0.5.7
			fclose( $res_csvfile );

		}
		#	/サイトマップをセット
		#:::::::::::::::::::::::::::::::::::::::::::::::::::::::::::

		return	true;
	}
	#----------------------------------------------------------------------------
	#	ページ属性の値を反映させる。
	#	( サイトマップをキャッシュから読むときは通りません。 )
	function setsitemap_attributes(){
		#	PxFW 0.6.9 廃止。
		$this->errors->error_log( '$site->setsitemap_attributes() は廃止されました。' , __FILE__ , __LINE__ );
		return false;
#		if( !$this->dbh->is_file( $this->conf->path_sitemap_dir.'/sitemap_attributes.array' ) ){
#			return	false;
#		}
#		$attributes = require( $this->conf->path_sitemap_dir.'/sitemap_attributes.array' );
#		if( !is_array( $attributes ) ){ $attributes = array(); }
#		foreach( $attributes as $pid=>$attributeContent ){
#			if( $pid != $this->getpageinfo( $pid , 'id' ) ){ continue; }//存在しないページの属性は採用しない。//Pickles Framework 0.5.0 追加。
#			$this->setpageinfo( $pid , 'attributes' , $attributeContent );
#		}
#		return	true;
	}


	#----------------------------------------------------------------------------
	#	現在$this->sitemapに展開されているサイトマップを、
	#	配列を表す文字列に変換し、キャッシュとして保存する。
	function save_cache_sitemap( $sitemap = null , $lang = null , $sitemap_path_cache = null ){
		#	Pickles Framework 0.5.7 : 第3引数 $sitemap_path_cache を追加。
		if( is_null( $sitemap ) ){
			$sitemap = &$this->sitemap;
		}
		if( is_null( $sitemap_path_cache ) ){
			$sitemap_path_cache = $this->sitemap_path_cache;
		}
		if( !$this->dbh->is_dir( $this->conf->path_sitemap_dir ) ){
			$this->errors->error_log( 'Sitemap Directory is NOT a directory. [ '.$this->conf->path_sitemap_dir.' ].' , __FILE__ , __LINE__ );
			return	false;
		}
#		if( !@is_writable( $this->conf->path_sitemap_dir ) ){
#			$this->errors->error_log( 'Sitemap Directory is NOT writable. [ '.$this->conf->path_sitemap_dir.' ].' , __FILE__ , __LINE__ );
#			return	false;
#		}
#↑Pickles Framework 0.5.7 : サイトマップディレクトリは書き込みできる必要はない。

#		if( !$this->conf->use_sitemapcache_flg ){//$conf->use_sitemapcache_flg は PxFW 0.7.0 で廃止
#			#	サイトマップキャッシュが無効だったらおしまい。
#			#	戻り値は true。
#			return true;
#		}

		#--------------------------------------
		#	ディレクトリの準備
		$sitemap_cache_dir = $this->conf->path_cache_dir.'/sitemap';
		if( !$this->dbh->is_dir( $sitemap_cache_dir ) ){
			$this->dbh->mkdirall( $sitemap_cache_dir );
		}
		if( !$this->dbh->is_dir( $sitemap_cache_dir ) ){
			$this->errors->error_log( 'Faild to mkdir [ '.$sitemap_cache_dir.' ].' , __FILE__ , __LINE__ );
			return false;
		}
		if( !@is_writable( $sitemap_cache_dir ) ){
			$this->errors->error_log( 'Sitemap Cache Directory [ '.$sitemap_cache_dir.' ] is NOT writable.' , __FILE__ , __LINE__ );
			return false;
		}

		$tmp_filename = null;//Pickles Framework 0.5.7 : 一旦、一時ファイルに作成するように改修。メモリー節約。
		while(1){
			$tmp_filename = 'tmp_cache_'.md5( time::microtime() );
			clearstatcache();
			if( !$this->dbh->is_file( $sitemap_cache_dir.'/'.$tmp_filename ) ){
				break;
			}
		}
		touch( $sitemap_cache_dir.'/'.$tmp_filename );//まずはファイルを作っちゃう。

		#--------------------------------------
		#	URLマップを作成
		$is_linkto = false;
		$index_linkto = 'linkto';

		error_log( '<'.'?php'."\n" , 3 , $sitemap_cache_dir.'/'.$tmp_filename );
		error_log( '/'.'* URL map cache : '.date('Y-m-d H:i:s').' *'.'/'."\n" , 3 , $sitemap_cache_dir.'/'.$tmp_filename );
		error_log( 'return array('."\n" , 3 , $sitemap_cache_dir.'/'.$tmp_filename );

		foreach( array_keys( $sitemap ) as $pid ){
			set_time_limit(0);//←PxFW 0.5.7
			if( preg_match( '/^\/+/' , $sitemap[$pid][$index_linkto] ) ){
				$linkto = $sitemap[$pid][$index_linkto];
				$linkto = preg_replace( '/^\/+/' , '/' , $linkto );
				$linkto = preg_replace( '/#.*$/' , '' , $linkto );//PxFW 0.5.10 : アンカーを削除
				$linkto = preg_replace( '/\?.*$/' , '' , $linkto );//PxFW 0.5.10 : パラメータを削除
					//PxFW 0.6.8 アンカーが削除されていなかった不具合を修正。
				error_log( '    '.text::data2text($linkto).'=>'.text::data2text($pid).' ,'."\n" , 3 , $sitemap_cache_dir.'/'.$tmp_filename );
				$is_linkto = true;
			}
		}
		error_log( ');'."\n" , 3 , $sitemap_cache_dir.'/'.$tmp_filename );
		error_log( '?'.'>' , 3 , $sitemap_cache_dir.'/'.$tmp_filename );

		set_time_limit(60);//←PxFW 0.5.7

		$urlmap_path = $sitemap_cache_dir.'/urlmap.cache';
		if( $is_linkto ){
			@unlink( $urlmap_path );
			$this->dbh->rename( $sitemap_cache_dir.'/'.$tmp_filename , $urlmap_path );
		}elseif( $this->dbh->is_file( $urlmap_path ) ){
			$this->dbh->rmdir( $urlmap_path );
		}
		unset( $FIN , $is_linkto );
		#	/ URLマップを作成
		#--------------------------------------

		@unlink( $sitemap_cache_dir.'/'.$tmp_filename );
		@touch( $sitemap_cache_dir.'/'.$tmp_filename );
		clearstatcache();

		#--------------------------------------
		#	サイトマップキャッシュ
		#	$confに、サイトマップキャッシュを使わない設定があったら、
		#	サイトマップのキャッシュ保存を行わず、trueを返す。
//		if( !$this->conf->use_sitemapcache_flg ){ return true; }//$conf->use_sitemapcache_flg は PxFW 0.7.0 で廃止

		if( !is_array( $sitemap ) ){ $sitemap = $this->getpageinfo(); }
		if( !is_array( $sitemap ) ){ return false; }

		error_log( '<'.'?php'."\n\n\n" , 3 , $sitemap_cache_dir.'/'.$tmp_filename );
		error_log( '/* '.mb_internal_encoding().' */'."\n" , 3 , $sitemap_cache_dir.'/'.$tmp_filename );
		error_log( '/* LastUpdate : '.date( 'Y/m/d H:i:s' ).' */'."\n" , 3 , $sitemap_cache_dir.'/'.$tmp_filename );
		error_log( '/* Language : '.$lang.' */'."\n" , 3 , $sitemap_cache_dir.'/'.$tmp_filename );
		error_log( "\n" , 3 , $sitemap_cache_dir.'/'.$tmp_filename );

		error_log( 'return array('."\n" , 3 , $sitemap_cache_dir.'/'.$tmp_filename );

		foreach( $sitemap as $key=>$val ){
			set_time_limit(0);//←PxFW 0.5.7
			if( !is_array( $val ) ){ continue; }
			foreach( $val as $valkey=>$valval ){
				if( $valkey == 'id' ){ continue; }//←PxFW 0.6.7 追加 : トップページのIDが null になってしまうから。
				if( is_null( $valval ) ){ unset( $val[$valkey] ); }
			}
			error_log( '	'.text::data2text( $key ).'=>'."\n" , 3 , $sitemap_cache_dir.'/'.$tmp_filename );
			error_log( '		'.text::data2text( $val ).' ,'."\n" , 3 , $sitemap_cache_dir.'/'.$tmp_filename );
		}
		set_time_limit(60);//←PxFW 0.5.7
		error_log( ');'."\n" , 3 , $sitemap_cache_dir.'/'.$tmp_filename );

		error_log( "\n\n".'?'.'>' , 3 , $sitemap_cache_dir.'/'.$tmp_filename );

		#	ファイルの保存
		if( strlen( $lang ) && $this->conf->allow_lang[$lang] ){
			#	指定言語のキャッシュを保存
			@unlink( $sitemap_cache_dir.'/sitemap_'.$lang.'.cache' );
			$results = $this->dbh->rename( $sitemap_cache_dir.'/'.$tmp_filename , $sitemap_cache_dir.'/sitemap_'.$lang.'.cache' );

		}
		#	/ サイトマップキャッシュ
		#--------------------------------------

		@unlink( $sitemap_cache_dir.'/'.$tmp_filename );
		@touch( $sitemap_cache_dir.'/'.$tmp_filename );
		clearstatcache();

		#--------------------------------------
		#	サイトマップパンくずキャッシュ

		error_log( '<'.'?php'."\n\n\n" , 3 , $sitemap_cache_dir.'/'.$tmp_filename );
		error_log( '/* '.mb_internal_encoding().' */'."\n" , 3 , $sitemap_cache_dir.'/'.$tmp_filename );
		error_log( '/* LastUpdate : '.date( 'Y/m/d H:i:s' ).' */'."\n" , 3 , $sitemap_cache_dir.'/'.$tmp_filename );
		error_log( '/* Language : '.$lang.' */'."\n" , 3 , $sitemap_cache_dir.'/'.$tmp_filename );
		error_log( "\n" , 3 , $sitemap_cache_dir.'/'.$tmp_filename );

		error_log( 'return array('."\n" , 3 , $sitemap_cache_dir.'/'.$tmp_filename );

		foreach( $sitemap_path_cache as $key=>$val ){
			set_time_limit(0);//←PxFW 0.5.7
			if( !is_array( $val ) ){ continue; }
			foreach( $val[0] as $valkey=>$valval ){
				if( !strlen( $valval ) ){ unset( $val[0][$valkey] ); }
			}
			error_log( '	'.text::data2text( $key ).'=>'."\n" , 3 , $sitemap_cache_dir.'/'.$tmp_filename );
			error_log( '		'.text::data2text( $val ).' ,'."\n" , 3 , $sitemap_cache_dir.'/'.$tmp_filename );
		}
		set_time_limit(60);//←PxFW 0.5.7
		error_log( ');'."\n" , 3 , $sitemap_cache_dir.'/'.$tmp_filename );

		error_log( "\n\n".'?'.'>' , 3 , $sitemap_cache_dir.'/'.$tmp_filename );

		#	ファイルの保存
		if( strlen( $lang ) && $this->conf->allow_lang[$lang] ){
			#	指定言語のキャッシュを保存
			@unlink( $sitemap_cache_dir.'/breadcrumb_'.$lang.'.cache' );
			$results = $this->dbh->rename( $sitemap_cache_dir.'/'.$tmp_filename , $sitemap_cache_dir.'/breadcrumb_'.$lang.'.cache' );

		}
		#	/ サイトマップパンくずキャッシュ
		#--------------------------------------

		#--------------------------------------
		#	readme.txt を残す。
		if( !$this->dbh->is_file( $sitemap_cache_dir.'/readme.txt' ) ){
			$README = '';
			$README .= '[ Sitemap Caches Directory ]'."\n";
			$README .= 'ここに保存されているファイルは、サイトマップCSVから作成したキャッシュファイルです。'."\n";
			$README .= 'サイトマップCSVを編集した場合、すべてのキャッシュファイルを削除してください。'."\n";
			$README .= ''."\n";
			$README .= 'Empty all caches on edit CSV Files of Sitemap.'."\n";
			$README .= ''."\n";
			$README .= 'Written by: save_cache_sitemap()'."\n";
			$README .= '            '.__FILE__.' on LINE: '.__LINE__.''."\n";
			$this->dbh->file_overwrite( $sitemap_cache_dir.'/readme.txt' , $README );
		}

		@unlink( $sitemap_cache_dir.'/'.$tmp_filename );
		clearstatcache();

		return	$results;
	}

	#--------------------------------------
	#	ページの情報を取得する
	function get_page_info( $pid = null , $name = null ){ return $this->getpageinfo( $pid , $name ); }//←PxFW 0.6.3 追加：エイリアス
	function getpageinfo( $pid = null , $name = null ){
		if( is_null( $pid ) ){
			$RTN = array();
			foreach( $this->sitemap as $pageId=>$pageInfo ){
				//	Pickles Framework 0.5.0 追加
				set_time_limit(0);//←Pickles Framework 0.5.7
				if( !$this->is_visiblepage( $pageId ) ){
					continue;
				}
				$RTN[$pageId] = $this->getpageinfo( $pageId.'' );
			}
			set_time_limit(60);//←Pickles Framework 0.5.7
			return $RTN;
		}
		if( substr( $pid , 0 , 1 ) == ':' ){
			#	PxFW 0.6.4 : 先頭の : (コロン)で$req->po() を表現できるようになった。
			$pid = preg_replace( '/^\:/' , $this->req->po().'.' , $pid );
		}

		#	シャープとスラッシュ以降は無視
		if( strpos( $pid , '#' ) ){ list( $pid , $waste ) = explode( '#' , $pid , 2 ); }
		if( strpos( $pid , '/' ) ){ list( $pid , $waste ) = explode( '/' , $pid , 2 ); }
		#	末尾のドットを削除
		$pid = preg_replace( '/\.+$/' , '' , $pid );
		#	先頭のドットを削除//Pickles Framework 0.5.1 追加
		$pid = preg_replace( '/^\.+/' , '' , $pid );

		#	見つかるまで $pid を丸める。
		while( $this->sitemap[$pid]['id'] != $pid || !$this->is_visiblepage( $pid ) ){
			if( !strpos( $pid , '.' ) ){
				if( !$this->conf->auto_notfound ){//←PxFW 0.6.7 追加
					$pid = '';
				}
				break;
			}
			$pid = preg_replace( '/^(.*)\..*?$/' , '$1' , $pid );
//			$pid = preg_replace( '/\.(?:(?:(?!\.).)*)$/' , '' , $pid );
		}

		if( !$this->is_visiblepage( $pid ) ){
			#	表示できないページならアウト
			return	null;
		}

		if( !strlen( $name ) ){
			#	キーがなければ、連想配列ごと返す。
			$RTN = $this->sitemap[$pid];
			if( !strlen( $RTN['title_breadcrumb'] ) ){
				$RTN['title_breadcrumb'] = $RTN['title'];
			}
			if( !strlen( $RTN['title_page'] ) ){
				$RTN['title_page'] = $RTN['title'];
			}
			if( !strlen( $RTN['title_label'] ) ){
				$RTN['title_label'] = $RTN['title'];
			}
			return	$RTN;
		}

		$indexStr = strtolower( $name );

		switch( $name ){
			#	省略可能なパラメータ
			#	※省略時に別な値を参照する。
			case 'title_breadcrumb':
			case 'title_page':
			case 'title_label':
				if( !strlen( $this->sitemap[$pid][$indexStr] ) ){
					$name = 'title';
					$indexStr = strtolower( $name );
				}
				break;
			default:
				break;
		}
		$RTN = $this->sitemap[$pid][$indexStr];

		return	$RTN;
	}

	#--------------------------------------
	#	表示可能なページか否か
	function is_visiblepage( $pid ){
		if( !is_array( $this->sitemap[$pid] ) ){
			return	false;
		}

		$release_date = $this->sitemap[$pid]['release_date'];
		$close_date = $this->sitemap[$pid]['close_date'];
		$public_flg = $this->sitemap[$pid]['public_flg'];
		if(
			( strlen( $release_date ) && $release_date > $this->conf->time )	#	公開日が未来だったら
			|| ( strlen( $close_date ) && $close_date < $this->conf->time )		#	終了日が過去だったら
			|| ( !$public_flg && !$this->conf->show_invisiblepage )				#	公開フラグがオフ、かつ、$conf->show_invisiblepage フラグがオフなら
				//	(Pickles Framework 0.5.2 : show_invisiblepage の有効範囲を $public_flg だけに限定)
		){
			#	非公開
			return	false;
		}
		return	true;

	}

	#--------------------------------------
	#	ページの情報をセットする
	function set_page_info( $pid , $name , $value ){ return $this->setpageinfo( $pid , $name , $value ); }//←PxFW 0.6.3 追加：エイリアス
	function setpageinfo( $pid , $name , $value ){
		#	Pickles Framework 0.5.10 : 引数を trim() するようにした。
		$pid = trim( $pid );
		$name = strtolower( trim( $name ) );
		$value = trim( $value );
		if( !is_string( $pid ) ){ return false; }
		if( !is_string( $name ) ){ return false; }

		if( substr( $pid , 0 , 1 ) == ':' ){
			#	PxFW 0.6.4 : 先頭の : (コロン)で$req->po() を表現できるようになった。
			$pid = preg_replace( '/^\:/' , $this->req->po().'.' , $pid );
		}

		#	シャープとスラッシュ以降は無視
		if( strpos( $pid , '#' ) ){ list( $pid , $waste ) = explode( '#' , $pid , 2 ); }
		if( strpos( $pid , '/' ) ){ list( $pid , $waste ) = explode( '/' , $pid , 2 ); }
		#	末尾のドットを削除
		$pid = preg_replace( '/\.+$/' , '' , $pid );
		#	先頭のドットを削除//Pickles Framework 0.5.1 追加
		$pid = preg_replace( '/^\.+/' , '' , $pid );

		$definition = $this->get_sitemap_definition();
		$nameType = $definition[$name]['rules']['type'];

		if( !array_key_exists( $pid , $this->sitemap ) ){
			#	$pid が存在していなければ、
			#	まず、$pid を定義する。
			#	Pickles Framework 0.4.7 追加の処理。14:42 2008/09/07
			$this->sitemap[$pid] = array();
			#	公開フラグに、自動的に1を設定する。
			$this->sitemap[$pid]['public_flg'] = 1;
		}

		if( $nameType == 'path' ){
			#	パンくずのパスだったら
			if( strpos( $value , ':' ) === 0 ){
				#	:(コロン)で始まっていたら、カレントの$req->po()のパスに置き換える。
				#	PxFW 0.6.5 追加
				$value = preg_replace( '/^\:/' , $this->getpageinfo( $this->req->po() , 'path' ).'/' , $value );
				$value = preg_replace( '/\/+$/' , '' , $value );
			}
			if( $value == '/' || $value == '\\' ){ $value = ''; }//PxFW 0.6.2 追加の処理
			$this->sitemap[$pid][$name] = $value;
			if( strlen($pid) ){
				$this->sitemap[$pid][$name] .= '/'.$pid;//←PxFW 0.6.9 トップページのパスが '/' ではなく '' になるように。
			}

			#	Pickles Framework 0.5.7 : 追加
			if( !is_array( $this->sitemap_path_cache[$value] ) ){
				$this->sitemap_path_cache[$value] = array( array() , array() , false );
					//	↑[0]=ページリスト、[1]=ページリスト(リスト表示のみ)、[2]ソート済みフラグ
			}
			$tmp_hitflg = false;
			foreach( $this->sitemap_path_cache[$value][0] as $tmp_pid ){
				if( $tmp_pid == $pid ){
					$tmp_hitflg = true;
					break;
				}
			}
			if( !$tmp_hitflg ){ // ←PxFW0.6.3 追加：一覧上に既に存在する場合に重複登録してしまう問題を修正。
				array_push( $this->sitemap_path_cache[$value][0] , $pid );
			}
			$this->sitemap_path_cache[$value][2] = false;
			#	/ Pickles Framework 0.5.7 : 追加

		}elseif( $nameType == 'linkto' ){
			#	linktoだったら
			#	PxFW 0.6.7 追加
			if( strpos( $value , './' ) === 0 ){
				#	./ で始まっていたら、カレントの$req->po()のパスに置き換える。
				$value = preg_replace( '/^\.\//' , '/'.str_replace( '.' , '/' , $this->req->po() ).'/' , $value );
			}
			$this->sitemap[$pid][$name] = $value;

		}elseif( $nameType == 'date' || $nameType == 'datetime' ){
			#	日付系だったら
			$MEMO_DATETIME = null;
			if( $nameType == 'date' ){
				$MEMO_DATETIME = time::date2int( $value );
			}else{
				$MEMO_DATETIME = time::datetime2int( $value );
			}
			if( is_int( $MEMO_DATETIME ) ){
				#	正しく日付として解釈できた場合
				$this->sitemap[$pid][$name] = $MEMO_DATETIME;
			}
			unset( $MEMO_DATETIME );
		}elseif( $nameType == 'int' ){
			#	数値型の場合(PxFW 0.6.7 追加)
			if( strlen( $value ) || $definition[$name]['rules']['notnull'] ){
				$this->sitemap[$pid][$name] = intval($value);
			}else{
				$this->sitemap[$pid][$name] = null;
			}
		}elseif( $nameType == 'bool' ){
			#	真偽型の場合(PxFW 0.6.7 追加)
			if( !strlen( $value ) && !$definition[$name]['rules']['notnull'] ){
				$this->sitemap[$pid][$name] = null;
			}elseif( !$value || strtolower( $value ) == 'false' ){
				$this->sitemap[$pid][$name] = false;
			}else{
				$this->sitemap[$pid][$name] = true;
			}
		}elseif( $nameType == 'text' ){
			#	単一行文字列型の場合(PxFW 0.6.7 追加)
			if( !strlen( $value ) && !$definition[$name]['rules']['notnull'] ){
				$this->sitemap[$pid][$name] = null;
			}else{
				$this->sitemap[$pid][$name] = preg_replace( '/\r\n|\r|\n/s' , '' , $value );
			}
		}else{
			#	普通はこれ
			if( !strlen( $value ) && !$definition[$name]['rules']['notnull'] ){
				$this->sitemap[$pid][$name] = null;
			}else{
				$this->sitemap[$pid][$name] = $value;
			}
		}
		return	true;
	}
	#--------------------------------------
	#	ページの情報を、1ページ分一式セットする
	function set_page_info_all( $pid , $values ){ return $this->setpageinfoall( $pid , $values ); }//←PxFW 0.6.3 追加：エイリアス
	function setpageinfoall( $pid , $values ){
		if( !is_string( $pid ) ){ return false; }
		if( is_null( $values['public_flg'] ) ){
			#	公開フラグが意識されていなければ、
			#	自動的に1を設定する。(面倒すぎるので改修)
			#	10:23 2007/04/09
			$values['public_flg'] = 1;
		}

		if( substr( $pid , 0 , 1 ) == ':' ){
			#	PxFW 0.6.4 : 先頭の : (コロン)で$req->po() を表現できるようになった。
			$pid = preg_replace( '/^\:/' , $this->req->po().'.' , $pid );
		}

		#	シャープとスラッシュ以降は無視
		if( strpos( $pid , '#' ) ){ list( $pid , $waste ) = explode( '#' , $pid , 2 ); }
		if( strpos( $pid , '/' ) ){ list( $pid , $waste ) = explode( '/' , $pid , 2 ); }
		#	末尾のドットを削除
		$pid = preg_replace( '/\.+$/' , '' , $pid );
		#	先頭のドットを削除//Pickles Framework 0.5.1 追加
		$pid = preg_replace( '/^\.+/' , '' , $pid );

		if( !is_array( $values ) ){ return false; }
		$this->setpageinfo( $pid , 'id' , $pid );
		foreach( $this->get_sitemap_definition() as $defLine=>$defCont ){
			if( $defLine == 'id' ){ continue; }
			$this->setpageinfo( $pid , $defLine , $values[$defLine] );
		}

		return	true;
	}
	#--------------------------------------
	#	ページの情報を削除
	function delete_page_info( $pid ){ return $this->delpageinfo( $pid ); }//←PxFW 0.6.3 追加：エイリアス
	function delpageinfo( $pid ){
		if( !is_string( $pid ) ){ return false; }//トップページは消せない。
		unset( $this->sitemap[$pid] );
		return	true;
	}
	#--------------------------------------
	#	ページの属性情報を取得する
	function get_page_attribute( $pid = null , $attkey = null , $multikey = null ){ return $this->getpageattribute( $pid , $attkey , $multikey ); }//←PxFW 0.6.3 追加：エイリアス
	function getpageattribute( $pid = null , $attkey = null , $multikey = null ){
		#	PxFW 0.6.9 廃止。
		$this->errors->error_log( '$site->getpageattribute() は廃止されました。' , __FILE__ , __LINE__ );
		return null;
#		if( is_null( $pid ) ){ return	null; }
#		if( is_null( $attkey ) ){ return	$this->getpageinfo( $pid , 'attributes' ); }
#		$attkey = strtolower( $attkey );
#
#		$att = $this->getpageinfo( $pid , 'attributes' );
#		if( is_array( $att[$attkey] ) && !is_null( $multikey ) ){
#			return	$att[$attkey][$multikey];
#		}
#		return	$att[$attkey];
	}
	#--------------------------------------
	#	子階層のページのIDの一覧を取得する
	function get_children( $pid = null , $option = array() ){
		$RTN = array();
		if( is_null( $pid ) ){ $pid = $this->req->p(); }//←PxFW 0.6.4 : デフォルトがカレントページになった。
		if( !strlen( $pid ) ){ $pid = ''; }

		$target_path = $this->getpageinfo( $pid , 'path' );
		if( $target_path == '/' || $target_path == '\\' ){
			$target_path = '';
		}

		if( !$this->sitemap_path_cache[$target_path][2] ){
			#	ソート済みフラグがオフだったら、
			#	ソートして、またキャッシュに戻す。

			$tmplist = $this->sitemap_path_cache[$target_path][0];
			if( !is_array( $tmplist ) ){
				$tmplist = array();
			}

			#	全子供リストを精査
			foreach( $tmplist as $key=>$val ){
				if( !strlen( $val ) || !strlen( $this->getpageinfo($val,'id') ) ){
					#	トップページは対象外。
					#	存在しないページは対象外。
					unset( $tmplist[$key] );
					continue;
				}
				if( $this->getpageinfo($val,'path') != $target_path.'/'.$val ){
					#	path が変更された場合を想定して、
					#	ここで再チェックする。
					#	指定ページの子供じゃなかったら対象外。
					unset( $tmplist[$key] );
					continue;
				}
			}

			//PxFW 0.6.0 : やっぱりソートが思い通りに動いてないので、思い切ってmanualとautoとに分けて並べ替えるようにした。
			$tmplist_manual = array();//並び替えるもの
			$tmplist_auto = array();//並び替えないもの
			foreach( $tmplist as $tmpline ){
				if( strlen( $this->sitemap[$tmpline]['orderby'] ) || strlen( $this->sitemap[$tmpline]['release_date'] ) ){
					array_push( $tmplist_manual , $tmpline );
				}else{
					array_push( $tmplist_auto , $tmpline );
				}
			}

			$tmplist_manual = array_reverse( $tmplist_manual );//Pickles Framework 0.5.2 追加 : なぜか usort() のコールバック関数が ゼロ を返した場合に前後が逆転してしまうため。
			usort( $tmplist_manual , array( $this , 'sort_sitemap' ) );//Pickles Framework 0.5.2 : uasort -> usort
			$tmplist = array_merge( $tmplist_manual , $tmplist_auto );
			$this->sitemap_path_cache[$target_path][0] = $tmplist;
			unset( $tmplist_manual , $tmplist_auto );

			#	リスト表示フラグが true なものだけのリストを精査
			foreach( $tmplist as $key=>$val ){
				if( !$this->getpageinfo($val,'list_flg') ){
					#	リストに表示してはいけないページは対象外
					unset( $tmplist[$key] );
					continue;
				}
			}
			$this->sitemap_path_cache[$target_path][1] = $tmplist;

			$this->sitemap_path_cache[$target_path][2] = true;
			unset($tmplist);
		}

		if( $option['all'] ){
			#	全部入ってる一覧
			$RTN = $this->sitemap_path_cache[$target_path][0];
		}else{
			#	リスト表示がオンのものだけの一覧
			$RTN = $this->sitemap_path_cache[$target_path][1];
		}
		return	$RTN;
	}
	#--------------------------------------
	#	同階層のページのIDの一覧を取得する
	function get_bros( $pid = null , $option = array() ){
		#	Pickles Framework 0.5.0 追加
		if( is_null( $pid ) ){ $pid = $this->req->p(); }//←PxFW 0.6.4 : デフォルトがカレントページになった。
		if( !is_string( $pid ) ){ $pid = ''; }
		if( !strlen( $pid ) ){ return array(''); }
		return	$this->get_children( $this->get_parent( $pid ) , $option );
	}

	#--------------------------------------
	#	親ページIDを取得する
	function get_parent( $pid = null ){
		if( is_null( $pid ) ){ $pid = $this->req->p(); }//←PxFW 0.6.4 : デフォルトがカレントページになった。
		if( !strlen( $pid ) ){ return null; }
		$path = $this->getpageinfo( $pid , 'path' );
		if( !strlen( $path ) || $path == '/' ){ return null; }
		$breadcrumb = explode( '/' , $path );
		$i = 2;
		$RTN = $breadcrumb[(count($breadcrumb)-$i)];//PxFW 0.6.5 : 親ページがカレントと同じだった場合に、その次を探すようになった。
		while( $RTN == $this->req->p() && strlen( $RTN ) ){
			$i ++;
			$RTN = $breadcrumb[(count($breadcrumb)-$i)];
		}
		return	$RTN;
	}

	#--------------------------------------
	#	前のページのページIDを取得する
	function get_prev( $pid = null ){
		#	Pickles Framework 0.6.1 追加
		if( is_null( $pid ) ){ $pid = $this->req->p(); }//←PxFW 0.6.4 : デフォルトがカレントページになった。
		if( !is_string( $pid ) ){ $pid = ''; }
		if( !strlen( $pid ) ){ return null; }
		$bros = $this->get_bros( $pid );
		foreach( $bros as $key=>$val ){
			if( $val == $pid ){
				return	$bros[$key-1];
			}
		}
		return	null;
	}
	#--------------------------------------
	#	次のページのページIDを取得する
	function get_next( $pid = null ){
		#	Pickles Framework 0.6.1 追加
		if( is_null( $pid ) ){ $pid = $this->req->p(); }//←PxFW 0.6.4 : デフォルトがカレントページになった。
		if( !is_string( $pid ) ){ $pid = ''; }
		if( !strlen( $pid ) ){ return null; }
		$bros = $this->get_bros( $pid );
		foreach( $bros as $key=>$val ){
			if( $val == $pid ){
				return	$bros[$key+1];
			}
		}
		return	null;
	}

	#--------------------------------------
	#	全ページのページIDの一覧を得る
	function get_pagelist(){
		#	Pickles Framework 0.5.10 : 追加
		return	array_keys( $this->sitemap );
	}

	#----------------------------------------------------------------------------
	#	ページ情報の配列を並び替える
	#	$a,$bには、PIDがくるようにしてください。
	function sort_sitemap( $a , $b ){

		#	優先度順
		$orderby_a = $this->getpageinfo( $a , 'orderby' );
		$orderby_b = $this->getpageinfo( $b , 'orderby' );
		if( strlen( $orderby_a ) && !strlen( $orderby_b ) ){
			return	-1;
		}elseif( strlen( $orderby_b ) && !strlen( $orderby_a ) ){
			return	1;
		}elseif( $orderby_a < $orderby_b ){
			return	-1;
		}elseif( $orderby_a > $orderby_b ){
			return	1;
		}

		#	リリース日順
		#	Pickles Framework 0.5.0 追加。
		$release_date_a = $this->getpageinfo( $a , 'release_date' );
		$release_date_b = $this->getpageinfo( $b , 'release_date' );
		if( $release_date_a < $release_date_b ){
			return	1;
		}elseif( $release_date_a > $release_date_b ){
			return	-1;
		}

		return	0;
	}
	function sort_sitemap_by_releasedate( $a , $b ){

		#	リリース日順
		$release_date_a = $this->getpageinfo( $a , 'release_date' );
		$release_date_b = $this->getpageinfo( $b , 'release_date' );
		if( $release_date_a < $release_date_b ){
			return	1;
		}elseif( $release_date_a > $release_date_b ){
			return	-1;
		}

		#	優先度順
		$orderby_a = $this->getpageinfo( $a , 'orderby' );
		$orderby_b = $this->getpageinfo( $b , 'orderby' );
		if( strlen( $orderby_a ) && !strlen( $orderby_b ) ){
			return	-1;
		}elseif( strlen( $orderby_b ) && !strlen( $orderby_a ) ){
			return	1;
		}elseif( $orderby_a < $orderby_b ){
			return	-1;
		}elseif( $orderby_a > $orderby_b ){
			return	1;
		}

		return	0;
	}



	#--------------------------------------
	#	カレントページのデザイン情報を設定
	function set_theme_message( $pid , $value = null ){
		$indexStr = 'message';
		if( is_null( $value ) || is_array( $value ) ){
			$this->sitemap[$pid][$indexStr] = $value;
		}elseif( is_string( $value ) ){
			$this->sitemap[$pid][$indexStr] = $this->parse_option( $value );
		}
		return	true;
	}
	#--------------------------------------
	#	カレントページのデザイン情報を取得
	function get_theme_message( $pid = null , $key = null ){
		if( is_null( $pid ) ){ $pid = $this->req->p(); }//←PxFW 0.6.4 : デフォルトがカレントページになった。
		if( !strlen( $pid ) ){ $pid = ''; }
		$indexStr = 'message';
		$RTN = $this->parse_option( $this->sitemap[$pid][$indexStr] );
		if( !is_null( $key ) ){
			return	$RTN[$key];
		}
		return	$RTN;
	}

	#--------------------------------------
	#	ページIDから、パンくずの階層を得る
	function getcontentlayer( $pid ){
		#	戻り値は配列です。
		#	配列の末尾に、$pid 自身を含みます。
		$indexStr = 'path';
		while( !is_array( $this->sitemap[$pid] ) ){
			$tmp_pid = preg_replace( '/\..+?$/is' , '' , $pid );
			if( $tmp_pid == $pid ){
				break;
			}
			$pid = $tmp_pid;
			unset( $tmp_pid );
			if( !strlen( $pid ) || is_array( $this->sitemap[$pid] ) ){
				break;
			}
		}
		$path = $this->sitemap[$pid][$indexStr];
		$path = explode( '/' , $path );
		return	$path;
	}

	#--------------------------------------
	#	DBHをメンバーに加える
	function putdbh( &$dbh ){
		if(!is_object($dbh)){return	false;}
		$this->dbh = &$dbh;
		return	true;
	}

	#--------------------------------------
	#	オプションを分解して、連想配列で返す。
	function parse_option( $Val , $sep1 = '&' , $sep2 = '=' ){
		if( is_array( $Val ) ){ return $Val; }
		if( !is_string( $Val ) ){ return array(); }

		$list1 = explode( $sep1 , $Val );
		$RTN = array();
		foreach( $list1 as $Line ){
			if( !strlen( $Line ) ){ continue; }
			$list2 = explode( $sep2 , $Line );
			if( $sep1 == '&' ){
				#	&区切りの場合、URLデコードする
				$RTN[urldecode( $list2[0] )] = urldecode( $list2[1] );
			}else{
				$RTN[$list2[0]] = $list2[1];
			}
		}
		return	$RTN;
	}

}

?>