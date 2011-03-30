<?php
    ddefine('HOCKEY_INCLUDE_DIR', '../includes/main.php');
    require(constant('HOCKEY_INCLUDE_DIR'));
    
    $ios = new iOSUpdater(dirname(__FILE__).DIRECTORY_SEPARATOR);
    
    $protocol = 'http';
	$port = '';
	switch ($_SERVER["SERVER_PORT"]) {
	case '80':
			$protocol = 'http';
			$port = '';
			break;
		case '443':
			$protocol = 'https';
			$port = '';
			break;
		default:
			$protocol = strtolower(substr($_SERVER["SERVER_PROTOCOL"],0,5))=='https'?'https':'http';
			$port = $_SERVER["SERVER_PORT"]=='80'?'':':'.$_SERVER["SERVER_PORT"];
	}
	
	$baseURL = $protocol."://".$_SERVER['SERVER_NAME'].$port.$_SERVER['REQUEST_URI'];
?>