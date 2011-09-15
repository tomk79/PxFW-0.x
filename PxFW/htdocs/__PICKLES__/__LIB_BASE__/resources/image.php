<?php

#	Copyright (C)Tomoya Koyanagi.
#	Last Update : 0:21 2008/11/29

#******************************************************************************************************************
#	画像ファイルを扱う
class base_resources_image{
	#	このクラスはGDライブラリを利用して画像ファイルの変換や加工を行います。
	#	GDライブラリが無効な環境では動作しません。
	#	本クラスをインスタンス化した後、enable()メソッドを通じて確認してください。

	var $conf;
	var $req;
	var $dbh;
	var $errors;

	var $imagepath;
	var $imageinfo;
	var $imageresource = null;//画像リソース

	var $jpeg_quality = 75;
		#	JPEG生成時の画質
	var $png_quality = 0;
		#	PNG生成時の画質

	#----------------------------------------------------------------------------
	#	コンストラクタ
	function base_resources_image( &$conf , &$req , &$dbh , &$errors ){
		$this->conf = &$conf;
		$this->req = &$req;
		$this->dbh = &$dbh;
		$this->errors = &$errors;

		$this->setup( &$conf , &$req , &$dbh , &$errors );
	}

	#--------------------------------------
	#	セットアップの追加事項(必要に応じて拡張して使用)
	function setup( &$conf , &$req , &$dbh , &$errors ){}

	#--------------------------------------
	#	画像編集機能群(このクラスの機能)が使用可能か調べる
	function enable(){
		if( !$this->is_active() ){ return false; }
		return	true;
	}

	#--------------------------------------
	#	GDモジュールがアクティブか調べる
	function is_active(){
		if( !function_exists( 'gd_info' ) ){ return false; }
		return	true;
	}

	#--------------------------------------
	#	パスを受け取り、イメージをメンバーに追加
	function set_image( $path ){
		if( !$this->dbh->is_file( $path ) ){ return false; }
		$this->imagepath = $path;
		$this->imageinfo = $this->getimagesize();
		if( $this->imageinfo['mime'] == 'image/jpeg' ){
			$this->imageresource = imagecreatefromjpeg( $this->imagepath );
		}elseif( $this->imageinfo['mime'] == 'image/gif' ){
			$this->imageresource = imagecreatefromgif( $this->imagepath );
		}elseif( $this->imageinfo['mime'] == 'image/png' ){
			$this->imageresource = imagecreatefrompng( $this->imagepath );
		}elseif( $this->imageinfo['mime'] == 'image/x-bitmap' ){
			$this->imageresource = imagecreatefromwbmp( $this->imagepath );
		}else{
			return	false;
		}
		return	true;
	}

	#--------------------------------------
	#	新しい画像を作成する
	function create_newimage( $path , $width , $height , $mime ){
		#	23:03 2007/11/11 Pickles Framework 0.1.13 で、とりあえず実装してみた。
		$this->imagepath = $path;
		if( is_callable( 'imagecreatetruecolor' ) ){
			$this->imageresource = @imagecreatetruecolor( $width , $height );
		}else{
			$this->imageresource = @imagecreate( $width , $height );
		}
		if( $this->imageresource === false ){
			return	false;
		}
		$this->imageinfo = array();
		$this->imageinfo[0] = intval($width);
		$this->imageinfo[1] = intval($height);
		$this->imageinfo['mime'] = $mime;
		return	true;
	}

	#--------------------------------------
	#	MIMEタイプをセットする
	function set_mime( $mime ){
		#	23:12 2007/11/11 Pickles Framework 0.1.13 で追加。
		if( !strlen( $mime ) ){ return false; }
		$mime = strtolower( $mime );
		switch( $mime ){
			#	MIMEタイプじゃなくて、拡張子で指定されたら、
			#	相応しいMIMEタイプに変えてあげる。
			case 'jpg':
			case 'jpeg':
			case 'jpe':
				$mime = 'image/jpeg';break;
			case 'gif':
				$mime = 'image/gif';break;
			case 'png':
				$mime = 'image/png';break;
			case 'bmp':
			case 'xbm':
				$mime = 'image/x-bitmap';break;
		}
		$this->imageinfo['mime'] = $mime;
		return	true;
	}

