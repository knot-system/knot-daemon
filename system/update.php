<?php

// this file can update the system with the latest release from github. create a empty file called 'update' or 'update.txt' in the root directory, and then add '?update' to the url, to trigger the update

$api_url = 'https://api.github.com/repos/maxhaesslein/postamt/releases';
$dev_zip = 'https://github.com/maxhaesslein/postamt/archive/refs/heads/main.zip';

$step = false;
if( ! empty($_GET['step']) ) $step = $_GET['step'];



$basefolder = str_replace( 'index.php', '', $_SERVER['PHP_SELF']);

if( isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ) $baseurl = 'https://';
else $baseurl = 'http://';
$baseurl .= $_SERVER['HTTP_HOST'];
$baseurl .= $basefolder;


include_once( 'functions_shared/helper.php' );
include_once( 'functions_shared/request.php' );

	
$core_version = get_system_version( $abspath );

?>
<h1>Postamt System Update</h1>
<?php


if( $step == 'check' ) {

	$json = get_remote_json( $api_url );
	
	if( ! $json || ! is_array($json) ) {
		?>
		<p><strong>Error:</strong> could not get release information from GitHub</p>
		<?php
		exit;
	}

	$latest_release = $json[0];

	$release_name = $latest_release->name;

	?>
	<p>
		Currently installed: <strong>v.<?= $core_version ?></strong><br>
		Latest release: <strong>v.<?= $release_name ?></strong>
	</p>
	<?php

	$release_notes = array();

	$version_number_old = explode('.', $core_version);
	$version_number_new = explode('.', $release_name);

	if( $version_number_new[0] > $version_number_old[0] 
	 || ($version_number_new[0] == $version_number_old[0] && $version_number_new[1] > $version_number_old[1] )
	 || ($version_number_new[0] == $version_number_old[0] && $version_number_new[1] == $version_number_old[1] && $version_number_new[2] > $version_number_old[2] )
	){

		foreach( $json as $release ) {
			$tag_name = str_replace( 'v.', '', $release->tag_name );
			$release_number = explode('.', $tag_name);

			$newer_version = false;
			if( $release_number[0] > $version_number_old[0] 
			 || ($release_number[0] == $version_number_old[0] && $release_number[1] > $version_number_old[1] )
			 || ($release_number[0] == $version_number_old[0] && $release_number[1] == $version_number_old[1] && $release_number[2] > $version_number_old[2] )
			){
			 	$newer_version = true;
			}

			if( ! $newer_version ) break;

			$release_notes[] = [
				'title' => $release->tag_name,
				'body' => $release->body
			];
		}
	
		echo '<p><strong>New version available!</strong> You should update your system.</p>';

		if( count($release_notes) ) {
			?>
			<h2>Release notes:</h2>
			<?php
			
			foreach( $release_notes as $release_note ) {
				echo '<h3>'.$release_note['title'].'</h3>';

				$body = htmlentities($release_note['body']);
				$body = nl2br($body);
			
				echo $body;
			}

		}

	} else {
		echo '<p>You are running the latest version.</p>';
	}
	?>
	<hr>

	<form action="<?= $baseurl ?>" method="GET">
		<input type="hidden" name="update" value="true">
		<input type="hidden" name="step" value="install">
		<p><label>Version: <select name="version">
			<option value="latest" selected>latest stable release (v.<?= $release_name ?>)</option>
			<option value="dev">unstable dev release (not recommended)</option>
		</select></label></p>
		<p><button>update system</button></p>
	</form>

	<?php
	exit;

} elseif( $step == 'install' )  {

	if( empty($_REQUEST['version']) || $_REQUEST['version'] == 'latest' ) {

		$json = get_remote_json( $api_url );
		
		if( ! $json || ! is_array($json) ) {
			?>
			<p><strong>Error:</strong> could not get release information from GitHub</p>
			<?php
			exit;
		}

		$latest_release = $json[0];

		$zipball = $latest_release->zipball_url;

		$zip_folder_name_start = 'maxhaesslein-postamt-';

	} elseif( $_REQUEST['version'] == 'dev' ) {

		$zipball = $dev_zip;

		$zip_folder_name_start = 'postamt-';

	} else {

		?>
		<p><strong>Error:</strong> unknown version</p>
		<?php
		exit;

	}

	if( ! $zipball ) {
		?>
		<p><strong>Error:</strong> could not get new .zip file from GitHub</p>
		<?php
		exit;
	}

	echo '<p>Downloading new .zip from GitHub … ';
	flush();

	$temp_zip_file = $abspath.'cache/_new_release.zip';
	if( file_exists($temp_zip_file) ) unlink($temp_zip_file);

	$file_handle = fopen( $temp_zip_file, 'w+' );

	$ch = curl_init( $zipball );
	curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1 );
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
	curl_setopt( $ch, CURLOPT_USERAGENT, 'maxhaesslein/postamt/'.$core_version );
	curl_setopt( $ch, CURLOPT_FILE, $file_handle );
	curl_exec( $ch );
	curl_close( $ch );

	fclose($file_handle);

	echo 'done.</p>';

	echo '<p>Extracting .zip file … ';
	flush();

	function deleteDirectory( $dirPath ) {

		if( ! is_dir($dirPath) ) return;

		$objects = scandir($dirPath);
		foreach ($objects as $object) {
			if( $object == "." || $object == "..") continue;

			if( is_dir($dirPath . DIRECTORY_SEPARATOR . $object) ){
				deleteDirectory($dirPath . DIRECTORY_SEPARATOR . $object);
			} else {
				unlink($dirPath . DIRECTORY_SEPARATOR . $object);
			}
		}
		rmdir($dirPath);
	}


	$temp_folder = $abspath.'cache/_new_release/';
	if( is_dir($temp_folder) ) deleteDirectory($temp_folder);
	mkdir( $temp_folder );

	$zip = new ZipArchive;
	$res = $zip->open($temp_zip_file);
	if( $res !== TRUE ) {
		echo '<p><strong>Error:</strong> could not extract .zip file</p>';
		exit;
	}
	$zip->extractTo( $temp_folder );
	$zip->close();

	echo 'done.</p>';

	$subfolder = false;
	foreach( scandir( $temp_folder ) as $obj ) {
		if( $obj == '.' || $obj == '..' ) continue;
		if( ! is_dir($temp_folder.$obj) ) continue;
		if( ! str_starts_with($obj, $zip_folder_name_start) ) continue;
		// the zip file should have exactly one subfolder. this is what we want to get here
		$subfolder = $temp_folder.$obj.'/';
	}

	if( ! $subfolder ) {
		echo '<p><strong>Error:</strong> something went wrong with the .zip file</p>';
		exit;
	}

	echo '<p>Deleting old files … ';
	flush();

	deleteDirectory( $abspath.'system/' );
	unlink( $abspath.'index.php' );
	unlink( $abspath.'README.md');
	unlink( $abspath.'changelog.txt');

	echo 'done.</p>';

	echo '<p>Moving new files to new location … ';
	flush();

	rename( $subfolder.'system', $abspath.'system' );
	rename( $subfolder.'index.php', $abspath.'index.php' );
	rename( $subfolder.'README.md', $abspath.'README.md' );
	rename( $subfolder.'changelog.txt', $abspath.'changelog.txt' );

	echo 'done.</p>';
	echo '<p>Cleaning up …';
	@unlink( $abspath.'update.txt' );
	@unlink( $abspath.'update' );

	deleteDirectory( $abspath.'cache/');
	mkdir( $abspath.'cache/' );

	echo 'done.</p>';
	flush();

	echo '<p>Please <a href="'.$baseurl.'">refresh this page</a></p>';

} else {
	?>

	<p>Currently installed version: <strong>v.<?= $core_version ?></strong></p>

	<p>Please backup your <em>config.php</em> file before updating!</p>

	<form action="<?= $baseurl ?>" method="GET">
		<input type="hidden" name="update" value="true">
		<input type="hidden" name="step" value="check">
		<button>check for update</button>
	</form>

	<?php

	exit;
}
