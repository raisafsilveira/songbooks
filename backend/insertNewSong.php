<?php
// check whether song is already in db. If so, give user the option of taking a look at the one already stored

$sql = "";
$sql = "INSERT INTO `song`(`songId`, `work-title`, `songKey`, `mode`) VALUES ('$songId','$title','',''); ";

// queries for inserting creators and created_by
$values = "";
$created_by = "";
foreach ($creator as $type => $name)
{
    $values .= "('".$name."'),";
    $created_by .= "('".$songId."','".$name."','".$type."'),";
}
$values = rtrim($values, ",");
$created_by = rtrim($created_by, ",");

$sql .= "INSERT IGNORE INTO `creator`(`creator`) VALUES $values;
         INSERT IGNORE INTO `created_by`(`songId`, `creator`, `type`) VALUES $created_by; ";

$newSongQuery = queryDb($cid, $sql);
if ($newSongQuery['status']==='ko')
    $errors[] = ['type'=>"newSongQuery", 'msg'=>$newSongQuery['msg']];

?>