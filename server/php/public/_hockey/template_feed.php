<?php
	if(!$phpFile) // Must have the file path
		die();
		
	$ios = new iOSUpdater(dirname($phpFile).DIRECTORY_SEPARATOR);
    
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
    $baseURL = str_replace("/feed.php", "/", $baseURL);

    echo '<?xml version="1.0" encoding="utf-8"?>';
?>

<feed xmlns="http://www.w3.org/2005/Atom">
  <title><?php echo $_SERVER['SERVER_NAME'] ?> iOS Apps Updates</title>
  <subtitle></subtitle>
  <link rel="alternate" type="text/html" href="<?php echo $baseURL ?>"/>
  <link rel="self" type="application/atom+xml" href="<?php echo $baseURL ?>/feed.php"/>
  <id><?php echo $baseURL ?></id>
<?php 
    foreach ($ios->applications as $i => $app) :
?>

  <entry>
    <title><?php echo $app[iOSUpdater::INDEX_APP] ?> V<?php 
    if ($app[iOSUpdater::INDEX_SUBTITLE]) {
      echo $app[iOSUpdater::INDEX_SUBTITLE]." (".$app[iOSUpdater::INDEX_SHORT_VERSION].")";
    } else {
      echo $app[iOSUpdater::INDEX_SHORT_VERSION];
    } ?></title>
    <id><?php echo $app[iOSUpdater::INDEX_APP].$app[iOSUpdater::INDEX_SUBTITLE].$app[iOSUpdater::INDEX_SHORT_VERSION] ?></id>
    <link rel="alternate" type="text/html" href="<?php echo $baseURL ?>"/>
    <published><?php echo date('Y-m-d\TH:i:s\Z', $app[iOSUpdater::INDEX_DATE]) ?></published>
    <updated><?php echo date('Y-m-d\TH:i:s\Z', $app[iOSUpdater::INDEX_DATE]) ?></updated>
    <content type="html" xml:base="http://<?php echo $_SERVER['SERVER_NAME'] ?>/" xml:lang="en"><![CDATA[
    <?php if ($app[iOSUpdater::INDEX_IMAGE]) { ?>
        <p><img src="<?php echo $baseURL.$app[iOSUpdater::INDEX_IMAGE] ?>"></p>
    <?php } ?>
    <p><b>Application:</b> <?php echo $app[iOSUpdater::INDEX_APP] ?></p>
    <?php if ($app[iOSUpdater::INDEX_SUBTITLE]) { ?>
      <p><b>Version:</b> <?php echo $app[iOSUpdater::INDEX_SUBTITLE] ?> (<?php echo $app[iOSUpdater::INDEX_SHORT_VERSION] ?>)</p>
    <?php } else { ?>
      <p><b>Version:</b> <?php echo $app[iOSUpdater::INDEX_SHORT_VERSION] ?></p>
    <?php } ?>
    <p><b>Released:</b> <?php echo date('m/d/Y H:i:s', $app[iOSUpdater::INDEX_DATE]) ?></p>
    <?php if ($app[iOSUpdater::INDEX_NOTES]) : ?>
        <p><b>What's New:</b><br/><?php echo $app[iOSUpdater::INDEX_NOTES] ?></p>
    <?php endif ?>]]></content>
  </entry>
<?php 
    endforeach;
?>
</feed>
