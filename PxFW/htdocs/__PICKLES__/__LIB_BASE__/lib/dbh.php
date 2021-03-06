<?php

#	Copyright (C)Tomoya Koyanagi.
#	LastUpdate : 2:20 2011/05/29

#--------------------------------------
#	【ファイル内目次】
#	ファイル/ディレクトリ操作関連：allabout_filehandle
#	データベース操作関連：allabout_dbhandle
#	パス処理系メソッド：path_operators
#	その他：allabout_others

#******************************************************************************************************************
#	データベースアクセスオブジェクトクラス
class base_lib_dbh{

	var $conf;
	var $errors;

	var $localconf_auto_transaction_flg = false;
		#	自動トランザクション設定
	var $localconf_auto_commit_flg = false;
		#	自動コミット設定
	var $try2connect_count = 1;
		#	接続に挑戦する回数

	var $con = null;				#	データベースとのコネクション
	var $errorlist = array();		#	エラーリスト
	var $result = null;				#	RDBクエリの実行結果リソース
	var $transaction_flg = false;	#	トランザクションフラグ
	var $file = array();			#	ファイルオープンリソースのリスト

	var $heavy_query_limit = 0.5;	#	Heavy Queryと判断されるまでの時間。

	var $method_eventhdl_connection_error;
	var $method_eventhdl_query_error;
		#	↑コールバックメソッド

	function setup( &$conf , &$errors ){
		$this->conf = &$conf;
		$this->errors = &$errors;

		if( is_float( $this->conf->dbh_heavy_query_limit ) || is_int( $this->conf->dbh_heavy_query_limit ) ){
			#	設定オブジェクトに設定値があれば、そちらを優先。
			$this->heavy_query_limit = $this->conf->dbh_heavy_query_limit;
		}

	}

	#--------------------------------------
	#	すでに確立されたデータベース接続情報を外部から受け入れる
	function setconnection( &$con ){
		if( $this->check_connection() ){
			#	内部の接続が有効であれば、
			#	外部からの接続情報は受け入れない
			return	false;
		}
		$this->con = &$con;
		return	true;
	}

	#******************************************************************************************************************
	#	データベース操作関連
	#	anch: allabout_dbhandle

	#--------------------------------------
	#	データベースコネクションを確立する
	function connect(){
		if( $this->check_connection() ){ return true; }

		if( $this->conf->rdb['type'] == 'MySQL' ){
			#--------------------------------------
			#	【 MySQL 】
			$server = $this->conf->rdb['server'];
			if( strlen( $this->conf->rdb['port'] ) ){
				$server .= ':'.$this->conf->rdb['port'];
			}
			$try_counter = 0;
			while( $res = @mysql_connect( $server , $this->conf->rdb['user'] , $this->conf->rdb['passwd'] , true ) ){
				$try_counter ++;
				if( is_resource( $res ) ){
					break;
				}
				if( $try_counter >= $this->try2connect_count ){
					break;
				}
				sleep(1);
			}
			if( is_resource( $res ) ){
				$this->con = &$res;
				mysql_select_db( $this->conf->rdb['name'] , $this->con );
				if( strlen( $this->conf->rdb['charset'] ) ){
					#	DB文字コードの指定があれば、
					#	SET NAMES を発行。
					@mysql_query( 'SET NAMES '.addslashes( $this->conf->rdb['charset'] ).';' , $this->con );
				}
				return	true;
			}else{
				$this->adderror( 'DB connect was faild. DB Type of ['.$this->conf->rdb['type'].'] Server ['.$this->conf->rdb['server'].']' , null , __FILE__ , __LINE__ );
				$this->eventhdl_connection_error( 'Database Connection Error.' , __FILE__ , __LINE__ );	//	DB接続エラー時のコールバック関数
				return	false;
			}
		}elseif( $this->conf->rdb['type'] == 'PostgreSQL' ){
			#--------------------------------------
			#	【 PostgreSQL 】
			$pg_connect_string = '';
			if( strlen( $this->conf->rdb['server'] ) ){
				$pg_connect_string .= 'host='.$this->conf->rdb['server'].' ';
			}
			if( strlen( $this->conf->rdb['port'] ) ){
				$pg_connect_string .= 'port='.$this->conf->rdb['port'].' ';
			}
			if( strlen( $this->conf->rdb['user'] ) ){
				$pg_connect_string .= 'user='.$this->conf->rdb['user'].' ';
			}
			if( strlen( $this->conf->rdb['passwd'] ) ){
				$pg_connect_string .= 'password='.$this->conf->rdb['passwd'].' ';
			}
			if( strlen( $this->conf->rdb['name'] ) ){
				$pg_connect_string .= 'dbname='.$this->conf->rdb['name'].' ';
			}

			$try_counter = 0;
			while( $res = @pg_connect( $pg_connect_string , true ) ){
				$try_counter ++;
				if( is_resource( $res ) ){
					break;
				}
				if( $try_counter >= $this->try2connect_count ){
					break;
				}
				sleep(1);
			}
			if( is_resource( $res ) ){
				$this->con = &$res;
				return	true;
			}else{
				$this->adderror( 'DB connect was faild. DB Type of ['.$this->conf->rdb['type'].'] Server ['.$this->conf->rdb['server'].']' , null , __FILE__ , __LINE__ );
				$this->eventhdl_connection_error( 'Database Connection Error. ' , __FILE__ , __LINE__ );	//	DB接続エラー時のコールバック関数
				return	false;
			}

		}elseif( $this->conf->rdb['type'] == 'SQLite' ){
			#--------------------------------------
			#	【 SQLite 】
			$res = sqlite_open( $this->conf->rdb['name'] , 0666 , $sqlite_error_msg );
			if( is_resource( $res ) ){
				$this->con = &$res;
				return	true;
			}else{
				$this->adderror( 'DB connect was faild. Because:['.$sqlite_error_msg.']. DB Type of ['.$this->conf->rdb['type'].'] DB ['.$this->conf->rdb['name'].']' , null , __FILE__ , __LINE__ );
				$this->eventhdl_connection_error( 'Database Connection Error. ' , __FILE__ , __LINE__ );	//	DB接続エラー時のコールバック関数
				return	false;
			}

		}elseif( $this->conf->rdb['type'] == 'Oracle' ){
			#--------------------------------------
			#	【 Oracle 】

			$try_counter = 0;
			while( $res = @oci_connect( $this->conf->rdb['user'] , $this->conf->rdb['passwd'] , $this->conf->rdb['name'] , $this->conf->rdb['charset'] , $this->conf->rdb['sessionmode'] ) ){
				$try_counter ++;
				if( is_resource( $res ) ){
					break;
				}
				if( $try_counter >= $this->try2connect_count ){
					break;
				}
				sleep(1);
			}
			if( is_resource( $res ) ){
				$this->con = &$res;
				return	true;
			}else{
				$this->adderror( 'DB connect was faild. DB Type of ['.$this->conf->rdb['type'].'] Server ['.$this->conf->rdb['server'].']' , null , __FILE__ , __LINE__ );
				$this->eventhdl_connection_error( 'Database Connection Error.' , __FILE__ , __LINE__ );	//	DB接続エラー時のコールバック関数
				return	false;
			}
		}
		$this->adderror( 'PicklesFrameworkは、現在 '.$this->conf->rdb['type'].' をサポートしていません。' , 'connect' , __FILE__ , __LINE__ );
		$this->eventhdl_connection_error( 'Database Connection Error. Unknown DB Type ['.$this->conf->rdb['type'].'] ' , __FILE__ , __LINE__ );	//	DB接続エラー時のコールバック関数
		return	false;
	}

	#--------------------------------------
	#	データベースコネクション$conが有効かどうか確認
	function check_connection( $con = null ){
		if( !is_resource( $con ) ){
			$con = &$this->con;
		}
		if( !is_resource( $con ) ){ return false; }

		if( $this->conf->rdb['type'] == 'MySQL' ){
			#--------------------------------------
			#	【 MySQL 】
			if( !@mysql_ping( $con ) ){
				return false;
			}
			return true;

		}elseif( $this->conf->rdb['type'] == 'PostgreSQL' ){
			#--------------------------------------
			#	【 PostgreSQL 】
			if( !@pg_ping( $con ) ){
				return false;
			}
			return true;

		}elseif( $this->conf->rdb['type'] == 'SQLite' ){
			#--------------------------------------
			#	【 SQLite 】
			if( !is_resource( $con ) ){
				return false;
			}
			return true;

		}elseif( $this->conf->rdb['type'] == 'Oracle' ){
			#--------------------------------------
			#	【 Oracle 】
			#	UTODO : Oracle : 未実装です
			return false;

		}
		return true;
	}

	#--------------------------------------
	#	直前のクエリで処理された件数を得る
	function get_affected_rows( $res = null ){
		#--------------------------------------
		#	MySQLとPostgreSQLでは、渡すべきリソースの種類が異なります。
		#	MySQLには接続リソース、
		#	PostgreSQLには、前回のクエリの実行結果リソースを渡します。
		#	
		#	PostgreSQLの場合には、前回のクエリの実行時に記憶した
		#	結果リソース $this->result を、
		#	自動的に適用します。
		#	
		#	このため、基本的に、引数$resは指定しないで使うことを想定しています。
		#	
		#	明示的に$resを指定する場合は、
		#	呼び出し元側でデータベースの種類に応じた判断がされている必要があります。
		#	
		#--------------------------------------

		if( !is_null( $res ) && !is_resource( $res ) ){
			#	何かを渡してるのに、
			#	リソース型じゃなかったらダメ。
			return	false;
		}

		if( $this->conf->rdb['type'] == 'MySQL' ){
			#--------------------------------------
			#	【 MySQL 】
			if( !is_resource( $res ) ){
				#	MySQLは、接続リソースをとる。
				#	ゆえに、直前のクエリの結果しか知れない。
				$res = &$this->con;
			}
			return @mysql_affected_rows( $res );

		}elseif( $this->conf->rdb['type'] == 'PostgreSQL' ){
			#--------------------------------------
			#	【 PostgreSQL 】
			if( !is_resource( $res ) ){
				#	PostgreSQLは、リクエストの結果のリソースをとる。
				$res = &$this->result;
			}
			return @pg_affected_rows( $res );

		}elseif( $this->conf->rdb['type'] == 'SQLite' ){
			#--------------------------------------
			#	【 SQLite 】
			if( !is_resource( $res ) ){
				#	SQLiteは、接続リソースをとる。
				#	ゆえに、直前のクエリの結果しか知れない。
				$res = &$this->con;
			}
			return	@sqlite_changes( $res );

		}elseif( $this->conf->rdb['type'] == 'Oracle' ){
			#--------------------------------------
			#	【 Oracle 】
			#	UTODO : Oracle : 未実装です
			return false;

		}
		return	false;
	}

	#--------------------------------------
	#	トランザクション
	function start_transaction(){
		$this->connect();
		if( !$this->is_transaction() ){
			$this->transaction_flg = true;
			if( $this->conf->rdb['type'] == 'Oracle' ){
				#	【 Oracle 】
				#	Oracleでは、トランザクションのスタートを宣言しません。
				#	以下、PHPマニュアルからの引用。
				#	> トランザクションは、直近のコミット/ロールバック、またはオートコミットがオフになった時点、
				#	> または接続が確立された時点から指定した接続に加えられた全ての変更として定義されます。
				return	true;
			}
			$sql = 'START TRANSACTION;';
			if( $this->conf->rdb['type'] == 'SQLite' ){
				$sql = 'BEGIN TRANSACTION;';
			}
			$result = $this->execute_sendquery( $sql , &$this->con );
			return	$result;
		}
		return	null;
	}
	#	トランザクション：コミット
	function commit(){
		$this->connect();
		if( !$this->is_transaction() ){
			#	トランザクション中じゃなかったらコミットしない。
			#	Pickles Framework 0.3.0
			return	true;
		}
		$this->transaction_flg = false;
		if( $this->conf->rdb['type'] == 'SQLite' ){
			#	SQLiteの処理
			return	$this->execute_sendquery( 'COMMIT TRANSACTION;' , &$this->con );
		}elseif( $this->conf->rdb['type'] == 'Oracle' ){
			#	Oracleの処理
			return	ocicommit( &$this->con );
		}
		return	$this->execute_sendquery( 'COMMIT;' , &$this->con );
	}
	#	トランザクション：ロールバック
	function rollback(){
		$this->connect();
		if( !$this->is_transaction() ){
			#	トランザクション中じゃなかったらロールバックもしない。
			#	Pickles Framework 0.3.0
			return	true;
		}
		$this->transaction_flg = false;
		if( $this->conf->rdb['type'] == 'SQLite' ){
			#	SQLiteの処理
			return	$this->execute_sendquery( 'ROLLBACK TRANSACTION;' , &$this->con );
		}elseif( $this->conf->rdb['type'] == 'Oracle' ){
			#	Oracleの処理
			return	ocirollback( &$this->con );
		}
		return	$this->execute_sendquery( 'ROLLBACK;' , &$this->con );
	}
	#	トランザクション：トランザクション中かどうか返す
	function is_transaction(){
		return	$this->transaction_flg;
	}

