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

if(!empty($_POST['store']))
{
	$key = $_POST['store'];
	
	$folder = dirname(__FILE__) . "/" . $key;
    if(file_exists($folder))
    {
    	header('Location: '.$key); 
    	die();
    }
    
    $noStore = true;
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
			<h4>My store :
			<form method=post action="index.php">
			<input type="text" name="store" size=40 />
			<br />
			<input type="submit" value="Enter">
			</from>
			</h4>
<?php
if ($noStore)
	echo "<h5>Store doesn't exist</h5>";
?>
		</center>
	</body>
</html>