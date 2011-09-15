<?php

#	Copyright (C)Tomoya Koyanagi.
#	Last Update : 11:50 2010/02/03

/* ******************************************************************************************************************************************************** *
	■ユーザ情報の取得系
		anch: pickles_userinfo_getting_methods
	■ユーザ情報の確認系
		anch: pickles_check_userinfo_methods
	■ユーザ情報の作成・編集保存系
		anch: pickles_userinfo_createAndEdit_methods
	■ユーザグループ作成・編集保存系
		anch: pickles_groupinfo_createAndEdit_methods
	■初期設定、セットアップ系
		anch: pickles_userinfo_setup_methods
/* ******************************************************************************************************************************************************** */

#******************************************************************************************************************
#	ユーザを編集する
class base_resources_user_usereditor{

	var $conf;
	var $dbh;
	var $user;
	var $errors;

	var $last_insert_user_cd = null;
		#	INSERTしたユーザCDを記憶
	var $last_insert_group_cd = null;
		#	INSERTしたグループCDを記憶

	#----------------------------------------------------------------------------
	#	コンストラクタ
	function base_resources_user_usereditor( &$conf , &$dbh , &$user , &$errors ){
		$this->conf = &$conf;
		$this->dbh = &$dbh;
		$this->user = &$user;
			#	↑ユーザ情報などの定義を知るために必要。パスや設定などは、$confを参照して取得。
		$this->errors = &$errors;
	}


	/* ******************************************************************************************************************************************************** *
		ユーザ情報の取得系
		anch: pickles_userinfo_getting_methods
	/* ******************************************************************************************************************************************************** */

	#--------------------------------------
	#	ユーザコードの一覧を取得
	function get_useridlist( $OFFSET = 0 , $LIMIT = null ){
		#	PxFW 0.6.2 get_usercdlist() に改名した。
		#	get_useridlist() は、互換性維持のために残す。
		return	$this->get_usercdlist( $OFFSET , $LIMIT );
	}
	function get_usercdlist( $OFFSET = 0 , $LIMIT = null ){
		$OFFSET = intval( $OFFSET );
		if( !is_null( $LIMIT ) ){
			$LIMIT = intval( $LIMIT );
		}

		$RTN = array();
		if( is_array( $this->conf->rdb_usertable ) ){
			#	【 DB版 】
			#	$this->conf->rdb_usertable が配列だった場合、
			#	ユーザ情報をDB管理しているとみなす。

			$sql = 'SELECT user_cd FROM :D:tableName WHERE del_flg = 0';
			if( $LIMIT > 0 ){
				$sql .= $this->dbh->mk_sql_limit( $LIMIT , $OFFSET );
			}
			$sql .= ';';
			$bindData = array(
				'tableName'=>$this->conf->rdb_usertable['master'],
			);
			$sql = $this->dbh->bind( $sql , $bindData );
			$res = $this->dbh->sendquery( $sql );
			$values = $this->dbh->getval();

			if( !is_array( $values ) ){ $values = array(); }
			foreach( $values as $Line ){
				if( $Line == '.' || $Line == '..' || $Line == '@_SYSTEM' ){ continue; }
				array_push( $RTN , $Line['user_cd'] );
			}
			return	$RTN;

		}else{
			#	【 ファイル版 】
			$values = $this->dbh->getfilelist( $this->conf->path_userdir );
			if( !is_array( $values ) ){ $values = array(); }
			sort($values);

			$i = 0;
			foreach( $values as $Line ){
				if( $Line == '.' || $Line == '..' || $Line == '@_SYSTEM' ){ continue; }
				$i ++;
				if( $i < $OFFSET+1 ){ continue; }
				if( $i >= $OFFSET+1 + $LIMIT && is_int( $LIMIT ) ){ break; }

				array_push( $RTN , $Line );
			}
			return	$RTN;
		}
		return	array();
	}

	#--------------------------------------
	#	全登録ユーザ数を得る
	function get_usercount(){

		if( is_array( $this->conf->rdb_usertable ) ){
			#	【 DB版 】
			#	$this->conf->rdb_usertable が配列だった場合、
			#	ユーザ情報をDB管理しているとみなす。

			$sql = 'SELECT count(*) AS count FROM :D:tableName WHERE del_flg = 0;';
			$bindData = array(
				'tableName'=>$this->conf->rdb_usertable['master'],
			);
			$sql = $this->dbh->bind( $sql , $bindData );
			$res = $this->dbh->sendquery( $sql );
			$values = $this->dbh->getval();

			return	intval( $values[0]['count'] );

		}else{
			#	【 ファイル版 】
			$values = $this->dbh->getfilelist( $this->conf->path_userdir );
			if( !is_array( $values ) ){ $values = array(); }
			$i = 0;
			foreach( $values as $Line ){
				if( $Line == '.' || $Line == '..' || $Line == '@_SYSTEM' ){ continue; }
				$i ++;
			}

			return	$i;
		}

		return	false;
	}

	#--------------------------------------
	#	ユーザコード番号からユーザIDを取得
	function get_user_cd_by_id( $user_id ){
		if( !strlen( $user_id ) ){
			return	false;
		}

		if( is_array( $this->conf->rdb_usertable ) ){
			#	【 DB版 】
			#	$this->conf->rdb_usertable が配列だった場合、
			#	ユーザ情報をDB管理しているとみなす。

			$sql = 'SELECT user_cd FROM :D:tableName WHERE user_id = :S:user_id AND del_flg = 0;';
			$bindData = array(
				'tableName'=>$this->conf->rdb_usertable['master'],
				'user_id'=>$user_id,
			);
			$sql = $this->dbh->bind( $sql , $bindData );
			$res = $this->dbh->sendquery( $sql );
			$value = $this->dbh->getval();

			if( !is_array( $value ) || !count( $value ) ){
				return	false;
			}

			return	intval( $value[0]['user_cd'] );

		}else{
			#	【 ファイル版 】
			#	ファイル管理の場合は、user_cdはuser_idと同じ文字列。
			if( !$this->dbh->is_dir( $this->conf->path_userdir.$user_id ) ){
				return	false;
			}
			return	$user_id;
		}
		return	false;
	}

	#--------------------------------------
	#	ユーザ情報を取得
	function get_userinfo( $user_cd , $project_id = null ){
		if( !$this->is_user( $user_cd ) ){ return false; }

		$RTN = array();
		$RTN['master'] = $this->get_userinfo_master( $user_cd );
		$RTN['property'] = $this->get_userinfo_property( $user_cd );
		if( strlen( $project_id ) ){
			$RTN['project_authgroup'] = $this->get_userinfo_project_authgroup( $user_cd , $project_id );
			$RTN['project_status'] = $this->get_userinfo_project_status( $user_cd , $project_id );
			$RTN['project_authoptions'] = $this->get_userinfo_project_authoptions( $user_cd , $project_id );
		}
		return	$RTN;
	}