	#--------------------------------------
	#	データベースにクエリを送る
	function &sendquery( $querystring ){
		if( !is_string( $querystring ) ){ return false; }
		$this->connect();
		if( $this->localconf_auto_transaction_flg ){
			$this->start_transaction();
		}
		$this->result = &$this->execute_sendquery( $querystring );
		return	$this->result;
	}
	#	実際にクエリを送信するのはここ。
	#	※オブジェクト外から直接呼ばないでください。
	function &execute_sendquery( $querystring ){
		if( $this->conf->system_exec_mode == 'setup' ){
			#	セットアップモードが有効なときは、
			#	クエリを投げない。
			return	false;
		}

		$this->connect();

		list( $microtime , $time ) = explode( ' ' , microtime() ); 
		$start_mtime = ( floatval( $time ) + floatval( $microtime ) );

		if( $this->conf->rdb['type'] == 'MySQL' ){
			#--------------------------------------
			#	【 MySQL 】
			$RTN = @mysql_query( $querystring , &$this->con );	//クエリを投げる。

		}elseif( $this->conf->rdb['type'] == 'PostgreSQL' ){
			#--------------------------------------
			#	【 PostgreSQL 】
			$RTN = @pg_query( &$this->con , $querystring );	//クエリを投げる。

		}elseif( $this->conf->rdb['type'] == 'SQLite' ){
			#--------------------------------------
			#	【 SQLite 】
			$RTN = @sqlite_query( &$this->con , $querystring );	//クエリを投げる。

		}elseif( $this->conf->rdb['type'] == 'Oracle' ){
			#--------------------------------------
			#	【 Oracle 】
			$stm = @ociparse( &$this->con , $querystring );
			if( $stm ){
				$RTN = @ociexecute( $stm );	//クエリを投げる。
			}else{
				$RTN = false;
			}

		}else{
			#	【 想定外のDB 】
			$debug = debug_backtrace();
			$FILE = $debug[1]['file'];
			$LINE = $debug[1]['line'];

			$SQL2ErrorMessage = preg_replace( '/(?:\r\n|\r|\n|\t| )+/i' , ' ' , $querystring );
			$this->adderror( '['.$this->conf->rdb['type'].']は、未対応のデータベースです。 SQL[ '.$SQL2ErrorMessage.' ]' , 'sendQuery' , $FILE , $LINE );
			$this->eventhdl_query_error( 'DB Query Error. ['.$this->conf->rdb['type'].']は、未対応のデータベースです。 SQL[ '.$SQL2ErrorMessage.' ]' , $FILE , $LINE );	//	クエリエラー時のコールバック関数

			return	false;
		}

		list( $microtime , $time ) = explode( ' ' , microtime() ); 
		$end_mtime = ( floatval( $time ) + floatval( $microtime ) );
		$exec_time = $end_mtime - $start_mtime;
		if( $exec_time >= $this->heavy_query_limit ){
			#	1回のクエリに時間がかかっている場合。
			$debug = debug_backtrace();
			$FILE = $debug[1]['file'];
			$LINE = $debug[1]['line'];
			$this->adderror( ''.$this->conf->rdb['type'].' Heavy Query ['.$exec_time.'] sec. on SQL[ '.preg_replace( '/(?:\r\n|\r|\n|\t| )+/i' , ' ' , $querystring ).' ]' , 'sendQuery' , $FILE , $LINE );
		}

		if( $RTN === false ){
			#	クエリに失敗したときのエラー処理
			$debug = debug_backtrace();
			$FILE = $debug[1]['file'];
			$LINE = $debug[1]['line'];

			$SQL2ErrorMessage = preg_replace( '/(?:\r\n|\r|\n|\t| )+/i' , ' ' , $querystring );
			$error_report = $this->get_sql_error();
			$DB_ERRORMSG = $error_report['message'];
			$this->adderror( ''.$this->conf->rdb['type'].' Query Error! ['.$DB_ERRORMSG.'] on SQL[ '.$SQL2ErrorMessage.' ]' , 'sendQuery' , $FILE , $LINE );
			$this->eventhdl_query_error( ''.$this->conf->rdb['type'].' Query Error. ['.$DB_ERRORMSG.'] on SQL[ '.$SQL2ErrorMessage.' ]' , $FILE , $LINE );	//	クエリエラー時のコールバック関数

		}

		return	$RTN;

	}

	#--------------------------------------
	#	SQL文に値をバインドする
	function bind( $sql , $vars = array() ){
		#	数値型の場合 => :N:key
		#	文字列型の場合 => :S:key
		#	そのまま置き換え(テーブル名、フィールド名などの時に使用) => :D:key
		#	のルールで、メタ文字を仕込む。
		#	エラーがある場合は、falseを返す

		if( !is_array( $vars ) ){ return false; }

		$ptn = '/^(.*?)(:(s|S|n|N|d|D):([a-zA-Z0-9_-]+))(.*)$/sm';

		$RTN = '';
		while( strlen( $sql ) ){
			if( !preg_match( $ptn , $sql , $matches ) ){
				$RTN .= $sql;
				break;
			}

			$loopcounter ++;
			$RTN .= $matches[1];
			$value = $vars[$matches[4]];
			if( is_null( $value ) ){
				$RTN .= 'NULL';
			}else{
				switch( strtolower( $matches[3] ) ){
					case 'd':
						$RTN .= $value;
						break;
					case 'n':
						$RTN .= intval( $value );
						break;
					case 's':
						if( $this->conf->rdb['type'] == 'SQLite' ){
							#	Pickles Framework 0.3.6 で追加された処理。
							#	SQLiteの場合は、エスケープにバックスラッシュを使用せず、
							#	シングルクオートを重ねる仕様になっているらしい。
							#	よって、addslashes() では対応できない。
							$sqlite_valMemo = $value;
							$sqlite_valMemo = preg_replace( '/\'/' , '\'\'' , $sqlite_valMemo );
							$RTN .= '\''.$sqlite_valMemo.'\'';
							unset( $sqlite_valMemo );
						}else{
							$RTN .= '\''.addslashes( $value ).'\'';
						}
						break;
					default:
						$RTN .= $vars[$matches[2]];
						break;
				}
			}

			$sql = $matches[5];
			continue;
		}

		return	$RTN;
	}

	#--------------------------------------
	#	クエリの実行結果を得る
	function getval( $res = null ){
		$RTN = array();
		if( !$res ){ $res = &$this->result; }
		if( is_bool( $res ) ){ return $res; }
		if( !is_resource( $res ) ){ return array(); }

		if( $this->conf->rdb['type'] == 'MySQL' ){
			#--------------------------------------
			#	【 MySQL 】
			while( $Line = mysql_fetch_assoc( $res )){ array_push( $RTN , $Line ); }
			return	$RTN;
		}elseif( $this->conf->rdb['type'] == 'PostgreSQL' ){
			#--------------------------------------
			#	【 PostgreSQL 】
			while( $Line = pg_fetch_assoc( $res )){ array_push( $RTN , $Line ); }
			return	$RTN;
		}elseif( $this->conf->rdb['type'] == 'SQLite' ){
			#--------------------------------------
			#	【 SQLite 】
			while( $Line = sqlite_fetch_array( $res , SQLITE_ASSOC )){ array_push( $RTN , $Line ); }
			return	$RTN;
		}elseif( $this->conf->rdb['type'] == 'Oracle' ){
			#--------------------------------------
			#	【 Oracle 】
			while( $Line = oci_fetch_assoc( $res )){ array_push( $RTN , $Line ); }
			return	$RTN;
		}
		$this->adderror( $this->conf->rdb['type'].'は、未対応のデータベースです。' , 'getval' , __FILE__ , __LINE__ );
		return	null;
	}

	#--------------------------------------
	#	クエリの実行結果を1行ずつ得る
	function fetch_assoc( $res = null ){
		$RTN = array();
		if( !$res ){ $res = &$this->result; }
		if( is_bool( $res ) ){ return $res; }
		if( !is_resource( $res ) ){ return array(); }

		if( $this->conf->rdb['type'] == 'MySQL' ){
			#--------------------------------------
			#	【 MySQL 】
			$RTN = mysql_fetch_assoc( $res );
			return	$RTN;
		}elseif( $this->conf->rdb['type'] == 'PostgreSQL' ){
			#--------------------------------------
			#	【 PostgreSQL 】
			$RTN = pg_fetch_assoc( $res );
			return	$RTN;
		}elseif( $this->conf->rdb['type'] == 'SQLite' ){
			#--------------------------------------
			#	【 SQLite 】
			$RTN = sqlite_fetch_array( $res , SQLITE_ASSOC );
			return	$RTN;
		}elseif( $this->conf->rdb['type'] == 'Oracle' ){
			#--------------------------------------
			#	【 Oracle 】
			$RTN = oci_fetch_assoc( $res );
			return	$RTN;
		}
		$this->adderror( $this->conf->rdb['type'].'は、未対応のデータベースです。' , 'fetch_assoc' , __FILE__ , __LINE__ );
		return	null;
	}

	#--------------------------------------
	#	直前のクエリのエラー報告を受ける
	function get_sql_error(){
		if( $this->conf->rdb['type'] == 'MySQL' ){
			#--------------------------------------
			#	【 MySQL 】
			$errornum = mysql_errno( &$this->con );
			$errormsg = mysql_error( &$this->con );
			return	array( 'message'=>$errormsg , 'number'=>$errornum );

		}elseif( $this->conf->rdb['type'] == 'PostgreSQL' ){
			#--------------------------------------
			#	【 PostgreSQL 】
			$errormsg = pg_last_error( &$this->con );
			$result_error = pg_result_error( &$this->result );
			return	array( 'message'=>$errormsg , 'number'=>null , 'result_error'=>$result_error );

		}elseif( $this->conf->rdb['type'] == 'SQLite' ){
			#--------------------------------------
			#	【 SQLite 】
			$error_cd = sqlite_last_error( &$this->con );
			$errormsg = sqlite_error_string( $error_cd );
			return	array( 'message'=>$errormsg , 'number'=>$error_cd );

		}elseif( $this->conf->rdb['type'] == 'Oracle' ){
			#--------------------------------------
			#	【 Oracle 】
			$errorall = ocierror( &$this->con );
			$errorall['message'] = $errorall['message'];
			$errorall['number'] = $errorall['code'];
			return	$errorall;

		}
		$this->adderror( $this->conf->rdb['type'].'は、未対応のデータベースです。' , 'get_sql_error' , __FILE__ , __LINE__ );
		return	array( 'message'=>$this->conf->rdb['type'].'は、未対応のデータベースです。' );
	}

	#--------------------------------------
	#	直前のクエリ(INSERT)で挿入されたレコードのIDを得る
	function get_last_insert_id( $res = null , $seq_table_name = null ){
		#--------------------------------------
		#	$res のリソース型は、データベースによって異なります。
		#	これを判断するのは、呼び出し元の責任となります。
		#	省略した場合は、自動的に選択します。
		#--------------------------------------

		if( $this->conf->rdb['type'] == 'MySQL' ){
			#--------------------------------------
			#	【 MySQL 】
			if( !$res ){ $res = &$this->con; }
			$RTN = mysql_insert_id( $res );
			return	$RTN;

		}elseif( $this->conf->rdb['type'] == 'PostgreSQL' ){
			#--------------------------------------
			#	【 PostgreSQL 】
			if( !strlen( $seq_table_name ) ){ return false; }//PostgreSQLでは必須
			if( !$res ){ $res = &$this->result; }

			$result = @pg_query( &$this->con , 'SELECT CURRVAL(\''.addslashes($seq_table_name).'\') AS seq' );
			$data = @pg_fetch_assoc( $result );
			$RTN = intval( $data['seq'] );
			return	$RTN;

		}elseif( $this->conf->rdb['type'] == 'SQLite' ){
			#--------------------------------------
			#	【 SQLite 】
			if( !$res ){ $res = &$this->con; }
			$RTN = sqlite_last_insert_rowid( $res );
			return	$RTN;

		}elseif( $this->conf->rdb['type'] == 'Oracle' ){
			#--------------------------------------
			#	【 Oracle 】
			#	UTODO : Oracle : 未実装です。

		}
		$this->adderror( $this->conf->rdb['type'].'は、未対応のデータベースです。' , 'get_last_insert_id' , __FILE__ , __LINE__ );
		return	array( 'message'=>$this->conf->rdb['type'].'は、未対応のデータベースです。' );
	}

