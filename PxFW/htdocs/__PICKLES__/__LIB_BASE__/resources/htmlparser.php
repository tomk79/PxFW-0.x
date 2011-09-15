<?php

#	Copyright (C)Tomoya Koyanagi.
#	Last Update : 11:31 2010/01/25

#------------------------------------------------------------------------------------------------------------------
#	HTML文書ソース解析
#	※UTF-8 で編集してください。
class base_resources_htmlparser{

	var $localconf = array( 'print_comment' => true , 'do_reform' => false );
	var $pattern_html = '(?:[a-z0-9A-Z_-]+\:)?[a-zA-Z][a-z0-9A-Z_-]*';// Pickles Framework 0.5.1 : XMLNameSpaceを考慮
	var $pattern_attribute = '(?:[a-z0-9A-Z_-]+\:)?[a-z0-9A-Z_-]+';// Pickles Framework 0.5.1 : XMLNameSpaceを考慮
	var $pattern_js = '';
	var $pattern_css = '';
	var $parsed_html = array();
	var $original_string = '';
	var $pattern_allow_selfclose = '/^(?:img|br|wbr|link|meta|input|hr)$/is';

	var $RewriteRules = array();
		#	タグ名の置き換えルールを連想配列で設定
		#	strtolowerして使われるので、大文字は設定できません。

	var $safetycounter_limit = 999999;
		#	閉じタグを探すループの回数制限。
		#	無限ループに陥ってしまわないための措置。

	var $errorlist = array();
		#	内部エラーを記憶する器。

	function set_pattern_html( $str )		{ $this->pattern_html = $str; }
	function set_pattern_attribute( $str )	{ $this->pattern_attribute = $str; }
	function set_pattern_js( $str )			{ $this->pattern_js = $str; }
	function set_pattern_css( $str )		{ $this->pattern_css = $str; }

	#--------------------------------------
	#	設定内容のセット/取得
	function set_conf( $key , $val = null )	{ $this->localconf[$key] = $val; }
	function get_conf( $key )				{ return $this->localconf[$key]; }

	#--------------------------------------
	#	パースした結果セットされたHTMLの要素を取得/設定する
	function sethtmlelements( $key , $val )	{ $this->htmlelements[$key] = $val; return true; }
	function gethtmlelements( $key )		{ return $this->htmlelements[$key]; }


