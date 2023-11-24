// input verification
function checkFile()
{
    if (!document.getElementById("songxml").files[0].name)
        return;

    title = document.getElementById("songxml").files[0].name;
    exist = false;

    if (title.endsWith(".musicxml"))
    {
        toRemove = ".musicxml";
        title = title.substring(0, title.length - toRemove.length);
        div = document.getElementById("songList");
        options = div.getElementsByTagName("option");
        
        for (i = 0; i < options.length; i++)
        {
            if (options[i].getAttribute("title") === title)
            {
                console.log(options[i].getAttribute("title"));
                exist = true;
                displaySong(options[i].getAttribute("value"));
                break;
            }
        }
    } else
    {
        document.getElementById("uploadFile").disabled = true;
        return;
    }
    
    if (exist===true)
        setTimeout(function() { confirmDuplicate() }, 500);
    else
        document.getElementById("uploadFile").disabled = false;
}

function confirmDuplicate()
{
    text = "This song is already on the song list!\nAre you sure you want to upload your file?";
    if (confirm(text))
        parseSong();
    else
        document.getElementById("songForm").reset();
}

// form submission
function parseSong()
{
    const formData = new FormData();
    formData.append("songxml", document.getElementById("songxml").files[0]);
    
    $.post ({
        url: "backend/parseSong.php", 
        data: formData,
        processData: false,
        contentType: false
    }).done(function(data) 
    {
        res=JSON.parse(data);
        list = document.getElementById("songList");
        option = document.createElement("OPTION");
        option.title = res['work-title'];
        option.value = res['songId'];
        optionTxt = res['work-title'] + " ("+res['creator'] +")";
        option.innerHTML = optionTxt;
        option.id = option.value;
        list.appendChild(option);
        document.getElementById("searchInput").value = option.title;
        document.getElementById(option.id).setAttribute("onclick", "displaySong(value)");  
        displaySong(option.value);
    }).fail(function()
    {
        alert("Unable to parse the song given in input");
    });
    document.getElementById("songForm").reset();
    document.getElementById("uploadFile").disabled = true;
}

// display features 
function displaySong(songId)
{
    document.getElementById("simplify").value = "On";
    document.getElementById("toggleChords").value = "Hide";
    document.getElementById("simplify").innerHTML = document.getElementById("simplify").value;
    document.getElementById("toggleChords").innerHTML = document.getElementById("toggleChords").value;
    // console.log(songId);
	$.post("frontend/displaySong.php", {songId: songId}, function(data) {
        document.getElementById("songDiv").innerHTML = data;
        document.getElementById("searchInput").value = document.getElementById("work-title").innerHTML;
	});
}

function transpose(direction)
{
    chords = document.getElementsByName("songChords[]");
    simValue = document.getElementById("simplify").value;

    if (direction == "up")
    {
        chords.forEach((element) =>
        {
            if (element.getAttribute("kind") != "none")
            {
                writtenHarmony = "";
                rootInfo = stepUp(element.getAttribute("root-step"), element.getAttribute("root-alter"));
                bassInfo = stepUp(element.getAttribute("bass-step"), element.getAttribute("bass-alter"));

                element.setAttribute("root-step", rootInfo['step']);
                element.setAttribute("root-alter", rootInfo['alter']);

                if (bassInfo['step'])
                {
                    element.setAttribute("bass-step", bassInfo['step']);
                    element.setAttribute("bass-alter", bassInfo['alter']);
                }

                writtenHarmony += rootInfo['step'] + accidentals(rootInfo['alter']);
                if (simValue == "Off")
                    writtenHarmony += (element.getAttribute("simplesymbol")) ? (element.getAttribute("simplesymbol")) : "";
                else
                {
                    writtenHarmony += element.getAttribute("chordsymbol");
                    if (bassInfo['step'])
                        writtenHarmony += "/" + bassInfo['step'] + accidentals(bassInfo['alter']);
                }           
                element.innerHTML = writtenHarmony;
            }
        });
    } else
    {
        chords.forEach((element) =>
        {
            if (element.getAttribute("kind") != "none")
            {
                writtenHarmony = "";
                rootInfo = stepDown(element.getAttribute("root-step"), element.getAttribute("root-alter"));
                bassInfo = stepDown(element.getAttribute("bass-step"), element.getAttribute("bass-alter"));

                element.setAttribute("root-step", rootInfo['step']);
                element.setAttribute("root-alter", rootInfo['alter']);

                if (bassInfo['step'])
                {
                    element.setAttribute("bass-step", bassInfo['step']);
                    element.setAttribute("bass-alter", bassInfo['alter']);
                }

                writtenHarmony += rootInfo['step'] + accidentals(rootInfo['alter']);
                if (simValue == "Off")
                    writtenHarmony += (element.getAttribute("simplesymbol")) ? (element.getAttribute("simplesymbol")) : "";
                else
                {
                    writtenHarmony += element.getAttribute("chordsymbol");
                    if (bassInfo['step'])
                        writtenHarmony += "/" + bassInfo['step'] + accidentals(bassInfo['alter']);
                }
                element.innerHTML = writtenHarmony;
            }
        });
    }
}