	#******************************************************************************************************************
	#	その他DB関連

	#--------------------------------------
	#	データベースの文字エンコードタイプを取得
	function get_db_encoding(){
		$this->connect();
		if( $this->conf->rdb['type'] == 'MySQL' ){
			#--------------------------------------
			#	【 MySQL 】
			return	mysql_client_encoding( $this->con );

		}elseif( $this->conf->rdb['type'] == 'PostgreSQL' ){
			#--------------------------------------
			#	【 PostgreSQL 】
			return	pg_client_encoding( $this->con );

		}elseif( $this->conf->rdb['type'] == 'SQLite' ){
			#--------------------------------------
			#	【 SQLite 】
			return	sqlite_libencoding();

		}elseif( $this->conf->rdb['type'] == 'Oracle' ){
			#--------------------------------------
			#	【 Oracle 】
			#	UTODO : Oracle : 未実装です。
			return	false;

		}
		$this->adderror( '未対応のデータベースです。' , 'get_db_encoding' , __FILE__ , __LINE__ );
		return	false;
	}

	#--------------------------------------
	#	エンコーディングをあらわす文字の変換
	function translateencoding_db2php( $db_encoding ){
		$db_encoding = strtolower( $db_encoding );
		switch( $db_encoding ){
			case 'unicode':
			case 'utf8_bin':
			case 'utf8_unicode_ci';
			case 'utf8_general_ci';
				return	'UTF-8';
			case 'euc_jp':
			case 'ujis_bin':
			case 'ujis_japanese_ci':
				return	'EUC-JP';
		}
		return	$db_encoding;
	}
	function translateencoding_php2db( $php_encoding ){
		$php_encoding = strtolower( $php_encoding );
		if( preg_match( '/utf/i' , $php_encoding ) ){
			if( $this->conf->dbh['type'] == 'PostgreSQL' ){
				return	'UNICODE';
			}
			return	'utf8_unicode_ci';
		}elseif( preg_match( '/euc/i' , $php_encoding ) ){
			if( $this->conf->dbh['type'] == 'PostgreSQL' ){
				return	'EUC_JP';
			}
			return	'ujis_japanese_ci';
		}elseif( preg_match( '/sjis|shift_jis/i' , $php_encoding ) ){
			return	'ujis_japanese_ci';
		}
		return	$php_encoding;
	}

	#--------------------------------------
	#	テーブルの一覧を得る
	function get_tablelist( $dbname = null ){
		if( !$dbname ){ $dbname = $this->conf->rdb['name']; }
		$this->connect();

		if( $this->conf->rdb['type'] == 'MySQL' ){
			#--------------------------------------
			#	【 MySQL 】
			$tablelist = $this->getval( mysql_list_tables( $dbname ) );
			if( !is_array( $tablelist ) ){
				$tablelist = array();
			}
			$result = array();
			foreach( $tablelist as $Line ){
				foreach( $Line as $Line2 ){
					array_push( $result , $Line2 );
				}
			}
			return	$result;

		}elseif( $this->conf->rdb['type'] == 'PostgreSQL' ){
			#--------------------------------------
			#	【 PostgreSQL 】
			ob_start();?>
SELECT c.relname as "table", 'table' as  "type", u.usename as "Owner"
FROM pg_class c LEFT JOIN pg_user u ON c.relowner = u.usesysid
   WHERE c.relkind IN ('r') AND c.relname !~ '^pg_'
ORDER BY 1;
<?php
			$sql = @ob_get_clean();
			$res = $this->sendquery( $sql );
			if( !$res ){
				return	false;
			}
			$tablelist = $this->getval( $res );
			if( !is_array( $tablelist ) ){
				$tablelist = array();
			}
			$result = array();
			foreach( $tablelist as $Line ){
				array_push( $result , $Line['table'] );
			}
			return	$result;

		}elseif( $this->conf->rdb['type'] == 'SQLite' ){
			#--------------------------------------
			#	【 SQLite 】
			#	Pickles Framework 0.3.0 実装
			ob_start();?>
SELECT name FROM sqlite_master WHERE type='table' ORDER BY name;
<?php
			$sql = @ob_get_clean();
			$res = $this->sendquery( $sql );
			if( !$res ){
				return	false;
			}
			$tablelist = $this->getval( $res );
			if( !is_array( $tablelist ) ){
				$tablelist = array();
			}
			$result = array();
			foreach( $tablelist as $Line ){
				array_push( $result , $Line['name'] );
			}
			return	$result;

		}elseif( $this->conf->rdb['type'] == 'Oracle' ){
			#--------------------------------------
			#	【 Oracle 】
			#	UTODO : Oracle : 未実装です。
			return	false;

		}
		$this->adderror( '未対応のデータベースです。' , 'get_tablelist' , __FILE__ , __LINE__ );
		return	false;
	}

	#--------------------------------------
	#	テーブルの定義を知る
	function get_table_definition( $tablename ){
		$this->connect();
		if( $this->conf->rdb['type'] == 'MySQL' ){
			#--------------------------------------
			#	【 MySQL 】
			$sql = 'SHOW COLUMNS FROM :D:table_name;';
			$sql = $this->bind( $sql , array( 'table_name'=>$tablename ) );
			$res = &$this->sendquery( $sql );
			$VALUE = $this->getval( &$res );
			if( !is_array( $VALUE ) ){ return false; }
			$RTN = array();
			$i = 0;
			foreach( $VALUE as $Key=>$Line ){
				$i ++;
				$RTN[$Line['Field']] = array();
				foreach( $Line as $Key2=>$Line2 ){
					$RTN[$Line['Field']][strtolower($Key2)] = $Line2;
				}
				$RTN[$Line['Field']]['num'] = $i;
				$RTN[$Line['Field']]['null'] = (bool)$Line['Null'];
				$RTN[$Line['Field']]['not null'] = empty( $Line['Null'] );
			}
			return	$RTN;

		}elseif( $this->conf->rdb['type'] == 'PostgreSQL' ){
			#--------------------------------------
			#	【 PostgreSQL 】
			if( !is_callable( 'pg_meta_data' ) ){ return false; }
			$VALUE = pg_meta_data( $this->con , $tablename );
			if( !is_array( $VALUE ) ){ return false; }
			$RTN = array();
			foreach( $VALUE as $Key=>$Line ){
				$RTN[$Key] = array();
				$RTN[$Key]['field'] = $Key;
				foreach( $Line as $Key2=>$Line2 ){
					$RTN[$Key][strtolower( $Key2 )] = $Line2;
				}
				$RTN[$Key]['null'] = empty( $Line['not null'] );
				$RTN[$Key]['not null'] = (bool)$Line['not null'];
			}
			return	$RTN;

		}elseif( $this->conf->rdb['type'] == 'SQLite' ){
			#--------------------------------------
			#	【 SQLite 】
			#	Pickles Framework 0.3.0 追加
			if( !is_callable( 'sqlite_fetch_column_types' ) ){ return false; }
			$VALUE = sqlite_fetch_column_types( $tablename , $this->con );
			if( !is_array( $VALUE ) ){ return false; }
			$RTN = array();
			foreach( $VALUE as $Key=>$Line ){
				$RTN[$Key] = array();
				$RTN[$Key]['field'] = $Key;
				$RTN[$Key]['type'] = $Line;
			}
			return	$RTN;

		}elseif( $this->conf->rdb['type'] == 'Oracle' ){
			#--------------------------------------
			#	【 Oracle 】
			#	UTODO : Oracle : 未実装です。
			return	false;

		}
		$this->adderror( '未対応のデータベースです。' , 'get_table_definition' , __FILE__ , __LINE__ );
		return	false;
	}


	#******************************************************************************************************************
	#	DB関連データ変換

	#--------------------------------------
	#	date型の値を、time()形式に変換
	function date2int( $time ){
		if( !preg_match( '/^([0-9]+)-([0-9]+)-([0-9]+)(?: (?:[0-9]+):(?:[0-9]+):(?:[0-9]+))?$/' , $time , $res ) ){
			return	false;
		}
		return	mktime( 0 , 0 , 0 , intval($res[2]) , intval($res[3]) , intval($res[1]) );
	}
	#--------------------------------------
	#	datetime型の値を、time()形式に変換
	function datetime2int( $time ){
		#	このメソッドは、PostgreSQLのtimestamp型文字列を吸収します。
		if( !preg_match( '/^([0-9]+)-([0-9]+)-([0-9]+)(?: ([0-9]+):([0-9]+):([0-9]+)(?:\.[0-9]+?)?)?$/' , $time , $res ) ){
			return	false;
		}
		return	mktime( intval($res[4]) , intval($res[5]) , intval($res[6]) , intval($res[2]) , intval($res[3]) , intval($res[1]) );
	}
	#--------------------------------------
	#	time()形式の値を、date型に変換
	function int2date( $time ){
		return	date( 'Y-m-d' , $time );
	}
	#--------------------------------------
	#	time()形式の値を、datetime型に変換
	function int2datetime( $time ){
		return	date( 'Y-m-d H:i:s' , $time );
	}


	#--------------------------------------
	#	DBコネクションに失敗した時に実行されるメソッド
	function eventhdl_connection_error( $errorMessage = null , $FILE = null , $LINE = null ){
		$method = &$this->method_eventhdl_connection_error;
		if( is_array( $method ) ){
			#	配列を受けていたら
			if( is_object( $method[0] ) && is_string( $method[1] ) ){
				#	オブジェクトとメソッドのセット
				return	eval( 'return	$method[0]->'.$method[1].'( $errorMessage , $FILE , $LINE );' );
			}elseif( is_string( $method[0] ) && is_string( $method[1] ) ){
				#	スタティッククラスとメソッドのセット
				return	eval( 'return	'.$method[0].'::'.$method[1].'( $errorMessage , $FILE , $LINE );' );
			}
		}elseif( is_string( $method ) ){
			#	グローバル関数または匿名関数
			return	$method( $errorMessage , $FILE , $LINE );
		}
		return	true;
	}
	function set_eventhdl_connection_error( $method ){
		$this->eventhdl_connection_error = $method;
		return	true;
	}

	#--------------------------------------
	#	SQLエラー時に実行されるメソッド
	function eventhdl_query_error( $errorMessage = null , $FILE = null , $LINE = null ){
		$method = &$this->method_eventhdl_query_error;
		if( is_array( $method ) ){
			#	配列を受けていたら
			if( is_object( $method[0] ) && is_string( $method[1] ) ){
				#	オブジェクトとメソッドのセット
				return	eval( 'return	$method[0]->'.$method[1].'( $errorMessage , $FILE , $LINE );' );
			}elseif( is_string( $method[0] ) && is_string( $method[1] ) ){
				#	スタティッククラスとメソッドのセット
				return	eval( 'return	'.$method[0].'::'.$method[1].'( $errorMessage , $FILE , $LINE );' );
			}
		}elseif( is_string( $method ) ){
			#	グローバル関数または匿名関数
			return	$method( $errorMessage , $FILE , $LINE );
		}
		return	true;
	}
	function set_eventhdl_query_error( $method ){
		$this->method_eventhdl_query_error = $method;
		return	true;
	}

	#--------------------------------------
	#	SQLのLIMIT句を作成する
	function mk_sql_limit( $limit , $offset = 0 ){
		#	PxFW 0.6.5 : 追加
		$sql = '';
		if( $this->conf->rdb['type'] == 'PostgreSQL' ){
			#	【 PostgreSQL 】
			$sql .= ' OFFSET '.intval( $offset ).' LIMIT '.intval( $limit ).' ';
		}else{
			#	【 MySQL/SQLite 】
			$sql .= ' LIMIT '.intval( $offset ).','.intval( $limit ).' ';
		}
		return $sql;
	}

	#--------------------------------------
	#	配列からINSERT文を生成する
	function mksql_insert( $table_name , $insert_values , $column_define = null ){
		#	PxFW 0.6.5 で、mk_sql_insert() に改名されました。
		#	以降は、mk_sql_insert() を使用してください。
		return	$this->mk_sql_insert( $table_name , $insert_values , $column_define );
	}
	function mk_sql_insert( $table_name , $insert_values , $column_define = null ){
		if( !strlen( $table_name ) ){ return false; }
		if( !is_array( $insert_values ) ){ return false; }
		if( !count( $insert_values ) ){ return false; }
		if( !is_array( $column_define ) ){
			#	定義が空だったら、
			#	挿入値の0個目から定義を作成する
			$column_define = $insert_values[0];
			foreach( array_keys( $column_define ) as $key ){
				$column_define[$key] = 'S';
			}
		}
		if( !count( $column_define ) ){ return false; }

		$column_keys = array_keys( $column_define );
		$insert_values_sql = array();
		foreach( $insert_values as $val ){
			$insert_val_sql_MEMO = array();
			foreach( $column_define as $val_key=>$val_type ){
				$MEMO = ':'.$val_type.':value';
				$MEMO = $this->bind( $MEMO , array( 'value'=>$val[$val_key] ) );
				array_push( $insert_val_sql_MEMO , $MEMO );
			}
			array_push( $insert_values_sql , implode( ' , ' , $insert_val_sql_MEMO ) );
		}

		$SQL = '';
		$SQL .= 'INSERT INTO '.$table_name.'( ';
		$SQL .= implode( ' , ' , $column_keys );
		$SQL .= ' ) VALUES ( ';
		$SQL .= implode( ' ),( ' , $insert_values_sql );
		$SQL .= ' );';

		return	$SQL;
	}