	#--------------------------------------
	#	編集完了後のバイナリデータを取得
	function get_bin(){
		#	必ず、saveimage() を先にコールする必要があります。
		#	保存せずにバイナリを取り出す方法は提供されていません。
		if( !$this->dbh->is_file( $this->imagepath ) ){ return false; }
		return	$this->dbh->file_get_contents( $this->imagepath );
	}

	#--------------------------------------
	#	$binを保存する
	function saveimage(){
		if( $this->imageinfo['mime'] == 'image/jpeg' ){
			$this->imageresource = imagejpeg( $this->imageresource , $this->imagepath , $this->jpeg_quality );
		}elseif( $this->imageinfo['mime'] == 'image/gif' ){
			$this->imageresource = imagegif( $this->imageresource , $this->imagepath );
		}elseif( $this->imageinfo['mime'] == 'image/png' ){
			$this->imageresource = imagepng( $this->imageresource , $this->imagepath , $this->png_quality );
		}elseif( $this->imageinfo['mime'] == 'image/x-bitmap' ){
			$this->imageresource = imagewbmp( $this->imageresource , $this->imagepath );
		}else{
			#	それ以外なら、全部JPEGで保存しちゃう。
			#	23:03 2007/11/11 Pickles Framework 0.1.13 で仕様変更。
			$this->imageresource = imagejpeg( $this->imageresource , $this->imagepath , $this->jpeg_quality );
		}
		return	true;
	}






	#--------------------------------------
	#	画像のサイズを測る
	function getimagesize(){
		if( !$this->is_active() ){ return false; }
		$size = getimagesize( $this->imagepath );
		return	$size;
	}









	#--------------------------------------
	#	直線を描画する
	function draw_line( $start_x , $start_y , $end_x , $end_y , $color = '000000' ){
		#	4:36 2008/10/21 Pickles Framework 0.5.0 追加
		$colorNumber = imagecolorallocate(
			$this->imageresource ,
			eval( 'return 0x'.substr( $color , 0 , 2 ).';' ) ,
			eval( 'return 0x'.substr( $color , 2 , 2 ).';' ) ,
			eval( 'return 0x'.substr( $color , 4 , 2 ).';' )
		);
		if( $colorNumber === false || $colorNumber === -1 ){
			return	false;
		}

		$result = imageline(
			$this->imageresource ,
			intval( $start_x ) ,
			intval( $start_y ) ,
			intval( $end_x ) ,
			intval( $end_y ) ,
			$colorNumber
		);

		return	$result;
	}

	#--------------------------------------
	#	文字を書き込む
	function write_text( $TEXT , $start_x , $start_y , $color = '000000' , $option = array() ){
		#	15:29 2008/10/22 Pickles Framework 0.5.0 追加
		#	GDの機能を使用。
		#	$option['font'] は、1～5の数値で指定。(GD依存)
		if( !strlen( $color ) ){
			$color = '000000';
		}
		$colorNumber = imagecolorallocate(
			$this->imageresource ,
			eval( 'return 0x'.substr( $color , 0 , 2 ).';' ) ,
			eval( 'return 0x'.substr( $color , 2 , 2 ).';' ) ,
			eval( 'return 0x'.substr( $color , 4 , 2 ).';' )
		);
		if( $colorNumber === false || $colorNumber === -1 ){
			return	false;
		}

		$font = intval( $option['font'] );
		if( !$font ){ $font = 2; }
		$result = imagestring(
			$this->imageresource ,
			$font ,
			intval( $start_x ) ,
			intval( $start_y ) ,
			$TEXT ,
			$colorNumber
		);

		return	$result;
	}

