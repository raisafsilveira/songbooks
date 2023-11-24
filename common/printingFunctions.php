<?php
function wrapChord($chord, $lyrics="&nbsp", $closeDiv=true)
{
   $diagram = ($chord['imagePath'] !== "") ? (substr($chord['imagePath'], 3)) : "";

   if (($chord['root-step'] !== $chord['bass-step']) || ($chord['root-alter'] !==  $chord['bass-alter']))
   {
      $wrapChord = "<div class='chord-lyrics' name='chord-lyrics[]'>
      <span class='chord' name='songChords[]' root-step='".$chord['root-step']."' root-alter='".$chord['root-alter']."' bass-step='".$chord['bass-step']."' bass-alter='".$chord['bass-alter']."' chordsymbol='".$chord['chordsymbol']."' kind='".$chord['kind']."'>".$chord['writtenHarmony'];
   } else
   {
      $wrapChord = "<div class='chord-lyrics' name='chord-lyrics[]'>
      <span class='chord' name='songChords[]' root-step='".$chord['root-step']."' root-alter='".$chord['root-alter']."' chordsymbol='".$chord['chordsymbol']."' kind='".$chord['kind']."'>".$chord['writtenHarmony'];
   }

   $wrapChord .= ($diagram !== "") ? "<span class='chord-diagram' name='diagrams[]'><img src='".$diagram."'></img></span></span>".$lyrics : "</span>".$lyrics;
   $wrapChord .= ($closeDiv) ? "</div>" : "";
   return $wrapChord;
}

function printLyrics($segmentLyrics, $previousSegment=[])
{
   $newLine = "</div><div class='line' name='lines[]'>";
   $span = (empty($previousSegment['span'])) ? false : true;
   $div = (empty($previousSegment['div'])) ? false : true;
   $lastSyllabic = (empty($previousSegment['lastSyllabic'])) ? "single" : ($previousSegment['lastSyllabic']);
   $lyricsLastLength = (empty($previousSegment['lyricsLastLength'])) ? 0 : ($previousSegment['lyricsLastLength']);
   $harmonyLastLength = (empty($previousSegment['harmonyLastLength'])) ? 0 : ($previousSegment['harmonyLastLength']);
   
   foreach ($segmentLyrics as $key=>$lyricsInfo)
   {
      $text = "";
      if (str_contains($lyricsInfo['text'], "|"))
      {
         $text = explode( "|", $lyricsInfo['text']);
         if ($div && !$span)
         {
            if ($lastSyllabic == "begin" || $lastSyllabic == "middle")
            {
               if ($lyricsLastLength <= $harmonyLastLength)
               {
                  echo str_pad("", $harmonyLastLength+1 - $lyricsLastLength, "___");
                  $lyricsLastLength = 0;
                  $harmonyLastLength = 0;
               }
               echo $text[0]."&nbsp</div></span>";
            } else
               echo "</div><span>".$text[0]."&nbsp</span>";
         } elseif (!$div && $span)
         {
            if ($lastSyllabic == "begin" || $lastSyllabic == "middle")
               echo $text[0]."&nbsp</span></span>";
            else
               echo "</span><span>".$text[0]."&nbsp</span>";
         } else
         {
            echo "<span>".$text[0]."&nbsp</span>";
            if ($lastSyllabic == "begin" || $lastSyllabic == "middle")
               echo "</span>";
         }
         echo $newLine;
         $div = false;
         if ($text[1] != "")
         {
            if ($lyricsInfo['syllabic'] == "middle")
            {
               echo "<span class='sticky'><span>".$text[1];
               $span = true;
            } elseif ($lyricsInfo['syllabic'] == "end")
            {
               echo "<span>".$text[1]."&nbsp<span>";
               $span = false;
            }            
            $lyricsInfo['text'] = $text[1];
         }
      } else
      {
         if ($div && !$span)
         {            
               if ($lyricsLastLength <= $harmonyLastLength)
               {
                  echo str_pad("", $harmonyLastLength+1 - $lyricsLastLength, "___");
                  $lyricsLastLength = 0;
                  $harmonyLastLength = 0;
               }

               if ($lyricsInfo['syllabic'] == "begin")
               {
                  echo "</div><span class='sticky'><span>".$lyricsInfo['text'];
                  $div = false;
                  $span = true;
               } elseif ($lyricsInfo['syllabic'] == "middle")
               {
                  echo $lyricsInfo['text'];
               } else
               {
                  echo $lyricsInfo['text']."&nbsp</div>";
                  if ($lyricsInfo['syllabic'] == "end")
                     echo "</span>";
                  $div = false;
               }
         } elseif (!$div && $span)
         {
            if ($lyricsInfo['syllabic'] == "begin")
               echo "</span><span class='sticky'><span>".$lyricsInfo['text'];
            elseif ($lyricsInfo['syllabic'] == "middle")
               echo $lyricsInfo['text'];
            else
            {
               echo $lyricsInfo['text']."&nbsp</span>";
               if ($lyricsInfo['syllabic'] == "end")
                  echo "</span>";
               $span = false;
            }
         } else
         {
            if ($lyricsInfo['syllabic'] == "begin")
            {
               echo "<span class='sticky'><span>".$lyricsInfo['text'];
               $span = true;
            } elseif ($lyricsInfo['syllabic'] == "middle")
            {
               echo "<span>".$lyricsInfo['text'];
               $span = true;
            } else
            {
               echo "<span>".$lyricsInfo['text']."&nbsp</span>";
               if ($lyricsInfo['syllabic'] == "end")
                  echo "</span>";
            }
         }
      }
      
      $lastSyllabic = $lyricsInfo['syllabic'];
   }
   return (['span'=> $span, 'div'=> $div, 'lastSyllabic'=> $lastSyllabic, 'lyricsLastLength'=>$lyricsLastLength, 'harmonyLastLength'=>$harmonyLastLength]);
}

