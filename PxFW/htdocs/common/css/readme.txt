@charset "UTF-8";


【 Pickles CSS @readme.txt 】
version 0.4.3
Last Update: 2:00 2011/01/26
-- --------------------------------------------------------------------------
これらは、Pickles Framework が標準とするCSS(カスケーディング・スタイル・シート)です。
screen、handheld、print、tvの各メディアタイプをサポートします。

-- --------------------------------------------------------------------------
【同梱ファイルリスト】
・pickles.css
・pickles_all.css
・pickles_handheld.css
・pickles_print.css
・pickles_tv.css
・readme.txt(このファイル)

-- --------------------------------------------------------------------------
【更新履歴】

■Pickles CSS 0.4.3
・コンテンツエリア外のスタイルを分離した。

■Pickles CSS 0.4.2
・.haribotekit のエイリアス .imagereplace を追加。

■Pickles CSS 0.4.1
・メディアタイプ別の出しわけモジュールが、インライン要素 span に対応した。

■Pickles CSS 0.4.0
・モジュールを、スタティックモジュール、パーツモジュール、ユニットモジュールに分類した。
・div.pane(n)Block を div.unit_pane(n) に改名
・div.pager を div.unit_pager に改名。
・div.thumbnailList を div.unit_thumblist に、div.thumbnailList ul li div.thumbnailListImage を div.unit_thumblist ul li div.unit_thumblistImage に、div.thumbnailList ul li div.thumbnailListText を div.unit_thumblist ul li div.unit_thumblistText に、それぞれ改名。
・マージン、パディング系のフルスペル表記版を削除。
・.variableを削除。var要素に絞る。
・.paneBlockを削除。

-- --------------------------------------------------------------------------
【使い方】

次のような<link>タグを<head>セクション内に出力してください。

<link rel="stylesheet" href="/common/css/pickles.css" type="text/css" media="all" />

格納ディレクトリは、/common/css としていますが、
ここに限らず、どこに設置してもかまいません。

pickles.css は、以下のメディアタイプ別の各スタイルシートをインポートしています。

    ・pickles_all.css (全メディアタイプ用)
    ・pickles_handheld.css (handheld専用)
    ・pickles_print.css (print専用)
    ・pickles_tv.css (tv専用)

基本的に、汎用的な、そっけない表示がされます。
これを、例えば下記のように別のスタイルシートを用意して、
拡張して利用します。

<link rel="stylesheet" href="/common/css/pickles.css" type="text/css" media="all" />
<link rel="stylesheet" href="/common/css/custom.css" type="text/css" media="all" />

拡張用スタイルシートのファイル名や格納ディレクトリ、拡張の順序などは一例です。
これについて制限などはありません。

pickles.css を使用したコーディングのルールなど、
詳しくは、次のウェブページを参照してください。
http://pickles.pxt.jp/ja/stylesheet/index.html

-- --------------------------------------------------------------------------
Copyright (C)Tomoya Koyanagi, All rights reserved.

