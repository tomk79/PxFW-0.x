<?php

#	Copyright (C)Tomoya Koyanagi.
#	Last Update : 1:06 2009/10/29

#******************************************************************************************************************
#	時間を扱うクラス(static)
#	※インスタンス化せず、スタティックに使用してください。
class base_static_time{

	#--------------------------------------
	#	date型の値を、time()形式に変換
	function date2int( $datetime = null ){
		if( !strlen( $datetime ) ){
			#	省略されたら、null
			#	Pickles Framework 0.4.7 仕様変更。
			#	これ以前のバージョンでは、現在時刻を返していました。
			return null;
		}
		if( is_string( $datetime ) ){//←Pickles Framework 0.5.8
			if( preg_match( '/^([0-9]+)(?:-|\/|\.)([0-9]+)(?:-|\/|\.)([0-9]+)(?:\s+([0-9]+)(?:(?::|\.)?([0-9]+)(?:(?::|\.)?([0-9]+))?)?)?$/' , $datetime , $res ) ){
				#	YYYY-MM-DD HH:ii:ss
				return	mktime( 0 , 0 , 0 , intval($res[2]) , intval($res[3]) , intval($res[1]) );
			}
			if( preg_match( '/^(?:([0-9]+)(?:(?::|\.)?([0-9]+)(?:(?::|\.)?([0-9]+))?)?\s+)?([0-9]+)(?:-|\/|\.)([0-9]+)(?:-|\/|\.)([0-9]+)$/' , $datetime , $res ) ){
				#	HH:ii:ss YYYY-MM-DD
				return	mktime( 0 , 0 , 0 , intval($res[5]) , intval($res[6]) , intval($res[4]) );
			}
			if( preg_match( '/^([0-9]{4})([0-9]{2})([0-9]{2})(?:(?: |_|\-)([0-9]{2})(?:([0-9]{2})(?:([0-9]{2}))?)?)?$/' , $datetime , $res ) ){
				#	YYYYMMDD_HHiiss
				return	mktime( 0 , 0 , 0 , intval($res[2]) , intval($res[3]) , intval($res[1]) );
			}
			if( preg_match( '/^([0-9]+)(?:年)([0-9]+)(?:月)([0-9]+)(?:日)(?:\s*([0-9]+)(?:時)(?:([0-9]+)(?:分)(?:([0-9]+)(?:秒))?)?)?$/' , $datetime , $res ) ){
				#	YYYY年MM月DD日 HH時ii分ss秒
				return	mktime( 0 , 0 , 0 , intval($res[2]) , intval($res[3]) , intval($res[1]) );
			}
			if( preg_match( '/^([0-9]{4})\-([0-9]{2})\-([0-9]{2})T([0-9]{2})\:([0-9]{2})(?:\:([0-9]{2})(?:([\-\+])([0-9]{2}\:([0-9]{2})))?)?$/' , $datetime , $res ) ){
				#	YYYY-MM-DDTHH:ii:ss[+=]timezone (W3C-DTF)
				#	PxFW 0.6.6 追加
				$time = gmmktime( intval($res[4]) , intval($res[5]) , intval($res[6]) , intval($res[2]) , intval($res[3]) , intval($res[1]) );
				if( strlen( $res[7] ) ){
					$tmp_jisa = ( intval( $res[8] )*60*60 ) + ( intval( $res[9] )*60 );
					if( $res[7] == '-' ){ $time = $time + $tmp_jisa; }elseif( $res[7] == '+' ){ $time = $time - $tmp_jisa; }
				}
				return mktime( 0 , 0 , 0 , date('m',$time) , date('d',$time) , date('y',$time) );
			}
		}

		if( preg_match( '/^[0-9]+$/' , $datetime ) ){
			#	数字だけで構成されていたら、数値型にしちゃう
			$datetime = mktime( 0 , 0 , 0 , intval(date('m',$datetime)) , intval(date('d',$datetime)) , intval(date('Y',$datetime)) );
		}elseif( is_string( $datetime ) ){
			#	PxFW 0.6.3 追加
			$datetime = strtotime( $datetime );
			$datetime = mktime( 0 , 0 , 0 , intval(date('m',$datetime)) , intval(date('d',$datetime)) , intval(date('Y',$datetime)) );
		}
		if( is_int( $datetime ) ){
			#	数値型だったら
			return $datetime;
		}

		#	どれにも該当しなければ false
		return false;
	}
	#--------------------------------------
	#	datetime型の値を、time()形式に変換
	function datetime2int( $datetime = null ){
		if( !strlen( $datetime ) ){
			#	省略されたら、null
			#	Pickles Framework 0.4.7 仕様変更。
			#	これ以前のバージョンでは、現在時刻を返していました。
			return null;
		}
		if( is_string( $datetime ) ){//←Pickles Framework 0.5.8
			if( preg_match( '/^([0-9]+)(?:-|\/|\.)([0-9]+)(?:-|\/|\.)([0-9]+)(?:\s+([0-9]+)(?:(?::|\.)?([0-9]+)(?:(?::|\.)?([0-9]+))?)?)?$/' , $datetime , $res ) ){
				#	YYYY-MM-DD HH:ii:ss
				return	mktime( intval($res[4]) , intval($res[5]) , intval($res[6]) , intval($res[2]) , intval($res[3]) , intval($res[1]) );
			}
			if( preg_match( '/^(?:([0-9]+)(?:(?::|\.)?([0-9]+)(?:(?::|\.)?([0-9]+))?)?\s+)?([0-9]+)(?:-|\/|\.)([0-9]+)(?:-|\/|\.)([0-9]+)$/' , $datetime , $res ) ){
				#	HH:ii:ss YYYY-MM-DD
				return	mktime( intval($res[1]) , intval($res[2]) , intval($res[3]) , intval($res[5]) , intval($res[6]) , intval($res[4]) );
			}
			if( preg_match( '/^([0-9]{4})([0-9]{2})([0-9]{2})(?:(?: |_|\-)([0-9]{2})(?:([0-9]{2})(?:([0-9]{2}))?)?)?$/' , $datetime , $res ) ){
				#	YYYYMMDD_HHiiss
				return	mktime( intval($res[4]) , intval($res[5]) , intval($res[6]) , intval($res[2]) , intval($res[3]) , intval($res[1]) );
			}
			if( preg_match( '/^([0-9]+)(?:年)([0-9]+)(?:月)([0-9]+)(?:日)(?:\s*([0-9]+)(?:時)(?:([0-9]+)(?:分)(?:([0-9]+)(?:秒))?)?)?$/' , $datetime , $res ) ){
				#	YYYY年MM月DD日 HH時ii分ss秒
				return	mktime( intval($res[4]) , intval($res[5]) , intval($res[6]) , intval($res[2]) , intval($res[3]) , intval($res[1]) );
			}
			if( preg_match( '/^([0-9]{4})\-([0-9]{2})\-([0-9]{2})T([0-9]{2})\:([0-9]{2})(?:\:([0-9]{2})(?:([\-\+])([0-9]{2}\:([0-9]{2})))?)?$/' , $datetime , $res ) ){
				#	YYYY-MM-DDTHH:ii:ss[+=]timezone (W3C-DTF)
				#	PxFW 0.6.6 追加
				$time = gmmktime( intval($res[4]) , intval($res[5]) , intval($res[6]) , intval($res[2]) , intval($res[3]) , intval($res[1]) );
				if( strlen( $res[7] ) ){
					$tmp_jisa = ( intval( $res[8] )*60*60 ) + ( intval( $res[9] )*60 );
					if( $res[7] == '-' ){ $time = $time + $tmp_jisa; }elseif( $res[7] == '+' ){ $time = $time - $tmp_jisa; }
				}
				return $time;
			}
		}

		if( preg_match( '/^[0-9]+$/' , $datetime ) ){
			#	数字だけで構成されていたら、数値型にしちゃう
			$datetime = intval( $datetime );
		}elseif( is_string( $datetime ) ){
			#	PxFW 0.6.3 追加
			$datetime = strtotime( $datetime );
		}
		if( is_int( $datetime ) ){
			#	数値型だったら
			return $datetime;
		}

		#	どれにも該当しなければ false
		return false;
	}
	#--------------------------------------
	#	time()形式の値を、date型に変換
	function int2date( $time = null ){
		if( !strlen( $time ) ){ $time = time(); }
		return	date( 'Y-m-d' , $time );
	}
	#--------------------------------------
	#	time()形式の値を、datetime型に変換
	function int2datetime( $time = null ){
		if( !strlen( $time ) ){ $time = time(); }
		return	date( 'Y-m-d H:i:s' , $time );
	}