	#******************************************************************************************************************
	#	ファイル/ディレクトリ操作関連
	#	anch: allabout_filehandle

	#--------------------------------------
	#	扱うファイルがルートディレクトリ内にあることをチェックする
	#	※プライベートなメソッドです。
	function check_rootdir( $targetpath = null ){
		if( is_null( $targetpath ) ){ return	false; }
		$targetpath_MEMO = $targetpath;

		#--------------------------------------
		#	対象となるパスを上階層へ上っていき、
		#	realpathが存在した時点でbreakする。
		for( $i = 0; $i < 100; $i++ ){
			if( @realpath( $targetpath_MEMO ) ){
				break;
			}
#			$targetpath_MEMO = preg_replace( '/(.*)\\/(?:(?:(?!\\/).)*)$/' , '\\1' , $targetpath_MEMO );
			$targetpath_MEMO = preg_replace( '/(.*)(?:\\\\|\\/).*$/' , '\\1' , $targetpath_MEMO );//Pickles Framework 0.2.2 書き換え
		}
		if( !@realpath( $targetpath_MEMO ) ){
			#	結局realpathが見つからない場合は、失敗。falseを返す。
			return	false;
		}

		$targetpath = @realpath( $targetpath_MEMO );
		unset( $targetpath_MEMO );

		#--------------------------------------
		#	書き込んでいいディレクトリ内か検証
		$rootdir = @realpath( $this->conf->path_root );
		if( !@is_dir( $rootdir ) )	{ return false; }
		if( !preg_match( '/^'.preg_quote( $rootdir , '/' ).'/' , $targetpath ) ){
			return	false;
		}
		return	true;
	}

	#--------------------------------------
	#	書き込み/上書きしてよいアイテムか検証
	function is_writable( $path ){
		if( $this->conf->system_exec_mode == 'setup' ){
			#	セットアップモードが有効なとき
			return	false;
		}

		if( strlen( $this->conf->fs_encoding ) ){
			//PxFW 0.6.4 追加
			$path = @text::convert_encoding( $path , $this->conf->fs_encoding );
		}

		if( !$this->check_rootdir( $path ) ){
			return	false;
		}
		if( is_array( $this->conf->writeprotect ) ){
			foreach( $this->conf->writeprotect as $Line ){
				if( preg_match( '/^'.preg_quote( $Line , '/' ).'/' , $path ) ){
					return	false;
				}
			}
		}
		if( is_array( $this->conf->readprotect ) ){
			foreach( $this->conf->readprotect as $Line ){
				if( preg_match( '/^'.preg_quote( $Line , '/' ).'/' , $path ) ){
					return	false;
				}
			}
		}
		if( @file_exists( $path ) && !@is_writable( $path ) ){
			return	false;
		}
		return	true;
	}

	#--------------------------------------
	#	読み込んでよいアイテムか検証
	function is_readable( $path ){
		if( strlen( $this->conf->fs_encoding ) ){
			//PxFW 0.6.4 追加
			$path = @text::convert_encoding( $path , $this->conf->fs_encoding );
		}
		if( is_array( $this->conf->readprotect ) ){
			foreach( $this->conf->readprotect as $Line ){
				if( preg_match( '/^'.preg_quote( $Line , '/' ).'/' , $path ) ){
					return	false;
				}
			}
		}
		if( !@is_readable( $path ) ){
			return	false;
		}
		return	true;
	}

	#--------------------------------------
	#	ファイルが存在するかどうか調べる
	#	PxFW 0.6.4 追加
	function is_file( $path ){
		if( strlen( $this->conf->fs_encoding ) ){
			$path = @text::convert_encoding( $path , $this->conf->fs_encoding );
		}
		return @is_file( $path );
	}

	#--------------------------------------
	#	フォルダが存在するかどうか調べる
	#	PxFW 0.6.4 追加
	function is_dir( $path ){
		if( strlen( $this->conf->fs_encoding ) ){
			$path = @text::convert_encoding( $path , $this->conf->fs_encoding );
		}
		return @is_dir( $path );
	}

	#--------------------------------------
	#	ファイルまたはフォルダが存在するかどうか調べる
	#	PxFW 0.6.4 追加
	function file_exists( $path ){
		if( strlen( $this->conf->fs_encoding ) ){
			$path = @text::convert_encoding( $path , $this->conf->fs_encoding );
		}
		return @file_exists( $path );
	}

	#--------------------------------------
	#	ディレクトリを作成する
	function mkdir( $dirpath , $perm = null ){
		if( $this->conf->system_exec_mode == 'setup' ){
			#	セットアップモードが有効なとき
			return	false;
		}

		if( strlen( $this->conf->fs_encoding ) ){
			//PxFW 0.6.4 追加
			$dirpath = @text::convert_encoding( $dirpath , $this->conf->fs_encoding );
		}

		if( !$this->check_rootdir( $dirpath ) )	{ return false; }
		if( @is_dir( $dirpath ) ){
			#	既にディレクトリがあったら、作成を試みない。
			$this->chmod( $dirpath , $perm );
			return	true;
		}
		$result = @mkdir( $dirpath );
		$this->chmod( $dirpath , $perm );
		clearstatcache();//Pickles Framework 0.2.2 追記
		return	$result;
	}
	#--------------------------------------
	#	ディレクトリを作成する(上層ディレクトリも全て作成)
	function mkdirall( $dirpath , $perm = null ){
		if( $this->conf->system_exec_mode == 'setup' ){
			#	セットアップモードが有効なとき
			return	false;
		}

		if( strlen( $this->conf->fs_encoding ) ){
			//PxFW 0.6.4 追加
			$dirpath = @text::convert_encoding( $dirpath , $this->conf->fs_encoding );
		}

		if( !$this->check_rootdir( $dirpath ) ){ return false; }
		if( @is_dir( $dirpath ) ){ return true; }
		$patharray = explode( '/' , $this->get_realpath( $dirpath ) );
		$targetpath = '';
		foreach( $patharray as $Line ){
			if( !strlen( $Line ) || $Line == '.' || $Line == '..' ){ continue; }
			$targetpath = $targetpath.'/'.$Line;
			if( !@is_dir( $targetpath ) ){
				//PxFW 0.6.4 追加
				$targetpath = @text::convert_encoding( $targetpath , mb_internal_encoding() , $this->conf->fs_encoding );
				$this->mkdir( $targetpath , $perm );
			}
		}
		return	true;
	}

	#--------------------------------------
	#	ファイルを保存する
	function savefile( $filepath , $CONTENT , $perm = null ){
		#	このメソッドは、$filepathにデータを保存します。
		#	もともと保存されていた内容は破棄され、新しいデータで上書きします。
		#	もとのデータを保持したまま追記したい場合は、
		#	savefile_push()メソッドを使用してください。
		#
		#	ただし、fopenしたリソースは、1回の処理の間保持されるので、
		#	1回の処理で同じファイルに対して2回以上コールされた場合は、
		#	追記される点に注意してください。
		#	1回の処理の間に何度も上書きする必要がある場合は、
		#	明示的に$dbh->fclose($filepath);をコールし、
		#	一旦ファイルを閉じてください。

		$filepath = $this->get_realpath($filepath);

		if( strlen( $this->conf->fs_encoding ) ){
			//PxFW 0.6.4 追加
			$filepath = @text::convert_encoding( $filepath , $this->conf->fs_encoding );
		}

		if( $this->conf->system_exec_mode == 'setup' ){
			#	セットアップモードが有効なとき
			return	false;
		}

		if( !$this->is_writable( $filepath ) )	{ return false; }
		if( @is_dir( $filepath ) ){ return false; }
		if( @is_file( $filepath ) && !@is_writable( $filepath ) ){ return false; }
		if( !is_array( $this->file[$filepath] ) ){
			$this->fopen( $filepath , 'w' );
		}elseif( $this->file[$filepath]['mode'] != 'w' ){
			$this->fclose( $filepath );
			$this->fopen( $filepath , 'w' );
		}

		if( !strlen( $CONTENT ) ){
			#	空白のファイルで上書きしたい場合
			if( @is_file( $filepath ) ){
				@unlink( $filepath );
			}
			@touch( $filepath );
			$this->chmod( $filepath , $perm );
			clearstatcache();
			return	@is_file( $filepath );
		}

		$res = &$this->file[$filepath]['res'];
		if( !is_resource( $res ) ){ return	false; }
		fwrite( $res , $CONTENT );
		$this->chmod( $filepath , $perm );
		clearstatcache();
		return	@is_file( $filepath );
	}

	#--------------------------------------
	#	ファイルの末尾に文字列を追加保存する
	function savefile_push( $filepath , $CONTENT , $perm = null ){
		if( $this->conf->system_exec_mode == 'setup' ){
			#	セットアップモードが有効なとき
			return	false;
		}

		if( strlen( $this->conf->fs_encoding ) ){
			//PxFW 0.6.4 追加
			$filepath = @text::convert_encoding( $filepath , $this->conf->fs_encoding );
		}

		if( !$this->is_writable( $filepath ) )	{ return false; }

		$this->chmod( $filepath , $perm );
		if( !@error_log( $CONTENT , 3 , $filepath ) ){
			return	false;
		}
		return	@is_file( $filepath );
	}

	#--------------------------------------
	#	ファイルを上書き保存して閉じる
	function file_overwrite( $filepath , $CONTENT , $perm = null ){
		#	Pickles Framework 0.3.2 追加 0:53 2008/05/17
		if( $this->is_file_open( $filepath ) ){
			#	既に開いているファイルだったら、一旦閉じる。
			$this->fclose( $filepath );
		}

		if( strlen( $this->conf->fs_encoding ) ){
			//PxFW 0.6.4 追加
			$filepath = @text::convert_encoding( $filepath , $this->conf->fs_encoding );
		}

		#	ファイルを上書き保存
		$result = $this->savefile( $filepath , $CONTENT , $perm );

		#	ファイルを閉じる
		$this->fclose( $filepath );
		return	$result;
	}

	#--------------------------------------
	#	ファイルの中身を1行ずつ配列にいれて返す
	function read_file( $path ){
		#	このメソッドは古いです。
		#	(互換性のために残してあります)
		#	file_get_lines() を正とします。
		return	$this->file_get_lines( $path );
	}
	function file_get_lines( $path ){

		if( strlen( $this->conf->fs_encoding ) ){
			//PxFW 0.6.4 追加
			$path = @text::convert_encoding( $path , $this->conf->fs_encoding );
		}

		if( @is_file( $path ) ){
			if( !$this->is_readable( $path ) ){ return false; }
			return	@file( $path );
		}elseif( preg_match( '/^(?:http:\/\/|https:\/\/)/' , $path ) ){
			#	対象がウェブコンテンツの場合、
			#	それを取得しようと試みます。
			#	しかし、この使用方法は推奨されません。
			#	対象が、とてもサイズの大きなファイルだったとしても、
			#	このメソッドはそれを検証しません。
			#	また、そのように巨大なファイルの場合でも、
			#	ディスクではなく、メモリにロードします。
			#	( 2007/01/05 TomK )
			if( !ini_get( 'allow_url_fopen' ) ){
				#	PHP設定値 allow_url_fopen が無効な場合は、
				#	file() によるウェブアクセスができないため。
				$this->errors->error_log( 'php.ini value "allow_url_fopen" is FALSE. So, disable to get Web contents ['.$path.'] on $dbh->file_get_lines();' );
				return	false;
			}
			return	@file( $path );
		}
		return	false;
	}

	#--------------------------------------
	#	ファイルの中身を文字列型にして返す
	function read_file_as_str( $path ){
		#	このメソッドは古いです。
		#	(互換性のために残してあります)
		#	file_get_contents() を正とします。
		return	$this->file_get_contents( $path );
	}
	function file_get_contents( $path ){

		if( strlen( $this->conf->fs_encoding ) ){
			//PxFW 0.6.4 追加
			$path = @text::convert_encoding( $path , $this->conf->fs_encoding );
		}

		if( @is_file( $path ) ){
			if( !$this->is_readable( $path ) ){ return false; }
			return	file_get_contents( $path );
		}elseif( preg_match( '/^(?:http:\/\/|https:\/\/)/' , $path ) ){
			#	対象がウェブコンテンツの場合、それを取得しようと試みます。
			#	ただし、ウェブコンテンツをこのメソッドからダウンロードする場合は、
			#	注意が必要です。
			#	対象が、とてもサイズの大きなファイルだったとしても、
			#	このメソッドはそれを検証しません。
			#	また、そのように巨大なファイルの場合でも、
			#	ディスクではなく、メモリに直接ロードします。
			return	$this->gethttpcontent( $path );
		}
		return	false;
	}