function printHarmony($segmentHarmony, $previousSegment=[])
{
   $span = (empty($previousSegment['span'])) ? false : true;
   $div = (empty($previousSegment['div'])) ? false : true;
   $lastSyllabic = (empty($previousSegment['lastSyllabic'])) ? "single" : ($previousSegment['lastSyllabic']);
   $lyricsLastLength = (empty($previousSegment['lyricsLastLength'])) ? 0 : ($previousSegment['lyricsLastLength']);
   $harmonyLastLength = (empty($previousSegment['harmonyLastLength'])) ? 0 : ($previousSegment['harmonyLastLength']);

   foreach ($segmentHarmony as $key=>$harmonyInfo)
      echo wrapChord($harmonyInfo);

   return (['span'=> $span, 'div'=> $div, 'lastSyllabic'=> $lastSyllabic, 'lyricsLastLength'=>$lyricsLastLength, 'harmonyLastLength'=>$harmonyLastLength]);
}

function printSongSegment($segmentLyrics, $segmentHarmony, $previousSegment=[])
{
   $lyricsLastLength = 0;
   $harmonyLastLength = 0;
   $newLine = "</div><div class='line' name='lines[]'>";
   $span = (empty($previousSegment['span'])) ? false : true;
   $div = (empty($previousSegment['div'])) ? false : true;
   $lastSyllabic = (empty($previousSegment['lastSyllabic'])) ? "single" : ($previousSegment['lastSyllabic']);
   $justLyrics = false;
   $justHarmony = false;
   
   $hi = 0;
   $li = 0;
   $maxHarmony = count($segmentHarmony);
   $maxLyrics = count($segmentLyrics);

   for ($harmonyIndex=$hi; $harmonyIndex < $maxHarmony; $harmonyIndex++) 
   {
      $harmonyPos = $segmentHarmony[$harmonyIndex]['absolutePosition'];
      for ($lyricsIndex=$li; $lyricsIndex < $maxLyrics; $lyricsIndex++)
      {
         $lyricPos = $segmentLyrics[$lyricsIndex]['absolutePosition'];
         if ($harmonyPos < $lyricPos)
         {
            if ($div && !$span)
            {
               if ($lyricsLastLength <= $harmonyLastLength)
               {
                  echo str_pad("", $harmonyLastLength - $lyricsLastLength, "___");
                  $lyricsLastLength = 0;
                  $harmonyLastLength = 0;
               }
               echo "</div>";
            }               
            elseif (!$div && $span)
               echo "</span>";

            if ($lastSyllabic == "middle" || $lastSyllabic == "begin")
            {
               echo (wrapChord($segmentHarmony[$harmonyIndex], '', false));
               $div = true;
               $span = false;
            } else
            {
               echo (wrapChord($segmentHarmony[$harmonyIndex]));
               $div = false;
               $span = false;
            }
            $previousSegment = ['span'=> $span, 'div'=> $div, 'lastSyllabic'=> $lastSyllabic, 'lyricsLastLength'=>$lyricsLastLength, 'harmonyLastLength'=>$harmonyLastLength];
            $hi++;
            if ($hi >= $maxHarmony)
               $justLyrics = true;            
            break;               
         } elseif ($harmonyPos > $lyricPos)
         {
            $text = "";
            if (str_contains($segmentLyrics[$lyricsIndex]['text'], "|"))
            {
               $text = explode( "|", $segmentLyrics[$lyricsIndex]['text']);
               if ($div && !$span)
               {
                  if ($lastSyllabic == "begin" || $lastSyllabic == "middle")
                     echo $text[0]."&nbsp</div></span>";
                  else
                     echo "</div><span>".$text[0]."&nbsp</span>";
                  
                  $div = false;    
               } elseif (!$div && $span)
               {
                  if ($lastSyllabic == "begin" || $lastSyllabic == "middle")
                     echo $text[0]."&nbsp</span></span>";
                  else
                     echo "</span><span>".$text[0]."&nbsp</span>";
                  
                  $span = false;
               } else
               {
                  echo "<span>".$text[0]."&nbsp</span>";
                  if ($lastSyllabic == "begin" || $lastSyllabic == "middle")
                     echo "</span>";
               }
               echo $newLine;
               if ($text[1] != "")
               {
                  if ($segmentLyrics[$lyricsIndex]['syllabic'] == "middle")
                  {
                     echo "<span class='sticky'><span>".$text[1];
                     $span = true;
                  } elseif ($segmentLyrics[$lyricsIndex]['syllabic'] == "end")
                  {
                     echo "<span>".$text[1]."&nbsp</span>";
                  }
               }
            } else
            {
               if ($div && !$span)
               {
                  if ($segmentLyrics[$lyricsIndex]['syllabic'] == "begin")
                  {
                     echo "</div><span class='sticky'><span>".$segmentLyrics[$lyricsIndex]['text'];
                     $span = true;
                     $div = false;
                  } elseif ($segmentLyrics[$lyricsIndex]['syllabic'] == "middle")
                  {
                     echo $segmentLyrics[$lyricsIndex]['text'];
                  } else
                  {
                     echo $segmentLyrics[$lyricsIndex]['text']."&nbsp</div>";
                     if ($segmentLyrics[$lyricsIndex]['syllabic'] == "end")
                        echo "</span>";
                     $div = false;
                  }
               } elseif (!$div && $span)
               {
                  if ($segmentLyrics[$lyricsIndex]['syllabic'] == "begin")
                  {
                     echo "</span><span class='sticky'><span>".$segmentLyrics[$lyricsIndex]['text'];
                  } elseif ($segmentLyrics[$lyricsIndex]['syllabic'] == "middle")
                  {
                     echo $segmentLyrics[$lyricsIndex]['text'];
                  } else
                  {
                     echo $segmentLyrics[$lyricsIndex]['text']."&nbsp</span>";
                     if ($segmentLyrics[$lyricsIndex]['syllabic'] == "end")
                        echo "</span>";
                     $span = false;
                  }
               } else
               {
                  if ($segmentLyrics[$lyricsIndex]['syllabic'] == "begin")
                  {
                     echo "<span class='sticky'><span>".$segmentLyrics[$lyricsIndex]['text'];
                     $span = true;
                  } elseif ($segmentLyrics[$lyricsIndex]['syllabic'] == "middle")
                  {
                     echo "<span>".$segmentLyrics[$lyricsIndex]['text'];
                     $span = true;
                  } else
                  {
                     echo "<span>".$segmentLyrics[$lyricsIndex]['text']."&nbsp</span>";
                     if ($segmentLyrics[$lyricsIndex]['syllabic'] == "end")
                        echo "</span>";
                  }
               }
            }
            $lastSyllabic = $segmentLyrics[$lyricsIndex]['syllabic'];
            $previousSegment = ['span'=> $span, 'div'=> $div, 'lastSyllabic'=> $lastSyllabic, 'lyricsLastLength'=>$lyricsLastLength, 'harmonyLastLength'=>$harmonyLastLength];
            $li++;
            if ($li >= $maxLyrics)
            {
               $justHarmony = true;
               break;
            }
         } elseif ($harmonyPos == $lyricPos)
         {
            $text = "";
            if (str_contains($segmentLyrics[$lyricsIndex]['text'], "|")) 
            {
               $text = explode( "|", $segmentLyrics[$lyricsIndex]['text']);
               if ($div && !$span)
               {
                  if ($lyricsLastLength <= $harmonyLastLength)
                  {
                     echo str_pad("", $harmonyLastLength+1 - $lyricsLastLength, "___");
                     $lyricsLastLength = 0;
                     $harmonyLastLength = 0;
                  }                     
                  echo "</div>";
               }
               elseif (!$div && $span)
                  echo "</span>";
               
               echo wrapChord($segmentHarmony[$harmonyIndex], $text[0]."&nbsp");
               if ($lastSyllabic == "begin" || $lastSyllabic == "middle")
                  echo "</span>";
               
               $div = false;
               $span = false;
               echo $newLine;
               if ($text[1] != "")
               {
                  if ($segmentLyrics[$lyricsIndex]['syllabic'] == "middle")
                  {
                     echo "<span class='sticky'><span>".$text[1];
                     $span = true;
                  } elseif ($segmentLyrics[$lyricsIndex]['syllabic'] == "end")
                  {
                     echo "<span class='sticky'><span>".$text[1]."&nbsp</span>";
                  }
               }
            } else
            {
               if ($div && !$span)
               {
                  if ($segmentLyrics[$lyricsIndex]['syllabic'] == "begin")
                     echo "</div><span class='sticky'>".wrapChord($segmentHarmony[$harmonyIndex], $segmentLyrics[$lyricsIndex]['text'], false);
                  elseif ($segmentLyrics[$lyricsIndex]['syllabic'] == "middle")
                  {
                     if ($lyricsLastLength <= $harmonyLastLength)
                     {
                        echo str_pad("", $harmonyLastLength+1 - $lyricsLastLength, "___");
                        $lyricsLastLength = 0;
                        $harmonyLastLength = 0;
                     }
                     echo "</div>".wrapChord($segmentHarmony[$harmonyIndex], $segmentLyrics[$lyricsIndex]['text'], false);
                  } elseif ($segmentLyrics[$lyricsIndex]['syllabic'] == "end")
                  {
                     if ($lyricsLastLength <= $harmonyLastLength)
                     {
                        echo str_pad("", $harmonyLastLength+1 - $lyricsLastLength, "___");
                        $lyricsLastLength = 0;
                        $harmonyLastLength = 0;
                     }
                     echo "</div>".wrapChord($segmentHarmony[$harmonyIndex], $segmentLyrics[$lyricsIndex]['text']."&nbsp")."</span>";
                     $div = false;                     
                  } else
                  {
                     echo "</div>".wrapChord($segmentHarmony[$harmonyIndex], $segmentLyrics[$lyricsIndex]['text']."&nbsp");
                     $div = false;
                  }
               } elseif (!$div && $span)
               {
                  if ($segmentLyrics[$lyricsIndex]['syllabic'] == "begin")
                  {
                     echo "</span><span class='sticky'>".wrapChord($segmentHarmony[$harmonyIndex], $segmentLyrics[$lyricsIndex]['text'], false);
                     $div = true;
                     $span = false;
                  } elseif ($segmentLyrics[$lyricsIndex]['syllabic'] == "middle")
                  {
                     echo "</span>".wrapChord($segmentHarmony[$harmonyIndex], $segmentLyrics[$lyricsIndex]['text'], false);
                     $div = true;
                     $span = false;
                  } else
                  {
                     echo "</span>".wrapChord($segmentHarmony[$harmonyIndex], $segmentLyrics[$lyricsIndex]['text']."&nbsp");
                     if ($segmentLyrics[$lyricsIndex]['syllabic'] == "end")
                        echo "</span>";
                     $span = false;
                  }
               } elseif (!$div && !$span)
               {
                  if ($segmentLyrics[$lyricsIndex]['syllabic'] == "begin")
                  {
                     echo "<span class='sticky'>".wrapChord($segmentHarmony[$harmonyIndex], $segmentLyrics[$lyricsIndex]['text'], false);
                     $div = true;
                  } elseif ($segmentLyrics[$lyricsIndex]['syllabic'] == "middle")
                  {
                     echo (wrapChord($segmentHarmony[$harmonyIndex], $segmentLyrics[$lyricsIndex]['text'], false));
                     $div = true;
                  } else
                  {
                     echo (wrapChord($segmentHarmony[$harmonyIndex], $segmentLyrics[$lyricsIndex]['text']."&nbsp"));
                     if ($segmentLyrics[$lyricsIndex]['syllabic'] == "end")
                        echo "</span>";
                  }
               }
            }
            $lastSyllabic = $segmentLyrics[$lyricsIndex]['syllabic'];
            $previousSegment = ['span'=> $span, 'div'=> $div, 'lastSyllabic'=> $lastSyllabic, 'lyricsLastLength'=>$lyricsLastLength, 'harmonyLastLength'=>$harmonyLastLength];
            $hi++;
            $li++;

            $harmonyLastLength = mb_strlen($segmentHarmony[$harmonyIndex]['writtenHarmony']);
            $lyricsLastLength = mb_strlen($segmentLyrics[$lyricsIndex]['text']);

            if ($hi >= $maxHarmony && $li < $maxLyrics) 
               $justLyrics = true;
            elseif ($hi < $maxHarmony && $li >= $maxLyrics)
               $justHarmony = true;

            break;
         }
      }
      if ($justLyrics || $justHarmony)
         break;
   }

   if ($justLyrics)
      $previousSegment = printLyrics(array_slice($segmentLyrics, $li), $previousSegment);
   elseif ($justHarmony)
      $previousSegment = printHarmony(array_slice($segmentHarmony, $hi), $previousSegment);

   return $previousSegment;
}

/* placeholder function*/
function relativeMinor($key)
{
   switch ($key)
   {
      case "C":
         return "A";
     case "G":
         return "E";
     case "D":
         return "B";
     case "A":
         return "F#";
     case "E":
         return "C#";
     case "B":
         return "G#";
      case "Cb":
         return "Ab";
     case "F#":
         return "D#";
      case "Gb":
         return "Eb";
     case "C#":
         return "A#";
     case "Db":
         return "Bb";
     case "Ab":
         return "F";
     case "Eb":
         return "C";
     case "Bb":
         return "G";
     case "F":
         return "D";
   }
}
?>
