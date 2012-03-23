<?php

## index.php
## 
##  Created by Andreas Linde on 8/17/10.
##             Stanley Rost on 8/17/10.
##  Copyright 2010 Andreas Linde. All rights reserved.
##
##  Permission is hereby granted, free of charge, to any person obtaining a copy
##  of this software and associated documentation files (the "Software"), to deal
##  in the Software without restriction, including without limitation the rights
##  to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
##  copies of the Software, and to permit persons to whom the Software is
##  furnished to do so, subject to the following conditions:
##
##  The above copyright notice and this permission notice shall be included in
##  all copies or substantial portions of the Software.
##
##  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
##  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
##  FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
##  AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
##  LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
##  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
##  THE SOFTWARE.

require('json.inc');
require('plist.inc');

define('CHUNK_SIZE', 1024*1024); // Size (in bytes) of tiles chunk

  // Read a file and display its content chunk by chunk
  function readfile_chunked($filename, $retbytes = TRUE) {
    $buffer = '';
    $cnt =0;
    // $handle = fopen($filename, 'rb');
    $handle = fopen($filename, 'rb');
    if ($handle === false) {
      return false;
    }
    while (!feof($handle)) {
      $buffer = fread($handle, CHUNK_SIZE);
      echo $buffer;
      ob_flush();
      flush();
      if ($retbytes) {
        $cnt += strlen($buffer);
      }
    }
    $status = fclose($handle);
    if ($retbytes && $status) {
      return $cnt; // return num. bytes delivered like readfile() does.
    }
    return $status;
}

function nl2br_skip_html($string)
{
	// remove any carriage returns (Windows)
	$string = str_replace("\r", '', $string);

	// replace any newlines that aren't preceded by a > with a <br />
	$string = preg_replace('/(?<!>)\n/', "<br />\n", $string);

	return $string;
}

class iOSUpdater
{
    // define URL type parameter values
    const TYPE_PROFILE = 'profile';
    const TYPE_APP     = 'app';
    const TYPE_IPA     = 'ipa';
    const TYPE_IMG     = 'img';
    
    // define keys for the returning json string
    const RETURN_RESULT   = 'result';
    const RETURN_NOTES    = 'notes';
    const RETURN_TITLE    = 'title';
    const RETURN_SUBTITLE = 'subtitle';
    const RETURN_PROXID = 'proxid';

    // define keys for the array to keep a list of available beta apps to be displayed in the web interface
    const INDEX_APP            = 'app';
    const INDEX_VERSION        = 'version';
    const INDEX_SHORT_VERSION  = 'shortVersion';
    const INDEX_SUBTITLE       = 'subtitle';
    const INDEX_DATE           = 'date';
    const INDEX_NOTES          = 'notes';
    const INDEX_PROFILE        = 'profile';
    const INDEX_PROFILE_UPDATE = 'profileupdate';
    const INDEX_DIR            = 'dir';
    const INDEX_IMAGE          = 'image';
    const INDEX_STATS          = 'stats';


    // define keys for the array to keep a list of devices installed this app

    const DEVICE_USER       = 'user';
    const DEVICE_PLATFORM   = 'platform';
    const DEVICE_OSVERSION  = 'osversion';
    const DEVICE_APPVERSION = 'appversion';
    const DEVICE_LASTCHECK  = 'lastcheck';

