<?php

#	Copyright (C)Tomoya Koyanagi.
#	Last Update : 19:45 2008/07/10

#******************************************************************************************************************
#	メール送信クラス
class base_resources_mail{

	var $conf;
	var $errors;

	var $from = array( 'name' => 'Pickles Framework' , 'address' => 'errormail@pickles.framework' );
	var $sender = array();
	var $to = array();
	var $cc = array();
	var $bcc = array();
	var $subject = '';
	var $body = '';
	var $errorto = array();
	var $returnto = array();
	var $attach = array();
	var $charset = 'ISO-2022-JP';

	#----------------------------------------------------------------------------
	#	コンストラクタ
	function base_resources_mail( &$conf , &$errors ){
		$this->conf = &$conf;
		$this->errors = &$errors;
	}

	#----------------------------------------------------------------------------
	#	送信者(From)を設定する
	function setfrom( $address , $name = null ){
		if( !$this->check_email( $address ) ){
			return	false;
		}
		$this->from['address'] = $address;
		$this->from['name'] = $name;
		return	true;
	}
	#----------------------------------------------------------------------------
	#	送信者(Sender)を設定する
	function setsender( $address , $name = null ){
		if( !$this->check_email( $address ) ){
			return	false;
		}
		$this->sender['address'] = $address;
		$this->sender['name'] = $name;
		return	true;
	}
	#----------------------------------------------------------------------------
	#	errortoを設定する
	function seterrorto( $address , $name = null ){
		if( !$this->check_email( $address ) ){
			return	false;
		}
		$this->errorto['address'] = $address;
		$this->errorto['name'] = $name;
		return	true;
	}
	#----------------------------------------------------------------------------
	#	returntoを設定する
	function setreturnto( $address , $name = null ){
		if( !$this->check_email( $address ) ){
			return	false;
		}
		$this->returnto['address'] = $address;
		$this->returnto['name'] = $name;
		return	true;
	}
	#----------------------------------------------------------------------------
	#	サブジェクトを設定する
	function setsubject( $subject ){
		$this->subject = $subject;
		return	true;
	}
	#----------------------------------------------------------------------------
	#	本文を設定する
	function setbody( $body ){
		$this->body .= $body;
		return	true;
	}
	function clearbody(){
		$this->body = '';
		return	true;
	}

	#----------------------------------------------------------------------------
	#	添付ファイルを追加する
	function putattach( $name , $contenttype , $encode , $body , $disposition = null ){
		#	$name → 添付ファイル名
		#	$contenttype → MIME
		#	$encode → 7bit、base64、file
		#	$body → データ本体。encodeがfileの場合はファイルのパス。
		#	$disposition → 任意。添付かどうかを指定？

		if($encode == 'file'){
			$body = @realpath($body);
			if(!@is_file($body)){
				return	false;
			}
		}
		$MEMO['name'] = $name;
		$MEMO['Content-type'] = $contenttype;
		$MEMO['encode'] = $encode;
		$MEMO['body'] = $body;
		$MEMO['Content-Disposition'] = $disposition;
		array_push( $this->attach , $MEMO );
		return	true;
	}

	#----------------------------------------------------------------------------
	#	宛先を追加する
	function putto( $address , $name = null ){
		if( !$this->check_email( $address ) ){
			$this->errors->adderror( 'メールアドレスの形式が不正です' , 0 , 2 );
			return	false;
		}
		array_push( $this->to , array( 'address' => $address , 'name' => $name ) );
		return	true;
	}
	function putcc( $address , $name = null ){
		if( !$this->check_email( $address ) ){
			$this->errors->adderror( 'メールアドレスの形式が不正です' , 0 , 2 );
			return	false;
		}
		array_push( $this->cc , array( 'address' => $address , 'name' => $name ) );
		return	true;
	}
	function putbcc( $address , $name = null ){
		if( !$this->check_email( $address ) ){
			$this->errors->adderror( 'メールアドレスの形式が不正です' , 0 , 2 );
			return	false;
		}
		array_push( $this->bcc , array( 'address' => $address , 'name' => $name ) );
		return	true;
	}

	#----------------------------------------------------------------------------
	#	メールアドレスの形式をチェック
	function check_email( $str ){
		#	Pickles Framework 0.3.9 変更。この関数だけの独自コードを排除した。
		return	text::is_email( $str );
	}

