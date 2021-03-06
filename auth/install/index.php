<?php
//this script runs entire installation process in 5 steps

//take "step" variable to determine which step the current is
$step = isset($_POST['step']) ? $_POST['step'] : '0';


//perform field validation(steps 3-5) and database connection tests (steps 3 and 4) and send back to previous step if not working
$errorMessage = array();
if ($step == "3"){
	//first, validate all fields are filled in
	$database_host = (isset($_POST['database_host']) ? trim($_POST['database_host']) : null);
	$database_username = (isset($_POST['database_username']) ? trim($_POST['database_username']) : null);
	$database_password = (isset($_POST['database_password']) ? trim($_POST['database_password']) : null);
	$database_name = (isset($_POST['database_name']) ? trim($_POST['database_name']) : null);

	if (!$database_host) $errorMessage[] = 'Host name is required';
	if (!$database_name) $errorMessage[] = 'Database name is required';
	if (!$database_username) $errorMessage[] = 'User name is required';
	if (!$database_password) $errorMessage[] = 'Password is required';

	//only continue to checking DB connections if there were no errors this far
	if (count($errorMessage) > 0){
		$step="2";
	}else{

		//first check connecting to host
		$link = mysqli_connect("$database_host", "$database_username", "$database_password");
		if (!$link) {
			$errorMessage[] = "Could not connect to the server '" . $database_host . "'<br />MySQL Error: " . mysqli_error($link);
		}else{

			//next check that the database exists
			$dbcheck = mysqli_select_db($link, "$database_name");
			if (!$dbcheck) {
				$errorMessage[] = "Unable to access the database '" . $database_name . "'.  Please verify it has been created.<br />MySQL Error: " . mysqli_error($link);
			}else{

				//make sure the tables don't already exist - otherwise this script will overwrite all of the data!
				$query = "SELECT count(*) count FROM information_schema.`TABLES` WHERE table_schema = '" . $database_name . "' AND table_name='User' and table_rows > 0";

				//if User table exists, error out
				if (!$row = mysqli_fetch_array(mysqli_query($link, $query))){
					$errorMessage[] = "Please verify your database user has access to select from the information_schema MySQL metadata database.";
				}else{
					if ($row['count'] > 0 ){
						$errorMessage[] = "The Authentication tables already exist.  If you intend to upgrade, please run upgrade.php instead.  If you would like to perform a fresh install you will need to manually drop all of the Authentication tables in this schema first.";
					}else{

						//passed db host, name check, can open/run file now
						//make sure SQL file exists
						$test_sql_file = "test_create.sql";
						$sql_file = "create_tables_data.sql";

						if (!file_exists($test_sql_file)) {
							$errorMessage[] = "Could not open sql file: " . $test_sql_file . ".  If this file does not exist you must download new install files.";
						}else{
							//run the file - checking for errors at each SQL execution
							$f = fopen($test_sql_file,"r");
							$sqlFile = fread($f,filesize($test_sql_file));
							$sqlArray = explode(";",$sqlFile);

							//Process the sql file by statements
							foreach ($sqlArray as $stmt) {
							   if (strlen(trim($stmt))>3){

									$result = mysqli_query($link, $stmt);
									if (!$result){
										$errorMessage[] = mysqli_error($link) . "<br /><br />For statement: " . $stmt;
										 break;
									}
								}
							}

						}


						//once this check has passed we can run the entire ddl/dml script
						if (count($errorMessage) == 0){
							if (!file_exists($sql_file)) {
								$errorMessage[] = "Could not open sql file: " . $sql_file . ".  If this file does not exist you must download new install files.";
							}else{
								//run the file - checking for errors at each SQL execution
								$f = fopen($sql_file,"r");
								$sqlFile = fread($f,filesize($sql_file));
								$sqlArray = explode(';',$sqlFile);



								//Process the sql file by statements
								foreach ($sqlArray as $stmt) {
								   if (strlen(trim($stmt))>3){

										$result = mysqli_query($link, $stmt);
										if (!$result){
											$errorMessage[] = mysqli_error($link) . "<br /><br />For statement: " . $stmt;
											 break;
										}
									}
								}

							}
						}
					}
				}
			}
		}

	}

	if (count($errorMessage) > 0){
		$step="2";
	}

}else if ($step == "4"){

	//first, validate all fields are filled in
	$database_host = (isset($_POST['database_host']) ? trim($_POST['database_host']) : null);
	$database_username = (isset($_POST['database_username']) ? trim($_POST['database_username']) : null);
	$database_password = (isset($_POST['database_password']) ? trim($_POST['database_password']) : null);
	$database_name = (isset($_POST['database_name']) ? trim($_POST['database_name']) : null);
	$session_timeout = (isset($_POST['session_timeout']) ? trim($_POST['session_timeout']) : null);

    $ldap = array(
        'ldap_enabled'     =>(isset($_POST['ldap_enabled']) ? 'Y' : 'N'),
        'host'        =>(isset($_POST['ldap_host']) ? $_POST['ldap_host'] : null),
        'port'        =>(isset($_POST['ldap_port']) ? $_POST['ldap_port'] : null),
        'search_key'  =>(isset($_POST['ldap_search_key']) ? $_POST['ldap_search_key'] : null),
        'base_dn'     =>(isset($_POST['ldap_base_dn']) ? $_POST['ldap_base_dn'] : null),
        'bindAccount' =>(isset($_POST['ldap_bind_account']) ? $_POST['ldap_bind_account'] : null),
        'bindPass'=>(isset($_POST['ldap_bind_password']) ? $_POST['ldap_bind_password'] : null)
    );

    if ($ldap['ldap_enabled']=='Y') {
        if (!$ldap['host']) $errorMessage[] = "LDAP Host is required for LDAP";
        if (!$ldap['search_key']) $errorMessage[] = "LDAP Search Key is required for LDAP";
        if (!$ldap['base_dn']) $errorMessage[] = "LDAP Base DN is required for LDAP";
    }

	if (!$database_username) $errorMessage[] = 'User name is required';
	if (!$database_password) $errorMessage[] = 'Password is required';
	if (!$session_timeout) $errorMessage[] = 'Session timeout is required';

	//only continue to checking DB connections if there were no errors this far
	if (count($errorMessage) > 0){
		$step="3";
	}else{

		//first check connecting to host
		$link = mysqli_connect("$database_host", "$database_username", "$database_password");
		if (!$link) {
			$errorMessage[] = "Could not connect to the server '" . $database_host . "'<br />MySQL Error: " . mysqli_error($link);
		}else{

			//next check that the database exists
			$dbcheck = mysqli_select_db($link, "$database_name");
			if (!$dbcheck) {
				$errorMessage[] = "Unable to access the database '" . $database_name . "'.  Please verify it has been created.<br />MySQL Error: " . mysqli_error($link);
			}else{
				//passed db host, name check, test that user can select from Auth database
				$result = mysqli_query($link, "SELECT loginID FROM " . $database_name . ".User WHERE loginID like '%coral%';");
				if (!$result){
					$errorMessage[] = "Unable to select from the User table in database '" . $database_name . "' with user '" . $database_username . "'.  Error: " . mysqli_error($link);
				}

			}
		}

	}


	//only continue if there were no errors this far
	if (count($errorMessage) > 0){
		$step="3";
	}else{

		//write the config file
		$configFile = "../admin/configuration.ini";
		$fh = fopen($configFile, 'w');

		if (!$fh){
			$errorMessage[] = "Could not open file " . $configFile . ".  Please verify you can write to the /admin/ directory.";
		}else{

			$iniData = array();
			$iniData[] = "[settings]";
			$iniData[] = "timeout=" . $session_timeout;

			$iniData[] = "\n[database]";
			$iniData[] = "type = \"mysql\"";
			$iniData[] = "host = \"" . $database_host . "\"";
			$iniData[] = "name = \"" . $database_name . "\"";
			$iniData[] = "username = \"" . $database_username . "\"";
			$iniData[] = "password = \"" . $database_password . "\"";

            $iniData[] = "\n[ldap]";
            foreach ($ldap as $fname => $fvalue) {
                $iniData[] = "$fname = \"$fvalue\"";
            }
			fwrite($fh, implode("\n",$iniData));
			fclose($fh);
		}


	}

	if (count($errorMessage) > 0){
		$step="3";
	}

}