	#--------------------------------------
	#	HTTP通信により、コンテンツを取得する
	function gethttpcontent( $url , $saveTo = null ){
		#	対象が、とてもサイズの大きなファイルだったとしても、
		#	このメソッドはそれを検証しないことに注意してください。
		#	また、そのように巨大なファイルの場合でも、
		#	ディスクではなく、メモリに直接ロードします。

		if( $this->conf->system_exec_mode == 'setup' ){
			#	セットアップモードが有効なとき
			return	false;
		}

		if( !ini_get('allow_url_fopen') ){
			#	PHP設定値 allow_url_fopen が無効な場合は、
			#	file() によるウェブアクセスができないため、エラーを記録。
			$this->errors->error_log( 'php.ini value "allow_url_fopen" is FALSE. So, disable to get Web contents ['.$path.'] on $dbh->file_get_contents();' );
			return	false;
		}
		if( preg_match( '/^(?:http:\/\/|https:\/\/)/' , $url ) ){
			if( !is_null( $saveTo ) ){
				#	取得したウェブコンテンツを
				#	ディスクに保存する場合

				if( @is_file( $saveTo ) && !@is_writable( $saveTo ) ){
					#	保存先ファイルが既に存在するのに、書き込めなかったらfalse;
					return	false;

				}elseif( !@is_file( $saveTo ) && @is_dir( dirname( $saveTo ) ) && !@is_writable( dirname( $saveTo ) ) ){
					#	保存先ファイルが存在しなくて、
					#	親ディレクトリがあるのに、書き込めなかったらfalse;
					return	false;

				}

				if( !@is_dir( dirname( $saveTo ) ) ){
					#	親ディレクトリがなかったら、作ってみる。
					if( !$this->mkdirall( dirname( $saveTo ) ) ){	//Pickles Framework 0.1.1 で修正。
						#	失敗したらfalse;
						return	false;
					}
				}

				#	重たいファイルを考慮して、
				#	1行ずつディスクに保存していく。
				$res = $this->fopen( $url , 'r' , false );
				while( $LINE = @fgets( $res ) ){
					if( !strlen( $LINE ) ){ break; }
					$this->savefile( $saveTo , $LINE );
				}
				$this->fclose( $url );
				return	true;
			}

			#	取得したウェブコンテンツのバイナリを
			#	メモリにロードする場合。
			return	file_get_contents( $url );
		}
		return	false;
	}

	#--------------------------------------
	#	ファイルの更新日時を比較する
	function is_newer_a_than_b( $path_a , $path_b ){
		return	$this->comp_filemtime( $path_a , $path_b );
	}
	function comp_filemtime( $path_a , $path_b ){
		#	$path_a の方が新しかった場合にtrue
		#	$path_b の方が新しかった場合にfalse
		#	同時だった場合にnullを返す。

		if( strlen( $this->conf->fs_encoding ) ){
			//PxFW 0.6.4 追加
			$path_a = @text::convert_encoding( $path_a , $this->conf->fs_encoding );
			$path_b = @text::convert_encoding( $path_b , $this->conf->fs_encoding );
		}

		$mtime_a = filemtime( $path_a );
		$mtime_b = filemtime( $path_b );
		if( $mtime_a > $mtime_b ){
			return	true;
		}elseif( $mtime_a < $mtime_b ){
			return	false;
		}
		return	null;
	}

	#--------------------------------------
	#	ファイル名/ディレクトリ名を変更する
	function rename( $original , $newname ){
		if( $this->conf->system_exec_mode == 'setup' ){
			#	セットアップモードが有効なとき
			return	false;
		}

		if( strlen( $this->conf->fs_encoding ) ){
			//PxFW 0.6.4 追加
			$original = @text::convert_encoding( $original , $this->conf->fs_encoding );
			$newname = @text::convert_encoding( $newname , $this->conf->fs_encoding );
		}

		if( !@file_exists( $original ) ){ return	false; }
		if( !$this->is_writable( $original ) ){ return	false; }
		return	@rename( $original , $newname );
	}
	#--------------------------------------
	#	ファイル名/ディレクトリ名の変更を完全に実行する
	function rename_complete( $original , $newname ){
		if( $this->conf->system_exec_mode == 'setup' ){
			#	セットアップモードが有効なとき
			return	false;
		}

		if( strlen( $this->conf->fs_encoding ) ){
			//PxFW 0.6.4 追加
			$original = @text::convert_encoding( $original , $this->conf->fs_encoding );
			$newname = @text::convert_encoding( $newname , $this->conf->fs_encoding );
		}

		if( !@file_exists( $original ) ){ return	false; }
		if( !$this->is_writable( $original ) ){ return	false; }
		$dirname = dirname( $newname );
		if( !@is_dir( $dirname ) ){
			if( !$this->mkdirall( $dirname ) ){
				return	false;
			}
		}
		return	@rename( $original , $newname );
	}

	#--------------------------------------
	#	ルート相対パスを得る
	#	※このメソッドは、realpath()と違い、
	#	　存在しないアイテムもフルパスに変換します。
	#	　ただし、ルート直下のディレクトリまでは一致している必要があり、
	#	　そうでない場合は、falseを返します。
	function get_realpath( $path , $itemname = null ){
		$itemname = preg_replace( '/^\/'.'*'.'/' , '/' , $itemname );//先頭のスラッシュを1つにする。
		if( $itemname == '/' ){ $itemname = ''; }//スラッシュだけが残ったら、ゼロバイトの文字にする。
		if( text::realpath( $path ) == '/' ){
			return	false;
		}

		if( strlen( $this->conf->fs_encoding ) ){
			//PxFW 0.6.4 追加
			$path = @text::convert_encoding( $path , $this->conf->fs_encoding );
		}

		if( @file_exists( $path ) ){
			return	text::realpath( $path ).$itemname;
		}

		if( basename( $path ) == '.' ){
			#	カレントディレクトリを含むパスへの対応
			return	$this->get_realpath( dirname( $path ) , $itemname );
		}
		if( basename( $path ) == '..' ){
			$count = 0;
			while( basename( $path ) == '..' && strlen( dirname( $path ) ) && dirname( $path ) != '/' ){
				$count ++;
				$path = dirname( $path );
			}
			for( $i = 0; $i < $count; $i++ ){
				$path = dirname( $path );
			}
			#	ペアレントディレクトリを含むパスへの対応
			return	$this->get_realpath( $path , $itemname );
		}
		return	$this->get_realpath( dirname( $path ) , basename( $path ).$itemname );
	}

	#--------------------------------------
	#	パス情報を得る
	function pathinfo( $path ){
		if( strlen( $this->conf->fs_encoding ) ){
			//PxFW 0.6.4 追加
			$path = @text::convert_encoding( $path , $this->conf->fs_encoding );
		}
		$pathinfo = pathinfo( $path );
		$pathinfo['filename'] = $this->get_filename( $path );
		return	$pathinfo;
	}
	#--------------------------------------
	#	パス情報から、ファイル名を取得する
	function get_basename( $path ){
		return	pathinfo( $path , PATHINFO_BASENAME );
	}
	#--------------------------------------
	#	パス情報から、拡張子を除いたファイル名を取得する
	function get_filename( $path ){
		$pathinfo = pathinfo( $path );
		$RTN = preg_replace( '/\.'.preg_quote( $pathinfo['extension'] , '/' ).'$/' , '' , $pathinfo['basename'] );
		return	$RTN;
	}
	#--------------------------------------
	#	ファイル名を含むパス情報から、ファイルが格納されているディレクトリ名を取得する
	function get_dirpath( $path ){
		return	pathinfo( $path , PATHINFO_DIRNAME );
	}
	#--------------------------------------
	#	パス情報から、拡張子を取得する
	function get_extension( $path ){
		return	pathinfo( $path , PATHINFO_EXTENSION );
	}


	#--------------------------------------
	#	CSVファイルを読み込む
	function read_csv( $path , $size = 10000 , $delimiter = ',' , $enclosure = '"' , $encoding = 'SJIS-win' , $option = array() ){
		#	$encoding は、保存されているCSVファイルの文字エンコードです。
		#	省略時は Shift_JIS から、内部エンコーディングに変換します。

		if( strlen( $this->conf->fs_encoding ) ){
			//PxFW 0.6.4 追加
			$path = @text::convert_encoding( $path , $this->conf->fs_encoding );
		}

		$path = text::realpath( $path );
		if( !@is_file( $path ) ){
			#	ファイルがなければfalseを返す
			return	false;
		}

		if( !strlen( $delimiter ) )		{ $delimiter = ','; }
		if( !strlen( $enclosure ) )		{ $enclosure = '"'; }
		if( !strlen( $size ) )			{ $size = 10000; }
		if( !strlen( $encoding ) )		{ $encoding = 'SJIS-win'; }

		$RTN = array();
		if( !$this->fopen($path,'r') ){ return false; }
		$filelink = &$this->get_file_resource($path);
		if( !is_resource( $filelink ) || !is_null( $this->file[$path]['contents'] ) ){
			return $this->file[$path]['contents'];
		}
		while( $SMMEMO = fgetcsv( $filelink , intval( $size ) , $delimiter , $enclosure ) ){
			$SMMEMO = text::convert_encoding( $SMMEMO , mb_internal_encoding() , $encoding.',UTF-8,SJIS-win,eucJP-win,SJIS,EUC-JP' );
			array_push( $RTN , $SMMEMO );
		}
		$this->fclose($path);
		return	$RTN;
	}

	#--------------------------------------
	#	UTF-8のCSVファイルを読み込む
	function read_csv_utf8( $path , $size = 10000 , $delimiter = ',' , $enclosure = '"' , $option = array() ){
		#	Pickles Framework 0.3.6 追加
		#	読み込み時にUTF-8の解釈が優先される。
		return	$this->read_csv( $path , $size , $delimiter , $enclosure , 'UTF-8' , $option );
	}

	#--------------------------------------
	#	配列をCSV形式に変換する
	function mk_csv( $array , $encoding = 'SJIS-win' ){
		#	$encoding は、出力されるCSV形式の文字エンコードを指定します。
		#	省略時は Shift_JIS に変換して返します。
		if( !is_array( $array ) ){ $array = array(); }

		if( !strlen( $encoding ) ){ $encoding = 'SJIS-win'; }
		$RTN = '';
		foreach( $array as $Line ){
			if( is_null( $Line ) ){ continue; }
			if( !is_array( $Line ) ){ $Line = array(); }
			foreach( $Line as $cell ){
				$cell = mb_convert_encoding( $cell , $encoding , mb_internal_encoding().',UTF-8,SJIS-win,eucJP-win,SJIS,EUC-JP' );
				if( preg_match( '/"/' , $cell ) ){
					$cell = preg_replace( '/"/' , '""' , $cell);
				}
				if( strlen( $cell ) ){
					$cell = '"'.$cell.'"';
				}
				$RTN .= $cell.',';
			}
			$RTN = preg_replace( '/,$/' , '' , $RTN );
			$RTN .= "\n";
		}
		return	$RTN;
	}
	#--------------------------------------
	#	配列をUTF8-エンコードのCSV形式に変換する
	#	Pickles Framework 0.5.3 追加
	function mk_csv_utf8( $array ){
		return	$this->mk_csv( $array , 'UTF-8' );
	}