	#----------------------------------------------------------------------------
	#	Eメールを送信する
	function send(){
		$charset = $this->charset;
		$multipart = false;

		if( count( $this->attach ) ){
			$multipart = true;
		}
		$str_boundary = '----_PX'.md5( time() ).'_MULTIPART_MIXED_';


		$head = '';
		#--------------------------------------
		#	From
		if( strlen( $this->from['name'] ) && strlen( $this->from['address'] ) ){
			$head .= 'From: '.mb_encode_mimeheader( $this->from['name'] , $charset , 'B' ).' <'.$this->from['address'].'>'."\n";
		}elseif( strlen( $this->from['address'] ) ){
			$head .= 'From: '.$this->from['address'].''."\n";
		}
		#--------------------------------------
		#	Sender
		if( strlen( $this->sender['name'] ) && strlen( $this->sender['address'] ) ){
			$head .= 'Sender: '.mb_encode_mimeheader( $this->sender['name'] , $charset , 'B' ).' <'.$this->sender['address'].'>'."\n";
		}elseif( strlen( $this->sender['address'] ) ){
			$head .= 'Sender: '.$this->sender['address'].''."\n";
		}
		#--------------------------------------
		#	宛先系
		if( count($this->to) ){
			$head .= 'To: ';
			foreach( $this->to as $Line ){
				if( strlen( $Line['name'] ) && strlen( $Line['address'] ) ){
					$head .= ''.mb_encode_mimeheader( $Line['name'] , $charset , 'B' ).' <'.$Line['address'].'>,';
				}elseif( strlen( $Line['address'] ) ){
					$head .= ''.$Line['address'].',';
				}
			}
			$head = preg_replace( '/,+$/' , '' , $head );
			$head .= "\n";
		}
		if( count($this->cc) ){
			$head .= 'Cc: ';
			foreach( $this->cc as $Line ){
				if(strlen($Line['name'])){
					$head .= ''.mb_encode_mimeheader( $Line['name'] , $charset , 'B' ).' <'.$Line['address'].'>,';
				}else{
					$head .= ''.$Line['address'].',';
				}
			}
			$head = preg_replace( '/,+$/' , '' , $head );
			$head .= "\n";
		}
		if( count($this->bcc) ){
			$head .= 'Bcc: ';
			foreach( $this->bcc as $Line ){
				if(strlen($Line['name'])){
					$head .= ''.mb_encode_mimeheader( $Line['name'] , $charset , 'B' ).' <'.$Line['address'].'>,';
				}else{
					$head .= ''.$Line['address'].',';
				}
			}
			$head = preg_replace( '/,+$/' , '' , $head );
			$head .= "\n";
		}
		#--------------------------------------
		#	返信先
		if( count($this->returnto) ){
			$head .= 'Reply-To: ';
			if(strlen($this->returnto['name'])){
				$head .= mb_encode_mimeheader( $this->returnto['name'] , $charset , 'B' ).' <'.$this->returnto['address'].'>,';
			}else{
				$head .= $this->returnto['address'].',';
			}
			$head = preg_replace( '/,+$/' , '' , $head );
			$head .= "\n";
		}

		if( $multipart ){
			$head .= 'Content-Type: multipart/mixed; boundary="'.$str_boundary.'"'."\n";
		}else{
			$head .= 'Content-Type: text/plain; charset="'.$charset.'"'."\n";
		}

		#--------------------------------------
		#	ここから本文作成
		$body_fin = '';//本文の初期化
		if( $multipart ){
			#--------------------------------------
			#	マルチパートだったら(添付ファイルが存在する場合)

			$body_fin .= "\n";
			$body_fin .= '--'.$str_boundary."\n";
			$body_fin .= 'Content-type: text/plain; charset="'.$charset.'"'."\n";
			$body_fin .= "\n";
			$body_fin .= mb_convert_encoding( $this->body , $charset , mb_internal_encoding().',UTF-8,SJIS,EUC-JP,JIS' )."\n";
			$body_fin .= "\n";

			$body_keys = array_keys( $this->attach );
			foreach( $body_keys as $Line ){
				if( !$this->attach[$Line]['encode'] ){
					$this->attach[$Line]['encode'] = '7bit';
				}
				switch( $this->attach[$Line]['encode'] ){
					#--------------------------------------
					case '7bit':
					case '':
					case null:
						$body_fin .= "\n";
						$body_fin .= '--'.$str_boundary."\n";
						$body_fin .= 'Content-type: '.$this->attach[$Line]['Content-type'].'; charset="'.$charset.'"'."\n";
						if( $this->attach[$Line]['Content-Disposition'] ){
							if( !strlen( $this->attach[$Line]['name'] ) ){
								$this->attach[$Line]['name'] = basename( $this->attach[$Line]['body'] );
							}
							$body_fin .= 'Content-Disposition: '.$this->attach[$Line]['Content-Disposition'].'; filename="'.mb_encode_mimeheader( $this->attach[$Line]['name'] , $charset ).'"'."\n";
						}
						$body_fin .= 'Content-Transfer-Encoding: '.$this->attach[$Line]['encode']."\n";
						$body_fin .= "\n";

						#	JISに変換して本文にセット
						$body_fin .= $this->attach[$Line]['body'];
						break;

					#--------------------------------------
					case 'base64':
						$body_fin .= "\n";
						$body_fin .= '--'.$str_boundary."\n";
						$body_fin .= 'Content-type: '.$this->attach[$Line]['Content-type'].';'."\n";
						if( $this->attach[$Line]['Content-Disposition'] ){
							if( !strlen( $this->attach[$Line]['name'] ) ){
								$this->attach[$Line]['name'] = basename( $this->attach[$Line]['body'] );
							}
							$body_fin .= 'Content-Disposition: '.$this->attach[$Line]['Content-Disposition'].'; filename="'.mb_encode_mimeheader( $this->attach[$Line]['name'] , $charset , 'B' ).'"'."\n";
						}
						$body_fin .= 'Content-Transfer-Encoding: '.$this->attach[$Line]['encode']."\n";
						$body_fin .= "\n";

						$body_fin .= $this->attach[$Line]['body'];
						break;

					#--------------------------------------
					case 'file':
						$body_fin .= "\n";
						$body_fin .= '--'.$str_boundary."\n";
						$body_fin .= 'Content-type: '.$this->attach[$Line]['Content-type'].';'."\n";
						if( $this->attach[$Line]['Content-Disposition'] ){
							if( !strlen( $this->attach[$Line]['name'] ) ){
								$this->attach[$Line]['name'] = basename( $this->attach[$Line]['body'] );
							}
							$body_fin .= 'Content-Disposition: '.$this->attach[$Line]['Content-Disposition'].'; filename="'.mb_encode_mimeheader( $this->attach[$Line]['name'] , $charset , 'B' ).'"'."\n";
						}
						$body_fin .= 'Content-Transfer-Encoding: base64'."\n";
						$body_fin .= "\n";

						if( @is_file( $this->attach[$Line]['body'] ) ){
							$body_fin .= base64_encode( file_get_contents( $this->attach[$Line]['body'] ) );
						}else{
							$body_fin .= 'ファイルがありません。'."\n";
						}
						break;

					#--------------------------------------
					default:
						$body_fin .= "\n";
						$body_fin .= '--'.$str_boundary."\n";
						$body_fin .= 'Content-type: '.$this->attach[$Line]['Content-type'].';'."\n";
						if( $this->attach[$Line]['Content-Disposition'] ){
							if( !strlen( $this->attach[$Line]['name'] ) ){
								$this->attach[$Line]['filename'] = basename( $this->attach[$Line]['body'] );
							}
							$body_fin .= 'Content-Disposition: '.$this->attach[$Line]['Content-Disposition'].'; filename="'.mb_encode_mimeheader( $this->attach[$Line]['name'] , $charset ).'"'."\n";
						}
						$body_fin .= 'Content-Transfer-Encoding: '.$this->attach[$Line]['encode']."\n";
						$body_fin .= "\n";
						$body_fin .= "\n";
						break;
				}
			}
			$body_fin .= "\n";
			$body_fin .= '--'.$str_boundary.'--'."\n";

		}else{
			#--------------------------------------
			#	添付ファイルがない場合
			$body_fin .= mb_convert_encoding( $this->body , $charset , mb_internal_encoding().',UTF-8,SJIS,EUC-JP,JIS' )."\n";

		}

		$head = trim( $head );

		#--------------------------------------
		#	メールを発信する
		$results = $this->mail(
			null,	//	[to] は、$headの中に書かれています。
			mb_encode_mimeheader( $this->subject , $charset , 'B' ),
			$body_fin,
			$head
		);
		return	$results;
	}

	#--------------------------------------
	#	メールを発信する
	function mail( $to , $subject , $message , $additional_headers = null , $additional_parameters = null ){
		#	Pickles Framework 0.3.2 追加
		#	基本的に、PHPの mail()関数の、ただのラッパです。
		#	サーバのPHPに設定されたメール送信プログラムが、
		#	特殊なパラメータなどを特に要求する場合には、
		#	このメソッドを拡張することで対応してください。
		return	mail( $to , $subject , $message , $additional_headers , $additional_parameters );
	}

}

?>