	#--------------------------------------
	#	ユーザ情報(基本情報)を取得
	function get_userinfo_master( $user_cd ){
		if( !$this->is_user( $user_cd ) ){ return false; }

		if( is_array( $this->conf->rdb_usertable ) ){
			#	DB版
			#	$this->conf->rdb_usertable が配列だった場合、
			#	ユーザ情報をDB管理しているとみなす。

			$sql = 'SELECT * FROM :D:tableName WHERE user_cd = :N:user_cd AND del_flg = 0;';
			$bindData = array(
				'tableName'=>$this->conf->rdb_usertable['master'],
				'user_cd'=>$user_cd,
			);
			$sql = $this->dbh->bind( $sql , $bindData );
			$res = $this->dbh->sendquery( $sql );
			$value = $this->dbh->getval();
			$RTN = $value[0];
			return	$RTN;

		}else{
			#	ファイル版
			$value = array();
			$value[0] = array();
			$value[0]['user_id'] = $user_cd;
			$value[0]['user_cd'] = $user_cd;
			$value[0]['user_name'] = $this->dbh->file_get_contents( $this->conf->path_userdir.$user_cd.'/name.txt' );
			$value[0]['user_email'] = $this->dbh->file_get_contents( $this->conf->path_userdir.$user_cd.'/email.txt' );
			$value[0]['user_pw'] = $this->dbh->file_get_contents( $this->conf->path_userdir.$user_cd.'/pw.txt' );
			$value[0]['device_id'] = $this->dbh->file_get_contents( $this->conf->path_userdir.$user_cd.'/device.txt' );
			$value[0]['tmp_pw'] = $this->dbh->file_get_contents( $this->conf->path_userdir.$user_cd.'/tmp_pw.txt' );
			$value[0]['tmp_email'] = $this->dbh->file_get_contents( $this->conf->path_userdir.$user_cd.'/tmp_email.txt' );
			$value[0]['tmp_data'] = $this->dbh->file_get_contents( $this->conf->path_userdir.$user_cd.'/tmp_data.txt' );
			$RTN = $value[0];
			return	$RTN;
		}
		return	array();
	}
	#--------------------------------------
	#	ユーザ情報(プロパティ情報)を取得
	function get_userinfo_property( $user_cd ){
		if( !$this->is_user( $user_cd ) ){ return false; }

		static $RTN = null;

		if( is_array( $this->conf->rdb_usertable ) ){
			#	【 DB版 】
			#	$this->conf->rdb_usertable が配列だった場合、
			#	ユーザ情報をDB管理しているとみなす。

			$sql = 'SELECT * FROM :D:tableName WHERE user_cd = :N:user_cd;';
			$bindData = array(
				'tableName'=>$this->conf->rdb_usertable['property'],
				'user_cd'=>$user_cd,
			);
			$sql = $this->dbh->bind( $sql , $bindData );
			$res = $this->dbh->sendquery( $sql );
			$value = $this->dbh->getval();

			$RTN = array();
			if( is_array( $value ) ){
				foreach( $value as $Line ){
					$RTN[$Line['keystr']] = text::convert_encoding( $Line['valstr'] );
				}
			}
			return	$RTN;

		}else{
			#	【 ファイル版 】
			$RTN = array();
			if( $this->dbh->is_file( $this->conf->path_userdir.$user_cd.'/info.csv' ) ){
				$user_info = $this->dbh->read_csv( $this->conf->path_userdir.$user_cd.'/info.csv' , null , null , null , $this->user->localconf_userinfo_encoding );
				if( !is_array( $user_info ) ){ $user_info = array(); }
				foreach( $user_info as $Line ){
					$RTN[$Line[0]] = text::convert_encoding( $Line[1] );
				}
			}
			return	$RTN;
		}
		return	array();
	}
	#--------------------------------------
	#	ユーザ情報(プロジェクト権限情報)を取得
	function get_userinfo_project_authoptions( $user_cd , $project_id = null ){
		if( !$this->is_user( $user_cd ) ){ return false; }
		if( is_null( $project_id ) ){ $project_id = $this->conf->info_projectid; }
		if( !strlen( $project_id ) ){ return false; }

		if( is_array( $this->conf->rdb_usertable ) ){
			#	【 DB版 】
			#	$this->conf->rdb_usertable が配列だった場合、
			#	ユーザ情報をDB管理しているとみなす。

			$sql = 'SELECT * FROM :D:tableName WHERE user_cd = :N:user_cd AND project_id = :S:project_id;';
			$bindData = array(
				'tableName'=>$this->conf->rdb_usertable['project_authoptions'],
				'user_cd'=>$user_cd,
				'project_id'=>$project_id,
			);
			$sql = $this->dbh->bind( $sql , $bindData );
			$res = $this->dbh->sendquery( $sql );
			$value = $this->dbh->getval();

			$RTN = array();
			if( is_array( $value ) ){
				foreach( $value as $Line ){
					if( !$Line['valstr'] ){ continue; }
					$RTN[$Line['keystr']] = text::convert_encoding( $Line['valstr'] );
				}
			}
			return	$RTN;

		}else{
			#	【 ファイル版 】
			$RTN = array();
			$filepath = $this->conf->path_userdir.$user_cd.'/projectdata/'.$project_id.'/auth/authoptions.array';
			if( $this->dbh->is_file( $filepath ) ){
				$RTN = include( $filepath );
			}
			return	$RTN;
		}
		return	array();
	}
	#--------------------------------------
	#	ユーザ情報(プロジェクトユーザグループ所属)を取得
	function get_userinfo_project_authgroup( $user_cd , $project_id = null ){
		if( !$this->is_user( $user_cd ) ){ return false; }
		if( is_null( $project_id ) ){ $project_id = $this->conf->info_projectid; }
		if( !strlen( $project_id ) ){ return false; }

		if( is_array( $this->conf->rdb_usertable ) ){
			#	【 DB版 】
			#	$this->conf->rdb_usertable が配列だった場合、
			#	ユーザ情報をDB管理しているとみなす。

			$sql = 'SELECT * FROM :D:tableName WHERE user_cd = :N:user_cd AND project_id = :S:project_id AND active_flg = 1;';
			$bindData = array(
				'tableName'=>$this->conf->rdb_usertable['project_authgroup'],
				'project_id'=>$project_id,
				'user_cd'=>$user_cd,
			);
			$sql = $this->dbh->bind( $sql , $bindData );
			$res = $this->dbh->sendquery( $sql );
			$value = $this->dbh->getval();

			$RTN = array();
			if( is_array( $value ) ){
				foreach( $value as $Line ){
					$RTN[$Line['group_cd']] = true;
				}
			}
			return	$RTN;

		}else{
			#	【 ファイル版 】
			$RTN = array();
			if( $this->dbh->is_file( $this->conf->path_userdir.$user_cd.'/projectdata/'.$project_id.'/auth/authgroup.array' ) ){
				$RTN = include( $this->conf->path_userdir.$user_cd.'/projectdata/'.$project_id.'/auth/authgroup.array' );
			}
			return	$RTN;
		}
		return	array();
	}
	#--------------------------------------
	#	ユーザ情報(プロジェクトステータス情報)を取得
	function get_userinfo_project_status( $user_cd , $project_id = null ){
		if( !$this->is_user( $user_cd ) ){ return false; }
		if( is_null( $project_id ) ){ $project_id = $this->conf->info_projectid; }
		if( !strlen( $project_id ) ){ return false; }

		if( is_array( $this->conf->rdb_usertable ) ){
			#	【 DB版 】
			#	$this->conf->rdb_usertable が配列だった場合、
			#	ユーザ情報をDB管理しているとみなす。

			$sql = 'SELECT * FROM :D:tableName WHERE user_cd = :N:user_cd AND project_id = :S:project_id AND registed_flg = 1;';
			$bindData = array(
				'tableName'=>$this->conf->rdb_usertable['project_status'],
				'user_cd'=>$user_cd,
				'project_id'=>$project_id,
			);
			$sql = $this->dbh->bind( $sql , $bindData );
			$res = $this->dbh->sendquery( $sql );
			$value = $this->dbh->getval();
			$RTN = $value[0];
			return	$RTN;

		}else{
			#	【 ファイル版 】
			$value = array();
			$value[0] = array();
			$value[0]['user_cd'] = $user_cd;
			$value[0]['project_id'] = $project_id;
			$value[0]['authlevel'] = $this->dbh->file_get_contents( $this->conf->path_userdir.$user_cd.'/projectdata/'.$project_id.'/auth/authlevel.int' );
			$RTN = $value[0];
			return	$RTN;

		}
		return	array();
	}
	#--------------------------------------
	#	ユーザ情報(プロジェクトデータ)を取得
	function get_userinfo_project_datas( $user_cd , $dataspace , $keystr , $project_id = null ){
		if( !$this->is_user( $user_cd ) ){ return false; }
		if( is_null( $project_id ) ){ $project_id = $this->conf->info_projectid; }
		if( !strlen( $project_id ) ){ return false; }
		if( !strlen( $dataspace ) ){ return false; }
		if( !strlen( $keystr ) ){ return false; }

		$dataspace = strtolower( preg_replace( '/[^a-zA-Z0-9-_@]/' , '' , $dataspace ) );
		$keystr = strtolower( preg_replace( '/[^a-zA-Z0-9-_@]/' , '' , $keystr ) );

		if( is_array( $this->conf->rdb_usertable ) ){
			#	【 DB版 】
			#	$this->conf->rdb_usertable が配列だった場合、
			#	ユーザ情報をDB管理しているとみなす。

			$sql = 'SELECT valstr FROM :D:tableName WHERE user_cd = :N:user_cd AND project_id = :S:project_id AND dataspace = :S:dataspace AND keystr = :S:keystr;';
			$bindData = array(
				'tableName'=>$this->conf->rdb_usertable['project_datas'],
				'user_cd'=>$user_cd,
				'project_id'=>$project_id,
				'dataspace'=>$dataspace,
				'keystr'=>$keystr,
			);
			$sql = $this->dbh->bind( $sql , $bindData );
			$res = $this->dbh->sendquery( $sql );
			$value = $this->dbh->getval();
			$RTN = $value[0]['valstr'];
			if( !strlen( $RTN ) ){
				return	null;
			}
			return	unserialize( $RTN );

		}else{
			#	【 ファイル版 】
			$user_project_dir = $this->conf->path_userdir.$user_cd.'/projectdata/'.$project_id;
			if( !$this->dbh->is_dir( $user_project_dir ) ){
				return	null;
			}
			if( !$this->dbh->is_file( $user_project_dir.'/datas/'.$dataspace.'/'.$keystr ) ){
				return	null;
			}
			return	@include( $user_project_dir.'/datas/'.$dataspace.'/'.$keystr );

		}

		return	null;
	}


	#--------------------------------------
	#	ユーザ情報(プロジェクトデータ)を保存
	function save_userinfo_project_datas( $user_cd , $dataspace , $keystr , $valstr , $project_id = null ){
		if( !strlen( $project_id ) ){ $project_id = $this->conf->info_projectid; }
		if( !strlen( $project_id ) ){ return false; }
		if( !$this->is_user( $user_cd ) ){ return false; }

		$dataspace = strtolower( preg_replace( '/[^a-zA-Z0-9-_@]/' , '' , $dataspace ) );
		$keystr = strtolower( preg_replace( '/[^a-zA-Z0-9-_@]/' , '' , $keystr ) );

		if( !strlen( $dataspace ) ){ return false; }
		if( !strlen( $keystr ) ){ return false; }

		if( is_array( $this->conf->rdb_usertable ) ){
			#	【 DB版 】
			#	$this->conf->rdb_usertable が配列だった場合、
			#	ユーザ情報をDB管理しているとみなす。

			$sql = array();
			$sql['select'] = 'SELECT count(*) as count FROM :D:tableName WHERE user_cd = :N:user_cd AND project_id = :S:project_id AND dataspace = :S:dataspace AND keystr = :S:keystr;';
			$sql['update'] = 'UPDATE :D:tableName SET valstr = :S:valstr, lastupdate_date = :S:now WHERE user_cd = :N:user_cd AND project_id = :S:project_id AND dataspace = :S:dataspace AND keystr = :S:keystr;';
			$sql['insert'] = 'INSERT INTO :D:tableName ( user_cd , project_id , dataspace , keystr , valstr , create_date , lastupdate_date ) VALUES ( :N:user_cd , :S:project_id , :S:dataspace , :S:keystr , :S:valstr , :S:now , :S:now );';
			$bindData = array(
				'tableName'=>$this->conf->rdb_usertable['project_datas'],
				'user_cd'=>$user_cd,
				'project_id'=>$project_id,
				'dataspace'=>$dataspace,
				'keystr'=>$keystr,
				'valstr'=>serialize( $valstr ),
				'now'=>date( 'Y-m-d H:i:s' ),
			);
			$sqlSelect = $this->dbh->bind( $sql['select'] , $bindData );
			$res = $this->dbh->sendquery( $sqlSelect );
			$value = $this->dbh->getval();

			if( !$value[0]['count'] ){
				$sqlFinal = $this->dbh->bind( $sql['insert'] , $bindData );
			}else{
				$sqlFinal = $this->dbh->bind( $sql['update'] , $bindData );
			}
			$res = $this->dbh->sendquery( $sqlFinal );
			if( !$res ){
				$this->dbh->rollback();
				return	false;
			}
			$this->dbh->commit();
			return	true;

		}else{
			#	【 ファイル版 】
			$user_project_dir = $this->conf->path_userdir.$user_cd.'/projectdata/'.$project_id;
			if( !$this->dbh->is_dir( $user_project_dir ) ){
				return	false;
			}
			if( !$this->dbh->is_dir( $user_project_dir.'/datas/'.$dataspace ) ){
				if( !$this->dbh->mkdirall( $user_project_dir.'/datas/'.$dataspace ) ){
					return	false;
				}
				clearstatcache();
			}
			if( !is_null( $valstr ) ){
				return	$this->dbh->file_overwrite( $user_project_dir.'/datas/'.$dataspace.'/'.$keystr , text::data2phpsrc( $valstr ) );
			}else{
				return	$this->dbh->rmdir( $user_project_dir.'/datas/'.$dataspace.'/'.$keystr );
			}

		}

		return	false;
	}


	#--------------------------------------
	#	グループ情報を取得
	function get_groupinfo( $group_cd , $project_id = null ){
		if( !strlen( $project_id ) ){ $project_id = $this->conf->info_projectid; }
		if( !strlen( $project_id ) ){ return false; }
		if( !$this->is_group( $group_cd , $project_id ) ){ return false; }

		if( is_array( $this->conf->rdb_usertable ) ){
			#	【 DB版 】
			#	$this->conf->rdb_usertable が配列だった場合、
			#	ユーザ情報をDB管理しているとみなす。

			$sql = 'SELECT * FROM :D:tableName WHERE group_cd = :N:group_cd AND del_flg = 0;';
			$bindData = array(
				'tableName'=>$this->conf->rdb_usertable['group'],
				'group_cd'=>$group_cd,
#				'project_id'=>$project_id,
			);
			$sql = $this->dbh->bind( $sql , $bindData );
			$res = $this->dbh->sendquery( $sql );
			$RTN = $this->dbh->getval();

			return	$RTN[0];

		}else{
			#	【 ファイル版 】

			$path_group_mst = $this->conf->path_userdir.'@_SYSTEM/authgroup/authgroup_define.array';
			if( !$this->dbh->is_file( $path_group_mst ) ){
				return	false;
			}

			$allgroups = @include( $path_group_mst );
			if( !is_array( $allgroups[$project_id][$group_cd] ) ){
				return	false;
			}
			return	$allgroups[$project_id][$group_cd];

		}

		return	array();
	}

	#--------------------------------------
	#	プロジェクトに属するグループ一覧を取得
	function get_groupinfo_list( $project_id = null ){
		if( is_null( $project_id ) ){ $project_id = $this->conf->info_projectid; }

		if( is_array( $this->conf->rdb_usertable ) ){
			#	【 DB版 】
			#	$this->conf->rdb_usertable が配列だった場合、
			#	ユーザ情報をDB管理しているとみなす。

			$sql = 'SELECT * FROM :D:tableName WHERE del_flg = 0;';
			$bindData = array(
				'tableName'=>$this->conf->rdb_usertable['group'],
#				'project_id'=>$project_id,
			);
			$sql = $this->dbh->bind( $sql , $bindData );
			$res = $this->dbh->sendquery( $sql );
			$RTN = $this->dbh->getval();

			return	$RTN;

		}else{
			#	【 ファイル版 】

			$path_group_mst = $this->conf->path_userdir.'@_SYSTEM/authgroup/authgroup_define.array';
			if( !$this->dbh->is_file( $path_group_mst ) ){
				return	false;
			}

			$allgroups = @include( $path_group_mst );
			if( !is_array( $allgroups[$project_id] ) ){
				return	false;
			}
			return	$allgroups[$project_id];

		}

		return	array();
	}

