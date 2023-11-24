<?php

// extract general information about the measure: attributes, barline (repeat), direction (coda, segno, ...)
function extractMeasureInfo(&$measureNode, $measureCursor)
{
    $newMeasureInfo = [];
    if ($measureNode->attributes)
    {
        if ($measureNode->attributes->divisions)
            $GLOBALS['divisions'] = (int) $measureNode->attributes->divisions;

        if ($measureNode->attributes->key) 
        {
            if (!$GLOBALS['key']) 
            {
                $fifths = (int) $measureNode->attributes->key->fifths;
                $mode = ($measureNode->attributes->key->mode) ? (string) $measureNode->attributes->key->mode : "major";
                $GLOBALS['key'] = getTonality($fifths, $mode);
                $GLOBALS['mode'] = $mode;
                $newMeasureInfo['key'] = true;
            }
        }

        if ($measureNode->attributes->time)
        {
            $GLOBALS['time'] = ['beats'=>(int) $measureNode->attributes->time->beats, 'beatType'=>(int) $measureNode->attributes->time->{'beat-type'}]; 
            $newMeasureInfo['time'] = true;
        }
    }
    
    if ($measureNode->barline) // In this order (musicXml): segno, coda, ending, repeat;
    {
        foreach ($measureNode->barline as $barline)
        {
            if ($barline->ending)
            {
                $position = $measureCursor;
                $specific = (string) $barline->ending['type'];
                $number = (string) $barline->ending['number'];
                if ($specific !== "start")
                    $position = $measureCursor + $GLOBALS['time']['beats'];
                
                $GLOBALS['repeatsJumps'][] = ['type'=>"ending", 'number'=> $number, 'specific'=>$specific, 'position'=>$position];
            }

            if ($barline->repeat)
            {
                $position = $measureCursor;
                $specific = (string) $barline->repeat['direction'];
                if ($specific !== "forward")
                    $position = $measureCursor + $GLOBALS['time']['beats'];
                
                $GLOBALS['repeatsJumps'][] = ['type'=>"repeat", 'specific'=>$specific, 'position'=>$position];
            }
        }
    }

    if ($measureNode->direction)
    {
        $jumps = array("D.C.","D.C. al Fine","D.C. al Coda","D.S.","D.S. al Fine","D.S. al Coda", "Da Coda", "To &lt;sym&gt;coda&lt;/sym&gt;", "To Coda");
        $daCapo = array("D.C.","D.C. al Fine","D.C. al Coda");
        
        foreach ($measureNode->direction as $direction)
        {
            $position = $measureCursor;
            if ($direction->{'direction-type'}->segno)
                $GLOBALS['repeatsJumps'][] = ['type'=>"segno", 'position'=>$position];
            if ($direction->{'direction-type'}->coda)
                $GLOBALS['repeatsJumps'][] = ['type'=>"coda", 'position'=>$position];

            if ($direction->{'direction-type'}->words)
            {
                $string = (string)$direction->{'direction-type'}->words;
                if (in_array($string, $jumps))
                    $GLOBALS['repeatsJumps'][] = ['type'=>$string, 'position'=>$position+$GLOBALS['time']['beats']];
                else
                {                    
                    if (str_contains($string, "D.C.") || str_contains($string, "D.S."))
                        $GLOBALS['repeatsJumps'][] = ['type'=>$string, 'position'=>$position+$GLOBALS['time']['beats']];
                }

                if (in_array($string, $daCapo) || str_contains($string, "D.C."))
                    $GLOBALS['daCapo'] = true;
            }
        }
    }
    return $newMeasureInfo;
}

