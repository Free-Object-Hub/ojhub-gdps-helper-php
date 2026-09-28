let helperRequest = function(url, data = '') {
    return new Promise((resolve, reject) => {
        let xhr = new XMLHttpRequest();
        let method = 'GET';
        if (data !== '') 
            method = 'POST';

        xhr.open(method, url);
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                resolve(xhr.response);
            }
        };
        //.catch(function(error) {returnError(error+servError)})
        xhr.onerror = function() {
            reject(new Error('Network error'), xhr);
        };

        if (data !== '') {
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.send(data);
        } else {
            xhr.send();
        };
    });
},
getElement = (id) => document.getElementById(id),
ignoreHistory = false,
ignorePaste = false,
ignorePasteModify = val => {
    console.log(val);
    ignorePaste = val;
},
windowDeleteOpen = false,

isFolder = true,
selectedFile = 0;
maxSelectID = 2;
path = '../';
globalRename = ['', '', ''],

windowsId = 0,
newWindowsId = 1,
windowses = {
    w0: ['../', '']
},
megaAlert = (text, waitTime = 3000)=>{
    document.body.insertAdjacentHTML('beforeend', `<div class=ALERT id=alert style=color:white><h1>${text}</h1></div>`,1);
    setTimeout(()=>{
        getElement('alert').remove();
    }, waitTime);
};

// #region окна
getWindow = (id)=>{
    getElement('w'+windowsId).className = 'windowButtonUnselected';
    getElement('w'+id).className = 'windowButtonSelected';

    path = windowses['w'+id][0];
    getElement('pos').innerHTML = path;
    getElement('Window'+windowsId).style.display = 'none';
    getElement('Window'+id).style.display = 'block';
    windowsId = id;
},
openWindow = (id)=>{ // DIDNT WORK, fix later
    let localPath = windowses['w'+id][0];
    let folderCheck = localPath.length - localPath.lastIndexOf('/');
    if (folderCheck == 1)
        return megaAlert('Folders cant open in window mode!')
    document.body.insertAdjacentHTML('beforeend',
        `<div class=window id=window${id}>`+
            `<div class=upperwindow id=upperwindow${id}>`+
            `</div>`+
            `<iframe src=mini.php?${localPath} width=400px height=300px />`+
        `</div>`
    )
    dragElement(id);
},
createWindow = (winPath = '../')=>{
    windowses['w'+newWindowsId] = [winPath, ''];
    getElement('w'+windowsId).className = 'windowButtonUnselected';
    getElement('windowses').insertAdjacentHTML('beforeend',
        `<button onclick="getWindow(${newWindowsId})" ondblclick=openWindow(${newWindowsId}) id=w${newWindowsId} class=windowButtonSelected>${winPath}</button> `
    )

    getElement('Window'+windowsId).style.display = 'none';
    getElement('filemgr').insertAdjacentHTML('beforeend',
        `<div id=Window${newWindowsId}>`+
            `<table id=table${newWindowsId} style=width:100%>`+
                `<tbody id="place${newWindowsId}"></tbody>`+
            `</table>`+
        `</div>`
    )

    windowsId = newWindowsId;
    newWindowsId++;
    if (winPath.endsWith('/'))
        openFolder(winPath);
    else 
        openFile(winPath)
},