	#--------------------------------------
	#	グループ情報(プロジェクト権限情報)を取得
	function get_groupinfo_project_authoptions( $group_cd , $project_id = null ){
		if( !strlen( $project_id ) ){ $project_id = $this->conf->info_projectid; }
		if( !strlen( $project_id ) ){ return false; }
		if( !$this->is_group( $group_cd , $project_id ) ){ return false; }

		if( is_array( $this->conf->rdb_usertable ) ){
			#	【 DB版 】
			#	$this->conf->rdb_usertable が配列だった場合、
			#	ユーザ情報をDB管理しているとみなす。

			$sql = 'SELECT * FROM :D:tableName WHERE group_cd = :N:group_cd;';
			$bindData = array(
				'tableName'=>$this->conf->rdb_usertable['group_authoptions'],
				'group_cd'=>$group_cd,
			);
			$sql = $this->dbh->bind( $sql , $bindData );
			$res = $this->dbh->sendquery( $sql );
			$value = $this->dbh->getval();

			$RTN = array();
			if( is_array( $value ) ){
				foreach( $value as $Line ){
					if( !$Line['valstr'] ){ continue; }
					$RTN[$Line['keystr']] = text::convert_encoding( $Line['valstr'] );
				}
			}
			return	$RTN;

		}else{
			#	【 ファイル版 】
			$saveTargetPath = $this->conf->path_userdir.'@_SYSTEM/authgroup/authgroup_define.array';
			$group_define = @include( $saveTargetPath );
			if( !is_array( $group_define[$project_id][$group_cd] ) ){
				#	グループCDが未定義だったらダメ。
				return	array();
			}
			return	$group_define[$project_id][$group_cd]['authoptions'];
		}

		return	array();

	}


	/* ******************************************************************************************************************************************************** *
		ユーザ情報の確認系
		anch: pickles_check_userinfo_methods
	/* ******************************************************************************************************************************************************** */

	#--------------------------------------
	#	ユーザCDから、ユーザが存在するか確認する
	function is_user( $user_cd ){
		if( !strlen( $user_cd ) ){
			return	false;
		}

		if( is_array( $this->conf->rdb_usertable ) ){
			#	【 DB版 】
			#	$this->conf->rdb_usertable が配列だった場合、
			#	ユーザ情報をDB管理しているとみなす。

			$sql = 'SELECT count(*) as count FROM :D:tableName WHERE user_cd = :N:user_cd AND del_flg = 0;';
			$bindData = array(
				'tableName'=>$this->conf->rdb_usertable['master'],
				'user_cd'=>$user_cd,
			);
			$sql = $this->dbh->bind( $sql , $bindData );
			$res = $this->dbh->sendquery( $sql );
			$value = $this->dbh->getval();

			if( $value[0]['count'] > 0 ){
				return	true;
			}
			return	false;

		}else{
			#	【 ファイル版 】
			if( $this->dbh->is_dir( $this->conf->path_userdir.$user_cd ) ){
				return	true;
			}
			return	false;
		}
		return	false;
	}

	#--------------------------------------
	#	ユーザIDから、ユーザが存在するか確認する
	function is_user_id( $user_id ){
		if( !strlen( $user_id ) ){ return false; }
		$user_id = strtolower( $user_id );

		if( is_array( $this->conf->rdb_usertable ) ){
			#	【 DB版 】
			#	$this->conf->rdb_usertable が配列だった場合、
			#	ユーザ情報をDB管理しているとみなす。

			$sql = 'SELECT count(*) as count FROM :D:tableName WHERE user_id = :S:user_id AND del_flg = 0;';
			$bindData = array(
				'tableName'=>$this->conf->rdb_usertable['master'],
				'user_id'=>$user_id,
			);
			$sql = $this->dbh->bind( $sql , $bindData );
			$res = $this->dbh->sendquery( $sql );
			$value = $this->dbh->getval();

			if( $value[0]['count'] > 0 ){
				return	true;
			}
			return	false;

		}else{
			#	【 ファイル版 】
			if( $this->dbh->is_dir( $this->conf->path_userdir.$user_id ) ){
				return	true;
			}
			return	false;
		}
		return	false;
	}

	#--------------------------------------
	#	グループCDから、グループが存在するか確認する
	function is_group( $group_cd , $project_id = null ){
		if( !strlen( $group_cd ) ){ return	false; }
		if( !strlen( $project_id ) ){ $project_id = $this->conf->info_projectid; }
		if( !strlen( $project_id ) ){ return false; }

		if( is_array( $this->conf->rdb_usertable ) ){
			#	【 DB版 】
			#	$this->conf->rdb_usertable が配列だった場合、
			#	ユーザ情報をDB管理しているとみなす。

			$sql = 'SELECT count(*) as count FROM :D:tableName WHERE group_cd = :N:group_cd;';
			$bindData = array(
				'tableName'=>$this->conf->rdb_usertable['group'],
				'group_cd'=>$group_cd,
#				'project_id'=>$project_id,
			);
			$sql = $this->dbh->bind( $sql , $bindData );
			$res = $this->dbh->sendquery( $sql );
			$value = $this->dbh->getval();

			if( $value[0]['count'] > 0 ){
				return	true;
			}
			return	false;

		}else{
			#	【 ファイル版 】

			$define_path = $this->conf->path_userdir.'@_SYSTEM/authgroup/authgroup_define.array';
			if( !$this->dbh->is_file( $define_path ) ){
				return	false;
			}
			$group_define = @include( $define_path );
			if( !is_array( $group_define[$project_id] ) ){ $group_define[$project_id] = array(); }
			foreach( $group_define[$project_id] as $Line ){
				if( $Line['group_cd'] == $group_cd ){ return true; }
			}
		}
		return	false;
	}

	#--------------------------------------
	#	ユーザがプロジェクトに登録しているかどうか
	function is_registed( $user_cd , $project_id = null ){
		static $RTN = null;
		if( !is_null( $RTN ) ){ return $RTN; }

		if( is_null( $project_id ) ){ $project_id = $this->conf->info_projectid; }

		$RTN = false;
		if( is_array( $this->conf->rdb_usertable ) ){
			#	【 DB版 】
			#	$this->conf->rdb_usertable が配列だった場合、
			#	ユーザ情報をDB管理しているとみなす。
			$sql = 'SELECT registed_flg FROM :D:tableName WHERE user_cd = :N:user_cd AND project_id = :S:project_id;';
			$bindData = array(
				'tableName'=>$this->conf->rdb_usertable['project_status'],
				'user_cd'=>$user_cd,
				'project_id'=>$project_id,
			);
			$sql = $this->dbh->bind( $sql , $bindData );
			$res = $this->dbh->sendquery( $sql );
			$value = $this->dbh->getval();
			if( $value[0]['registed_flg'] ){
				$RTN = true;
			}
			return	$RTN;
		}else{
			#	【 ファイル版 】
			if( $this->dbh->is_dir( $this->conf->path_userdir.$user_cd.'/projectdata/'.$project_id ) ){
				$RTN = true;
			}
			return	$RTN;
		}
		return	false;
	}




	/* ******************************************************************************************************************************************************** *
		ユーザ情報の作成・編集保存系
		anch: pickles_userinfo_createAndEdit_methods
	/* ******************************************************************************************************************************************************** */


	#--------------------------------------
	#	新しいユーザを作成する
	function create_newuser( $user_id , $user_name , $user_email , $user_pw , $device_id = null ){
		#	Pickles Framework 0.3.0 : $device_id を省略可能にした。

		$user_id = strtolower( $user_id );
		if( !$this->user->validate_user_id( $user_id ) ){
			#	IDの形式に沿わない場合は false
			return false;
		}
		if( $this->is_user_id( $user_id ) ){
			#	すでにユーザが存在する場合は false
			return false;
		}

		if( is_array( $this->conf->rdb_usertable ) ){
			#	【 DB版 】
			#	$this->conf->rdb_usertable が配列だった場合、
			#	ユーザ情報をDB管理しているとみなす。

			ob_start();?>
INSERT INTO :D:tableName (
	user_id,
	user_pw,
	user_name,
	user_email,
	device_id,
	tmp_pw,
	tmp_email,
	tmp_data,
	create_date,
	lastupdate_date,
	del_flg
)VALUES(
	:S:user_id,
	:S:user_pw,
	:S:user_name,
	:S:user_email,
	:S:device_id,
	:S:tmp_pw,
	:S:tmp_email,
	:S:tmp_data,
	:S:create_date,
	:S:lastupdate_date,
	:N:del_flg
);
<?php
			$sql = ob_get_contents();
			@ob_end_clean();
			$bindData = array(
				'tableName'=>$this->conf->rdb_usertable['master'],
				'user_id'=>$user_id,
				'user_name'=>$user_name,
				'user_email'=>$user_email,
				'user_pw'=>$this->user->crypt_user_password( $user_pw , $user_id ),
				'device_id'=>$device_id,
				'tmp_pw'=>null,
				'tmp_email'=>null,
				'tmp_data'=>null,
				'create_date'=>$this->dbh->int2datetime( time() ),
				'lastupdate_date'=>$this->dbh->int2datetime( time() ),
				'del_flg'=>0,
			);
			$sql = $this->dbh->bind( $sql , $bindData );
			$res = $this->dbh->sendquery( $sql );
			if( !$res ){
				return false;
			}
			$value = $this->dbh->getval();

			#	INSERT した、ユーザCDを記憶
			$this->last_insert_user_cd = $this->dbh->get_last_insert_id( null , $this->conf->rdb_usertable['master'].'_user_cd_seq' );

			$this->dbh->commit();

			return	true;

		}else{
			#	【 ファイル版 】

			$userdatdir = $this->conf->path_userdir;
			if( !$userdatdir ){ return	false; }

			$results = $this->dbh->mkdir( $userdatdir.'/'.$user_id );
			if( !$results ){
				return	false;
			}
			$results = array();
			$results['user_pw'] = $this->dbh->file_overwrite( $userdatdir.'/'.$user_id.'/pw.txt' , $this->user->crypt_user_password( $user_pw , $user_id ) );
			$results['user_name'] = $this->dbh->file_overwrite( $userdatdir.'/'.$user_id.'/name.txt' , $user_name );
			$results['user_email'] = $this->dbh->file_overwrite( $userdatdir.'/'.$user_id.'/email.txt' , $user_email );
			$results['device_id'] = $this->dbh->file_overwrite( $userdatdir.'/'.$user_id.'/device.txt' , $device_id );
			$results['user_info'] = $this->dbh->file_overwrite( $userdatdir.'/'.$user_id.'/info.csv' , '' );
			foreach( $results as $res ){
				if( !$res ){
					return	false;
				}
			}
			$this->last_insert_user_cd = $user_id;
			return	true;

		}
		return	true;
	}
	#--------------------------------------
	#	直前に作成したユーザのユーザCDを取得する
	function get_last_insert_user_cd(){
		return	$this->last_insert_user_cd;
	}