    protected $appDirectory;
    protected $folder;
    protected $json = array();
    public $proxid;
    public $applications = array();

    
    function __construct($dir) {
        
        date_default_timezone_set('UTC');
		
		$bundleidentifier = null;
		 
		if (isset($_GET['bundleidentifier'])) {
			$bundles = explode("/", urldecode($_GET['bundleidentifier']));
			if(count($bundles) == 2) {
				$this->folder = $bundles[0];
				$this->appDirectory = $dir . $this->folder . "/";
			}
			else {
				$this->appDirectory = $dir;
				$this->folder = null;
			}
			
			$bundleidentifier = $this->validateDir($bundles[count($bundles)-1]);
		}
		else {
			$this->appDirectory = $dir;
			$this->folder = null;
		}
		
		if (isset($_GET['proxid']))
			$this->proxid = $_GET['proxid'];
		elseif (!file_exists($dir."proxy.php") && (basename(dirname($dir)) != "stats"))
			$this->proxid = $this->getProxid();
			
		
		/*$this->appDirectory = $dir;

        $bundleidentifier = isset($_GET['bundleidentifier']) ?
            $this->validateDir($_GET['bundleidentifier']) : null;*/
		
        $type = isset($_GET['type']) ? $this->validateType($_GET['type']) : null;

        // if (!$bundleidentifier)
        // {
        //     $this->json = array(self::RETURN_RESULT => -1);
        //     return $this->sendJSONAndExit();
        // }
        
        if((file_exists($dir."proxy.php")) || ($this->appDirectory != $dir) || ($this->folder != null)) {
        	if(($bundleidentifier) && $this->checkProxid())
        			return $this->deliver($bundleidentifier, $type);
        		else {
        			header('HTTP/1.0 403 Forbidden');
					exit();
        		}
        }
        
        elseif ($bundleidentifier)
        {
            return $this->deliver($bundleidentifier, $type);
        }
        
        $this->show();
    }
    
    // create the proxid and save it in the proxylist.txt file
    protected function getProxid()
    {
    	$session_id = session_id();
    	//$fivemin = floor(date("i")/30); // (was 5 min befor)
    	//$proxid = sha1(session_id().$this->appDirectory.date("Y/m/d-H:").$fivemin);
    	$proxid = sha1(session_id().$this->appDirectory.date("Y/m/d-H:00")); // hardcore for hour
    	
    	$ideviceid = isset($_GET['ideviceid']) ? $_GET['ideviceid'] : null;
    	if (!ereg("^[0-9a-fA-F]{40}$",$ideviceid)) $ideviceid = null; // 40 hexadecimal caracters "^[:xdigit:]{40}$"
    	
		if(!empty($session_id)) {
			$folder = basename($this->appDirectory);
			//$ipAddress = $_SERVER['REMOTE_ADDR'];
            $thisproxid = $proxid.";".$folder.";".time().";".$ideviceid;
            $content =  "";

            $filename = dirname(HOCKEY_INCLUDE_DIR) .'/proxylist.txt';
            $content = @file_get_contents($filename);
            
            $lines = explode("\n", $content);
            $content = "";
            $found = false;
            
            foreach ($lines as $i => $line) :
                if ($line == "") continue;
                $aproxid = explode( ";", $line);

                $newline = $line;
                
                if (count($aproxid) == 4) {
                    // is this the same proxid?
                    if ($aproxid[0] == $proxid) {
                        $newline = $thisproxid;
                        $found = true;
                        $content .= $newline."\n";
                    }
                    elseif (($aproxid[3] != "") && ($aproxid[3] == $ideviceid)) {
                        $newline = $thisproxid;
                        $found = true;
                        $content .= $newline."\n";
                    }
                    elseif (($aproxid[3] != "") && ((time() - $aproxid[2]) < 5400)) // 90 min for existing link with an udid (ideviceid) - (was 30 min befor)
                    {
                    	$content .= $newline."\n";
                    }
                    elseif ((time() - $aproxid[2]) < 3600) // 60 min for existing link - (was 5 min befor)
                    {
                    	$content .= $newline."\n";
                    }
                }
            endforeach;
            
            if (!$found) {
                $content .= $thisproxid;
            }
            
            // write back the proxylist
            @file_put_contents($filename, $content);
            
            $this->removeProxIpa();		
			
			return $proxid;
		}
		else
			return NULL;
    }
    
