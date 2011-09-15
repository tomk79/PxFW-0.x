<?php

#	Copyright (C)Tomoya Koyanagi.
#	LastUpdate : 15:30 2010/03/04

#******************************************************************************************************************
#	ユーザエージェント解析クラス
class base_resources_user_useragent{

	var $conf;
	var $req;
	var $dbh;

	var $all;
	var $os;
	var $osv;
	var $bsr;
	var $bsrv;
	var $bsrv_string;
	var $group;
	var $ct = 'PC';
	var $enduserclass = 'human';
	var $enable_http_referer = null;
	var $enable_cookie = null;
	var $device_id = null;
	var $device_spec = array();// Pickles Framework 0.1.2 追加

	#----------------------------------------------------------------------------
	#	コンストラクタ
	function base_resources_user_useragent( &$conf , &$req , &$dbh ){
		$this->conf = &$conf;
		$this->req = &$req;
		$this->dbh = &$dbh;//Pickles Framework 0.1.0 で追加。
	}

	#----------------------------------------------------------------------------
	#	カスタムユーザエージェントパーサ
	function custom_useragent_parser( $HTTP_USER_AGENT , $OTHER_PARAMS ){
		/* -------------------------------------- *
		このメソッドは、プロジェクトが拡張して使用します。
		Pickles Framework が標準的に意識していないユーザエージェントを
		ハンドルするには、ここに実装してください。
		parse_useragent()にコールされます。
		このメソッドが false を返した場合、
		parse_useragent()はPickles Framework の標準の解析を行います。
		このメソッドが true を返す場合、
		次の値をメンバにセットしている必要があります。
			・$this->os
			・$this->osv
			・$this->bsr
			・$this->bsrv
			・$this->bsrv_string
			・$this->group
			・$this->device_spec
			・$this->ct
			・$this->enduserclass
		/* -------------------------------------- */
		return	false;
	}