	#--------------------------------------
	#	楕円を描画する
	function ellipse( $cx , $cy , $width , $height , $fill_color = null , $border_color = null ){
		#	20:31 2008/11/07 Pickles Framework 0.5.1 追加
		#	GDの機能を使用。
		if( strlen( $fill_color ) ){
			$fill_colorNumber = imagecolorallocate(
				$this->imageresource ,
				eval( 'return 0x'.substr( $fill_color , 0 , 2 ).';' ) ,
				eval( 'return 0x'.substr( $fill_color , 2 , 2 ).';' ) ,
				eval( 'return 0x'.substr( $fill_color , 4 , 2 ).';' )
			);
			if( $fill_colorNumber === false || $fill_colorNumber === -1 ){
				return	false;
			}
		}
		if( strlen( $border_color ) ){
			$border_colorNumber = imagecolorallocate(
				$this->imageresource ,
				eval( 'return 0x'.substr( $border_color , 0 , 2 ).';' ) ,
				eval( 'return 0x'.substr( $border_color , 2 , 2 ).';' ) ,
				eval( 'return 0x'.substr( $border_color , 4 , 2 ).';' )
			);
			if( $border_colorNumber === false || $border_colorNumber === -1 ){
				return	false;
			}
		}

		$result = true;
		if( strlen( $fill_color ) ){
			if( !imagefilledellipse( $this->imageresource , intval( $cx ) , intval( $cy ) , intval( $width ) , intval( $height ) , $fill_colorNumber ) ){
				$result = false;
			}
		}
		if( strlen( $border_color ) ){
			if( !imageellipse( $this->imageresource , intval( $cx ) , intval( $cy ) , intval( $width ) , intval( $height ) , $border_colorNumber ) ){
				$result = false;
			}
		}
		return	$result;
	}

	#--------------------------------------
	#	矩形を描画する
	function rectangle( $x1 , $y1 , $x2 , $y2 , $fill_color = null , $border_color = null ){
		#	20:31 2008/11/07 Pickles Framework 0.5.1 追加
		#	GDの機能を使用。

		if( strlen( $fill_color ) ){
			$fill_colorNumber = imagecolorallocate(
				$this->imageresource ,
				eval( 'return 0x'.substr( $fill_color , 0 , 2 ).';' ) ,
				eval( 'return 0x'.substr( $fill_color , 2 , 2 ).';' ) ,
				eval( 'return 0x'.substr( $fill_color , 4 , 2 ).';' )
			);
			if( $fill_colorNumber === false || $fill_colorNumber === -1 ){
				return	false;
			}
		}
		if( strlen( $border_color ) ){
			$border_colorNumber = imagecolorallocate(
				$this->imageresource ,
				eval( 'return 0x'.substr( $border_color , 0 , 2 ).';' ) ,
				eval( 'return 0x'.substr( $border_color , 2 , 2 ).';' ) ,
				eval( 'return 0x'.substr( $border_color , 4 , 2 ).';' )
			);
			if( $border_colorNumber === false || $border_colorNumber === -1 ){
				return	false;
			}
		}

		$result = true;
		if( strlen( $fill_color ) ){
			if( !imagefilledrectangle( $this->imageresource , intval( $x1 ) , intval( $y1 ) , intval( $x2 ) , intval( $y2 ) , $fill_colorNumber ) ){
				$result = false;
			}
		}
		if( strlen( $border_color ) ){
			if( !imagerectangle( $this->imageresource , intval( $x1 ) , intval( $y1 ) , intval( $x2 ) , intval( $y2 ) , $border_colorNumber ) ){
				$result = false;
			}
		}
		return	$result;
	}

