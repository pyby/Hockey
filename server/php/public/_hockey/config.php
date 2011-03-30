<?php
	$session_id = session_id();
	if(empty($session_id))
		session_start();

    define('HOCKEY_INCLUDE_DIR', '../../includes/main.php');
?>