// HARMONY
// extract harmony node information: chord and fretboard diagram
function extractHarmonyInfo(&$harmonyNode, $cid)
{
    $rootStep = (string) $harmonyNode->root->{'root-step'};
    $rootAlter = ($harmonyNode->root->{'root-alter'}) ? (int) $harmonyNode->root->{'root-alter'} : 0;
    if (!$harmonyNode->bass)
    {
        $bassStep = $rootStep;
        $bassAlter = $rootAlter;
    } else 
    {
        $bassStep = (string) $harmonyNode->bass->{'bass-step'};
        $bassAlter = ($harmonyNode->bass->{'bass-alter'}) ? (int) $harmonyNode->bass->{'bass-alter'} : 0;
    }
    $kind = (string) $harmonyNode->kind;
    if ($kind == "none")
    {
        $rootStep = "";
        $rootAlter = "";
        $bassStep = "";
        $bassAlter = "";
    }
    $degrees = [];
    $chordSymbol = "";
    $fretboardDiagram = [];    
    // extract chord information
if (!$harmonyNode->degree)
{        
    if ($kind === "diminished" && $harmonyNode->kind['use-symbols'] === "yes") // check whether a chord identified as diminished is actually a diminished-seventh chord
        $kind = "diminished-seventh";
} else
{
    if ($kind == "suspended-fourth")
    {
        if ($harmonyNode->kind['text'] == "7sus")
            $kind = "seventh-suspended-fourth";
        elseif ($harmonyNode->kind['text'] == "9sus")
            $kind = "ninth-suspended-fourth";
        else
            $kind = "13th-suspended-fourth";
    }
    foreach ($harmonyNode->degree as $key=>$degreeNode)
    {
        $d = ['degree_value'=>(int) $degreeNode->{'degree-value'}, 'degree_alter'=>(int) $degreeNode->{'degree-alter'}, 'degree_type'=>(string) $degreeNode->{'degree-type'}];              
        if ($d == ['degree_value'=>5, 'degree_alter'=>-1, 'degree_type'=>"alter"]) // check whether a chord with a flat fifth is diminished or half-diminished
        {
            if ($harmonyNode->kind === "minor")
            {
                   $kind = "diminished";
                   continue; // if a chord is identified as diminished or half-diminished, the flat fifth shouldn't be added as a degree
            } elseif (in_array((string) $harmonyNode->kind, ["minor-seventh", "minor-ninth", "minor-11th", "minor-13th"]))
            {
                    $kind = "half-diminished";
                    if (!$harmonyNode->kind === "minor-seventh") // minor-ninth/11th/13th  
                    {
                       $d = ['degree_value'=>9, 'degree_alter'=>0, 'degree_type'=>"add"];
                       $degrees[] = $d;
                    }

                    if (!$harmonyNode->kind === "minor-9th") // minor-11th/13th -> The New Real Book
                    {
                       $d = ['degree_value'=>11, 'degree_alter'=>0, 'degree_type'=>"add"];
                       $degrees[] = $d;
                    }   
                   continue;
            } 
        } 
        
        if (in_array($kind, ["seventh-suspended-fourth", "ninth-suspended-fourth", "13th-suspended-fourth"]))
        {
            if (($d==['degree_value'=>7, 'degree_alter'=>0, 'degree_type'=>"add"]) || ($d==['degree_value'=>9, 'degree_alter'=>0, 'degree_type'=>"add"]) || ($d==['degree_value'=>11, 'degree_alter'=>0, 'degree_type'=>"add"])|| ($d==['degree_value'=>13, 'degree_alter'=>0, 'degree_type'=>"add"]))
                continue;
        }
        
        $degrees[] = $d;
    } 
}
    $parentheses = ($harmonyNode->kind['parentheses-degrees']) ? (string) $harmonyNode->kind['parentheses-degrees'] : null;
    $chordSymbol = chordSymbol($kind, $parentheses, $degrees);
    // check whether $chordSymbol is in the system.
    $symbolSql = "SELECT * FROM `chordsymbol` WHERE `symbol` = '".$chordSymbol."';";
    $checkSymbolQuery = queryRecordExist($cid, $symbolSql);
    if ($checkSymbolQuery['status']=="ok")
    {
        if ($checkSymbolQuery['msg'] !== 1)
        {
            $addOns = extractAddOnsFromChordSymbol($chordSymbol, $kind);
            $addOnsSql = "SELECT * FROM `structure_add_ons` WHERE `addOns` = '".$addOns["addOns"]."';";
            $checkAddOnsQuery = queryRecordExist($cid, $symbolSql);
            if ($checkAddOnsQuery['status']=="ok")
            {
                if ($checkSymbolQuery['msg']==1)
                {
                    $newChordSymbolSql = "INSERT INTO `chordsymbol`(`symbol`, `kind`, `addOns`) VALUES ('".$chordSymbol."','".$kind."','".$addOns['addOns']."');";
                    $insertNewChordSymbol = queryDb($cid, $newChordSymbolSql);
                    if ($insertNewChordSymbol['status'] !== "ok")
                    {
                        // handle error
                    }
                } else
                {
                    $values = "";
                    foreach ($addOns as $key=>$description)
                    {
                        if ($key=="sus")
                        {
                            if ($description=="major")
                                $values .= "('".$addOns['addOns']."','3','major','subtract'), ";
                            else
                                $values .= "('".$addOns['addOns']."','3','none','subtract'), ";
                            
                            $values .= "('".$addOns['addOns']."','4','perfect','add'), ";
                        } elseif ($key=="add")
                        {
                            foreach ($description as $degreeNumber)
                                $values .= "('".$addOns['addOns']."','".$degreeNumber."','".degreeAlterTranslate(['degree_value'=>$degreeNumber, 'degree_alter'=>0])."','add'), ";
                        } elseif ($key=="omit")
                        {
                            foreach ($description as $degreeNumber)
                                $values .= "('".$addOns['addOns']."','".$degreeNumber."','none','subtract'), ";
                        } else if ($key == "sharp" || $key == "flat")
                        {                            
                            foreach ($description as $degreeNumber)
                                $values .= "('".$addOns['addOns']."','".$degreeNumber."','".degreeAlterTranslate(['degree_value'=>$degreeNumber, 'degree_alter'=>0])."','add'), ";
                        }
                    }
                    $values = rtrim($values, ", ");

                    $addOnsSql ="INSERT INTO `structure_add_ons`(`addOns`, `degreeNumber`, `degreeQuality`, `degreeType`) VALUES ".$values.";";
                    $insertAddOnsQuery = queryDb($cid, $addOnsSql);
                    if ($insertAddOnsQuery['status'] !=="ok")
                    {
                        $errors[] = ['type'=>"insertAddOnsQuery", 'msg'=>$insertAddOnsQuery['msg']];
                        // handle error
                    } else
                    {
                        $newChordSymbolSql = "INSERT INTO `chordsymbol`(`symbol`, `kind`, `addOns`) VALUES ('".$chordSymbol."','".$kind."','".$addOns['addOns']."');";
                        $insertNewChordSymbol = queryDb($cid, $newChordSymbolSql);
                        if ($insertNewChordSymbol['status'] !== "ok")
                        {
                            // handle error
                        }
                    }
                }                
            } else
            {
                // handle error
            }
        }
    } else
        $errors[] = ['type'=>"checkChordSymbol", 'msg'=>$checkSymbol['msg']];

    // extract fretboard diagram (frame node) information
    $diagramId = "";
    if ($harmonyNode->frame)
    { 
        $frame = $harmonyNode->frame;
        $firstFret = ($frame->{'first-fret'}) ? ((int)$frame->{'first-fret'}) : 0;

        $noteSequence = computeNoteSequence($frame);
        // check if diagram exists in db
        $sql = "SELECT `noteSequence` FROM `fretboarddiagram` WHERE `noteSequence`='".$firstFret."_".$noteSequence."';";
        $diagramQuery = queryRecordExist($cid, $sql);
        
        if ($diagramQuery['status'] !== "ok")
            $errors[] = ['type'=>"checkFretboardDiagram", 'msg'=>$diagramQuery['msg']];
        else
        {
            if ($diagramQuery['msg'] == 0) // diagram not in db
            {
                // generate new diagram using noteSequence
                $directory = "../diagrams/";
                $newDiagramPath = newFretboardDiagram($noteSequence, $firstFret, $directory); // creates diagram image file and returns its path
                // add to db
                $sql = "INSERT INTO `fretboarddiagram` (`noteSequence`, `imagePath`) VALUES ('".$firstFret."_".$noteSequence."','".$newDiagramPath."');";
                $newDiagramQuery = queryDb($cid, $sql);
                if ($newDiagramQuery['status']!=="ok")
                {
                    // handle error
                } else
                    $diagramId = $firstFret."_".$noteSequence;
            } else
                $diagramId = $firstFret."_".$noteSequence;
        }
    }
    $writtenHarmony = addslashes(writtenHarmony($rootStep, $rootAlter, $bassStep, $bassAlter, $chordSymbol));
    $harmonyInfo = ['writtenHarmony'=>$writtenHarmony, 'root_step'=>$rootStep, 'root_alter'=>$rootAlter, 'bass_step'=>$bassStep, 'bass_alter'=>$bassAlter, 'chordSymbol'=>$chordSymbol, 'fretboardDiagram'=>$diagramId];
    
    return $harmonyInfo;
}
// returns chord symbol
function chordSymbol($kind, $parentheses=null, &$degrees=[])
{    
    $chordSymbol = "";   
    switch ($kind) 
    {
        // major sth
        case 'major':
            $chordSymbol = '';
            break;
        case 'major-sixth':
            $chordSymbol = '6';
            break;
        case 'major-seventh':
            $chordSymbol = 'ma7';
            break;
        case 'major-ninth':
            $chordSymbol = 'ma9';
            break;
        case 'major-11th':
            $chordSymbol = 'ma11';
            break;
        case 'major-13th':
            $chordSymbol = 'ma13';
            break;
        case 'major-minor':
            $chordSymbol = 'mi(ma7)';
            break;
        // minor sth
        case 'minor':
            $chordSymbol = 'mi';
            break;
        case 'minor-sixth':
            $chordSymbol = 'mi6';
            break;
        case 'minor-seventh':
            $chordSymbol = 'mi7';
            break;
        case 'minor-ninth':
            $chordSymbol = 'mi9';
            break;
        case 'minor-11th':
            $chordSymbol = 'mi11';
            break;
        case 'minor-13th':
            $chordSymbol = 'mi13';
            break;
        // dominant sth
        case 'dominant':
            $chordSymbol = '7';            
            break;
        case 'dominant-ninth':
            $chordSymbol = '9';
            break;
        case 'dominant-11th':
            $chordSymbol = '11';
            break;
        case 'dominant-13th':
            $chordSymbol = '13';
            break;
        // augmented, diminished sth
        case 'augmented':
            $chordSymbol = '+';
            break;
        case 'augmented-seventh':
            $chordSymbol = '+7';
            break;
        case 'diminished':
            $chordSymbol = 'mi(b5)';
            break;
        case 'half-diminished':
            $chordSymbol = 'mi7(b5)';
            break;
        case 'diminished-seventh':
            $chordSymbol = 'o';
            break;
        // others
        case 'none':
            $chordSymbol = 'N.C.';
            break;
        case 'suspended-fourth':
            $chordSymbol = 'sus';
            break;
        case 'suspended-second':
            $chordSymbol = 'sus2';
            break;
        case 'power';
            $chordSymbol = '5';
            break;
        // others not in musicXML standard
        case 'seventh-suspended-fourth':
            $chordSymbol = '7sus';
            break;
        case 'ninth-suspended-fourth':
            $chordSymbol = '9sus';
            break;
        case '13th-suspended-fourth':
            $chordSymbol = '13sus';
            break;
    }

    if (empty($degrees))
        return $chordSymbol;

    if ($parentheses=="yes")
    {
        if ($kind == "major-minor" && count($degrees==1))
        {
            if ($degrees[0] == ['degree_value'=>9, 'degree_alter'=>0, 'degree_type'=>"add"])
            {
                $chordSymbol = "mi9(ma7)";
                return $chordSymbol;
            } // other possible major-minor chords aren't listed in references used in the project
        }
         
        if ($kind == "half-diminished")
        {
            if ((count($degrees) == 1) && $degrees[0]['degree_value'] == 9)
            {
                $chordSymbol = "mi9(b5)";
                return $chordSymbol;
            } 

            if ((count($degrees) == 2) && $degrees[1]['degree_value'] == 11)
            {
                $chordSymbol = "mi11(b5)";
                return $chordSymbol;
            } 
        }
        
        $addedNotes = "";
        foreach ($degrees as $d)
        {
            if ($d['degree_alter'] === 0) // add or subtract (omit) only (no alter)
            {
                if ($d['degree_type']=="subtract")
                    $addedNotes = "omit".$d['degree_value'];
                else
                    $addedNotes = "add".$d['degree_value'];
                continue;
            } elseif ($d['degree_alter'] === 1)
            {
                $addedNotes .= "#".$d['degree_value'];
            } elseif ($d['degree_alter'] === -1)
            {
                $addedNotes .= "b".$d['degree_value'];
            } 
        }
        $chordSymbol .= "(".$addedNotes.")";
        return $chordSymbol;
    } else
    {
        if ($kind == "augmented-seventh")
        {
            if ((count($degrees) == 1) && $degrees[0]['degree_value']==9)
            {
                $chordSymbol = "+9";
                return $chordSymbol;
            } 
        }
        
        if ((count($degrees) == 1) && $degrees[0]['degree_value']==9)
        {
            if ($kind == "major-sixth")
                $chordSymbol = "69";
        
            if ($kind == "minor-sixth")
                $chordSymbol = "mi69";
        }
    }
    return $chordSymbol;
}
// returns writtenHarmony
function writtenHarmony($rootStep, $rootAlter, $bassStep, $bassAlter, $chordSymbol)
{
    $writtenHarmony = $rootStep;
    if ($rootAlter != 0)
        $writtenHarmony .= accidentals($rootAlter);
    
    $writtenHarmony .= $chordSymbol;
    
    if ($bassStep != $rootStep || $bassAlter != $rootAlter)
        $writtenHarmony .= "/".$bassStep.accidentals($bassAlter);
    
    return $writtenHarmony;
}
// returns accidentals as a string
function accidentals($alter)
{
    switch ($alter)
    {
        case 1:
            return "#";
        case 2:            
            return "\u{1D12A}"; // double sharp
        case -1:
            return "b";
        case -2:
            return "bb";
    }
}
// extracts AddOns from chords
function extractAddOnsFromChordSymbol($chordSymbol, $kind)
{
    $trimmedSymbol = "";
    if (in_array($kind, ["major-minor", "diminished", "half-diminished"]))
    {
        $trimmedSymbol = str_replace("mi", "", $chordSymbol);
        $trimmedSymbol = ($kind == "diminished" || $kind == "half-diminished") ? str_replace("b5", "", $trimmedSymbol) : str_replace("ma7", "", $trimmedSymbol);
        $trimmedSymbol = ($kind == "half-diminished") ? str_replace("7", "", $trimmedSymbol) : $trimmedSymbol;
    } elseif (in_array($kind, ["suspended-fourth", "seventh-suspended-fourth", "ninth-suspended-fourth", "13th-suspended-fourth"]))
    {
        if (str_contains($chordSymbol, "7sus"))
        	$trimmedSymbol = str_replace("7sus", "", $chordSymbol);
        elseif (str_contains($chordSymbol, "9sus"))
            $trimmedSymbol = str_replace("9sus", "", $chordSymbol);
        elseif (str_contains($chordSymbol, "13sus"))
            $trimmedSymbol = str_replace("13sus", "", $chordSymbol);
        else
        	$trimmedSymbol = str_replace("sus", "", $chordSymbol);
        	
        $trimmedSymbol = str_replace("(", "", (str_replace(")", "", $trimmedSymbol)));
    } else
    {
    	$trimmedSymbol = str_replace("(", "", str_replace(")", "", str_replace($trimmedSymbol, "", $chordSymbol)));
    }    
    $resultSet = [];
    $resultSet["addOns"] = $trimmedSymbol;
    preg_match_all('~add*\K\d+~', $trimmedSymbol, $matches);
    $resultSet["add"] = $matches[0];
    preg_match_all('~omit*\K\d+~', $trimmedSymbol, $matches);
    $resultSet["omit"] = $matches[0];
    preg_match_all('/b(\d+)/', $trimmedSymbol, $matches);
    $resultSet["flat"] = $matches[1];
    preg_match_all('/#(\d+)/', $trimmedSymbol, $matches);
    $resultSet["sharp"] = $matches[1];

    return $resultSet;
}
// translates degrees into interval quality
function degreeAlterTranslate($degree)
{
    $quality = "";
    if ($degree['degree_value'] == 4 || $degree['degree_value'] == 5 || $degree['degree_value'] == 11)
    {
        if ($degree['degree_alter'] == 0)
            $quality = "perfect";
        elseif ($degree['degree_alter'] == 1)
            $quality = "augmented";
        elseif ($degree['degree_alter'] == -1)
            $quality = "diminished";
    } elseif ($degree['degree_value'] == 6 || $degree['degree_value'] == 9 || $degree['degree_value'] == 13)
    {
        if ($degree['degree_alter'] == 0)
            $quality = "major";
        elseif ($degree['degree_alter'] == 1)
            $quality = "augmented";
        elseif ($degree['degree_alter'] == -1)
            $quality = "minor";
    } elseif ($degree['degree_value'] == 7)
    {
        if ($degree['degree_alter'] == 0)
            $quality = "minor";
        elseif ($degree['degree_alter'] == 1)
            $quality = "major";
        elseif ($degree['degree_alter'] == -1)
            $quality = "diminished";
    }
    return $quality;
}
// returns tonality from fifths value
function getTonality($fifths, $mode="major")
{
    if (in_array($mode, ["major", "ionian", "minor", "aeolian"]))
    {
        switch ($fifths)
        {
            case 0:
                return ($mode=="major" || $mode=="ionian") ? "C" : "Am";
            case 1:
                return ($mode=="major" || $mode=="ionian") ? "G" : "Em";
            case 2:
                return ($mode=="major" || $mode=="ionian") ? "D" : "Bm";
            case 3:
                return ($mode=="major" || $mode=="ionian") ? "A" : "F#m";
            case 4:
                return ($mode=="major" || $mode=="ionian") ? "E" : "C#m";
            case 5:
                return ($mode=="major" || $mode=="ionian") ? "B" : "G#m";
            case 6:
                return ($mode=="major" || $mode=="ionian") ? "F#" : "D#m";
            case 7:
                return ($mode=="major" || $mode=="ionian") ? "C#" : "A#m";
            case -1:
                return ($mode=="major" || $mode=="ionian") ? "F" : "Dm";
            case -2:
                return ($mode=="major" || $mode=="ionian") ? "Bb" : "Gm";
            case -3:
                return ($mode=="major" || $mode=="ionian") ? "Eb" : "Cm";
            case -4:
                return ($mode=="major" || $mode=="ionian") ? "Ab" : "Fm";
            case -5:
                return ($mode=="major" || $mode=="ionian") ? "Db" : "Bbm";
            case -6:
                return ($mode=="major" || $mode=="ionian") ? "Gb" : "Ebm";
            case -7:
                return ($mode=="major" || $mode=="ionian") ? "Cb" : "Abm";
        }
    }
}

