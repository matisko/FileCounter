<?php

// configure these for your web server:

date_default_timezone_set('America/Montreal'); 

$DataDir = "/home/mywebsite/public_html/counters/data/";     // Keep the trailing slash


/*  fileCounter  by Michael Matisko   January, 2016  
    -----------------------------------------------
 
 A webpage counter and log using only readable files, no database required.  It's efficient and simple.


 Settings to configure, at the top of this file:

  - date_default_timezone_set('????')   the local time zone
  - $DataDir                            the folder for the log files, can be relative or absolute


 Parameters:
 
   fileCounter.php( bool PrintOnPage,  bool RecordInAll,  bool RecordInThisPage,  string RecordInPredefinedPage );

  - The parameters are optional.
  - The default behaviour (i.e. without parameters) is the count is not printed, the page visit is recorded 
    in the All log, and the page log, and the page log filename is based on the page name.

  -  bool printOnPage:               when true, the count for the visited page is printed to the page
  -  bool recordInAll:               when true, the All log is updated
  -  bool recordInThisPage:          when true, the log for the visited page is updated
  -  string useThisLogFile:          if included, instead of updating the log corresponding to the page name, a log with
                                     this name is updated  (RecordInThisPage must be true, otherwise this is ignored)

  Blocking bots:

  - Some spam bots (or whatever they're called) try to access outdated pages or non-existent admin pages.  I want to record those 
    hits but not in the normal log, so my 404 page calls the counter like this:  fileCounter.php( false, false, true, "ThisPageDoesNotExist" );
	You could also have several pages logged in a common file by setting useThisLogFile to the same in each of them. 

  - Some spam bots visit so regularly, you get to know their IP address range.  A simple blocking feature logs these known 
    culprits in a separate log file and redirects the visitor to a blank page.  If you want to prevent the bot from seeing 
	your content (and reduce your server bandwith) include the counter near the top of your pages.

  - You could alter another check that would simply exit the counter from some IP addresses, for example if you don't want to 
	log your own visits.


 Files and folders:

  - fileCounter.php  The main file, with all the code.  Include in the pages you want to log.  This is the only necessary file
                     to capture the data, the others improve the display.

  - index.php        Calls the function in the main file that lists of the log files.  Ooh, nice formatting!

  - fileCounter.css  Style sheet for the logs

  - Each log is made of three files.  For a page called FamilyPics.php they are:
    
	counter_FamilyPics_.php				The parent file (a.k.a. skeleton file)
    counter_FamilyPics_.total			Holds only a number, the total visits to the page
    counter_FamilyPics_.details.tsv  

  - all.php			Collects stats for all pages  (along with all.total, all.details.tsv)

  - The data and program files can be in the same or different folders.  $DataDir must indicate the data folder.


  What this *doesn't* do:  File Locking

   - file locking:  this counter is for simple, low traffic websites. If simultaneous writes to a log happen 
     frequently, you should add file locking.  Better yet, get a counter that uses a database.
*/


function get_var( $name, $default ) 
{
    if($var = getenv($name)) { return $var; }
	else					 { return $default; }
}