	#----------------------------------------------------------------------------
	#	現在のマイクロ秒を返す
	function microtime(){
		#	Pickles Framework 0.1.10 base_static_text から引越しました。19:15 2007/10/13
		#	return	microtime( true );	//	←これはPHP5以降じゃないと使えないらしい。
		list( $microtime , $time ) = explode( ' ' , microtime() ); 
		return ( intval( $time ) + floatval( $microtime ) );
	}


	#----------------------------------------------------------------------------
	#	クーロン式時間設定の時間中かどうか判定
	function is_ontime( $timeset , $now = null ){

		if( !strlen( $timeset ) ){
			#	定形外の指定だったら、
			return	null;
		}
		if( !strlen( $now ) ){
			$now = time();
		}
		$now = intval( $now );

		$now_info = getdate( $now );

		$time_info = preg_split( '/(?:\r|\n|\t| )+/' , $timeset );

		$judge = array();

		$datas = array(
			'minutes'=>trim( $time_info[0] ),
			'hours'=>trim( $time_info[1] ),
			'mday'=>trim( $time_info[2]),
			'mon'=>trim( $time_info[3] ),
			'wday'=>trim( $time_info[4] ),
		);

		foreach( array_keys( $datas ) as $timekey ){
			$value = trim( $datas[$timekey] );
			if( !strlen( $value ) ){
				#	空白の場合はマッチする
				$judge[$timekey] = true;
				continue;
			}
			if( $value == '*' ){
				#	*は何にもマッチする
				$judge[$timekey] = true;
				continue;
			}
			if( $value == $now_info[$timekey] ){
				#	完全一致したら正
				$judge[$timekey] = true;
				continue;
			}

			#	列挙(カンマ区切り)の処理
			$values = explode( ',' , $value );
			foreach( $values as $value_line ){
				$value_line = trim( $value_line );
				if( $value_line == $now_info[$timekey] ){
					#	完全一致すれば、時間外
					$judge[$timekey] = true;continue 2;
				}
				#	範囲指定(ハイフン区切り)の処理
				list( $time_from , $time_to ) = explode( '-' , $value_line );
				if( trim( $time_from ) <= $now_info[$timekey] && $now_info[$timekey] <= trim( $time_to ) ){
					#	範囲内ならば、時間外
					$judge[$timekey] = true;continue 2;
				}
			}

		}

		if( count( $judge ) >= count( $datas ) ){
			#	全ての条件が真ならば
			#	サービス時間外
			return	true;
		}

		return	false;
	}

}

?>