	#--------------------------------------
	#	多角形を描画する
	function polygon( $points , $fill_color = null , $border_color = null ){
		#	20:31 2008/11/07 Pickles Framework 0.5.1 追加
		#	GDの機能を使用。
		if( !is_array( $points ) ){ return false; }//ポイントリストが配列じゃなかったらダメ
		if( count( $points ) < 6 ){ return false; }//ポイントリストの数は最低6件(頂点3つ分)ないといけない
		if( count( $points ) % 2 ){ return false; }//ポイントリストの数は偶数じゃないといけない

		if( strlen( $fill_color ) ){
			$fill_colorNumber = imagecolorallocate(
				$this->imageresource ,
				eval( 'return 0x'.substr( $fill_color , 0 , 2 ).';' ) ,
				eval( 'return 0x'.substr( $fill_color , 2 , 2 ).';' ) ,
				eval( 'return 0x'.substr( $fill_color , 4 , 2 ).';' )
			);
			if( $fill_colorNumber === false || $fill_colorNumber === -1 ){
				return	false;
			}
		}
		if( strlen( $border_color ) ){
			$border_colorNumber = imagecolorallocate(
				$this->imageresource ,
				eval( 'return 0x'.substr( $border_color , 0 , 2 ).';' ) ,
				eval( 'return 0x'.substr( $border_color , 2 , 2 ).';' ) ,
				eval( 'return 0x'.substr( $border_color , 4 , 2 ).';' )
			);
			if( $border_colorNumber === false || $border_colorNumber === -1 ){
				return	false;
			}
		}

		$result = true;
		if( strlen( $fill_color ) ){
			if( !imagefilledpolygon( $this->imageresource , $points , intval( count( $points )/2 ) , $fill_colorNumber ) ){
				$result = false;
			}
		}
		if( strlen( $border_color ) ){
			if( !imagepolygon( $this->imageresource , $points , intval( count( $points )/2 ) , $border_colorNumber ) ){
				$result = false;
			}
		}
		return	$result;
	}

	#--------------------------------------
	#	幅に合わせてリサイズする
	#	(16:12 2007/11/11 Pickles Framework 0.1.13 追加)
	function fit2width( $width , $option = array() ){
		$width = intval( $width );
		if( !$width ){ return false; }

		$imageSize = $this->getimagesize();
		$imageOriginalWidth = intval( $imageSize[0] );
		$imageOriginalHeight = intval( $imageSize[1] );
		$imageOriginalAspectRatio = $imageOriginalWidth/$imageOriginalHeight;

		$resizeRate = $width/$imageOriginalWidth;
		$height = intval( $imageOriginalHeight*$resizeRate );

		return	$this->resize( $width , $height , $option );
	}


	#--------------------------------------
	#	高さに合わせてリサイズする
	#	(16:12 2007/11/11 Pickles Framework 0.1.13 追加)
	function fit2height( $height , $option = array() ){
		$height = intval( $height );
		if( !$height ){ return false; }

		$imageSize = $this->getimagesize();
		$imageOriginalWidth = intval( $imageSize[0] );
		$imageOriginalHeight = intval( $imageSize[1] );
		$imageOriginalAspectRatio = $imageOriginalWidth/$imageOriginalHeight;

		$resizeRate = $height/$imageOriginalHeight;
		$width = intval( $imageOriginalWidth*$resizeRate );

		return	$this->resize( $width , $height , $option );
	}


