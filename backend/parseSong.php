<?php
include_once "../common/setup.php";
include_once "../common/parsingFunctions.php";
include_once "../common/genericFunctions.php";
include_once "../common/printingFunctions.php";

$songId = uniqidReal();
$nl = "<br>";
$cursorPosition = 0;
$measureCursor = 0;
$errors = [];
//----- general song data
$title = "";
$creator = [];
$instrumentation = [];
$newPart = false;
$currentPart = "";
//----- measure data -----
// attributes
$divisions = 1;
$key = ""; // fifths: positive sharp, negative flat
$mode = "";
$time = []; // beats, beat-type
// song structure data
$repeatsJumps = [];
$daCapo = false;
// lyrics (notes)
$lyricsInMeasure = [];
// harmony
$harmonyInMeasure = [];

/*------------------------PARSE SONG --------------------- */
$songxml = $_FILES["songxml"]["tmp_name"];

$song = new XMLReader;
$song->open($songxml);

while ($song->read())
{
    switch ($song->name)
    {
        case 'work-title':
            $title = addslashes($song->readString());
            $song->next();
            break;
        case 'creator':
            while ($song->name === 'creator')
            {                
                $creator[$song->getAttribute('type')] = $song->readString();
                $song->next();
            }
            break;
        case 'part-list': 
            // last required child of <score-partwise> before <part> description
            include_once "../backend/insertNewSong.php";
            break;
        case 'part-name':
            if (!in_array($song->readString(), $instrumentation))
                $instrumentation[] = $song->readString();
            $song->next();
            break;
        case 'part':
            $newPart = !$newPart;
            if ($newPart === true)
            {
                $currentPart = (string) $song->getAttribute('id');
                $cursorPosition = 0;
                $measureCursor = 0;
            }
            break;
        case 'measure':
            while ($song->name === 'measure')
            {
                $measure = new SimpleXMLElement($song->readOuterXML());
                $lyricsInMeasure = [];
                $harmonyInMeasure = [];
                
                if ($currentPart === "P1")
                    $newMeasureInfo = extractMeasureInfo($measure, $measureCursor); // only do this if part is the first one.

                foreach ($measure->children() as $child)
                {
                    switch ($child->getName())
                    {
                        case 'note':
                            if ($child->lyric)
                            {
                                $voice = ($child->voice) ? ((int) $child->voice) : 1;
                                $staff = ($child->staff) ? (string) ($child->staff) : 1;
                                $lyricsInMeasure[] = ['staff'=>$staff, 'voice'=>$voice, 'absolutePosition'=>$cursorPosition, 'lyrics'=>extractLyrics($child)];
                            }                           
                            $cursorPosition += ((double)(1/$divisions)*($child->duration))*($time['beatType']/4); // cursor position
                            break;
                        case 'harmony':
                            $currentHarmony = extractHarmonyInfo($child, $cid);
                            $harmonyInMeasure[] = ['absolutePosition'=>$cursorPosition, 'divisions'=>$divisions, 'harmony'=>$currentHarmony];
                            break;
                        case 'backup':
                            $cursorPosition -= (($child->duration)*(1/$divisions))*($time['beatType']/4);
                            break;
                        case 'forward': // not present in any of the examples, added because part of the standard
                            $cursorPosition += (($child->duration)*(1/$divisions))*($time['beatType']/4);
                            break;
                    }
                }
                include "../backend/insertMeasureInfo.php";
                // update measureCursor
                if ($measure['number'] == "0")
                    $measureCursor = $cursorPosition;
                else
                    $measureCursor += $time['beats'];
                $song->next();
            }
            break;
    }
}

include_once "../backend/insertInstrumentInfo.php";
include_once "../backend/computeSongSegments.php";
include_once "../backend/insertSongSegments.php";

if (empty($errors))
{
    $songInfo["songId"]=$songId;
    $songInfo["work-title"]=stripslashes($title);

    $tempCreator = "";
    $creatorUnique = array_unique($creator);
    foreach ($creatorUnique as $type => $name)
    {
        $tempCreator .= $name.", ";
    }
    $tempCreator = rtrim($tempCreator, ", ");
    $songInfo["creator"]=$tempCreator;

    echo json_encode($songInfo);
} else
{
    echo json_encode($errors);
}
?>