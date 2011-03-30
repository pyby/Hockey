<?php

if(!empty($_GET))
{
	foreach ($_GET as $key => $value)
	{
		$folder = dirname(__FILE__) . "/" . $key;
    	if(file_exists($folder))
    	{
    		header('Location: '.$key); 
    		die();
    	}
	}
}

?>

<html>
	<head>
		<title>iOS Store</title>
		<link rel="apple-touch-icon" href="apple-touch-icon.png" />
	</head>
	<body>
		<center>
			<h1>iOS Store</h1>
			<h3>your private AppStore</h3>
			<h5><a href="https://github.com/pyby/Hockey">An open source project on GitHub</a></h5>
		</center>
	</body>
</html>