function stepUp(step, alter)
{
    notes = ["A", "B", "C", "D", "E", "F", "G"];
    stepIndex = notes.indexOf(step);

    if (step == 'E' && alter == 1)
    {
        return {'step': "F", 'alter': 1};
    } else if (step == 'B' && alter == 1)
    {
        return {'step': "C", 'alter': 1};
    }

    if (alter > 0)
    {
        step = notes[(stepIndex + 1) % 7];
        alter = 0;
    } else if (alter < 0)
    {
        step = notes[stepIndex];
        alter = 0;
    } else
    {
        step = notes[stepIndex];
        alter = 1;
    }

    return {'step': step, 'alter': alter};
}

function stepDown(step, alter)
{
    notes = ["A", "B", "C", "D", "E", "F", "G"];
    stepIndex = notes.indexOf(step);

    if (step == 'E' && alter == -1)
    {
        return {'step': "D", 'alter': 0};
    } else if (step == 'B' && alter == -1)
    {
        return {'step': "A", 'alter': 0};
    }

    if (alter > 0)
    {
        step = notes[stepIndex];
        alter = 0;
    } else if (alter < 0)
    {
        if ((stepIndex - 1 < 0))
        {
            step = notes[((stepIndex + (stepIndex-1)) % 7) + 7];
            alter = 0;
        } else
        {
            step = notes[(stepIndex -1) % 7];
            alter = 0;
        }
    } else
    {
        step = notes[stepIndex];
        alter = -1;
    }

    return {'step': step, 'alter': alter};
}

