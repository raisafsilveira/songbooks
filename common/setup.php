<?php

$hostname = 'localhost';
$username = 'root';
$password = '';
$db = 'songbooks';

try {
    $cid = new mysqli($hostname,$username,$password,$db);
} catch (Exception $e) {
    $msg = 'Caught exception: '.$e->getMessage();
    return $msg;
}

?>
