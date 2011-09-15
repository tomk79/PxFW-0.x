<?php

#	Copyright (C)Tomoya Koyanagi.
#	Last Update : 10:43 2008/08/21

require_once( $conf->path_lib_base.'/resources/htmlparser.php' );

#------------------------------------------------------------------------------------------------------------------
#	HTMLドキュメントソース解析
class base_resources_html2hankana extends base_resources_htmlparser{

	var $pattern_html = 'form|input|textarea|select|option|label|button';
		#	↑Pickles Framework 0.4.5 追加
		#		半角変換しないタグを指定するように変更

	#########################################################################################################################################################
	#	HTMLの書き出し
	function publish( $parsed_html = null , $option = null ){
		if( !is_array( $parsed_html ) ){ $parsed_html = $this->parsed_html; }
		if( !count( $parsed_html ) ){ return	$this->original_string; }
		if( !is_array( $parsed_html ) ){ return	false; }

		foreach( $parsed_html as $Line ){

			$enable_singleclose = preg_match( $this->pattern_allow_selfclose , $Line['tag'] );

			if( strlen($Line['str_prev']) ){ $RTN .= text::hankana( $Line['str_prev'] , true ); }

			if( strlen($Line['commentout']) ){
				#	コメント行はそのまま書き出しておしまい。
				if( $this->get_conf('print_comment') ){
					$RTN .= '<!--'.$Line['commentout'].'-->';
				}
				if( strlen($Line['str_next']) ){ $RTN .= text::hankana( $Line['str_next'] , true ); }
				continue;
			}
			if( strlen($Line['php_script']) ){
				#	PHPスクリプトはそのまま書き出しておしまい。
				$RTN .= '<'.'?php '."\n".$Line['php_script']."\n".' ?'.'>';
				if( strlen($Line['str_next']) ){ $RTN .= text::hankana( $Line['str_next'] , true ); }
				continue;
			}
			if( !strlen( $Line['tag'] ) ){
				#	タグ情報が空ならスキップ
				if( strlen( $Line['str_next'] ) ){ $RTN .= text::hankana( $Line['str_next'] , true ); }
				continue;
			}

			if( method_exists( $this , 'tag_'.strtolower( $Line['tag'] ) ) ){
				#--------------------------------------
				#	カスタムタグの実装があれば、そちらに任せる
				$taginfo = $Line;
				unset( $taginfo['str_prev'] );
				unset( $taginfo['str_next'] );
				$RTN .= eval( 'return	$this->tag_'.strtolower( $Line['tag'] ).'( $taginfo , $option );' );
				if( strlen($Line['str_next']) ){ $RTN .= text::hankana( $Line['str_next'] , true ); }
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
				if( strlen($Line['str_next']) ){ $RTN .= text::hankana( $Line['str_next'] , true ); }
				continue;
			}

			#--------------------------------------
			#	タグにはさまれた部分
			if( count( $Line['content'] ) && is_array( $Line['content'] ) ){
				$RTN .= $this->publish( $Line['content'] );
			}elseif( $Line['content_str'] ){
				$RTN .= text::hankana( $Line['content_str'] , ( $Line['tag'] != 'textarea' && $Line['tag'] != 'style' ) );
			}
			#	/タグにはさまれた部分
			#--------------------------------------

			$RTN .= '</'.$Line['tag'].'>';

			if( strlen($Line['str_next']) ){ $RTN .= text::hankana( $Line['str_next'] , true ); }
		}
		return	$RTN;
	}

}

?>