function accidentals(alter) 
{
    switch (alter)
    {
        case 0:
            return "";
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

function toggleChords()
{
    chordLyrics = document.getElementsByName("chord-lyrics[]");
    for (let index = 0; index < chordLyrics.length; index++)
    {
        a = chordLyrics[index].textContent.replaceAll(/\s+/g, '');
        z = chordLyrics[index].children;
        for (let j = 0; j < z.length; j++)
        {
            z[j].hidden = !z[j].hidden;
            if (a.replace(z[j].textContent, '').length === 0 && z[j].hidden)
                chordLyrics[index].style.width = 0;
            else
                chordLyrics[index].style.width = "auto";
        }
    }  
    toggle = document.getElementById("toggleChords");
    toggle.value = (toggle.value == "Hide") ? "Show" : "Hide";
    toggle.innerHTML = toggle.value;
}

function fontSize(modifier)
{
    chords = document.getElementsByName("songChords[]");
    lines =  document.getElementsByName("lines[]");

    chordFontSize = (chords[0].style.fontSize) ? (chords[0].style.fontSize) : window.getComputedStyle(chords[0]).getPropertyValue('font-size');
    chordFontSize = Number(chordFontSize.replace(/\D/g, ""));

    lineFontSize = (lines[0].style.fontSize) ? (lines[0].style.fontSize) : window.getComputedStyle(lines[0]).getPropertyValue('font-size');
    lineFontSize = Number(lineFontSize.replace(/\D/g, ""));

    if (modifier == "+")
    {
        cSize = (chordFontSize) < 40 ? chordFontSize + 2 : 40;
        fSize = (lineFontSize < 40) ? lineFontSize + 2 : 40;
    } else
    {
        cSize = (chordFontSize > 10) ? chordFontSize - 2 : 10;
        fSize = (lineFontSize > 10) ? chordFontSize - 2 : 10;
    }
    lines.forEach((element) =>
    {
        element.style.fontSize = fSize.toString()+"px";
    });
    chords.forEach((element) =>
    {
        element.style.fontSize = cSize.toString()+"px";
    });
}

function simplify()
{    
    chords = document.getElementsByName("songChords[]");
    sim = document.getElementById("simplify");
    
    major = ["major", "major-sixth", "major-seventh", "major-ninth", "major-11th", "major-13th"];
    minor = ["minor", "minor-sixth", "minor-seventh", "minor-ninth", "minor-11th", "minor-13th", "major-minor"];
    dominant = ["dominant", "dominant-ninth", "dominant-11th", "dominant-13th"];
    augmented = ["augmented", "augmented-seventh"];
    diminished = ["diminished", "half-diminished", "diminished-seventh"];

    chords.forEach((element) =>
    {
        if (sim.value == "On")
        {
            if (!element.getAttribute("simplesymbol"))
            {                
                symbol = (element.getAttribute("chordsymbol")) ? (element.getAttribute("chordsymbol")): "";
                kind = element.getAttribute("kind");

                if (symbol.includes("sus"))
                {
                    if (symbol.includes("sus2"))
                        symbol = "sus2";
                    else
                        symbol = "sus";
                } 
                else if (symbol.includes("N.C."))
                    symbol = "N.C.";                
                else if ((symbol.includes("#5")))
                    symbol = "+";
                else 
                {
                    if ((major.includes(kind)))
                        symbol = "";
                    else if ((minor.includes(kind)))
                        symbol = "mi";
                    else if ((dominant.includes(kind)))
                        symbol = "7";
                    else if ((augmented.includes(kind)))
                        symbol = "+";
                    else if ((diminished.includes(kind)))
                        symbol = "mi(b5)";
                }
                element.setAttribute("simplesymbol", symbol);
            }
            symbol = (element.getAttribute("simplesymbol")) ? (element.getAttribute("simplesymbol")): "";
            root = element.getAttribute("root-step");
            alter = accidentals(Number(element.getAttribute("root-alter")));
            element.innerHTML = root + alter + symbol;
        } else
        {
            symbol = (element.getAttribute("chordsymbol")) ? (element.getAttribute("chordsymbol")): "";
            root = element.getAttribute("root-step");
            rootAlter = accidentals(Number(element.getAttribute("root-alter")));
            bass = (element.getAttribute("bass-step")) ? (element.getAttribute("bass-step")) : root;
            bassAlter = (element.getAttribute("bass-alter")) ? (element.getAttribute("bass-alter")) : rootAlter;

            writtenHarmony = root;
            if (rootAlter != 0)
                writtenHarmony += rootAlter;
            
            writtenHarmony += symbol;
            
            if (bass != root || bassAlter != rootAlter)
                writtenHarmony += "/" + bass + accidentals(Number(bassAlter));
            
            element.innerHTML = writtenHarmony;
        }
    });

    sim.value = (sim.value == "On") ? "Off" : "On";
    sim.innerHTML = sim.value;    
}

function filterFunction()
{
    input = document.getElementById("searchInput");
    filter = input.value.toUpperCase();
    div = document.getElementById("songList");
    options = div.getElementsByTagName("option");
    for (i = 0; i < options.length; i++)
    {
        txtValue = options[i].textContent || options[i].innerText;
        if (txtValue.toUpperCase().indexOf(filter) > -1)
            options[i].style.display = "";
        else
            options[i].style.display = "none";
    }
}