function UpdateCounter( $printOnPage = false,  $recordInAll = true,  $recordInThisPage = true,  $useThisLogFile = "" )
{
	$remoteIP = "";

	if(!$remoteIP = get_var("HTTP_X_FORWARDED_FOR",false))
	{
	   if(!$remoteIP = get_var("REMOTE_HOST", false))  { $remoteIP = get_var( "REMOTE_ADDR",  "-"); }
	}
	

	// detect blocked visitors 
	if(substr($remoteIP, 0, 7) == "000.00.")	{ $blocked = true;  $useThisLogFile = "counterBlocked";  $recordInAll = false;  $recordInThisPage = true;  }
	

	if(!$remote_host = get_var("REMOTE_HOST", false)) { $remote_host = get_var( "REMOTE_ADDR",  "-"); }  // sometimes identical to $remoteIP

	if($remote_host == "-") { $remote_host = gethostbyaddr( $remoteIP ); }

	if($remote_host == $remoteIP) { $remote_host = "-"; }   // do not record the same info twice


	$date           = date("d/M/Y H:i:s");  
	$referer        = get_var( "HTTP_REFERER",     "#" );
	$user_agent     = get_var( "HTTP_USER_AGENT",  "-" );
	$remote_user    = get_var( "REMOTE_USER",      "-" );
	$remote_ident   = get_var( "REMOTE_IDENT",     "-" );
	$request_method = get_var( "REQUEST_METHOD", "GET" );
	$request_uri    = get_var( "REQUEST_URI",      "-" );
	$server_name    = get_var( "SERVER_NAME",      "-" );
  //$server_port    = get_var( "SERVER_PORT", 80);  

	// $remote_host $remote_ident $remote_user 
	// $request_method http://$server_name$server_port$request_uri
    
	
	$TSVLog  =           $date 
				. "\t" . time()  // seconds since Jan 1, 1970, Greenwich Time
	            . "\t" . $request_uri
		        . "\t" . $referer		
				. "\t" . $user_agent 
				. "\t" . $server_name 
				. "\t" . $remoteIP		
				. "\t" . $remote_ident
				. "\t" . $remote_host
				. "\t" . $remote_user;	

	if ($recordInThisPage)
	{
		if ($useThisLogFile == "")  {$thelogfile  =  GetLogFileRootName(); }
		else                        {$thelogfile  =  $useThisLogFile; }

		$skeletonfile  =  $GLOBALS["DataDir"] . $thelogfile . ".php";

		if  (!file_exists( $skeletonfile ))
		{
			$thefile  =  fopen( $skeletonfile, 'w' );   
			if (!$thefile) {echo "<p>couldn't create file: ".$skeletonfile; return;}   // create the counter skeleton file
			
			fwrite( $thefile, "<?php  require '". __FILE__ ."';  ReadCounter();  ?>" );  // the skeleton is a single line :-}
			
			fclose( $thefile ); 
		}


		WriteLog(    $GLOBALS["DataDir"] . $thelogfile. ".details.tsv",  $TSVLog );

		UpdateTotal( $GLOBALS["DataDir"] . $thelogfile. ".total",  $printOnPage );   // print the total
	}

	if ($recordInAll)
	{
		WriteLog(    $GLOBALS["DataDir"] . "all.details.tsv",  $TSVLog );

		UpdateTotal( $GLOBALS["DataDir"] . "all.total" ); 
	}


	// redirect blocked visitors
	if($blocked)	{ echo '<script type="text/javascript"> window.location = "/_" </script>';   die(); }

}



function GetLogFileRootName()
{
   $countpage = getenv("REQUEST_URI");
   $countpage = preg_replace("/(\?.*)/", "", $countpage);
   $countpage = preg_replace("/[^\w]/", "_", $countpage);

   return ( "counter" . $countpage );
}



function WriteLog( $logfile, $data )
{
	$thefile  =  fopen ( $logfile, 'a' );  // creates the file if it doesn't exist, otherwise it appends

	if (!$thefile) {echo "<p>couldn't open file: ".$logfile."<p>"; return;}  // unable to open the file

	fwrite( $thefile, $data."\r\n\r\n" );  // add two newlines after the data.  The second newline improves readability and adds only two chars per line.

	fclose( $thefile );

	return;
}


// Reads the given file, which contains only one number, then rewrites the file with the number incremented.
// If the second parm is True, the new value is printed to the webpage.
//
function UpdateTotal( $totalfile,  $printTotal = false )
{
	$number = "0";  // default

	if  (file_exists($totalfile)) 
	{
		$thefile  =  fopen ( $totalfile, 'r' );  // open for reading

		if (!$thefile) {return;}   // unable to open
	
		$number = fgets($thefile);  // read the first (and only) line
		
		fclose($thefile); 
	}

	
	$thefile  =  fopen ( $totalfile, 'w' );  // create/overwrite the file 

	if (!$thefile) {return;}  // unable to open the file

	$newnum  =  $number + 1;

	fwrite( $thefile, $newnum ); 

	fclose( $thefile );

	if ($printTotal)  { echo $newnum; }
}


