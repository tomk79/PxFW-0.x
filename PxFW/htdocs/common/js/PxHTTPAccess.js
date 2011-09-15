/* HTTP通信オブジェクトクラス */
function PxHTTPAccess(){
	this.gotcontent = '- preset text -';

	this.setup = function(){
		this.setcont('');
		this.gotcontent = '- preset text -';
		this.httpaccess = this.createHTTPAccessObject();
	}

	/* HTTPアクセスオブジェクトを作成 */
	this.createHTTPAccessObject = function(){
		httpaccess = false;
		try{
			httpaccess = new ActiveXObject("Msxml2.XMLHTTP");
		}catch( e ){
			try{
				httpaccess = new ActiveXObject("Microsoft.XMLHTTP");
			}catch( E ){
				httpaccess = false;
			}
		}
		if( !httpaccess && typeof XMLHttpRequest != 'undefined' ){
			httpaccess = new XMLHttpRequest();
		}
		return httpaccess;
	}

	this.getHttpContents = function( target_url , type ){
		if( type == 'xml' ){
			return	this.getHttpContents_as_xml( target_url , this );
		}
		return	this.getHttpContents_as_text( target_url , this );
	}
	this.getHttpContents_as_text = function( target_url , gotobject ){
		var httpaccess = this.httpaccess;
		var RTN = '';
		var i = 0;
		httpaccess.open( "GET", target_url );
		httpaccess.onreadystatechange = function(){
			if( httpaccess.readyState == 4 && httpaccess.status == 200 ){
				gotobject.setcont( httpaccess.responseText );
				gotobject.httponload( gotobject.getcont() );
			}
		}
		httpaccess.send(null);
		return	null;
	}
	this.getHttpContents_as_xml = function( target_url , gotobject ){
		var httpaccess = this.httpaccess;
		var RTN = '';
		var i = 0;
		httpaccess.open( "GET", target_url );
		httpaccess.onreadystatechange = function(){
			if( httpaccess.readyState == 4 && httpaccess.status == 200 ){
				gotobject.setcont( httpaccess.responseXML );
				gotobject.httponload( gotobject.getcont() );
			}
		}
		httpaccess.send(null);
		return	null;
	}

	this.setcont = function( CONT ){
		this.gotcontent = CONT;
	}
	this.getcont = function(){
		return	this.gotcontent;
	}

	/* 取得したリソースを処理する */
	this.httponload = function( CONT ){
		if( !CONT.match( new RegExp("^<\!--ajax-->") ) ){
			CONT = '<div class="ttr">ログイン状態が解除されました。</div>';
		}
	}
}