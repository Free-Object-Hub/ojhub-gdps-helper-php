<?php

exit('-1');

include 'build.php';

define('LANGS_PATH', __DIR__.'/server/'.$build.'/languages/');

error_reporting(E_ALL);

$jsons = [
  'RU' => json_decode(file_get_contents(LANGS_PATH.'RU.json'), true),
  'EN' => json_decode(file_get_contents(LANGS_PATH.'EN.json'), true),
  'UA' => json_decode(file_get_contents(LANGS_PATH.'UA.json'), true),
  'JJ' => json_decode(file_get_contents(LANGS_PATH.'JJ.json'), true),
  'DE' => json_decode(file_get_contents(LANGS_PATH.'DE.json'), true),
];

if (isset($_GET['movedata'])) {
  $movedata = explode(',', $_GET['movedata']);
  $moveto = $_GET['moveto'];
  foreach ($movedata as $elem) {
    $findString = "\n  \"$elem\"";
    foreach ($jsons as $key => $value) {
      $langFile = file_get_contents(LANGS_PATH.$key.'.json');
      $langFile = mb_convert_encoding($langFile, 'UTF-8');
      //if (strpos($langFile, "\n$findString"))
      //  $findString = "\n$findString";
      $firstPos = strpos($langFile, $findString);
      $secondPos = strpos($langFile, "\n", $firstPos+5) - $firstPos;
      $findString2 = substr($langFile, $firstPos, $secondPos);
      $langFile = str_replace($findString2, '', $langFile);
      $wherePut = strpos($langFile, "\n", strpos($langFile, "\n  \"$moveto\"")+3);
      $doneStr = substr($langFile, 0, $wherePut) . $findString2 . substr($langFile, $wherePut);
      file_put_contents(LANGS_PATH.$key.'.json', $doneStr);
    }
    $moveto = $elem;
  }
}

if (isset($_GET['data'])) {
  $json = json_decode($_GET['data']);
  foreach ($json as $langName => $lang) {
    $langFile = file_get_contents(LANGS_PATH.$langName.'.json');
    $langFile = mb_convert_encoding($langFile, 'UTF-8');
    foreach ($lang as $key => $value) {
      $newStr = '"'.$key.'":"'.$value."\",\n";
      $langFile = preg_replace("/\"$key\":\"(.*)\n/", $newStr, $langFile);
    }
    file_put_contents(LANGS_PATH.$langName.'.json', $langFile);
  }
  exit();
}

if (isset($_GET['newdata'])) {
  $json = json_decode($_GET['newdata']);
  $newString = "\n";
  if ($json->addNL)
    $newString .= "\n";
  $newString .= '  "'.$json->keyname.'":"';
  foreach ($json->strings as $key => $value) {
    $newString2 = $newString.$value.'",';
    if ($json->addLastNL)
      $newString2 .= "\n";
    $langFile = file_get_contents(LANGS_PATH.$key.'.json');
    $langFile = mb_convert_encoding($langFile, 'UTF-8');
    $pos = strpos(
      $langFile,
      "\n",
      strpos($langFile, '"'.$json->current)
    );
    $newString3 = substr($langFile, 0, $pos) . $newString2 . substr($langFile, $pos);
    file_put_contents(LANGS_PATH.$key.'.json', $newString3);
  }
  exit('true');
}

$allKeys = [];
foreach ($jsons as $lang => $data) {
  $allKeys = array_merge($allKeys, array_keys($data));
}
$allKeys = array_unique($allKeys);

$newHelper = file_get_contents('newHelper.js');
$html = '<table>';

  $html .= '<tr>';
  $html .= '<th>langKey</th>';
  foreach (array_keys($jsons) as $langCode) {
    $html .= "<th>$langCode</th>";
  }
  $html .= "<th>+</th>";
  $html .= '</tr>';

  // Строки с переводами
  $c = 0;

  foreach ($allKeys as $key) {
    $html .= "<tr id=\"key$c\"><td style=tab-size:2 onclick=addSelection('keyname$c') class=keys id=\"keyname$c\">$key</td>";

    foreach ($jsons as $lang => $langData) {
      $style = 'width:240px;height:120px';
      $text = '???';

      if (isset($langData[$key])) {
        if ($langData[$key] != '')
          $text = $langData[$key];
        else
          $style .= ';background:#ff8888';
      } else {
        $style .= ';background:#ff8888';
      }

      $html .= "<td><textarea style=\"$style\" class=\"$lang\" key=\"$key\" id=\"$lang$key\">" . htmlspecialchars($text) . '</textarea></td>';
    }
    $html .= "<td><button onclick=newKey($c,0,getKey(this))>+</button>".
             "<button onclick=newKey($c,1,getKey(this))>\\n+</button>".
             "<button onclick=newKey($c,2,getKey(this))>\\n+\\n</button></td>";

    $html .= "</tr>";
    $c++;
  }

$html .= '</table>';

