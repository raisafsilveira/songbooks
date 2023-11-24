<?php
// general measure info (time, key, mode)
$sql = "";
$generalInfoQuery = "";
if (!empty($newMeasureInfo))
{
    if (array_key_exists('key', $newMeasureInfo))
        $sql .= "UPDATE `song` SET `songKey`='$key',`mode`='$mode' WHERE `songId` = '$songId'; ";

    if (array_key_exists('time', $newMeasureInfo))
     {
        $sql .= "INSERT IGNORE INTO `timesignature`(`beats`, `beatType`) VALUES ('".$time['beats']."','".$time['beatType']."'); ";
        $sql .= "INSERT INTO `time_in_song`(`songId`, `absolutePosition`, `beats`, `beatType`) VALUES ('$songId','$measureCursor','".$time['beats']."','".$time['beatType']."'); "; 
     }

    $generalInfoQuery = queryDb($cid, $sql);    
    if ($generalInfoQuery['status']==='ko')
        $errors[] = ['type'=>"generalInfoQuery", 'msg'=>$generalInfoQuery['msg']];
}

// lyrics
$sql = "";
$lyricsQuery = "";
if (!empty($lyricsInMeasure))
{
    $songLyrics = "";
    foreach ($lyricsInMeasure as $lyricsInfo)
    {   
        $temp = "";
        foreach ($lyricsInfo['lyrics'] as $lyrics)
        {
            $temp .= "'$songId', '".$lyricsInfo['staff']."','".$lyricsInfo['voice']."','".$lyrics['lyricNumber']."','".$lyricsInfo['absolutePosition']."','".$lyrics['text']."','".$lyrics['syllabic']."'"; 
            $songLyrics .= "($temp), ";
            $temp = "";
        }
    }
    $songLyrics = rtrim($songLyrics, ", ");
    $sql .= "INSERT INTO `songlyrics`(`songId`, `staff`, `voice`, `lyricNumber`, `absolutePosition`, `text`, `syllabic`) VALUES $songLyrics; ";
    
    $lyricsQuery = queryDb($cid, $sql);
    if ($lyricsQuery['status']==='ko')
        $errors[] = ['type'=>"lyricsQuery", 'msg'=>$lyricsQuery['msg']];
}

// harmony 
$sql = "";
$harmonyQuery = "";
if (!empty($harmonyInMeasure))
{
    $writtenHarmonyValues = "";
    $harmonyInSongValues = "";
    $harmonyAsDiagram = "";
    foreach ($harmonyInMeasure as $harmonyInfo)
    {   
        $harmonyDescription = $harmonyInfo['harmony'];
        $writtenHarmonyValues .= "('".$harmonyDescription['writtenHarmony']."','".$harmonyDescription['chordSymbol']."','".$harmonyDescription['root_step']."','".$harmonyDescription['root_alter']."','".$harmonyDescription['bass_step']."','".$harmonyDescription['bass_alter']."'), ";
        $harmonyInSongValues .= "('$songId','".$harmonyDescription['writtenHarmony']."','".$harmonyDescription['fretboardDiagram']."','".$harmonyInfo['absolutePosition']."'), ";
        if ($harmonyDescription['fretboardDiagram'] !== "")
            $harmonyAsDiagram .= "('".$harmonyDescription['writtenHarmony']."','".$harmonyDescription['fretboardDiagram']."'), ";
    }    
    $writtenHarmonyValues = rtrim($writtenHarmonyValues, ", ");
    $harmonyInSongValues = rtrim($harmonyInSongValues, ", ");
    if ($harmonyAsDiagram !== "")
        $harmonyAsDiagram = rtrim($harmonyAsDiagram, ", ");

    $sql = "INSERT IGNORE INTO `writtenharmony`(`writtenHarmony`, `chordSymbol`, `root-step`, `root-alter`, `bass-step`, `bass-alter`) VALUES $writtenHarmonyValues; ";
    $sql .= "INSERT INTO `harmony_in_song`(`songId`, `writtenHarmony`, `diagramId`, `absolutePosition`) VALUES $harmonyInSongValues;";
    // insert ignore into harmony_as_diagram
    if ($harmonyAsDiagram !== "")
        $sql .= "INSERT IGNORE INTO `harmony_as_diagram`(`writtenHarmony`, `diagramId`) VALUES $harmonyAsDiagram;";
    
    $harmonyQuery = queryDb($cid, $sql);
    if ($harmonyQuery['status']==='ko')
        $errors[] = ['type'=>"harmonyQuery", 'msg'=>$harmonyQuery['msg']];
}
?>