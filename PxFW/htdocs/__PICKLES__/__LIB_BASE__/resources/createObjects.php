<?php

#	Copyright (C)Tomoya Koyanagi.
#	Last Update : 20:57 2011/02/08

#******************************************************************************************************************
#	オブジェクトを生成して返す。
class base_resources_createObjects{

	#----------------------------------------------------------------------------
	#	基本libクラスを生成
	function &create_object_lib_errors( &$conf ){
		require_once( $conf->path_lib_project.'/lib/errors.php' );
		$RTN = new project_lib_errors();
		return	$RTN;
	}
	function &create_object_lib_dbh( &$conf ){
		require_once( $conf->path_lib_project.'/lib/dbh.php' );
		$RTN = new project_lib_dbh();
		return	$RTN;
	}
	function &create_object_lib_req( &$conf ){
		require_once( $conf->path_lib_project.'/lib/request.php' );
		$RTN = new project_lib_request();
		return	$RTN;
	}
	function &create_object_lib_user( &$conf ){
		require_once( $conf->path_lib_project.'/lib/user.php' );
		$RTN = new project_lib_user();
		return	$RTN;
	}
	function &create_object_lib_site( &$conf ){
		require_once( $conf->path_lib_project.'/lib/site.php' );
		$RTN = new project_lib_site();
		return	$RTN;
	}
	function &create_object_lib_theme( &$conf , &$req , &$user ){
		#	Pickles Framework 0.4.0 以降、
		#	テーマ格納ディレクトリのパスのルールが変更となり、
		#	テーマIDとCTの順が逆になりました。(1:58 2008/07/11 Tomoya Koyanagi)

		$theme = null;
		require_once( $conf->path_lib_base.'/lib/theme.php' );
		if( @is_file( $conf->path_lib_project.'/lib/theme.php' ) ){
			#	汎用デフォルトテーマがあれば、ロード
			require_once( $conf->path_lib_project.'/lib/theme.php' );
		}
		if( @is_file( $conf->path_lib_project.'/lib/theme_'.$user->get_ct().'.php' ) ){
			#	CT向けのデフォルトテーマがあれば、ロード
			require_once( $conf->path_lib_project.'/lib/theme_'.$user->get_ct().'.php' );
		}
		if( $req->in('THEME') == 'null' && $conf->allow_cancel_customtheme ){
			#	themeに、文字列nullが渡った場合、カスタムテーマをロードしない。
			#	【Pickles Framework 0.1.8 追加】
			#		カスタムテーマのキャンセル機能(THEME=null)の有効/無効を、
			#		コンフィグ項目 allow_cancel_customtheme から制御できるようになりました。

		}elseif( @is_file( $conf->path_theme_collection_dir.'/'.$user->gettheme().'/'.$user->get_ct().'/lib/lib/theme.php' ) ){
			require_once( $conf->path_theme_collection_dir.'/'.$user->gettheme().'/'.$user->get_ct().'/lib/lib/theme.php' );

#		}elseif( @is_file( $conf->path_theme_collection_dir.'/default/'.$user->get_ct().'/lib/lib/theme.php' ) ){
#			$user->settheme( 'default' );
#			require_once( $conf->path_theme_collection_dir.'/default/'.$user->get_ct().'/lib/lib/theme.php' );
#
#		}elseif( @is_file( $conf->path_theme_collection_dir.'/'.$user->gettheme().'/PC/lib/lib/theme.php' ) ){
#			require_once( $conf->path_theme_collection_dir.'/'.$user->gettheme().'/PC/lib/lib/theme.php' );
			#	↑Pickles Framework 0.4.10 : PC以外のCTにおいて、正しいテーマを選択できない場合があるため、削除。
		}
		if( class_exists( 'theme_lib_theme' ) ){
			$theme = new theme_lib_theme();
		}elseif( class_exists( 'project_lib_theme_'.$user->get_ct() ) ){
			$theme = eval( 'return new project_lib_theme_'.$user->get_ct().'();' );
		}elseif( class_exists( 'project_lib_theme' ) ){
			$theme = new project_lib_theme();
		}elseif( class_exists( 'package_lib_theme_'.$user->get_ct() ) ){
			$theme = eval( 'return new package_lib_theme_'.$user->get_ct().'();' );//←Pickles Framework 0.4.10 追加
		}elseif( class_exists( 'package_lib_theme' ) ){
			$theme = new package_lib_theme();//←Pickles Framework 0.4.0 追加
		}elseif( class_exists( 'base_lib_theme_'.$user->get_ct() ) ){
			$theme = eval( 'return new base_lib_theme_'.$user->get_ct().'();' );//←Pickles Framework 0.4.10 追加
		}else{
			$theme = new base_lib_theme();//←Pickles Framework 0.4.0 追加
		}

		return	$theme;
	}
	function &create_object_lib_additional_objects( &$conf , &$req , &$dbh , &$user , &$site , &$errors ){
		#	Pickles Framework 0.3.4 で、$theme を受け取らないようになった。
		#	Pickles Framework 0.6.2 で、クラスからインスタンス化するようになった。
		$className = $dbh->require_lib('/lib/custom.php');
		if( !$className ){
			$errors->error_log('/lib/custom.php のロードに失敗しました。',__FILE__,__LINE__);
			settype( $custom , 'object' );
			return	$custom;
		}
		$obj = new $className();
		$obj->setup( &$conf , &$req , &$dbh , &$user , &$site , &$errors );
		return	$obj;
	}

	#----------------------------------------------------------------------------
	#	スタティッククラスをロード
	function load_static_classes( &$conf ){
		#	使用するクラスライブラリをロード
		$path = $conf->path_lib_project.'/static';
		$dr = opendir($path);
		while( ( $ent = readdir( $dr ) ) !== false ){
			#	CurrentDirとParentDirは含めない
			if( $ent == '.' || $ent == '..' ){ continue; }
			if( is_file( $path.'/'.$ent ) ){ @include_once( $path.'/'.$ent ); }
		}
		closedir($dr);
		return	true;
	}

}

?>