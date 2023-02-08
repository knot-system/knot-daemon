<?php

// this file creates some basic files and folderstructure and gets called, if important files are missing (like the config.php or .htaccess)


$basefolder = str_replace( 'index.php', '', $_SERVER['PHP_SELF']);

if( isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ) $baseurl = 'https://';
else $baseurl = 'http://';
$baseurl .= $_SERVER['HTTP_HOST'];
$baseurl .= $basefolder;


if( file_exists($abspath.'config.php') && file_exists($abspath.'.htaccess') ) {
	?>
	<p>Setup already finished. Please delete <em>config.php</em> or <em>.htaccess</em> from the root directory to re-run the setup.</p>
	<?php
	exit;
}

?>
<p>Hi. This is the first-time setup of Postamt.</p>
<p>We create some files and folders to get everything up and running.</p>

<hr>

<h3>Environment:</h3>
<ul>
	<li>ABSPATH: <em><?= $abspath ?></em></li>
	<li>BASEFOLDER: <em><?= $basefolder ?></em></li>
	<li>BASEURL: <em><?= $baseurl ?></em></li>
</ul>

<?php

if( $abspath == '' ) {
	?>
	<p><strong>ERROR:</strong> ABSPATH is empty. we don't know what went wrong. we abort the setup here.</p>
	<?php
	exit;
}
if( $basefolder == '' ) {
	?>
	<p><strong>ERROR:</strong> BASEFOLDER is empty. we don't know what went wrong. we abort the setup here.</p>
	<?php
	exit;
}
if( $baseurl == '' ) {
	?>
	<p><strong>ERROR:</strong> BASEURL is empty. we don't know what went wrong. we abort the setup here.</p>
	<?php
	exit;
}

$config = true;
if( file_exists($abspath.'config.php') ) $config = false;

?>

<hr>

<h3>checking <em>.htaccess</em> file:</h3>
<ul>
	<li>checking if <em>.htaccess</em> file exists</li>
<?php
if( ! file_exists( $abspath.'.htaccess' ) ) {
	$rewrite_base = $basefolder;
	if( $rewrite_base == '' ) $rewrite_base = '/';
	?>
	<li>file <em>.htaccess</em> does not exist, creating it with rewrite base <em><?= $rewrite_base ?></em></li>
	<?php
	$content = "<IfModule mod_rewrite.c>\r\nRewriteEngine on\r\nRewriteBase ".$rewrite_base."\r\n\r\nRewriteRule (^|/)\.(?!well-known\/) index.php [L]\r\nRewriteRule ^content/(.*)\.(txt|md|mdown)$ index.php [L]\r\nRewriteRule ^content/(.*)\.(jpg|jpeg|png)$ index.php [L]\r\nRewriteRule ^system/(.*) index.php [L]\r\nRewriteRule ^log/(.*) index.php [L]\r\nRewriteRule ^cache/(.*) index.php [L]\r\n\r\nRewriteCond %{REQUEST_FILENAME} !-d\r\nRewriteCond %{REQUEST_FILENAME} !-f\r\nRewriteRule . index.php [L]\r\n</IfModule>";
	if( file_put_contents( $abspath.'.htaccess', $content ) === false ) {
		?>
		<li><strong>ERROR:</strong> file <em>.htaccess</em> could not be created. Please check the permissions of the root folder and make sure we are allowed to write to it. we abort the setup here.</li>
		<?php
		exit;
	} else {
		?>
		<li>file <em>.htaccess</em> was successfully created</li>
		<?php
	}
} else {
	?>
	<li>file <em>.htaccess</em> exists; if you need to recreate it, delete it and rerun this setup.</li>
	<?php
}
?>
</ul>


<h3>checking <em>content/</em> folder:</h3>
<ul>
<?php
if( ! is_dir( $abspath.'content/') ) {
	?>
	<li>folder <em>content/</em> does not exist, trying to create it</li>
	<?php
	if( mkdir( $abspath.'content/', 0777, true ) === false ) {
		?>
		<li><strong>ERROR:</strong> folder <em>content/</em> could not be created. Please check the permissions of the root folder and make sure we are allowed to write to it. we abort the setup here.</li>
		<?php
		exit;
	} else {
		?>
		<li>folder <em>content/</em> was created successfully</li>
		<?php
	}
} else {
	?><li>folder <em>content/</em> already exists, we do not need to create it</li><?php
}
?>
</ul>


<h3>checking <em>cache/</em> folder:</h3>
<ul>
<?php
if( ! is_dir( $abspath.'cache/') ) {
	?>
	<li>folder <em>cache/</em> does not exist, trying to create it</li>
	<?php
	if( mkdir( $abspath.'cache/', 0777, true ) === false ) {
		?>
		<li><strong>ERROR:</strong> folder <em>cache/</em> could not be created. Please check the permissions of the root folder and make sure we are allowed to write to it. we abort the setup here.</li>
		<?php
		exit;
	} else {
		?>
		<li>folder <em>cache/</em> was created successfully</li>
		<?php
	}
} else {
	?><li>folder <em>cache/</em> already exists, we do not need to create it</li><?php
}
?>
</ul>


<h3>creating the <em>config.php</em> file:</h3>
<ul>
	<?php
	if( $config ) {
		$content = "<?php\r\n\r\nreturn [\r\n	'debug' => true,\r\n];\r\n"; // CLEANUP: remove the debug option, when the system is stable enough
		if( file_put_contents( $abspath.'config.php', $content ) === false ) {
			?>
			<li><strong>ERROR:</strong> could not create the file <em>config.php</em>. make sure the folder is writeable. we abort the setup here.</li>
			<?php
		} else {
			?>
			<li>file <em>config.php</em> created successfully</li>
			<?php
		}
	} else {
		?>
		<li>file <em>config.php</em> exists; if you need to recreate it, delete it and rerun this setup.</li>
		<?php
	}
	?>
</ul>


<hr>
<h3>Setup finished!</h3>
<p>please <a href="<?= $baseurl ?>">reload this page</a>.</p>
<hr>
<?php
exit;
