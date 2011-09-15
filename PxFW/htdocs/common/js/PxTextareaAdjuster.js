/* ----------------------------------------------------------------------------
	PxTextareaAdjuster
	Last Update : 21:33 2010/10/21
---------------------------------------------------------------------------- */
function PxTextareaAdjuster( textarea ){
  var textarea = textarea;
  textarea.style.overflow='hidden';
  var defaultHeight = textarea.offsetHeight;
  function adjustTa(){
    textarea.style.height = defaultHeight+'px';
    var tmp_sh = textarea.scrollHeight;
    while( tmp_sh > textarea.scrollHeight ){
      tmp_sh = textarea.scrollHeight;
      textarea.scrollHeight ++;
    }
    if( textarea.scrollHeight > textarea.offsetHeight ){
      textarea.style.height = textarea.scrollHeight+'px';
    }
  }
  function resetTa(){
    textarea.scrollTop = 0;
    textarea.style.height = defaultHeight+'px';
  }
  textarea.onfocus  = function(){ adjustTa(); }
  textarea.onkeyup  = function(){ adjustTa(); }
  textarea.onchange = function(){ adjustTa(); resetTa(); }
  textarea.onblur   = function(){ adjustTa(); resetTa(); }
}//PxTextareaAdjuster()