    // check the proxid if it can access or not
    protected function checkProxid()
    {
    	$proxid = $this->proxid;
    	$appFolder = basename($this->appDirectory);
		
		$ideviceid = isset($_GET['ideviceid']) ? $_GET['ideviceid'] : null;
		
		if($appFolder == $this->folder) {	
            $content =  "";

            $filename = dirname(HOCKEY_INCLUDE_DIR) .'/proxylist.txt';
            $content = @file_get_contents($filename);
            
            $lines = explode("\n", $content);
            $content = "";
            $found = false;
            foreach ($lines as $i => $line) :
                if ($line == "") continue;
                $aproxid = explode( ";", $line);
                
                if (count($aproxid) == 4) {
                    // is this the same proxid, same folder
                    if (($aproxid[0] == $proxid) && ($aproxid[1] == $appFolder)) {
                    	if (($aproxid[3] != nil) && ($aproxid[3] == $ideviceid) && ((time() - $aproxid[2]) < 5400)) // 90 min for existing link with an udid (ideviceid) - (was 30 min befor)
                    		$found = true;
                    	elseif ((time() - $aproxid[2]) < 3600) // 60 min for existing link - (was 5 min befor)
                        	$found = true;
                    }
                }
            endforeach;
            
            if ($found)
                return true;
		}
		
		return false;
    }
    
    // remove old proxy ipa files
    protected function removeProxIpa()
    {
		$proxidlist = array();
		$filename = dirname(HOCKEY_INCLUDE_DIR) .'/proxylist.txt';
		$content = @file_get_contents($filename);
            
        $lines = explode("\n", $content);
        $content = "";
        $found = false;
        foreach ($lines as $i => $line) {
            if ($line == "") continue;
            $aproxid = explode( ";", $line);
            
            if (count($aproxid) == 4) {
            	$proxidlist[] = $aproxid[0];
            }
        }
        
        $files = scandir(dirname($this->appDirectory) . '/proxy/');
        
		foreach ($files as $file)
		{
			$fileName = explode("-", $file);
			if(count($fileName) == 2)
			{
				$fileid = $fileName[0];
				if (!in_array($fileid, $proxidlist))
				{
					unlink(dirname($this->appDirectory) . '/proxy/' . $file);
				}	
			}
		}   
    }
    
    protected function array_orderby()
    {
        $args = func_get_args();
        $data = array_shift($args);
        foreach ($args as $n => $field) {
            if (is_string($field)) {
                $tmp = array();
                foreach ($data as $key => $row)
                    $tmp[$key] = $row[$field];
                $args[$n] = $tmp;
                }
        }
        $args[] = &$data;
        @call_user_func_array('array_multisort', $args);
        return array_pop($args);
    }


    protected function validateDir($dir)
    {
        // do not allow .. or / in the name and check if that path actually exists
        if (
            $dir &&
            !preg_match('#(/|\.\.)#u', $dir) &&
            file_exists($this->appDirectory.$dir))
        {
            return $dir;
        }
        return null;
    }
    
    protected function validateType($type)
    {
        if (in_array($type, array(self::TYPE_PROFILE, self::TYPE_APP, self::TYPE_IPA, self::TYPE_IMG)))
        {
            return $type;
        }
        return null;
    }
    
    // map a device UDID into a username
    protected function mapUser($user, $userlist)
    {
        $username = $user;
        $lines = explode("\n", $userlist);

        foreach ($lines as $i => $line) :
            if ($line == "") continue;
            
            $userelement = explode(";", $line);

            if (count($userelement) == 2) {
                if ($userelement[0] == $user) {
                    $username = $userelement[1];
                    break;
                }
            }
        endforeach;

        return $username;
    }
    
