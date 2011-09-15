<?php

#	Copyright (C)Tomoya Koyanagi.
#	Last Update : 2:56 2009/01/05

###################################################################################################################
#	ロールオーバーのJavaScriptを生成する
class base_resources_jsctrl_rollover{
	#	[ Pickles Framework 0.1.4 ]
	#		3:01 2007/07/19
	#		接頭辞 PxRo のオリジナルスクリプトに変更。
	#		しかし、比較的最近のブラウザ(document.getElementByIdが存在するもの)にしか対応していない。
	#		(PxRo は Pickles Rollover の略)

	var $preloaded_imagelist = array();
	var $imagenumber = 0;
	var $last_img_name = '';

	#--------------------------------------
	#	基本スクリプトを生成
	function head(){
		#	このJavaScriptコードは、<head>セクション内に固定で出力してください。

		ob_start();?><script type="text/javascript"><?php ob_end_clean();
		ob_start();?>
			function PxRo_preloadImage(){
				if( document.getElementById && document.images ){
					var args = PxRo_preloadImage.arguments;
					var i=0;
					document.PxRo_preloadedImages = new Array();
					for( i = 0; args[i]; i ++ ){
						document.PxRo_preloadedImages[i] = new Image;
						document.PxRo_preloadedImages[i].src = args[i];
					}
				}

			}
			function PxRo_mouseOver( targetImageId , imageUrl ){
				if( document.getElementById && document.images ){
					document.PxRo_hoverMemo = new Array();
					document.PxRo_hoverMemo[0] = targetImageId;
					document.PxRo_hoverMemo[1] = document.getElementById(targetImageId).src;
					document.getElementById(targetImageId).src = imageUrl;
				}
			}
			function PxRo_mouseOut(){
				if( document.getElementById && document.images ){
					document.getElementById(document.PxRo_hoverMemo[0]).src = document.PxRo_hoverMemo[1];
				}
			}
		<?php
		$RTN = ob_get_clean();
		ob_start();?></script><?php ob_end_clean();
		$RTN = preg_replace( '/\r\n|\r|\n|\t/' , '' , $RTN );
		return	$RTN;
	}

	#--------------------------------------
	#	プリロードする画像をリストに追加
	function putimage( $imgname ){
		$this->preloaded_imagelist[$imgname] = $imgname;
		return	true;
	}

	#--------------------------------------
	#	最後に使用した画像名を取得
	function get_last_imagename(){
		return	$this->last_img_name;
	}

	#--------------------------------------
	#	リンクタグのスクリプトを生成
	function link( $imgname , $handlename = null ){
		$this->imagenumber ++;

		#	このJavaScriptコードは、
		#	ロールオーバーする<a>タグの属性として出力してください。

		if( !strlen( $handlename ) ){
			$handlename = 'PxRoImage'.$this->imagenumber;
		}
		$this->last_img_name = $handlename;

		$this->putimage( $imgname );
		$RTN .= ' onmouseover="PxRo_mouseOver(\''.htmlspecialchars($handlename).'\',\''.htmlspecialchars($imgname).'\');" onmouseout="PxRo_mouseOut();"';
		return	$RTN;
	}

	#--------------------------------------
	#	プリロードスクリプトを生成
	function preload(){
		#	このJavaScriptコードは、
		#	<body>タグのonloadイベントでコールされるように出力してください。

		if( !is_array( $this->preloaded_imagelist ) || !count( $this->preloaded_imagelist ) ){
			return	'';
		}

		$RTN = '';
		$RTN .= 'PxRo_preloadImage(';
		$i = 0;
		$contents = $this->preloaded_imagelist;
		$count = count( $contents );
		foreach( $contents as $Line ){
			$i ++;
			$RTN .= text::data2jstext( $Line );
			if( $count > $i ){
				$RTN .= ',';
			}
		}
		$RTN .= ');';
		return	$RTN;
	}

}





?>