<?php

#	カレントディレクトリをセット(コマンドラインから実行されたときのために)
chdir( dirname(__FILE__) );

# for PxFW EZ mode.
$_SERVER['PATH_INFO'] = preg_replace( '/\.pxhtml$/' , '.html' , $_SERVER['PATH_INFO'] );

#	アプリケーションの設定をロード
require_once('./__PICKLES__/lib'.'/config.php');

#--------------------------------------
#	【インスタンス生成】基本設定オブジェクト
$conf = new project_config();

require_once('./__PICKLES__/lib'.'/lib/conductor.php');

$conductor = new project_lib_conductor();
$conductor->setup( &$conf );
exit;

?>