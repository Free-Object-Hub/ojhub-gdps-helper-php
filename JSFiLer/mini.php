<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="main.js?ver=<?=time()?>"></script>
    <link rel="stylesheet" href="style.css?ver=<?=time()?>">
    <title>File Manager</title>
</head>
<body style="overflow:hidden;margin:0;background-color:white">
    <div style="position:absolute;
                width:calc(100% - 16px);
                height:calc(100% - 16px);
                display:none" id="durak">
        <div style="position:absolute;
                    top:50%;
                    left:50%;
                    transform:translate(-50%,-50%);
                    background-color:#999">
            <p>ты точно хочешь удалить эту хуетень?</p><br>
            <button id="durak2">ага</button>
            <button id="durak3" onclick="document.getElementById('durak').style.display='none'">неа</button>
        </div>
    </div>

    <div style=height:120px;display:flex;flex-direction:row;display:none>
        <div style=display:flex;flex-direction:column>
            <div class="directory" id="thisIsPath!!"><span id="pos" ondblclick="replacePath()">../</span></div>
            <div>
                <button id="refresh" onclick="refreshPath(path)">Refreesh</button><br>
                <button id="back" onclick="openFolder('../')">Main folder</button>
                <button id="drop" onclick="drop(path)">Go up</button>
                <button onclick="history.back()">&lt;</button>
                <button onclick="history.forward()">&gt;</button>
            </div>
            <div>
                <input id="filename" placeholder="file/dir name">
                <button onclick="fileCreateOptions('MAKEFILE')">create file</button>
                <button onclick="fileCreateOptions('MAKEDIR')">make dir</button><br>
            </div>
            <div>
                <input type="file" id="uploadFile" multiple="">
                <button onclick="uploadFile()">оно не работает</button>
                <progress id=loadProg style=display:none></progress>
            </div>
        </div>
    </div>
    <div align="left" class="filemgr" style="height:calc(100vh - 120px);overflow-y:scroll">
        <table style=width:100%>
            <tbody id=place0></tbody>
        </table>
        <script>
            let token = 'token='+localStorage.getItem('helperUser');

            if (path[path.length - 1] === '/') {
                openFolder(path, token);
            } else {
                openFile(path, token);
            }
        </script>
    </div>
</body>