	#########################################################################################################################################################
	#	HTMLの解析
	function html_parse( $strings , $pedigree = array() ){
		$this->original_string = $strings;
		if( !is_string( $strings ) ){ return array(); }
		if( !strlen( $strings ) ){ return array(); }

		$this->parsed_html = array();
		$RTN = array();

		$pregstring = $this->get_pattern_html();

		$is_hit = 0;
		$stringsMemo = '';
		while( 1 ){
			#	PxFW 0.6.6 : ヒットしなかった場合にリトライするようにした。
			#	PxFW 0.6.7 : リトライのロジックを見直した。
			#	(http://www.pxt.jp/ja/diary/article/218/index.html この問題への対応)
			$tmp_start = strpos( $strings , '<' );
			if( !is_int( $tmp_start ) || $tmp_start < 0 ){
				#	[<]記号 が見つからなかったら、
				#	本当にヒットしなかったものとみなせる。
				$strings    = $stringsMemo.$strings;
				break;
			}
			$stringsMemo .= substr( $strings , 0 , $tmp_start );
			$strings = substr( $strings , $tmp_start );
			$is_hit = preg_match( $pregstring , $strings , $results );
			if( $is_hit ){
				#	ヒットしたらここでbreak;
				$results[1] = $stringsMemo.$results[1];
				$strings    = $stringsMemo.$strings;
				break;
			}
			//今回先頭にあるはずの[<]記号を $stringsMemo に移してリトライ
			$stringsMemo .= substr( $strings , 0 , 1 );
			$strings = substr( $strings , 1 );
		}
		unset( $stringsMemo );
		unset( $tmp_start );

		if( $is_hit ){
			$MEMO = array();
			$preg_number = 0;
			$preg_number ++;
			$MEMO['str_prev']			= $results[$preg_number++];
			$MEMO['commentout']			= $results[$preg_number++];
			$MEMO['cdata']				= $results[$preg_number++];//PxFW 0.6.5 追加
			$MEMO['php_script']			= $results[$preg_number++];
			$MEMO['tag']				= strtolower( $results[$preg_number++] );
			$MEMO['tagOriginal']		= $MEMO['tag'];//Pickles Framework 0.5.1 追加
			$MEMO['attribute_str']		= $results[$preg_number++];
			$MEMO['att_quot']			= $results[$preg_number++];
			$MEMO['closed_flg']			= $results[$preg_number++];
			$MEMO['str_next']			= $results[$preg_number++];
			$MEMO['pedigree']			= $pedigree;
			unset( $preg_number );

			if( strlen( $this->RewriteRules[$MEMO['tag']] ) ){
				#	タグ名書き換えルールに合致したら、置き換える。
				$MEMO['tag'] = strtolower( $this->RewriteRules[$MEMO['tag']] );
			}

			$MEMO['attribute'] = $this->html_attribute_parse( $MEMO['attribute_str'] );

			if( !$MEMO['closed_flg'] && preg_match( '/<\\/'.preg_quote( $MEMO['tagOriginal'] , '/' ).'>/is' , $MEMO['str_next'] ) ){
				$MEMO2 = $this->search_closetag( $MEMO['tagOriginal'] , $MEMO['str_next'] );
				$MEMO['content_str']		= $MEMO2['content_str'];
				$MEMO['str_next']			= $MEMO2['str_next'];
				unset($MEMO2);
			}

			#	content_strをパース
			if( $MEMO['tag'] == 'style' ){
				$MEMO['content']	= null;
			}elseif( $MEMO['tag'] == 'script' ){
				$MEMO['content']	= null;
			}else{
				$pedigree2next = $pedigree;
				array_push( $pedigree2next , array( 'tag'=>$MEMO['tag'] , 'attribute'=>$MEMO['attribute'] , 'attribute_str'=>$MEMO['attribute_str'] ) );
				$MEMO['content'] = $this->html_parse( $MEMO['content_str'] , $pedigree2next );
			}

			#	ユニークなタグの情報をメンバにセット
			if( $MEMO['tag'] == 'title' ){
				$this->sethtmlelements( 'title' , $MEMO['content_str'] );
			}

			#	str_nextをパース
			$MEMO2 = $this->html_parse( $MEMO['str_next'] , $pedigree );

			if( count( $MEMO2 ) ){
				$MEMO['str_next'] = null;
				array_push( $RTN , $MEMO );
				foreach( $MEMO2 as $Line ){
					array_push( $RTN , $Line );
				}
			}else{
				array_push( $RTN , $MEMO );
			}
			unset( $MEMO );

			unset( $MEMO2 );
		}
		$this->parsed_html = $RTN;
		return	$RTN;
	}