echo $html?>
<script>
  let getElement = function(i) {
    if (!document.getElementById(i))
      console.warn('Cant find element with "'+i+'" id!');
    return document.getElementById(i);
  },
  helperRequest = function(url, data, headers = '') {
    return new Promise(function(resolve, reject) {
      let XHR = new XMLHttpRequest(),
        METHOD = 'POST';
    
      XHR.open(METHOD, url);
      XHR.setRequestHeader("Content-Type", "application/json");
    
      XHR.onreadystatechange = function() {
        if (XHR.readyState === 4 ) {
          if (XHR.status === 200) {
            servError = "\n\nADDR: "+url+"\n\nSERVER RESP:\n\n"+XHR.response;
            resolve(XHR.response);
          } else {
            servError = '';
            reject(new Error('Unknown error, code status '+XHR.status), XHR);
          }
        }
      };

      XHR.onerror = function() {
        servError = '';
        reject(new Error('Network error'), XHR);
      };
    
      if (data !== undefined) {
        XHR.send(data);
      } else {
        XHR.send();
      };
    });
  },
  saveLangs = function() {
    let data = 'data='+encodeURIComponent(JSON.stringify(modifiedLangs))
      .replaceAll('+', '%20');
    helperRequest('langs.php?'+data)
      .then(function(data) {
        console.log(data);
      })
      .catch(function(e) {console.error(e)});
  },
  insertID = 0,
  insertNewLineTemplate = function(keyName, addNewLine) {
    insertID++;
    return `<div id=insert${insertID} style=padding:16px;background:lightgray;position:fixed;top:50%;left:50%;transform:translate(-50%,-50%)>`+
      `<p>after <span id=current>${keyName}</span></p>`+
      `<input newline name=keyname placeholder=keyname><br><br>`+<?php
      foreach ($jsons as $lang => $langData) {
        echo "      `<input newline name=$lang placeholder=$lang><br>`+\n";
      }
      ?>
      addNewLine+
      `<br><button onclick=createRealNewKey()>ADD</button>`+
      `<br><button onclick=getElement('insert${insertID}').remove()>NO</button>`+
    `</div>`;
  };
  newKey = function(current, newLine, keyName) {
    if (newLine)
      newLine = `<input id=newline type=hidden value=${newLine}>`;
    else 
      newLine = '';
    document.body.insertAdjacentHTML('afterend', insertNewLineTemplate(keyName, newLine));
  },
  createRealNewKey = function() {
    let stringsPre = document.querySelectorAll('[newline]'),
        strings = [],
        query = '';
    stringsPre.forEach(el=>{
      strings.push(`"${el.name}":"${el.value}"`);
    });
    query = '{';
    if (getElement('newline')) {
      query += `"addNL":true,`;
      if (getElement('newline').value == 2)
        query += `"addLastNL":true,`;
      else 
        query += `"addLastNL":false,`;
    } else 
      query += `"addNL":false,"addLastNL":false,`;
    
    query += `"current":"${getElement('current').textContent}",`
    query += strings[0]+',"strings":{';
    strings = strings.slice(1);
    query += strings.join(',');
    query += '}}';
    console.log(query);
    
    let data = 'newdata='+encodeURIComponent(query)
      .replaceAll('+', '%20');
    helperRequest('langs.php?'+data)
      .then(function(data) {
        location.search = '?cache=' + Date.now();
      })
      .catch(function(e) {console.error(e)});
  }
  getKey = function(thus) {
    return thus.parentElement.parentElement.childNodes[0].textContent;
  },
  preventClick = false,
  addSelection = function(elId) {
    let elem = getElement(elId);
    if (preventClick) {
      if (!selection.includes(elem.textContent))
        getElement('movekeys').value = elem.textContent;
      return;
    }
    if (elem.style.background == '') {
      elem.style.background = `rgb(170, 255, 170)`;
      selection.push(elem.textContent);
    } else {
      elem.style.background = ``;
      selection.splice(selection.indexOf(elem.textContent), 1);
    }
  },
  moveLangs = function(id) {
    let data = [getElement('movekeys').value, selection.join(',')];
    helperRequest('langs.php?movedata='+data[1]+'&moveto='+data[0])
      .then(function(data) {
        document.body.innerHTML = data;
      })
      .catch(function(e) {console.error(e)});
    
    preventClick = false;
    getElement(id).remove();
  },
  moveLangsPre = function() {
    insertID++;
    preventClick = true;
    let html = `<div id=insert${insertID} style=padding:16px;background:lightgray;position:fixed;top:50%;left:50%;transform:translate(-50%,-50%)>`+
      `<p>after?</p>`+
      `<input id=movekeys name=keyname placeholder=keyname><br><br>`+
      `<br><button onclick=moveLangs('insert${insertID}')>MOVE</button>`+
      `<br><button onclick=preventClick=false;getElement('insert${insertID}').remove()>NO</button>`+
    `</div>`;
    document.body.insertAdjacentHTML('beforeend', html);
  }
  selection = [],
  modifiedLangs = {
<?php
  foreach ($jsons as $lang => $langData) {
    echo "  $lang:{},\n";
  }
  ?>
  }
  window.oninput = (e)=>{
    let elem = e.target,
      lang = elem.classList[0],
      langKey = elem.getAttribute('key'),
      text = getElement(lang+langKey).value,
      preText = getElement(lang+langKey).textContent;

    if (text !== preText) {
      elem.style.background = '#aaaaff';
      modifiedLangs[lang][langKey] = text;
      console.log(modifiedLangs);
      console.log(modifiedLangs[lang][langKey]);
    } else {
      elem.style.background = '';
      delete modifiedLangs[lang][langKey];
      console.log('false');
    }
  }
</script>
  <div style="position:fixed;right:8px;top:8px;width:250px;background:rgba(255,255,255,0.8)">
    <button onclick="saveLangs()">save</button>
    <button onclick="moveLangsPre()">moveTo</button>
  </div>
