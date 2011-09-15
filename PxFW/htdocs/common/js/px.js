/* ----------------------------------------------------------------------------
	px object (Common Functions) @UTF-8
	Last Update : 22:32 2009/11/18
---------------------------------------------------------------------------- */
window.px = new function(){
	//--------------------------------------
	//	ポップアップウィンドウを開く
	this.openPopup = function( url ){
		window.open( url , '_blank' , 'width=600,height=420,scrollbars=yes,resizable=yes' );
	}

	//--------------------------------------
	//	文字列の幅を調べる
	this.strlen = function( $string ){
		if( $string === undefined ){ return 0; }
		if( $string === null ){ return 0; }
		$string = $string + '';
		return $string.length;
	}

	//--------------------------------------
	//	HTMLの特殊文字を変換する
	this.htmlspecialchars = function( $string ){
		if( $string === undefined ){ return ''; }
		if( $string === null ){ return ''; }
		$string = $string + '';
		$string = $string.replace( new RegExp("&", "gi")  , '&amp;'  );
		$string = $string.replace( new RegExp("<", "gi")  , '&lt;'   );
		$string = $string.replace( new RegExp(">", "gi")  , '&gt;'   );
		$string = $string.replace( new RegExp("\"", "gi") , '&quot;' );
		return $string;
	}

	//--------------------------------------
	//	HTMLの特殊文字変換を戻す
	this.htmlspecialchars_decode = function( $string ){
		if( $string === undefined ){ return ''; }
		if( $string === null ){ return ''; }
		$string = $string + '';
		$string = $string.replace( new RegExp("&lt;", "gi")  , '<'  );
		$string = $string.replace( new RegExp("&gt;", "gi")  , '>'   );
		$string = $string.replace( new RegExp("&quot;", "gi")  , '"'   );
		$string = $string.replace( new RegExp("&amp;", "gi") , '&' );
		return $string;
	}

	//--------------------------------------
	//	文字列型かどうか調べる
	this.is_string = function( val ){
		var type = typeof( val );
		if( type.toLowerCase() != 'string' ){ return false; }
		return true;
	}

	//--------------------------------------
	//	整数型かどうか調べる
	this.is_int = function( val ){
		var type = typeof( val );
		if( type.toLowerCase() != 'number' ){ return false; }
		return true;
	}

	//--------------------------------------
	//	オブジェクト型かどうか調べる
	this.is_object = function( val ){
		if( val === undefined ){ return false; }
		if( val === null ){ return false; }
		var type = typeof( val );
		if( type.toLowerCase() != 'object' ){ return false; }
		return true;
	}

	//--------------------------------------
	//	配列型かどうか調べる
	this.is_array = function( val ){
		if( val === undefined ){ return false; }
		if( val === null ){ return false; }
		var type = typeof( val );
		if( type.toLowerCase() != 'object' ){ return false; }
		return true;
	}

};