	#--------------------------------------
	#	リサイズする
	function resize( $width = 0 , $height = 0 , $option = array() ){
		if( !$this->is_active() ){ return false; }

		#	$option['method'] = 画像のリサイズの仕方を指示
		#		trim(規定値) = オリジナル画像のアスペクト比(縦横比)は変えず、指定サイズからはみ出した部分を削る。
		#		fill = オリジナル画像のアスペクト比は変えず、指定サイズに足りない部分を白色で埋める。
		#		liquid = アスペクト比を変更して指定サイズにフィットさせる。

		if( !$this->dbh->is_file( $this->imagepath ) ){ return	false; }

		$resize_method = 'trim';
		switch( strtolower( $option['method'] ) ){
			case 'trim':
			case 'fill':
			case 'liquid':
				$resize_method = strtolower( $option['method'] );
				break;
		}

		$flg_antialias = true;
		if( !is_null( $option['antialias'] ) ){
			if( empty( $option['antialias'] ) ){
				$flg_antialias = false;
			}
		}

		$imagesize = $this->getimagesize();

		if( !$width ){
			$width = $imagesize[0];
		}
		if( !$height ){
			$height = $imagesize[1];
		}

		$tmpresource = imagecreatetruecolor( $width , $height );
//		imagefill( $tmpresource , 0 , 0 , 16 );

		if( $resize_method == 'trim' ){
			#	【はみ出した部分を削る】
			#	ペーストの開始座標
			$start_x = 0;
			$start_y = 0;
			$c_width = $width;
			$c_height = $height;

			$memo_a = $width/$height;
			$memo_b = $imagesize[0]/$imagesize[1];
			if( $memo_a == $memo_b ){
				#	縦横比が同じ
				$start_x = 0;
				$start_y = 0;
			}elseif( $memo_a < $memo_b ){
				#	オリジナルの方が横長
				$c_width = ( $height / $imagesize[1] ) * $imagesize[0];
				$start_x = -1 * ( $c_width / 2 ) + ( $width / 2 );
			}elseif( $memo_a > $memo_b ){
				#	オリジナルの方が縦長
				$c_height = ( $width / $imagesize[0] ) * $imagesize[1];
				$start_y = -1 * ( $c_height / 2 ) + ( $height / 2 );
			}
			if( $flg_antialias ){
				imagecopyresampled(
					$tmpresource ,
					$this->imageresource ,
					$start_x ,
					$start_y ,
					0 ,
					0 ,
					$c_width ,
					$c_height ,
					$imagesize[0] ,
					$imagesize[1] 
				);
			}else{
				imagecopyresized(
					$tmpresource ,
					$this->imageresource ,
					$start_x ,
					$start_y ,
					0 ,
					0 ,
					$c_width ,
					$c_height ,
					$imagesize[0] ,
					$imagesize[1] 
				);
			}
		}elseif( $resize_method == 'fill' ){
			#	【足りない部分を白色で埋める】
			#	ペーストの開始座標
			$start_x = 0;
			$start_y = 0;
			$c_width = $width;
			$c_height = $height;

			$memo_a = $width/$height;
			$memo_b = $imagesize[0]/$imagesize[1];
			if( $memo_a == $memo_b ){
				#	縦横比が同じ
				$start_x = 0;
				$start_y = 0;
			}elseif( $memo_a < $memo_b ){
				#	オリジナルの方が横長
				$c_height = ( $width / $imagesize[0] ) * $imagesize[1];
				$start_y = -1 * ( $c_height / 2 ) + ( $height / 2 );
			}elseif( $memo_a > $memo_b ){
				#	オリジナルの方が縦長
				$c_width = ( $height / $imagesize[1] ) * $imagesize[0];
				$start_x = -1 * ( $c_width / 2 ) + ( $width / 2 );
			}
			if( $flg_antialias ){
				imagecopyresampled(
					$tmpresource ,
					$this->imageresource ,
					$start_x ,
					$start_y ,
					0 ,
					0 ,
					$c_width ,
					$c_height ,
					$imagesize[0] ,
					$imagesize[1] 
				);
			}else{
				imagecopyresized(
					$tmpresource ,
					$this->imageresource ,
					$start_x ,
					$start_y ,
					0 ,
					0 ,
					$c_width ,
					$c_height ,
					$imagesize[0] ,
					$imagesize[1] 
				);
			}
		}elseif( $resize_method == 'liquid' ){
			#	【指定サイズに無理やりフィットさせる】
			if( $flg_antialias ){
				imagecopyresampled(
					$tmpresource ,
					$this->imageresource ,
					0 ,
					0 ,
					0 ,
					0 ,
					$width ,
					$height ,
					$imagesize[0] ,
					$imagesize[1] 
				);
			}else{
				imagecopyresized(
					$tmpresource ,
					$this->imageresource ,
					0 ,
					0 ,
					0 ,
					0 ,
					$width ,
					$height ,
					$imagesize[0] ,
					$imagesize[1] 
				);
			}
		}

		$this->imageresource = $tmpresource;

		return	true;
	}