    // map a device code into readable name
    protected function mapPlatform($device)
    {
        $platform = $device;
        
        switch ($device) {
            case "i386":
                $platform = "iPhone Simulator";
                break;
            case "iPhone1,1":
                $platform = "iPhone";
                break;
            case "iPhone1,2":
                $platform = "iPhone 3G";
                break;
            case "iPhone2,1":
                $platform = "iPhone 3GS";
                break;
            case "iPhone3,1":
                $platform = "iPhone 4";
                break;
            case "iPad1,1":
                $platform = "iPad";
                break;
            case "iPad2,1":
                $platform = "iPad 2 Wifi";
                break;
            case "iPad2,2":
                $platform = "iPad 2 GSM";
                break;
            case "iPad2,3":
                $platform = "iPad 2 CDMA";
                break;
            case "iPod1,1":
                $platform = "iPod Touch";
                break;
            case "iPod2,1":
                $platform = "iPod Touch 2nd Gen";
                break;
            case "iPod3,1":
                $platform = "iPod Touch 3rd Gen";
                break;
            case "iPod4,1":
                $platform = "iPod Touch 4th Gen";
                break;
        }
	
        return $platform;
    }
    
    protected function deliver($bundleidentifier, $type)
    {
        $plist               = @array_shift(glob($this->appDirectory.$bundleidentifier . '/*.plist'));
        $ipa                 = @array_shift(glob($this->appDirectory.$bundleidentifier . '/*.ipa'));
        $provisioningProfile = @array_shift(glob($this->appDirectory.$bundleidentifier . '/*.mobileprovision'));
        $note                = @array_shift(glob($this->appDirectory.$bundleidentifier . '/*.html'));
        $image               = @array_shift(glob($this->appDirectory.$bundleidentifier . '/*.png'));
        
        // did we get any user data?
        $udid = isset($_GET['udid']) ? $_GET['udid'] : null;
        $appversion = isset($_GET['version']) ? $_GET['version'] : "";
        $osversion = isset($_GET['ios']) ? $_GET['ios'] : "";
        $platform = isset($_GET['platform']) ? $_GET['platform'] : "";
        
        if ($udid) {
            $thisdevice = $udid.";;".$platform.";;".$osversion.";;".$appversion.";;".date("m/d/Y H:i:s");
            $content =  "";

            $filename = $this->appDirectory."stats/".$bundleidentifier;

            $content = @file_get_contents($filename);
            
            $lines = explode("\n", $content);
            $content = "";
            $found = false;
            foreach ($lines as $i => $line) :
                if ($line == "") continue;
                $device = explode( ";;", $line);

                $newline = $line;
                
                if (count($device) > 0) {
                    // is this the same device?
                    if ($device[0] == $udid) {
                        $newline = $thisdevice;
                        $found = true;
                    }
                }
                
                $content .= $newline."\n";
            endforeach;
            
            if (!$found) {
                $content .= $thisdevice;
            }
            
            // write back the updated stats
            @file_put_contents($filename, $content);
        }

        // notes file is optional, other files are required
        if (!$plist || !$ipa)
        {
            $this->json = array(self::RETURN_RESULT => -1);
            return $this->sendJSONAndExit();
        }
        
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

        if (!$type) {
            // check for available updates for the given bundleidentifier
            // and return a JSON string with the result values

            // parse the plist file
            $plistDocument = new DOMDocument();
            $plistDocument->load($plist);
            $parsed_plist = parsePlist($plistDocument);

            // get the bundle_version which we treat as build number
            $latestversion = $parsed_plist['items'][0]['metadata']['bundle-version'];

            // add the latest release notes if available
            if ($note && file_exists($note)) {
                $this->json[self::RETURN_NOTES] = nl2br_skip_html(file_get_contents($note));
            }
			
            $this->json[self::RETURN_TITLE]   = $parsed_plist['items'][0]['metadata']['title'];

            if (array_key_exists('subtitle', $parsed_plist['items'][0]['metadata']))
	            $this->json[self::RETURN_SUBTITLE]   = $parsed_plist['items'][0]['metadata']['subtitle'];
    
            $this->json[self::RETURN_RESULT]  = $latestversion;
            
            $this->json[self::RETURN_PROXID]  = $this->proxid;

            return $this->sendJSONAndExit();

        } else if ($type == self::TYPE_PROFILE) {

            // send latest profile for the given bundleidentifier
            header('Content-Disposition: attachment; filename=' . urlencode(basename($provisioningProfile)));
            header('Content-Type: application/octet-stream;');
            header('Content-Transfer-Encoding: binary');
            header('Content-Length: '.filesize($provisioningProfile).";\n");
            readfile($provisioningProfile);

        } else if ($type == self::TYPE_APP) {
			
			$bundles = explode("/", urldecode($_GET['bundleidentifier']));
			
			$subPath = null;
			$ipa_url = null;
			if(count($bundles) == 2) {
				$proxyFileName = $this->proxid . '-' . md5($udid) . md5($ipa);
				$proxyFilePath = dirname($this->appDirectory) . '/proxy/' . $proxyFileName;
				if (!(file_exists($proxyFilePath) || is_link($proxyFilePath)))
					link($ipa, $proxyFilePath);
				$subPath = $bundles[0] . "/";
				//$ipa_url = dirname(dirname($protocol."://".$_SERVER['SERVER_NAME'].$port.$_SERVER['REQUEST_URI'])) . '/proxy.php?type=' . self::TYPE_IPA . '&amp;bundleidentifier=' . $_GET['bundleidentifier'] . '&amp;proxid=' . $this->proxid; // url without proxy files	
				$ipa_url = dirname(dirname($protocol."://".$_SERVER['SERVER_NAME'].$port.$_SERVER['REQUEST_URI'])) . '/proxy/' . urlencode($proxyFileName);	// url with proxy files	

			}
			else {
			
            // send XML with url to app binary file
            $ipa_url =
            	dirname($protocol."://".$_SERVER['SERVER_NAME'].$port.$_SERVER['REQUEST_URI']) . 
                '/index.php?type=' . self::TYPE_IPA . '&amp;bundleidentifier=' . $bundleidentifier;
			}
            $plist_content = file_get_contents($plist);
            $plist_content = str_replace('__URL__', $ipa_url, $plist_content);
            if ($image) {
            	$image_url = null;
            	if(count($bundles) == 2)
            		$image_url = dirname(dirname($protocol."://".$_SERVER['SERVER_NAME'].$port.$_SERVER['REQUEST_URI'])) . '/proxy.php?type=' . self::TYPE_IMG . '&amp;bundleidentifier=' . $_GET['bundleidentifier'] . '&amp;proxid=' . $this->proxid;
            	else
                $image_url =
                    dirname($protocol."://".$_SERVER['SERVER_NAME'].$port.$_SERVER['REQUEST_URI']) . '/' . $subPath .
                    $bundleidentifier . '/' . basename($image);
                $imagedict = "<dict><key>kind</key><string>display-image</string><key>needs-shine</key><false/><key>url</key><string>".$image_url."</string></dict></array>";
                $insertpos = strpos($plist_content, '</array>');
                $plist_content = substr_replace($plist_content, $imagedict, $insertpos, 8);
            }
            header('content-type: application/xml');
            echo $plist_content;

        } else if ($type == self::TYPE_IPA) {
            // send latest ipa for the given bundleidentifier
            header('Content-Disposition: attachment; filename=' . urlencode(basename($ipa)));
            header('Content-Type: application/octet-stream;');
            header('Content-Transfer-Encoding: binary');
            header('Content-Length: '.filesize($ipa).";\n");
            readfile_chunked($ipa);
        }
        else if ($type == self::TYPE_IMG) {
 
            // send latest profile for the given bundleidentifier
            $filename = $appDirectory  . $image;
            header('Content-Disposition: attachment; filename=' . urlencode(basename($filename)));
            header('Content-Type: application/octet-stream;');
            header('Content-Transfer-Encoding: binary');
            header('Content-Length: '.filesize($filename).";\n");
            readfile($filename);
        }

        exit();
    }
    