// LYRICS
function extractLyrics(&$noteNode)
{
    $noteLyrics=[];
    foreach($noteNode->lyric as $lyricNode)
    {
        $lyricNumber = (int) $lyricNode['number'];
        $syllabic = (string) $lyricNode->syllabic;
        $lyricString = "";
        foreach ($lyricNode->text as $text)
        {
            if ((string) $text === "")
                $text = " ";
            
            if ((string) $text === "")
                $text = "-";    
            $lyricString .= (addslashes((string)$text));
        }
        $noteLyrics[] = ['lyricNumber'=>$lyricNumber,'text'=> $lyricString, 'syllabic'=>$syllabic];
    }
    return $noteLyrics;
}

// FRETBOARD DIAGRAMS
// computes note sequence based on information from framenode
function computeNoteSequence(&$frame)
{
    $numberOfStrings = (int) $frame->{'frame-strings'};
    // $numberOfFrets = $harmonyNode->{'frame-frets'}; // only necessary for printing diagrams with a variable number of frets; this could be a future feature.    
    $firstFret = ($frame->{'first-fret'}) ? ((int)$frame->{'first-fret'}) : 0;
    $noteSequence = [];
    $barre = [];

    foreach($frame->{'frame-note'} as $note)
    {
        $stringNumber = $note->string;
        $fretNumber = $note->fret;
        $noteSequence[(string) $stringNumber] = ($fretNumber != 0) ? ($fretNumber) : "x";        
        if ($note->barre)
        {
            $barreType = (string) $note->barre['type'];
            // $barre[$barreType] = ['stringNumber'=>$stringNumber, 'fret'=>$fretNumber+$firstFret];
            if ($fretNumber < $firstFret)
            {
                $barre[$barreType] = ['stringNumber'=>$stringNumber, 'fret'=>$firstFret];
                $noteSequence[(string) $stringNumber] = $firstFret;
            }
            else
                $barre[$barreType] = ['stringNumber'=>$stringNumber, 'fret'=>$fretNumber+$firstFret];
        }
    }

    $diagramString = "";
    for ($string=0; $string < $numberOfStrings; $string++)
    {
        if (key_exists((string) ($string + 1), $noteSequence))
            $diagramString .= (string) $noteSequence[($string+1)];
        else
        {
            if (!empty($barre))
            {
                if ($barre['start']['stringNumber'] >= ($string + 1) || ($string + 1) <= $barre['stop']['stringNumber'])
                    $diagramString .= (string) ($barre['start']['fret']);
                else
                    $diagramString .= "0";
            } else
                $diagramString .= "0";
        }
        $diagramString .= "-"; 
    }
    $diagramString = rtrim($diagramString, "-");    
    return $diagramString;
}
// creates a new fretboard diagram jpeg
function newFretboardDiagram($noteSequence, $firstFret, $fileDestination)
{
    $imgfile = "../diagrams/guitar.jpg";
    $font = "../diagrams/arial.ttf";
    $text = ".";    
  
    $im = imagecreatefromjpeg($imgfile);
    $x = imagesx($im);
    $y = imagesy($im);
    $fontsize = 100;
    $white = imagecolorallocate($im, 0, 0, 0);
  
    $chords = explode('-', $noteSequence);
  
    if ($firstFret !== 0)
      imagettftext($im, 15, 0, 1, 32, $white, $font, $firstFret);
    else
      imagettftext($im, 15, 0, 1, 32, $white, $font, "");
  
    $add = 0;
    if ($firstFret > 0)
    {
      $add = 30;  
    }
    // chord positions
    $interval6 = ($chords[0] != 0 ? (25 + $add + (intval($chords[0]) - $firstFret) * 30) : 0);
    $interval5 = ($chords[1] != 0 ? (25 + $add + (intval($chords[1]) - $firstFret) * 30) : 0);
    $interval4 = ($chords[2] != 0 ? (25 + $add + (intval($chords[2]) - $firstFret) * 30) : 0);
    $interval3 = ($chords[3] != 0 ? (25 + $add + (intval($chords[3]) - $firstFret) * 30) : 0);
    $interval2 = ($chords[4] != 0 ? (25 + $add + (intval($chords[4]) - $firstFret) * 30) : 0);
    $interval1 = ($chords[5] != 0 ? (25 + $add + (intval($chords[5]) - $firstFret) * 30) : 0);

    // write to image
    imagettftext($im, $fontsize, 0, 01, $interval1, $white, $font, $text);
    imagettftext($im, $fontsize, 0, 18, $interval2, $white, $font, $text);
    imagettftext($im, $fontsize, 0, 36, $interval3, $white, $font, $text);
    imagettftext($im, $fontsize, 0, 53, $interval4, $white, $font, $text);
    imagettftext($im, $fontsize, 0, 70, $interval5, $white, $font, $text);
    imagettftext($im, $fontsize, 0, 86, $interval6, $white, $font, $text);
    /// upload file to server image directory, then return path
    $imagePath = $fileDestination.$firstFret."_".$noteSequence.".jpeg";
    imagejpeg($im, $imagePath);
    ImageDestroy($im);
    return $imagePath;
}
?>