	#--------------------------------------
	#	閉じタグを検索する
	function search_closetag( $tagname , $strings ){
		#	タグの深さ
		$att = $this->pattern_attribute;
		$depth = 0;
		$strings_original = $strings;

		$rnsp = '(?:\r\n|\r|\n| |\t)';

		#	属性のパターン
		#	属性の中にタグがあってはならない
		$atteribute = ''.$rnsp.'*?(?:'.$rnsp.'*(?:'.$att.')(?:'.$rnsp.'*'.preg_quote('=','/').''.$rnsp.'*?(?:(?:[^"\' ]+?)|([\'"]?).*?\7))?'.$rnsp.'*)*';

		$pregstring = '/^(.*?)(?:('.preg_quote('<'.'?','/').'(?:php)?)|('.preg_quote('<![CDATA[','/').')|('.preg_quote('<!--','/').')|(<(\/?)?(?:'.preg_quote($tagname,'/').')(?:'.$rnsp.'+?'.$atteribute.')?'.'>))(.*)$/is';

		$safetycounter = 0;
		$RTN = array(
			'content_str'=>'' ,
			'str_next'=>'' ,
		);
		while( true ){
			$safetycounter ++;
			if( is_int( $this->safetycounter_limit ) && $safetycounter > intval( $this->safetycounter_limit ) ){
				#	安全装置作動
				#	$this->safetycounter_limitに設定した数以上先の
				#	閉じタグは探せません。
				$msg = '[SafetyBreak!] on HTML Parser of LINE ['.__LINE__.']. COUNTER = ['.$safetycounter.'] TAGNAME = ['.$tagname.']';
				$this->error( $msg , __FILE__ , __LINE__ );
				return	array( 'content_str'=>$msg , 'str_next'=>'' );
			}

			$i = 0;

			$is_hit = 0;
			$stringsMemo = '';
			while( 1 ){
				#	PxFW 0.6.6 : ヒットしなかった場合にリトライするようにした。
				#	PxFW 0.6.7 : リトライのロジックを見直した。
				#	(http://www.pxt.jp/ja/diary/article/218/index.html この問題への対応)
				$tmp_start = strpos( $strings , '<' );
				if( !is_int( $tmp_start ) || $tmp_start < 0 ){
					#	[<]記号 が見つからなかったら、
					#	本当にヒットしなかったものとみなせる。
					$strings    = $stringsMemo.$strings;
					break;
				}
				$stringsMemo .= substr( $strings , 0 , $tmp_start );
				$strings = substr( $strings , $tmp_start );
				$is_hit = preg_match( $pregstring , $strings , $results );
				if( $is_hit ){
					#	ヒットしたらここでbreak;
					$results[1] = $stringsMemo.$results[1];
					$strings    = $stringsMemo.$strings;
					break;
				}
				//今回先頭にあるはずの[<]記号を $stringsMemo に移してリトライ
				$stringsMemo .= substr( $strings , 0 , 1 );
				$strings = substr( $strings , 1 );
			}
			unset( $stringsMemo );
			unset( $tmp_start );

			if( $is_hit ){
				#	何かしらの結果があった場合
				$preg_i = 0;
				$MEMO = array();
				$preg_i ++;
				$MEMO['str_prev']			= $results[$preg_i++];
				$MEMO['start_php']			= $results[$preg_i++];
				$MEMO['start_cdata']		= $results[$preg_i++];
				$MEMO['start_htmlcomment']	= $results[$preg_i++];
				$MEMO['mytag']				= $results[$preg_i++];
				$MEMO['closed_flg']			= $results[$preg_i++];
				$MEMO['attribute_str']		= $results[$preg_i++];
				$MEMO['str_next']			= $results[$preg_i++];

				#--------------------------------------
				#	戻り値を作成
				if( strlen( $MEMO['start_php'] ) ){
					#	Pickles Framework 0.5.3 追加
					#	PHPスクリプト内の閉じタグは検出してはいけない
					preg_match( '/^(.*?)(?:'.preg_quote('?'.'>','/').')(.*)$/si' , $MEMO['str_next'] , $php_preg_matched );
					$RTN['content_str'] .= $MEMO['str_prev'];
					$RTN['content_str'] .= $MEMO['start_php'];
					$RTN['content_str'] .= $php_preg_matched[1];
					$RTN['content_str'] .= '?'.'>';
					$strings = $php_preg_matched[2];
					continue;

				}elseif( strlen( $MEMO['start_cdata'] ) ){
					#	Pickles Framework 0.6.5 追加
					#	CDATAセクション内の閉じタグは検出してはいけない
					preg_match( '/^(.*?)(?:'.preg_quote(']]'.'>','/').')(.*)$/si' , $MEMO['str_next'] , $php_preg_matched );
					$RTN['content_str'] .= $MEMO['str_prev'];
					$RTN['content_str'] .= $MEMO['start_cdata'];
					$RTN['content_str'] .= $php_preg_matched[1];
					$RTN['content_str'] .= ']]'.'>';
					$strings = $php_preg_matched[2];
					continue;

				}elseif( strlen( $MEMO['start_htmlcomment'] ) ){
					#	Pickles Framework 0.5.3 追加
					#	コメントタグ内の閉じタグは検出してはいけない
					preg_match( '/^(.*?)(?:'.preg_quote('--'.'>','/').')(.*)$/si' , $MEMO['str_next'] , $php_preg_matched );
					$RTN['content_str'] .= $MEMO['str_prev'];
					$RTN['content_str'] .= $MEMO['start_htmlcomment'];
					$RTN['content_str'] .= $php_preg_matched[1];
					$RTN['content_str'] .= '--'.'>';
					$strings = $php_preg_matched[2];
					continue;

				}elseif( strlen( $MEMO['closed_flg'] ) && $depth <= 0 ){
					$RTN['content_str'] .= $MEMO['str_prev'];
					#	深さ0階層で、閉じタグを発見した場合
					$RTN['str_next'] .= $MEMO['str_next'];

					return	$RTN;
					break;

				}elseif( strlen( $MEMO['closed_flg'] ) && $depth > 0 ){
					$RTN['content_str'] .= $MEMO['str_prev'].$MEMO['mytag'];
					#	深さ1階層以上で、閉じタグを発見した場合
					$depth --;
					$strings = $MEMO['str_next'];
					continue;

				}elseif( !strlen( $MEMO['closed_flg'] ) ){
					$RTN['content_str'] .= $MEMO['str_prev'].$MEMO['mytag'];
					#	入れ子の開始タグを発見してしまった場合
					$depth ++;
					$strings = $MEMO['str_next'];
					continue;

				}else{
					break;

				}

			}
			break;
		}

		#	解析が最後まで行ってしまった場合
		#	つまり、閉じタグがなかった場合
		$RTN = array( 'content_str'=>'' , 'str_next'=>$strings_original );
		return	$RTN;

	}

