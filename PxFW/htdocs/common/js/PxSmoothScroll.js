/* ---------------------------------------------------------------------------- *
PxSmoothScroll.js
(C)Tomoya Koyanagi.
Last Update : 19:33 2010/08/18
/* ---------------------------------------------------------------------------- */

function PxSmoothScroll( targetElementId ){
	if( targetElementId ){
		this.targetElementId = targetElementId;
	}

	this.PxSmoothScroll_getTargetAbsOffsetTop = function( targetElementId ){
		var $element = this.PxSmoothScroll_getTargetElement( targetElementId );
		if( !$element ){ return 0; }
		var $elmBox = null;
		var $scrollTop = this.PxSmoothScroll_getOffsetScrollTop();
		var $RTN = 0;
		if( $element.getBoundingClientRect ){
			//IE/Opera/Firefox3
			$elmBox = $element.getBoundingClientRect();
			$RTN = $elmBox.top + $scrollTop -2;
			return	$RTN;
		}else if( document.getBoxObjectFor ){
			//Mozilla/Firefox2
			$elmBox = document.getBoxObjectFor( $element );
			$RTN = $elmBox.y;
			return	$elmBox.y;
		}

		while( $element.offsetParent ){
			$RTN += $element.offsetTop;
			$element = $element.offsetParent;
		}
		$RTN = $RTN -2;
		return	$RTN;
	}
	this.PxSmoothScroll_getOffsetScrollTop = function(){
		var UA = window.navigator.userAgent.toLowerCase();

		if( window.pageYOffset || UA.indexOf('netscape') >= 0 || window.opera || UA.indexOf('gecko') >= 0 ){
			return	parseInt( window.pageYOffset );
		}else if( document.body && document.body.scrollTop ){
			return	parseInt(document.body.scrollTop);
		}else{
			return	parseInt(document.documentElement.scrollTop);
		}
		return	0;
	}
	this.PxSmoothScroll_getTargetElement = function( targetElementId ){
		if( !targetElementId ){ return; }
		if( !document.getElementById( targetElementId ) ){ return; }
		return	document.getElementById( targetElementId );
	}
	this.PxSmoothScroll_fix = function( targetElementId ){
		if( document.getElementById( targetElementId ) ){
			window.location.hash = targetElementId;
		}else{
			window.scrollTo( 0 , 0 );
		}
		return;
	}

	var UA = window.navigator.userAgent.toLowerCase();
	var NowScroll = this.PxSmoothScroll_getOffsetScrollTop();
	var targetScroll = this.PxSmoothScroll_getTargetAbsOffsetTop( this.targetElementId );
	var ToScroll = (NowScroll-targetScroll)/1.8 +targetScroll;
	var y = Math.round( ToScroll );

	if( typeof(y) != 'number' || y.toString() == 'NaN' ){
		this.PxSmoothScroll_fix( this.targetElementId );
		return;
	}

	if( ( y <= targetScroll+2 ) && ( y >= targetScroll-2 ) ){
		this.PxSmoothScroll_fix( this.targetElementId );
		this.targetElementId = null;
		return;
	}else{
		window.scrollTo( 0 , y );
		if( NowScroll == this.PxSmoothScroll_getOffsetScrollTop() ){
			this.PxSmoothScroll_fix( this.targetElementId );
			return;
		}
		setTimeout('PxSmoothScroll();',10);
	}
}