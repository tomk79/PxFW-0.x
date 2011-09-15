/* ----------------------------------------------------------------------------
	Parse UserAgent Object
	Last Update : 23:20 2008/07/15
---------------------------------------------------------------------------- */
function PxUserAgent(){
	this.bsrUserAgent = navigator.userAgent;

	//	OS
	this.pName = navigator.platform.toLowerCase();
	if( this.pName.indexOf( "win" ) >= 0 ){
		this.UA_OS = 'Windows';
	}else if( this.pName.indexOf( "mac" ) >= 0 ){
		this.UA_OS = 'Macintosh';
	}else if( this.pName.indexOf( "palm" ) >= 0 ){
		this.UA_OS = 'Palm';
	}else{
		this.UA_OS = 'Unknown';
	}

	//	Browser Name
	this.aName = navigator.appName.toLowerCase();
	if( this.aName.indexOf( "opera" ) >= 0 || window.opera ){
		this.UA_BSR = 'Opera';
	}else if( this.aName.indexOf( "explorer" ) >= 0 ){
		this.UA_BSR = 'MSIE';
	}else if( this.aName.indexOf( "safari" ) >= 0 ){
		this.UA_BSR = 'Safari';
	}else if( this.aName.indexOf( "firefox" ) >= 0 ){
		this.UA_BSR = 'Gecko';
	}else if( this.aName.indexOf( "netscape" ) >= 0 ){
		if( document.getElementById ){
			this.UA_BSR = 'Gecko';
		}else{
			this.UA_BSR = 'Netscape';
		}
	}else if( this.aName.indexOf( "netfront" ) >= 0 ){
		this.UA_BSR = 'NetFront';
	}else{
		this.UA_BSR = 'Unknown';
	}

	//	Browser Version
	this.VerArr = new Array();
	this.VerArr = navigator.appVersion.split(' ');
	if( this.UA_BSR == 'Netfront' ){
		this.UA_BSRV = parseInt( navigator.appVersion );
	}else{
		this.UA_BSRV = this.bsrUserAgent.replace( new RegExp('.*('+this.UA_BSR+'(?: |/)([0-9\.]+).*)') , '$2' ) + 0;
//		this.UA_BSRV = parseInt( this.VerArr[0] );
	}



	this.getBsrName		 = function(){ return	this.UA_BSR; }
	this.getBsrVersion	 = function(){ return	this.UA_BSRV; }
	this.getOsName		 = function(){ return	this.UA_OS; }

	this.isWindows = function(){
		if( this.UA_OS == 'Windows' ){
			return	true;
		}else{
			return	false;
		}
	}
	this.isWinIE = function(){
		if( this.UA_OS == 'Windows' && this.UA_BSR == 'MSIE' ){
			return	true;
		}else{
			return	false;
		}
	}
	this.isGecko = function(){
		if( this.UA_BSR == 'Gecko' ){
			return	true;
		}else{
			return	false;
		}
	}
	this.isOpera = function(){
		if( this.UA_BSR == 'Opera' ){
			return	true;
		}else{
			return	false;
		}
	}
	this.isMacOS = function(){
		if( this.UA_OS == 'Macintosh' ){
			return	true;
		}else{
			return	false;
		}
	}
	this.isSafari = function(){
		if( this.UA_BSR == 'Safari' ){
			return	true;
		}else{
			return	false;
		}
	}


}