	#--------------------------------------
	#	HTMLタグを検出するPREGパターンを生成して返す。
	function get_pattern_html(){
		#	タグの種類
		$tag = $this->pattern_html;
		$att = $this->pattern_attribute;

		$rnsp = '(?:\r\n|\r|\n| |\t)';

		#	タグの前の文字列パターン
		#	タグのパターンで認識できる文字列があってはならない
#		$strprev = '(?:(?!<(?:(?:(?:'.$tag.')[> ])|(?:\!--))).)*';
#		$strprev = '.*?';

		#	属性のパターン
		#	属性の中にタグがあってはならない
		$atteribute = ''.$rnsp.'*(?:'.$rnsp.'*(?:'.$att.')(?:'.$rnsp.'*\='.$rnsp.'*(?:(?:[^"\' ]+)|([\'"]?).*?\7))?'.$rnsp.'*)*'.$rnsp.'*';

		#	コメントタグのパターン
		$commentout = '(?:<\!--((?:(?!-->).)*)(?:-->)?)';
		$cdata      = '(?:<\!\[CDATA\[(.*?)\]\]>)';//PxFW 0.6.5 追加
		$php_script = '(?:<\?(?:php)?((?:(?!\?'.'>).)*)(?:\?'.'>)?)';
#		$php_script = '(?:<\?(?:php)?(.+?)(?:\?'.'>)?)';

		$pregstring = '/(.*?)(?:'.$commentout.'|'.$cdata.'|'.$php_script.'|(?:<('.$tag.')(?:'.$rnsp.'+('.$atteribute.'))?(\\/?)>))(.*)/is';

		return	$pregstring;
	}

