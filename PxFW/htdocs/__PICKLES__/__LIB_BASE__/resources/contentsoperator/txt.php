<?php

#	Copyright (C)Tomoya Koyanagi.
#	LastUpdate : 23:15 2011/05/05

require_once( $conf->path_lib_base.'/resources/contentsoperator/html.php' );

#******************************************************************************************************************
#	Conductorが読み込んだテキストファイルのを処理する
class base_resources_contentsoperator_txt extends base_resources_contentsoperator_html{
	#--------------------------------------
	#	このクラスの実装は暫定的な実験段階にあります。
	#	将来の拡張により、
	#	メソッド名やWIKI文法仕様などが、
	#	変更される可能性があります。
	#--------------------------------------

	var $execute_type = 'txt';//PxFW 0.7.2 追加

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
			if( !$this->dbh->is_dir( dirname( $path_cache ) ) ){
				$this->dbh->mkdirall( dirname( $path_cache ) );
			}

			#	コンテンツを処理
			$CACHE_SRC = $this->process_src( $ORIGINAL_HTML_SRC );

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
	#	コンテンツを処理
	function process_src( $ORIGINAL_HTML_SRC ){

		#	リンクを変換
		$RTN = '';
		while( 1 ){
			if( !preg_match( '/^(.*?)((?:http|https|ftp):\/\/(?:[a-zA-Z0-9'.preg_quote('_.&=@:%?~#+-/','/').']|\&[a-zA-Z0-9_-]+\;)+)(.*)$/is' , $ORIGINAL_HTML_SRC , $matched ) ){
				$RTN .= preg_replace( '/\r\n|\r|\n/' , '<br />'."\n" , htmlspecialchars( preg_replace( '/((?:\t| ){2,})/is' , '<pre style="display:inline;">$1</pre>' , $ORIGINAL_HTML_SRC ) ) );
				break;
			}
			$RTN .= preg_replace( '/\r\n|\r|\n/' , '<br />'."\n" , htmlspecialchars( preg_replace( '/((?:\t| ){2,})/is' , '<pre style="display:inline;">$1</pre>' , $matched[1] ) ) );
			$href = $matched[2];
			$RTN .= '<a href="'.htmlspecialchars($href).'" onclick="window.open(this.href);return false;">'.htmlspecialchars($href).'</a>';

			$ORIGINAL_HTML_SRC = $matched[3];
		}
		#	ttrで囲む
		$RTN = '<div class="ttr">'.$RTN.'</div>';

		return	$RTN;
	}

}

?>