	#----------------------------------------------------------------------------
	#	ユーザエージェントを解析して覚える
	function parse_useragent( $HTTP_USER_AGENT = null , $OTHER_PARAMS = null ){
		#--------------------------------------
		#	引数の精査
		if( is_null( $OTHER_PARAMS ) ){
			$OTHER_PARAMS = $_SERVER;
		}
		if( is_null( $HTTP_USER_AGENT ) ){
			$HTTP_USER_AGENT = $OTHER_PARAMS['HTTP_USER_AGENT'];
		}
		#	/ 引数の精査
		#--------------------------------------

		#--------------------------------------
		#	$all は、ユーザエージェント文字列のそのままを格納
		$this->all = $HTTP_USER_AGENT;

		#--------------------------------------
		#	クッキーの有無を知るためのクッキーを発行
#		$this->req->setcookie( 'CCUA' , '1' );

		$custom_parser_result = $this->custom_useragent_parser( $HTTP_USER_AGENT , $OTHER_PARAMS );
		if( !$custom_parser_result ){
			#--------------------------------------
			#	カスタムユーザエージェントパーサが解析できなかった場合
			#	Pickles Framework 標準のパーサ

			$UA_lower = strtolower( $this->all );

			############################################################################
			#	OS
			############################################################################

			if( is_int( strpos( $UA_lower , 'windows ce' ) ) ){
				$this->os = 'WindowsCE';
				$this->ct = 'PDA';
			}elseif( is_int( strpos( $UA_lower , 'win' ) ) ){
				$this->os = 'Windows';
			}elseif( is_int( strpos( $UA_lower , 'mac' ) ) ){
				$this->os = 'Macintosh';
			}elseif( is_int( strpos( $UA_lower , 'linux' ) ) ){
				$this->os = 'Linux';
			}elseif( is_int( strpos( $UA_lower , 'docomo' ) ) ){
				$this->os = 'DoCoMo';
			}elseif( is_int( strpos( $UA_lower , 'kddi' ) ) ){
				$this->os = 'KDDI';
			}elseif( is_int( strpos( $UA_lower , 'softbank' ) ) ){
				$this->os = 'SoftBank';
			}elseif( is_int( strpos( $UA_lower , 'vodafone' ) ) ){
				$this->os = 'SoftBank';
			}elseif( is_int( strpos( $UA_lower , 'j-phone' ) ) ){
				$this->os = 'SoftBank';
			}else{
				$this->os = 'UnknownOS';
			}
			$this->osv = $this->os;


			############################################################################
			#	Browser
			############################################################################

			if( is_int( strpos( $UA_lower , 'baidu' ) ) ){
				if( is_int( strpos( $UA_lower , 'baiduimagespider' ) ) ){//Pickles Framework 0.5.3 追加
					$this->group = 'Searcher';
					$this->bsr = 'BaiduImagespider';
					$this->ct = 'PC';
					$this->enduserclass = 'robot';
				}else{
					$this->group = 'Searcher';
					$this->bsr = 'Baiduspider';
					$this->ct = 'PC';
					$this->enduserclass = 'robot';
				}
			}elseif( is_int( strpos( $UA_lower , 'googlebot-mobile' ) ) ){
				$this->group = 'Searcher';
				$this->bsr = 'Googlebot-Mobile';
				$this->ct = 'MP';
				$this->enduserclass = 'robot';
			}elseif( is_int( strpos( $UA_lower , 'googlebot' ) ) ){
				$this->group = 'Searcher';
				$this->bsr = 'Googlebot';
				$this->ct = 'PC';
				$this->enduserclass = 'robot';
			}elseif( is_int( strpos( $UA_lower , 'msnbot' ) ) ){
				$this->group = 'Searcher';
				$this->bsr = 'msnbot';
				$this->ct = 'PC';
				$this->enduserclass = 'robot';
			}elseif( is_int( strpos( $UA_lower , 'yahoo! slurp' ) ) ){
				$this->group = 'Searcher';
				$this->bsr = 'YahooSlurp';
				$this->ct = 'PC';
				$this->enduserclass = 'robot';
			}elseif( is_int( strpos( $UA_lower , 'ichiro' ) ) ){//Pickles Framework 0.5.3 追加
				$this->group = 'Searcher';
				$this->bsr = 'ichiro';
				$this->ct = 'PC';
				$this->enduserclass = 'robot';
			}elseif( is_int( strpos( $UA_lower , 'ia_archiver' ) ) ){//Pickles Framework 0.5.3 追加
				$this->group = 'Searcher';
				$this->bsr = 'ia_archiver';
				$this->ct = 'PC';
				$this->enduserclass = 'robot';
			}elseif( is_int( strpos( $UA_lower , 'feedhub metadatafetcher' ) ) ){//Pickles Framework 0.5.3 追加
				$this->group = 'Searcher';
				$this->bsr = 'FeedHubMetaDataFetcher';
				$this->ct = 'PC';
				$this->enduserclass = 'robot';
			}elseif( is_int( strpos( $UA_lower , 'presto' ) ) ){//PxFW 0.6.5 追加
				$this->group = 'Opera';
				$this->bsr = 'Presto';
			}elseif( is_int( strpos( $UA_lower , 'opera' ) ) ){
				$this->group = 'Opera';
				$this->bsr = 'Opera';
			}elseif( is_int( strpos( $UA_lower , 'chrome' ) ) ){//Pickles Framework 0.4.8 追加
				$this->group = 'KHTML';
				$this->bsr = 'Chrome';
			}elseif( is_int( strpos( $UA_lower , 'amaya' ) ) ){
				$this->group = 'amaya';
				$this->bsr = 'amaya';
			}elseif( is_int( strpos( $UA_lower , 'msie' ) ) ){
				$this->group = 'MSIE';
				$this->bsr = 'MSIE';
			}elseif( is_int( strpos( $UA_lower , 'firefox' ) ) ){
				$this->group = 'Gecko';
				$this->bsr = 'Firefox';
			}elseif( is_int( strpos( $UA_lower , 'netscape' ) ) ){
				$this->group = 'Gecko';
				$this->bsr = 'Netscape';
			}elseif( is_int( strpos( $UA_lower , 'safari' ) ) ){
				$this->group = 'KHTML';
				$this->bsr = 'Safari';
			}elseif( is_int( strpos( $UA_lower , 'camino' ) ) ){
				$this->group = 'Camino';
				$this->bsr = 'Camino';
			}elseif( is_int( strpos( $UA_lower , 'firebird' ) ) ){
				$this->group = 'Gecko';
				$this->bsr = 'Firebird';
			}elseif( is_int( strpos( $UA_lower , 'gecko' ) ) ){
				$this->group = 'Gecko';
				$this->bsr = 'Gecko';
			}elseif( is_int( strpos( $UA_lower , 'docomo' ) ) ){
				$this->group = 'DoCoMo';
				$this->bsr = 'DoCoMo';
				$this->ct = 'MP';
			}elseif( is_int( strpos( $UA_lower , 'j-phone' ) ) ){
				$this->group = 'Softbank';
				$this->bsr = 'J-PHONE';
				$this->ct = 'MP';
			}elseif( is_int( strpos( $UA_lower , 'vodafone' ) ) ){
				$this->group = 'Softbank';
				$this->bsr = 'Vodafone';
				$this->ct = 'MP';
			}elseif( is_int( strpos( $UA_lower , 'softbank' ) ) ){
				$this->group = 'Softbank';
				$this->bsr = 'Softbank';
				$this->ct = 'PC';
					// 0:00 2007/08/08 Pickles Framework 0.1.6
					// SoftBankでは、モバイルブラウザとPCサイトブラウザを2つで一つという扱いで考えているように見える。
					// スタイルシートのmedia type = handheld を解釈するため、PCという扱いで統一してよいと考えたため、
					// SoftBankのユーザエージェントをPCと解釈するように方針を変更した。
			}elseif( is_int( strpos( $UA_lower , 'up.browser' ) ) ){
				$this->group = 'UP.Browser';
				$this->bsr = 'UP.Browser';
				$this->ct = 'MP';
			}elseif( is_int( strpos( $UA_lower , 'pdxgw' ) ) ){
				$this->group = 'PDXGW';
				$this->bsr = 'PDXGW';
				$this->ct = 'MP';
			}elseif( is_int( strpos( $UA_lower , 'netfront' ) ) ){
				$this->group = 'NetFront';
				$this->bsr = 'NetFront';
				$this->ct = 'PDA';
			}elseif( is_int( strpos( $UA_lower , 'sharp pda browser' ) ) ){
				$this->group = 'Zaurus';
				$this->bsr = 'Zaurus';
				$txt = 'sharp pda browser';
				$this->ct = 'PDA';
			}elseif( is_int( strpos( $UA_lower , 'cnf' ) ) ){
				$this->group = 'CNF';
				$this->bsr = 'CNF';
				$this->ct = 'MP';
			}elseif( is_int( strpos( $UA_lower , 'website explorer' ) ) ){
				$this->group = 'Searcher';
				$this->bsr = 'WebsiteExplorer';
			}elseif( is_int( strpos( $UA_lower , 'yeti' ) ) ){
				#	1:02 2007/09/22 (Pickles Framework 0.1.9) Add
				#	Yeti/0.01 (nhn/1noon, yetibot@naver.com, check robots.txt daily and follow it)
				$this->group = 'Searcher';
				$this->bsr = 'Yeti';
				$this->ct = 'PC';
				$this->enduserclass = 'robot';
			}elseif( is_int( strpos( $UA_lower , 'bookmark renewal check agent' ) ) ){
				$this->group = 'Searcher';
				$this->bsr = 'BookmarkRenewalCheckAgent';
				$this->ct = 'PC';
				$this->enduserclass = 'robot';
			}elseif( is_int( strpos( $UA_lower , 'mozilla' ) ) ){
				$this->group = 'SeaMonkey';
				$this->bsr = 'Mozilla';
			}else{
				$this->bsr = 'Unknown';
			}

			if( is_int( strpos( $UA_lower , 'y!j' ) ) ){
				//	Y!J-SRD : Pickles Framework 0.4.8 追加
				//	その他 : Pickles Framework 0.5.3 追加
				$this->group = 'Searcher';
				$this->enduserclass = 'robot';
				if( is_int( strpos( $UA_lower , 'y!j-srd' ) ) ){
					#	このクローラは、
					#	ケータイ3キャリアそれぞれを偽装するので、
					#	ロボットであることだけを設定し、
					#	ブラウザ名は、だまされてよい。
				}elseif( preg_match( '/('.preg_quote('Y!-','/').'[a-zA-Z0-9]+)/si' , $this->all , $preg_matched ) ){
					#	Y!J-DSC ,Y!J-VSC, Y!J-PSC, Y!J-NSC, Y!J-SRD, Y!J-BRG/GSC, Y!J-BRI ,Y!J-BRE
					$this->bsr = $preg_matched[1];
				}

			}elseif( is_int( strpos( $UA_lower , 'p90lis' ) ) ){//Pickles Framework 0.4.8 追加
				#	このクローラは、P901iS になりきってアクセスしてきているつもりでいるが、
				#	1 を l と間違っているらしい。
				$this->group = 'Searcher';
				$this->enduserclass = 'robot';
			}

			if(!$txt){
				$txt = $this->bsr;
			}

			$NAME_BITE = strpos( $this->all , $txt , 0 );
			$USERAGENT = substr( $this->all , $NAME_BITE );

			if( preg_match( '/-/' , $this->bsr ) ){	# $this->bsr =~ m/-/gi
				#	ブラウザ名にハイフンが入っている場合
				list( $AgentValue_BSR , $AgentValue_BSRV , $LENG2 ) = preg_split( '/ |\/|\;/' , $USERAGENT );//PxFW 0.6.8 : split() を preg_split() に変更
			}else{
				#	普通はこれでいける
				list( $AgentValue_BSR , $AgentValue_BSRV , $LENG2 ) = preg_split( '/ |\/|\;|\-/' , $USERAGENT );//PxFW 0.6.8 : split() を preg_split() に変更
			}
			$this->bsrv_string = $AgentValue_BSRV;
			$MEMO = preg_split( '/[^0-9]/' , $AgentValue_BSRV );
			$AgentValue_BSRV = $MEMO[0].'.'.$MEMO[1];//PxFW 0.6.5 : 2桁目までしか使わないことにした。
			$this->bsrv = floatval( $AgentValue_BSRV );
			unset( $AgentValue_BSRV );
			unset( $MEMO );

			if( $this->bsr == 'Opera' && $this->bsrv >= 10 ){
				#	Opera 10 の特殊処理(IEまたはFirefoxに偽装していた場合、ここに該当)
				#	PxFW 0.6.5 : 追加
				$this->bsr = 'Presto';
				$this->bsrv = 2.2;
				$this->bsrv_string = '2.2.15';
			}elseif( $this->bsr == 'Opera' && $this->bsrv >= 9.6 ){
				#	Opera 9.6 の特殊処理(IEまたはFirefoxに偽装していた場合、ここに該当)
				#	PxFW 0.6.5 : 追加
				$this->bsr = 'Presto';
				$this->bsrv = 2.1;
				$this->bsrv_string = '2.1.1';
			}

			#	/ カスタムユーザエージェントパーサが解析できなかった場合
			#--------------------------------------
		}

		#--------------------------------------
		#	デフォルト値のセット
		if( !strlen( $this->ct ) ){
			$this->ct = 'PC';
		}
		if( !strlen( $this->bsr ) ){
			$this->bsr = 'Unknown';
		}
		if( !strlen( $this->group ) ){
			$this->group = 'Unknown';
		}
		if( !strlen( $this->bsrv ) ){
			$this->bsr = 0;
		}
		if( !strlen( $this->enduserclass ) ){
			$this->enduserclass = 'human';
		}
		#	/ デフォルト値のセット
		#--------------------------------------

		#;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;
		#	その他、環境変数などから、特に判断可能な情報
		#;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;

		#	リファラを送信してくるか？
		if( is_null( $this->enable_http_referer ) ){
			$this->enable_http_referer = false;
		}
		if( strlen( $OTHER_PARAMS['HTTP_REFERER'] ) ){
			$this->enable_http_referer = true;
		}

		#	クッキーが有効か？
		if( is_null( $this->enable_cookie ) ){
			$this->enable_cookie = false;
		}
		if( strlen( $this->req->getcookie( 'CCUA' ) ) ){
			$this->enable_cookie = true;
		}
		$this->req->setcookie( 'CCUA' , '1' );
			#	クッキーの有無を知るためのクッキーを発行

		#	コマンドラインで呼ばれていたら
		if( $this->req->is_cmd() ){
			$this->enduserclass = 'command';
		}

		#	端末識別IDを調べる
		$device_id = $this->parse_device_id( $OTHER_PARAMS );
		if( strlen( $device_id ) ){
			$this->req->setsession( 'DEVICE_ID' , $device_id );
		}
		$this->device_id = $this->req->getsession( 'DEVICE_ID' );

		return	true;
	}