	#----------------------------------------------------------------------------
	#	HTML属性の解析
	function html_attribute_parse( $strings ){
		preg_match_all( $this->get_pattern_attribute() , $strings , $results );
		for( $i = 0; !is_null($results[0][$i]); $i++ ){
			if( !strlen($results[3][$i]) ){
				$results[4][$i] = null;
			}
			if( $results[2][$i] ){
				$RTN[strtolower( $results[1][$i] )] = $results[2][$i];
			}else{
				$RTN[strtolower( $results[1][$i] )] = $results[4][$i];
			}
			//	Pickles Framework 0.5.7 : $this->html2text() をかけるのをやめた。
			//	属性中の特殊文字は、テキストとしてではなく、特殊文字のままで取り扱うことにした。
		}
		return	$RTN;
	}

	#--------------------------------------
	#	タグの属性情報を検出するPREGパターンを生成して返す。
	function get_pattern_attribute(){
		#	属性の種類
		$rnsp = '(?:\r\n|\r|\n| |\t)';
		$prop = $this->pattern_attribute;
		$typeA = '([\'"]?)(.*?)\3';	#	ダブルクオートあり
		$typeB = '[^"\' ]+';					#	ダブルクオートなし

		#	属性指定の式
#		$prop_exists = '/[ ]*('.$prop.'){1,1}(?:\=([\'"]?)([^"]*)\2[ ]?)?/is';
#		$prop_exists = '/ *('.$prop.'){1,1}(?:\=([\'"]?)(.*?)\2 *)?/is';
		$prop_exists = '/'.$rnsp.'*('.$prop.')(?:\=(?:('.$typeB.')|'.$typeA.'))?'.$rnsp.'*/is';

		return	$prop_exists;
	}




	#########################################################################################################################################################
	#	解析されたHTMLを表す配列から、特定の情報を取り出し、別の領域に記憶する
	function pickup( $parsed_html = null , $option = null ){
		return;
	}