	#--------------------------------------
	#	画像を合成する
	function merge( $image_src , $dst_x , $dst_y , $src_x , $src_y , $src_w , $src_h , $pct = 100 ){
		#	Pickles Framework 0.5.2 追加。0:21 2008/11/29
		#	$image_src に指定された画像 $image_src を重ね合わせます。
		#	GDの機能 imagecopymerge() を使用。

		#--------------------------------------
		#	合成画像の透明度を調整
		$pct = intval( $pct );
		if( $pct <= 0 ){
			#	ゼロ以下だと重ねられないのでおしまい。
			return	false;
		}
		if( $pct > 100 ){
			#	100は超えられないので、100に丸める。
			$pct = 100;
		}
		#	/ 合成画像の透明度を調整
		#--------------------------------------

		$src_resource = null;
		if( is_string( $image_src ) ){
			#	[文字列型]
			#	ソースファイルの置き場所として解釈
			if( !$this->dbh->is_file( $image_src ) || !@is_readable( $image_src ) ){
				return	false;
			}
			$thisClassName = get_class( $this );
			$tmp_image_obj = new $thisClassName( &$this->conf , &$this->req , &$this->dbh , &$this->errors );
			if( !$tmp_image_obj->set_image( $image_src ) ){
				return	false;
			}
			if( !is_resource( $tmp_image_obj->imageresource ) ){
				return	false;
			}
			$src_resource = &$tmp_image_obj->imageresource;

		}elseif( is_object( $image_src ) ){
			#	[オブジェクト型]
			#	画像をロードしたbase_resources_imageのインスタンスとして解釈
			if( !is_resource( $image_src->imageresource ) ){
				return	false;
			}
			$src_resource = &$image_src->imageresource;

		}elseif( is_resource( $image_src ) ){
			#	[リソース型]
			#	画像をロードしたbase_resources_imageのインスタンスとして解釈
			$src_resource = &$image_src;

		}else{
			#	それ以外の型
			return	false;

		}

		if( !is_resource( $src_resource ) ){
			#	リソースを取得できていなかったら。
			return	false;
		}

		#--------------------------------------
		#	画像を合成する
		$result = imagecopymerge(
			$this->imageresource ,
			$src_resource ,
			$dst_x ,
			$dst_y ,
			$src_x ,
			$src_y ,
			$src_w ,
			$src_h ,
			$pct
		);

		if( !$result ){
			return	false;
		}
		return	true;
	}


	#--------------------------------------
	#	切り取る
	function trim( $start_x , $start_y , $width , $height , $option = array() ){
		if( !$this->is_active() ){ return false; }
		if( !$this->dbh->is_file( $this->imagepath ) ){ return	false; }

		$flg_antialias = true;
		if( !is_null( $option['antialias'] ) ){
			if( empty( $option['antialias'] ) ){
				$flg_antialias = false;
			}
		}

		$tmpresource = imagecreatetruecolor( $width , $height );

		if( $flg_antialias ){
			imagecopyresampled(
				$tmpresource ,
				$this->imageresource ,
				0 ,
				0 ,
				$start_x ,
				$start_y ,
				$width ,
				$height ,
				$width ,
				$height 
			);
		}else{
			imagecopyresized(
				$tmpresource ,
				$this->imageresource ,
				0 ,
				0 ,
				$start_x ,
				$start_y ,
				$width ,
				$height ,
				$width ,
				$height 
			);
		}

		$this->imageresource = $tmpresource;

		return	true;
	}

}

?>