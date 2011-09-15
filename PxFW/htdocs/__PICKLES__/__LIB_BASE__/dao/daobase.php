<?php

#	Copyright (C)Tomoya Koyanagi.
#	LastUpdate : 2:03 2007/01/09

#******************************************************************************************************************
#	DAO(データベースアクセスオブジェクト)のオブジェクトクラス
class base_dao_daobase{
	var $conf;
	var $dbh;
	var $errors;
	function base_dao_daobase( &$conf , &$dbh , &$errors ){
		$this->conf = &$conf;
		$this->dbh = &$dbh;
		$this->errors = &$errors;
	}

}

?>