	#--------------------------------------
	#	端末識別IDをパースして調べる
	#	※parse_useragent()内からか、またはparse_useragent()実行後にコールされる必要があります。
	function parse_device_id( $OTHER_PARAMS = null ){
		if( is_null( $OTHER_PARAMS ) ){
			$OTHER_PARAMS = $_SERVER;
		}
		$device_id = null;
		if( strlen( $OTHER_PARAMS['HTTP_X_UP_SUBNO'] ) ){
			#	au
			$device_id = $OTHER_PARAMS['HTTP_X_UP_SUBNO'];
		}elseif( $this->bsr == 'DoCoMo' ){
			#	DoCoMo
			if( preg_match( '/ser([a-zA-Z0-9]{11,15})$/' , $this->all , $result ) ){
				$device_id = $result[1];
			}elseif( preg_match( '/icc([a-zA-Z0-9]{20})\)$/' , $this->all , $result ) ){
				$device_id = $result[1];
			}
		}elseif( strlen( $OTHER_PARAMS['HTTP_X_JPHONE_UID'] ) ){
			#	SoftBank/vodafone
			$device_id = $OTHER_PARAMS['HTTP_X_JPHONE_UID'];
		}
		return	$device_id;
	}


	#------------------------------------------------------------------------------------------------------------------
	#	解析したクライアント情報を取得するメソッド集