	#--------------------------------------
	#	iniファイルを読み込んで、連想にして返す
	#	Pickles Framework 0.1.9 追加 (0:12 2007/09/23)
	function read_ini( $path_ini_file , $option = array() ){
		#	PHPの関数 parse_ini_file() と似てますが、まったくの独自仕様です。
		#	【返る配列】
		#		$RTN = array(
		#			'common'=>array( <===最初のセクションが宣言される前にセットされた値
		#				$key=>$value ,
		#				$key=>$value ,
		#			) ,
		#			'section'=>array( <===セクション別の値がセットされる配列
		#				$section_name1=>array(
		#					$key=>$value ,
		#					$key=>$value ,
		#				) ,
		#				$section_name2=>array(
		#					{以下同様...}
		#				) ,
		#			) ,
		#		);
		#	【仕様・制約】
		#	・キーや値、セクション名などに、改行を含むことはできません。
		#	・エスケープ処理は、一切含まれません。たいへん素直に文字列を取り出します。
		#	・空白文字で始まる(または終わる)キー、値、セクション名は設定できません。(trim()により丸められます)
		#	・非空白行にコメントは書けません。

		if( strlen( $this->conf->fs_encoding ) ){
			//PxFW 0.6.4 追加
			$path_ini_file = @text::convert_encoding( $path_ini_file , $this->conf->fs_encoding );
		}

		if( !$this->is_readable( $path_ini_file ) ){
			return	false;
		}
		$ini_lines = $this->file_get_lines( $path_ini_file );
		if( !is_array( $ini_lines ) ){
			return	false;
		}

		#	コメント行設定
		$comment_marker = ';';
		if( strlen( $option['comment_marker'] ) ){
			$comment_marker = $option['comment_marker'];
		}

		$RTN = array( 'common'=>array() , 'section'=>array() );
			#	戻り値の箱を宣言

		$current_section = '';//解析中の現在のセクション名
		foreach( $ini_lines as $Line ){
			if( strlen( $option['charset'] ) ){
				#	iniファイルの文字コードが指定されている($option['charset'])場合、
				#	文字コードを内部エンコードに変換する。
				$Line = text::convert_encoding( $Line , mb_internal_encoding() , $option['charset'] );
			}

			$Line = trim( $Line );//左右両方の余白をトリミング

			if( preg_match( '/^'.preg_quote($comment_marker,'/').'/' , $Line ) ){
				#	コメント行は無視
				continue;
			}
			if( !strlen( $Line ) ){
				#	空白行は無視
				continue;
			}

			if( preg_match( '/^\[(.*)\]$/' , $Line , $result ) ){
				#	セクション宣言行
				$current_section = $result[1];
				$RTN['section'][$current_section] = array();
				continue;
			}

			if( preg_match( '/^(.*?)=(.*)$/' , $Line , $result ) ){
				#	データ行
				if( strlen( $current_section ) ){
					$RTN['section'][$current_section][trim($result[1])] = trim($result[2]);
				}else{
					$RTN['common'][trim($result[1])] = trim($result[2]);
				}
				continue;
			}

		}
		return	$RTN;
	}

	#--------------------------------------
	#	連想配列からiniファイルを作成して保存する
	#	Pickles Framework 0.2.5 追加 (5:28 2008/02/05)
	function save_ini( $path_ini_file , $ini_data , $option = array() ){
		#	$dbh->read_ini() が返す形式と同じ連想配列を得て、
		#	INI形式のファイルを保存する。

		if( strlen( $this->conf->fs_encoding ) ){
			//PxFW 0.6.4 追加
			$path_ini_file = @text::convert_encoding( $path_ini_file , $this->conf->fs_encoding );
		}

		#	コメント行設定
		$comment_marker = ';';
		if( strlen( $option['comment_marker'] ) ){
			$comment_marker = $option['comment_marker'];
		}

		$charset = mb_internal_encoding();
		if( strlen( $option['charset'] ) ){
			$charset = $option['charset'];
		}

		$FIN = '';
		if( strlen( $option['title'] ) ){
			$FIN .= $comment_marker.'	[[ '.text::convert_encoding( $option['title'] ).' ]]'."\n";
		}
		$FIN .= $comment_marker.'	Pickles Framework INI'."\n";
		$FIN .= $comment_marker.'	charset: '.text::convert_encoding( strtoupper( $charset ) ).''."\n";
		$FIN .= "\n";
		if( is_array( $ini_data['common'] ) ){
			foreach( $ini_data['common'] as $key=>$val ){
				$key = preg_replace( '/\r\n|\r|\n|=/' , ' ' , $key );
				$val = preg_replace( '/\r\n|\r|\n/' , ' ' , $val );
					//改行は含められない
				$FIN .= text::convert_encoding( $key , $charset ).'='.text::convert_encoding( $val , $charset )."\n";
			}
		}
		$FIN .= "\n";
		if( is_array( $ini_data['section'] ) ){
			foreach( $ini_data['section'] as $section_name=>$keyval ){
				$section_name = preg_replace( '/\r\n|\r|\n/' , ' ' , $section_name );
					//改行は含められない
				$FIN .= '['.text::convert_encoding( $section_name , $charset ).']'."\n";
				foreach( $keyval as $key=>$val ){
					$key = preg_replace( '/\r\n|\r|\n|=/' , ' ' , $key );
					$val = preg_replace( '/\r\n|\r|\n/' , ' ' , $val );
						//改行は含められない
					$FIN .= text::convert_encoding( $key , $charset ).'='.text::convert_encoding( $val , $charset )."\n";
				}
				$FIN .= "\n";
			}
		}

		#	作成したINIファイルをファイルに保存する。
		if( !$this->file_overwrite( $path_ini_file , $FIN ) ){
			return	false;
		}
		return	true;
	}

	#--------------------------------------
	#	ファイルを複製する
	function copy( $from , $to , $perm = null ){
		if( $this->conf->system_exec_mode == 'setup' ){
			#	セットアップモードが有効なとき
			return	false;
		}

		if( strlen( $this->conf->fs_encoding ) ){
			//PxFW 0.6.4 追加
			$from = @text::convert_encoding( $from , $this->conf->fs_encoding );
			$to   = @text::convert_encoding( $to   , $this->conf->fs_encoding );
		}

		if( !@is_file( $from ) ){
			return false;	//	Pickles Framework 0.3.5 追加
		}
		if( !$this->is_readable( $from ) ){
			return false;	//	Pickles Framework 0.3.5 追加
		}

		if( !$this->check_rootdir( $to ) ){
			return false;
		}
		if( @is_file( $to ) ){
			//	PxFW 0.6.5 : まったく同じファイルだった場合は、複製しないでtrueを返すようにした。
			if( md5_file( $from ) == md5_file( $to ) && filesize( $from ) == filesize( $to ) ){
				return true;
			}
		}
		if( !@copy( $from , $to ) ){
			return false;	//	Pickles Framework 0.3.5 追加
		}
		$this->chmod( $to , $perm );
		return true;
	}
	#--------------------------------------
	#	ディレクトリを複製する(下層ディレクトリも全てコピー)
	function copyall( $from , $to , $perm = null ){
		if( $this->conf->system_exec_mode == 'setup' ){
			#	セットアップモードが有効なとき
			return	false;
		}

		if( strlen( $this->conf->fs_encoding ) ){
			//PxFW 0.6.4 追加
			$from = @text::convert_encoding( $from , $this->conf->fs_encoding );
			$to   = @text::convert_encoding( $to   , $this->conf->fs_encoding );
		}

		if( !$this->check_rootdir( $to ) )	{ return false; }

		$result = true;

		if( @is_file( $from ) ){
			if( $this->mkdirall( dirname( $to ) ) ){
				if( !$this->copy( $from , $to , $perm ) ){
					$result = false;
				}
			}else{
				$result = false;
			}
		}elseif( @is_dir( $from ) ){
			if( !@is_dir( $to ) ){
				if( !$this->mkdirall( $to ) ){
					$result = false;
				}
			}
			$itemlist = $this->getfilelist( $from );
			foreach( $itemlist as $Line ){
				if( $Line == '.' || $Line == '..' ){ continue; }
				if( @is_dir( $from.'/'.$Line ) ){
					if( @is_file( $to.'/'.$Line ) ){
						continue;
					}elseif( !@is_dir( $to.'/'.$Line ) ){
						if( !$this->mkdirall( $to.'/'.$Line ) ){
							$result = false;
						}
					}
					if( !$this->copyall( $from.'/'.$Line , $to.'/'.$Line , $perm ) ){
						$result = false;
					}
					continue;
				}elseif( @is_file( $from.'/'.$Line ) ){
					if( !$this->copyall( $from.'/'.$Line , $to.'/'.$Line , $perm ) ){
						$result = false;
					}
					continue;
				}
			}
		}

		return	$result;
	}

	#--------------------------------------
	#	ファイルを開き、ファイルリソースをセット
	function &fopen( $filepath , $mode = 'r' , $flock = true ){
		$filepath_fsenc = $filepath;
		if( strlen( $this->conf->fs_encoding ) ){
			//PxFW 0.6.4 追加
			$filepath_fsenc = @text::convert_encoding( $filepath_fsenc , $this->conf->fs_encoding );
		}

		$filepath = $this->get_realpath( $filepath );

		#	すでに開かれていたら
		if( is_resource( $this->file[$filepath]['res'] ) ){
			if( $this->file[$filepath]['mode'] != $mode ){
				#	$modeが前回のアクセスと違っていたら、
				#	前回の接続を一旦closeして、開きなおす。
				$this->fclose( $filepath );
			}else{
				#	前回と$modeが一緒であれば、既に開いているので、
				#	ここで終了。
				return	true;
			}
		}

		#	対象がディレクトリだったら開けません。
		if( @is_dir( $filepath_fsenc ) ){
			return	false;
		}

		#	ファイルが存在するかどうか確認
		if( @is_file( $filepath_fsenc ) ){
			$filepath = text::realpath( $filepath );
			#	【対象のパーミッションをチェック】
			#	Pickles Framework 0.3.5 までの各バージョンには、
			#	$mode に関わらず 書き込み権限がないと false を返す不具合がありました。
			#	この問題は Pickles Framework 0.3.6 で解消されています。
			switch( strtolower($mode) ){
				case 'r':
					if( !$this->is_readable( $filepath ) ){ return false; }
					break;
				case 'w':
				case 'a':
				case 'x':
					if( !$this->is_writable( $filepath ) ){ return false; }
					break;
				case 'r+':
				case 'w+':
				case 'a+':
				case 'x+':
					if( !$this->is_readable( $filepath ) ){ return false; }
					if( !$this->is_writable( $filepath ) ){ return false; }
					break;
			}
		}


		if( is_array( $this->file[$filepath] ) ){ $this->fclose( $filepath ); }

		for( $i = 0; $i < 5; $i++ ){
			$res = @fopen( $filepath_fsenc , $mode );
			if( $res ){ break; }		#	openに成功したらループを抜ける
			sleep(1);
		}
		if( !is_resource( $res ) ){ return false; }	#	5回挑戦して読み込みが成功しなかった場合、falseを返す
		if( $flock ){ flock( $res , LOCK_EX ); }
		if( @is_file( $filepath_fsenc ) ){
			$filepath = text::realpath( $filepath );
		}
		$this->file[$filepath]['filepath'] = $filepath;
		$this->file[$filepath]['res'] = &$res;
		$this->file[$filepath]['mode'] = $mode;
		$this->file[$filepath]['flock'] = $flock;
		return	$res;
	}

	#--------------------------------------
	#	ファイルのリソースを取得する。
	function &get_file_resource( $filepath ){
		$filepath = $this->get_realpath($filepath);
		return	$this->file[$filepath]['res'];
	}

	#--------------------------------------
	#	パーミッションを変更する
	function chmod( $filepath , $perm = null ){
		if( $this->conf->system_exec_mode == 'setup' ){
			#	セットアップモードが有効なとき
			return	false;
		}

		if( strlen( $this->conf->fs_encoding ) ){
			//PxFW 0.6.4 追加
			$filepath = @text::convert_encoding( $filepath , $this->conf->fs_encoding );
		}

		if( is_null( $perm ) ){
			if( @is_dir( $filepath ) ){
				$perm = $this->conf->dbh_dir_default_permission;
			}else{
				$perm = $this->conf->dbh_file_default_permission;
			}
		}
		if( is_null( $perm ) ){
			$perm = 0775;	//	コンフィグに設定モレがあった場合
		}
		return	@chmod( $filepath , $perm );
	}

	#---------------------------------------------------------------------------
	#	パーミッション情報を調べ、3桁の数字で返す。
	function getperminfo( $path ){
		if( strlen( $this->conf->fs_encoding ) ){
			//PxFW 0.6.4 追加
			$path = @text::convert_encoding( $path , $this->conf->fs_encoding );
		}
		$path = @realpath( $path );
		if( !@file_exists( $path ) ){ return false; }
		$perm = rtrim( sprintf( "%o\n" , fileperms( $path ) ) );
		$start = strlen( $perm ) - 3;
		return	substr( $perm , $start , 3 );
	}


	#---------------------------------------------------------------------------
	#	ディレクトリにあるファイル名のリストを配列で返す。
	function getfilelist($path){
		if( strlen( $this->conf->fs_encoding ) ){
			//PxFW 0.6.4 追加
			$path = @text::convert_encoding( $path , $this->conf->fs_encoding );
		}
		$path = @realpath($path);
		if( $path === false ){ return false; }
		if( !@file_exists( $path ) ){ return false; }
		if( !@is_dir( $path ) ){ return false; }

		$RTN = array();
		$dr = @opendir($path);
		while( ( $ent = readdir( $dr ) ) !== false ){
			#	CurrentDirとParentDirは含めない
			if( $ent == '.' || $ent == '..' ){ continue; }
			array_push( $RTN , $ent );
		}
		closedir($dr);
		if( strlen( $this->conf->fs_encoding ) ){
			//PxFW 0.6.4 追加
			$RTN = @text::convert_encoding( $RTN , mb_internal_encoding() );
		}
		return	$RTN;
	}