	#--------------------------------------
	#	ユーザ情報(基本情報)を保存する
	function update_userinfo_master( $user_cd , $user_name , $user_email , $user_pw = null , $device_id = null , $user_id = null ){
		#	Pickles Framework 0.4.3 : $user_id オプションが追加されました。
		#	※$user_idを指定しても、このメソッドでIDを変更することはできません。
		#	  この値は、専ら$user->crypt_user_password()に渡すためだけに利用されます。

		if( !$this->is_user( $user_cd ) ){ return false; }

		if( strlen( $user_pw ) && !strlen( $user_id ) ){
			#	Pickles Framework 0.4.3 追加の処理
			#	パスワードの変更を要求した場合は、
			#	$user_id が必須になる。
			$this->errors->error_log( 'パスワードを変更する場合は、ユーザIDを必ず指定する必要があります。' , __FILE__ , __LINE__ );
			return	false;
		}

		if( is_array( $this->conf->rdb_usertable ) ){
			#	【 DB版 】
			#	$this->conf->rdb_usertable が配列だった場合、
			#	ユーザ情報をDB管理しているとみなす。

			ob_start();
?>
UPDATE :D:tableName
SET
	user_name = :S:user_name,
	user_email = :S:user_email, 
<?php if( !is_null( $user_pw ) ){ ?>
	user_pw = :S:user_pw,
<?php } ?>
<?php if( !is_null( $device_id ) ){ ?>
	device_id = :S:device_id,
<?php } ?>
	lastupdate_date = :S:lastupdate_date
WHERE user_cd = :N:user_cd;
<?php
			$sql = ob_get_clean();
			$bindData = array(
				'tableName'=>$this->conf->rdb_usertable['master'],
				'user_cd'=>$user_cd,
				'user_id'=>$user_id,
				'user_name'=>$user_name,
				'user_email'=>$user_email,
				'user_pw'=>$this->user->crypt_user_password( $user_pw , $user_id ),
				'device_id'=>$device_id,
				'lastupdate_date'=>$this->dbh->int2datetime( time() ),
			);
			$sql = $this->dbh->bind( $sql , $bindData );
			$res = $this->dbh->sendquery( $sql );
			if( !$res ){
				return	false;
			}
			$value = $this->dbh->getval();
			$this->dbh->commit();
			return	true;

		}else{
			#	【 ファイル版 】

			$results = array();
			$results['user_name'] = $this->dbh->file_overwrite( $this->conf->path_userdir.$user_cd.'/name.txt' , $user_name );
			$results['user_email'] = $this->dbh->file_overwrite( $this->conf->path_userdir.$user_cd.'/email.txt' , $user_email );
			if( !is_null( $user_pw ) ){
				$results['user_pw'] = $this->dbh->file_overwrite( $this->conf->path_userdir.$user_cd.'/pw.txt' , $this->user->crypt_user_password( $user_pw , $user_cd ) );//ファイル管理の場合は、$user_cd と $user_id は同じになる。
			}else{
				$results['user_pw'] = true;
			}
			if( !is_null( $device_id ) ){
				$results['device_id'] = $this->dbh->file_overwrite( $this->conf->path_userdir.$user_cd.'/device.txt' , $device_id );
			}else{
				$results['device_id'] = true;
			}

			if( !$results['user_name'] || !$results['user_email'] || !$results['user_pw'] || !$results['device_id'] ){
				return	false;
			}
			return	true;
		}
		return	false;
	}

	#--------------------------------------
	#	ユーザ情報(基本情報)のうち、端末IDだけを更新する
	function update_device_id( $user_cd , $device_id = null ){
		if( !$this->is_user( $user_cd ) ){ return false; }

		if( is_array( $this->conf->rdb_usertable ) ){
			#	【 DB版 】
			#	$this->conf->rdb_usertable が配列だった場合、
			#	ユーザ情報をDB管理しているとみなす。

			ob_start();
?>
UPDATE :D:tableName
SET
	device_id = :S:device_id,
	lastupdate_date = :S:lastupdate_date
WHERE user_cd = :N:user_cd;
<?php
			$sql = ob_get_clean();
			$bindData = array(
				'tableName'=>$this->conf->rdb_usertable['master'],
				'user_cd'=>$user_cd,
				'device_id'=>$device_id,
				'lastupdate_date'=>$this->dbh->int2datetime( time() ),
			);
			$sql = $this->dbh->bind( $sql , $bindData );
			$res = $this->dbh->sendquery( $sql );
			if( !$res ){
				return	false;
			}
			$value = $this->dbh->getval();
			$this->dbh->commit();
			return	true;

		}else{
			#	【 ファイル版 】
			$results = array();
			$results['device_id'] = $this->dbh->file_overwrite( $this->conf->path_userdir.$user_cd.'/device.txt' , $device_id );
			if( !$results['device_id'] ){
				return	false;
			}
			return	true;
		}
		return	false;
	}

	#--------------------------------------
	#	ユーザ一時情報(基本情報)を保存する
	function update_userinfo_master_tmp( $user_cd , $tmp_pw = null , $tmp_email = null , $tmp_data = null ){
		if( !$this->is_user( $user_cd ) ){ return false; }

		if( is_array( $this->conf->rdb_usertable ) ){
			#	【 DB版 】
			#	$this->conf->rdb_usertable が配列だった場合、
			#	ユーザ情報をDB管理しているとみなす。

			ob_start();
?>
UPDATE :D:tableName
SET
	tmp_pw = :S:tmp_pw,
	tmp_email = :S:tmp_email,
	tmp_data = :S:tmp_data,
	lastupdate_date = :S:lastupdate_date
WHERE user_cd = :N:user_cd;
<?php
			$sql = ob_get_clean();
			$bindData = array(
				'tableName'=>$this->conf->rdb_usertable['master'],
				'user_cd'=>$user_cd,
				'tmp_pw'=>$tmp_pw,
				'tmp_email'=>$tmp_email,
				'tmp_data'=>$tmp_data,
				'lastupdate_date'=>$this->dbh->int2datetime( time() ),
			);
			$sql = $this->dbh->bind( $sql , $bindData );
			$res = $this->dbh->sendquery( $sql );
			if( !$res ){
				return	false;
			}
			$value = $this->dbh->getval();
			$this->dbh->commit();
			return	true;

		}else{
			#	【 ファイル版 】
			$results = array();
			$results['tmp_pw'] = $this->dbh->file_overwrite( $this->conf->path_userdir.$user_cd.'/tmp_pw.txt' , $tmp_pw );
			$results['tmp_email'] = $this->dbh->file_overwrite( $this->conf->path_userdir.$user_cd.'/tmp_email.txt' , $tmp_email );
			$results['tmp_data'] = $this->dbh->file_overwrite( $this->conf->path_userdir.$user_cd.'/tmp_data.txt' , $tmp_data );

			if( !$results['tmp_pw'] || !$results['tmp_email'] || !$results['tmp_data'] ){
				return	false;
			}
			return	true;
		}
		return	false;
	}

	#--------------------------------------
	#	ユーザ一時情報(基本情報)をクリアする
	function clear_userinfo_master_tmp( $user_cd ){
		if( !$this->is_user( $user_cd ) ){ return false; }

		if( is_array( $this->conf->rdb_usertable ) ){
			#	【 DB版 】
			#	$this->conf->rdb_usertable が配列だった場合、
			#	ユーザ情報をDB管理しているとみなす。

			ob_start();
?>
UPDATE :D:tableName
SET
	tmp_pw = :S:tmp_pw,
	tmp_email = :S:tmp_email,
	tmp_data = :S:tmp_data,
	lastupdate_date = :S:lastupdate_date
WHERE user_cd = :N:user_cd;
<?php
			$sql = ob_get_clean();
			$bindData = array(
				'tableName'=>$this->conf->rdb_usertable['master'],
				'user_cd'=>$user_cd,
				'tmp_pw'=>null,
				'tmp_email'=>null,
				'tmp_data'=>null,
				'lastupdate_date'=>$this->dbh->int2datetime( time() ),
			);
			$sql = $this->dbh->bind( $sql , $bindData );
			$res = $this->dbh->sendquery( $sql );
			if( !$res ){
				return	false;
			}
			$value = $this->dbh->getval();
			$this->dbh->commit();
			return	true;

		}else{
			#	【 ファイル版 】
			$results = array();
			$results['tmp_pw'] = $this->dbh->rmdir( $this->conf->path_userdir.$user_cd.'/tmp_pw.txt' );
			$results['tmp_email'] = $this->dbh->rmdir( $this->conf->path_userdir.$user_cd.'/tmp_email.txt' );
			$results['tmp_data'] = $this->dbh->rmdir( $this->conf->path_userdir.$user_cd.'/tmp_data.txt' );

			if( !$results['tmp_pw'] || !$results['tmp_email'] || !$results['tmp_data'] ){
				return	false;
			}
			return	true;
		}
		return	false;
	}



	#--------------------------------------
	#	ユーザ情報(プロパティ情報)を保存する
	function update_userinfo_property( $user_cd , $properties = array() ){
		if( !$this->is_user( $user_cd ) ){ return false; }

		$userinfo_define = $this->user->get_userinfo_definition();

		if( is_array( $this->conf->rdb_usertable ) ){
			#	【 DB版 】
			#	$this->conf->rdb_usertable が配列だった場合、
			#	ユーザ情報をDB管理しているとみなす。

			#--------------------------------------
			#	SQL3種作成
			ob_start();?>
SELECT * FROM :D:tableName
WHERE user_cd = :N:user_cd AND keystr = :S:keystr;
			<?php
			$sql['select'] = ob_get_contents();
			@ob_end_clean();

			ob_start();?>
INSERT INTO :D:tableName (
	user_cd,
	keystr,
	valstr,
	create_date,
	lastupdate_date
) VALUES (
	:N:user_cd,
	:S:keystr,
	:S:valstr,
	:S:create_date,
	:S:lastupdate_date
);
			<?php
			$sql['insert'] = ob_get_contents();
			@ob_end_clean();

			ob_start();?>
UPDATE :D:tableName
SET
	valstr = :S:valstr,
	lastupdate_date = :S:lastupdate_date
WHERE user_cd = :N:user_cd AND keystr = :S:keystr;
			<?php
			$sql['update'] = ob_get_contents();
			@ob_end_clean();

			#	/ SQL3種作成
			#--------------------------------------

			foreach( array_keys( $userinfo_define ) as $Line ){
				if( !array_key_exists( $Line , $properties ) ){ continue; }

				#	共通バインドデータ
				$bindData = array(
					'tableName'=>$this->conf->rdb_usertable['property'],
					'user_cd'=>$user_cd,
					'keystr'=>$Line,
					'valstr'=>$properties[$Line],
					'create_date'=>$this->dbh->int2datetime( time() ),
					'lastupdate_date'=>$this->dbh->int2datetime( time() ),
				);

				#	まず、レコードがあるかどうか確認
				$sqlFinal = $this->dbh->bind( $sql['select'] , $bindData );
				$res = $this->dbh->sendquery( $sqlFinal );
				$value = $this->dbh->getval();
				$sqlTitle = '';
				if( count( $value ) ){
					#	存在したら、UPDATE
					$sqlTitle = 'update';
				}else{
					#	なかったら、INSERT
					$sqlTitle = 'insert';
				}

				$sqlFinal = $this->dbh->bind( $sql[$sqlTitle] , $bindData );
				$res = $this->dbh->sendquery( $sqlFinal );
				if( !$res ){
					$this->dbh->rollback();
					return	false;
				}
				$value = $this->dbh->getval();

			}

			$this->dbh->commit();
			return	true;

		}else{
			#	【 ファイル版 】
			#	現在登録されている情報を取得し、
			#	今回変更するところのみ上書き
			$user_properties = $this->get_userinfo_property( $user_cd );
			foreach( array_keys( $properties ) as $Line ){
				$user_properties[$Line] = $properties[$Line];
			}

			#	ユーザ情報の定義と$user_propertiesをマージして、CSVを作成
			$ETDW = '';
			foreach( array_keys( $userinfo_define ) as $Line ){
				$ETDW .= '"'.preg_replace( '/"/' , '""' , $Line ).'","'.preg_replace( '/"/' , '""' , $user_properties[$Line] ).'"'."\n";
			}
			$result = $this->dbh->file_overwrite( $this->conf->path_userdir.$user_cd.'/info.csv' , $ETDW );
			return	$result;
		}
		return	false;
	}


