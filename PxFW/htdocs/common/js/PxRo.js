/* ---------------------------------------------------------------------------- *
Pickles Rollover JavaScript
(C)Tomoya Koyanagi.
Last Update : 1:47 2008/07/09
/* ---------------------------------------------------------------------------- */
function PxRo_preloadImage(){if( document.getElementById && document.images ){var args = PxRo_preloadImage.arguments;var i=0;document.PxRo_imageMemo = new Array();for( i = 0; args[i]; i ++ ){document.PxRo_imageMemo[i] = new Image;document.PxRo_imageMemo[i].src = args[i];}}}function PxRo_mouseOver( targetImageId , imageUrl ){if( document.getElementById && document.images ){document.PxRo_hoverMemo = new Array();document.PxRo_hoverMemo[0] = targetImageId;document.PxRo_hoverMemo[1] = document.getElementById(targetImageId).src;document.getElementById(targetImageId).src = imageUrl;}}function PxRo_mouseOut(){if( document.getElementById && document.images ){document.getElementById(document.PxRo_hoverMemo[0]).src = document.PxRo_hoverMemo[1];}}
