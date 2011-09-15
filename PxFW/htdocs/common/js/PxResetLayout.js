/* -------------------------------------- *
Firefoxでスタイルシートの読み込みがバグった場合に、調整するスクリプト。
---
この症状は、スタイルシートの読み込み(または描画？)に時々問題が起こる模様。
リロードすると直ったり、再発現したりする。
(必ず発現できる条件は判っていない)
「オフライン作業」にチェックを入れてリロードすると
100%正しい表示になるため、スタイルシートは間違っていないと思うのだが・・・。
---
というわけで、苦肉の策。
<body>タグの最後に<span>タグを挿入している。
FirefoxはこれをきっかけにHTMLを読み込むのか、直るようだ。
---
(C)Tomoya Koyanagi.
LastUpdate : 23:01 2008/07/15
/* -------------------------------------- */
function PxResetLayout(){
	var body = document.getElementsByTagName('body')[0];
	if( navigator.userAgent.toLowerCase().indexOf( "firefox" ) >= 0 ){
		//Firefoxの特殊処理
		body.innerHTML += '';
	}else{
		//普通はこっち(というか、普通は要らない？)
		var new_element = document.createElement('span');
		body.appendChild( new_element );//ダミーの空白エレメントを作成
		body.removeChild( new_element );//作ったらすぐ消す
	}
}