	#----------------------------------------------------------------------------
	#	ディレクトリを中身ごと完全に削除する
	function rmdir( $path ){
		#	このメソッドは、ファイルやシンボリックリンクも削除します。
		#	シンボリックリンクは、その先を追わず、
		#	シンボリックリンク本体のみを削除します。

		if( $this->conf->system_exec_mode == 'setup' ){
			#	セットアップモードが有効なとき
			return	false;
		}

		if( strlen( $this->conf->fs_encoding ) ){
			//PxFW 0.6.4 追加
			$path = @text::convert_encoding( $path , $this->conf->fs_encoding );
		}

		if( !$this->is_writable( $path ) ){
			return false;
		}
		$path = @realpath( $path );
		if( $path === false ){ return false; }
		if( @is_file( $path ) || @is_link( $path ) ){
			#	ファイルまたはシンボリックリンクの場合の処理
			$result = @unlink( $path );
			return	$result;

		}elseif( @is_dir( $path ) ){
			#	ディレクトリの処理
			$flist = $this->getfilelist( $path );
			foreach ( $flist as $Line ){
				if( $Line == '.' || $Line == '..' ){ continue; }
				$this->rmdir( $path.'/'.$Line );
			}
			$result = @rmdir( $path );
			return	$result;

		}

		return	false;
	}

	#----------------------------------------------------------------------------
	#	ディレクトリの内部を比較し、$comparisonに含まれない要素を$targetから削除する
	function compare_and_cleanup( $target , $comparison ){
		if( $this->conf->system_exec_mode == 'setup' ){
			#	セットアップモードが有効なとき
			return	false;
		}

		if( is_null( $comparison ) || is_null( $target ) ){ return	false; }

		if( strlen( $this->conf->fs_encoding ) ){
			//PxFW 0.6.4 追加
			$target = @text::convert_encoding( $target , $this->conf->fs_encoding );
			$comparison = @text::convert_encoding( $comparison , $this->conf->fs_encoding );
		}

		if( !@file_exists( $comparison ) && @file_exists( $target ) ){
			$this->rmdir( $target );
			return	true;
		}

		if( @is_dir( $target ) ){
			$flist = $this->getfilelist( $target );
		}else{
			return	true;
		}

		foreach ( $flist as $Line ){
			if( $Line == '.' || $Line == '..' ){ continue; }
			$this->compare_and_cleanup( $target.'/'.$Line , $comparison.'/'.$Line );
		}

		return	true;
	}

	#----------------------------------------------------------------------------
	#	指定されたディレクトリ以下の、全ての空っぽのディレクトリを削除する
	function rmemptydir( $path , $option = array() ){
		if( $this->conf->system_exec_mode == 'setup' ){
			#	セットアップモードが有効なとき
			return	false;
		}

		if( strlen( $this->conf->fs_encoding ) ){
			//PxFW 0.6.4 追加
			$path = @text::convert_encoding( $path , $this->conf->fs_encoding );
		}

		if( !$this->is_writable( $path ) ){ return false; }
		if( !@is_dir( $path ) ){ return false; }
		if( @is_file( $path ) || @is_link( $path ) ){ return false; }
		$path = @realpath( $path );
		if( $path === false ){ return false; }

		#--------------------------------------
		#	次の階層を処理するかどうかのスイッチ
		$switch_donext = false;
		if( is_null( $option['depth'] ) ){
			#	深さの指定がなければ掘る
			$switch_donext = true;
		}elseif( !is_int( $option['depth'] ) ){
			#	指定がnullでも数値でもなければ掘らない
			$switch_donext = false;
		}elseif( $option['depth'] <= 0 ){
			#	指定がゼロ以下なら、今回の処理をして終了
			$switch_donext = false;
		}elseif( $option['depth'] > 0 ){
			#	指定が正の数(ゼロは含まない)なら、掘る
			$option['depth'] --;
			$switch_donext = true;
		}else{
			return	false;
		}
		#	/ 次の階層を処理するかどうかのスイッチ
		#--------------------------------------

		$flist = $this->getfilelist( $path );
		if( !count( $flist ) ){
			#	開いたディレクトリの中身が
			#	"." と ".." のみだった場合
			#	削除して終了
			$result = @rmdir( $path );
			return	$result;
		}
		$alive = false;
		foreach ( $flist as $Line ){
			if( $Line == '.' || $Line == '..' ){ continue; }
			if( @is_link( $path.'/'.$Line ) ){
				#	シンボリックリンクはシカトする。
			}elseif( @is_dir( $path.'/'.$Line ) ){
				if( $switch_donext ){
					#	さらに掘れと指令があれば、掘る。
					$this->rmemptydir( $path.'/'.$Line , $option );
				}
			}
			if( @file_exists( $path.'/'.$Line ) ){
				$alive = true;
			}
		}
		if( !$alive ){
			$result = @rmdir( $path );
			return	$result;
		}
		return	true;
	}


	#----------------------------------------------------------------------------
	#	指定された2つのディレクトリの内容を比較し、まったく同じかどうか調べる
	function compare_dir( $dir_a , $dir_b , $option = array() ){
		#	$option['compare_filecontent'] = bool;
		#		ファイルの中身も比較するか
		#	$option['compare_emptydir'] = bool;
		#		空っぽのディレクトリの有無も評価に含めるか？

		if( strlen( $this->conf->fs_encoding ) ){
			//PxFW 0.6.4 追加
			$dir_a = @text::convert_encoding( $dir_a , $this->conf->fs_encoding );
			$dir_b = @text::convert_encoding( $dir_b , $this->conf->fs_encoding );
		}

		if( ( @is_file( $dir_a ) && !@is_file( $dir_b ) ) || ( !@is_file( $dir_a ) && @is_file( $dir_b ) ) ){
			return	false;
		}
		if( ( ( @is_dir( $dir_a ) && !@is_dir( $dir_b ) ) || ( !@is_dir( $dir_a ) && @is_dir( $dir_b ) ) ) && $option['compare_emptydir'] ){
			return	false;
		}

		if( @is_file( $dir_a ) && @is_file( $dir_b ) ){
			#--------------------------------------
			#	両方ファイルだったら
			if( $option['compare_filecontent'] ){
				#	ファイルの内容も比較する設定の場合、
				#	それぞれファイルを開いて同じかどうかを比較
				$filecontent_a = $this->file_get_contents( $dir_a );
				$filecontent_b = $this->file_get_contents( $dir_b );
				if( $filecontent_a !== $filecontent_b ){
					return	false;
				}
			}
			return	true;
		}

		if( @is_dir( $dir_a ) || @is_dir( $dir_b ) ){
			#--------------------------------------
			#	両方ディレクトリだったら
			$contlist_a = $this->getfilelist( $dir_a );
			$contlist_b = $this->getfilelist( $dir_b );

			if( $option['compare_emptydir'] && $contlist_a !== $contlist_b ){
				#	空っぽのディレクトリも厳密に評価する設定で、
				#	ディレクトリ内の要素配列の内容が異なれば、false。
				return	false;
			}

			$done = array();
			foreach( $contlist_a as $Line ){
				#	Aをチェック
				if( $Line == '..' || $Line == '.' ){ continue; }
				if( !$this->compare_dir( $dir_a.'/'.$Line , $dir_b.'/'.$Line , $option ) ){
					return	false;
				}
				$done[$Line] = true;
			}

			foreach( $contlist_b as $Line ){
				#	Aに含まれなかったBをチェック
				if( $done[$Line] ){ continue; }
				if( $Line == '..' || $Line == '.' ){ continue; }
				if( !$this->compare_dir( $dir_a.'/'.$Line , $dir_b.'/'.$Line , $option ) ){
					return	false;
				}
				$done[$Line] = true;
			}

		}

		return	true;
	}


	#******************************************************************************************************************
	#	エラー処理

	#--------------------------------------
	#	エラーを記録
	function adderror( $errortext = null , $errorkey = null , $file = null , $line = null ){
		static $seq;	// シーケンス
		if( !$errortext ){ return null; }
		if( !$seq ){ $seq = 0; }
		if( is_null( $errorkey ) ){ $errorkey = $seq; }
		if( is_null( $errortext ) ){ $errortext = 'Error'; }
		$this->errorlist[$errorkey] = $errortext;
		$seq ++;	// シーケンスを一つ進める

		#	エラーログを保存
		$this->errors->error_log( $errortext , $file , $line );

		return	true;
	}
	function geterrorlist(){
		return	$this->errorlist;
	}


	#******************************************************************************************************************
	#	パス処理系メソッド
	#	anch: path_operators

	function getpath_contents( $localpath = null ){
		return	$this->get_realpath( $this->conf->path_contents_dir.$localpath );
	}
	function getpath_sitemap( $localpath = null ){
		return	$this->get_realpath( $this->conf->path_sitemap_dir.$localpath );
	}
	function getpath_romdata( $localpath = null ){
		return	$this->get_realpath( $this->conf->path_ramdata_dir.$localpath );
	}
	function getpath_ramdata( $localpath = null ){
		return	$this->get_realpath( $this->conf->path_ramdata_dir.$localpath );
	}
	function getpath_theme_collection( $localpath = null ){
		return	$this->get_realpath( $this->conf->path_theme_collection_dir.$localpath );
	}
	function getpath_system( $localpath = null ){
		return	$this->get_realpath( $this->conf->path_system_dir.$localpath );
	}





	#******************************************************************************************************************
	#	その他
	#	anch: allabout_others

	#--------------------------------------
	#	基本ライブラリのロード
	function require_lib( $lib_localpath , $layer = 'theme' , $themeCt = null , $themeId = null , $options = array() ){
		$lib_localpath = preg_replace( '/^\/'.'*'.'/' , '/' , $lib_localpath );
		$lib_localpath = preg_replace( '/\/+/' , '/' , $lib_localpath );
		$classname_body = str_replace( '/' , '_' , text::trimext( $lib_localpath ) );

		if( !strlen( $layer ) || !is_string( $layer ) ){
			#	デフォルトはテーマ層
			$layer = 'theme';
		}
		$layer = strtolower( $layer );

		if( is_null( $this->conf->theme_id ) && $layer == 'theme' ){
			#	Pickles Framework 0.4.0 追加
			#	ThemeId = null が採用されても、
			#	defaultテーマのviewstyleが採用されていた
			#	不具合に対する修正として。
			$layer = 'project';
		}

		if( $layer == 'theme' ){
			#	テーマ層指定だったら、
			#	第3、第4引数を確認
			#	$themeCtと$themeIdの両方が必須となり、
			#	かつ、存在する有効なテーマである必要がある。
			#	さもなくば、$confから、$userが設定した値を取得。
			#	それでもなければ、project指定として扱う。

			if( class_exists( 'theme'.$classname_body ) ){
				#	既にそのクラス名が存在していたら、そこでOK。
				return	'theme'.$classname_body;
			}

			if( !strlen( $themeCt ) && strlen( $this->conf->CT ) ){
				$themeCt = $this->conf->CT;
			}
			if( !strlen( $themeId ) && strlen( $this->conf->theme_id ) ){
				$themeId = $this->conf->theme_id;
			}
			if( !strlen( $themeId ) && strlen( $this->conf->default_theme_id ) ){
				$themeId = $this->conf->default_theme_id;
			}

			if( !strlen( $themeCt ) || !strlen( $themeId ) ){
				$layer = 'project';
			}
			if( !@is_dir( $this->conf->path_theme_collection_dir.'/'.$themeId.'/'.$themeCt ) ){//Pickles Framework 0.4.0 より前のバージョンでは、$themeIdと$themeCtとの順が逆でした。
				$layer = 'project';
			}
		}

		$adoptLayer = null;
		switch( $layer ){
			case 'theme':
				#	テーマ層
				#	$themeCtと$themeIdは、大文字/小文字を区別します。
				if( class_exists( 'theme'.$classname_body ) ){
					#	既にそのクラス名が存在していたら、そこでOK。
					return	'theme'.$classname_body;
				}
				$rootpath_tmp = $this->conf->path_theme_collection_dir.$themeId.'/'.$themeCt.'/lib';//Pickles Framework 0.4.0 より前のバージョンでは、$themeIdと$themeCtとの順が逆でした。
				if( isolated::require_once_with_conf( $rootpath_tmp.$lib_localpath , &$this->conf ) ){
					#	対象のファイルを見つけたら、
					#	パスをセットしてswitchを抜ける。
					if( class_exists( 'theme'.$classname_body ) ){
						#	クラスがちゃんと存在したら。
						$adoptLayer = 'theme';
						$rootpath = $rootpath_tmp;
						break;
					}
				}
				unset($rootpath_tmp);
			case 'project':
				if( class_exists( 'project'.$classname_body ) ){
					#	既にそのクラス名が存在していたら、そこでOK。
					return	'project'.$classname_body;
				}
				if( isolated::require_once_with_conf( $this->conf->path_lib_project.$lib_localpath , &$this->conf ) ){
					#	対象のファイルを見つけたら、
					#	パスをセットしてswitchを抜ける。
					if( class_exists( 'project'.$classname_body ) ){
						#	クラスがちゃんと存在したら。
						$adoptLayer = 'project';
						$rootpath = $this->conf->path_lib_project;
						break;
					}
				}
			case 'package':
				if( class_exists( 'package'.$classname_body ) ){
					#	既にそのクラス名が存在していたら、そこでOK。
					return	'package'.$classname_body;
				}
				if( isolated::require_once_with_conf( $this->conf->path_lib_package.$lib_localpath , &$this->conf ) ){
					#	対象のファイルを見つけたら、
					#	パスをセットしてswitchを抜ける。
					if( class_exists( 'package'.$classname_body ) ){
						#	クラスがちゃんと存在したら。
						$adoptLayer = 'package';
						$rootpath = $this->conf->path_lib_package;
						break;
					}
				}
			case 'base':
				if( class_exists( 'base'.$classname_body ) ){
					#	既にそのクラス名が存在していたら、そこでOK。
					return	'base'.$classname_body;
				}
				if( isolated::require_once_with_conf( $this->conf->path_lib_base.$lib_localpath , &$this->conf ) ){
					#	対象のファイルを見つけたら、
					#	パスをセットしてswitchを抜ける。
					if( class_exists( 'base'.$classname_body ) ){
						#	クラスがちゃんと存在したら。
						$adoptLayer = 'base';
						$rootpath = $this->conf->path_lib_base;
						break;
					}
				}
			default:
				return	false;
				break;
		}

		if( $this->conf->debug_mode ){
			if( $layer != 'theme' && $layer != $adoptLayer ){
				$this->errors->error_log( '['.$layer.']を探した結果、['.$adoptLayer.']が採用されました。['.$lib_localpath.']' );
			}
		}

		if( !class_exists( $adoptLayer.$classname_body ) ){
			return	false;
		}
		return	$adoptLayer.$classname_body;
	}