dragElementLegacy = (elmnt)=>{
    if (document.getElementById('upper'+elmnt.id)) {
        document.getElementById('upper'+elmnt.id).onmousedown = dragMouseDown;
    } else {
        elmnt.onmousedown = dragMouseDown;
    }
    let posWindowX = 0,
        posWindowY = 0,
        posMouseX = 0,
        posMouseY = 0;

    function dragMouseDown(cursor) {
        cursor = cursor || window.event;
        cursor.preventDefault();

        posMouseX = cursor.clientX;
        posMouseY = cursor.clientY;
        document.onmouseup = closeDragElement;

        document.onmousemove = elementDrag;
    }

    function elementDrag(cursor) {
        cursor = cursor || window.event;
        cursor.preventDefault();

        console.log(cursor.clientX+' '+cursor.clientX)
        
        posWindowX = posMouseX - cursor.clientX;
        posWindowY = posMouseY - cursor.clientY;
        posMouseX = cursor.clientX;
        posMouseY = cursor.clientY;

        elmnt.style.top = (elmnt.offsetTop - posWindowY) + "px";
        elmnt.style.left = (elmnt.offsetLeft - posWindowX) + "px";
    }

    function closeDragElement() {
        document.onmouseup = null;
        document.onmousemove = null;
    }
},
dragElement = function(windowId) {
  let Window = getElement('window'+windowId)
    pos1 = 0, pos2 = 0, pos3 = 0, pos4 = 0,
  startDrag = function(e) {
    e = e || Window.event;
    e.preventDefault();
    pos3 = e.clientX;
    pos4 = e.clientY;
    document.onmouseup = stopDrag;
    document.onmousemove = draggerMove;
  },
  draggerMove = function(e) {
    e = e || Window.event;
    e.preventDefault();

    pos1 = pos3 - e.clientX;
    pos2 = pos4 - e.clientY;
    pos3 = e.clientX;
    pos4 = e.clientY;

    Window.style.top = (Window.offsetTop - pos2) + "px";
    Window.style.left = (Window.offsetLeft - pos1) + "px";
  },
  stopDrag = function() {
    document.onmouseup = null;
    document.onmousemove = null;
  }

  if (getElement(`upperwindow${windowId}`))
    getElement(`upperwindow${windowId}`).onmousedown = startDrag;
  else
    Window.onmousedown = startDrag;
},
setWindow = (id, path, text)=>{
    windowses['w'+id][0] = path;
    windowses['w'+id][1] = text;

    let pathNew = '';
    if (path === '../') {
        pathNew = path;
    } else {
        pathNew = path.slice(path.lastIndexOf('/')+1);
        if (pathNew == '') {
            let pathTemp = path.slice(0, -1)
            pathNew = pathTemp.slice(pathTemp.lastIndexOf('/')+1);
        }
    }
    getElement('w'+id).innerHTML = pathNew;
},
// #endregion
// #region папки
refreshPath = (localPath)=>{
    if (localPath[localPath.length - 1] === '/') {
        openFolder(localPath);
    } else {
        openFile(localPath);
    }
},
renderFolders = (data)=>{
    let html = '<tr><th>File</th><th>Size</th><th>Actions</th></tr>';
    let parsedData = JSON.parse(data);
    let ID = 0;
    parsedData.Folders.forEach(Elem => {
        ID++
        html +=
            `<tr id="select${ID}" path="${Elem[0]}">`+
                `<td id=rename${ID} value="${Elem[1]}" type="folder">`+
                    `<button class="folderfile" onclick="openFolder('${Elem[0]}')">${Elem[1]}</button>`+
                    `<button onclick="renamePre(${ID})">F</button>`+
                `</td>`+
                `<td id="${Elem[1]}"><button class="folderfile" onclick="getFolderSize('${Elem[0]}','${Elem[1]}')"">Folder</button></td>`+
                `<td><button onclick="fileDeleteOptions('RMDIR','${path + Elem[1]}',1)">Remove</button></td>`+
            `</tr>`;
    })
    parsedData.Files.forEach(Elem => {
        ID++
        if (Elem[0].slice(-4) !== '.zip')
            html +=
                `<tr id="select${ID}" path="${Elem[0]}">`+
                    `<td id=rename${ID} value="${Elem[1]}" type="file">`+
                        `<button class="filefile" onclick="openFile('${Elem[0]}')">${Elem[1]}</button>`+
                        `<button onclick="renamePre(${ID})">F</button>`+
                    `</td>`+
                    `<td>${Elem[2]}</td>`+
                    `<td><button onclick="fileDeleteOptions('RMFILE','${Elem[0]}',1)">Delete</button></td>`+
                `</tr>`;
        else
            html +=
                `<tr id="select${ID}" path="${Elem[0]}">`+
                    `<td id=rename${ID} value="${Elem[1]}" type="file">`+
                        `<button class="zipfile" onclick="readZip('${Elem[0]}')">${Elem[1]}</button>`+
                        `<button onclick="renamePre(${ID})">F</button>`+
                    `</td>`+
                    `<td>${Elem[2]}</td>`+
                    `<td><button onclick="fileDeleteOptions('RMFILE','${Elem[0]}',1)">Delete</button></td>`+
                `</tr>`;
    })
    selectedFile = 0;
    maxSelectID = ID + 1;
    return html;
},
openFolder = (dir, token = '')=>{
    path = dir;
    if (!ignoreHistory) {
        history.pushState(null, null, '?'+path);
    } else {
        history.replaceState(null, null, '?'+path);
        ignoreHistory = false;
    }
    ignorePasteModify(false);
    isFolder = true;

    helperRequest('findFiles.php?dir='+dir, token)
        .then(data => {
            replacePath(1);
            let dataNew = renderFolders(data);
            let place = getElement('place'+windowsId);
            place.innerHTML = dataNew;
            getElement('pos').innerHTML = path;
            setWindow(windowsId, path, dataNew);
        })
},
getFolderSize = (path, ID)=>{
    helperRequest('getTotalSize.php?dir=' + path)
        .then(data => {
            getElement(ID).innerHTML = data;
        })
},
drop = (dir)=>{
    let dir2 = dir.slice(0, -1);
    if (/[^\/\.]/.test(dir2)) {
        let lastSlashIndex = dir2.lastIndexOf('/');
        let next = dir2.substring(0, lastSlashIndex);
        openFolder(next+'/');
    } else {
        openFolder(dir+'../');
    }
},
replacePath = (type = 0)=>{
    if (type == 0) {
        getElement('thisIsPath!!').innerHTML = 
        `<input id=pos ondblclick="replacePath(1)" value=${path}>`+
        `<button onclick=refreshPath(getElement('pos').value)>go</button>`;
        ignorePasteModify(true);
    } else {
        getElement('thisIsPath!!').innerHTML = `<span id=pos ondblclick="replacePath()">${path}</span>`;
        if (isFolder)
            ignorePasteModify(false);
    }
},
// #endregion
// #region файлы
openFile = (dir, token = '')=>{
    path = dir;
    if (!ignoreHistory) {
        history.pushState(null, null, '?'+path);
    } else {
        history.replaceState(null, null, '?'+path);
        ignoreHistory = false
    }
    ignorePasteModify(true);
    isFolder = false;

    helperRequest('editFile.php?dir=' + dir, token)
        .then(data => {
            replacePath(1);
            let place = getElement('place'+windowsId),
                html =
                `<textarea wrap=off spellcheck=false id=takeMEE${windowsId} style="width:100%;height:calc(100vh - 250px);min-width:300px">`+data+`</textarea>`+
                `<br><button onclick=saveFile('${path}',${windowsId})>save</button>`;
            place.innerHTML = html;
            getElement('pos').innerHTML = path;
            setWindow(windowsId, path, html);
        })

  //document.body.insertAdjacentHTML('beforeend', `<iframe src="editFile.php?dir=${dir}"></iframe>`);
},
saveFile = (dir, windowId)=>{
    let saveText = getElement('takeMEE'+windowId).value;
    
    let Text = encodeURIComponent(saveText);

    helperRequest('saveFile.php?dir=' + dir, 'file='+Text)
        .then(data=> {
            getElement('takeMEE'+windowId).value = data;
            megaAlert("DONE");
        })
},
fileCreateOptions = (type)=>{
    let impyt = getElement('filename')
    let txt = path + impyt.value
    
    switch (type) {
        case 'MAKEFILE':
            type = 'addFile'
            break
        case 'MAKEDIR':
            type = 'makeDir'
    }
    helperRequest(type + '.php?name=' + txt)
        .then(data => {
            impyt.value = ''
            let place = getElement('place'+windowsId);
            place.innerHTML = renderFolders(data);
        })
},
fileDeleteOptions = (type, pathToFile, alarm = 0)=>{
    switch (type) {
        case 'RMDIR':
            type = 'rmvDir';
            break;
        case 'RMFILE':
            type = 'delFile';
            break;
    }
    if (alarm == 0) {
        getElement('durak').style.display = 'none';
        windowDeleteOpen = false;
        helperRequest(type + '.php?name=' + pathToFile)
            .then(data => {
                let place = getElement('place'+windowsId);
                place.innerHTML = renderFolders(data);
            })
    } else {
        alarmRemove(type, pathToFile);
    }
},
alarmRemove = (type, path)=>{
    windowDeleteOpen = true;
    getElement('durak').style.display = 'block';
    getElement('durak2').setAttribute('onclick', `fileDeleteOptions('${type}','${path}')`);
},
renamePre = (localId = selectedFile)=>{
    ignorePasteModify(true);
    let rename = getElement('rename'+localId);
    let fileName = rename.getAttribute('value');
    let fileType = rename.getAttribute('type');
    let html =
    `<input value="${fileName}" id="renameInput${localId}"> `+
    `<button onclick="rename('${fileName}', ${localId}, '${fileType}')">Y</button> `+
    `<button onclick="rename('${fileName}', ${localId}, '${fileType}', true)">N</button>`;
    globalRename = [fileName, localId, fileType];
    rename.innerHTML = html;
    getElement('renameInput'+localId).focus();
},
rename = (oldName, selectId, type, isDrop = false)=>{
    ignorePasteModify(false);
    let rename = getElement('rename'+selectId);
    let newName = getElement('renameInput'+selectId).value;
    let backFunctions = [];
    switch (type) {
        case 'file':
            backFunctions = ['filefile', `openFile(${path}${oldName})`, `openFile(${path}${newName})`];
            break;
        case 'folder':
            backFunctions = ['folderfile', `openFolder(${path}${oldName})`, `openFolder(${path}${newName})`];
            break;
    }
    if (isDrop)
        return rename.innerHTML = `<button class="${backFunctions[0]}" onclick="${backFunctions[1]}">${oldName}</button>`;
    helperRequest(`rename.php?dir=${path}&old=${oldName}&new=${newName}`)
        .then(data => {
            if (data != '-1') {
                let place = getElement('place'+windowsId);
                place.innerHTML = renderFolders(data);
            } else {
                rename.innerHTML = `<button class="${backFunctions[0]}" onclick="${backFunctions[1]}">${oldName}</button>`;
            }
        })
},
readZip = (dir)=>{
    helperRequest('readZip.php?dir=' + dir)
        .then(data => {
            megaAlert(data);
        })

  //document.body.insertAdjacentHTML('beforeend', `<iframe src="editFile.php?dir=${dir}"></iframe>`);
},
uploadFile = async ()=>{
    getElement('loadProg').style.display = 'block';
    let xhr = new XMLHttpRequest();
    xhr.open('POST', 'fileUplo.php?dir='+path, true);
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === XMLHttpRequest.DONE) {
            if (xhr.status >= 200 && xhr.status < 300) {
                null;
            }
        }
    }
    
    xhr.upload.onprogress = (e) => {
        if (e.lengthComputable) {
            var perc = (e.loaded / e.total);
            getElement('loadProg').setAttribute('value', perc);
        }
    }

    xhr.onload = function() {
        if (xhr.status == 200) {
            getElement('loadProg').style.display = 'none';
            let place = getElement('place'+windowsId);
            place.innerHTML = renderFolders(xhr.response);
        }
    }

    let formData = new FormData();
    let filez = getElement('uploadFile');

    if (filez.files.length > 0) {
        for (var i = 0; i < filez.files.length; i++) {
            formData.append('files[]', filez.files[i]);
        }
    }
    xhr.send(formData);
    filez.value = '';
},
uploadFile2 = async () => {
    getElement('loadProg').style.display = 'block';
    let xhr = new XMLHttpRequest();
    xhr.open('POST', 'fileUplo.php?dir=' + encodeURIComponent(path), true);
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === XMLHttpRequest.DONE) {
            if (xhr.status >= 200 && xhr.status < 300) {
                null;
            }
        }
    }

    xhr.upload.onprogress = (e) => {
        if (e.lengthComputable) {
            getElement('loadProg').value = (e.loaded / e.total);
        }
    };

    xhr.onload = function() {
        getElement('loadProg').style.display = 'none';
        if (xhr.status == 200) {
            let place = getElement('place' + windowsId);
            place.innerHTML = renderFolders(xhr.response);
        }
    };

    let formData = new FormData(),
        files = getElement('uploadFile2').files,
        folders = [];
    
    // Синхронная обработка файлов и папок
    for (let i = 0; i < files.length; i++) {
        let file = files[i],
            path = file.webkitRelativePath.slice(0, file.webkitRelativePath.lastIndexOf('/'));
        if (!folders.includes(path))
            folders.push(path);
        await new Promise((resolve) => {
            let reader = new FileReader();
            reader.onload = () => {
                formData.append('paths[]', file.webkitRelativePath);
                formData.append('fileContents[]', reader.result);
                resolve(); // Завершаем Promise
            };
            reader.readAsText(file);
        });
        //let reader = new FileReader();
        //reader.onload = ()=>{
        //    formData.append('fileContents[]', reader.result);
        //    formData.append('paths[]', file.webkitRelativePath);
        //}
        //reader.readAsDataURL(file);
    }
    folders.sort((a, b) => {
        let depthA = (a.match(/\//g) || []).length,
            depthB = (b.match(/\//g) || []).length;
        return depthA - depthB; // От меньшей глубины к большей
    });
    formData.append('folders', folders.join('|'));
    console.log(formData);
    
    xhr.send(formData);
    getElement('uploadFile2').value = '';
};
// #endregion


window.addEventListener("keydown", event => {
    console.log(event.keyCode);
    if (event.keyCode === 113) {
        renamePre();
        return;
    }
    if (event.keyCode === 27) {
        if (ignorePaste) {
            rename(globalRename[0], globalRename[1], globalRename[2], true);
        }
        if (windowDeleteOpen) {
            getElement('durak3').click();
            return
        }
    }
    if (event.keyCode === 13) {
        if (ignorePaste) {
            rename(globalRename[0], globalRename[1], globalRename[2]);
            return;
        }
        if (windowDeleteOpen) {
            getElement('durak2').click();
            return
        }
        let str = document.querySelector('[class=selected]').getAttribute('path');
        refreshPath(str);
        return;
    }
    if (ignorePaste)
        return;
    if (event.keyCode === 13) {
        let str = document.querySelector('[class=selected]').getAttribute('path');
        refreshPath(str);
        return;
    }
    if (event.keyCode === 46) {
        let str = document.querySelector('[class=selected]').getAttribute('path');
        if (str[str.length - 1] === '/') {
            str = str.slice(0, -1);
            fileDeleteOptions('RMDIR',str,1);
        }
        console.log(str);
    }
    if (document.querySelector('[class=selected]'))
        document.querySelector('[class=selected]').setAttribute('class', '')
    let blockMinus = false;
    if (path[path.length-1] === '/' && selectedFile < maxSelectID - 1) {
        if (selectedFile <= 1)
            blockMinus = true;

        if (event.keyCode == 40) selectedFile++;
        if (event.keyCode == 38 && !blockMinus) selectedFile--;
    } else if (event.keyCode == 38) selectedFile--;
    if (selectedFile)
        getElement('select'+selectedFile).setAttribute('class', 'selected');
});

window.addEventListener("paste", event => {
    if (!ignorePaste) {
        let dt  = new DataTransfer();
        let file_list = dt.files;
        let files = event.clipboardData.items;
        for (let i = 0; i <= files.length; i++) {
            if (typeof(files[i]) != 'undefined') {
                let file = files[i].getAsFile();
                let reader = new FileReader();
                reader.onload = (e) => {
                    let text = e.target.result;
                    dt.items.add(new File([text], file.name, {type: 'text/plain'}));
                };
                reader.readAsText(file);
            }
        }
        console.dir(file_list);
        getElement('uploadFile').files = file_list;
    }   
});

window.addEventListener("popstate", () => {
    path = window.location.search.replace('?', '');
    ignoreHistory = true;
    refreshPath(path);
});

window.addEventListener("keydown",e=>{
    if ((e.metaKey || e.ctrlKey) && e.code === "KeyS") {
        console.log("CTRL + S pressed");
        e.preventDefault(); // Prevent default browser behavior
        let localPath = windowses['w'+windowsId][0];
        let folderCheck = localPath.length - localPath.lastIndexOf('/');
        if (folderCheck == 1)
            return megaAlert('Folders cant save!')
        saveFile(localPath,windowsId)
    }
},false);

if (window.location.search != '') {
    path = window.location.search.replace('?', '')
}