?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>CORAL Installation</title>
<link rel="stylesheet" href="css/style.css" type="text/css" />
<script src="js/jquery.js"></script>
<script src="js/index.js"></script>
</head>
<body>
<center>
<table style='width:700px;'>
<tr>
<td style='vertical-align:top;'>
<div style="text-align:left;">


<?php if($step=='0'){ ?>

	<h3>Welcome to a new CORAL Auth installation!</h3>
	This installation will:
	<ul>
		<li>Check that you are running PHP 5</li>
		<li>Connect to MySQL and create the CORAL Auth tables</li>
		<li>Test the database connection the CORAL Auth application will use </li>
		<li>Set up the config file with settings you choose</li>
	</ul>

	<br />
	To get started you should:
	<ul>
		<li>Create a MySQL Schema created for CORAL Auth Module - recommended name is coral_auth_prod.  Each CORAL module has separate user permissions and requires a separate schema.</li>
		<li>Know your host, username and password for MySQL with permissions to create tables</li>
		<li>It is recommended for security to have a different username and password for CORAL with only select, insert, update and delete privileges to CORAL schemas</li>
		<li>Verify that your /admin/ directory is writable by server during the installation process (chmod 777).  After installation you should chmod it back.</li>
	</ul>


	<form action="<?php echo $_SERVER['PHP_SELF']?>" method="post">
	<input type='hidden' name='step' value='1'>
	<input type="submit" value="Continue" name="submit">
	</form>


<?php
//first step - check system info and verify php 5
} else if ($step == '1') {
	ob_start();
    phpinfo(-1);
    $phpinfo = array('phpinfo' => array());
    if(preg_match_all('#(?:<h2>(?:<a name=".*?">)?(.*?)(?:</a>)?</h2>)|(?:<tr(?: class=".*?")?><t[hd](?: class=".*?")?>(.*?)\s*</t[hd]>(?:<t[hd](?: class=".*?")?>(.*?)\s*</t[hd]>(?:<t[hd](?: class=".*?")?>(.*?)\s*</t[hd]>)?)?</tr>)#s', ob_get_clean(), $matches, PREG_SET_ORDER))
    foreach($matches as $match){
        if(strlen($match[1]))
            $phpinfo[$match[1]] = array();
        elseif(isset($match[3]))
            $phpinfo[end(array_keys($phpinfo))][$match[2]] = isset($match[4]) ? array($match[3], $match[4]) : $match[3];
        else
            $phpinfo[end(array_keys($phpinfo))][] = $match[2];
    }




    ?>

	<h3>Getting system info and verifying php version</h3>
	<ul>
	<li>System: <?php echo $phpinfo['phpinfo']['System'];?></li>
    <li>PHP version: <?php echo phpversion();?></li>
    <li>Server API: <?php echo $phpinfo['phpinfo']['Server API'];?></li>
	</ul>

	<br />

	<?php


	if (phpversion() >= 5){
	?>
		<form action="<?php echo $_SERVER['PHP_SELF']?>" method="post">
		<input type='hidden' name='step' value='2'>
		<input type="submit" value="Continue" name="submit">
		</form>
	<?php
	}else{
		echo "<span style='font-size=115%;color:red;'>PHP 5 is not installed on this server!  Installation will not continue.</font>";
	}

//second step - ask for DB info to run DDL
} else if ($step == '2') {

	if (!isset($database_host)) $database_host='localhost';
	if (!isset($database_name)) $database_name='coral_auth_prod';
    if (!isset($database_username)) $database_username = "";
    if (!isset($database_password)) $database_password = "";
	?>
		<form method="post" action="<?php echo $_SERVER['PHP_SELF']?>">
		<h3>MySQL info with permissions to create tables</h3>
		<?php
			if (count($errorMessage) > 0){
				echo "<span style='color:red'><b>The following errors occurred:</b><br /><ul>";
				foreach ($errorMessage as $err)
					echo "<li>" . $err . "</li>";
				echo "</ul></span>";
			}
		?>
		<table width="100%" border="0" cellspacing="0" cellpadding="2">
		<tr>
			<tr>
				<td>&nbsp;Database Host</td>
				<td>
					<input type="text" name="database_host" size="30" value='<?php echo $database_host?>'>
				</td>
			</tr>
			<tr>
				<td>&nbsp;Database Schema Name</td>
				<td>
					<input type="text" name="database_name" size="30" value="<?php echo $database_name?>">
				</td>
			</tr>
			<tr>
				<td>&nbsp;Database Username</td>
				<td>
					<input type="text" name="database_username" size="30" value="<?php echo $database_username?>">
				</td>
			</tr>
			<tr>
				<td>&nbsp;Database Password</td>
				<td>
					<input type="password" name="database_password" size="30" value="<?php echo $database_password?>">
				</td>
			</tr>
			<tr>
				<td colspan=2>&nbsp;</td>
			</tr>
			<tr>
				<td align='left'>&nbsp;</td>
				<td align='left'>
				<input type='hidden' name='step' value='3'>
				<input type="submit" value="Continue" name="submit">
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				<input type="button" value="Cancel" onclick="document.location.href='index.php'">
				</td>
			</tr>

		</table>
		</form>
<?php
//third step - ask for DB info to log in from CORAL
} else if ($step == '3') {

	if (!isset($session_timeout)) $session_timeout='3600';

    $ldap = array('host'=>'', 'port'=>'', 'search_key'=>'', 'base_dn'=>'', 'bindAccount'=>'','bindPass'=>'');
    if (isset($_POST['ldap_enabled'])) {
        $ldap['ldap_enabled'] = 'Y';
        if (isset($_POST['ldap_host']))
            $ldap['host'] = $_POST['ldap_host'];
        if (isset($_POST['ldap_port']))
            $ldap['port'] = $_POST['ldap_port'];
        if (isset($_POST['ldap_search_key']))
            $ldap['search_key'] = $_POST['ldap_search_key'];
        if (isset($_POST['ldap_base_dn']))
            $ldap['base_dn'] = $_POST['ldap_base_dn'];
        if (isset($_POST['ldap_bind_account']))
            $ldap['bindAccount'] = $_POST['ldap_bind_account'];
        if (isset($_POST['ldap_bind_password']))
            $ldap['bindPass'] = $_POST['ldap_bind_password'];
    } else {
        $ldap['ldap_enabled'] = 'N';
    }

	?>
		<form method="post" action="<?php echo $_SERVER['PHP_SELF']?>">
		<h3>MySQL user for CORAL web application - with select, insert, update, delete privileges to CORAL schemas</h3>
		*It's recommended but not required that this user is different than the one used on the prior step
		<?php
			if (count($errorMessage) > 0){
				echo "<br /><span style='color:red'><b>The following errors occurred:</b><br /><ul>";
				foreach ($errorMessage as $err)
					echo "<li>" . $err . "</li>";
				echo "</ul></span>";
			}
		?>
		<input type="hidden" name="database_host" value='<?php echo $database_host?>'>
		<input type="hidden" name="database_name" value="<?php echo $database_name?>">

		<table width="100%" border="0" cellspacing="0" cellpadding="2">
		<tr>
			<tr>
				<td>&nbsp;Database Username</td>
				<td>
					<input type="text" name="database_username" size="30" value="<?php echo $database_username?>">
				</td>
			</tr>
			<tr>
				<td>&nbsp;Database Password</td>
				<td>
					<input type="password" name="database_password" size="30" value="<?php echo $database_password?>">
				</td>
			</tr>

			<tr>
				<td>&nbsp;Session Timeout - in seconds</td>
				<td>
					<input type="text" name="session_timeout" size="30" value="<?php echo $session_timeout?>">
				</td>
			</tr>

            <tr>
				<td colspan=2>&nbsp;</td>
			</tr>

            <tr>
				<td>&nbsp;Enable LDAP</td>
				<td>
					<input type="checkbox" id="ldap_enabled" name="ldap_enabled" size="30" <?php echo ($ldap['ldap_enabled']=='Y')?'checked="true"':''?> onclick="ShowLDAP()">
				</td>
			</tr>
            <tr>
                <td>&nbsp;LDAP Host</td>
                <td>
                    <input type="text" name="ldap_host" class="ldap" size="30" value="<?php echo $ldap['host']?>" <?php echo ($ldap['ldap_enabled']=='Y')?'':'disabled="disabled"'?>>
                </td>
            </tr>

            <tr>
                <td>&nbsp;LDAP Port</td>
                <td>
                    <input type="text" name="ldap_port" class="ldap" size="30" value="<?php echo $ldap['port']?>" <?php echo ($ldap['ldap_enabled']=='Y')?'':'disabled="disabled"'?>>
                </td>
            </tr>

            <tr>
                <td>&nbsp;LDAP Search Key</td>
                <td>
                    <input type="text" name="ldap_search_key" class="ldap" size="30" value="<?php echo $ldap['search_key']?>" <?php echo ($ldap['ldap_enabled']=='Y')?'':'disabled="disabled"'?>>
                </td>
            </tr>

            <tr>
                <td>&nbsp;LDAP Base DN</td>
                <td>
                    <input type="text" name="ldap_base_dn" class="ldap" size="30" value="<?php echo $ldap['base_dn']?>" <?php echo ($ldap['ldap_enabled']=='Y')?'':'disabled="disabled"'?>>
                </td>
            </tr>

            <tr>
                <td>&nbsp;LDAP Bind Account</td>
                <td>
                    <input type="text" name="ldap_bind_account" class="ldap" size="30" value="<?php echo $ldap['bindAccount']?>" <?php echo ($ldap['ldap_enabled']=='Y')?'':'disabled="disabled"'?>>
                </td>
            </tr>

            <tr>
                <td>&nbsp;LDAP Bind Password</td>
                <td>
                    <input type="password" name="ldap_bind_password" class="ldap" size="30" value="<?php echo $ldap['bindPass']?>" <?php echo ($ldap['ldap_enabled']=='Y')?'':'disabled="disabled"'?>>
                </td>
            </tr>

            <tr>
				<td colspan=2>&nbsp;</td>
			</tr>

			<tr>
				<td align='left'>&nbsp;</td>
				<td align='left'>
				<input type='hidden' name='step' value='4'>
				<input type="submit" value="Continue" name="submit">
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				<input type="button" value="Cancel" onclick="document.location.href='index.php'">
				</td>
			</tr>
		</table>
		</form>
    <script>
    ShowLDAP();
    </script>
<?php
//fourth step - ask for other settings in configuration.ini
} else if ($step == '4') {

?>
	<h3>CORAL Authentication installation is now complete!</h3>
	It is recommended you now:
	<ul>
		<li>Set up your .htaccess file</li>
		<li>Remove the /install/ directory for security purposes</li>
		<li>Set up your users on the <a href='../admin.php'>admin screen</a>.  You may log in initially with coral/admin.</li>
	</ul>

<?php
}
?>

</td>
</tr>
</table>
<br />
</center>


</body>
</html>