	#--------------------------------------
	#	ユーザ情報(プロジェクトステータス情報)を保存
	function save_userinfo_project_status( $user_cd , $project_id = null , $registed_flg = 0 , $authlevel = null ){
		if( !$this->is_user( $user_cd ) ){ return false; }
		if( is_null( $project_id ) ){ $project_id = $this->conf->info_projectid; }
		if( !strlen( $project_id ) ){ return false; }
		if( !preg_match( '/^[a-zA-Z0-9_.-]+$/' , $project_id ) ){ return false; }

		if( !$registed_flg ){
			#	registed_flgがemptyなら、プロジェクト関連情報を抹消する。
			return	$this->delete_authorpermissions( $user_cd , $project_id );
		}

		if( is_array( $this->conf->rdb_usertable ) ){
			#	【 DB版 】

			#--------------------------------------
			#	SQL3種作成
			ob_start();?>
SELECT * FROM :D:tableName
WHERE user_cd = :N:user_cd AND project_id = :S:project_id;
			<?php
			$sql['select'] = ob_get_contents();
			@ob_end_clean();

			ob_start();?>
INSERT INTO :D:tableName (
	user_cd,
	project_id,
	registed_flg,
	authlevel,
	create_date,
	lastupdate_date
) VALUES (
	:N:user_cd,
	:S:project_id,
	:N:registed_flg,
	:N:authlevel,
	:S:create_date,
	:S:lastupdate_date
);
			<?php
			$sql['insert'] = ob_get_contents();
			@ob_end_clean();

			ob_start();?>
UPDATE :D:tableName
SET
	registed_flg = :N:registed_flg,
	authlevel = :N:authlevel,
	lastupdate_date = :S:lastupdate_date
WHERE user_cd = :N:user_cd AND project_id = :S:project_id;
			<?php
			$sql['update'] = ob_get_contents();
			@ob_end_clean();
			#	/ SQL3種作成
			#--------------------------------------

			if( is_null( $authlevel ) ){
				$authlevel = null;
			}else{
				$authlevel = intval( $authlevel );
			}

			foreach( array_keys( $sql ) as $sqlTitle ){
				$bindData = array(
					'tableName'=>$this->conf->rdb_usertable['project_status'],
					'user_cd'=>$user_cd,
					'project_id'=>$project_id,
					'registed_flg'=>intval( $registed_flg ),
					'authlevel'=>$authlevel,
					'create_date'=>$this->dbh->int2datetime( time() ),
					'lastupdate_date'=>$this->dbh->int2datetime( time() ),
				);
				$sql[$sqlTitle] = $this->dbh->bind( $sql[$sqlTitle] , $bindData );
			}

			#	まず、レコードがあるかどうか確認
			$res = $this->dbh->sendquery( $sql['select'] );
			$value = $this->dbh->getval();
			$sqlTitle = '';
			if( count( $value ) ){
				#	存在したら、UPDATE
				$sqlTitle = 'update';
			}else{
				#	なかったら、INSERT
				$sqlTitle = 'insert';
			}

			$res = $this->dbh->sendquery( $sql[$sqlTitle] );
			if( !$res ){
				return	false;
			}
			$value = $this->dbh->getval();

			$this->dbh->commit();
			return	true;

		}else{
			#	【 ファイル版 】

			$userdatdir = $this->conf->path_userdir;
			if( !$this->dbh->is_dir( $userdatdir ) ){
				$this->errors->error_log( '[ '.$userdatdir.' ]が、存在しません。' , __FILE__ , __LINE__ );
				return	false;
			}

			if( !$this->dbh->is_dir( $userdatdir.'/'.$user_cd.'/projectdata' ) ){
				if( !$this->dbh->mkdir( $userdatdir.'/'.$user_cd.'/projectdata' ) ){
					return	false;
				}
				clearstatcache();
			}

			if( !$this->dbh->is_dir( $userdatdir.'/'.$user_cd.'/projectdata/'.$project_id ) ){
				if( !$this->dbh->mkdir( $userdatdir.'/'.$user_cd.'/projectdata/'.$project_id ) ){
					return	false;
				}
				clearstatcache();
			}
			if( !$this->dbh->is_dir( $userdatdir.'/'.$user_cd.'/projectdata/'.$project_id.'/auth' ) ){
				if( !$this->dbh->mkdir( $userdatdir.'/'.$user_cd.'/projectdata/'.$project_id.'/auth' ) ){
					return	false;
				}
				clearstatcache();
			}

			$basedir = $userdatdir.'/'.$user_cd.'/projectdata/'.$project_id.'/auth';
			if( is_null( $authlevel ) ){
				$this->dbh->rmdir( $basedir.'/authlevel.int' );
			}else{
				$this->dbh->file_overwrite( $basedir.'/authlevel.int' , intval( $authlevel ) );
			}
			return	true;

		}
		return	null;
	}

	#--------------------------------------
	#	ユーザ情報(プロジェクトユーザグループ所属)を保存
	function save_userinfo_project_authgroup( $user_cd , $project_id = null , $authGroup = array() ){
		if( !$this->is_user( $user_cd ) ){ return false; }
		if( is_null( $project_id ) ){ $project_id = $this->conf->info_projectid; }
		if( !strlen( $project_id ) ){ return false; }

		if( is_array( $this->conf->rdb_usertable ) ){
			#	【 DB版 】
			#	$this->conf->rdb_usertable が配列だった場合、
			#	ユーザ情報をDB管理しているとみなす。

			#--------------------------------------
			#	SQL3種作成
			ob_start();?>
SELECT * FROM :D:tableName WHERE user_cd = :N:user_cd AND project_id = :S:project_id AND group_cd = :N:group_cd;
			<?php
			$sql['select'] = ob_get_contents();
			@ob_end_clean();

			ob_start();?>
INSERT INTO :D:tableName (
	user_cd,
	project_id,
	group_cd,
	active_flg,
	create_date,
	lastupdate_date
) VALUES (
	:N:user_cd,
	:S:project_id,
	:N:group_cd,
	:N:active_flg,
	:S:create_date,
	:S:lastupdate_date
);
			<?php
			$sql['insert'] = ob_get_contents();
			@ob_end_clean();

			ob_start();?>
UPDATE :D:tableName
SET
	active_flg = :N:active_flg,
	lastupdate_date = :S:lastupdate_date
WHERE user_cd = :N:user_cd AND project_id = :S:project_id AND group_cd = :N:group_cd;
			<?php
			$sql['update'] = ob_get_contents();
			@ob_end_clean();

			#	/ SQL3種作成
			#--------------------------------------

			foreach( array_keys( $authGroup ) as $auth_group_cd ){
				if( $authGroup[$auth_group_cd] ){
					$active_flg = 1;
				}else{
					$active_flg = 0;
				}
				$bindData = array(
					'tableName'=>$this->conf->rdb_usertable['project_authgroup'],
					'user_cd'=>$user_cd,
					'project_id'=>$project_id,
					'group_cd'=>$auth_group_cd,
					'active_flg'=>$active_flg,
					'create_date'=>$this->dbh->int2datetime( time() ),
					'lastupdate_date'=>$this->dbh->int2datetime( time() ),
				);

				#	まず、レコードがあるかどうか確認
				$sqlFinal = $this->dbh->bind( $sql['select'] , $bindData );
				$res = $this->dbh->sendquery( $sqlFinal );
				$value = $this->dbh->getval();
				$sqlTitle = '';
				if( is_array( $value ) && count( $value ) ){
					#	存在したら、UPDATE
					$sqlTitle = 'update';
				}else{
					#	なかったら、INSERT
					$sqlTitle = 'insert';
				}

				$sqlFinal = $this->dbh->bind( $sql[$sqlTitle] , $bindData );
				$res = $this->dbh->sendquery( $sqlFinal );
				if( !$res ){
					$this->dbh->rollback();
					return	false;
				}
				$value = $this->dbh->getval();

			}

			$this->dbh->commit();
			return	true;

		}else{
			#	【 ファイル版 】

			$userdatdir = $this->conf->path_userdir;
			if( !$this->dbh->is_dir( $userdatdir.'/'.$user_cd.'/projectdata' ) ){
				$this->dbh->mkdir( $userdatdir.'/'.$user_cd.'/projectdata' );
			}
			if( !$this->dbh->is_dir( $userdatdir.'/'.$user_cd.'/projectdata/'.$project_id ) ){
				$this->dbh->mkdir( $userdatdir.'/'.$user_cd.'/projectdata/'.$project_id );
			}
			if( !$this->dbh->is_dir( $userdatdir.'/'.$user_cd.'/projectdata/'.$project_id.'/auth' ) ){
				$this->dbh->mkdir( $userdatdir.'/'.$user_cd.'/projectdata/'.$project_id.'/auth' );
			}

			$save2path = $userdatdir.'/'.$user_cd.'/projectdata/'.$project_id.'/auth';

			$ETDW = $this->get_userinfo_project_authgroup( $user_cd , $project_id );
			foreach( array_keys( $authGroup ) as $auth_group_cd ){
				if( $authGroup[$auth_group_cd] ){
					$ETDW[$auth_group_cd] = true;
				}else{
					$ETDW[$auth_group_cd] = false;
				}
			}

			#	出来上がった配列を、保存できる形式に変換
			$ETDW = text::data2phpsrc( $ETDW );

			#	保存する
			return	$this->dbh->file_overwrite( $save2path.'/authgroup.array' , $ETDW );

		}
		return	null;
	}