function ListLogFiles()
{
	$dir  =  $GLOBALS["DataDir"];

    print("<html> <head>   <meta http-equiv='Content-Type' content='text/html; charset=windows-1252'>  
	 <link href='fileCounter.css' rel='stylesheet'> <title> fileCounter </title> </head>
	 <body>
	 <h1> " . $_SERVER['HTTP_HOST'] . " &nbsp;&nbsp;&nbsp;&nbsp; fileCounter 1.0 </h1> ");
	//<p> Data folder:  $dir ");

    echo " Current server date: ". strftime("%Y %b %d %H:%M");
    echo "<br> PHP version: ". phpversion();
    
	print("<p> <table cellspacing='0' cellpadding='3' border='0' class='recordtbl'> \n\n");	

    chdir( $dir );    // Change to directory

    $handle = @opendir( $dir )  or  die( "Directory '$dir' not found." );   

    // get the desired files from the data folder
    while( $entry = readdir($handle) )
    {
		if (false  == strpos( $entry, ".php" ))  continue;   // exclude files without .php in the name
		if ($entry == "index.php")               continue; 
		if ($entry == "fileCounter.php")         continue; 

		$logfiles[]  =  $entry;  // add this
    }

    sort( $logfiles );   

    foreach( $logfiles as &$thisfile )    // Print the files
    {
		$temp = "http://".$_SERVER['HTTP_HOST'].str_replace($_SERVER['DOCUMENT_ROOT'], '', $dir.$thisfile);
		
		 // get the date and size from the .details file, the PHP doesn't change
		$base  =  basename( $thisfile, ".php" );						// strip folder and .php 
		$dets  =  str_replace( ".php",  ".details.tsv",  $thisfile );   // replace filetype

        echo "<tr class='even'> <td> <a href='$temp'> $base </a> </td>"  
				
		 . " <td style='text-align:right'>" . GetFileSizeInKB( $dets ) . " </td>"

		 . " <td style='text-align:right; font-size:70%;'> &nbsp;&nbsp;&nbsp;&nbsp;" .  date("Y/M/d H:i", filemtime( $dets )) . " </td> </tr> \n";
    }

    print( "</table> 
	 <p> fileCounter 1.0 by Michael Matisko in Toronto, Canada.  Free for personal use.  It would be luverly if this helps your website!
	 </body>
	 </html>" );

    closedir( $handle );    // Close directory
}



function GetFileSizeInKB( $filename )  // always return in KB
{
	$file_size = filesize( $filename );
	
	$file_size = round( $file_size / 1024 * 100 );

	while (strlen($file_size) < 3)   { $file_size = "0" . $file_size; }  // prepend zeroes as necessary
	
	$format_size = substr($file_size, 0, strlen($file_size)-2 ) . "." . substr($file_size, strlen($file_size)-2, 1) . " KB";  // insert decimal place
	
	return $format_size;
}



function ReadCounter()
{

 $logfile  =  preg_replace( '/\.php$/', '',  $_SERVER["SCRIPT_FILENAME"] );  // full path and rootname of the current page, the suffix is stripped
 
 echo '<html>
  <head>
  <meta http-equiv="Content-Type" content="text/html; charset=windows-1252">
  <link href="phpcounter.css" rel="stylesheet">
  <title> fileCounter '. basename( $logfile ) .' </title>
  </head>
  <body>
  <big><big>
  <b> ' . basename( $logfile ) .'  </b>	           &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;  Total hits: ' . file_get_contents( $logfile . ".total") 
  . '<p></big>    <a href="."> Return to the Index </a>  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';

  $currentURL  =  $_SERVER["REQUEST_URI"];

  if (!strpos($currentURL, "?"))  { $currentURL .= "?"; }  // add question mark if absent
  
  $URLOutline  =  $currentURL;   
  $URLTruncate =  $currentURL;

  if ( strpos($currentURL,  "outline=no"  ))  { $URLOutline  = str_replace("outline=no",  "outline=yes", $currentURL);  $linkOutline = "With outlining";  }
  if ( strpos($currentURL,  "outline=yes" ))  { $URLOutline  = str_replace("outline=yes", "outline=no",  $currentURL);  $linkOutline =   "No outlining";  }  

  // note the following adds '&' at the end 
  if (!strpos($currentURL,  "outline=" ))     { $URLOutline .= "outline=no&";  $linkOutline = "No outlining";  }  // add outline query string if absent


  if ( strpos($currentURL, "maxchars=400"))   { $URLTruncate = str_replace("maxchars=400", "maxchars=50",  $currentURL);   $linkTruncate = "Shorten long values";}
  if ( strpos($currentURL, "maxchars=50" ))   { $URLTruncate = str_replace("maxchars=50",  "maxchars=400", $currentURL);   $linkTruncate = "Long values intact";}

  if (!strpos($currentURL, "maxchars=" ))     { $URLTruncate .= "maxchars=400&";  $linkTruncate = "Long values intact";  }  // add truncate query string if absent
  
 echo "<a href='$URLTruncate '>$linkTruncate</a> 	&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
 echo "<a href='$URLOutline  '>$linkOutline</a><br>";
  
 echo " </big> <p> \n\n"; 

 $max     = htmlspecialchars( $_GET["maxchars"] );
 $outline = htmlspecialchars( $_GET["outline"] );

 if (!$max    )  $max     = 70;  // default truncation
 if (!$outline)  $outline =  1;  // default outlining is on

 // create the table header

 echo "<table width='100%' cellspacing='0' cellpadding='3' border='0' class='recordtbl'>
  <tr  style='outline: thin solid'>
	<td class='tblHeading'> Date </td>
	<td class='tblHeading'> </td>
	<td class='tblHeading'> Page </td>
	<td class='tblHeading'> HTTP Referer </td>
	<td class='tblHeading'> Remote IP </td>
	<td class='tblHeading'> User Agent </td>
	<td class='tblHeading'> Server Name </td>
  </tr> \n";
  
	// The current server doesn't provide proper values for these, hence they are always blank, so don't print
	// Remote Ident,  Remote Host, Remote User 

 // create the table rows from the details file

 $latestAtBottom = file( $logfile . '.details.tsv' );  // read entire file into array, one line per element

 $latestAtTop    = array_reverse( $latestAtBottom );  // reverse the lines, so the latest log line is now first in the array

 $prev = array();  $tt = "~";   // for the outlining

 foreach ($latestAtTop as &$line) 
 { 
   $line = trim( $line ); 

   if(strlen($line) < 5)  continue;  // too short to be meaningful (usually the blanks) so skip this line 

   $cols = explode( "\t", $line );  //  parse the line by tab chars


   $dtm = new DateTime('@'.$cols[1] );   $dtm->setTimezone(new DateTimeZone('America/Montreal'));  // convert the UTC timestamp to the local time
   
   if ( ($dtm->format('d')) % 2  ==  0 )  { echo "<tr class='even'>"; }    // colour rows differently when the day is odd or even
   else                                   { echo "<tr class='odd'>";  }

   $temp  =  explode(" ", $cols[0]);  $thedate = $temp[0];  $thetime = $temp[1];  // split the date & time into two variables

   $referer   = str_replace("http://",  "", $cols[3]); 
   $useragent = $cols[4];   
   $server    = $cols[5];
   $remIP     = $cols[6];

   if (strlen(  $referer)+4 > $max)  {  $referer = substr(  $referer, 0, $max) . "...";}
   if (strlen($useragent)+4 > $max)  {$useragent = substr($useragent, 0, $max) . "...";}
   
   if ($outline != "no")
   {
	   // To improve readability, when a value is the same as the one above, print a tilde instead of 
	   // repeating the text.  Note, I don't outline all columns, in particular, time.
   
	   if ($thedate    == $prev[0])  { $thedate   = $tt; }    else { $prev[0] = $thedate;   }
	   if ($referer    == $prev[3])  { $referer   = $tt; }    else { $prev[3] = $referer;   }
	   if ($useragent  == $prev[4])  { $useragent = $tt; }    else { $prev[4] = $useragent; }
	   if ($server     == $prev[5])  { $server    = $tt; }    else { $prev[5] = $server;    }
	   if ($remIP      == $prev[6])  { $remIP     = $tt; }    else { $prev[6] = $remIP;     }
	}


   echo "<td>"         . $thedate .                      "</td>";  // date
   echo "<td>"         . $thetime .                      "</td>";  // time
   echo "<td><a href='". $cols[2] ."'>" . $cols[2] . "</a></td>";  // page
   echo "<td><a href='". $cols[3] ."'>" . $referer . "</a></td>";  // referer
   echo "<td>"         . $remIP   .                      "</td>";  // vistor IP
   echo "<td>"         . $useragent .                    "</td>";  // user agent
   echo "<td>"         . $server  .                      "</td>";  // server name
   // $cols[7] .  // remote ident
   // $cols[8] .  // remote host
   // $cols[9] .  // remote user

   echo "</tr> \n\n";
 }

 echo "</table></body></html>";
}

?>