	function get_browser_name(){ return $this->bsr; }
	function get_browser_version( $digit = null ){
		if( is_int( $digit ) ){
			$digits = explode( '.' , $this->bsrv_string );
			return intval( $digits[$digit] );
		}
		return $this->bsrv;
	}
	function get_browser_version_string(){ return $this->bsrv_string; }
	function get_useragent(){ return $this->all; }// Pickles Framework 0.1.12 追加
	function get_browser_group(){ return $this->group; }
	function get_os_name(){ return $this->os; }
	function get_os_version(){ return $this->osv; }
	function get_ct(){ return $this->ct; }
	function get_enduserclass(){ return $this->enduserclass; }
	function get_device_id(){ return $this->device_id; }
	function get_device_spec( $key = null ){
		// Pickles Framework 0.1.2 追加

		#	【 MEMO：端末詳細スペックのキーと意味の一覧 】
		#	screen_width
		#		ブラウザ画面の横幅(px)
		#	screen_height
		#		ブラウザ画面の縦幅(px)
		#	screen_max_width
		#		ブラウザ画面の横幅の上限(px)
		#	screen_max_height
		#		ブラウザ画面の縦幅の上限(px)
		#	screen_min_width
		#		ブラウザ画面の横幅の下限(px)
		#	screen_min_height
		#		ブラウザ画面の縦幅の下限(px)
		#	color_number
		#		使用可能な色の数
		#	cache_size
		#		ロード可能なページ容量の上限(バイト)

		if( !is_null( $key ) ){
			return $this->device_spec[$key];
		}
		return $this->device_spec;
	}
	function is_enable_cookie(){ return $this->enable_cookie; }
	function is_enable_http_referer(){ return $this->enable_http_referer; }

}

?>