	#--------------------------------------
	#	ユーザ情報(編集権限オプション)を保存
	function save_userinfo_project_authoptions( $user_cd , $project_id = null , $authOptions = array() ){
		if( !$this->is_user( $user_cd ) ){ return false; }
		if( is_null( $project_id ) ){ $project_id = $this->conf->info_projectid; }
		if( !strlen( $project_id ) ){ return false; }
		$authoptions_define = $this->user->get_authoptions_definition();
		if( !is_array( $authoptions_define ) ){
			#	定義書が配列じゃなければ、失敗。
			return	false;
		}

		if( is_array( $this->conf->rdb_usertable ) ){
			#	【 DB版 】
			#	$this->conf->rdb_usertable が配列だった場合、
			#	ユーザ情報をDB管理しているとみなす。


			#--------------------------------------
			#	SQL3種作成
			ob_start();?>
SELECT * FROM :D:tableName WHERE user_cd = :N:user_cd AND project_id = :S:project_id AND keystr = :S:keystr;
			<?php
			$sql['select'] = ob_get_contents();
			@ob_end_clean();

			ob_start();?>
INSERT INTO :D:tableName (
	user_cd,
	project_id,
	keystr,
	valstr,
	create_date,
	lastupdate_date
) VALUES (
	:N:user_cd,
	:S:project_id,
	:S:keystr,
	:S:valstr,
	:S:create_date,
	:S:lastupdate_date
);
			<?php
			$sql['insert'] = ob_get_contents();
			@ob_end_clean();

			ob_start();?>
UPDATE :D:tableName
SET
	valstr = :S:valstr,
	lastupdate_date = :S:lastupdate_date
WHERE user_cd = :N:user_cd AND project_id = :S:project_id AND keystr = :S:keystr;
			<?php
			$sql['update'] = ob_get_contents();
			@ob_end_clean();

			#	/ SQL3種作成
			#--------------------------------------

			foreach( array_keys( $authoptions_define ) as $Line ){
				$bindData = array(
					'tableName'=>$this->conf->rdb_usertable['project_authoptions'],
					'user_cd'=>$user_cd,
					'project_id'=>$project_id,
					'keystr'=>$Line,
					'valstr'=>$authOptions[$Line],
					'create_date'=>$this->dbh->int2datetime( time() ),
					'lastupdate_date'=>$this->dbh->int2datetime( time() ),
				);

				#	まず、レコードがあるかどうか確認
				$sqlFinal = $this->dbh->bind( $sql['select'] , $bindData );
				$res = $this->dbh->sendquery( $sqlFinal );
				$value = $this->dbh->getval();
				$sqlTitle = '';
				if( count( $value ) ){
					#	存在したら、UPDATE
					$sqlTitle = 'update';
				}else{
					#	なかったら、INSERT
					$sqlTitle = 'insert';
				}

				$sqlFinal = $this->dbh->bind( $sql[$sqlTitle] , $bindData );
				$res = $this->dbh->sendquery( $sqlFinal );
				if( !$res ){
					$this->dbh->rollback();
					return	false;
				}
				$value = $this->dbh->getval();

			}

			$this->dbh->commit();
			return	true;

		}else{
			#	【 ファイル版 】
			$ETDW = array();
			foreach( array_keys( $authoptions_define ) as $Line ){
				$ETDW[$Line] = $authOptions[$Line];
			}
			$ETDW = text::data2phpsrc( $ETDW );
			$saveTargetPath = $this->conf->path_userdir.$user_cd.'/projectdata/'.$project_id.'/auth/authoptions.array';
			$result = $this->dbh->file_overwrite( $saveTargetPath , $ETDW );
			return	$result;

		}
		return	null;
	}

	#--------------------------------------
	#	ユーザの全ての編集者権限情報を削除する
	function delete_authorpermissions( $user_cd , $project_id = null ){
		if( !$this->is_user( $user_cd ) ){ return false; }
		if( is_null( $project_id ) ){ $project_id = $this->conf->info_projectid; }
		if( !strlen( $project_id ) ){ return false; }

		if( is_array( $this->conf->rdb_usertable ) ){
			#	【 DB版 】

			ob_start();?>
DELETE FROM :D:tableName WHERE user_cd = :N:user_cd AND project_id = :S:project_id;
			<?php
			$sql = ob_get_contents();
			@ob_end_clean();

			$targetTableNames = array(
				'project_status',
				'project_authoptions',
				'project_authgroup',
				'project_datas',
			);

			foreach( $targetTableNames as $tableName ){
				$bindData = array(
					'tableName'=>$this->conf->rdb_usertable[$tableName],
					'user_cd'=>$user_cd,
					'project_id'=>$project_id,
				);
				$sqlFinish = $this->dbh->bind( $sql , $bindData );
				$res = $this->dbh->sendquery( $sqlFinish );
				if( !$res ){
					$this->dbh->rollback();
					return	false;
				}
				$value = $this->dbh->getval();

			}

			$this->dbh->commit();
			return	true;

		}else{
			#	【 ファイル版 】
			if( !$this->dbh->is_dir( $this->conf->path_userdir ) ){
				return	false;
			}
			if( !$this->dbh->is_dir( $this->conf->path_userdir.$user_cd.'/projectdata/'.$project_id ) ){
				return	true;
			}
			return	$this->dbh->rmdir( $this->conf->path_userdir.$user_cd.'/projectdata/'.$project_id );

		}
		return	null;
	}



	#--------------------------------------
	#	ユーザの登録情報を全て抹消する
	function delete_user( $user_cd , $physical_flg = false ){
		#	Pickles Framework 0.4.3 : $physical_flg を追加。(物理的な削除を指示するフラグ)

		if( !$this->is_user( $user_cd ) ){ return false; }

		if( is_array( $this->conf->rdb_usertable ) ){
			#	【 DB版 】
			#	$this->conf->rdb_usertable が配列だった場合、
			#	ユーザ情報をDB管理しているとみなす。

			if( $physical_flg ){
				#	DB管理している場合は、
				#	物理削除フラグを受け取ると、物理削除するように振舞う。
				#	デフォルトは論理削除。
				$sql = 'DELETE FROM :D:tableName WHERE user_cd = :N:user_cd;';
				$targetTableNames = array(
					'property',
					'project_status',
					'project_authoptions',
					'project_authgroup',
					'project_datas',
					'master',
				);

				foreach( $targetTableNames as $tableName ){
					$bindData = array(
						'tableName'=>$this->conf->rdb_usertable[$tableName],
						'user_cd'=>$user_cd,
					);
					$sqlFinish = $this->dbh->bind( $sql , $bindData );
					$res = $this->dbh->sendquery( $sqlFinish );
					if( !$res ){
						$this->dbh->rollback();
						return	false;
					}
					$value = $this->dbh->getval();

				}

				$this->dbh->commit();
				return	true;

			}else{
				#	デフォルトは論理削除。
				#	※Pickles Framework 0.4.2 とそれ以前のデフォルトは、物理削除でした。

				$sql = 'UPDATE :D:tableName SET del_flg = 1 WHERE user_cd = :N:user_cd;';

				$bindData = array(
					'tableName'=>$this->conf->rdb_usertable['master'],
					'user_cd'=>$user_cd,
				);
				$sqlFinish = $this->dbh->bind( $sql , $bindData );
				$res = $this->dbh->sendquery( $sqlFinish );
				if( !$res ){
					$this->dbh->rollback();
					return	false;
				}
				$value = $this->dbh->getval();

				$this->dbh->commit();
				return	true;

			}


		}else{
			#	【 ファイル版 】
			$result = false;
			if( $this->dbh->is_dir( $this->conf->path_userdir.$user_cd ) ){
				$result = $this->dbh->rmdir( $this->conf->path_userdir.$user_cd );
			}
			return	$result;

		}
		return	null;
	}


	/* ******************************************************************************************************************************************************** *
		ユーザグループ作成・編集保存系
		anch: pickles_groupinfo_createAndEdit_methods
	/* ******************************************************************************************************************************************************** */

	#--------------------------------------
	#	新しいグループを作成する
	function create_newgroup( $group_cd , $group_name , $description = null , $project_id = null ){
		if( !strlen( $project_id ) ){ $project_id = $this->conf->info_projectid; }
		if( !strlen( $project_id ) ){ return false; }
		if( $this->is_group( $group_cd , $project_id ) ){ return false; }//すでに存在していたらダメ。
		if( !strlen( $group_name ) ){ return false; }

		if( is_array( $this->conf->rdb_usertable ) ){
			#	【 DB版 】
			#	$this->conf->rdb_usertable が配列だった場合、
			#	ユーザ情報をDB管理しているとみなす。

			ob_start();?>
INSERT INTO :D:tableName (
	group_name,
	description,
	create_date,
	lastupdate_date,
	del_flg
)VALUES(
	:S:group_name,
	:S:description,
	:S:create_date,
	:S:lastupdate_date,
	:N:del_flg
);
<?php
			$sql = ob_get_contents();
			@ob_end_clean();
			$bindData = array(
				'tableName'=>$this->conf->rdb_usertable['group'],
				'group_name'=>$group_name,
				'description'=>$description,
				'create_date'=>$this->dbh->int2datetime( time() ),
				'lastupdate_date'=>$this->dbh->int2datetime( time() ),
				'del_flg'=>0,
			);
			$sql = $this->dbh->bind( $sql , $bindData );
			$res = $this->dbh->sendquery( $sql );
			if( !$res ){
				$this->dbh->rollback();
				return	false;
			}

			#	INSERT した、グループCDを記憶
			$this->last_insert_group_cd = $this->dbh->get_last_insert_id( null , $this->conf->rdb_usertable['group'].'_group_cd_seq');

			$this->dbh->commit();
			return	true;

		}else{
			#	【 ファイル版 】

			$saveToDir = $this->conf->path_userdir.'@_SYSTEM/authgroup';
			if( !$this->dbh->is_dir( $saveToDir ) ){
				#	保存先ディレクトリの作成
				if( !$this->dbh->mkdirall( $saveToDir ) ){
					return	false;
				}
			}

			$path_define_file = $saveToDir.'/authgroup_define.array';

			$authgroup = @include( $path_define_file );
			if( !is_null( $authgroup[$project_id][$group_cd] ) ){
				#	既に存在していたら、上書きしてはダメ。(create_newだから)
				return	false;
			}
			$authgroup[$project_id][$group_cd] = array(
				'group_cd'=>$group_cd,
				'group_name'=>$group_name,
				'project_id'=>$project_id,
				'description'=>$description,
				'lastupdate_date'=>time(),
			);

			#	出来上がった配列を、保存できる形式に変換
			$ETDW = text::data2phpsrc( $authgroup );

			#	保存する
			if( !$this->dbh->file_overwrite( $path_define_file , $ETDW ) ){
				return	false;
			}
			$this->last_insert_group_cd = $group_cd;
			return	true;

		}
		return	true;
	}
	#--------------------------------------
	#	最後に追加したユーザグループのグループコードを取り出す。
	function get_last_insert_group_cd(){
		return	$this->last_insert_group_cd;
	}

	#--------------------------------------
	#	グループ情報を保存する
	function update_groupinfo_master( $group_cd , $group_name , $description = null , $project_id = null){
		if( !strlen( $project_id ) ){ $project_id = $this->conf->info_projectid; }
		if( !strlen( $project_id ) ){ return false; }
		if( !$this->is_group( $group_cd , $project_id ) ){ return false; }

		if( is_array( $this->conf->rdb_usertable ) ){
			#	【 DB版 】
			#	$this->conf->rdb_usertable が配列だった場合、
			#	ユーザ情報をDB管理しているとみなす。

			ob_start();
?>
UPDATE :D:tableName
SET
	group_name = :S:group_name,
	description = :S:description,
	lastupdate_date = :S:lastupdate_date
WHERE
	group_cd = :N:group_cd
;
<?php
			$sql = ob_get_clean();
			$bindData = array(
				'tableName'=>$this->conf->rdb_usertable['group'],
				'group_cd'=>$group_cd,
				'group_name'=>$group_name,
				'description'=>$description,
				'lastupdate_date'=>$this->dbh->int2datetime( time() ),
			);
			$sql = $this->dbh->bind( $sql , $bindData );
			$res = $this->dbh->sendquery( $sql );
			if( !$res ){
				$this->dbh->rollback();
				return	false;
			}
			$value = $this->dbh->getval();
			$this->dbh->commit();
			return	true;

		}else{
			#	【 ファイル版 】

			$saveToDir = $this->conf->path_userdir.'@_SYSTEM/authgroup';
			if( !$this->dbh->is_dir( $saveToDir ) ){
				#	保存先ディレクトリの作成
				if( !$this->dbh->mkdirall( $saveToDir ) ){
					return	false;
				}
			}

			$path_define_file = $saveToDir.'/authgroup_define.array';

			$authgroup = @include( $path_define_file );
			if( !is_array( $authgroup[$project_id] ) ){
				$authgroup[$project_id] = array();
			}

			$authgroup[$project_id][$group_cd] = array(
				'group_cd'=>$group_cd,
				'group_name'=>$group_name,
				'project_id'=>$project_id,
				'description'=>$description,
				'lastupdate_date'=>time(),
			);

			#	出来上がった配列を、保存できる形式に変換
			$ETDW = text::data2phpsrc( $authgroup );

			#	保存する
			if( !$this->dbh->file_overwrite( $path_define_file , $ETDW ) ){
				return	false;
			}
			return	true;

		}
		return	false;
	}

