<?php

if( ! $postamt ) exit;

?>
<h1>Unauthorized</h1>
<p>You cannot open this page directly. Use a microsub client to connect to this server.</p>
<p><small><a href="https://github.com/maxhaesslein/postamt" target="_blank" rel="noopener">Postamt</a> v.<?= $postamt->version() ?></small></p>