    protected function sendJSONAndExit()
    {
        @ob_end_clean();
        header('Content-type: application/json');
        print json_encode($this->json);
        exit();
    }
    
    protected function show()
    {
        // first get all the subdirectories, which do not have a file named "private" present
        if ($handle = opendir($this->appDirectory)) {
            while (($file = readdir($handle)) !== false) {
                if (in_array($file, array('.', '..')) || !is_dir($this->appDirectory . $file) || glob($this->appDirectory . $file . '/private')) {
                    // skip if not a directory or has `private` file
                    continue;
                }

                // now check if this directory has the 3 mandatory files
                $ipa                 = @array_shift(glob($this->appDirectory.$file . '/*.ipa'));
                $provisioningProfile = @array_shift(glob($this->appDirectory.$file . '/*.mobileprovision'));
                $plist               = @array_shift(glob($this->appDirectory.$file . '/*.plist'));
                $note                = @array_shift(glob($this->appDirectory.$file . '/*.html'));
                $image               = @array_shift(glob($this->appDirectory.$file . '/*.png'));

                if (!$ipa || !$plist) {
                    continue;
                }

                $plistDocument = new DOMDocument();
                $plistDocument->load($plist);
                $parsed_plist = parsePlist($plistDocument);

                $newApp = array();

                // now get the application name from the plist
                $newApp[self::INDEX_APP]            = $parsed_plist['items'][0]['metadata']['title'];
                if (array_key_exists('subtitle', $parsed_plist['items'][0]['metadata']))
                  $newApp[self::INDEX_SUBTITLE]       = $parsed_plist['items'][0]['metadata']['subtitle'];
                $newApp[self::INDEX_VERSION]        = $parsed_plist['items'][0]['metadata']['bundle-version'];
                
                $versionComponents = explode("+", $newApp[self::INDEX_VERSION]);
                if ((count($versionComponents) > 1) &&
        			(end($versionComponents) != "dev") && (end($versionComponents) != "test"))
        			$newApp[self::INDEX_SHORT_VERSION] = end($versionComponents);
        		else
                	$newApp[self::INDEX_SHORT_VERSION]  = $newApp[self::INDEX_VERSION];
                	
                $newApp[self::INDEX_DATE]           = filemtime($ipa);
                $newApp[self::INDEX_DIR]            = $file;
                $newApp[self::INDEX_IMAGE]          = substr($image, strpos($image, $file));
                $newApp[self::INDEX_NOTES]          = $note ? nl2br_skip_html(file_get_contents($note)) : '';
                $newApp[self::INDEX_STATS]          = array();

                if ($provisioningProfile) {
                    $newApp[self::INDEX_PROFILE]        = $provisioningProfile;
                    $newApp[self::INDEX_PROFILE_UPDATE] = filemtime($provisioningProfile);
                }
                
                // now get the current user statistics
                $userlist =  "";

                $filename = $this->appDirectory."stats/".$file;
                $userlistfilename = $this->appDirectory."stats/userlist.txt";
            
                if (file_exists($filename)) {
                    $userlist = @file_get_contents($userlistfilename);
                    
                    $content = file_get_contents($filename);
                    $lines = explode("\n", $content);

                    foreach ($lines as $i => $line) :
                        if ($line == "") continue;
                        
                        $device = explode(";;", $line);
                        
                        $newdevice = array();

                        $newdevice[self::DEVICE_USER] = $this->mapUser($device[0], $userlist);
                        $newdevice[self::DEVICE_PLATFORM] = $this->mapPlatform($device[1]);
                        $newdevice[self::DEVICE_OSVERSION] = $device[2];
                        $newdevice[self::DEVICE_APPVERSION] = $device[3];
                        $newdevice[self::DEVICE_LASTCHECK] = $device[4];
                        
                        $newApp[self::INDEX_STATS][] = $newdevice;
                    endforeach;
                    
                    // sort by app version
                    $newApp[self::INDEX_STATS] = self::array_orderby($newApp[self::INDEX_STATS], self::DEVICE_APPVERSION, SORT_DESC, self::DEVICE_OSVERSION, SORT_DESC, self::DEVICE_PLATFORM, SORT_ASC, self::DEVICE_LASTCHECK, SORT_DESC);
                }
                
                // add it to the array
                $this->applications[] = $newApp;
            }
            closedir($handle);
        }
    }
}


?>