	#--------------------------------------
	#	グループ情報(編集権限オプション)を保存
	function save_groupinfo_project_authoptions( $group_cd , $authOptions = array() , $project_id = null ){
		if( is_null( $project_id ) ){ $project_id = $this->conf->info_projectid; }
		if( !strlen( $project_id ) ){ return false; }
		if( !$this->is_group( $group_cd , $project_id ) ){ return false; }
		$authoptions_define = $this->user->get_authoptions_definition();
		if( !is_array( $authoptions_define ) ){
			#	定義書が配列じゃなければ、失敗。
			return	false;
		}

		if( is_array( $this->conf->rdb_usertable ) ){
			#	【 DB版 】
			#	$this->conf->rdb_usertable が配列だった場合、
			#	ユーザ情報をDB管理しているとみなす。


			#--------------------------------------
			#	SQL3種作成
			ob_start();?>
SELECT * FROM :D:tableName WHERE group_cd = :N:group_cd AND keystr = :S:keystr;
			<?php
			$sql['select'] = ob_get_contents();
			@ob_end_clean();

			ob_start();?>
INSERT INTO :D:tableName (
	group_cd,
	keystr,
	valstr,
	create_date,
	lastupdate_date
) VALUES (
	:N:group_cd,
	:S:keystr,
	:S:valstr,
	:S:create_date,
	:S:lastupdate_date
);
			<?php
			$sql['insert'] = ob_get_contents();
			@ob_end_clean();

			ob_start();?>
UPDATE :D:tableName
SET
	valstr = :S:valstr,
	lastupdate_date = :S:lastupdate_date
WHERE group_cd = :N:group_cd AND keystr = :S:keystr;
			<?php
			$sql['update'] = ob_get_contents();
			@ob_end_clean();

			#	/ SQL3種作成
			#--------------------------------------

			foreach( array_keys( $authoptions_define ) as $Line ){
				$bindData = array(
					'tableName'=>$this->conf->rdb_usertable['group_authoptions'],
					'group_cd'=>$group_cd,
					'keystr'=>$Line,
					'valstr'=>$authOptions[$Line],
					'create_date'=>$this->dbh->int2datetime( time() ),
					'lastupdate_date'=>$this->dbh->int2datetime( time() ),
				);

				#	まず、レコードがあるかどうか確認
				$sqlFinal = $this->dbh->bind( $sql['select'] , $bindData );
				$res = $this->dbh->sendquery( $sqlFinal );
				$value = $this->dbh->getval();
				$sqlTitle = '';
				if( count( $value ) ){
					#	存在したら、UPDATE
					$sqlTitle = 'update';
				}else{
					#	なかったら、INSERT
					$sqlTitle = 'insert';
				}

				$sqlFinal = $this->dbh->bind( $sql[$sqlTitle] , $bindData );
				$res = $this->dbh->sendquery( $sqlFinal );
				if( !$res ){
					$this->dbh->rollback();
					return	false;
				}
				$value = $this->dbh->getval();

			}

			$this->dbh->commit();
			return	true;

		}else{
			#	【 ファイル版 】
			$saveTargetPath = $this->conf->path_userdir.'@_SYSTEM/authgroup/authgroup_define.array';

			$group_define = @include( $saveTargetPath );
			if( !is_array( $group_define[$project_id][$group_cd] ) ){
				#	グループCDが未定義だったらダメ。
				return	false;
			}
			$group_define[$project_id][$group_cd]['authoptions'] = array();

			foreach( array_keys( $authoptions_define ) as $Line ){
				$group_define[$project_id][$group_cd]['authoptions'][$Line] = $authOptions[$Line];
			}
			$ETDW = text::data2phpsrc( $group_define );
			return	$this->dbh->file_overwrite( $saveTargetPath , $ETDW );

		}
		return	null;
	}

	#--------------------------------------
	#	グループ情報を削除する
	function delete_groupinfo_master( $group_cd , $project_id = null){
		if( !strlen( $project_id ) ){ $project_id = $this->conf->info_projectid; }
		if( !strlen( $project_id ) ){ return false; }
		if( !$this->is_group( $group_cd , $project_id ) ){ return false; }

		if( is_array( $this->conf->rdb_usertable ) ){
			#	【 DB版 】
			#	$this->conf->rdb_usertable が配列だった場合、
			#	ユーザ情報をDB管理しているとみなす。

			ob_start();
?>
UPDATE :D:tableName
SET
	del_flg = :N:del_flg,
	lastupdate_date = :S:lastupdate_date
WHERE
	group_cd = :N:group_cd
--	AND project_id = :S:project_id
;
<?php
			$sql = ob_get_clean();
			$bindData = array(
				'tableName'=>$this->conf->rdb_usertable['group'],
				'group_cd'=>$group_cd,
#				'project_id'=>$project_id,
				'del_flg'=>1,
				'lastupdate_date'=>$this->dbh->int2datetime( time() ),
			);
			$sql = $this->dbh->bind( $sql , $bindData );
			$res = $this->dbh->sendquery( $sql );
			if( !$res ){
				return	false;
			}
			$value = $this->dbh->getval();
			$this->dbh->commit();
			return	true;

		}else{
			#	【 ファイル版 】

			$saveToDir = $this->conf->path_userdir.'@_SYSTEM/authgroup';
			if( !$this->dbh->is_dir( $saveToDir ) ){
				#	保存先ディレクトリの作成
				if( !$this->dbh->mkdirall( $saveToDir ) ){
					return	false;
				}
			}

			$path_define_file = $saveToDir.'/authgroup_define.array';

			$authgroup = @include( $path_define_file );

			unset( $authgroup[$project_id][$group_cd] );

			#	出来上がった配列を、保存できる形式に変換
			$ETDW = text::data2phpsrc( $authgroup );

			#	保存する
			if( !$this->dbh->file_overwrite( $path_define_file , $ETDW ) ){
				return	false;
			}
			return	true;

		}
		return	false;
	}



	/* ******************************************************************************************************************************************************** *
		初期設定、セットアップ系
		anch: pickles_userinfo_setup_methods
	/* ******************************************************************************************************************************************************** */