	#--------------------------------------
	#	ページャー情報を計算して答える
	function get_pager_info( $total_count , $current_page_num , $display_per_page = 10 , $option = array() ){
		#	Pickles Framework 0.1.3 で追加

		#	総件数
		$total_count = intval( $total_count );
		if( $total_count <= 0 ){ return false; }

		#	現在のページ番号
		$current_page_num = intval( $current_page_num );
		if( $current_page_num <= 0 ){ $current_page_num = 1; }

		#	ページ当たりの表示件数
		$display_per_page = intval( $display_per_page );
		if( $display_per_page <= 0 ){ $display_per_page = 10; }

		#	インデックスの範囲
		$index_size = 0;
		if( !is_null( $option['index_size'] ) ){
			$index_size = intval( $option['index_size'] );
		}
		if( $index_size < 1 ){
			$index_size = 5;
		}

		$RTN = array(
			'tc'=>$total_count,
			'dpp'=>$display_per_page,
			'current'=>$current_page_num,
			'total_page_count'=>null,
			'first'=>null,
			'prev'=>null,
			'next'=>null,
			'last'=>null,
			'limit'=>$display_per_page,
			'offset'=>0,
			'index_start'=>0,
			'index_end'=>0,
			'errors'=>array(),
		);

		if( $total_count%$display_per_page ){
			$RTN['total_page_count'] = intval($total_count/$display_per_page) + 1;
		}else{
			$RTN['total_page_count'] = intval($total_count/$display_per_page);
		}

		if( $RTN['total_page_count'] != $current_page_num ){
			$RTN['last'] = $RTN['total_page_count'];
		}
		if( 1 != $current_page_num ){
			$RTN['first'] = 1;
		}

		if( $RTN['total_page_count'] > $current_page_num ){
			$RTN['next'] = intval($current_page_num) + 1;
		}
		if( 1 < $current_page_num ){
			$RTN['prev'] = intval($current_page_num) - 1;
		}

		$RTN['offset'] = ($RTN['current']-1)*$RTN['dpp'];

		if( $current_page_num > $RTN['total_page_count'] ){
			array_push( $RTN['errors'] , 'Current page num ['.$current_page_num.'] is over the Total page count ['.$RTN['total_page_count'].'].' );
		}

		#	インデックスの範囲
		#		23:50 2007/08/29 Pickles Framework 0.1.8 追加
		$RTN['index_start'] = 1;
		$RTN['index_end'] = $RTN['total_page_count'];
		if( ( $index_size*2+1 ) >= $RTN['total_page_count'] ){
			#	範囲のふり幅全開にしたときに、
			#	総ページ数よりも多かったら、常に全部出す。
			$RTN['index_start'] = 1;
			$RTN['index_end'] = $RTN['total_page_count'];
		}elseif( ( $index_size < $RTN['current'] ) && ( $index_size < ( $RTN['total_page_count']-$RTN['current'] ) ) ){
			#	範囲のふり幅全開にしたときに、
			#	すっぽり収まるようなら、前後に $index_size 分だけ出す。
			$RTN['index_start'] = $RTN['current']-$index_size;
			$RTN['index_end'] = $RTN['current']+$index_size;
		}elseif( $index_size >= $RTN['current'] ){
			#	前方が収まらない場合は、
			#	あまった分を後方に回す
			$surplus = ( $index_size - $RTN['current'] + 1 );
			$RTN['index_start'] = 1;
			$RTN['index_end'] = $RTN['current']+$index_size+$surplus;
		}elseif( $index_size >= ( $RTN['total_page_count']-$RTN['current'] ) ){
			#	後方が収まらない場合は、
			#	あまった分を前方に回す
			$surplus = ( $index_size - ($RTN['total_page_count']-$RTN['current']) );
			$RTN['index_start'] = $RTN['current']-$index_size-$surplus;
			$RTN['index_end'] = $RTN['total_page_count'];
		}

		return	$RTN;
	}

	#--------------------------------------
	#	アプリケーションをロックする
	function lock( $lockname = 'applock' , $user_cd = null , $timeout_limit = 10 , $lockfile_expire = 0 ){
		#	PxFW 0.6.4 : $lockfile_expire を追加。
		if( !preg_match( '/^[a-zA-Z0-9_-]+$/ism' , $lockname ) ){ $lockname = 'applock'; }
		$lockfilepath = $this->conf->path_system_dir.'applock/'.$lockname.'.txt';
		$lockfile_expire = intval( $lockfile_expire );

		$timeout_limit = intval( $timeout_limit );
		if( $timeout_limit <= 0 ){
			#	初期値：10秒
			$timeout_limit = 10;
		}

		if( !@is_dir( dirname( $lockfilepath ) ) ){
			$this->mkdirall( dirname( $lockfilepath ) );
		}

		#	PHPのFileStatusCacheをクリア
		clearstatcache();

		$i = 0;
		while( ( @is_file( $lockfilepath ) && @filesize( $lockfilepath ) ) ){
			if( $lockfile_expire > 0 ){
				$file_bin = $this->file_get_contents( $lockfilepath );
				$file_bin_ary = explode( '::' , $file_bin );
				$file_time = time::datetime2int( $file_bin_ary[1] );
				if( ( time() - $file_time ) > $lockfile_expire ){
					#	有効期限を過ぎていたら、ロックは成立する。(PxFW0.6.4 追加仕様)
					break;
				}
			}

			$i ++;
			if( $i >= $timeout_limit ){
				return false;
				break;
			}
			sleep(1);

			#	PHPのFileStatusCacheをクリア
			clearstatcache();
		}
		$RTN = $this->file_overwrite( $lockfilepath , $user_cd.'::'.date( 'Y-m-d H:i:s' , $this->conf->time ) );
		return	$RTN;
	}

	#--------------------------------------
	#	アプリケーションロックを解除する
	function unlock( $lockname = 'applock' ){
		if( !preg_match( '/^[a-zA-Z0-9_-]+$/ism' , $lockname ) ){ $lockname = 'applock'; }
		$lockfilepath = $this->conf->path_system_dir.'applock/'.$lockname.'.txt';

		#	PHPのFileStatusCacheをクリア
		clearstatcache();

		$this->rmdir( $lockfilepath );
		$RTN = $this->file_overwrite( $lockfilepath , '' );
		return	$RTN;
	}

	#--------------------------------------
	#	実行したコマンドの標準出力を得て返す。
	function get_cmd_stdout( $cmd ){
		$res = @popen( $cmd , 'r' );
		$RTN = '';
		while( $LINE = @fgets( $res ) ){
			$RTN .= $LINE;
		}
		@pclose($res);
		return $RTN;
	}

	#******************************************************************************************************************
	#	終了系の処理集

	#--------------------------------------
	#	全てのファイルとデータベースを閉じる
	function close_all(){
		$res_f = $this->fclose_all();
		$res_d = $this->disconnect_all();

		if( !$res_f || !$res_d ){ return false; }

		return	true;
	}

	#--------------------------------------
	#	開いている全てのファイルを閉じる
	function fclose_all(){
		foreach($this->file as $line){
			$this->fclose( $line['filepath'] );
		}
		return	true;
	}

	#--------------------------------------
	#	開いているファイル(単体)を閉じる
	function fclose( $filepath ){
		$filepath = $this->get_realpath( $filepath );
		if( !$this->is_file_open( $filepath ) ){
			#	ファイルを開いていない状態だったらスキップ
			return	false;
		}

		if( $this->file[$filepath]['flock'] ){
			flock( $this->file[$filepath]['res'] , LOCK_UN );
		}
		fclose( $this->file[$filepath]['res'] );
		unset( $this->file[$filepath] );
		return	true;
	}

	#--------------------------------------
	#	ファイルを開いている状態か確認する
	function is_file_open( $filepath ){
		#	Pickles Framework 0.3.2 追加 0:57 2008/05/17
		$filepath = $this->get_realpath( $filepath );
		if( !@array_key_exists( $filepath , $this->file ) ){ return false; }
		if( !@is_array( $this->file[$filepath] ) ){ return false; }
		return	true;
	}

	#--------------------------------------
	#	データベースコネクションを切断する
	function disconnect_all(){
		return	$this->disconnect();
	}
	function disconnect(){
		if( !$this->check_connection() ){return	true;}
		if( $this->is_transaction() ){
			if( $this->localconf_auto_commit_flg ){
				#	オートコミットモード
				$this->commit();
			}else{
				#	オートコミットモードが無効な場合、ロールバック
				$this->rollback();
			}
		}

		if( $this->conf->rdb['type'] == 'MySQL' ){
			#--------------------------------------
			#	【 MySQL 】
			if( mysql_close( $this->con ) ){
				unset( $this->con );
				return	true;
			}else{
				$this->adderror( 'Faild to disconnect DB.' , 'disconnect' , __FILE__ , __LINE__ );
				return	false;
			}

		}elseif( $this->conf->rdb['type'] == 'PostgreSQL' ){
			#--------------------------------------
			#	【 PostgreSQL 】
			if( pg_close( $this->con ) ){
				unset( $this->con );
				return	true;
			}else{
				$this->adderror( 'Faild to disconnect DB.' , 'disconnect' , __FILE__ , __LINE__ );
				return	false;
			}

		}elseif( $this->conf->rdb['type'] == 'SQLite' ){
			#--------------------------------------
			#	【 SQLite 】
			sqlite_close( $this->con );
			unset( $this->con );
			return	true;

		}elseif( $this->conf->rdb['type'] == 'Oracle' ){
			#--------------------------------------
			#	【 Oracle 】
			#	UTODO : Oracle : 未実装です。

		}
		$this->adderror( '未対応のデータベースです。' , 'disconnect' , __FILE__ , __LINE__ );
		return	false;
	}


	#--------------------------------------
	#	サーバがUNIXパスか調べる
	#	PicklesFramework 0.3.0 追加
	function is_unix(){
		$rootpath = @realpath( '/' );
		if( $rootpath == '/' ){
			return	true;
		}
		return	false;
	}

	#--------------------------------------
	#	サーバがWindowsパスか調べる
	#	PicklesFramework 0.3.0 追加
	function is_windows(){
		$rootpath = @realpath( '/' );
		if( preg_match( '/^(?:[a-z]\:|'.preg_quote('\\\\','/').')/is' , $rootpath ) ){
			return	true;
		}
		return	false;
	}

}

?>