	#########################################################################################################################################################
	#	HTMLの書き出し
	function publish( $parsed_html = null , $option = null ){
		if( !is_array( $parsed_html ) ){ $parsed_html = $this->parsed_html; }
		if( !count( $parsed_html ) ){ return	$this->original_string; }
		if( !is_array( $parsed_html ) ){ return	false; }

		foreach( $parsed_html as $Line ){

			$enable_singleclose = preg_match( $this->pattern_allow_selfclose , $Line['tag'] );

			if( strlen( $Line['str_prev'] ) ){ $RTN .= $Line['str_prev']; }

			if( strlen( $Line['commentout'] ) ){
				#	コメント行はそのまま書き出しておしまい。
				if( $this->get_conf('print_comment') ){
					$RTN .= '<'.'!--'.$Line['commentout'].'--'.'>';
				}
				if( strlen($Line['str_next']) ){ $RTN .= $Line['str_next']; }
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
				if( strlen( $Line['str_next'] ) ){ $RTN .= $Line['str_next']; }
				continue;
			}
			if( !strlen( $Line['tag'] ) ){
				#	タグ情報が空ならスキップ
				if( strlen( $Line['str_next'] ) ){ $RTN .= $Line['str_next']; }
				continue;
			}

			if( method_exists( $this , 'tag_'.strtolower( $Line['tag'] ) ) ){
				#--------------------------------------
				#	カスタムタグの実装があれば、そちらに任せる
				$taginfo = $Line;
				unset( $taginfo['str_prev'] );
				unset( $taginfo['str_next'] );
				$RTN .= eval( 'return	$this->tag_'.strtolower( $Line['tag'] ).'( $taginfo , $option );' );
				if( strlen($Line['str_next']) ){ $RTN .= $Line['str_next']; }
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
				if( strlen($Line['str_next']) ){ $RTN .= $Line['str_next']; }
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

			if( strlen($Line['str_next']) ){ $RTN .= $Line['str_next']; }
		}
		return	$RTN;
	}

	#--------------------------------------
	#	属性を書き出す
	function publish_attribute( $tagname , $attributes , $attributes_str , $option = null ){
		if( $tagname == '!DOCTYPE' ){
			return	preg_replace( '/[ ]*\/$/' , '' , $attributes_str );
		}
		$written = array();
		$RTN = '';

		#--------------------------------------
		#	タグ名毎のアトリビュートの順序などを整理
		#	※省略した場合は、元のソースにかかれた通りの順序で復元します。
		if( $tagname == 'img' ){
			#	<img />
			$RTN .= ' src="'.( $attributes['src'] ).'"';
			$written['src'] = true;
			if( strlen($attributes['width']) ){
				$RTN .= ' width="'.( $attributes['width'] ).'"';
				$written['width'] = true;
			}
			if( strlen($attributes['height']) ){
				$RTN .= ' height="'.( $attributes['height'] ).'"';
				$written['height'] = true;
			}
			if( strlen($attributes['border']) ){
				$RTN .= ' border="'.( $attributes['border'] ).'"';
				$written['border'] = true;
			}
			$RTN .= ' alt="'.( $attributes['alt'] ).'"';
			$written['alt'] = true;
		}elseif( $tagname == 'table' ){
			#	<table></table>
			if( strlen($attributes['border']) ){
				$RTN .= ' border="'.( $attributes['border'] ).'"';
				$written['border'] = true;
			}
			if( strlen($attributes['cellpadding']) ){
				$RTN .= ' cellpadding="'.( $attributes['cellpadding'] ).'"';
				$written['cellpadding'] = true;
			}
			if( strlen($attributes['cellspacing']) ){
				$RTN .= ' cellspacing="'.( $attributes['cellspacing'] ).'"';
				$written['cellspacing'] = true;
			}
			if( strlen($attributes['width']) ){
				$RTN .= ' width="'.( $attributes['width'] ).'"';
				$written['width'] = true;
			}
		}elseif( $tagname == 'td' || $tagname == 'th' ){
			#	<td><th>
			if( strlen($attributes['align']) ){
				$RTN .= ' align="'.( $attributes['align'] ).'"';
				$written['align'] = true;
			}
			if( strlen($attributes['valign']) ){
				$RTN .= ' valign="'.( $attributes['valign'] ).'"';
				$written['valign'] = true;
			}
			if( strlen($attributes['width']) ){
				$RTN .= ' width="'.( $attributes['width'] ).'"';
				$written['width'] = true;
			}
		}
		#	/タグ名毎のアトリビュートの順序などを整理
		#--------------------------------------

		if( count( $attributes ) ){
			$keys = array_keys( $attributes );
			foreach( $keys as $Line_att ){
				if( $written[$Line_att] ){continue;}
				$RTN .= ' '.$Line_att;
				if( !is_null($attributes[$Line_att]) ){
					$RTN .= '="'.( $attributes[$Line_att] ).'"';
				}
			}
		}
		return	$RTN;
	}





	#----------------------------------------------------------------------------
	#	受け取ったHTMLをテキスト形式に変換する
	#	(クラス base_static_text からのコピー)
	function html2text(){
		//	htmlspecialchars_decode()というのもあるが、
		//	PHP5以降からなので、とりあえず使ってない。
		list($TEXT) = func_get_args();
		$TEXT = preg_replace( '/<br(?: \/)?>/' , "\n" , $TEXT );
		$TEXT = preg_replace( '/&lt;/' , '<' , $TEXT );
		$TEXT = preg_replace( '/&gt;/' , '>' , $TEXT );
		$TEXT = preg_replace( '/&quot;/' , '"' , $TEXT );
		$TEXT = preg_replace( '/&amp;/' , '&' , $TEXT );
		return	$TEXT;
	}



	#----------------------------------------------------------------------------
	#	内部エラーハンドラ

	#	クラス内にエラーを保持する
	function error( $errormessage , $FILE = null , $LINE = null ){
		$ERROR = array();
		$ERROR['msg'] = $errormessage;
		$ERROR['file'] = $FILE;
		$ERROR['line'] = $LINE;
		array_push( $this->errorlist , $ERROR );
		return	true;
	}

	#	保持したエラーを取得する
	function get_errorlist(){
		return	$this->errorlist;
	}

	#	エラーが発生したか否か調べる
	function is_error(){
		if( count( $this->errorlist ) ){
			return	true;
		}
		return	false;
	}

}

?>