	#--------------------------------------
	#	ユーザ管理テーブルを作成する
	function create_user_tables( $exec_mode = null ){
		#	このメソッドは、
		#	SQL[ CREATE TABLE ]を発行し、ユーザテーブルの初期セットアップを行います。
		#	ファイル管理版の場合は、ユーザディレクトリを作成します。

		if( is_array( $this->conf->rdb_usertable ) ){
			#	【 DB版 】

			switch( $this->conf->rdb['type'] ){
				case 'MySQL':
				case 'PostgreSQL':
				case 'SQLite':
#				case 'Oracle':
					break;
				default:
					return false;
					break;
			}

			$sql = array();
			$sqlComment = array();

			ob_start();?>
-- ****************************************************************************
-- PicklesFramework標準のユーザ管理テーブルを作成する
-- for <?php print $this->conf->rdb['type']; print "\n"; ?>
-- LastUpdate: 11:57 2009/11/02
-- ****************************************************************************

<?php
			$sqlComment['headerinfo'] = @ob_get_clean();

			#--------------------------------------
			#	master: ユーザマスタテーブル
			ob_start();?>
<?php if( $this->conf->rdb['type'] == 'PostgreSQL' ){ ?>
CREATE TABLE :D:tableName(
    user_cd    SERIAL NOT NULL,
    user_id    VARCHAR NOT NULL,
    user_pw    VARCHAR NOT NULL,
    user_name    VARCHAR,
    user_email    VARCHAR,
    device_id    VARCHAR,
    tmp_pw    VARCHAR,
    tmp_email    VARCHAR,
    tmp_data    TEXT,
    lastlogin_date    TIMESTAMP DEFAULT 'NOW',
    create_date    TIMESTAMP DEFAULT 'NOW',
    lastupdate_date    TIMESTAMP DEFAULT 'NOW',
    del_flg    INT2 NOT NULL DEFAULT '0'
);
<?php }elseif( $this->conf->rdb['type'] == 'SQLite' ){ ?>
-- --------------------------------------------------------------------------
-- ユーザマスタテーブル
CREATE TABLE :D:tableName(
    user_cd    INTEGER NOT NULL PRIMARY KEY,
    user_id    VARCHAR(64) NOT NULL,
    user_pw    VARCHAR(32) NOT NULL,
    user_name    VARCHAR(128),
    user_email    VARCHAR(128),
    device_id    VARCHAR(128),
    tmp_pw    VARCHAR(32),
    tmp_email    VARCHAR(128),
    tmp_data    TEXT,
    lastlogin_date    DATETIME DEFAULT NULL,
    create_date    DATETIME DEFAULT NULL,
    lastupdate_date    DATETIME DEFAULT NULL,
    del_flg    INT(1) NOT NULL DEFAULT '0'
);
<?php }else{ ?>
-- --------------------------------------------------------------------------
-- ユーザマスタテーブル
CREATE TABLE :D:tableName(
    user_cd    INT(11) NOT NULL,
    user_id    VARCHAR(64) NOT NULL,
    user_pw    VARCHAR(32) NOT NULL,
    user_name    VARCHAR(128),
    user_email    VARCHAR(128),
    device_id    VARCHAR(128),
    tmp_pw    VARCHAR(32),
    tmp_email    VARCHAR(128),
    tmp_data    TEXT,
    lastlogin_date    DATETIME DEFAULT NULL,
    create_date    DATETIME DEFAULT NULL,
    lastupdate_date    DATETIME DEFAULT NULL,
    del_flg    INT(1) NOT NULL DEFAULT '0'
);
<?php } ?>
<?php
			$sql['master'] = array();
			array_push( $sql['master'] , @ob_get_clean() );
			if( $this->conf->rdb['type'] == 'PostgreSQL' ){
				#	PostgreSQL
				array_push( $sql['master'] , 'ALTER TABLE :D:tableName ADD PRIMARY KEY ( user_cd );' );
				array_push( $sql['master'] , 'ALTER TABLE :D:tableName ADD UNIQUE ( user_id );' );
			}elseif( $this->conf->rdb['type'] == 'MySQL' ){
				#	MySQL
				array_push( $sql['master'] , 'ALTER TABLE :D:tableName ADD PRIMARY KEY ( user_cd );' );
				array_push( $sql['master'] , 'ALTER TABLE :D:tableName CHANGE user_cd user_cd INT(11) NOT NULL AUTO_INCREMENT;' );
				array_push( $sql['master'] , 'CREATE UNIQUE INDEX user_id ON :D:tableName (user_id(64));' );
			}

			#--------------------------------------
			#	property: プロパティテーブル
			ob_start();?>
<?php if( $this->conf->rdb['type'] == 'PostgreSQL' ){ ?>
CREATE TABLE :D:tableName(
    user_cd    INT NOT NULL,
    keystr    VARCHAR NOT NULL,
    valstr    TEXT NOT NULL,
    create_date    TIMESTAMP DEFAULT 'NOW',
    lastupdate_date    TIMESTAMP DEFAULT 'NOW'
);
<?php }else{ ?>
-- --------------------------------------------------------------------------
-- プロパティテーブル
CREATE TABLE :D:tableName(
    user_cd    INT(11) NOT NULL,
    keystr    VARCHAR(128) NOT NULL,
    valstr    TEXT NOT NULL,
    create_date    DATETIME DEFAULT NULL,
    lastupdate_date    DATETIME DEFAULT NULL
);
<?php } ?>

<?php
			$sql['property'][0] = @ob_get_clean();
			array_push( $sql['property'] , 'CREATE INDEX pxidx_:D:tableName ON :D:tableName (user_cd);' );

			#--------------------------------------
			#	project_status: プロジェクトステータステーブル
			ob_start();?>
<?php if( $this->conf->rdb['type'] == 'PostgreSQL' ){ ?>
CREATE TABLE :D:tableName(
    user_cd    INT NOT NULL,
    project_id    VARCHAR NOT NULL,
    registed_flg    INT NOT NULL DEFAULT '0',
    authlevel    INT2 DEFAULT NULL,
    lastlogin_date    TIMESTAMP DEFAULT 'NOW',
    create_date    TIMESTAMP DEFAULT 'NOW',
    lastupdate_date    TIMESTAMP DEFAULT 'NOW'
);
<?php }else{ ?>
-- --------------------------------------------------------------------------
-- プロジェクトステータステーブル
CREATE TABLE :D:tableName(
    user_cd    INT(11) NOT NULL,
    project_id    VARCHAR(64) NOT NULL,
    registed_flg    INT(1) NOT NULL DEFAULT '0',
    authlevel    INT(2) DEFAULT NULL,
    lastlogin_date    DATETIME DEFAULT NULL,
    create_date    DATETIME DEFAULT NULL,
    lastupdate_date    DATETIME DEFAULT NULL
);
<?php } ?>

<?php
			$sql['project_status'][0] = @ob_get_clean();
			if( $this->conf->rdb['type'] == 'MySQL' ){
				array_push( $sql['project_status'] , 'CREATE INDEX pxidx_:D:tableName ON :D:tableName (user_cd,project_id(10));' );
			}else{
				array_push( $sql['project_status'] , 'CREATE INDEX pxidx_:D:tableName ON :D:tableName (user_cd,project_id);' );
			}

			#--------------------------------------
			#	project_auth: ユーザ権限オプションテーブル
			ob_start();?>
<?php if( $this->conf->rdb['type'] == 'PostgreSQL' ){ ?>
CREATE TABLE :D:tableName(
    user_cd    INT NOT NULL,
    project_id    VARCHAR NOT NULL,
    keystr    VARCHAR NOT NULL,
    valstr    TEXT,
    create_date    TIMESTAMP DEFAULT 'NOW',
    lastupdate_date    TIMESTAMP DEFAULT 'NOW'
);
<?php }else{ ?>
-- --------------------------------------------------------------------------
-- ユーザ権限オプションテーブル
CREATE TABLE :D:tableName(
    user_cd    INT(11) NOT NULL,
    project_id    VARCHAR(64) NOT NULL,
    keystr    VARCHAR(128) NOT NULL,
    valstr    TEXT,
    create_date    DATETIME DEFAULT NULL,
    lastupdate_date    DATETIME DEFAULT NULL
);
<?php } ?>

<?php
			$sql['project_authoptions'][0] = @ob_get_clean();
			if( $this->conf->rdb['type'] == 'MySQL' ){
				array_push( $sql['project_authoptions'] , 'CREATE INDEX pxidx_:D:tableName ON :D:tableName (user_cd,project_id(10));' );
			}else{
				array_push( $sql['project_authoptions'] , 'CREATE INDEX pxidx_:D:tableName ON :D:tableName (user_cd,project_id);' );
			}

			#--------------------------------------
			#	project_datas: プロジェクトデータテーブル
			ob_start();?>
<?php if( $this->conf->rdb['type'] == 'PostgreSQL' ){ ?>
CREATE TABLE :D:tableName(
    user_cd    INT NOT NULL,
    project_id    VARCHAR NOT NULL,
    dataspace    TEXT,
    keystr    VARCHAR NOT NULL,
    valstr    TEXT,
    create_date    TIMESTAMP DEFAULT 'NOW',
    lastupdate_date    TIMESTAMP DEFAULT 'NOW'
);
<?php }else{ ?>
-- --------------------------------------------------------------------------
-- プロジェクトデータテーブル
CREATE TABLE :D:tableName(
    user_cd    INT(11) NOT NULL,
    project_id    VARCHAR(64) NOT NULL,
    dataspace    TEXT,
    keystr    VARCHAR(128) NOT NULL,
    valstr    TEXT,
    create_date    DATETIME DEFAULT NULL,
    lastupdate_date    DATETIME DEFAULT NULL
);
<?php } ?>

<?php
			$sql['project_datas'][0] = @ob_get_clean();
			if( $this->conf->rdb['type'] == 'MySQL' ){
				array_push( $sql['project_datas'] , 'CREATE INDEX pxidx_:D:tableName ON :D:tableName (user_cd,project_id(10));' );
			}else{
				array_push( $sql['project_datas'] , 'CREATE INDEX pxidx_:D:tableName ON :D:tableName (user_cd,project_id);' );
			}

			#--------------------------------------
			#	project_authgroup: ユーザグループ所属管理テーブル
			ob_start();?>
<?php if( $this->conf->rdb['type'] == 'PostgreSQL' ){ ?>
CREATE TABLE :D:tableName(
    user_cd    INT NOT NULL,
    project_id    VARCHAR NOT NULL,
    group_cd    INT NOT NULL,
    active_flg    INT2 NOT NULL DEFAULT '1',
    create_date    TIMESTAMP DEFAULT 'NOW',
    lastupdate_date    TIMESTAMP DEFAULT 'NOW'
);
<?php }else{ ?>
-- --------------------------------------------------------------------------
-- ユーザグループ所属管理テーブル
CREATE TABLE :D:tableName(
    user_cd    INT(11) NOT NULL,
    project_id    VARCHAR(64) NOT NULL,
    group_cd    INT(11) NOT NULL,
    active_flg    INT(1) NOT NULL DEFAULT '0',
    create_date    DATETIME DEFAULT NULL,
    lastupdate_date    DATETIME DEFAULT NULL
);
<?php } ?>

<?php
			$sql['project_authgroup'][0] = @ob_get_clean();
			if( $this->conf->rdb['type'] == 'MySQL' ){
				array_push( $sql['project_authgroup'] , 'CREATE INDEX pxidx_:D:tableName ON :D:tableName (user_cd,project_id(10),group_cd);' );
			}else{
				array_push( $sql['project_authgroup'] , 'CREATE INDEX pxidx_:D:tableName ON :D:tableName (user_cd,project_id,group_cd);' );
			}

			#--------------------------------------
			#	group: ユーザグループマスタテーブル
			ob_start();?>
<?php if( $this->conf->rdb['type'] == 'PostgreSQL' ){ ?>
CREATE TABLE :D:tableName(
    group_cd    SERIAL NOT NULL,
    group_name    VARCHAR,
    description    TEXT,
    create_date    TIMESTAMP DEFAULT 'NOW',
    lastupdate_date    TIMESTAMP DEFAULT 'NOW',
    del_flg    INT2 NOT NULL DEFAULT '0'
);
<?php }elseif( $this->conf->rdb['type'] == 'SQLite' ){ ?>
-- --------------------------------------------------------------------------
-- ユーザグループマスタテーブル
CREATE TABLE :D:tableName(
    group_cd    INTEGER NOT NULL PRIMARY KEY,
    group_name    VARCHAR(128),
    description    TEXT,
    create_date    DATETIME DEFAULT NULL,
    lastupdate_date    DATETIME DEFAULT NULL,
    del_flg    INT(1) NOT NULL DEFAULT '0'
);
<?php }else{ ?>
-- --------------------------------------------------------------------------
-- ユーザグループマスタテーブル
CREATE TABLE :D:tableName(
    group_cd    INT(11) NOT NULL,
    group_name    VARCHAR(128),
    description    TEXT,
    create_date    DATETIME DEFAULT NULL,
    lastupdate_date    DATETIME DEFAULT NULL,
    del_flg    INT(1) NOT NULL DEFAULT '0'
);
<?php } ?>

<?php
			$sql['group'] = array();
			array_push( $sql['group'] , @ob_get_clean() );
			if( $this->conf->rdb['type'] == 'PostgreSQL' ){
				#	PostgreSQL
				array_push( $sql['group'] , 'ALTER TABLE :D:tableName ADD PRIMARY KEY ( group_cd );' );
			}elseif( $this->conf->rdb['type'] == 'MySQL' ){
				#	MySQL
				array_push( $sql['group'] , 'ALTER TABLE :D:tableName ADD PRIMARY KEY ( group_cd );' );
				array_push( $sql['group'] , 'ALTER TABLE :D:tableName CHANGE group_cd group_cd INT(11) NOT NULL AUTO_INCREMENT;' );
			}

			#--------------------------------------
			#	project_auth: ユーザグループ権限オプションテーブル
			ob_start();?>
<?php if( $this->conf->rdb['type'] == 'PostgreSQL' ){ ?>
CREATE TABLE :D:tableName(
    group_cd    INT NOT NULL,
    keystr    VARCHAR NOT NULL,
    valstr    TEXT,
    create_date    TIMESTAMP DEFAULT 'NOW',
    lastupdate_date    TIMESTAMP DEFAULT 'NOW'
);
<?php }else{ ?>
-- --------------------------------------------------------------------------
-- ユーザグループ権限オプションテーブル
CREATE TABLE :D:tableName(
    group_cd    INT(11) NOT NULL,
    keystr    VARCHAR(128) NOT NULL,
    valstr    TEXT,
    create_date    DATETIME DEFAULT NULL,
    lastupdate_date    DATETIME DEFAULT NULL
);
<?php } ?>

<?php
			$sql['group_authoptions'][0] = @ob_get_clean();
			array_push( $sql['group_authoptions'] , 'CREATE INDEX pxidx_:D:tableName ON :D:tableName (group_cd);' );



			$targetTableNames = array();
			array_push( $targetTableNames , 'master' );
			array_push( $targetTableNames , 'property' );
			array_push( $targetTableNames , 'project_status' );
			array_push( $targetTableNames , 'project_authoptions' );
			array_push( $targetTableNames , 'project_authgroup' );
			array_push( $targetTableNames , 'project_datas' );
			array_push( $targetTableNames , 'group' );
			array_push( $targetTableNames , 'group_authoptions' );


			#	ダウンロード用に作成するソース
			$SQL4DOWNLOAD = $sqlComment['headerinfo']."\n";

			if( !strlen( $exec_mode ) ){
				#	$exec_modeが空白ならば、
				#	SQLを流すモード
				$this->dbh->start_transaction();
			}

			foreach( $targetTableNames as $tableName ){
				foreach( $sql[$tableName] as $sql_content ){
					$bindData = array(
						'tableName'=>$this->conf->rdb_usertable[$tableName],
					);
					$sqlFinal = $this->dbh->bind( $sql_content , $bindData );
					if( !strlen( $sqlFinal ) ){ continue; }
					$SQL4DOWNLOAD .= $sqlFinal."\n";

					if( !strlen( $exec_mode ) ){
						#	$exec_modeが空白ならば、
						#	SQLを流すモード
						if( !$this->dbh->sendquery( $sqlFinal ) ){
							$this->dbh->rollback();
						}
					}
				}
			}

			if( !strlen( $exec_mode ) ){
				#	$exec_modeが空白ならば、
				#	SQLを流すモード
				$this->dbh->commit();
			}elseif( $exec_mode == 'GET_SQL_SOURCE' ){
				return	$SQL4DOWNLOAD;
			}
			return	true;


		}else{
			#	【 ファイル版 】

			$userdatdir = $this->conf->path_userdir;
			if( !strlen( $userdatdir ) ){
				#	ユーザディレクトリパスが設定されていなければ、
				#	続行不可能。
				return	false;
			}
			$results = $this->dbh->mkdir( $userdatdir );
			if( !$results ){
				return	false;
			}

			$results = $this->dbh->mkdir( $userdatdir.'@_SYSTEM' );
			if( !$results ){
				return	false;
			}
			return	true;

		}

		return	null;
	}

}

?>