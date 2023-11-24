<?php
// queries for inserting instruments and song_instrumentation
$sql = "";
$values = "";
$song_inst = "";

foreach ($instrumentation as $inst)
{
    $values .= "('".$inst."'),";
    $song_inst .= "('".$songId."','".$inst."'),";
} 
$values = rtrim($values, ",");
$song_inst = rtrim($song_inst, ",");

$sql .= "INSERT IGNORE INTO `instrument`(`instrument`) VALUES $values;
         INSERT INTO `song_instrumentation`(`songId`, `instrument`) VALUES $song_inst; ";

$instrumentsQuery = queryDb($cid, $sql);

if ($instrumentsQuery['status']==='ko')
    $errors[] = ['type'=>"instrumentsQuery", 'msg'=>$instrumentsQuery['msg']];

?>