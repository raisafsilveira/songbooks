<?php
$segmentCounter = 0;
$segmentStart = 0;
$songSegments = [];
//
$segno = false;
$segnoStart = 0;
$segnoSegment = [];
//
$coda = false;
$codaPosition = 0;
$codaSegment = [];
//
$daCapoSegment = [];
$dalSegnoSegment = [];
$alCodaPosition = 0;
$dalSegnoPosition = 0;
$goToCoda = false;
$goToSegno = false;
$finePosition = 0;
//
$ending = false;
$endingStart = 0;
$endingStop = 0;
$endingNumber = 0;
$endingReference = 0;
$endingSegment = [];
//
$repeat = false;
$repeatStart = 0;
$lastRepeat = 0;

foreach ($repeatsJumps as $rj)
{
    // marks the start of a segment
    if ($rj['type'] === "segno")
    {
        $segnoStart = $rj['position'];
        $segno = true;
    }

    if ($rj['type'] === "coda")
    {
        $codaStart = $rj['position'];
        $coda = true;
    }
    
    // marks the return to a previous segment (with or without a jump at its end)
    if ($rj['type'] === "D.C." || str_contains($rj['type'], "D.C."))
    {
        $daCapoPosition = $rj['position'];
        if ($repeat == false) // add to songSegments the part that came before its indication in the score
            if ($segmentStart !== $rj['position'])
                $songSegments[] =['fromPosition'=>$segmentStart, 'toPosition'=>$rj['position']];
    
            $daCapoSegment[] = ['fromPosition'=>$segmentStart, 'toPosition'=>$rj['position']];
        
        if ($rj['type'] === "D.C.")
        {
            foreach ($daCapoSegment as $dc)
                $songSegments[] = ['fromPosition'=>$dc['fromPosition'], 'toPosition'=>$dc['toPosition']];
        } elseif ($rj['type'] === "D.C. al Fine" || $rj['type'] === "D.C. al Coda")
        {
            $toPosition = ($rj['type'] === "D.C. al Fine") ? $finePosition : $alCodaPosition;
            foreach ($daCapoSegment as $dc)
            {
                if ($dc['toPosition'] > $toPosition)
                {
                    $songSegments[] = ['fromPosition'=>$dc['fromPosition'], 'toPosition'=>$toPosition];
                    break;
                }
                $songSegments[] = ['fromPosition'=>$dc['fromPosition'], 'toPosition'=>$dc['toPosition']];
            }     
        } else // D.C. X times, D.C. X times and Coda
        {
            $int = (int) filter_var($rj['type'], FILTER_SANITIZE_NUMBER_INT);
            for ($i = 0; $i < $int-1; $i++)
                foreach ($daCapoSegment as $dc)
                    $songSegments[] = ['fromPosition'=>$dc['fromPosition'], 'toPosition'=>$dc['toPosition']];

            if (str_contains($rj['type'], "and Coda"))
                $songSegments[] = ['fromPosition'=>$dc['fromPosition'], 'toPosition'=>$alCodaPosition];
            else
                $songSegments[] = ['fromPosition'=>$dc['fromPosition'], 'toPosition'=>$dc['toPosition']];
        }
        
        $segmentStart = $rj['position'];
        $daCapo = false;
        $goToCoda = (str_contains($rj['type'], "Coda")) ? true : false;   
    }
    
    if ($rj['type'] === "D.S." || str_contains($rj['type'], "D.S.") )
    {
        $dalSegnoPosition = $rj['position'];
        if ($repeat == false) // add to songSegments the part that came before D.S. in the score
            if ($segmentStart !== $rj['position'])
                $songSegments[] = ['fromPosition'=>$segmentStart, 'toPosition'=>$rj['position']]; 

        if ($rj['type'] === "D.S.")
        {
            if ($segnoStart > $segmentStart)
                $dalSegnoSegment[] = ['fromPosition'=>$segnoStart, 'toPosition'=>$rj['position']];
            else
                $dalSegnoSegment[] = ['fromPosition'=>$segmentStart, 'toPosition'=>$rj['position']];
            
            foreach ($dalSegnoSegment as $ds)
                $songSegments[] = ['fromPosition'=>$ds['fromPosition'], 'toPosition'=>$ds['toPosition']];

        } elseif ($rj['type'] === "D.S. al Fine" || $rj['type'] === "D.S. al Coda")
        {
            $toPosition = ($rj['type'] === "D.S. al Fine") ? $finePosition : $alCodaPosition;
            if ($segnoStart > $segmentStart)
                if ($segnoStart < $toPosition)
                    $dalSegnoSegment[] = ['fromPosition'=>$segnoStart, 'toPosition'=>$toPosition];
            else
                if ($segmentStart < $toPosition)
                    $dalSegnoSegment[] = ['fromPosition'=>$segmentStart, 'toPosition'=>$toPosition];

            foreach ($dalSegnoSegment as $ds)
            {                
                if ($ds['toPosition'] > $toPosition)
                {
                    $songSegments[] = ['fromPosition'=>$ds['fromPosition'], 'toPosition'=>$toPosition];
                    break;
                }
                $songSegments[] = ['fromPosition'=>$ds['fromPosition'], 'toPosition'=>$ds['toPosition']];
            }     
        } else // D.S. X times, D.S. X times and Coda
        {
            $int = (int) filter_var($rj['type'], FILTER_SANITIZE_NUMBER_INT);
            for ($i = 0; $i < $int-1; $i++)
                foreach ($dalSegnoSegment as $ds)
                    $songSegments[] = ['fromPosition'=>$ds['fromPosition'], 'toPosition'=>$ds['toPosition']];

            if (str_contains($rj['type'], "and Coda"))
                $songSegments[] = ['fromPosition'=>$segmentStart, 'toPosition'=>$alCodaPosition];               
            else
                $songSegments[] = ['fromPosition'=>$ds['fromPosition'], 'toPosition'=>$ds['toPosition']];
        }
        $segmentStart = $rj['position'];
        $segno = false;
        $goToCoda = (str_contains($rj['type'], "Coda")) ? true : false;
    }

    // marks the end of a segment
    if ($rj['type'] === "Fine" || str_contains($rj['type'], "al Fine"))
        $finePosition = $rj['position'];
    
    if ($rj['type'] === "To Coda" || $rj['type'] === "Da Coda" || str_contains($rj['type'], "al Coda") || str_contains($rj['type'], "and Coda"))
        $alCodaPosition = $rj['position'];

    // repeats and endings
    if ($rj['type'] === "ending")
    {
        if ($rj['specific'] === "start")
        {
            $ending = true;
            $endingStart = $rj['position'];
            $endingNumber = $rj['number'];
        } 
        elseif ($rj['specific'] === "stop")
        {
            $endingStop = $rj['position'];
        } else // discontinue
        {
            $ending = false;
        }
    }
    
    if ($rj['type'] === "repeat")
    {
        if ($rj['specific'] === "forward")
        {
            if ($rj['position'] == 0)
                continue;                
                
            if ($repeat == false || $segmentCounter === 0)
            {
                if ($segmentStart !== $rj['position'])
                    $songSegments[] = ['fromPosition'=>$segmentStart, 'toPosition'=>$rj['position']];
                
                if ($daCapo === true)
                    $daCapoSegment[] = ['fromPosition'=>$segmentStart, 'toPosition'=>$rj['position']];
                
                if ($segno === true)
                    $dalSegnoSegment[] = ['fromPosition'=>$segmentStart, 'toPosition'=>$rj['position']];
                
                if ($coda === true)
                    $codaSegment[] = ['fromPosition'=>$segmentStart, 'toPosition'=>$rj['position']];
            }
            $repeat = true;
            $segmentStart = $rj['position'];
            $segmentCounter++;
        } else // backward
        {
            $lastRepeat = $rj['position'];
            if ($ending === true)
            {             
                if ($endingNumber == '1')
                {
                    if ($segmentStart !== $endingStart)
                        $songSegments[] = ['fromPosition'=>$segmentStart, 'toPosition'=>$rj['position']];
                        
                    $endingSegment = ['fromPosition'=>$segmentStart, 'toPosition'=>$endingStart];
                    $songSegments[] = $endingSegment;
                    if ($daCapo === true)
                        $daCapoSegment[] = $endingSegment;
                    if ($segno === true)
                        $dalSegnoSegment[] = $endingSegment;
                } else
                {
                    $songSegments[] = ['fromPosition'=>$segmentStart, 'toPosition'=>$rj['position']];
                    $songSegments[] = $endingSegment;
                }
                $ending = false;
            } else
            {
                $songSegments[] = ['fromPosition'=>$segmentStart, 'toPosition'=>$rj['position']];
                $songSegments[] = ['fromPosition'=>$segmentStart, 'toPosition'=>$rj['position']];
                if ($daCapo === true)
                    $daCapoSegment[] = ['fromPosition'=>$segmentStart, 'toPosition'=>$rj['position']];
                if ($segno === true)
                    $dalSegnoSegment[] = ['fromPosition'=>$segmentStart, 'toPosition'=>$rj['position']];
                if ($coda === true)
                    $codaSegment[] = ['fromPosition'=>$segmentStart, 'toPosition'=>$rj['position']];
            }                
            $segmentStart = $rj['position'];
            $repeat = false;
            $segmentCounter++;
        }
    }
}

if ($repeat === false && ($lastRepeat != $measureCursor))
    $songSegments[] = ['fromPosition'=>$segmentStart, 'toPosition'=>$measureCursor];
?>