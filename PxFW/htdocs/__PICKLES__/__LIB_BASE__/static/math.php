<?php

#	Copyright (C)Tomoya Koyanagi.
#	LastUpdate : 14:45 2005/03/24

#****************************************************************************
#	数値を扱うクラス
#	※インスタンス化せず、スタティックに使用してください。
class base_static_math{

	#--------------------------------------
	#	切捨てを行う。
	function rounddown( $value , $keta = 0 ){
		#	$valueを、$keta桁で切り捨て
		$keta = floor( $keta );
		$b = 1;
		if( $keta > 0 ){
			while( $keta ){
				$keta --;
				$b = $b * 10;
			}
		}elseif( $keta < 0 ){
			while( $keta ){
				$keta ++;
				$b = $b / 10;
			}
		}
		$value = $value * $b;
		$value = floor( $value );
		$value = $value / $b;
		return	$value;
	}

}

?>