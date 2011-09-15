<?php

require_once( $conf->path_lib_package.'/resources/contentsoperator/html.php' );
class project_resources_contentsoperator_html extends package_resources_contentsoperator_html{

	var $pattern_html = '(?:px\:)[a-zA-Z][a-z0-9A-Z_-]*';//←「px:」をつけないとパースしない設定


}

?>