<?php

#	Copyright (C)Tomoya Koyanagi.
#	Last Update : 22:53 2011/05/27

#******************************************************************************************************************
#	コンフィグ設定値の精査を行う。
#		Pickles Framework 0.2.0 で、base_resources_picklesinfo_setupmode から改名。
class base_resources_picklesinfo_configcheck{

	var $conf;
	var $error_list = array();

	#----------------------------------------------------------------------------
	#	コンストラクタ
	function base_resources_picklesinfo_configcheck( &$conf ){
		$this->conf = &$conf;
	}

	#----------------------------------------------------------------------------
	#	configcheck を実行する
	function configcheck(){
		#	Pickles Framework 0.2.1 で execute() に改名
		return	$this->execute();
	}
	function execute(){
		#	Pickles Framework 0.2.1 で追加

		if( strlen( $this->conf->php_mb_internal_encoding ) ){
			#	内部エンコード調整
			mb_internal_encoding( $this->conf->php_mb_internal_encoding );
		}

		if( !is_null( $_SERVER['REMOTE_ADDR'] ) ){
			#	ウェブアクセスへの応答
			header( 'Content-type: text/html; charset='.mb_internal_encoding() );
			print	'<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">'."\n";
			?><html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ja">
	<head>
		<meta http-equiv="content-type" content="text/html;charset=<?php print htmlspecialchars( mb_internal_encoding() ); ?>" />
		<title>Pickles Framework : Confing Check mode</title>
		<style type="text/css">
			#logo	{font-weight:bold; font-size:24px; border-bottom:1px solid #999999; font-family:"Helvetica","Arial"; }
			h1		{font-weight:bold; font-size:18px; background-color:#f3f3f3; padding:5px; border-left:3px solid #666666; font-family:"Helvetica","Arial"; }
			h2		{font-weight:bold; font-size:16px; background-color:#f5f5f5; padding:3px 3px 3px 8px; border-bottom:1px solid #999999; font-family:"Helvetica","Arial"; }
			h3		{font-weight:bold; font-size:14px; padding:2px 0px 2px 5px; border-left:3px solid #666666; font-family:"Helvetica","Arial"; }
			p,div	{font-weight:normal; font-size:13px; }
			.error	{color:#ff0000; }
			.ok		{color:#3333dd; }
			.boolTrue	{color:#0000ff; font-style:italic; }
			.boolFalse	{color:#005500; font-style:italic; }
			.null		{color:#666666; font-style:italic; }
			table.deftable			{empty-cells:show; margin:0px 0px 24px 0px; border-collapse:collapse; width:100%; }
			table.deftable caption	{}
			table.deftable tr		{}
			table.deftable tr th	{font-size:13px; empty-cells:show; border:1px solid #cccccc; font-weight:normal; background-color:#eeeeee; color:#333333; padding:3px 3px 3px 3px; text-align:left; width:20%; }
			table.deftable tr td	{font-size:13px; empty-cells:show; border:1px solid #cccccc; font-weight:normal; background-color:#ffffff; color:#333333; padding:3px 3px 3px 3px; text-align:left; width:80%; }
			table.deftable thead th	{font-size:13px; }
			table.deftable thead td	{font-size:13px; }
			table.deftable tfoot th	{font-size:13px; }
			table.deftable tfoot td	{font-size:13px; }
		</style>
	</head>
	<body>
		<div id="logo">Pickles Framework</div>
		<h1>Confing Check mode</h1>
<?php }else{
			#	コマンドラインへの応答
			print '---- Pickles Framework ----'."\n";
			print '-- Confing Check mode'."\n";
		}

		$RTN = '';

		$RTN .= $this->mk_hx('Pickles Framework Version Info');
		$version = $this->get_picklesframework_version();
		$history = $this->get_picklesframework_version_history();
		if( !is_null( $_SERVER['REMOTE_ADDR'] ) ){
			#--------------------------------------
			#	WEB ACCESS
			$RTN .= '<table class="deftable">'."\n";
			$RTN .= '	<tr>'."\n";
			$RTN .= '		<th>Version</th>'."\n";
			if( $version === false ){ $version = $this->print_ng().' Unknown version.'; }
			$RTN .= '		<td>'.$version.'</td>'."\n";
			$RTN .= '	</tr>'."\n";
			$RTN .= '	<tr>'."\n";
			$RTN .= '		<th>History</th>'."\n";
			if( $version === false ){ $version = $this->print_ng().' Unknown version.'; }
			$RTN .= '		<td>'."\n";
			if( $version === false ){
				$RTN .= '			<span class="null">'.$this->print_ng().' Unknown version history.'.'</span>'."\n";
			}elseif( !is_array( $history ) || !count( $history ) ){
				$RTN .= '			<span class="null">empty</span>'."\n";
			}else{
				foreach( $history as $versionInfo ){
					$RTN .= '			'.htmlspecialchars( $versionInfo['date'] ).' = &gt; '.htmlspecialchars( $versionInfo['name'] ).' '.htmlspecialchars( $versionInfo['version'] ).'<br />'."\n";
				}
			}
			$RTN .= '		</td>'."\n";
			$RTN .= '	</tr>'."\n";
			$RTN .= '</table>'."\n";
		}else{
			#--------------------------------------
			#	COMMAND LINE
			$RTN .= '    ** Version'."\n";
			if( $version === false ){ $version = $this->print_ng().' Unknown version.'; }
			$RTN .= '        '.$version.''."\n";
			$RTN .= '    ** History'."\n";
			if( $version === false ){ $version = $this->print_ng().' Unknown version.'; }
			if( $version === false ){
				$RTN .= '        '.$this->print_ng().' Unknown version history.'."\n";
			}elseif( !is_array( $history ) || !count( $history ) ){
				$RTN .= '        empty'."\n";
			}else{
				foreach( $history as $versionInfo ){
					$RTN .= '        '.$versionInfo['date'].'=>'.$versionInfo['name'].' '.$versionInfo['version']."\n";
				}
			}
		}

		$RTN .= $this->mk_hx('Pickles Plugins Info');
		$plugins_list = $this->get_plugin_list();
		if( !count( $plugins_list ) ){
			if( !is_null( $_SERVER['REMOTE_ADDR'] ) ){
				#--------------------------------------
				#	WEB ACCESS
				$RTN .= '<p>NO plugins found.</p>'."\n";
			}else{
				#--------------------------------------
				#	COMMAND LINE
				$RTN .= 'NO plugins found.'."\n";
			}
		}else{
			if( !is_null( $_SERVER['REMOTE_ADDR'] ) ){
				#--------------------------------------
				#	WEB ACCESS
				$RTN .= '<table class="deftable">'."\n";
				foreach( $plugins_list as $plugin_info ){
					$RTN .= '	<tr>'."\n";
					$RTN .= '		<th rowspan="3">'.htmlspecialchars( $plugin_info['name'] ).'</th>'."\n";
					$RTN .= '		<th>Version</th>'."\n";
					$version = $this->get_picklesframework_version( $plugin_info['name'] );
					$history = $this->get_picklesframework_version_history( $plugin_info['name'] );
					if( $version === false ){ $version = $this->print_ng().' Unknown version.'; }
					$RTN .= '		<td>'.$version.'</td>'."\n";
					$RTN .= '	</tr>'."\n";
					$RTN .= '	<tr>'."\n";
					$RTN .= '		<th>History</th>'."\n";
					$RTN .= '		<td>'."\n";
					if( $version === false ){
						$RTN .= '			<span class="null">'.$this->print_ng().' Unknown version history.'.'</span>'."\n";
					}elseif( !is_array( $history ) || !count( $history ) ){
						$RTN .= '			<span class="null">empty</span>'."\n";
					}else{
						foreach( $history as $versionInfo ){
							$RTN .= '			'.htmlspecialchars( $versionInfo['date'] ).' = &gt; '.htmlspecialchars( $versionInfo['name'] ).' '.htmlspecialchars( $versionInfo['version'] ).'<br />'."\n";
						}
					}
					$RTN .= '		</td>'."\n";
					$RTN .= '	</tr>'."\n";
					$RTN .= '	<tr>'."\n";
					$RTN .= '		<th>Setup Layer</th>'."\n";
					$RTN .= '		<td>'."\n";
					if( $plugin_info['base'] ){ $RTN .= '			base<br />'."\n"; }
					if( $plugin_info['package'] ){ $RTN .= '			package<br />'."\n"; }
					if( $plugin_info['project'] ){ $RTN .= '			project<br />'."\n"; }
					$RTN .= '		</td>'."\n";
					$RTN .= '	</tr>'."\n";
				}
				$RTN .= '</table>'."\n";
			}else{
				#--------------------------------------
				#	COMMAND LINE
				foreach( $plugins_list as $plugin_info ){
					$RTN .= '  ****** '.htmlspecialchars( $plugin_info['name'] ).' ******'."\n";
					$RTN .= '    ** Version'."\n";
					$version = $this->get_picklesframework_version( $plugin_info['name'] );
					$history = $this->get_picklesframework_version_history( $plugin_info['name'] );
					if( $version === false ){ $version = $this->print_ng().' Unknown version.'; }
					$RTN .= '        '.$version."\n";
					$RTN .= '    ** History'."\n";
					if( $version === false ){
						$RTN .= '        '.$this->print_ng().' Unknown version history.'."\n";
					}elseif( !is_array( $history ) || !count( $history ) ){
						$RTN .= '        empty'."\n";
					}else{
						foreach( $history as $versionInfo ){
							$RTN .= '        '.$versionInfo['date'].' => '.$versionInfo['name'].' '.$versionInfo['version'].''."\n";
						}
					}
					$RTN .= '    ** Setup Layer'."\n";
					if( $plugin_info['base'] ){ $RTN .= '        base'."\n"; }
					if( $plugin_info['package'] ){ $RTN .= '        package'."\n"; }
					if( $plugin_info['project'] ){ $RTN .= '        project'."\n"; }
				}
			}
		}

		$RTN .= $this->mk_hx('Path Config');
		$RTN .= $this->mk_simpletable(
			array(
				'path_root'=>$this->config_status($this->conf->path_root,'dir',true,true),
				'path_phpcommand'=>$this->config_status($this->conf->path_phpcommand,'string',true,true),
			)
		);

		$RTN .= $this->mk_hx('Lib Directory',3);
		$RTN .= $this->mk_simpletable(
			array(
				'path_lib_base'=>$this->config_status($this->conf->path_lib_base,'dir',true,true),
				'path_lib_package'=>$this->config_status($this->conf->path_lib_package,'dir',true,true),
				'path_lib_project'=>$this->config_status($this->conf->path_lib_project,'dir',true,true),
			)
		);

		$RTN .= $this->mk_hx('Project Directory',3);
		$RTN .= $this->mk_simpletable(
			array(
				'path_projectroot'=>$this->config_status($this->conf->path_projectroot,'dir',true,true),
				'path_contents_dir'=>$this->config_status($this->conf->path_contents_dir,'dir',true,true),
				'path_sitemap_dir'=>$this->config_status($this->conf->path_sitemap_dir,'dir',true,true),
				'path_theme_collection_dir'=>$this->config_status($this->conf->path_theme_collection_dir,'dir',true,true),
				'path_romdata_dir'=>$this->config_status($this->conf->path_romdata_dir,'dir',true,true),
				'path_ramdata_dir'=>$this->config_status($this->conf->path_ramdata_dir,'dir',true,true),
				'path_cache_dir'=>$this->config_status($this->conf->path_cache_dir,'dir',true,true),
				'path_system_dir'=>$this->config_status($this->conf->path_system_dir,'dir',true,true),
				'path_common_log_dir'=>$this->config_status($this->conf->path_common_log_dir,'dir',false,true),
				'session_save_path'=>$this->config_status($this->conf->session_save_path,'dir',false,true),
			)
		);

		$RTN .= $this->mk_hx('Document Path(s)',3);
		$RTN .= $this->mk_simpletable(
			array(
				'path_docroot'=>$this->config_status($this->conf->path_docroot,'dir'),
			)
		);

		$RTN .= $this->mk_hx('URL Config');
		$RTN .= $this->mk_simpletable(
			array(
				'url_root'=>$this->config_status($this->conf->url_root,'path',false,false),
				'url_domain'=>$this->config_status($this->conf->url_domain,'string',true,true),
				'url_sitetop'=>$this->config_status($this->conf->url_sitetop,'string',true,true),
				'url_action'=>$this->config_status($this->conf->url_action,'path',true,true),
				'url_resource'=>$this->config_status($this->conf->url_resource,'path',true,true),
				'url_localresource'=>$this->config_status($this->conf->url_localresource,'path',true,true),
				'url_themeresource'=>$this->config_status($this->conf->url_themeresource,'path',true,true),
				'default_filename'=>$this->config_status($this->conf->default_filename,'string'),
			)
		);

		$RTN .= $this->mk_hx('Project Information');
		$RTN .= $this->mk_simpletable(
			array(
				'info_projectid'=>$this->config_status($this->conf->info_projectid,'string'),
				'info_sitetitle'=>$this->config_status($this->conf->info_sitetitle,'string'),
				'show_invisiblepage'=>$this->config_status($this->conf->show_invisiblepage,'bool'),
//				'enable_urlmap'=>$this->config_status($this->conf->enable_urlmap,'bool'),//PxFW 0.7.0 削除
				'flg_staticurl'=>$this->config_status($this->conf->flg_staticurl,'bool'),
			)
		);

		$RTN .= $this->mk_hx('DBH Config');
		$value_writeprotect = null;
		if( !is_array( $this->conf->writeprotect ) || !count( $this->conf->writeprotect ) ){
			if( !is_null( $_SERVER['REMOTE_ADDR'] ) ){
				#--------------------------------------
				#	WEB ACCESS
				$value_writeprotect = '			<span class="null">empty</span>'."\n";
			}else{
				#--------------------------------------
				#	COMMAND LINE
				$value_writeprotect = 'empty';
			}
		}else{
			foreach( $this->conf->writeprotect as $Line ){
				if( !is_null( $_SERVER['REMOTE_ADDR'] ) ){
					#--------------------------------------
					#	WEB ACCESS
					$value_writeprotect = '			'.$this->config_status($Line,'path',true,true).'<br />'."\n";
				}else{
					#--------------------------------------
					#	COMMAND LINE
					$value_writeprotect = $this->config_status($Line,'path',true,true);
				}
			}
		}
		$value_readprotect = null;
		if( !is_array( $this->conf->readprotect ) || !count( $this->conf->readprotect ) ){
			if( !is_null( $_SERVER['REMOTE_ADDR'] ) ){
				#--------------------------------------
				#	WEB ACCESS
				$value_readprotect = '			<span class="null">empty</span>'."\n";
			}else{
				#--------------------------------------
				#	COMMAND LINE
				$value_readprotect = 'empty';
			}
		}else{
			foreach( $this->conf->readprotect as $Line ){
				if( !is_null( $_SERVER['REMOTE_ADDR'] ) ){
					#--------------------------------------
					#	WEB ACCESS
					$value_readprotect = '			'.$this->config_status($Line,'path',true,true).'<br />'."\n";
				}else{
					#--------------------------------------
					#	COMMAND LINE
					$value_readprotect = $this->config_status($Line,'path',true,true);
				}
			}
		}
		$RTN .= $this->mk_simpletable(
			array(
				'dbh_file_default_permission'=>$this->config_status('0'.decoct(intval($this->conf->dbh_file_default_permission)),'string'),
				'dbh_dir_default_permission'=>$this->config_status('0'.decoct(intval($this->conf->dbh_dir_default_permission)),'string'),
				'dbh_heavy_query_limit'=>$this->config_status($this->conf->dbh_heavy_query_limit,'string'),
				'writeprotect'=>$value_writeprotect,
				'readprotect'=>$value_readprotect,
			)
		);
		unset($value_writeprotect);
		unset($value_readprotect);


		$RTN .= $this->mk_hx('RDB Config',3);
		$DBCheck = $this->check_db_settings();
		$RTN .= $DBCheck['src'];
		$RTN .= $this->mk_hx('User Config');
		$RTN .= $this->mk_hx('User Management Mode',3);
		$RTN .= $this->mk_simpletable(
			array(
				'Mode'=>$this->get_user_management_mode(),
			)
		);

		if( strtolower( $this->get_user_management_mode() ) == 'file mode' ){
			$RTN .= $this->mk_hx('User Directory',3);
			$RTN .= $this->mk_simpletable(
				array(
					'path_userdir'=>$this->config_status($this->conf->path_userdir,'dir'),
				)
			);

		}elseif( strtolower( $this->get_user_management_mode() ) == 'database mode' ){
			$RTN .= $this->mk_hx('User Tables',3);
			$RTN .= $this->mk_simpletable(
				array(
					'master'=>$this->config_status($this->conf->rdb_usertable['master'],'string',true,true),
					'property'=>$this->config_status($this->conf->rdb_usertable['property'],'string',true,true),
					'project_authoptions'=>$this->config_status($this->conf->rdb_usertable['project_authoptions'],'string',true,true),
					'project_authgroup'=>$this->config_status($this->conf->rdb_usertable['project_authgroup'],'string',true,true),
					'project_status'=>$this->config_status($this->conf->rdb_usertable['project_status'],'string',true,true),
					'project_datas'=>$this->config_status($this->conf->rdb_usertable['project_datas'],'string',true,true),
					'group'=>$this->config_status($this->conf->rdb_usertable['group'],'string',true,true),
					'group_authoptions'=>$this->config_status($this->conf->rdb_usertable['group_authoptions'],'string',true,true),
				)
			);

		}
		$RTN .= $this->mk_hx('Login Config',3);
		$RTN .= $this->mk_simpletable(
			array(
				'allow_login_without_cookies'=>$this->config_status($this->conf->allow_login_without_cookies,'bool'),
				'allow_login_with_device_id'=>$this->config_status($this->conf->allow_login_with_device_id,'bool'),
				'user_keep_userid_on_session'=>$this->config_status($this->conf->user_keep_userid_on_session,'bool'),
				'try_to_login'=>$this->config_status($this->conf->try_to_login,'string'),
				'user_auth_method'=>$this->config_status($this->conf->user_auth_method,'string'),
				'session_key'=>$this->config_status($this->conf->session_key,'string'),
				'seckey'=>$this->config_status($this->conf->seckey,'string'),
				'logging_expire'=>$this->config_status($this->conf->logging_expire,'string'),
			)
		);

		$RTN .= $this->mk_hx('Log Config');
		$RTN .= $this->mk_simpletable(
			array(
				'errors_log_path'=>$this->config_status($this->conf->errors_log_path,'dir',true,true),
				'errors_log_rotate'=>$this->config_status($this->conf->errors_log_rotate,'string'),
				'access_log_path'=>$this->config_status($this->conf->access_log_path,'dir',true,true),
				'access_log_rotate'=>$this->config_status($this->conf->access_log_rotate,'string'),
			)
		);

		$RTN .= $this->mk_hx('PHP Setting');
		$RTN .= $this->mk_simpletable(
			array(
				'php_default_charset'=>$this->config_status($this->conf->php_default_charset,'string'),
				'php_mb_internal_encoding'=>$this->config_status($this->conf->php_mb_internal_encoding,'string'),
				'php_mb_http_input'=>$this->config_status($this->conf->php_mb_http_input,'string'),
				'php_mb_http_output'=>$this->config_status($this->conf->php_mb_http_output,'string'),
			)
		);

		$RTN .= $this->mk_hx('Language(s)');
		if( !is_null( $_SERVER['REMOTE_ADDR'] ) ){
			#--------------------------------------
			#	WEB ACCESS
			$RTN .= '<table class="deftable">'."\n";
			$RTN .= '	<tr>'."\n";
			$RTN .= '		<th>default_lang</th>'."\n";
			$RTN .= '		<td>'."\n";
			if( is_string($this->conf->default_lang) && is_array( $this->conf->allow_lang ) && $this->conf->allow_lang[$this->conf->default_lang] ){
				$RTN .= '			'.$this->print_ok().''."\n";
			}else{
				$RTN .= '			'.$this->print_ng().''."\n";
			}
			$RTN .= '			'.$this->config_status($this->conf->default_lang,'string',true,true).''."\n";
			$RTN .= '		</td>'."\n";
			$RTN .= '	</tr>'."\n";
			$RTN .= '	<tr>'."\n";
			$RTN .= '		<th>allow_lang</th>'."\n";
			$RTN .= '		<td>'."\n";
			if( !is_array( $this->conf->allow_lang ) || !count( $this->conf->allow_lang ) ){
				$RTN .= '			<span class="null">empty</span>'."\n";
			}else{
				foreach( array_keys( $this->conf->allow_lang ) as $Line ){
					$RTN .= '			'.htmlspecialchars( $Line ).' =&gt; '.$this->config_status($this->conf->allow_lang[$Line],'bool',true,true).'<br />'."\n";
				}
			}
			$RTN .= '		</td>'."\n";
			$RTN .= '	</tr>'."\n";
			$RTN .= '</table>'."\n";
		}else{
			#--------------------------------------
			#	COMMAND LINE
			$RTN .= '    ** default_lang'."\n";
			if( is_string($this->conf->default_lang) && is_array( $this->conf->allow_lang ) && $this->conf->allow_lang[$this->conf->default_lang] ){
				$RTN .= '        '.$this->print_ok().'';
			}else{
				$RTN .= '        '.$this->print_ng().'';
			}
			$RTN .= ' '.$this->config_status($this->conf->default_lang,'string',true,true).''."\n";
			$RTN .= '    ** allow_lang'."\n";
			if( !is_array( $this->conf->allow_lang ) || !count( $this->conf->allow_lang ) ){
				$RTN .= '        empty'."\n";
			}else{
				foreach( array_keys( $this->conf->allow_lang ) as $Line ){
					$RTN .= '        '.htmlspecialchars( $Line ).' => '.$this->config_status($this->conf->allow_lang[$Line],'bool',true,true).''."\n";
				}
			}
		}

		$RTN .= $this->mk_hx('Others');
		$tmp_array = array();
		$tmp_array['debug_mode'] = $this->config_status($this->conf->debug_mode,'bool');
		$tmp_array['system_exec_mode'] = $this->config_status($this->conf->system_exec_mode,'string');
		$tmp_array['debug_mode_print_exec_microtime'] = $this->config_status($this->conf->debug_mode_print_exec_microtime,'bool');
		$tmp_array['allow_picklesinfo_service'] = $this->config_status($this->conf->allow_picklesinfo_service,'bool');
		$tmp_array['contents_start_str'] = $this->config_status($this->conf->contents_start_str,'string');
		$tmp_array['contents_end_str'] = $this->config_status($this->conf->contents_end_str,'string');
		$tmp_array['auto_notfound'] = $this->config_status($this->conf->auto_notfound,'bool');//Pickles Framework 0.5.1
		$tmp_array['enable_contents_preprocessor'] = $this->config_status($this->conf->enable_contents_preprocessor,'bool');//Pickles Framework 0.5.2
		$tmp_array['allow_flush_content_without_pages'] = $this->config_status($this->conf->allow_flush_content_without_pages,'bool');//PxFW 0.7.0
		$tmp_array['generate_localresourcepath_by_pid'] = $this->config_status($this->conf->generate_localresourcepath_by_pid,'bool');

		$tmp_allow_client_ip = '';
		if( !is_array( $this->conf->allow_client_ip ) || !count( $this->conf->allow_client_ip ) ){
			if( !is_null( $_SERVER['REMOTE_ADDR'] ) ){
				$tmp_allow_client_ip .= '			<span class="null">empty</span>'."\n";
			}else{
				$tmp_allow_client_ip .= 'empty';
			}
		}else{
			foreach( $this->conf->allow_client_ip as $Line ){
				if( !is_null( $_SERVER['REMOTE_ADDR'] ) ){
					$tmp_allow_client_ip .= '			'.$this->config_status($Line,'string',true,true).'<br />'."\n";
				}else{
					$tmp_allow_client_ip .= ''.$this->config_status($Line,'string',true,true).'; ';
				}
			}
		}
		$tmp_array['allow_client_ip'] = trim($tmp_allow_client_ip);
		unset($tmp_allow_client_ip);

		$tmp_out_of_servicetime = '';
		if( !is_array( $this->conf->out_of_servicetime ) || !count( $this->conf->out_of_servicetime ) ){
			if( !is_null( $_SERVER['REMOTE_ADDR'] ) ){
				$tmp_out_of_servicetime .= '			<span class="null">empty</span>'."\n";
			}else{
				$tmp_out_of_servicetime .= 'empty';
			}
		}else{
			foreach( $this->conf->out_of_servicetime as $Line ){
				if( !is_null( $_SERVER['REMOTE_ADDR'] ) ){
					$tmp_out_of_servicetime .= '			'.$this->config_status($Line,'string',true,true).'<br />'."\n";
				}else{
					$tmp_out_of_servicetime .= ''.$this->config_status($Line,'string',true,true).'; ';
				}
			}
		}
		$tmp_array['out_of_servicetime'] = trim($tmp_out_of_servicetime);
		unset($tmp_out_of_servicetime);

		$tmp_email = '';
		if( !is_array( $this->conf->email ) || !count( $this->conf->email ) ){
			if( !is_null( $_SERVER['REMOTE_ADDR'] ) ){
				$tmp_email .= '			<span class="null">empty</span>'."\n";
			}else{
				$tmp_email .= 'empty';
			}
		}else{
			foreach( array_keys( $this->conf->email ) as $Line ){
				if( !is_null( $_SERVER['REMOTE_ADDR'] ) ){
					$tmp_email .= '			'.htmlspecialchars( $Line ).' =&gt; '.$this->config_status($this->conf->email[$Line],'string',true,true).'<br />'."\n";
				}else{
					$tmp_email .= $Line.'=>'.$this->config_status($this->conf->email[$Line],'string',true,true).'; ';
				}
			}
		}
		$tmp_array['email'] = trim($tmp_email);
		unset($tmp_email);

		$tmp_command_path = '';
		if( !is_array( $this->conf->path_commands ) || !count( $this->conf->path_commands ) ){
			if( !is_null( $_SERVER['REMOTE_ADDR'] ) ){
				$tmp_command_path .= '			<span class="null">empty</span>'."\n";
			}else{
				$tmp_command_path .= 'empty';
			}
		}else{
			foreach( array_keys( $this->conf->path_commands ) as $Line ){
				if( !is_null( $_SERVER['REMOTE_ADDR'] ) ){
					$tmp_command_path .= '			'.htmlspecialchars( $Line ).' =&gt; '.$this->config_status($this->conf->path_commands[$Line],'string',true,true).'<br />'."\n";
				}else{
					$tmp_command_path .= $Line.'=>'.$this->config_status($this->conf->path_commands[$Line],'string',true,true).'; ';
				}
			}
		}
		$tmp_array['path_commands'] = trim($tmp_command_path);
		unset($tmp_command_path);

		$tmp_array['fs_encoding']                               = $this->config_status( $this->conf->fs_encoding , 'string' );//PxFW 0.6.4 追加
//		$tmp_array['use_sitemapcache_flg']                      = $this->config_status( $this->conf->use_sitemapcache_flg , 'bool' );//PxFW 0.7.0 廃止
		$tmp_array['session_always_addgene_if_without_cookies'] = $this->config_status( $this->conf->session_always_addgene_if_without_cookies , 'bool' );
		$tmp_array['session_cache_limiter']                     = $this->config_status( $this->conf->session_cache_limiter , 'string' );
		$tmp_array['allow_cancel_customtheme']                  = $this->config_status( $this->conf->allow_cancel_customtheme , 'bool' );
		$tmp_array['default_theme_id']                          = $this->config_status( $this->conf->default_theme_id , 'string' );
		if( is_array($this->conf->authinfo) && count($this->conf->authinfo) ){
			$tmp_array['authinfo'] = '';
			foreach($this->conf->authinfo as $authKey=>$authRow){
				$tmp_array['authinfo'] .= '<div>['.htmlspecialchars($authKey).'] = '.$this->config_status( $authRow['type'] , 'string' ).' / '.$this->config_status( $authRow['id'] , 'string' ).' / ********</div>'."\n";
			}
		}else{
			$tmp_array['authinfo']                              = $this->config_status( $this->conf->authinfo , 'array' );
		}
		$tmp_array['cmd_default_authlevel']                     = $this->config_status( $this->conf->cmd_default_authlevel , 'int' );
		$tmp_array['enable_externalurl']                        = $this->config_status( $this->conf->enable_externalurl , 'bool' );//PxFW 0.6.10 追加

		$RTN .= $this->mk_simpletable( $tmp_array );
		unset( $tmp_array );

		$RTN .= "\n";
		$RTN .= "\n";
		print	$RTN;
		if( !is_null( $_SERVER['REMOTE_ADDR'] ) ){
			#	ウェブアクセスへの応答
?>	</body>
</html>
<?php }

		return	true;
	}


	#--------------------------------------
	#	ユーザ管理モードを調べる
	function get_user_management_mode(){
		if( is_array( $this->conf->rdb_usertable ) ){
			return	'Database mode';
		}elseif( @is_dir( $this->conf->path_userdir ) ){
			return	'File mode';
		}
		return	''.$this->print_ng().' Neither [File mode] nor [Database mode].';
	}


	#--------------------------------------
	#	DBセッティングを出力
	function check_db_settings(){
		$is_cmd = false;
		if( is_null( $_SERVER['REMOTE_ADDR'] ) ){ $is_cmd = true; }

		$RTN = array( 'src'=>'' , 'error'=>array() );

		if( !is_array( $this->conf->rdb ) || !count( $this->conf->rdb ) ){
			if( !is_array( $this->conf->rdb ) ){
				$RTN['error'][] = '$conf->rdb is NOT array.';
				if( $is_cmd ){
					$RTN['src'] .= '$conf->rdb is NOT array.'."\n";
				}else{
					$RTN['src'] .= '<p>$conf->rdb is NOT array.</p>'."\n";
				}
			}elseif( !count( $this->conf->rdb ) ){
				$RTN['error'][] = '$conf->rdb is empty array.';
				if( $is_cmd ){
					$RTN['src'] .= '$conf->rdb is empty array.'."\n";
				}else{
					$RTN['src'] .= '<p>$conf->rdb is empty array.</p>'."\n";
				}
			}
		}else{
			$notnull = true;
			$empty_as_null = true;
			$type_src = null;

			switch( $this->conf->rdb['type'] ){
				case 'MySQL':
				case 'PostgreSQL':
				case 'SQLite':
					$type_src = $this->print_ok().' '.$this->config_status($this->conf->rdb['type'],'string',true);
					break;
				case 'Oracle':
					if( $is_cmd ){
						$type_src = ''.$this->config_status($this->conf->rdb['type'],'string',true).' (Not recommended)';
					}else{
						$type_src = ''.$this->config_status($this->conf->rdb['type'],'string',true).' (<span class="error">Not recommended</span>)';
					}
					break;
				case '':
					$notnull = false;
					$empty_as_null = false;
					if( $is_cmd ){
						$type_src = 'Empty';
					}else{
						$type_src = '<span style="font-style:italic;">Empty</span>';
					}
					break;
				default:
					$RTN['error'][] = '['.htmlspecialchars( $this->conf->rdb['type'] ).'] is Unknown Database type.';
					$type_src = ''.$this->print_ng().' Error! ['.htmlspecialchars( $this->conf->rdb['type'] ).'] is Unknown Database type.';
					break;
			}
			$SP_RES_SERVER = $this->config_status($this->conf->rdb['server'],'string',$notnull,$empty_as_null);
			if( $this->conf->rdb['type'] == 'SQLite' ){
				$SP_RES_SERVER = $this->config_status($this->conf->rdb['server'],'string',false,true);
			}
			$RTN['src'] .= $this->mk_simpletable(
				array(
					'type'=>$type_src,
					'version'=>$this->config_status($this->conf->rdb['version'],'string',$notnull),
					'server'=>$SP_RES_SERVER,
					'user'=>$this->config_status($this->conf->rdb['user'],'string',$notnull),
					'passwd'=>$this->config_status($this->conf->rdb['passwd'],'string',$notnull),
					'name'=>$this->config_status($this->conf->rdb['name'],'string',$notnull,$empty_as_null),
					'port'=>$this->config_status($this->conf->rdb['port'],'string',$notnull),
					'charset'=>$this->config_status($this->conf->rdb['charset'],'string',$notnull),
					'sessionmode'=>$this->config_status($this->conf->rdb['sessionmode'],'string',$notnull),
				)
			);

		}
		return	$RTN;
	}





	#--------------------------------------
	#	ステータスを判断
	function config_status( $value , $type = 'dir' , $notnull = false , $empty_as_null = false ){
		$is_cmd = false;
		if( is_null( $_SERVER['REMOTE_ADDR'] ) ){ $is_cmd = true; }

		if( $notnull && is_null( $value ) ){
			if( $is_cmd ){
				return	''.$this->print_ng().'NULL';
			}else{
				return	''.$this->print_ng().' <span class="null">NULL</span>';
			}
		}elseif( $notnull && $empty_as_null && !strlen( $value ) ){
			if( $is_cmd ){
				return	''.$this->print_ng().'empty';
			}else{
				return	''.$this->print_ng().' <span class="null">empty</span>';
			}
		}elseif( is_null( $value ) ){
			if( $is_cmd ){
				return	'NULL';
			}else{
				return	'<span class="null">NULL</span>';
			}
		}

		$RTN = '';
		switch( strtolower( $type ) ){
			case 'file':
				if( @is_file( $value ) ){
					$RTN .= ''.$this->print_ok().' ';
				}elseif( @is_dir( $value ) ){
					$RTN .= '[ <span class="error">It is a Directory but must be a File</span> ] ';
				}else{
					$RTN .= '[ <span class="error">NOT a File</span> ] ';
				}
				if( !strlen( $value ) || !is_string( $value ) ){
					$RTN .= '<span class="null">empty</span>';
				}elseif( @realpath( $value ) ){
					$RTN .= @realpath( $value );
				}else{
					$RTN .= $value;
				}
				return	$RTN;
				break;
			case 'dir':
				if( @is_dir( $value ) ){
					$RTN .= ''.$this->print_ok().' ';
				}elseif( @is_file( $value ) ){
					if( $is_cmd ){
						$RTN .= '[ It is a File but must be a Directory ] ';
					}else{
						$RTN .= '[ <span class="error">It is a File but must be a Directory</span> ] ';
					}
				}else{
					if( $is_cmd ){
						$RTN .= '[ NOT a Directory ] ';
					}else{
						$RTN .= '[ <span class="error">NOT a Directory</span> ] ';
					}
				}
				if( !strlen( $value ) || !is_string( $value ) ){
					if( $is_cmd ){
						$RTN .= 'empty';
					}else{
						$RTN .= '<span class="null">empty</span>';
					}
				}elseif( @realpath( $value ) ){
					$RTN .= @realpath( $value );
				}else{
					$RTN .= $value;
				}
				break;
			case 'path':
				if( !is_string( $value ) && !is_null( $value ) ){
					return	''.$this->print_ng().' Is NOT a String or Null. ( type of '.gettype( $value ).' )';
				}
				if( $notnull && is_null( $value ) ){
					return	''.$this->print_ng().' Is Null.';
				}
				if( $notnull && !strlen( $value ) && $empty_as_null ){
					return	''.$this->print_ng().' Is Empty.';
				}
				if( !strlen( $value ) ){
					$RTN .= ''.$this->print_ok().' ';
				}elseif( !preg_match( '/^\/.+/' , $value ) ){
					$RTN .= ''.$this->print_ng().' ';
				}else{
					$RTN .= ''.$this->print_ok().' ';
				}
				if( is_null( $value ) ){
					if( $is_cmd ){
						$RTN .= ' NULL';
					}else{
						$RTN .= ' <span class="null">NULL</span>';
					}
				}elseif( !strlen( $value ) ){
					if( $is_cmd ){
						$RTN .= ' empty';
					}else{
						$RTN .= ' <span class="null">empty</span>';
					}
				}else{
					if( $is_cmd ){
						$RTN .= $value;
					}else{
						$RTN .= htmlspecialchars( $value );
					}
				}
				return	$RTN;
				break;
			case 'string':
				if( $is_cmd ){
					return	$value;
				}else{
					return	htmlspecialchars( $value );
				}
				break;
			case 'int':
				if( $is_cmd ){
					return	$value;
				}else{
					return	htmlspecialchars( $value );
				}
				break;
			case 'array':
				if( !is_array( $value ) ){
					if( $is_cmd ){
						return	'is NOT a array(type of '.gettype($value).')';
					}else{
						return	'<span class="error">is NOT a array(type of '.gettype($value).')</span>';
					}
				}else{
					if( $is_cmd ){
						return	'array count '.count( $value );
					}else{
						return	'<span>array count '.count( $value ).'</span>';
					}
				}
				break;
			case 'bool':
				if( !is_bool( $value ) ){
					if( $is_cmd ){
						return	'is NOT a bool(type of '.gettype($value).')';
					}else{
						return	'<span class="error">is NOT a bool(type of '.gettype($value).')</span>';
					}
				}elseif( $value ){
					if( $is_cmd ){
						return	'true';
					}else{
						return	'<span class="boolTrue">true</span>';
					}
				}else{
					if( $is_cmd ){
						return	'false';
					}else{
						return	'<span class="boolFalse">false</span>';
					}
				}
				break;
			default:
				if( $is_cmd ){
					return 'System Error! Unknown type';
				}else{
					return '<span class="error">System Error! Unknown type</span>';
				}
				break;
		}

		return	$RTN;
	}


	#--------------------------------------
	#	PicklesFrameworkのバージョン情報を返す
	function get_picklesframework_version( $plugins_name = null ){
		if( !@is_dir( $this->conf->path_lib_base ) ){
			return	false;
		}
		$local_path = '';
		if( strlen( $plugins_name ) ){
			$local_path = '/plugins/'.$plugins_name;
		}

		$path = $this->conf->path_lib_base.$local_path.'/_UPDATELOG_/setupHistory.log';
		if( !@is_file( $path ) ){
			return	false;
		}
		if( !@is_readable( $path ) ){
			return	false;
		}

		$lines = @file( $path );

		foreach( $lines as $Line ){
			$Line = preg_replace( '/\r\n|\r|\n/' , '' , $Line );
			list( $date , $version , $name ) = explode( '	' , $Line );
			if( !strlen( $name ) ){
				if( strlen( $plugins_name ) ){
					$name = $plugins_name;
				}else{
					$name = 'Pickles Framework';
				}
			}
		}
		return	$name.' '.$version;

	}

	#--------------------------------------
	#	PicklesFrameworkのバージョン履歴を返す
	function get_picklesframework_version_history( $plugins_name = null ){
		if( !@is_dir( $this->conf->path_lib_base ) ){
			return	false;
		}
		$local_path = '';
		if( strlen( $plugins_name ) ){
			$local_path = '/plugins/'.$plugins_name;
		}

		$path = $this->conf->path_lib_base.$local_path.'/_UPDATELOG_/setupHistory.log';
		if( !@is_file( $path ) ){
			return	false;
		}
		if( !@is_readable( $path ) ){
			return	false;
		}

		$lines = @file( $path );

		$RTN = array();
		foreach( $lines as $Line ){
			$Line = preg_replace( '/\r\n|\r|\n/' , '' , $Line );
			list( $date , $version , $name ) = explode( '	' , $Line );
			if( !strlen( $name ) ){
				if( strlen( $plugins_name ) ){
					$name = $plugins_name;
				}else{
					$name = 'Pickles Framework';
				}
			}

			array_push( $RTN , array( 'date'=>$date , 'version'=>$version , 'name'=>$name ) );
		}
		return	$RTN;

	}


	#--------------------------------------
	#	プラグインの一覧を得る
	function get_plugin_list(){
		$RTN = array();

		#ベース層
		$path = $this->conf->path_lib_base.'/plugins';

		#--------------------------------------
		#	ディレクトリオープン
		if( strlen( $path ) && @file_exists( $path ) && @is_dir( $path ) ){
			$filelist = array();
			$dr = opendir($path);
			while( ( $ent = readdir( $dr ) ) !== false ){
				#	CurrentDirとParentDirは含めない
				if( $ent == '.' || $ent == '..' ){ continue; }
				array_push( $filelist , $ent );
			}
			closedir($dr);
		}
		#	/ ディレクトリオープン
		#--------------------------------------
		foreach( $filelist as $plugins_name ){
			array_push(
				$RTN ,
				array(
					'name'=>$plugins_name ,
					'base'=>true ,
					'package'=>@is_dir( $this->conf->path_lib_package.'/plugins/'.$plugins_name ) ,
					'project'=>@is_dir( $this->conf->path_lib_project.'/plugins/'.$plugins_name ) ,
				)
			);
		}

		return	$RTN;
	}


	#--------------------------------------
	#	見出しを表示
	function mk_hx( $title , $hx = 2 ){
		#	Pickles Framework 0.2.0 16:00 2007/11/20 追加
		if( is_null( $_SERVER['REMOTE_ADDR'] ) ){
			if( $hx == 2 ){
				return	'[[----- '.$title.' -----]]'."\n";
			}elseif( $hx == 3 ){
				return	'[** '.$title.' **]'."\n";
			}
			return	'[ '.$title.' ]'."\n";
		}
		return	'<h'.intval($hx).'>'.htmlspecialchars($title).'</h'.intval($hx).'>'."\n";
	}


	#--------------------------------------
	#	シンプルな表を作成する
	function mk_simpletable( $rows ){
		$RTN = '';
		if( is_null( $_SERVER['REMOTE_ADDR'] ) ){
			foreach( $rows as $key=>$val ){
				$RTN .= '    ** '.$key."\n";
				$RTN .= '        '.$val.''."\n";
			}
		}else{
			$RTN .= '<table class="deftable">'."\n";
			foreach( $rows as $key=>$val ){
				$RTN .= '	<tr>'."\n";
				$RTN .= '		<th>'.$key.'</th>'."\n";
				$RTN .= '		<td>'.$val.'</td>'."\n";
				$RTN .= '	</tr>'."\n";
			}
			$RTN .= '</table>'."\n";
		}
		return	$RTN;
	}

	#--------------------------------------
	#	OK/NGを表示
	function print_ok(){
		if( is_null( $_SERVER['REMOTE_ADDR'] ) ){
			return	'[ OK ]';
		}
		return	'[ <span class="ok">OK</span> ]';
	}
	function print_ng( $errorString = null ){
		if( is_null( $_SERVER['REMOTE_ADDR'] ) ){
			return	'[ NG ]';
		}
		return	'[ <span class="error">NG</span> ]';
	}

}



?>