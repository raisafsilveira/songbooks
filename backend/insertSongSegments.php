<?php
$sql = "";
$values = "";
$segments = "";
$segmentNumber = 0;
foreach ($songSegments as $segment)
{
    $values .= "('".$songId."','".$segmentNumber."','".$segment['fromPosition']."','".$segment['toPosition']."'),";
    $segmentNumber++;
} 
$values = rtrim($values, ",");

$sql .= "INSERT INTO `songsegments`(`songId`, `segmentNumber`, `fromPosition`, `toPosition`) VALUES $values;";

$segmentsQuery = queryDb($cid, $sql);
if ($segmentsQuery['status']==='ko')
      $errors[] = ['type'=>"segmentsQuery", 'msg'=>$segmentsQuery['msg']];

?>