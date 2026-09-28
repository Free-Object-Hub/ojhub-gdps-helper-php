// ГАЙД ПО МИНИФИКАЦИИ - getElement() это обычный док гет элембуид. innerMain() это заменить весь контект внутри div id=1st

/* порядок запуска хелпера (жс):
 *  вызывается функция 'reStart()', которая вызывает 'helperRequest()' но это мелочи, она назначает глобальные переменные GDPSes и Guides
 *  если есть вход в аккаунт назначаются ещё и thisUser, myGdpses и myguides
 *  после вызывается функция 'getLink()', которая берёт в урл всё после '?' и прогоняя через себя вызывает нужные функции (например '?guides' закинет в гайды, или '?gdps=45' откроет гдпс с айди 45)
 *  после выполнения 'getLink()' хелпер готов к работе с клиентом
*/

/* Поясняю за некоторую легаси парашу - типы комментов и лайков
 * На сервере ошхаба комментарии и лайки определяются и хранятся в каналах в одной таблице, ниже приведен список какой у кого канал
 * тип контента               | лайки | комментарии | Ко-овнеры
 * Кемпы                      | 0     | 0           | 1
 * Обджект шоу                | 0     | 0           | 1
 * Страницы вики              | 7     | 2           | -
 * Вики                       | 8     | -           | 2
 * Форум посты                | 9     | 4           | -
 * Новости                    | 6     | 3           | -
 * комментарии к кемпам       | 3     | -           | -
 * комментарии к шоу          | 3     | -           | -
 * комментарии к новостям     | 4     | -           | -
 * комментарии к страницам    | 5     | -           | -
 * комментарии к форум постам | 10    | -           | -
 * Ваш контент                | 11++  | 5++         | 4++
 * 
 * !!! Типы лайков комментов прописываются через костыли в renderComms() чтобы не возникало неожиданного поведения, учтите это если реально хотите на основе newHelper.js делать сайт
 * а ещё лучше сделайте один общий тип для всех комментов чтобы не мучать себя
*/

var getElement = function(i) {
  console.log(i)
  if (!document.getElementById(i))
    Console.warn('Cant find element with "'+i+'" id!');
  return document.getElementById(i);
},
querySelect = (i)=>{
  if (!document.querySelector(i))
    console.warn('Cant find element by "'+i+'" querySelector!');
  return document.querySelector(i);
},
querySelectAll = (i)=>{
  if (!document.querySelectorAll(i))
    console.warn('Cant find elements by "'+i+'" querySelector!');
  return document.querySelectorAll(i);
},

// #region базовые опции гдпс хелпера
ignoreCap = false,
renderBeta = false,
logAll = true,
techName = 'oschubADM',
Slocal = {
  get: function(name) {return localStorage.getItem(techName+name)},
  set: function(name, value) {return localStorage.setItem(techName+name, value)},
  remove: function(name) {return localStorage.removeItem(techName+name)},
},
Console = {
  error: function(data) {logAll ? console.error(data) : null},
  log: function(data) {logAll ? console.log(data) : null},
  warn: function(data) {logAll ? console.warn(data) : null},
  time: function(data) {logAll ? console.time(data) : null},
  timeEnd: function(data) {logAll ? console.timeEnd(data) : null},
},
ignore = false,
CacheCamps = '',
CacheShows = '',
CachePeres = '',
CacheWikis = '',
CacheVacans = '',

helperBuildNum = 133,
helperStrVer = '0.933',
helperTitleText = renderBeta ? 'ojhub-BUILD'+helperBuildNum : 'ojhub ADMIN',
helperCaptchaSiteKey = 'c7d9485d-7199-4801-9301-4c0ead7ce01e',

// Главные html теги
helperMain = getElement('1st'),
helperIcon = link = document.querySelector("link[rel~='icon']"),
helperTitle = document.querySelector('title'),
errorBox = getElement('errorBox'),

Windowses = [],
windowsCount = 0,

helperUrl = 'https://objecthub.xyz/',//'https://gdpshelper.xyz/',

curlJoin = ['https://objecthub.xyz/'],
baseApp = location.origin + location.pathname + '../',

sData = [
  baseApp+'server/'+helperBuildNum+'/content/',
  baseApp+'server/'+helperBuildNum+'/send/',
  baseApp+'server/'+helperBuildNum+'/',
  baseApp+'server/'+helperBuildNum+'/search/',
  baseApp+'server/'+helperBuildNum+'/delete/',
  baseApp+'server/'+helperBuildNum+'/user/',
  baseApp+'server/'+helperBuildNum+'/forum/',
  '.php'
],

errors = {},

token = localStorage.getItem('oschubUser'), // токен юзера

globalWiki = 0,

thisUser = [
  '???', // ник
  0, // айди
  0, // роль
  0, // активирован или нет
  0, // есть ли алармы или нет
  // обычно тут временно хранится токен
],

mainLang = 'RU', // язык, хотя вроде очевидно
servError = "\n\nSERVER RESP:\n\nxhr.response", // если 'helperRequest' вернёт ошибку, она будет записана сюда и отображена через 'returnError()'

guideEditorFrame = 0, // используется только в редакторе гайдов чтобы можно было удалять разделы не по порядку
errorCount = 0, // используется в returnError()

TimeOut = [null,null], // [0] для инпута, [1] для анимаций окон (регистрация и логин)

headerPhoneSwitcher = 0, //0 - не нажимался, 1 - в профиле, 2 - в навигаторе

// универсальная функция для запросов на сервер
helperRequest = function(url, data) {
  return new Promise(function(resolve, reject) {
    let XHR = new XMLHttpRequest(),
    // убрать комментарии на случай если ваше приложение на newHelper будет использовать php-сессии или что то похожее
    //   METHOD = 'GET';
    // if (data !== '')
      METHOD = `POST`;
    Slocal.set('User', token);
    if (Slocal.get('User')) {
      if (data !== undefined) 
        if (typeof(data) !== 'object')
          data += `&token=${token}`
        else
          data.append('token', token);
      else
        data = `token=${token}`
    }

    XHR.open(METHOD, url);

    if (typeof(data) !== 'object') {
      XHR.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    }

    XHR.onreadystatechange = function() {
      if (XHR.readyState === 4 && XHR.status === 200) {
        servError = "\n\nSERVER RESP:\n\n"+XHR.response;
        resolve(XHR.response);
      }
      if (XHR.status === 404) {
        servError = '';
        reject(new Error('404 not found:\n'+url), XHR);
      }
    };
    // .catch(function(error) {returnError(error);throw new Error();});
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
// отображение ошибок у 'helperRequest', переменная errorCount нужна чтобы считать ошибки
returnError = function(err, addr = '') {
  Console.error(err);
  errorCount++;
  if (errorCount === 1) {
    document.body.insertAdjacentHTML('beforeend',
      `<div id=errorBoxCount style=z-index:5;position:fixed;bottom:50px;left:50px;background-color:rgba(0,0,0,.5);padding:12px;border-radius:calc(var(--def-border-large)*1.5)>`+
        `<span id=errorCount style=position:absolute;right:10px;top:10px>1</span>`+
        `<button class=emptybtn onclick="for (let errId in errors) {if (!getElement('debug'+errId))renderError(errId,errors[errId])}">`+
          `<img src=${helperUrl}imgs/err.svg width=48px height=48px>`+
        `</button>`+
      `</div>`
    )
  } else {
    getElement('errorCount').innerHTML = errorCount;
  }
  renderError(errorCount, err, addr);
  errors[errorCount] = err;
  if (getElement('TheLoadElem'))
    getElement('TheLoadElem').remove();
},
renderError = function(errID, errText, serverAddress = '') {
  let errInfo = 
  (serverAddress === '' ? '' : `ADDRESS: ${serverAddress}\n`)+
  `LOCATION: ${location}\n`+
  `USERID: ${thisUser[1]}\n`;

  errorBox.insertAdjacentHTML('beforeend',
    `<div class=ALERTerr style=top:20%;left:50%; id=debug${errID}>`+
      `<p align=center style=margin:0>DEBUG INFO</p>`+
      `ERROR<br>`+
      `<div id=debugMega${errID} style=background-color:#000></div>`+
      `<br><br>`+
      `<center>`+
        `<button style=background-color:#333 onclick=reportError(${errID})>`+
          `REPORT`+
        `</button><br>`+
        `<button style=background-color:#333 onclick="location.reload()">`+
          `FULL RESTART`+
        `</button>`+
        `<button style=background-color:#333 onclick=reStart(0,${errID})>`+
          `RESTART`+
        `</button><br>`+
        `<button style=background-color:#333 onclick=linkCopy(getElement('debug${errID}').innerText)>`+
          `COPY ERROR`+
        `</button>`+
        `<button style=background-color:#333 onclick=getElement('debug${errID}').remove()>`+
          `HIDE ERROR`+
        `</button>`+
      `</center>`+
    `</div>`
  );
  getElement('debugMega'+errID).innerText = errInfo+errText;
},
reportError = function(errorId) {
  let text = getElement('debugMega'+errorId).innerHTML;
  Loading();
  helperRequest(`${sData[2]}reportError${sData[7]}`, 'error='+text+'\n\n'+navigator.userAgent)
    .then(function(data) {
      Loading(1);
      if (errorId !== 0) {
        if (getElement('debug'+errorId))
          getElement('debug'+errorId).remove();
        errors[errorId] = errors[errorCount];
        delete errors[errorCount];
        errorCount--;
        if (errorCount === 0) {
          getElement('errorBoxCount').remove();
        } else {
          getElement('errorCount').innerHTML = errorCount;
        }
      }   
    })
    .catch(function(error) {returnError(error);throw new Error();});
},
linkActions = {
  '': function() {innerMain(pageMain())},
  list: function() {innerMain(pageList())},
  shows: function() {innerMain(pageShows())},
  en: function() {translateReplaceLang('EN',1);innerMain(pageList())},
  ru: function() {translateReplaceLang('RU',1);innerMain(pageList())},

  camp: function(campId) {getCamp(campId)},
  show: function(showId) {getShow(showId)},
  news: function(postId) {getNewsWithComments(postId.split('.')[0], postId.split('.')[1])},
  special: function() {innerMain(uvazuha())},
  about: function() {innerMain(helperAbout())},

  profile: function() {innerMain(profilePage())},
  addedCamps: function() {innerMain(profilePage(campsWindow()))},
  addedShows: function() {innerMain(profilePage(showsWindow()))},
  addedWikis: function() {innerMain(profilePage(wikisWindow()))},
  addCamp: function() {innerMain(profilePage(addCamp()))},
  editCamp: function(campId) {innerMain(profilePage(''));editCamp(campId)},
  addShow: function() {innerMain(profilePage(addShow()))},
  editShow: function(showId) {innerMain(profilePage(''));editShow(showId)},
  alarms: function() {innerMain(profilePage(alarmsWindow()));GetAlarms()},
  alarm: function(msgId) {innerMain(profilePage(alarmsWindow()));getFullAlarm(msgId)},
  profiles: function(userId) {otherProfile(userId,'innerMain(pageList())')},
  profCamps: function(userId) {otherProfile(userId,'innerMain(pageList())',otherCampsWindow)},
  profShows: function(userId) {otherProfile(userId,'innerMain(pageList())',otherShowsWindow)},
  profWikis: function(userId) {otherProfile(userId,'innerMain(pageList())',otherWikisWindow)},
  color: function() {innerMain(profilePage());clrEditPage()},
  dropcolor: function() {innerMain(profilePage());clrEditPage();dropColorScheme()},

  wikis: function() {pageWikiList()},
  wiki: function(wikiId) {pageGuides(wikiId)},
  wikiNew: function() {createWiki(1)},
  wikiEdit: function(wikiId) {editWiki(wikiId,1)},
  wikiEditor: function(wikiId) {innerMain(profilePage(''));getGuidesAdmin(wikiId)},
  wikiPage: function(guideId) {getGuide(guideId.split('.')[0],guideId.split('.')[1])},
  wikiPageNew: function(wikiId) {createGuide(wikiId,1)},
  wikiPageEdit: function(guideId) {editGuide(guideId.split('.')[0],guideId.split('.')[1],1)},

  forum: function(forumId) {openForum(forumId)},
  forumPost: function(forum) {getForumPost(forum.split('.')[0],forum.split('.')[1])},

  gdpsLog: function(gdpsId) {innerMain(profilePage(''));getJoinLog(gdpsId)},
  campOwn: function(campId) {innerMain(profilePage(''));coownersMenu(campId,0)},
  ShowOwn: function(showId) {innerMain(profilePage(''));coownersMenu(showId,1)},
  wikiOwn: function(wikiId) {innerMain(profilePage(''));coownersMenu(wikiId,2)},

  admin: function() {adminPanel()}
},
setLink = function(val, pageTitle = helperTitleText) {
  if (!ignore) {
    history.pushState(null, null, '?'+val);
    if (pageTitle)
      helperTitle.innerHTML = pageTitle;
  }
  ignore = false;
},
getLink = function() {
  let params = window.location.search
    .replace('?','')
    .split('&')
    .reduce(
      function(p,e) {
        let a = e.split('=');
        p[ decodeURIComponent(a[0])] = decodeURIComponent(a[1]);
        return p;
      },
      {}
    );
  Console.log(params);

  for (let KEY in params) {
    let VALUE = params[KEY];
    if (typeof KEY !== 'undefined') {
      if (typeof VALUE === 'undefined') {
        VALUE = thisUser[1];
      };
      try {
        linkActions[KEY](VALUE);

        renderBeta ?
        document.body.insertAdjacentHTML('beforeend', `<p style=opacity:50%;position:absolute;top:0;right:0;margin:64px data-trans="helperVer"${getTrans('helperVer')}/p>`, 1)
        : '';
      } catch (e) {
        returnError("?"+KEY+"="+VALUE+' is broken link!');
        innerMain(pageMain());
        
        renderBeta ?
        document.body.insertAdjacentHTML('beforeend', `<p style=opacity:50%;position:absolute;top:0;right:0;margin:64px data-trans="helperVer"${getTrans('helperVer')}/p>`, 1)
        : '';
      }
    };
  };
},
getLinkLegacy = function() { // Функция из GDPS Helper, устаревшая и замененная с хеша[#] на поисковые параметры[?]
  let params = window.location.hash
    .replace('#','')
    .split('&')
    .reduce(
      function(p,e) {
        let a = e.split('=');
        p[ decodeURIComponent(a[0])] = decodeURIComponent(a[1]);
        return p;
      },
      {}
    );
  Console.log(params);

  for (let KEY in params) {
    let VALUE = params[KEY];
    if (typeof KEY !== 'undefined') {
      if (typeof VALUE === 'undefined') {
        VALUE = thisUser[1];
      };
      try {
        linkActions[KEY](VALUE);
      } catch (e) {
        returnError("#"+KEY+"="+VALUE+' is broken link!');
        innerMain(pageMain());
      }
    };
  };
},
reStart = function(drop = 0, errorId = 0) {
  if (errorId !== 0) {
    if (getElement('debug'+errorId))
      getElement('debug'+errorId).remove();
    errors[errorId] = errors[errorCount];
    delete errors[errorCount];
    errorCount--;
    if (errorCount === 0) {
      getElement('errorBoxCount').remove();
    } else {
      getElement('errorCount').innerHTML = errorCount;
    }
  }

  innerMain('');
  // убрать комментарии на случай если ваше приложение на newHelper будет использовать php-сессии или что то похожее
  // let token = Slocal.get('User'),
  // postData = token ? 'token='+token : '';
  Loading();
  helperRequest(sData[2]+'!newTakeAll'+sData[7])
    .then(function(data) {
      let serverResp = JSON.parse(data);

      thisUser = serverResp.user;
      CacheCamps = serverResp.camps;
      CacheShows = serverResp.shows;
      CachePeres = serverResp.peres;
      CacheWikis = serverResp.wikis;
      CacheVacans = serverResp.vacans;
      getLink();
      helperIcon.href = 'https://objecthub.xyz/favicon.ico';
      if (Slocal.get('BetaRead') == null)
        makeBetaAlert();
      Loading(1);
    })
    .catch(function(error) {returnError(error);throw new Error();});
  if (drop !== 0) {
    ignore = true;
    translateReplaceLang('RU');
  }
},
// #endregion
// #region компоненты
// #region микрокомпоненты

windowButton = function(text, func = '', style = '') {
  return `<button class=emptybtn style="padding:2px;color:var(--color-window);${style}" onclick="${func}">${text}</button>`;
},
basicButton = function(text, id = '', func = '', Class = '', style = '') {
  return `<button ${id ? 'id="'+id+'"' : ''}class="loginbtn ${Class}" style="${style}" onclick="${func}">${text}</button>`;
},

// #endregion
headerButtons = function(switcherM = 0) {
  if (switcherM === 1)
    return `<button class="headbtn" onclick="switchMobileMain();innerMain(pageMain())"${getTrans('main')}/button>`;
  return `<button class="headbtn" onclick="innerMain(pageMain())"${getTrans('main')}/button>`;
},

contentRender = function(
  preHtml = {
    ID: 0,
    title: '???',
    text: '???',
    likes: 0
  },
  date = 0,
  authorBtn = 1,
  likeType = 0,
  tags = '',
  specialButtons = 1,
  connectedWiki = 1,
  reportButton = '',
  joinData = '', // работает как кнопка назад в рендере новостей
  isComm = 1
 ) {
  let html = `<div class="framegdps" style="width:400px${isComm == 0 ? 'width:calc(100% - 40px)' : ''}">`+
    (preHtml.img || tags ?
    `<img onerror="Console.warn('broken link');this.src='${helperUrl}imgs/hubbig.png'" align="left" src="${decodeURIComponent(preHtml.img)}" width=128px height=128px style="border-radius:calc(var(--def-border)*2)">`
    : '')+
    `<h2>${preHtml.title}</h2>`+
    `<p style="margin:0">`+
    (authorBtn ?
      `<button class=loginbtn onclick="${joinData}(${preHtml.gdpsId})">${preHtml.gdpsTitle}</button>`+
      `- <button class=emptybtn onclick="otherProfile(${preHtml.author},'${joinData}(${preHtml.gdpsId})')">${preHtml.username}</button>`
    :
      `<span${getTrans('addedBy')}/span>:`+
      `<button onclick="otherProfile(${preHtml.author},'innerMain(pageList())')" style="background:0;border:0;color:var(--color-white)">${preHtml.username}</button>`
    )+
    `</p>`+
    (date ?
    `<p>${timeAgo(date)}</p>`
    : '')+
    (tags ?
    `<div class="flex-row">${tags}</div>`
    : '')+
    `<p>${Markdown(preHtml.text)}</p>`+
    `<div style="margin-top:15px">`+
      (specialButtons ?
      `<a class="loginbtn" href="../join${sData[7]}?id=${preHtml.ID}${joinData}" target=_blank${getTrans('joinToGdps')}/a>`+
      `<button class="loginbtn" onclick="linkCopy('https://objecthub.xyz/?camp=${preHtml.ID}')"${getTrans('getLink')}/button>`
      : '')+
      (connectedWiki ?
      `<button class=loginbtn onclick="pageGuides(${connectedWiki},\`getCamp(${preHtml.ID},'${joinData}')\`)" style="margin-top:8px"${getTrans('openConnectedWiki')}/button>`
      : '')+
      `<div class="likezone">`+
        `<span class=likeplace id="likesCount${preHtml.ID}">${preHtml.likes}</span>`+
        `<button onclick="sendLike(${preHtml.ID},${likeType})" id="like"></button>`+
        `<button onclick="sendDislike(${preHtml.ID},${likeType})" id="dislike"></button>`+
        (isComm == 0 ?
        `<button class=loginbtn onclick=getNewsWithComments(${preHtml.ID},${preHtml.gdpsId},'${joinData}')${getTrans('comms')}/button>`
        : '')+
      `</div>`+
    `</div>`+
    (reportButton ?
    `<button onclick="gdpsReport(${reportButton})" style="position:absolute;bottom:20px;right:20px;padding:2px 4px" class="loginbtn">`+
      `<img src=${helperUrl}imgs/flag.svg width=16px style=margin:0>`+
    `</button>`
    : '')+
    (preHtml.canDel ? 
    `<button onclick="deleteNews(${preHtml.ID},${isComm})" style="position:absolute;top:20px;right:20px;padding:2px 4px" class="loginbtn">`+
      `<img style=margin:0 width=24px src="${helperUrl}imgs/trash.svg">`+
    `</button>`
    : '')+
  `</div>`;
  return html;
},
contentRenderMinu = function(
  data,
  preHtml = [
    joinData,
    joinBtn,
    tagsOs,
    'width:300px;height:450px',/*size*/
    0,/*contentType*/
    0,/*liketype*/
  ],
  renderAuthor = 1,
  renderDesc = 1,
  renderTags = 1,
  renderImage = 1
 ) {
  let imgClass = ['', ''];
  if (renderImage == 1) {
    imgClass = ['FGDPStext', 'FGDPSdemo']
  }

  let btnFuncs = [];

  switch (preHtml[4]) {
    case -3: 
      btnFuncs = ['getForumPost', 'openForum('+preHtml[0]+')', 'f'];
      break;
    case -2:
      btnFuncs = ['getGuide', 'pageGuides()', 'g'];
      break;
    case -1:
      btnFuncs = ['pageGuides', 'pageWikiList()', 'w'];
      break;
    case 0:
      btnFuncs = ['getCamp', 'pageFind(0)', ''];
      break;
    case 1:
      btnFuncs = ['getShow', 'pageFind(1)', ''];
      break;
    case 2:
      btnFuncs = ['getPere', 'pageFind(2)', ''];
      break;
  }

  let 
  banWidth = parseInt(preHtml[3].split(';')[0].split(':')[1]) + 16,
  banHeight = Math.round(banWidth * 0.4166),
  darkZoneMargin = banHeight - 60,
  html =
  `<div class="framegdps" id="${btnFuncs[2]}${data.ID}" style="${preHtml[3]}">`+
    (renderImage ? `<div class=loh style="min-height:128px">` : '')+
      `<h2 style=width:290px${renderImage ? '' : ';margin-top:'+(banHeight - 35)+'px;position:inherit;z-index:1'}>${data.title}`+(data.language ? `<img src="${helperUrl}imgs/${data.language}.png">` : '')+`</h2>`+
      (renderAuthor ? `<p style="margin:0">`+
        `<span${getTrans('addedBy')}/span>:`+
        `<button onclick="otherProfile(${data.author},'${btnFuncs[1]}')" style="background:0;border:0;color:var(--color-white)">${data.username}</button>`+
      `</p>` : '')+
      (renderImage ? `<img onerror="Console.warn('broken link ${decodeURIComponent(data.img)}');this.src='${helperUrl}imgs/hubbig.png'" align="left" src="${decodeURIComponent(data.img)}" width=128px height=128px style="border-radius:calc(var(--def-border)*2)">`+
    `</div>` : '')+
    
    `<img class="${imgClass[0]}" id="guideimg" style=width:${banWidth}px;height:${banHeight}px src="${decodeURIComponent(data.ban)}" onerror="Console.warn('broken link ${decodeURIComponent(data.ban)}');this.src='${helperUrl}imgs/hubemp.png'">`+
    `<div class="${imgClass[1]} gdpsalpha" style="width:${banWidth}px;height:60px;margin-top:${darkZoneMargin}px"></div>`+

    `<div style=position:absolute;bottom:0;width:100%>`+
      `<div class="likezone" style=margin-left:-4px;margin-bottom:6px>`+
        `<span class=likeplace id="likesCount${data.ID}">${data.likes}</span>`+
        `<button onclick="sendLike(${data.ID},${preHtml[5]})" id="like"></button>`+
        `<button onclick="sendDislike(${data.ID},${preHtml[5]})" id="dislike"></button>`+
      `</div>`+
      `<div class="btnszone" style=position:absolute;bottom:0;right:16px>`+
        preHtml[1]+
        `<button class=loginbtnGDPS style=margin-left:-2px;border-bottom-right-radius:calc(var(--def-border)*1.5) onclick="${btnFuncs[0]}(${data.ID}${preHtml[0] ? `,'${preHtml[0]}'` : ''})"${getTrans('moreInfo')}/button>`+
      `</div>`+
    `</div>`+
    (renderDesc ? `<p ${renderImage ? 'class="'+imgClass[0]+' absolute"' : ''} ${renderImage ? 'style="margin:0"' : ''}>${data.text}${data.text[120] === undefined ? '' : '...'}</p>` : '')+
    (renderTags ? `<div class="flex-row FGDPStags absolute">${preHtml[2]}</div>` : '')+
  `</div>`;
  return html;
},
contentSendCommForm = function(sendCommData) {
  if (sendCommData != '' && thisUser[3] === 1)
   return `<div class="framecomm">`+
        `<input type="text" class="framelabel" id="text" style="width:calc(100% - 16px)" required minlength=10${getTrans('min10chars', 'input')}<br>`+
        `<button class="loginbtn" onclick="sendComm(${sendCommData})" id="commentBtn"${getTrans('commSend')}/button>`+
      `</div>`;
  return '';
},

GDPStags = {
  '1': 'Camptag1',
  '2': 'Camptag2',
  '3': 'Camptag3',
  '4': 'Camptag4',
  '5': 'Camptag5',
  '6': 'Camptag6',
  '7': 'Camptag7',
  '8': 'Camptag8',
  '9': 'Camptag9',
  '10': 'Camptag10',
},
GDPSoss = {
  '11': 'Camptag11',
  '12': 'Camptag12',
  '13': 'Camptag13',
  '14': 'Camptag14',
  '15': 'Camptag15',
},
SHOWtags = {
  '1': 'Showtag1',
  '2': 'Showtag2',
  '3': 'Showtag3',
  '4': 'Showtag4',
  '5': 'Showtag5',
  '6': 'Showtag6',
  '7': 'Showtag7',
  '8': 'Showtag8',
  '9': 'Showtag9',
},
SHOWoss = {
  '12': 'Showtag12',
  '13': 'Showtag13',
  '14': 'Showtag14',
  '15': 'Showtag15',
},

renderTagSearch = function(Array, Class, id) {
  let tagName = Array[id];
  return `<label class="tagUns" onclick="writeTag('${Class}',${id})" id=${tagName}${getTrans(tagName)}/label>`;
},
renderTagAdding = function(Array, Class, id, checked = '') {
  let tagName = Array[id];
  let html = `<input id=T${id} style=display:none name=${Class}[] type=checkbox${checked} value=${id}>`+
  `<label class=tagUns for=T${id} value=${id}${getTrans(tagName)}/label>`;
  return html;
},

// #endregion
// #region поиск + контент(открытие кемпов вики форумов и т д)
writeTag = function(type,tag) {
  let INDEX = 1,
    elemId = 'Camptag';
  switch (type) {
    case 'camp':
      INDEX = 1;
      break;
    case 'caOS':
      INDEX = 2;
      break;
    case 'show':
      INDEX = 1;
      elemId = 'Showtag';
      break;
    case 'shOS':
      INDEX = 2;
      elemId = 'Showtag';
      break;
  };
  if (!helperFindData[INDEX].includes(tag)) {
    Console.log(elemId+tag);
    getElement(elemId+tag).setAttribute('class','tagSel');
    helperFindData[INDEX].push(tag);
  } else {
    getElement(elemId+tag).setAttribute('class','tagUns');
    let tagPlace = helperFindData[INDEX].indexOf(tag);
    if (tagPlace !== -1) {
      helperFindData[INDEX].splice(tagPlace, 1);
    }
  }
  helperFindData[INDEX].sort(function(a,b) {return a-b});
  sendFinder();
},
setMethod = function(Method) {
  getElement('method'+helperFindData[0]).setAttribute('class','tagPre');
  helperFindData[0] = Method;
  getElement('method'+helperFindData[0]).setAttribute('class','tagSel');
  sendFinder();
},
sendFinder = function(page = 0, zapros = '') {
  if (getElement('nextGdps'))
    getElement('nextGdps').remove();

  if (zapros === '') {
    zapros = 'method='+helperFindData[0];
    let enteredName = getElement('gdpsNameInput').value;
    if (enteredName != '')
      zapros += '&name='+enteredName;

    helperFindData[1].forEach(function(tag) {
      zapros += '&tags[]='+tag;
    });

    helperFindData[2].forEach(function(os) {
      zapros += '&os[]='+os;
    });
  }

  Loading();
  helperRequest(`${sData[3]}new${sData[7]}?${zapros}&page=${page}&channel=${helperFindData[3]}`)
    .then(function(data) {
      let GDPSES = JSON.parse(data),
        page2 = page + 1,
        nextBtn = `sendFinder(${page2},'${zapros}')`,
        
        Count = Object.keys(GDPSES).length;
      if (helperFindData[3] == 1) {
        innerGdpsPlace(SHOWrenderMini(GDPSES), page);
      } else {
        innerGdpsPlace(GDPSrenderMini(GDPSES), page);
      }

      if (Count >= 9)
        innerGdpsPlace(insertBtn(nextBtn),-1);
      Loading(1);
    })
    .catch(function(error) {returnError(error);throw new Error();});
},
helperComments = function(postId, contentType, commPage = 0) {
  getElement('nextGdps').remove();
  let dataForNextButton = `${postId},'${contentType}',${parseInt(commPage + 1)}`;
  Loading();
  helperRequest(`${sData[0]}fetchComms${sData[7]}?id=${postId}&type=${contentType}&page=${commPage}`)
    .then(function(data) {
      let serverResp = JSON.parse(data);
      innerComments(renderComms(serverResp, contentType, dataForNextButton), 1);
      Loading(1);
    })
    .catch(function(error) {returnError(error);throw new Error();});
},

getCamp = function(id, joinData = 0) {
  if (querySelect(`[g${id}]`))
    return 0;
  let windowId = openWindow('camp'+id,
        contentPreload(id, `${id},1`, 'innerMain(pageList())')
      , 'g'+id),
      serverResp = {},
      html = '';

  serverResp.gdps = CacheCamps['c'+id];

  if (joinData !== 0)
    html = GDPSrender(serverResp, joinData);
  else 
    html = GDPSrender(serverResp);

  getElement('imageBG'+id).src = BETA_fixImg(decodeURIComponent(serverResp.gdps.ban));
  
  getElement('insertable'+id).innerHTML = html;

  getElement('imgurl'+id).innerHTML = serverResp.gdps.img;
  getElement('banurl'+id).innerHTML = serverResp.gdps.ban;
  getElement('allurl'+id).innerHTML = serverResp.gdps.link;
  getElement('title'+windowId).innerHTML = 'CAMP '+serverResp.gdps.title;

  return;
},
getShow = function(id, joinData = 0) {
  if (querySelect(`[g${id}]`))
    return 0;
  let windowId = openWindow('show'+id,
        contentPreload(id, `${id},1`, 'innerMain(pageShows())')
      , 'g'+id),
      serverResp = {},
      html = '';

  serverResp.gdps = CacheShows['s'+id];

  if (joinData !== 0)
    html = SHOWrender(serverResp, joinData);
  else 
    html = SHOWrender(serverResp);

  getElement('imageBG'+id).src = BETA_fixImg(decodeURIComponent(serverResp.gdps.ban));
  
  getElement('insertable'+id).innerHTML = html;

  console.log(getElement('imgurl'+id))
  getElement('imgurl'+id).innerHTML = serverResp.gdps.img;
  getElement('banurl'+id).innerHTML = serverResp.gdps.ban;
  getElement('allurl'+id).innerHTML = serverResp.gdps.link;
  getElement('title'+windowId).innerHTML = 'SHOW '+serverResp.gdps.title;
},
getPere = function(id, joinData = 0) {
  if (querySelect(`[g${id}]`))
    return 0;
  let windowId = openWindow('pere'+id,
        contentPreload(id, `${id},1`, 'innerMain(pageShows())')
      , 'g'+id),
      serverResp = {},
      html = '';

  serverResp.gdps = CachePeres['p'+id];

  if (joinData !== 0)
    html = PERErender(serverResp, joinData);
  else 
    html = PERErender(serverResp);

  getElement('imageBG'+id).src = BETA_fixImg(decodeURIComponent(serverResp.gdps.ban));
  
  getElement('insertable'+id).innerHTML = html;

  console.log(getElement('imgurl'+id))
  getElement('imgurl'+id).innerHTML = serverResp.gdps.img;
  getElement('banurl'+id).innerHTML = serverResp.gdps.ban;
  getElement('allurl'+id).innerHTML = serverResp.gdps.link;
  getElement('title'+windowId).innerHTML = 'SHOW '+serverResp.gdps.title;
},
pageGuides = function(wiki, backButton = '') {
  if (querySelect(`[wiki${wiki}]`))
    return 0;
  let html =
        basicButton('BAN', '', `WIKI.ban(${wiki})`)+
        basicButton('UNBAN', '', `WIKI.unban(${wiki})`)+
        basicButton('DELETE', '', `WIKI.delete(${wiki})`)+
        basicButton('ISLGBT', '', `WIKI.setlgbt(${wiki})`)+
        basicButton('ISNTLGBT', '', `WIKI.dellgbt(${wiki})`)+
        `<div id=helperContent>`+
          `<h1 align=center>`+
            `<span id=wikiName></span>`+
            ` <span${getTrans('guides09')}/span>`+
          `</h1>`+
          `<div class=gdps-list-place id=wikiInner${wiki}>`+
          `</div>`+
        `</div>`,
      windowId = openWindow('wiki'+wiki, 
        html
      , 'wiki'+wiki);
  helperRequest(`${sData[2]}!newTakeAll${sData[7]}?wiki=${wiki}`)
    .then(function(data) {
      let parsedData = JSON.parse(data),
        html = renderGuideMini(parsedData);
      getElement('wikiInner'+wiki).innerHTML = html;
      Loading(1);
    })
    .catch(function(error) {returnError(error+servError)});
},
guideACTS = function(title, guidId) {
  if (querySelect(`[guid${guidId}]`))
    return 0;
  openWindow('guid'+guidId,
    `<h1 style=margin:0>${title}</h1>`+
    basicButton('BAN', '', `GUIDES.ban(${guidId})`)+
    basicButton('UNBAN', '', `GUIDES.unban(${guidId})`)+
    basicButton('DELETE', '', `GUIDES.delete(${guidId})`)
  , 'guid'+guidId, 'width:300px;height:100px')
},
getGuide = function(id, wikiId = 0) {
  window.open('../?wikiPage='+id+'.'+wikiId)
},

getNewsWithComments = function(newsId, contentId = 0, backFunc = '') {
  setLink('news='+newsId+'.'+contentId);
  lastUsed3 = "getNewsWithComments("+newsId+","+contentId+")";
  contentPreload(`${newsId},3`, `${backFunc}(${contentId})`, 0, 0);

  Loading();
  helperRequest(`${sData[0]}newsC${sData[7]}?id=${newsId}`)
    .then(function(data) {
      if (data == '["NONE"]') {
        innerMain(pageList());
        megaAlert('CONTENTISNULL');
        Loading(1);
        return;
      }
      let dataForNextButton = `${newsId},3,1`,
        serverResp = JSON.parse(data),
        html = '';
        
      html = RenderNews(serverResp.gdps,1,'mega',backFunc);

      innerComments(renderComms(serverResp.comments, 3, dataForNextButton), 0);
      getElement('insertable').innerHTML = html;
      Loading(1);
    })
    .catch(function(error) {returnError(error);throw new Error();});
},
getGuides = function(wikiId, page) {
  if (getElement('nextGdps'))
    getElement('nextGdps').remove();

  Loading();
  helperRequest(`${sData[0]}getGuides${sData[7]}?wiki=${wikiId}&page=${page}`)
    .then(function(data) {
      let parsedData = JSON.parse(data),
        page2 = page++,
        html = renderGuideMini(parsedData, page2);
      innerGdpsPlace(html,1);
      Loading(1);
    })
    .catch(function(error) {returnError(error);throw new Error();});
},
getWikis = function(page) {
  if (getElement('nextGdps'))
    getElement('nextGdps').remove();

  Loading();
  helperRequest(`${sData[0]}getWikis${sData[7]}?page=${page}`)
    .then(function(data) {
      let parsedData = JSON.parse(data),
        page2 = page++,
        html = renderWiki(parsedData, page2);
      innerGdpsPlace(html,1);
      Loading(1);
    })
    .catch(function(error) {returnError(error);throw new Error();});
},
openForum = function(forumId) {
  contentPreload('', '', 0, 0);
  Loading();
  helperRequest(`${sData[6]}getPosts${sData[7]}?id=${forumId}`)
    .then(function(data) {
      setLink('forum='+forumId);
      Loading(1);

      let parsedData = JSON.parse(data),
        html = forumRenderMini(parsedData);

      innerGdpsPlace(html);
    })
    .catch(function(error) {returnError(error);throw new Error();});
},
getForumPost = function(forumId, postId) {
  //setLink('news='+forumId+'.'+newsId);
  //lastUsed3 = "getNewsWithComments("+forumId+","+newsId+")";
  contentPreload(`${postId},4`, `openForum(${forumId})`, 0, 0);

  Loading();
  helperRequest(`${sData[6]}getPost${sData[7]}?id=${postId}`)
    .then(function(data) {
      if (data == '["NONE"]') {
        innerMain(pageList());
        megaAlert('CONTENTISNULL');
        Loading(1);
        return;
      }
      setLink('forumPost='+forumId+'.'+postId);
      let dataForNextButton = `${postId},4,1`,
        serverResp = JSON.parse(data),
        html = '';
        
      html = forumRender(serverResp.post);

      innerComments(renderComms(serverResp.comments, 4, dataForNextButton), 0);
      getElement('insertable').innerHTML = html;
      Loading(1);
    })
    .catch(function(error) {returnError(error);throw new Error();});
},
// #endregion
// #region перевод "Налету", пожалуйста, не трогайте то что есть, я уже не помню за что какое значение отвечает
translateData = {
  RU: {
    "helperVer":"ver - +helperStrVer+ <span style=opacity:50%>(BUILD +helperBuildNum+)</span>",
    "ojhubname":"Object Hub",
    "src":"+helperUrl+imgs/RU.png",
    "hubMaster":"Бесплатный и незаменимый инструмент для обджект-шоу креаторов!",
    "main":"Главная",
    "loading...":"https://objecthub.xyz/imgs/load.svg",
    "profile":"Профиль",
    "yourProf":"Ваш профиль",
    "register":"Регистрация",
    "login":"Вход",
    "logout":"Выход",
    "logout2":"Выйти из аккаунта", 
    "dropPass":"Сбросить пароль",
    "getLogin":"Получить логин",
    "enterCode1":"Пройти верификацию",
    "enterCode2":"Введите код из почтового ящика",
    "enterCode3":"Код",
    "devices":"Устройства",
    "currentDevice":"Текущее устройство",
    "deviceNotTrust01":"Новое устройство",
    "removeDevice":"Вы уверены что хотите удалить это устройство?",
    "removeDeviceCurrent":"При удалении этого устройства будет выполнен выход с аккаунта",
    "deviceNotTrust02":"Пожалуйста, введите пароль чтобы подтвердить устройство",
    "wrongPass":"Неправильный пароль!",
    "accountEmpty":"Такого аккаунта не существует!",
    "loginClaimed":"Логин или почта уже заняты!",
    "somethingWentWrong":"Что-то пошло не так",
    "needEmailVerify":"Пожалуйста, пройдите верификацию по почте",
    "captchaDed":"Капча не пройдена!",
    "findName":"Название",

    "edit":"Изменить",
    "actions":"Действия",
    "back":"Назад",
    "helperSocials":"Наши соцсети",
    "helperDs":"Дискорд сервер",
    "helperTg":"Телеграм",
    "yes":"Да",
    "no":"Нет",
    "profName":"Никнейм",
    "profResume":"Резюме",
    "profSocials":"Соцсети",
    "profId":"ID пользователя",
    "profRole":"Роль",
    "profAccs":"Ваш аккаунт ",
    "notProfAccs":"Аккаунт ",
    "isActive":"Активирован",
    "isNotact":"Неактивирован",
    "projects":"Проекты",
    "Alarms":"Уведомления",
    "yourGuides":"Ваши Гайды",
    "yourWikis":"Ваши вики",
    "pages":"Страницы",
    "addWIki":"Создать вики",
    "alarms01":"Сообщения от администрации",
    "msgs":"Сообщения",
    "fullMsgs":"Полный текст",
    "role00":"Нет",
    "role01":"Менеджер",
    "role02":"Админ",
    "role03":"Главный Админ",

    "finder-name":"Поиск",
    "finder-channel":"Что вы будете искать?",

    "Picture01":"(рекомендуется картинка 256х256)",
    "Picture02":"(рекомендуется картинка 720х300)",
    "linkRequired":"Вы не выбрали ссылки!",
    "tagsRequired":"Вы не выбрали все теги!",

    "Camptag1":"Приз: Нету",
    "Camptag2":"Приз: Небольшой",
    "Camptag3":"Приз: Крупный",
    "Camptag4":"Приз: Особенный",
    "Camptag5":"Уникальная задумка",
    "Camptag6":"Шуточные",
    "Camptag7":"Уникальная механика",
    "Camptag8":"Короткий дедлайн",
    "Camptag9":"Длинный дедлайн",
    "Camptag10":"Один сезон",
    "Camptag11":"Роле-плей",
    "Camptag12":"Анимационный",
    "Camptag13":"Комиксный",
    "Camptag14":"Рисовальный",
    "Camptag15":"Игровой",
    "tags00":"Выберите теги",
    "os00":"Тип выполнения",

    "yourCamps":"Ваши кемпы",
    "addCamp":"Добавить кемп",
    "addCamp01":"Название:",
    "addCamp02":"Описание:",
    "addCamp03":"Ссылки на кемп:",
    "addCamp03a":"Добавить ссылку",
    "addCamp04":"Аватар кемпа:",
    "addCamp07":"Баннер кемпа:",
    "addCamp05":"Тэги:",
    "addCamp06":"ОС:",
    "afterCamp":"После изменения ваш кемп отправится на проверку модераторам",
    "editCamp":"Изменить кемп",
    "campInput01":"Название этого кемпа",
    "campInput02":"Описание этого кемпа",
    "campInput03":"http://www.boomlings.com/tools ИЛИ http://gofruit.space/gdps/XXXX",
    "campInput04":"Прямая ссылка на картинку",
    "campInput06":"Медиаконтент",
    "campInput07":"Выбрать файл",
    "campInput08":"Вставить ссылку",
    "campInput05":"https://discord.gg/gdps",
    "campunckecked":"кемп не проверен",
    "campbanned":"кемп забанен",
    "didntBan":"Ваш кемп не будет забанен",
    "searchCamps":"Кемпы",

    "yourShows":"Ваши обджект шоу",
    "Showtag1":"Комикс",
    "Showtag2":"Сериал",
    "Showtag3":"Компетишн",
    "Showtag4":"Нон компетишн",
    "Showtag5":"Драма",
    "Showtag6":"Комедия",
    "Showtag7":"Хоррор",
    "Showtag8":"Фантастика",
    "Showtag9":"Пародия",
    "Showtag12":"Короткие (1-5)",
    "Showtag13":"Средние (5-20)",
    "Showtag14":"Длинные (20-60)",
    "Showtag15":"Фильмы (60+)",
    "tags01":"Выберите жанр",
    "os01":"Длинна (мин.)",

    "yourPeres":"Ваши дубляжи",
    "Peretag1":"Комикс",
    "Peretag2":"Сериал",
    "Peretag3":"Компетишн",
    "Peretag4":"Нон компетишн",
    "Peretag5":"Драма",
    "Peretag6":"Комедия",
    "Peretag7":"Хоррор",
    "Peretag8":"Фантастика",
    "Peretag9":"Пародия",
    "Peretag12":"Короткие (1-5)",
    "Peretag13":"Средние (5-20)",
    "Peretag14":"Длинные (20-60)",
    "Peretag15":"Фильмы (60+)",
    "tags02":"Выберите жанр",
    "os02":"Длинна (мин.)",

    "addShow":"Добавить шоу",
    "addShow01":"Название:",
    "addShow02":"Описание:",
    "addShow03":"Ссылки на шоу:",
    "addShow03a":"Добавить ссылку",
    "addShow03b":"Новая ссылка",
    "addShow04":"Аватар обджект шоу:",
    "addShow07":"Баннер обджект шоу:",
    "addShow05":"Тэги:",
    "addShow06":"ОС:",
    "afterShow":"После изменения ваше шоу отправится на проверку модераторам",
    "editShow":"Изменить шоу",
    "showInput01":"Название этого шоу",
    "showInput02":"Описание этого шоу",
    "showInput03":"http://www.boomlings.com/tools ИЛИ http://gofruit.space/show/XXXX",
    "showInput04":"Прямая ссылка на картинку",
    "showInput06":"Медиаконтент",
    "showInput07":"Выбрать файл",
    "showInput08":"Вставить ссылку",
    "showInput05":"https://discord.gg/gdps",
    "showunckecked":"шоу не проверено",
    "showbanned":"шоу забанено",
    "didntBan2":"Ваш шоу не будет забанено",
    "searchShows":"Обджект шоу",

    "addPere":"Добавить дубляж",
    "addPere01":"Название:",
    "addPere02":"Описание:",
    "addPere03":"Ссылки на дубляж:",
    "addPere03a":"Добавить ссылку",
    "addPere03b":"Новая ссылка",
    "addPere04":"Аватар дубляжа:",
    "addPere07":"Баннер дубляжа:",
    "addPere05":"Тэги:",
    "addPere06":"ОС:",
    "afterPere":"После изменения ваш дубляж отправится на проверку модераторам",
    "editPere":"Изменить дубляж",
    "pereInput01":"Название дубляжа",
    "pereInput02":"Описание дубляжа",
    "pereInput03":"http://www.boomlings.com/tools ИЛИ http://gofruit.space/pere/XXXX",
    "pereInput04":"Прямая ссылка на картинку",
    "pereInput06":"Медиаконтент",
    "pereInput07":"Выбрать файл",
    "pereInput08":"Вставить ссылку",
    "pereInput05":"https://discord.gg/gdps",
    "pereunckecked":"дубляж не проверен",
    "perebanned":"дубляж забанен",
    "didntBan3":"Ваш дубляж не будет забанен",
    "searchPeres":"Дубляжи",

    "otherSort":"Метод поиска",
    "mostLike":"Самое лайкнутое",
    "mostDisl":"Самое дизлайкнутое",
    "search1":"Последняя активность",
    "search4":"Самые новые",
    "findByName":"Найдите по названию",

    "special00":"Благодарит этих людей за помощь в создании newHelper.js",
    "special01":"Обучение мчайдена программированию",
    "special03":"Оптимизация некоторых алгоритмов в логике пользователей",
    "special04":"Привет это я мчайден!!! создатель Object hub и newHelper.js!!",
    "special05":"Дизайн newHelper.js",
    "special08":"Вы все, кто пользуется сайтом",
    "special09":"Помощь в создании системы перевода \"налету\"",
    "special10":"Огромная помощь в дизайне и логике рендера",
    "special11":"Отдельные благодарности этим людям, за помощь с созданием самого сайта",
    "special12":"Логотип и баннер сайта",
    "special13":"Главная страница сайта, и весь последующий дизайн",

    "addedBy":"Автор",
    "moreInfo":"Подробнее",
    "getLink":"Скопировать ссылку",
    "showMore":"Показать больше",
    "loggedAs":"Вход выполнен",
    "commSend":"Отправить",
    "min10chars":"минимум 10 символов",
    "commSizeFail":"Комментарий слишком короткий!",
    "joinToGdps":"Войти",
    "openGdps":"Открыть",
    "account":"Аккаунт",
    "news":"Новости",
    "newsList":"Список новостей",
    "newsNone":"Ничего нет",
    "newsNoneReal":"Новостей нет",

    "remindPass":"Забыли пароль?",
    "login01":"Логин или Почта",
    "login02":"Пароль",
    "login03":"Адрес эл. почты",
    "login04":"Новый пароль",
    "login05":"Адрес эл. почты",
    "login06":"Логин",

    "gdpsLangRU":"Русский",
    "gdpsLangEN":"Английский",
    "gdpsLangUA":"Украинский",
    "gdpsLangJJ":"Пиратский",

    "gdpsLang00":"Язык",
    "gdpsLang01":"Русский",
    "gdpsLang02":"Английский",
    "gdpsLang03":"Испанский",
    "delete":"Удалить",
    "coowners":"Со-владельцы",
    "idOrName":"ID пользователя",
    "joinsTo":"Переходы на",
    "joins":"Переходы",
    "coownersNone":"Вы со-владелец",
    "isJE":"Требуется авторизация на сайте для входа в дискорд сервер",
    "isBL":"Бампнуть",
    "noConnectWiki":"Привязать вики",
    "connectedWiki":"Перепривязать вики",
    "noConnectContent":"Привязать контент",
    "connectedContent":"Перепривязать контент",
    "openConnectedWiki":"Открыть вики",
    "openConnectedContent":"Открыть привязанный контент",
    "files":"Файлы",
    "fileUplo":"Выложить файл",
    "fileTitle":"Имя файла в вики",
    "fileSizeAlert":"Превышен лимит в 16 мегабайт!",
    "fileLargeAlert":"Файл больше 6 мегабайт!",
    "fileGetLink":"Скоп. ссылку",
    "fileSure":"Вы уверены что хотите удалить этот файл?",

    "templates":"Шаблоны",
    "tempNew":"Создать шаблон",
    "tempEditor":"Редактор шаблона",

    "forumNone":"Создать форум",
    "forumHas":"Открыть форум",
    "forumAlert":"Вы уверены что хотите создать форум? (Это действие нельзя будет отменить!)",
    "vacancies":"Вакансии",
    "vacRespond":"Откликнуться",
    "vacResponded":"Вы откликнулись",
    "vacResponses":"Отклики",
    "removeSure":"Вы уверены?",
    "editVacs":"Изменить вакансию",
    "addVacs":"Добавить вакансию",
    "addVacs01":"Название:",
    "addVacs01a":"Выберите проект на который хотите добавить вакансию",
    "addVacs03a":"Сначала нужно добавить проект!",
    "addVacs02":"Описание:",
    "addVacs03":"Тэги:",
    "vacsInput01":"Название вакансии",
    "vacsInput02":"Описание вакансии",
    
    "mainWikiNone":"Установить главную страницу",
    "mainWikiHas":"Изменить главную страницу",

    "comms":"Комментарии",
    "commsNone":"Комментариев нет",
    "submit":"Подтвердить",
    "passReset":"Сброс пароля",
    "passResetIf":"Если вдруг вы владеете старым аккаунтом, на котором не привязана почта то обратитесь к администрации в дискорде",
    "addNews":"Новый новостной пост",
    "publishNews":"Опубликовать",
    "newsText":"Текст поста",
    "needLogin":"Требуется регистрация",

    "timeAgo01":" секунд",
    "timeAgo02":" минут и ",
    "timeAgo03":" секунд",
    "timeAgo04":" часов и ",
    "timeAgo05":" минут",
    "timeAgo06":" дней и ",
    "timeAgo07":" часов",
    "timeAgo08":" недель и ",
    "timeAgo09":" дней",
    "timeAgo10":" месяцев и ",
    "timeAgo11":" недель",
    "timeAgo12":"давно",
    "timeAgo13":" назад",
    "report01":"Жалоба",
    "report02":"Причина жалобы (например - не работает ссылка входа)",
    "report03":"Причина жалобы (например - контент нарушает закон)",
    "otmena":"Отмена",
    "copied":"Скопировано!",
    "reported":"Отправлено!",

    "aboutHelper":"О Object Hub",
    "history01":"Историческая справка",
    "history02":"27 октября 2023 началась разработка над сайтом предшественником, которая закончилась через 2 дня и была забыта. 20 августа 2024 Мчайден взяла за основу другой свой сайт и начала делать Object Hub, 28 августа вышла первая бета версия",
    "history03":"22 марта 2025 года Object hub был перенесён на newHelper.js 1.91 и вместе с этим получил много других полезных и удобных фич",
    "HLthanks":"Особые благодарности",
    "newNick":"новый никнейм",
    "newResume":"новое резюме",
    "newSocials":"сменить соцсети",
    "guides00":"Страницы",
    "guides01":"Новая страница",
    "guides02":"Название",
    "guides03":"Добавить раздел",
    "guides04":"Послесловие (например кто автор)",
    "guides05":"Картинка (формат 2:1)",
    "guides06":"Название раздела",
    "guides09":"Вики",
    "guides09-":"вики",
    "wikiName":"Название вики",
    "Markdown":"Текст раздела\n\nесть поддержка Markdown\n\nдля добавления картинки введите ![](link)",
    "wikiText":"Язык разметки, используемый на многих вики (представлен частично)",
    "MediaRender":"MediaRender используется для создания комиксов\n\nПросто вставляйте на каждой новой строке ссылку на отдельную страницу вашего комикса",
    "tagSetup01":"Тег страницы",
    "tagSetup02":"Установить",
    "tagBan01":"Нельзя начинать с цифры!",
    "tagBan02":"Разрешены только латинские буквы, цифры не в начале и дефис!",
    "tagBan03":"Тег уже занят!",
    "tagDone":"Тег установлен",
    
    "CONTENTISNULL":"ОШИБКА: data пустой, не вводите неправильный ID",
    "wait1":"Жди ещё ",
    "wait2":" секунд",
    "bumped":"Бампнуто!",
    "newPost":"Новый пост",
    
    "T2-hi":"Добро пожаловать на",
    "T1-createdForHelp":"Создан, чтобы помогать",
    "T1-insertAbout":"Я хочу добавить обджект шоу!",
    "T1-insertHelp":"Для этого вам необходимо зарегистрировать аккаунт, зайти в профиль, перейти в \"Ваши обджект-шоу, или кемпы, нажать на кнопку \"Добавить и заполнить форму. Подтверждение вашего проекта не займёт больше часа.",
    "T2-promo1":"не знаешь с чего начать?",
    "T2-promo2":"Не беда! Используйте кнопки снизу, либо же зайдите на страницу поиска, выберите теги и нужный метод поиска. Поиск автоматически реагирует на изменения.",
    "T2-promo3":"Совет: если вы хотите найти что нибуть по имени, лучше убрать все теги.",
    "fileWarning":"Если вы хотите заменить картинку, выберите файл повторно, если же хотите оставить картинку то не трогайте ссылку",

    "settings000":"Настройки",
    "settings001":"Настройки рендеринга newHelper.js",
    "settings002":"Применить настройки",
    "settings003":"Сбросить настройки",
    "settings004":"Применить схему",
    "settings005":"Цветовая схема",
    "settings006":"Масштабирование",
    "settings007":"Вызвать ложную ошибку",
    "settings008":"Вызвать ложную ошибку сети",
    "settings009":"Рендеринг",
    "Text;Tags":"Показать сначала теги/текст",
    "guidFull;guidWindow":"Открывать редактор страниц на весь экран/в окне",
    "Bg":"Фон",
    "Bg-alpha":"Фон (прозрачный)",
    "Main":"Основной",
    "Light":"Светлый",
    "Window":"Окна",
    "Profile":"Фон профиля",
    "Profile-alpha":"Фон профиля (прозрачный)",
    "Black":"Чёрный",
    "White":"Белый",
    "Font":"Размер шрифта",
    "Btn-size":"Размер кнопок",
    "Text-indent":"Отступы текста",
    "Border-small":"Закругление малых рамок",
    "Border":"Закругление рамок",
    "Border-large":"Закругление больших рамок",

    "WINDOW-close":"Закрыть",
    "WINDOW-logonWindow":"Вход",
    "WINDOW-logon2Window":"Регистрация",
    "WINDOW-REPform":"Жалоба на проект",
    "WINDOW-REPform2":"Жалоба на пост",
    "WINDOW-forumAlert":"Создание форума",
    "WINDOW-getLogin":"Получение логина",
    "WINDOW-getLogin2":"Чтение логина",
    "WINDOW-contentToWiki":"Привязка проекта",
    "WINDOW-wikiToContent":"Привязка вики",
    "WINDOW-setMainPage":"Главная страница",
    "WINDOW-uploadFiles":"Загрузка файлов",
    "WINDOW-deleteFile":"Удаление файла",
    "WINDOW-guidSettings":"Настройки страницы"
  },
  EN: {
    "helperVer":"ver - +helperStrVer+ <span style=opacity:50%>(BUILD +helperBuildNum+)</span>",
    "ojhubname":"Object Hub",
    "src":"+helperUrl+imgs/EN.png",
    "hubMaster":"A free and essential tool for object show creators!",
    "main":"Home",
    "loading...":"https://objecthub.xyz/imgs/load.svg",
    "profile":"Profile",
    "yourProf":"Your profile",
    "register":"Register",
    "login":"Login",
    "logout":"Logout",
    "logout2":"Logout of your account",
    "dropPass": "Reset password",
    "getLogin":"Get login",
    "enterCode1":"Pass verification",
    "enterCode2":"Enter the code from your mailbox",
    "enterCode3":"Code",
    "devices":"Devices",
    "currentDevice":"Current device",
    "deviceNotTrust01":"New device",
    "removeDevice":"Are you sure you want to delete this device?",
    "removeDeviceCurrent":"Removing this device will sign you out of your account",
    "deviceNotTrust02":"Please enter the password to confirm the device",
    "wrongPass":"Wrong Password!",
    "accountEmpty":"That account doesn't exist!",
    "loginClaimed":"Login or mail is already taken!",
    "somethingWentWrong":"Something went wrong",
    "needEmailVerify":"Please verify by email",
    "captchaDed":"Captcha didn't pass!",
    "findName":"Name",
    
    "edit":"Edit",
    "actions":"Actions",
    "back":"Back",
    "helperSocials":"Our social media",
    "helperDs":"Discord server",
    "helperTg":"Telegram",
    "yes":"Yes",
    "no":"No",
    "profName":"Nickname",
    "profResume":"Resume",
    "profSocials":"Socials",
    "profId":"UserID",
    "profRole":"Role",
    "profAccs":"Your account ",
    "notProfAccs":"Account",
    "isActive":"Activated",
    "isNotact":"Unactivated",
    "projects":"Projects",
    "Alarms":"Notifications",
    "yourGuides":"Your Guides",
    "yourWikis":"Your wikis",
    "pages":"Pages",
    "addWIki":"Create wiki",
    "alarms01":"Messages by administration",
    "msgs":"Messages",
    "fullMsgs":"Full text",
    "role00":"No",
    "role01":"Manager",
    "role02":"Admin",
    "role03":"Head Admin",
    
    "finder-name":"Search",
    "finder-channel":"What will you looking for?",
    
    "Picture01":"(recommended picture 256x256)",
    "Picture02":"(recommended picture 720x300)",
    "linkRequired":"You have not selected any links!",
    "tagsRequired":"You have not selected all tags!",
    
    "Camptag1":"Prize: None",
    "Camptag2":"Prize: Small",
    "Camptag3":"Prize: Large",
    "Camptag4":"Prize: Special",
    "Camptag5":"Unique twist",
    "Camptag6":"Humorous",
    "Camptag7":"Unique mechanics",
    "Camptag8":"Short deadline",
    "Camptag9":"Long deadline",
    "Camptag10":"One season",
    "Camptag11":"Role-play",
    "Camptag12":"Animated",
    "Camptag13":"Comic",
    "Camptag14":"Drawing",
    "Camptag15":"Playful",
    "tags00":"Select tags",
    "os00":"Completion type",
    
    "yourCamps":"Your camps",
    "addCamp":"Add camp",
    "addCamp01":"Title:",
    "addCamp02":"Description:",
    "addCamp03":"Links to camp:",
    "addCamp03a":"Add link",
    "addCamp04":"Camp Avatar:",
    "addCamp07":"Camp Banner:",
    "addCamp05":"Tags:",
    "addCamp06":"OS:",
    "afterCamp":"After edit, your camp will be sent to the moderators for review",
    "editCamp":"Edit camp",
    "campInput01":"Camp name",
    "campInput02":"Camp description",
    "campInput03":"http://www.boomlings.com/tools OR http://gofruit.space/gdps/XXXX ",
    "campInput04":"Direct link to picture",
    "campInput06":"Mediacontent",
    "campInput07":"Select file",
    "campInput08":"Insert link",
    "campInput05":"https://discord.gg/gdps",
    "campunckecked":"Camp not checked",
    "campbanned":"Camp banned",
    "didntBan":"your camp won't be banned",
    "searchCamps":"Camps",
    
    "yourShows":"Your object shows",
    "Showtag1":"Comics",
    "Showtag2":"Serial",
    "Showtag3":"Competition",
    "Showtag4":"Noncompetition",
    "Showtag5":"Drama",
    "Showtag6":"Comedy",
    "Showtag7":"Horror",
    "Showtag8":"Fiction",
    "Showtag9":"Parody",
    "Showtag12":"Short (1-5)",
    "Showtag13":"Medium (5-20)",
    "Showtag14":"Long (20-60)",
    "Showtag15":"Films (60+)",
    "tags01":"Select genre",
    "os01":"Series length (min.)",
    
    "yourPeres":"Your dubbings",
    "Peretag1":"Comics",
    "Peretag2":"Serial",
    "Peretag3":"Competition",
    "Peretag4":"Noncompetition",
    "Peretag5":"Drama",
    "Peretag6":"Comedy",
    "Peretag7":"Horror",
    "Peretag8":"Fiction",
    "Peretag9":"Parody",
    "Peretag12":"Short (1-5)",
    "Peretag13":"Medium (5-20)",
    "Peretag14":"Long (20-60)",
    "Peretag15":"Films (60+)",
    "tags02":"Select genre",
    "os02":"Series length (min.)",
    
    "addShow":"Add show",
    "addShow01":"Title:",
    "addShow02":"Description:",
    "addShow03":"Links to show:",
    "addShow03a":"Add link",
    "addShow03b":"New link",
    "addShow04":"Object show Avatar:",
    "addShow07":"Object show Banner:",
    "addShow05":"Tags:",
    "addShow06":"OS:",
    "afterShow":"After edit, your show will be sent to the moderators for review",
    "editShow":"Edit show",
    "showInput01":"Object show name",
    "showInput02":"Object show description",
    "showInput03":"http://www.boomlings.com/tools OR http://gofruit.space/gdps/XXXX ",
    "showInput04":"Direct link to picture",
    "showInput06":"Mediacontent",
    "showInput07":"Select file",
    "showInput08":"Insert link",
    "showInput05":"https://discord.gg/gdps",
    "showunckecked":"Show not checked",
    "showbanned":"Show banned",
    "didntBan2":"your show won't be banned",
    "searchShows":"Object shows",
    
    "addPere":"Add dubbing",
    "addPere01":"Title:",
    "addPere02":"Description:",
    "addPere03":"Dubbing links:",
    "addPere03a":"Add link",
    "addPere03b":"New link",
    "addPere04":"Dubbing avatar:",
    "addPere07":"Dubbing banner:",
    "addPere05":"Tags:",
    "addPere06":"OS:",
    "afterPere":"After editing, your dubbing will be sent to moderators for review",
    "editPere":"Edit dubbing",
    "pereInput01":"Dubbing name",
    "pereInput02":"Dubbing description",
    "pereInput03":"http://www.boomlings.com/tools OR http://gofruit.space/pere/XXXX",
    "pereInput04":"Direct link to image",
    "pereInput06":"Media content",
    "pereInput07":"Select file",
    "pereInput08":"Insert link",
    "pereInput05":"https://discord.gg/gdps",
    "pereunckecked":"Dubbing not verified",
    "perebanned":"Dubbing banned",
    "didntBan3":"Your dubbing will not be banned",
    "searchPeres":"Dubbings",
    
    "otherSort":"Search method",
    "mostLike":"Most liked",
    "mostDisl":"Most disliked",
    "search1":"Last activity",
    "search4":"Recent",
    "findByName":"Find by name",
    
    "special00":"Thanks these people for help in creating newHelper.js",
    "special01":"Teaching mchiden to program",
    "special03":"Optimizing some algorithms in user logic",
    "special04":"Hi its me mchiden!!! Object hub and newHelper.js creator!!",
    "special05":"Design for newHelper.js",
    "special08":"All of you who use the site",
    "special09":"Assistance in creating a translation system",
    "special10":"Huge help with design and render logic",
    "special11":"Special thanks to these people for helping with the creation of the site itself.",
    "special12":"Logo and website banner",
    "special13":"Main page of website, and all subsequent design",
    
    "addedBy":"Author",
    "moreInfo":"Learn more",
    "getLink":"Copy link",
    "showMore":"Show more",
    "loggedAs":"Logged as",
    "commSend":"Send",
    "min10chars":"Minimum 10 characters",
    "commSizeFail":"Comment too short!",
    "joinToGdps":"Join",
    "openGdps":"Open",
    "account":"Account",
    "news":"News",
    "newsList":"News list",
    "newsNone":"Nothing found",
    "newsNoneReal":"Here isn't news",
    
    "remindPass":"Forgot password?",
    "login01":"Login or Email",
    "login02":"Password",
    "login03":"Email address",
    "login04":"New password",
    "login05":"Email address",
    "login06":"Login",
    
    "gdpsLangRU":"Russian",
    "gdpsLangEN":"English",
    "gdpsLangUA":"Ukrainian",
    "gdpsLangJJ":"Pirate",
    
    "gdpsLang00":"Language",
    "gdpsLang01":"Russian",
    "gdpsLang02":"English",
    "gdpsLang03":"Spanish",
    "delete":"Delete",
    "coowners":"Co-owners",
    "idOrName":"userID",
    "joinsTo":"Joins to",
    "joins":"Joins",
    "coownersNone":"You are co-owner",
    "isJE":"Authorization required to join in discord server",
    "isBL":"Bump",
    "noConnectWiki":"Connect wiki",
    "connectedWiki":"Reconnect wiki",
    "noConnectContent":"Connect content",
    "connectedContent":"Reconnect content",
    "openConnectedWiki":"Open wiki",
    "openConnectedContent":"Open linked content",
    "files":"Files",
    "fileUplo":"Upload file",
    "fileTitle":"File wiki name",
    "fileSizeAlert":"16 megabyte limit exceeded!",
    "fileLargeAlert":"The file is larger than 6 megabytes!",
    "fileGetLink":"copy link",
    "fileSure":"Are you sure you want to delete this file?",
    
    "templates":"Templates",
    "tempNew":"Create template",
    "tempEditor":"Template editor",
    
    "forumNone":"Create forum",
    "forumHas":"Open forum",
    "forumAlert":"are you sure you want create forum? (this action can not be canceled!)",
    "vacancies":"Vacancies",
    "vacRespond":"Apply",
    "vacResponded":"You responded",
    "vacResponses":"Applications",
    "removeSure":"Are you sure?",
    "editVacs":"Edit vacancy",
    "addVacs":"Add vacancy",
    "addVacs01":"Title:",
    "addVacs01a":"Select the project for which you want to add a vacancy",
    "addVacs03a":"First you need to add a project!",
    "mainWikiNone":"Setup main page",
    "mainWikiHas":"Edit main page",
    
    "comms":"Comments",
    "commsNone":"There are no comments",
    "submit":"Confirm",
    "passReset":"Password Reset",
    "passResetIf":"If you suddenly own an old account that does not have mail linked to it, then contact the admins in the discord",
    "addNews":"New News Post",
    "publishNews":"Publish",
    "newsText":"text",
    "needLogin":"Registration is required",
    
    "timeAgo01": " seconds ", 
    "timeAgo02": " minutes and ", 
    "timeAgo03": " seconds ", 
    "timeAgo04": " hours and ", 
    "timeAgo05": " minutes ", 
    "timeAgo06": " days and ", 
    "timeAgo07": " hours ", 
    "timeAgo08": " weeks and ", 
    "timeAgo09": " days ", 
    "timeAgo10": " months and ", 
    "timeAgo11": " weeks ", 
    "timeAgo12": "long ago", 
    "timeAgo13":" ago",
    "report01":"Report",
    "report02":"Reason of report (for example, the join link does not work)",
    "report03":"Reason of report (for example, content violates the law)",
    "otmena":"Cancel",
    "copied":"Copied!",
    "reported":"Sent!",
    
    "aboutHelper":"About Object Hub",
    "history01":"Historical note",
    "history02":"On October 27, 2023 development began on the predecessor site, which ended after 2 days and was forgotten. On August 20, 2024, Mchiden took her other site as a basis and started making Object Hub, with the first beta version released on August 28th",
    "history03":"On March 22, 2025, Object hub was ported to newHelper.js 1.91 and along with it got many other useful and handy features",
    "HLthanks":"Special thanks",
    "newNick":"new nickname",
    "newResume":"new resume",
    "newSocials":"change socials",
    "guides00":"Pages",
    "guides01":"New page",
    "guides02":"Title",
    "guides03":"Add Section",
    "guides04":"Afterword (such as who the author)",
    "guides05":"Picture (2:1 format)",
    "guides06":"Title of section",
    "guides09":"Wiki",
    "guides09-":"wiki",
    "wikiName":"Wiki name",
    "Markdown":"Section text\n\nthere is Markdown support\n\nto add image enter ![](link)",
    "wikiText":"Markup language used on many wikis (partially represented)",
    "MediaRender":"MediaRender is used to create comic strips\n\nJust insert on each new line a link to a page of your comic strip",
    "tagSetup01":"Page tag",
    "tagSetup02":"Setup",
    "tagBan01":"You can't start with a number!",
    "tagBan02":"Only Latin letters, numbers not at the beginning and hyphens are allowed!",
    "tagBan03":"Tag already taken!",
    "tagDone":"Tag is set",
    
    "CONTENTISNULL":"ERROR: data is null, don't write wrong ID",
    "wait1":"Wait ",
    "wait2":" seconds",
    "bumped":"Bumped!",
    "newPost":"New post",
    
    "T2-hi":"Welcome on",
    "T1-createdForHelp":"Created for help",
    "T1-insertAbout":"I want to add an object show!",
    "T1-insertHelp":"To do this, you need to register an account, go to your profile, go to \"Your object shows, or camps, click the \"Add button and fill out the form. Confirmation of your project will not take more than an hour.",
    "T2-promo1":"don't know where to start?",
    "T2-promo2":"No problem! Use the buttons below, or go to the search page, select tags and the desired search method. Search automatically responds to changes.",
    "T2-promo3":"Tip: If you want to search for something by name, it's best to remove all tags.",
    "fileWarning":"If you want to replace picture, select the file again, if you want to keep picture, don't touch link",
    
    "settings000":"Settings",
    "settings001":"newHelper.js render settings",
    "settings002":"Apply settings",
    "settings003":"Reset settings",
    "settings004":"Apply scheme",
    "settings005":"Color scheme",
    "settings006":"Scaling",
    "settings007":"Call a false error",
    "settings008":"Call a false network error",
    "settings009":"Rendering",
    "Text;Tags":"Show tags/text first",
    "guidFull;guidWindow":"Open page editor in full screen/in window",
    "Bg":"Background",
    "Bg-alpha":"Background (transparent)",
    "Main":"Main",
    "Light":"Light",
    "Window":"Windows",
    "Profile":"Profile background",
    "Profile-alpha":"Profile background (transparent)",
    "Black":"Black",
    "White":"White",
    "Font":"Font size",
    "Btn-size":"Buttons size",
    "Text-indent":"Text indents",
    "Border-small":"Small frames rouding",
    "Border":"Frames rouding",
    "Border-large":"Large frames rouding",
    
    "WINDOW-close":"Close",
    "WINDOW-logonWindow":"Login",
    "WINDOW-logon2Window":"Register",
    "WINDOW-REPform":"Report project",
    "WINDOW-REPform2":"Report post",
    "WINDOW-forumAlert":"Creating forum",
    "WINDOW-getLogin":"Getting login",
    "WINDOW-getLogin2":"Reading login",
    "WINDOW-contentToWiki":"Project binding",
    "WINDOW-wikiToContent":"Wiki binding",
    "WINDOW-setMainPage":"Main page",
    "WINDOW-uploadFiles":"Uploading files",
    "WINDOW-deleteFile":"Deleting files",
    "WINDOW-guidSettings":"Page settings"
  }
},
getTrans = function(id, renderType = 'text') {
  try {
    let getText = id=>translateData[mainLang][id];
    switch (renderType) {
      case 'text':        return ` data-trans="${id}">${getText(id)}<`;
      case 'textButton':    return ` data-trans="${id}">${getText(id)}`;
      case 'input':       return ` data-trans="${id}" placeholder="${getText(id)}">`;
      case 'inputValue':    return ` data-trans="${id}" value="${getText(id)}">`;
      case 'textarea':      return ` data-trans="${id}" placeholder="${getText(id)}"><`;
      case 'img':         return ` data-trans="${id}" src="${getText(id)}"`;
      default:          return getText(id);
    }
  } catch (err) {
    let getText = id=>translateData['RU'][id];
    switch (renderType) {
      case 'text':        return ` data-trans="${id}">${getText(id)}<`;
      case 'textButton':    return ` data-trans="${id}">${getText(id)}`;
      case 'input':       return ` data-trans="${id}" placeholder="${getText(id)}">`;
      case 'inputValue':    return ` data-trans="${id}" value="${getText(id)}">`;
      case 'textarea':      return ` data-trans="${id}" placeholder="${getText(id)}"><`;
      case 'img':         return ` data-trans="${id}" src="${getText(id)}"`;
      default:          return getText(id);
    }
    returnError(err);
  }
},
translateReplaceLang = function(lang) {
  Console.time(helperBuildNum);
  mainLang = lang;
  Slocal.set('Lang', lang);
  document.querySelectorAll('[data-trans]').forEach(function(el) {
    let KEY = el.getAttribute('data-trans');
    switch (el.tagName) {
      case 'IMG':         el.src = translateData[lang][KEY];            break;
      case 'INPUT':
      case 'TEXTAREA':      el.setAttribute('placeholder', translateData[lang][KEY]); break;
      default:          el.innerHTML = translateData[lang][KEY];          break;
    }
  });
  Console.timeEnd(helperBuildNum);
},
// #endregion
// #region вставка в разные куски страницы, функция innerMain упомянута тут, за остальные поясню ниже
innerMain = function(textContent, insertType = 0) {
  if (!helperMain) 
    return new Error('Cant find main helper ("1st") element! Maybe you broken helperApp?');
  if (insertType == 0) 
    helperMain.innerHTML = textContent;
  else 
    helperMain.insertAdjacentHTML('beforeend', textContent);
},
// вставка контента в правую половину окна профилей, для телефонов замена всего экрана
innerProfile = function(textContent) {
  if (getElement('profileWindow')) 
    getElement('profileWindow').innerHTML = textContent;
  else 
    return new Error('Cant find "profileWindow" element!');
},
// вставка контента под рамкой поиска
innerGdpsPlace = function(textContent, insertType = 0) {
  if (!getElement('GDPSesPlace')) 
    return new Error('Cant find "GDPSesPlace" element!');

  if (insertType == 0) // профили
    getElement('GDPSesPlace').innerHTML = textContent;
  else if (insertType == 512)
    getElement('GDPSesPlace').insertAdjacentHTML('beforebegin', textContent);
  else if (insertType >= 1) // в поиске устарел
    getElement('GDPSesPlace').insertAdjacentHTML('beforeend', textContent);
  else // в поиске но лучще это
    getElement('GDPSesPlace').insertAdjacentHTML('afterend', textContent);
},
// вставка контента в рамку комментариев, прошу обратить внимание ибо у гдпсов она справа, а у гайдов и текстур заполняет весь экран
innerComments = function(id, textContent, insertType = 0) {
  if (!getElement('comments'+id)) 
    return new Error('Cant find "comments" element!');
  if (insertType == 0) // при рендере гдпса
    getElement('comments'+id).innerHTML = textContent;
  else 
    // а эт вроде когда "показать больше"
    getElement('comments'+id).insertAdjacentHTML('beforeend', textContent);
},
// вставка контента в рамку гайдов, как попало если что
innerGuides = function(textContent, insertType = 0) {
  if (!getElement('guidesPlace')) 
    return new Error('Cant find "guidesPlace" element!');
  if (insertType == 0)
    getElement('guidesPlace').insertAdjacentHTML('beforeend',textContent);
  else 
    getElement('guidesPlace').insertAdjacentHTML('afterend',textContent);
},
// #endregion
// #region разные формы
sendRegisterForm = function(wId) {
  let username = getElement('LGusername').value,
    password = getElement('LGpassword').value,
    email  = getElement('LGemail'   ).value,
    hcaptcha = document.querySelector('[data-hcaptcha-response]').getAttribute('data-hcaptcha-response');
    //hcaptcha = getElement(wId+'cap').getAttribute('data-hcaptcha-response');
  if (hcaptcha || ignoreCap) {
    Loading();
    helperRequest(
      `${sData[5]}register${sData[7]}`,
      `username=${username}&password=${password}&email=${email}`+
      `&g-recaptcha-response=${hcaptcha}&h-captcha-response=${hcaptcha}`
    )
    .then(function(data) {
      switch (data) {
        case '-1':
          megaAlert('loginClaimed');
          break;
        case '-2':
          megaAlert('captchaDed');
          break;
        default:
          let serverResp = JSON.parse(data);
          CacheCamps = serverResp[1];
          CacheShows = serverResp[2];
          CacheWikis = serverResp[3];
          thisUser = serverResp[0];
          myCamps = [];
          myShows = [];
          myguides = [];
          myCamps.push(serverResp[3][0]);
          myShows.push(serverResp[3][1]);
          myguides.push(serverResp[3][2]);
          token = thisUser[5];
          Slocal.set('User', token);
          thisUser.pop();

          getElement('regBtn').remove();
          getElement('btnLogin').innerHTML = `<span style="position:absolute;right:0;top:-8px">${thisUser[0]}</span>`;

          if (getElement('regBtn2')) {
            getElement('regBtn2').innerHTML = getTrans('logout', 0);
            getElement('regBtn2').setAttribute('data-trans', 'logout');
            getElement('regBtn2').setAttribute('onclick', 'gLogout()');
          }
          if (getElement('btnLogin2')) {
            getElement('btnLogin2').innerHTML = thisUser[0];
            getElement('btnLogin2').removeAttribute('data-trans');
            getElement('btnLogin2').setAttribute('onclick', 'innerMain(profilePage())');
          }
          innerMain(profilePage());
          document.querySelectorAll('[isloginwindow]').forEach(function(el) {
            closeWindow(el.id);
          });
      }
      Loading(1);
    })
    .catch(function(error) {returnError(error);throw new Error();});
  } else {
    megaAlert('captchaDed');
  };
},
sendLoginForm = function(wId) {
  let username = getElement('LGusername').value,
    password = getElement('LGpassword').value,
    hcaptcha = document.querySelector('[data-hcaptcha-response]').getAttribute('data-hcaptcha-response');
    //hcaptcha = getElement(wId+'cap').getAttribute('data-hcaptcha-response');
  Console.log(hcaptcha)
  if (hcaptcha || ignoreCap) {
    Loading();
    helperRequest(
      `${sData[5]}login${sData[7]}`,
      `username=${username}&password=${password}`+
      `&g-recaptcha-response=${hcaptcha}&h-captcha-response=${hcaptcha}`
    )
    .then(function(data) {
      switch (data) {
        case '-1':
          megaAlert('wrongPass');
          break;
        case '-2':
          megaAlert('accountEmpty');
          break;
        case '-3':
          megaAlert('captchaDed');
          break;
        default:
          let serverResp = JSON.parse(data);
          CacheCamps = serverResp[1];
          CacheShows = serverResp[2];
          CacheWikis = serverResp[3];
          thisUser = serverResp[0];
          myCamps = [];
          myShows = [];
          myguides = [];
          myCamps.push(serverResp[3][0]);
          myShows.push(serverResp[3][1]);
          myguides.push(serverResp[3][2]);
          token = thisUser[5];
          Slocal.set('User', token);
          thisUser.pop();

          getElement('regBtn').remove();
          getElement('btnLogin').innerHTML = `<span style="position:absolute;right:0;top:-8px">${thisUser[0]}</span>`;

          if (getElement('regBtn2')) {
            getElement('regBtn2').innerHTML = getTrans('logout', 0);
            getElement('regBtn2').setAttribute('data-trans', 'logout');
            getElement('regBtn2').setAttribute('onclick', 'gLogout()');
          }
          if (getElement('btnLogin2')) {
            getElement('btnLogin2').innerHTML = thisUser[0];
            getElement('btnLogin2').removeAttribute('data-trans');
            getElement('btnLogin2').setAttribute('onclick', 'innerMain(profilePage())');
          }
          innerMain(profilePage());
          document.querySelectorAll('[isloginwindow]').forEach(function(el) {
            closeWindow(el.id);
          });
      }
      Loading(1);
    })
    .catch(function(error) {returnError(error);throw new Error();});
  } else {
    megaAlert('captchaDed');
  };
},
sendDrop = function() {
  let username = getElement('LGusername').value,
    password = getElement('LGpassword').value,
    email  = getElement('LGemail'   ).value;
  Loading();
  helperRequest(
    `${sData[5]}drop${sData[7]}`,
    `username=${username}&password=${password}&email=${email}`
  )
  .then(function(data) {
    if (data == '-1')
      return alert('неа');
    else if (data == '1')
      if (thisUser[0] !== '???')
        dropLogin(1);
    Loading(1);
  })
  .catch(function(error) {returnError(error);throw new Error();});
},
gLogout = function() {
  thisUser = ['???',0,0,0,0];
  Slocal.remove('User');
  token = undefined;
  innerMain(pageMain());
},

editNickPre = function() {
  getElement('newNick').innerHTML =
  `<input class="framelabel" id=newNick2${getTrans('newNick', 'input')}`+
  `<button onclick="editNick()" class=loginbtn${getTrans('edit')}/button>`;
},
editNick = function() {
  let newNick = getElement('newNick2').value;
  helperRequest(`${sData[5]}setNickname${sData[7]}?name=${newNick}`)
    .then(function(data) {
      let timename = thisUser[0].slice();
      thisUser[0] = data;
      for (let gdpsKey in myCamps[0]) 
        if (myCamps[0][gdpsKey].userName == timename) 
          myCamps[0][gdpsKey].userName = data;

      // потом сделать во всех гдпсах и текстурах
      getElement('oldNick').innerHTML = data;
      getElement('newNick').innerHTML = '';
    })
},

sendLike = function(id, channel, isComm = 0) {
  if (thisUser[1] === 0)
    return megaAlert('needLogin');

  Loading();
  let data = 'ide=' + encodeURIComponent(id) + '&type=' + encodeURIComponent(channel);
  helperRequest(`${sData[1]}like${sData[7]}`, data)
    .then(function(data) {
      if (!isComm)
        getElement('likesCount' + id).innerText = data;
      else 
        getElement('likesCountComm' + id).innerText = data;
      Loading(1);
    })
    .catch(function(error) {returnError(error);throw new Error();});
},
sendDislike = function(id, channel, isComm = 0) {
  if (thisUser[1] === 0)
    return megaAlert('needLogin');
  
  Loading();
  let data = 'ide=' + encodeURIComponent(id) + '&type=' + encodeURIComponent(channel);
  helperRequest(`${sData[1]}dislike${sData[7]}`, data)
    .then(function(data) {
      if (!isComm)
        getElement('likesCount' + id).innerText = data;
      else 
        getElement('likesCountComm' + id).innerText = data;
      Loading(1);
    })
    .catch(function(error) {returnError(error);throw new Error();});
},
sendComm = function(id, channel) {
  if (thisUser[1] === 0)
    return;

  Loading();
  let dataForNextButton = `${id},'${channel}',1`,
    commText = getElement('text').value,
    data =
    'ide='   + encodeURIComponent(id)
  + '&type=' + encodeURIComponent(channel)
  + '&text=' + encodeURIComponent(commText);
  helperRequest(`${sData[1]}comment${sData[7]}`, data)
    .then(function(data) {
      Loading(1);
      if (data == '-4') {
        megaAlert('commSizeFail')
        return;
      }
      let serverResp = JSON.parse(data);

      innerComments(renderComms(serverResp,channel,dataForNextButton), 0);
    })
    .catch(function(error) {returnError(error);throw new Error();});
},
deleteComm = function(id, channel) {
  Loading();
  helperRequest(`${sData[4]}comment${sData[7]}?ide=${id}&type=${channel}`)
    .then(function(data) {
      if (data == '-1')
        return returnError('Access denied');
      getElement('comm'+data).remove();
      Loading(1);
    })
    .catch(function(error) {returnError(error);throw new Error();});
},
// #endregion
// #region публичные страницы
pHeader = function() {
  let regBtn = '',
    loginBtn = '',
    regBtnMobile = '',
    loginBtnMobile = '';

  if (thisUser[1] === 0) {
    regBtn = `<button id=regBtn class="emptybtn" onclick="registerPage()"${getTrans('register')}/button>`;
    loginBtn =
    `<button id=btnLogin style="margin-left:12px" class="emptybtn" onclick="switchLogin(32,'')">`+
      `<span styllle=position:absolute;right:0;top:-8px${getTrans('login')}/span>`;
    regBtnMobile = `<button id=regBtn2 class="loginbtn" onclick="registerPage()"${getTrans('register')}/button>`;
    loginBtnMobile =
    `<button id=btnLogin2 class="loginbtn" onclick="loginPage()">`+
      `<span${getTrans('login')}/span>`;
  } else {
    regBtn = ``;
    loginBtn =
    `<button id=btnLogin styllle="position:relative;width:20px;height:16px;margin-left:20px" class="emptybtn" onclick="switchLogin(32,'')">`+
      `<span styllle=position:absolute;right:0;top:-8px>${thisUser[0]}</span>`;
    regBtnMobile = `<button id=regBtn2 class="loginbtn" onclick="gLogout()"${getTrans('logout')}/button>`;
    loginBtnMobile =
    `<button id=btnLogin2 styllle=position:relative class="loginbtn" onclick="innerMain(profilePage());switchMobileMain()">`+
      `<span>${thisUser[0]}</span>`;
  }

  //(thisUser[4] == 1 ? '<span style="position:absolute;top:-4px;right:-4px;border:solid red 5px;border-radius:var(--def-border-small)"></span>' : '')

  if (thisUser[4] === 1) {
    loginBtn += `<span style="position:absolute;top:-14px;right:-6px;border:solid red 5px;border-radius:var(--def-border-small)"></span>`;
    loginBtnMobile += `<span style="position:absolute;top:-4px;right:-4px;border:solid red 5px;border-radius:var(--def-border-small)"></span>`;
  }
  loginBtn += `</button>`;
  loginBtnMobile += `</button>`;

  let html = 
  `<div class="header" id=helperMaster align="left">`+
    `<nodiv id=switchHtmlLang style=position:relative>`+
      `<button onclick="switchLang()" style="width:40px" class="emptybtn">`+
        `<img data-trans="src" src="${helperUrl}imgs/${mainLang}.png" width=40px style="padding-bottom:6px;margin-top:6px">`+
      `</button>`+ // !ПОИСК! switchLang = function
    `</nodiv>`+
    `<div class=contentAdaptiveBig>`+
      headerButtons()+
      `<div style=position:absolute;right:8px;top:16px>`+
        `<nodiv id=switchHtmlLogin style=position:relative>`+
          regBtn+
          loginBtn+ // !ПОИСК! switchLogin = function
        `</nodiv>`+
      `</div>`+
    `</div>`+
    `<div class=contentAdaptiveSmall style=display:flex;flex-direction:row-reverse>`+
      `<button class="contentAdaptiveSmall loginbtn" onclick="switchMobileMain()">`+
        `<div style="transform:rotate(90deg)">|||</div>`+
      `</button>`+
    `</div>`+
  `</div>`+
  `<div class=frameprofile id=helperSecond style=display:none>`+
    headerButtons(1)+
    `<div style=height:33px></div>`+
    loginBtnMobile+
    regBtnMobile+
    `<p align=right${getTrans('helperVer')}/p>`+
  `</div>`;
  return html;
},
pageMain = function(ignore = false) {
  if (!ignore)
    setLink('');
  let defDiv = `<div class=gdps-list-place style=background:black;overflow-y:auto;max-height:750px>`,
  html = pHeader()+
  `<div id=helperContent>`+
    `<div class=frameprofile id=gdpses>`+
      `<h1${getTrans('searchCamps')}/h1>`+
        defDiv+
        GDPSrenderMini(CacheCamps)+
      `</div>`+
    `</div><br>`+
    `<div class=frameprofile id=shows>`+
      `<h1${getTrans('searchShows')}/h1>`+
        defDiv+
        SHOWrenderMini(CacheShows)+
      `</div>`+
    `</div><br>`+
    `<div class=frameprofile id=shows>`+
      `<h1${getTrans('searchShows')}/h1>`+
        defDiv+
        PERErenderMini(CachePeres)+
      `</div>`+
    `</div><br>`+
    `<div class=frameprofile id=wikis>`+
      `<h1${getTrans('guides09')}/h1>`+
        defDiv+
        renderWiki(CacheWikis)+
      `</div>`+
    `</div><br>`+
    //`<div class=frameprofile id=wikis>`+
    //  `<h1${getTrans('vacancies')}/h1>`+
    //    defDiv+
    //    renderVacan(CacheVacans)+
    //  `</div>`+
    //`</div><br>`+
  `</div>`;
  return html;
},
setImgSize = function() {
  return;
  if (window.innerWidth >= 700) {
    if (getElement('imageBG')) {
      getElement('imageBG').style = '';
      let alphaY = getElement('imageBG').getBoundingClientRect().height + 72;
      getElement('gdpsalpha').style = `z-index:-5;position:absolute;top:${alphaY}px`;
      return;
    }
  } else if(getElement('gdpsalpha'))
      getElement('gdpsalpha').style = `z-index:-5`;
  if (getElement('gdpsalpha')) {
    let darkElement = getElement('gdpsalpha').getBoundingClientRect(),
    imgposY = darkElement.y,
    imgposX = imgposY * 2.4;

    getElement('imageBG').style.width = imgposX+'px';
    getElement('imageBG').style.height = imgposY+'px';
  }
},
BETA_fixImg = function(url) {
  if (!renderBeta)
    return url;

  if (url.includes('./imgs/'))
    return `.${url}`;
  else
    return url;
},
contentPreload = function(contentId, sendCommData = '', backFunc = '', renderNews = 1, renderBan = 1) {
  let html =
  basicButton('BAN', '', `GDPS.ban(${contentId})`)+
  basicButton('UNBAN', '', `GDPS.unban(${contentId})`)+
  basicButton('DELETE', '', `GDPS.delete(${contentId})`)+
  basicButton('ISLGBT', '', `GDPS.setlgbt(${contentId})`)+
  basicButton('ISNTLGBT', '', `GDPS.dellgbt(${contentId})`)+
  (renderBan ? 
    `<img style=width:400px;height:167px id="imageBG${contentId}">`
  : '')+
  `<p>img: <a id=imgurl${contentId}></a></p>`+
  `<p>ban: <a id=banurl${contentId}></a></p>`+
  `<p>all joins: <a id=allurl${contentId}></a></p>`+
  `<div id="insertable${contentId}" class="gdps-forum"></div>`+
  `<div class=gdps-list-place id=GDPSesPlace></div>`+
  `<div class="gdps-forum">`+
    (backFunc ?
    `<button class="loginbtn" onclick="${backFunc}"${getTrans('back')}/button>`
    : '')+
  `</div><br>`+
  `<div class=gdpsnewsalpha2 id=gdpsalpha style=z-index:-5;width:400px></div>`+
  `<div style="display:flex; flex-wrap:wrap">`+
    (renderNews ? 
    `<div style=overflow:auto;flex-basis:100%;max-height:300px align=center class=adaptiveNews id="news${contentId}"></div>`+
    `<div class=gdpsnewsalpha></div>`
    : '')+
    `<div style=overflow:auto;flex:50%>`+
      contentSendCommForm(sendCommData)+
      `<div id="comments${contentId}"></div>`+
    `</div>`+
  `</div>`;
  return html;
  innerMain(html);
},
insertBtn = function(lastUse, transText = 'showMore', useRemover = 1) {// кнопка "показать больше"
  return `<div ${useRemover === 1 ? 'id=nextGdps ' : ''}class=gdps-helper align=center>`+
    `<button onclick="${lastUse}" class=loginbtn `+
    `style="font-size:calc(var(--def-font)*2);padding:4px 8px;margin:12px 0"${getTrans(transText)}/button>`+
  `</div>`;
},

otherProfile = function(userId, backButton, innerHtnl = otherProfileMini) {
  let html = pHeader()+
  `<div id=helperContent>`+
    `<div class=frameprofile style="margin:0;height:100%">`+
      `<button style="position:absolute;top:80px;right:5px" class="contentAdaptiveSmall loginbtn" onclick="profileSwitcherPhone()">`+
        `<div style="transform:rotate(90deg)">|||</div>`+
      `</button>`+
      `<div id="phoneSelector" class=contentAdaptiveBig style="position:absolute;top:95px;width:235px" align="left">`+
        `<button class=loginbtn onclick="otherProfileMini(${userId})"${getTrans('profile')}/button><br><br>`+
        `<button class=loginbtn onclick="otherCampsWindow(${userId})"${getTrans('searchCamps')}/button><br><br>`+
        `<button class=loginbtn onclick="otherShowsWindow(${userId})"${getTrans('searchShows')}/button><br><br>`+
        `<button class=loginbtn onclick="otherWikisWindow(${userId})"${getTrans('guides09')}/button><br><br>`+
        `<br><button class=loginbtn onclick="${backButton}"${getTrans('back')}/button>`+
      `</div>`+
      `<div id="phoneSelectorSmall" class=contentAdaptiveSmall style=display:none>`+
        `<button class=loginbtn onclick="otherProfileMini(${userId});profileSwitcherPhone()"${getTrans('profile')}/button><br><br>`+
        `<button class=loginbtn onclick="otherCampsWindow(${userId});profileSwitcherPhone()"${getTrans('searchCamps')}/button><br><br>`+
        `<button class=loginbtn onclick="otherShowsWindow(${userId});profileSwitcherPhone()"${getTrans('searchShows')}/button><br><br>`+
        `<button class=loginbtn onclick="otherWikisWindow(${userId});profileSwitcherPhone()"${getTrans('guides09')}/button><br><br>`+
        `<br><button class=loginbtn onclick="${backButton}"${getTrans('back')}/button>`+
      `</div>`+
      `<div class=profileMobileRightWindow id="profileWindow" align="left">`+
      `</div>`+
    `</div>`+
  `</div>`;
  innerMain(html);
  innerHtnl(userId);
},
// #endregion
// #region кнопки профиля (лист входов, удалить аларм и т д)
GDPS = {
  ban: function(gdpsId) {
    Loading();
    helperRequest(`${sData[2]}Aaction${sData[7]}?id=${gdpsId}&type=0&action=ban`)
      .then(function(data) {
        megaAlert(data);
        getElement(gdpsId).style.background = 'rebeccapurple';
        Loading(1);
      })
      .catch(function(error) {returnError(error);throw new Error();});
  },
  unban: function(gdpsId) {
    Loading();
    helperRequest(`${sData[2]}Aaction${sData[7]}?id=${gdpsId}&type=0&action=activate`)
      .then(function(data) {
        megaAlert(data);
        getElement(gdpsId).style.background = '';
        Loading(1);
      })
      .catch(function(error) {returnError(error);throw new Error();});
  },
  delete: function(gdpsId) {
    Loading();
    helperRequest(`${sData[2]}Aaction${sData[7]}?id=${gdpsId}&type=0&action=delete`)
      .then(function(data) {
        megaAlert(data);
        getElement(gdpsId).remove();
        closeWindow(querySelect(`[g${gdpsId}]`).id);
        Loading(1);
      })
      .catch(function(error) {returnError(error);throw new Error();});
  },
  setlgbt: function(gdpsId) {
    Loading();
    helperRequest(`${sData[2]}Aaction${sData[7]}?id=${gdpsId}&type=0&action=setlgbt`)
      .then(function(data) {
        megaAlert(data);
        Loading(1);
      })
      .catch(function(error) {returnError(error);throw new Error();});
  },
  dellgbt: function(gdpsId) {
    Loading();
    helperRequest(`${sData[2]}Aaction${sData[7]}?id=${gdpsId}&type=0&action=dellgbt`)
      .then(function(data) {
        megaAlert(data);
        Loading(1);
      })
      .catch(function(error) {returnError(error);throw new Error();});
  },
},
WIKI = {
  ban: function(gdpsId) {
    Loading();
    helperRequest(`${sData[2]}Aaction${sData[7]}?id=${gdpsId}&type=2&action=ban`)
      .then(function(data) {
        megaAlert(data);
        getElement('w'+gdpsId).style.background = 'rebeccapurple';
        Loading(1);
      })
      .catch(function(error) {returnError(error);throw new Error();});
  },
  unban: function(gdpsId) {
    Loading();
    helperRequest(`${sData[2]}Aaction${sData[7]}?id=${gdpsId}&type=2&action=activate`)
      .then(function(data) {
        megaAlert(data);
        getElement('w'+gdpsId).style.background = '';
        Loading(1);
      })
      .catch(function(error) {returnError(error);throw new Error();});
  },
  delete: function(gdpsId) {
    Loading();
    helperRequest(`${sData[2]}Aaction${sData[7]}?id=${gdpsId}&type=2&action=delete`)
      .then(function(data) {
        megaAlert(data);
        getElement('w'+gdpsId).remove();
        closeWindow(querySelect(`[wiki${gdpsId}]`).id);
        Loading(1);
      })
      .catch(function(error) {returnError(error);throw new Error();});
  },
  setlgbt: function(gdpsId) {
    Loading();
    helperRequest(`${sData[2]}Aaction${sData[7]}?id=${gdpsId}&type=2&action=setlgbt`)
      .then(function(data) {
        megaAlert(data);
        Loading(1);
      })
      .catch(function(error) {returnError(error);throw new Error();});
  },
  dellgbt: function(gdpsId) {
    Loading();
    helperRequest(`${sData[2]}Aaction${sData[7]}?id=${gdpsId}&type=2&action=dellgbt`)
      .then(function(data) {
        megaAlert(data);
        Loading(1);
      })
      .catch(function(error) {returnError(error);throw new Error();});
  },
},
GUIDES = {
  ban: function(gdpsId) {
    Loading();
    helperRequest(`${sData[2]}Aaction${sData[7]}?id=${gdpsId}&type=1&action=ban`)
      .then(function(data) {
        megaAlert(data);
        getElement('g'+gdpsId).style.background = 'rebeccapurple';
        Loading(1);
      })
      .catch(function(error) {returnError(error);throw new Error();});
  },
  unban: function(gdpsId) {
    Loading();
    helperRequest(`${sData[2]}Aaction${sData[7]}?id=${gdpsId}&type=1&action=activate`)
      .then(function(data) {
        megaAlert(data);
        getElement('g'+gdpsId).style.background = '';
        Loading(1);
      })
      .catch(function(error) {returnError(error);throw new Error();});
  },
  delete: function(gdpsId) {
    Loading();
    helperRequest(`${sData[2]}Aaction${sData[7]}?id=${gdpsId}&type=1&action=delete`)
      .then(function(data) {
        megaAlert(data);
        getElement('g'+gdpsId).remove();
        Loading(1);
      })
      .catch(function(error) {returnError(error);throw new Error();});
  },
},

coownersMenu = function(id, contentType) {
  Loading();
  helperRequest(`${sData[0]}getOwners${sData[7]}?id=${id}&type=${contentType}`)
    .then(function(data) {
      switch (contentType) {
        case 1: setLink('campOwn='+id); break;
        case 2: setLink('showOwn='+id); break;
        case 3: setLink('wikiOwn='+id); break;
        
      }
      let parsedData = JSON.parse(data),
        html = 
      `<div>`+
        `<h1><span${getTrans('coowners')}/span> ${parsedData[0]}</h1>`+
        `<table id=comments>`+
          `<tr>`+
            `<td${getTrans('profName')}/td>`+
            `<td${getTrans('delete')}/td>`+
          `</tr>`;

      parsedData[1].forEach(function(arrat) {
        html +=
          `<tr id=perm${arrat[1]}>`+
            `<td>`+
              arrat[0]+
            `</td>`+
            `<td>`+
              `<button class=loginbtn onclick="deleteOwner(${id},${contentType},${arrat[1]})"${getTrans('delete')}/button>`+
            `</td>`+
          `</tr>`;
      });

      html += 
        `</table><br><br>`+
        `<input style="width:120px" id="addown" class="framelabel"${getTrans('idOrName', 'input')}`+
        `<button class="loginbtn" onclick="ownersAdd(${id},${contentType})">+</button>`+
      `</div>`;
      innerProfile(html);
      Loading(1);
    })
    .catch(function(error) {returnError(error);throw new Error();});
},
getJoinLog = function(gdpsId) { // legacy
  Loading();
  helperRequest(`${sData[0]}getJoinLog${sData[7]}?id=${gdpsId}`)
    .then(function(data) {
      setLink('gdpsLog='+gdpsId);
      let parsedData = JSON.parse(data),
        html = 
      `<div>`+
        `<h1><span${getTrans('joinsTo')}/span> ${parsedData[0][0]}</h1>`+
        `<table>`;
      parsedData.forEach(function(arrat) {
        if (arrat[1] !== 'Microwave') {
          html +=
          `<tr>`+
            `<td>`+
              arrat[0]+
            `</td>`+
            `<td>`+
              timeAgo(arrat[1])+
            `</td>`+
            `<td>`+
              arrat[2]+
            `</td>`+
          `</tr>`;
        }
      });
      html += 
        `</table>`+
      `</div>`;
      innerProfile(html);
      Loading(1);
    })
    .catch(function(error) {returnError(error);throw new Error();});
},
mediaselector = function(id, inputtype) {
  let fileTrans = '';
  if (id === 'img')
    fileTrans = 'Picture01';
  else
    fileTrans = 'Picture02';

  if (inputtype === 'toimg') {
    getElement('mediaselector'+id).innerHTML = 
    `<input type=text class=framelabel name=${id} style=width:40%${getTrans('campInput04', 'input')}`+
    `<button class=loginbtn onclick="mediaselector('${id}','tofile')"${getTrans('campInput07')}/button>`;
  } else {
    getElement('mediaselector'+id).innerHTML = 
    `<input type=file class=framelabel name=${id} style=width:40%>`+
    `<button class=loginbtn onclick="mediaselector('${id}','toimg')"${getTrans('campInput08')}/button>`+
    `<br><span style=opacity:50%${getTrans(fileTrans)}/span>`;
  }
},
// #endregion
// #region всплывающие окна
openWindow = function(windowId, htmlContent, customAttrs = '', size = 'width:400px;height:500px') {
  let html =
  `<div style="z-index:${parseInt(windowsCount+5)};padding:4vh 0" class="upperWindow frameprofile ANIM-create2" id=${windowsCount}${windowId} ${customAttrs}>`+
    `<div align=right class=underWindow id=DRAGGER${windowsCount}${windowId}>`+
      `<div align=left style=position:absolute;left:2px;width:90% id="title${windowsCount}">${windowsCount}${windowId}</div>`+
      windowButton('–', `hideWindow('${windowsCount}${windowId}')`, `font-weight:bold`)+
      windowButton('X', `closeWindow('${windowsCount}${windowId}')`, `font-weight:bold`)+
    `</div>`+
    `<div style=${size};overflow-y:auto>`+
      htmlContent+
    `</div>`+
  `</div>`;
  getElement('windowsXP').insertAdjacentHTML('beforeend', html);
  initDragger(windowId);
  return windowsCount++;
},
initDragger = function(windowId) {
  let Window = getElement(windowsCount+windowId),
    pos1 = 0, pos2 = 0, pos3 = 0, pos4 = 0,
  
  startDrag = function(e) {
    let target = e.target;
    if (target.tagName === 'BUTTON' || target.closest('button')) {
      if ('ontouchstart' in window) {
        stopDrag(e);
        return;
      }
      return;
    }

    let index = Windowses.findIndex(w => w.id === Window.id);
    if (index > -1) {
      Windowses.splice(index, 1);
      Windowses.push(Window);
    }
    for (let i = 0; i < Windowses.length; i++) {
      Windowses[i].style.zIndex = parseInt(i+5);
    }

    e.preventDefault();
    let clientX = e.clientX || e.touches[0].clientX,
        clientY = e.clientY || e.touches[0].clientY;
    
    pos3 = clientX;
    pos4 = clientY;
    
    document.onmouseup = stopDrag;
    document.ontouchend = stopDrag;

    document.onmousemove = draggerMove;
    document.ontouchmove = draggerMove;
  },
  
  draggerMove = function(e) {
    e.preventDefault();
    
    // Получаем координаты для мыши или касания
    let clientX = e.clientX || e.touches[0].clientX,
        clientY = e.clientY || e.touches[0].clientY;
    
    pos1 = pos3 - clientX;
    pos2 = pos4 - clientY;
    pos3 = clientX;
    pos4 = clientY;

    Window.style.top = (Window.offsetTop - pos2) + "px";
    Window.style.left = (Window.offsetLeft - pos1) + "px";
  },
  
  stopDrag = function() {
    document.onmouseup = null;
    document.ontouchend = null;

    document.onmousemove = null;
    document.ontouchmove = null;
  }

  let draggerElement = getElement(`DRAGGER${Window.id}`) || Window;
  
  draggerElement.onmousedown = startDrag;
  draggerElement.ontouchstart = startDrag;

  Windowses[windowsCount] = Window;
},
closeWindow = function(windowId) {
  if (getElement(windowId)) {
    if (getElement(windowId).classList.contains('ANIM-create2'))
      getElement(windowId).classList.replace('ANIM-create2', 'ANIM-stop2');
    if (getElement(windowId).classList.contains('ANIM-recreate2'))
      getElement(windowId).classList.replace('ANIM-recreate2', 'ANIM-stop2');
  }
  
  TimeOut[1] = setTimeout(function() {
    getElement(windowId).remove();
  },300)
},
hideWindow = function(windowId) {
  if (getElement(windowId)) {
    if (getElement(windowId).classList.contains('ANIM-create2'))
      getElement(windowId).classList.replace('ANIM-create2', 'ANIM-hide2');
    if (getElement(windowId).classList.contains('ANIM-recreate2'))
      getElement(windowId).classList.replace('ANIM-recreate2', 'ANIM-hide2');
  }
  
  TimeOut[1] = setTimeout(function() {
    getElement(windowId).style.display = 'none';
    getElement('Professional').insertAdjacentHTML('beforeend', basicButton(windowId, `hider${windowId}`, `unhideWindow('${windowId}')`, ``))
  },300)
},
unhideWindow = function(windowId) {
  if (getElement('hider'+windowId))
    getElement('hider'+windowId).remove();
  if (getElement(windowId)) {
    getElement(windowId).classList.replace('ANIM-hide2', 'ANIM-recreate2');
    getElement(windowId).style.display = '';
  }
  
  TimeOut[1] = setTimeout(function() {
  },300)
},
// #endregion
// #region рендер контента (шоу, кемпы)
toStringGDPS = function(tag) {
  let findArr = Object.assign({}, GDPStags, GDPSoss);
  return findArr[tag];
},
toStringSHOW = function(tag) {
  let findArr = Object.assign({}, SHOWtags, SHOWoss);
  return findArr[tag];
},
TagsVacs = {
  1:'Vacstag1',
  2:'Vacstag2',
  3:'Vacstag3',
  4:'Vacstag4',
  5:'Vacstag5',
  6:'Vacstag6',
  7:'Vacstag7',
  8:'Vacstag8',
  9:'Vacstag9',
},
toStringTagsVacs = (tag)=>{
  return TagsVacs[tag];
},
imageButton = (img = '', func = '', style = '', id = '', Class = '')=>{
  return `<button ${id ? 'id="'+id+'"' : ''}class="loginbtnMini ${Class}" style="${style}" onclick="${func}"><img style=margin:0 width="24px" src="${img}"></button>`;
},
GDPSswitchChannel = (channel)=>{
  switch (channel) {
    case 0:
      return ['camp', 'Camp', 'c', []];
    case 1:
      return ['show', 'Show', 's', []];
    case 2:
      return ['pere', 'Pere', 'p', []];
  }
},
removeVacPre = (id, gdpsId)=>{
  if (querySelect(`[vacremove${id}]`))
    return;
  openWindow('vacRemove'+id,
    `<p${getTrans('removeSure')}/p>`+
    basicButton(getTrans('delete'), `removeVac(${id},${gdpsId})`)+
    basicButton(getTrans('otmena'), `closeWindow(querySelect(\`[vacremove${id}]\`)?.id)`)
  , 'vacremove'+id);
},
removeVac = (id, gdpsId)=>{
    Loading();
    helperRequest(`${sData[8]}removeVac${sData[php]}?id=${id}&gdpsId=${gdpsId}`)
      .then(data=>{
        if (data == 1) {
          getElement('v'+id).remove();
          closeWindow(querySelect(`[vacremove${id}]`)?.id);
        }
        Loading(1);
      })
      .catch(e=>{console.error(e);getPromiseErrorPos(e)});;
},

GDPSrenderMini = function(parsedData, joinData = '') {
  let html = '',
    html2 = '',
    Count = 0,
    preHtml = [],
    gdpsData = null,
    Tags = null,
    renderJoinLink = null,
    tagsOs = '';
  
  for (let Id in parsedData) {
    Count++;
    tagsOs = '';

    gdpsData = parsedData[Id];
    
    if (JSON.parse(gdpsData.tags) != null && JSON.parse(gdpsData.os) != null) {
      Tags = JSON.parse(gdpsData.tags).concat(JSON.parse(gdpsData.os));
      Tags.forEach(function(tag){
        tagsOs += `<div class="tag"${getTrans(toStringGDPS(tag))}/div>`;
      });
    }

    renderJoinLink = renderJoinLink ? '' : `<a class="loginbtnGDPS" href="../join${sData[7]}?id=${gdpsData.ID}${joinData}" target=_blank${getTrans('joinToGdps')}/a>`;

    preHtml = [joinData, renderJoinLink, tagsOs, 'width:300px;height:450px', 0, 0];
    if (gdpsData.checked === 0) {
      preHtml[3] = 'width:300px;height:450px;background:brown';
      html2 += contentRenderMinu(gdpsData, preHtml);
    } else if (gdpsData.checked === -1) {
      preHtml[3] = 'width:300px;height:450px;background:rebeccapurple';
      html += contentRenderMinu(gdpsData, preHtml);
    } else
      html += contentRenderMinu(gdpsData, preHtml);
  }
  html = html2 + html;
  return html;
},
SHOWrenderMini = function(parsedData, joinData = '') {
  let html = '',
    html2 = '',
    Count = 0,
    preHtml = [],
    gdpsData = null,
    Tags = null,
    renderJoinLink = null,
    tagsOs = '';
  
  for (let Id in parsedData) {
    Count++;
    tagsOs = '';

    gdpsData = parsedData[Id];
    
    if (JSON.parse(gdpsData.tags) != null && JSON.parse(gdpsData.os) != null) {
      Tags = JSON.parse(gdpsData.tags).concat(JSON.parse(gdpsData.os));
      Tags.forEach(function(tag){
        tagsOs += `<div class="tag"${getTrans(toStringSHOW(tag))}/div>`;
      });
    }

    renderJoinLink = renderJoinLink ? '' : `<a class="loginbtnGDPS" href="../join${sData[7]}?id=${gdpsData.ID}${joinData}" target=_blank${getTrans('joinToGdps')}/a>`;

    preHtml = [joinData, renderJoinLink, tagsOs, 'width:300px;height:450px', 1, 0];
    if (gdpsData.checked === 0) {
      preHtml[3] = 'width:300px;height:450px;background:brown';
      html2 += contentRenderMinu(gdpsData, preHtml);
    } else if (gdpsData.checked === -1) {
      preHtml[3] = 'width:300px;height:450px;background:rebeccapurple';
      html += contentRenderMinu(gdpsData, preHtml);
    } else
      html += contentRenderMinu(gdpsData, preHtml);
  }
  html = html2 + html;
  return html;
},
PERErenderMini = function(parsedData, joinData = '') {
  let html = '',
    html2 = '',
    Count = 0,
    preHtml = [],
    gdpsData = null,
    Tags = null,
    renderJoinLink = null,
    tagsOs = '';
  
  for (let Id in parsedData) {
    Count++;
    tagsOs = '';

    gdpsData = parsedData[Id];
    
    if (JSON.parse(gdpsData.tags) != null && JSON.parse(gdpsData.os) != null) {
      Tags = JSON.parse(gdpsData.tags).concat(JSON.parse(gdpsData.os));
      Tags.forEach(function(tag){
        tagsOs += `<div class="tag"${getTrans(toStringSHOW(tag))}/div>`;
      });
    }

    renderJoinLink = renderJoinLink ? '' : `<a class="loginbtnGDPS" href="../join${sData[7]}?id=${gdpsData.ID}${joinData}" target=_blank${getTrans('joinToGdps')}/a>`;

    preHtml = [joinData, renderJoinLink, tagsOs, 'width:300px;height:450px', 2, 0];
    if (gdpsData.checked === 0) {
      preHtml[3] = 'width:300px;height:450px;background:brown';
      html2 += contentRenderMinu(gdpsData, preHtml);
    } else if (gdpsData.checked === -1) {
      preHtml[3] = 'width:300px;height:450px;background:rebeccapurple';
      html += contentRenderMinu(gdpsData, preHtml);
    } else
      html += contentRenderMinu(gdpsData, preHtml);
  }
  html = html2 + html;
  return html;
},
renderWiki = function(parsedData, page = 0) {
  page++
  let html = '',
    html2 = '',
    Count =  0,
    preHtml = [],
    gdpsData = null;

  
  for (let Id in parsedData) {
    Count++;

    gdpsData = parsedData[Id];
    console.log(gdpsData.checked)

    preHtml = ['', '', '', 'width:300px;height:290px', -1, 8];
    if (gdpsData.checked == 0) {
      preHtml[3] = 'width:300px;height:290px;background:brown';
      html2 += contentRenderMinu(gdpsData, preHtml, 0, 1, 0, 0); 
    } else if (gdpsData.checked == -1) {
      preHtml[3] = 'width:300px;height:290px;background:rebeccapurple';
      html += contentRenderMinu(gdpsData, preHtml);
    } else
      html += contentRenderMinu(gdpsData, preHtml);
  }
  html = html2 + html;
  return html;
},
renderVacan = (parsedData, isAdmin = true)=>{
  let html = '',
    Count = 0,
    TagsLocal = [],
    tagsOs = '',
    adminButtons = '',
    applyBtn = '';

  for (let longId in parsedData) {
    let id = longId.slice(1);
    Count++;
    tagsOs = '';
    if (Count == 9)
      return html;

    gdpsData = parsedData[longId];
    
    if (JSON.parse(gdpsData.tags) != null) {
      TagsLocal = JSON.parse(gdpsData.tags);
      TagsLocal.forEach(function(tag){
        tagsOs += `<div class="tag"${getTrans(toStringTagsVacs(tag))}/div>`;
      });
    }
    if (isAdmin) {
      adminButtons = 
        imageButton(`${helperUrl}imgs/trash.svg`, `removeVacPre(${id},${gdpsData.gId})`, 'position:absolute;top:8px;right:8px')+
        imageButton(`${helperUrl}imgs/edit.svg`, `editVacs(${gdpsData.gChannel},${gdpsData.gId},${id})`, 'position:absolute;top:8px;right:52px');
        
      applyBtn = `<button class=loginbtn onclick="vacResponses(${gdpsData.gChannel},${gdpsData.gId},${id})"${getTrans('vacResponses')}/button>`;
    } else if (!gdpsData.isLiked) 
      applyBtn = `<button id=a${id} class=loginbtn onclick="vacRespond(${longId.slice(1)})"${getTrans('vacRespond')}/button>`;
    else 
      applyBtn = `<button class=loginbtn id=a'+id${getTrans('vacResponded')}/button>`;

    html +=
    `<div class=framegdpsOld id=${longId} style=width:300px;height:450px>`+
      `<h2>${gdpsData.title} -`+basicButton(`>${gdpsData.gTitle}<`, `get${GDPSswitchChannel(gdpsData.gChannel)[1]}(${gdpsData.gId})`)+'</h2>'+
      (isAdmin ? adminButtons : '')+
      `<p>${gdpsData.text}</p>`+
      `<div class="flex-row FGDPStags absolute" style=bottom:4px>`+
        tagsOs+
        `<div style=width:100%>`+
          applyBtn+
        `</div>`+
      `</div>`+
    `</div>`;
  }
  return html;
},
renderGuideMini = function(parsedData, page = 0) {
  page++
  let html = '',
    html2 = '',
    Count =  0,
    preHtml = [],

    gdpsData = null;
  
  for (let Id in parsedData) {
    let guid = parsedData[Id];
    if (typeof guid !== 'object') {
      if (typeof guid === 'number' && guid !== 0)
        innerGdpsPlace(insertBtn('openForum('+guid+')', 'forumHas', 0),512);
      if (typeof guid === 'string') 
        getElement('wikiName').innerHTML = guid;
      continue;
    }
    Count++;

    gdpsData = {
      ID: guid[0],
      title: guid[1],
      language: guid[2],
      likes: guid[4],
      ban: guid[5],
      checked: guid[6],
    };

    preHtml = [globalWiki, `<button class=loginbtnGDPS onclick="guideACTS('${gdpsData.title}',${gdpsData.ID})">ACTIONS</button>`, '', 'width:250px;height:200px', -2, 7];
    console.log(gdpsData.checked)
    if (gdpsData.checked === 0) {
      preHtml[3] = 'width:250px;height:200px;background:brown';
      html2 += contentRenderMinu(gdpsData, preHtml, 0, 0, 0, 0); 
    } else if (gdpsData.checked === -1) {
      preHtml[3] = 'width:250px;height:200px;background:rebeccapurple';
      html += contentRenderMinu(gdpsData, preHtml, 0, 0, 0, 0);
    } else
      html += contentRenderMinu(gdpsData, preHtml, 0, 0, 0, 0);
  }
  html = html2 + html;
  return html;
},
forumRenderMini = function(parsedData, page = 0) {
  page++
  let html = '',
    Count =  0,
    preHtml = [],
    gdpsData = null;
  
  for (let Id in parsedData) {
    let guid = parsedData[Id];
    if (typeof guid !== 'object' && typeof guid === 'string') {
      getElement('insertable').innerHTML = '<h1>'+guid+'</h1>';
      continue;
    }
    Count++;
    if (Count == 5) {
      innerGdpsPlace(insertBtn(`getForumPosts(${forumId},${page})`),-1);
      return html;
    }

    gdpsData = {
      ID: guid[0],
      username: guid[2],
      author: guid[3],
      title: guid[4],
      text: guid[5],
      likes: guid[7]
    };

    preHtml = [guid[1], '', '', 'width:300px;height:290px', -3, 9];
    html += contentRenderMinu(gdpsData, preHtml, 1, 1, 0, 0);
  }
  return html;
},

GDPSrender = function(parsedData, joinData = '') {
  let html = '',
    gdpsData = parsedData.gdps,
    Tags = JSON.parse(gdpsData.tags),
    os = JSON.parse(gdpsData.os),
    tagsOs = '';

  tagsOs += '<div class="flex-row">';
  if (Tags != null)
    Tags.forEach(function(tag) {
      tagsOs += `<div class="tag"${getTrans(toStringGDPS(tag))}/div>`;
    });
  if (os != null) {
    tagsOs += '</div>'+'<div class="flex-row">';
    os.forEach(function(tag) {
      tagsOs += `<div class="tag"${getTrans(toStringGDPS(tag))}/div>`;
    });
  }
  tagsOs += '</div>';

  html += contentRender(gdpsData, 0, 0, 0, tagsOs, 1, gdpsData.wiki, gdpsData.ID, joinData);
  return html;
},
SHOWrender = function(parsedData, joinData = '') {
  let html = '',
    gdpsData = parsedData.gdps,
    Tags = JSON.parse(gdpsData.tags),
    os = JSON.parse(gdpsData.os),
    tagsOs = '';

  tagsOs += '<div class="flex-row">';
  if (Tags != null)
    Tags.forEach(function(tag) {
      tagsOs += `<div class="tag"${getTrans(toStringGDPS(tag))}/div>`;
    });
  if (os != null) {
    tagsOs += '</div>'+'<div class="flex-row">';
    os.forEach(function(tag) {
      tagsOs += `<div class="tag"${getTrans(toStringGDPS(tag))}/div>`;
    });
  }
  tagsOs += '</div>';

  html += contentRender(gdpsData, 0, 0, 0, tagsOs, 1, gdpsData.wiki, gdpsData.ID, joinData);
  return html;
},
PERErender = function(parsedData, joinData = '') {
  let html = '',
    gdpsData = parsedData.gdps,
    Tags = JSON.parse(gdpsData.tags),
    os = JSON.parse(gdpsData.os),
    tagsOs = '';

  tagsOs += '<div class="flex-row">';
  if (Tags != null)
    Tags.forEach(function(tag) {
      tagsOs += `<div class="tag"${getTrans(toStringGDPS(tag))}/div>`;
    });
  if (os != null) {
    tagsOs += '</div>'+'<div class="flex-row">';
    os.forEach(function(tag) {
      tagsOs += `<div class="tag"${getTrans(toStringGDPS(tag))}/div>`;
    });
  }
  tagsOs += '</div>';

  html += contentRender(gdpsData, 0, 0, 0, tagsOs, 1, gdpsData.wiki, gdpsData.ID, joinData);
  return html;
},
forumRender = function(post) {
  let html = '',
    gdpsData = {
      postId: post[0],
      forumId: post[1],
      username: post[2],
      author: post[3],
      title: post[4],
      text: post[5],
      date: post[6],
      likes: post[7]
    };

  html += contentRender(gdpsData, gdpsData.date, 0, 9, '', 0, 0, gdpsData.ID, '');
  return html;
},
RenderNews = function(data, isComm = 0, innerGdpsRendered = 'mega', backFunc = 'getCamp') {
  Console.log(isComm);
  let html = '',
    html2 = '',
    
    gData = null,
    gdpsData = null,
    miniRenderMode = '';

  for (let ide in data)  {
    html = '';
    gData = data[ide];
    gdpsData = {
      ID: gData[0],
      title: gData[1],
      text: gData[2],
      author: gData[3],
      username: gData[4],
      gdpsId: gData[5],
      gdpsTitle: gData[6],
      date: gData[7],
      likes: gData[8]
    };

    gdpsData.canDel = false;
    if (thisUser[1] == gdpsData.author || thisUser[2] > 0) {
      gdpsData.canDel = true;
    }
  
    html += contentRender(gdpsData, gdpsData.date, 1, 2, '', 0, 0, 0, backFunc, isComm);
    html2 = html2 + html;
  };
  if (html2 == '')
    return `<h1 class=contentAdaptiveBig${getTrans('newsNoneReal')}/h1>`;
  return html2;
},

renderComms = function(parsedData, channel = 0, dataForNextButton = '') {

  let commcount = 0,
    html = '',
    htmlFull = '',
    delBtn = '',
    likeChannel = 0,

    gdpsData = null,
    id = null,
    username = null,
    commText = null,
    userId = null,
    userrole = null,
    likes = null,
    date = null,
    nameColor = null;

  switch (channel) {
    case 0:
    case 1:
      likeChannel = 3;
      break;
    case 2:
      likeChannel = 6;
      break;
    case 3:
      likeChannel = 5;
      break;
    case 4:
      likeChannel = 10;
      break;
  }

  for (let ide in parsedData) {
    if (commcount == 10) {
      htmlFull = htmlFull + insertBtn(`helperComments(${dataForNextButton})`);
      return htmlFull;
    };
    commcount++;
  
    gdpsData = parsedData[ide];
    id = gdpsData[0];
    username = gdpsData[1];
    commText = gdpsData[2];
    userId = gdpsData[3];
    userrole = gdpsData[4];
    likes = gdpsData[5];
    date = gdpsData[6];
    switch (userrole) {
      case 0:
        nameColor = 'var(--color-white)';
        break;
      case 1:
        nameColor = 'greenyellow';
        break;
      case 2:
        nameColor = 'yellow';
        break;
      case 3:
        nameColor = '#ffcc22';
        break;
    }

    delBtn = 
    `<button onclick="deleteComm(${id},${channel})" style="position:absolute;top:20px;right:20px;padding:2px 4px" class="loginbtn">`+
      `<img width=24px src="${helperUrl}imgs/trash.svg">`+
    `</button>`;
    
    html = 
    `<div class="framecomm" id=comm${id}>`+
      `<button style="border:none;background:none;margin:0;font-size:calc(var(--def-font)*2);font-weight:bold;color:${nameColor}"`+
      `onclick="otherProfile(${userId},lastUsed3)">${username}</button>`+
      `<p style="margin:0">${timeAgo(date)}</p>`+
      `<p>${commText}</p>`+
      `<div class="likezone">`+
        `<span class=likeplace id="likesCountComm${id}">${likes}</span>`+
        `<button onclick="sendLike(${id},${likeChannel},1)" id="like"></button>`+
        `<button onclick="sendDislike(${id},${likeChannel},1)" id="dislike"></button>`+
      `</div>`+
      (thisUser[1] == userId || thisUser[2] > 0 ? delBtn : '')+
    `</div>`;
  
    htmlFull = htmlFull + html;
  
    html = '';
  };
  if (htmlFull == '')
    return `<h1${getTrans('commsNone')}/h1>`;
  return htmlFull;
},
timeAgo = function(timestamp) {
  let timeDiff = Math.floor((Date.now() / 1000) - timestamp);
  Console.log(timestamp+"\n"+timeDiff);

  if (timeDiff < 60) {
    return timeDiff + getTrans('timeAgo01', 0);
  } else if (timeDiff < 3600) {
    let Minutes = Math.floor(timeDiff / 60),
      Seconds = timeDiff % 60;
    return Minutes + getTrans('timeAgo02', 0) + Seconds + getTrans('timeAgo03', 0);
  } else if (timeDiff < 86400) {
    let Hours = Math.floor(timeDiff / 3600),
      Minutes = Math.floor((timeDiff % 3600) / 60);
    return Hours + getTrans('timeAgo04', 0) + Minutes + getTrans('timeAgo05', 0);
  } else if (timeDiff < 604800) {
    let Days = Math.floor(timeDiff / 86400),
      Hours = Math.floor((timeDiff % 86400) / 3600);
    return Days + getTrans('timeAgo06', 0) + Hours + getTrans('timeAgo07', 0);
  } else if (timeDiff < 2592000) {
    let Weeks = Math.floor(timeDiff / 604800),
      Days = Math.floor((timeDiff % 604800) / 86400);
    return Weeks + getTrans('timeAgo08', 0) + Days + getTrans('timeAgo09', 0);
  } else if (timeDiff < 31536000) {
    let Months = Math.floor(timeDiff / 2592000),
      Weeks = Math.floor((timeDiff % 2592000) / 604800);
    return Months + getTrans('timeAgo10', 0) + Weeks + getTrans('timeAgo11', 0);
  } else {
    return getTrans('timeAgo12', 0);
  };
},
// #endregion
// #region прочий хлам
uploadPost = function(forumId) {
  let html = 
  `<div id=helperContentProfile>`+
    `<h1 id=blacktext${getTrans('newPost')}/h1>`+
    `<form method=post onsubmit="return enterFormData(this,'forumPost${sData[7]}')">`+
      `<input style=width:90% class=framelabel type=title name=title${getTrans('addCamp01', 'input')}<br>`+
      `<textarea style=width:90%;height:64px class=framelabel name=text ${getTrans('newsText', 'textarea')}/textarea><br>`+
      `<input type=hidden name=forumId value=${forumId}>`+
      `<input type=submit class="loginbtn"${getTrans('publishNews', 'inputValue')}`+
    `</form>`+
  `</div>`;
  return html;
},

makeBetaAlert = function() {
  helperMain.insertAdjacentHTML('afterend',
    `<div class=ALERT id=BETAalert style=position:absolute;top:20%;left:50%><h1>BETA!</h1>`+
      `<p>Спешим вам сообщить что это бета версия сайта, а это значит что есть вероятность что мы внезапно удалим все аккаунты или сделаем что то похожее чтобы приблизить вас к выходу релиза сайта.</p>`+
      `<p>Пожалуйста, сообщайте о любых найденных багах и недочётах на наш дискорд сервер!</p><br>`+
      `<button style=background-color:#333 onclick="Slocal.set('BetaRead',1);getElement('BETAalert').remove()">Понятно</button>`+
    `</div>`
  );
},

playDoom = function() {
  getElement('doom').style.padding = '4vh';
  getElement('doom').innerHTML = ''; // вау вы нашли пасхалку

  returnError('deprecated secret found')
},

seePassword = function() {
  if (getElement('LGpassword').type == 'password') {
    getElement('LGpassword').type = 'text';
    getElement('LGbtn').src = helperUrl+'imgs/PSsee.svg';
  } else {
    getElement('LGpassword').type = 'password';
    getElement('LGbtn').src = helperUrl+'imgs/PShide.svg';
  }
},

Markdown = function(mdText) {
  // first, handle syntax for code-block
  mdText = mdText.replace(/\r\n/g, '\n');
  mdText = mdText.replace(/\n~~~ *(.*?)\n([\s\S]*?)\n~~~/g, '<pre><code title="$1">$2</code></pre>' );
  mdText = mdText.replace(/\n``\` *(.*?)\n([\s\S]*?)\n``\`/g, '<pre><code title="$1">$2</code></pre>' );
  
  // split by "pre>", skip for code-block and process normal text
  var mdHTML = '';
  var mdCode = mdText.split( 'pre>');
  
  for (var i=0; i<mdCode.length; i++) {
    if ( mdCode[i].substr(-2) == '</' ) {
      mdHTML += '<pre>' + mdCode[i] + 'pre>';
    } else {
      mdHTML += mdCode[i].replace(/(.*)<$/, '$1')
      .replace(/^##### (.*?)\s*#*$/gm, '<h5>$1</h5>')
      .replace(/^#### (.*?)\s*#*$/gm, '<h4>$1</h4>')
      .replace(/^### (.*?)\s*#*$/gm, '<h3>$1</h3>')
      .replace(/^## (.*?)\s*#*$/gm, '<h2>$1</h2>')
      .replace(/^# (.*?)\s*#*$/gm, '<h1>$1</h1>')  

      .replace(/^-{3,}|^\_{3,}|^\*{3,}/gm, '<hr/>')  

      .replace(/``(.*?)``/gm, '<code>$1</code>' )
      .replace(/`(.*?)`/gm, '<code>$1</code>' )

      .replace(/^\>\> (.*$)/gm, '<blockquote><blockquote>$1</blockquote></blockquote>')
      .replace(/^\> (.*$)/gm, '<blockquote>$1</blockquote>')

      .replace(/!\[(.*?)\]\((.*?) "(.*?)"\)/gm, '<img style=max-width:100% alt="$1" src="$2" $3 />')
      .replace(/!\[(.*?)\]\((.*?)\)/gm, '<img style=max-width:100% alt="$1" src="$2" />')
      .replace(/\[(.*?)\]\((.*?) "(.*?)"\)/gm, '<a href="$2" title="$3">$1</a>')

      .replace(/\<http(.*)\>/gm, '<a href="http$1">http$1</a>')
      .replace(/\[(.*?)\]\(\)/gm, '<a href="$1">$1</a>')
      .replace(/\[(.*?)\]\((.*?)\)/gm, '<a href="$2">$1</a>')

      .replace(/^[\*|+|-][ |.](.*)/gm, '<ul><li>$1</li></ul>' ).replace(/<\/ul\>\n<ul\>/g, '\n' )
      .replace(/^\d[ |.](.*)/gm, '<ol><li>$1</li></ol>' ).replace(/<\/ol\>\n<ol\>/g, '\n' )

      .replace(/\*\*(.*)\*\*/gm, '<b>$1</b>')
      .replace(/\*(.*)\*/gm, '<em>$1</em>')
      .replace(/\_\_(.*)\_\_/gm, '<u>$1</u>')
      .replace(/\_(.*)\_/gm, '<em>$1</em>')
      .replace(/~~(.*)~~/gm, '<del>$1</del>')
      .replace(/\^\^(.*)\^\^/gm, '<ins>$1</ins>')

      .replace(/ +\n/g, '\n<br/>')
      .replace(/\n\s*\n/g, '\n<p>\n')
      .replace(/^ {4,10}(.*)/gm, '<pre><code>$1</code></pre>')
      .replace(/^\t(.*)/gm, '<pre><code>$1</code></pre>' )
      .replace(/<\/code\><\/pre\>\n<pre\><code\>/g, '\n' )
      .replace(/\\([`_\\\*\+\-\.\(\)\[\]\{\}])/gm, '$1' );
    }  
  }
  mdHTML = mdHTML.replaceAll("\n", '<br>');
  return mdHTML.trim();
},

MediaRender = function(comicText) {
  let comicArray = comicText.split('\n'),
  comicStr = [];

  if (comicArray.length <= 1)
    return `<img style=max-width:100% src=${comicText}>`;

  for (let i = 0; i < comicArray.length; i++) {
    comicStr.push(`<img style=max-width:100% src=${comicArray[i]}> `)
  };
  return comicStr.join(' ');
},

switchLangMenu = function() {
  let preLang = '';
  for (let lang in translateData) {
    preLang += 
    `<button onclick="switchLang('${lang}')" style="width:40px;margin:2px" class="emptybtn">`+
      `<img src="${helperUrl}imgs/${lang}.png" width=40px style="padding-bottom:6px">`+
    `</button>`;
  };
  return `<div id=switchHtmlLang2 style="position:absolute;top:0px;left:48px;padding:8px;border:solid var(--color-black) 3px;border-radius:var(--def-border-small);background-color:rgba(255,255,255,.1);">`+
    preLang+
  `</div>`;
},
switchLang = function(lang = 32) {
  if (lang === 32) {
    if (!getElement('switchHtmlLang2')) {
      getElement('switchHtmlLang').insertAdjacentHTML('beforeend', switchLangMenu());
    } else {
      getElement('switchHtmlLang2').remove();
    }
  } else {
    translateReplaceLang(lang);
    getElement('switchHtmlLang2').remove()
  }
},

switchLoginMenu = function(predrop) {
  if (thisUser[1] === 0) {
    loginPage();
    return '';
  }
  if (predrop === 'predrop')
    predrop = 'dropLogin(1);';
  let preLang = '';
  preLang +=
  `<button style="width:80px" class="emptybtn" onclick="${predrop}innerMain(profilePage())">`+
    `<span${getTrans('profile')}/span>`+
  `</button>`+
  `<button style="width:80px" class="emptybtn" onclick="${predrop}gLogout()">`+
    `<span${getTrans('logout')}/span>`+
  `</button>`;
  return `<div id=switchHtmlLogin2 style="position:absolute; bottom:-55px; right:0px; padding:8px; border:solid var(--color-black) 3px;border-radius:var(--def-border-small); background-color:rgba(255,255,255,.1);">`+
    preLang+
  `</div>`;
},
switchLogin = function(lang = 32, predrop = '') {
  if (lang === 32) {
    if (!getElement('switchHtmlLogin2')) {
      getElement('switchHtmlLogin').insertAdjacentHTML('beforeend', switchLoginMenu(predrop))
    } else {
      getElement('switchHtmlLogin2').remove()
    }
  } else {
    getElement('switchHtmlLogin2').remove()
  }
},
profileSwitcherPhone = function() {
  let
  profileNavPhone = getElement('phoneSelectorSmall'),
  profileContent = getElement('helperContentProfile');

  if (headerPhoneSwitcher !== 1) {
    headerPhoneSwitcher = 1;
    profileNavPhone.style.display = 'grid';
    profileContent.style.display = 'none';
  } else {
    headerPhoneSwitcher = 0;
    profileNavPhone.style.display = 'none';
    profileContent.style.display = 'block';
  }
},
switchMobileMain = function() {
  let 
  helperNavPhone = getElement('helperSecond'),
  pageContent = getElement('helperContent');

  if (headerPhoneSwitcher !== 2) {
    headerPhoneSwitcher = 2;
    helperNavPhone.style.display = 'grid';
    pageContent.style.display = 'none';
  } else {
    if (getElement('phoneSelectorSmall') && getElement('phoneSelectorSmall').style.display === 'grid' && headerPhoneSwitcher !== 0) {
      headerPhoneSwitcher = 1;
      helperNavPhone.style.display = 'none';
      pageContent.style.display = 'block';
    } else {
      headerPhoneSwitcher = 0;
      helperNavPhone.style.display = 'none';
      pageContent.style.display = 'block';
    }
  }
},

Loading = function(stop = 0) {
  if (stop == 0)
    document.body.insertAdjacentHTML('beforeend',
      `<div data-trans="loading..." class=ALERT id=TheLoadElem style=position:absolute;top:20%;left:50%>`+
        `<h1${getTrans('loading...')}/h1>`+
      `</div>`
    );
  else 
    if (getElement('TheLoadElem'))
      getElement('TheLoadElem').remove();
},
linkCopy = function(string) {
  navigator.clipboard.writeText(string)
    .then(function() {})
    .catch(function(error) {Console.error(error);returnError(error)});
  megaAlert('copied');
},
megaAlert = function(text, waitTime = 3000) {
  innerMain(`<div class=ALERT id=alert style=top:20%;left:50%><h1${getTrans(text)}/h1></div>`,1);
  setTimeout(function() {
    getElement('alert').remove();
  }, waitTime);
},

enterFormData = function(form, sendPlace) {
  let FORMDATA = new FormData(form);
  params = '';

  switch (sendPlace) {
    case 'newsPost'+sData[7]:
      if (FORMDATA.get('gdps').startsWith('s')) {
        FORMDATA.append('type', 1);
        FORMDATA.set('gdps', FORMDATA.get('gdps').slice(1))
      } else if (FORMDATA.get('gdps').startsWith('c')) {
        FORMDATA.append('type', 0);
        FORMDATA.set('gdps', FORMDATA.get('gdps').slice(1))
      } else
        FORMDATA.append('type', 0);
      break;
  }

  let postHasFiles = false;
  for (let [key, value] of FORMDATA.entries()) {
    if (value instanceof File) {
      postHasFiles = true;
      break;
    }
  }
  if (!postHasFiles)
    params = new URLSearchParams(FORMDATA).toString();
  else
    params = FORMDATA;

  Loading();
  helperRequest(`${sData[1]}${sendPlace}`, params)
  .then(function(data) {
    if (sendPlace.indexOf('?') !== -1)
      sendPlace = sendPlace.split('?')[0];
    switch (sendPlace) {
      case 'forumPost'+sData[7]:
        let parsedData = JSON.parse(data);
        getForumPost(parsedData[0],parsedData[1]);
        break;
      case 'newsPost'+sData[7]:
        if (FORMDATA.get('type') == 1)
          getShow(FORMDATA.get('gdps'));
        else 
          getCamp(FORMDATA.get('gdps'));
        break;
      case 'writeAlarm'+sData[7]:
        getElement('F45').remove();
        break;
      case 'report'+sData[7]:
        megaAlert('reported', 1000);
        closeWindow(windowsCount+'REPform');
        break;
      case 'newGuide'+sData[7]:
        getGuide(data, FORMDATA.get('wikiId'));
        break;
      case 'editGuide'+sData[7]:
        getGuide(data, FORMDATA.get('wikiId'));
        break;
      case 'newWiki'+sData[7]:
        pageGuides(data);
        break;
      case 'editWiki'+sData[7]:
        pageGuides(data);
        break;
      case 'refreshGdps'+sData[7]:
        closeWindow(windowsCount+'link'+FORMDATA.get('gdps'));
        if (data === FORMDATA.get('link')) 
          megaAlert('reported');
        else 
          megaAlert('otmena');
        break;
      default:
        let serverResp = JSON.parse(data);
        CacheCamps = serverResp[1];
        CacheShows = serverResp[2];
        CacheWikis = serverResp[3];
        thisUser = serverResp[0];
        myCamps = [];
        myShows = [];
        myguides = [];
        myCamps.push(serverResp[3][0]);
        myShows.push(serverResp[3][1]);
        myguides.push(serverResp[3][2]);
        innerMain(profilePage());
    };
    Loading(1);
    return false;
  })
  .catch(function(error) {returnError(error);throw new Error();});

  return false;
},
deleteNews = function(id, goBack) {
  Loading();
  helperRequest(`${sData[4]}newsPost${sData[7]}?ide=${id}`)
    .then(function(data) {
      if (data == '-1')
        return returnError('Access denied');
      getElement('news'+data).remove();
      goBack ? history.back() : null;
      Loading(1);
    })
    .catch(function(error) {returnError(error);throw new Error();});
},

checkOwn = function(contentId, userId, type) {
  let log = logAll;
  if (log === true) Console.log(contentId+' '+userId+' '+type);

  if (userId === thisUser[1]) {
    if (log === true) Console.log('fullowner');
    return 2;
  }
  if (log === true) Console.log('fullfalse');
  return 0;
},
// #endregion
// #region страницы в профилях
otherProfileMini = function(userId) {
  setLink('profiles='+userId);
  Loading();
  helperRequest(`${sData[0]}getUser${sData[7]}?id=${userId}`)
  .then(function(data) {
    let userData = JSON.parse(data),
      accStatus = userData[3] ? getTrans('isActive') : getTrans('isNotact'),
      html = 
    `<div id=helperContentProfile>`+
      `<h1><span${getTrans('profile')}/span> ${userData[0]}</h1>`+
      `<p><span${getTrans('profName')}/span>: ${userData[0]}</p>`+
      `<p><span${getTrans('profId')}/span>: ${userData[1]}</p>`+
      `<p><span${getTrans('profRole')}/span>: ${toStringRole(userData[2])}</p>`+
      `<p><span${getTrans('notProfAccs')}/span> ${userData[0]} <span${accStatus}/span></p>`+
    `</div>`;
    innerProfile(html);
    Loading(1);
  })
  .catch(function(error) {returnError(error);throw new Error();});
},
otherCampsWindow = function(userId) {
  setLink('profCamps='+userId);
  Loading();
  helperRequest(`${sData[0]}getAddedCamps${sData[7]}?id=${userId}&type=0`)
  .then(function(data) {
    let parsedData = JSON.parse(data),
      gdpses = "";
    parsedData.forEach(function(gdps) {
      if (typeof(gdps) == 'object') {
        gdpses+=GDPSrenderInProfile(gdps);
      };
    });
    let html =
    `<div id=helperContentProfile>`+
      `<h1><span${getTrans('searchCamps')}/span> ${parsedData[0]}</h1><br>`+
      `<div style='display: flex; flex-direction: column; height:calc(100vh - 380px); overflow:auto' align=left>`+
        gdpses+
      `</div>`+
    `</div>`;
    innerProfile(html);
    Loading(1);
  })
  .catch(function(error) {returnError(error);throw new Error();});
},
otherShowsWindow = function(userId) {
  setLink('profShows='+userId);
  Loading();
  helperRequest(`${sData[0]}getAddedShows${sData[7]}?id=${userId}&type=1`)
  .then(function(data) {
    let parsedData = JSON.parse(data),
      gdpses = "";
    parsedData.forEach(function(gdps) {
      if (typeof(gdps) == 'object') {
        gdpses+=SHOWrenderInProfile(gdps);
      };
    });
    let html =
    `<div id=helperContentProfile>`+
      `<h1><span${getTrans('searchShows')}/span> ${parsedData[0]}</h1><br>`+
      `<div style='display: flex; flex-direction: column; height:calc(100vh - 380px); overflow:auto' align=left>`+
        gdpses+
      `</div>`+
    `</div>`;
    innerProfile(html);
    Loading(1);
  })
  .catch(function(error) {returnError(error);throw new Error();});
},
otherWikisWindow = function(userId) {
  setLink('profWikis='+userId);
  Loading();
  helperRequest(`${sData[0]}getUserGuides${sData[7]}?id=${userId}`)
  .then(function(data) {
    let parsedData = JSON.parse(data),
      gdpses = "";
    parsedData.forEach(function(gdps) {
      if (typeof(gdps) == 'object') {
        gdpses+=WIKIrenderInProfile(gdps);
      }
    });
    let html =
    `<div id=helperContentProfile>`+
      `<h1><span${getTrans('guides09')}/span> ${parsedData[0]}</h1><br>`+
      `<div style='display:flex;align-items:center;flex-wrap:wrap;flex-direction:row' align=left>`+
        gdpses+
      `</div>`+
    `</div>`;
    innerProfile(html);
    Loading(1);
  })
  .catch(function(error) {returnError(error);throw new Error();});
},
GDPSrenderInProfile = function(parsedData) {
  let html = '',
    Count = 0,

    gdpsData = null,
    thisId = null,
    title = null,
    description = null,
    likesCount = null,
    userId = null,
    username = null,
    pictureLink = null,
    bannerLink = null,
    renderJoinLink = null,
    isWeeklyData = ['',''],
    checked;
  
  for (let Id in parsedData) {
    Count++;
    if (Count == 9)
      return html;
    
    gdpsData = parsedData[Id];
    thisId = gdpsData.ID;
    title = gdpsData.title;
    description = gdpsData.text;
    likesCount = gdpsData.likes;
    userId = gdpsData.author;
    username = gdpsData.username;
    pictureLink = gdpsData.img;
    bannerLink = BETA_fixImg(gdpsData.ban);
    renderJoinLink = gdpsData.freejoin;
    checked = gdpsData.checked;

    renderJoinLink = renderJoinLink ? null : `<a class="loginbtn" href="${curlJoin}../join${sData[7]}?id=${thisId}" target=_blank${getTrans('joinToGdps')}/a>`;

    isWeeklyData = ['',''];

    html += 
    `<div class="framegdpsOld" style="${isWeeklyData[0]}width:calc(100% - 40px);" id="${thisId}">`+
      `${isWeeklyData[1]}`+
      `<h2 style="display:inline;margin-right:4px">${title}</h2>`+
      `<p style="display:inline;margin:0">`+
        `<span${getTrans('addedBy')}/span>:`+
        `<button onclick="otherProfile(${userId},'innerMain(pageList())')" style="background:0;border:0;color:var(--color-white)">${username}</button>`+
      `</p>`+
      `<div style="min-height:32px">`+
        `<img onerror="Console.warn('broken link');this.src='${helperUrl}imgs/hubbig.png'" align="left" src="${decodeURIComponent(pictureLink)}" width=32px height=32px style="border-radius:calc(var(--def-border-small)*0.75)">`+
        `<p>${description}</p>`+
      `</div>`+
      `<div style="margin-top:15px;padding-bottom:15px">`+
        `<a class="loginbtn" href="${curlJoin}../join${sData[7]}?id=${thisId}" target="_blank"${getTrans('joinToGdps')}/a>`+
      `</div>`+
    `</div>`;
  };
  return html;
},
SHOWrenderInProfile = function(parsedData) {
  let html = '',
    Count = 0,

    gdpsData = null,
    thisId = null,
    title = null,
    description = null,
    likesCount = null,
    userId = null,
    username = null,
    pictureLink = null,
    bannerLink = null,
    renderJoinLink = null,
    isWeeklyData = ['',''],
    checked;
  
  for (let Id in parsedData) {
    Count++;
    if (Count == 9)
      return html;
    
    gdpsData = parsedData[Id];
    thisId = gdpsData.ID;
    title = gdpsData.title;
    description = gdpsData.text;
    likesCount = gdpsData.likes;
    userId = gdpsData.author;
    username = gdpsData.username;
    pictureLink = gdpsData.img;
    bannerLink = BETA_fixImg(gdpsData.ban);
    renderJoinLink = gdpsData.freejoin;
    checked = gdpsData.checked;

    renderJoinLink = renderJoinLink ? null : `<a class="loginbtn" href="${curlJoin}../join${sData[7]}?id=${thisId}" target=_blank${getTrans('joinToGdps')}/a>`;

    isWeeklyData = ['',''];

    html += 
    `<div class="framegdpsOld" style="${isWeeklyData[0]}width:calc(100% - 40px);" id="${thisId}">`+
      `${isWeeklyData[1]}`+
      `<h2 style="display:inline;margin-right:4px">${title}</h2>`+
      `<p style="display:inline;margin:0">`+
        `<span${getTrans('addedBy')}/span>:`+
        `<button onclick="otherProfile(${userId},'innerMain(pageShows())')" style="background:0;border:0;color:var(--color-white)">${username}</button>`+
      `</p>`+
      `<div style="min-height:32px">`+
        `<img onerror="Console.warn('broken link');this.src='${helperUrl}imgs/hubbig.png'" align="left" src="${decodeURIComponent(pictureLink)}" width=32px height=32px style="border-radius:calc(var(--def-border-small)*0.75)">`+
        `<p>${description}</p>`+
      `</div>`+
      `<div style="margin-top:15px;padding-bottom:15px">`+
        `<a class="loginbtn" href="${curlJoin}../join${sData[7]}?id=${thisId}" target="_blank"${getTrans('joinToGdps')}/a>`+
      `</div>`+
    `</div>`;
  };
  return html;
},
GUIDrenderInProfile = function(parsedData) {
  let html = '',
    
    gdpsData = null,
    id = null,
    guidTitle = null,
    guidLang = null,
    date = null,
    likes = null,
    guidImg = null,
    userId = null;

  for (let Id in parsedData) {
    
    gdpsData = parsedData[Id];
    id = gdpsData[0];
    guidTitle = gdpsData[1];
    guidLang = gdpsData[2];
    date = gdpsData[5];
    likes = gdpsData[7];
    guidImg = gdpsData[7];
    userId = gdpsData[8];

    html += 
    `<div class=framegdpsOld style="width:260px;height:200px" id="${id}">`+
      `<img width=276px height=133px src="${guidImg}" onerror="Console.warn('broken link');this.src='${helperUrl}imgs/hubemp.png'" style="position:absolute;top:0;left:0;margin:0;border-top-left-radius:var(--def-border);border-top-right-radius:var(--def-border)">`+
      `<h2 style="z-index:1;position:inherit;margin-top:120px">${guidTitle} <img src="${helperUrl}imgs/${guidLang}.png"></h2>`+
      `<div style="position: absolute;top: 0;left: 0;width: 276px;height: 60px;margin-top: 73px;background: linear-gradient(rgba(0,0,0,0), var(--color-profile-alpha), var(--color-profile));"></div>`+
      `<div style="bottom:12px;left:20px" class="absolute btnszone">`+
        `<button onclick="getGuide(${id})" class=loginbtn style="margin-top:8px"${getTrans('moreInfo')}/button>`+
      `</div>`+
    `</div>`;
  };
  return html;
},
WIKIrenderInProfile = function(parsedData) {
  let html = '',
    
    gdpsData = null,
    id = null,
    guidTitle = null,
    guidLang = null,
    date = null,
    likes = null,
    guidImg = null,
    userId = null;

  for (let Id in parsedData) {
    
    gdpsData = parsedData[Id];
    id = gdpsData[0];
    guidTitle = gdpsData[1];
    guidLang = gdpsData[2];
    date = gdpsData[5];
    likes = gdpsData[7];
    guidImg = gdpsData[7];
    userId = gdpsData[8];

    html += 
    `<div class=framegdpsOld style="width:260px;height:200px" id="${id}">`+
      `<img width=276px height=133px src="${guidImg}" onerror="Console.warn('broken link');this.src='${helperUrl}imgs/hubemp.png'" style="position:absolute;top:0;left:0;margin:0;border-top-left-radius:var(--def-border);border-top-right-radius:var(--def-border)">`+
      `<h2 style="z-index:1;position:inherit;margin-top:120px">${guidTitle} <img src="${helperUrl}imgs/${guidLang}.png"></h2>`+
      `<div style="position: absolute;top: 0;left: 0;width: 276px;height: 60px;margin-top: 73px;background: linear-gradient(rgba(0,0,0,0), var(--color-profile-alpha), var(--color-profile));"></div>`+
      `<div style="bottom:12px;left:20px" class="absolute btnszone">`+
        `<button onclick="pageGuides(${id})" class=loginbtn style="margin-top:8px"${getTrans('moreInfo')}/button>`+
      `</div>`+
    `</div>`;
  };
  return html;
},
// #endregion
// #region кастомхелпер

cssRoot = document.documentElement.style,

RGBtoHEX = function(string) {
  let dataValues = string.split(',');
  let newColor = '#'
  dataValues.forEach(function(col) {
    let hexColor = parseInt(col).toString(16);

    if (hexColor.length > 2)
      newColor += 'ff';
    else if (hexColor.length < 2)
      newColor += '0'+hexColor;
    else
      newColor += hexColor;
  })
  return newColor;
},
HEXtoRGB = function(hex) {
  let preData = hex.replace('#', ''),
  dataValuesPre = preData.split(''),
  dataValues = [''],

  splitter = false,
  arrId = 0,
  colId = 0;
  dataValuesPre.forEach(function(el) {
    dataValues[arrId] += el;
    if (splitter) {
      arrId += 1;
      if (arrId === 3)
        return;
      dataValues[arrId] = '';
    }
    splitter = !splitter;
  });

  dataValues.forEach(function(col) {
    dataValues[colId] = parseInt(col,16);
    colId++;
  });

  return dataValues.join();
},
setColor = function(name, value, SlocalValue = '') {
  cssRoot.setProperty(name, value);
  if (SlocalValue)
    Slocal.set(SlocalValue, value);
},
dropColorScheme = function() {
  let rgb = {
    r: 97,
    g: 42,
    b: 157,
  },
  bgColor =  `${parseInt(rgb.r*0.09)},${parseInt(rgb.g*0.08)},${parseInt(rgb.b*0.08)}`,
  mainColor =  `${parseInt(rgb.r   )},${parseInt(rgb.g   )},${parseInt(rgb.b   )}`,
  windowColor =`${parseInt(rgb.r*0.65)},${parseInt(rgb.g*0.75)},${parseInt(rgb.b*0.6 )}`,
  lightColor = `${parseInt(rgb.r*1.35)},${parseInt(rgb.g*0   )},${parseInt(rgb.b*1.65)}`,
  profColor =  `${parseInt(rgb.r*0.3 )},${parseInt(rgb.g*0.5 )},${parseInt(rgb.b*0.2 )}`,

  colorScheme =
    `Bg|${RGBtoHEX(bgColor)},`+
    `Bg-alpha|${RGBtoHEX(bgColor)},`+
    `Main|${RGBtoHEX(mainColor)},`+
    `Light|${RGBtoHEX(lightColor)},`+
    `Window|${RGBtoHEX(windowColor)},`+
    `Profile|${RGBtoHEX(profColor)},`+
    `Profile-alpha|${RGBtoHEX(profColor)},`+
    `Black|#13120f,`+
    `White|#ffffff/`+

    `Font|16,`+
    `Btn-size|16,`+
    `Text-indent|16,`+
    `Border-small|8,`+
    `Border|12,`+
    `Border-large|30`;
  Slocal.set('ColorScheme', colorScheme);

  let [Colors, Sizes] = Slocal.get('ColorScheme').split('/');
  Console.log(Colors);
  Console.log(Sizes);

  Colors.split(',').forEach(function(col) {
    let [name,hex] = col.split('|');

    if (name.includes('-alpha'))
      hex += '99';
    SlocalName = 'Color' + name,
    CSSname = '--color-'+name.toLowerCase();
    setColor(CSSname, hex, SlocalName)
    Slocal.set(name, hex);
  });
  Sizes.split(',').forEach(function(siz) {
    let [name,val] = siz.split('|');

    val += 'px'

    SlocalName = 'Size' + name,
    CSSname = '--def-'+name.toLowerCase();
    setColor(CSSname, val, SlocalName)
    Slocal.set(name, val);
  });
  clrEditPage();
},
setToColorScheme = function() {
  let colorScheme = '',
    sizeScheme = '';
  document.querySelectorAll('[iscolorscheme]').forEach(function(el) {
    if (el.type == 'color') {
      let nameLover = '--color-'+el.name.toLowerCase(),
        hex = el.value,
        name = el.name;
      colorScheme += `,${name}|${hex}`;
      
      if (name.includes('-alpha'))
        hex += '99';
      setColor(nameLover, hex, 'Color'+name);
    } else {
      let nameLover = '--def-'+el.name.toLowerCase(),
        val = el.value,
        name = el.name;
      sizeScheme += `,${name}|${val}`;
      setColor(nameLover, val+'px', 'Size'+name);
    }
  })
  Slocal.set('ColorScheme',colorScheme.slice(1) + '/' + sizeScheme.slice(1));
  innerProfile(gProfileMini());
},
setColorScheme = function() {
  Slocal.set('ColorScheme',getElement('scheme').value);
  let [Colors, Sizes] = Slocal.get('ColorScheme').split('/');
  Colors.split(',').forEach(function(col) {
    let [name,hex] = col.split('|');

    if (name.includes('-alpha'))
      hex += '99';
    SlocalName = 'Color' + name,
    CSSname = '--color-'+name.toLowerCase();
    setColor(CSSname, hex, SlocalName)
    Slocal.set(name, hex);
  });
  Sizes.split(',').forEach(function(siz) {
    let [name,val] = siz.split('|');

    SlocalName = 'Size' + name,
    CSSname = '--def-'+name.toLowerCase();
    setColor(CSSname, val+'px', SlocalName)
    Slocal.set(name, val);
  })
},
clrEditPage = function() {
  setLink('color');
  let [Colors, Sizes] = Slocal.get('ColorScheme').split('/'),
    colorScheme = Colors.split(','),
    sizeScheme = Sizes.split(','),
    MenuC = '',
    MenuS = '',
    colorListeners = '',
    sizeListeners = '';

  colorScheme.forEach(function(el) {
    let [name,value] = el.split('|');
    MenuC += 
    `<tr>`+
      `<td`+
        getTrans(name)+
      `/td>`+
      `<td>`+
        `<input iscolorscheme class=colorscheme class=colorscheme type=color id="color-${name}" name="${name}" value=${value}>`+
      `</td>`+
    `</tr>`;
    colorListeners += `,color-${name}`;
  });
  sizeScheme.forEach(function(el) {
    let [name,value] = el.split('|');
    MenuS += 
    `<tr>`+
      `<td`+
        getTrans(name)+
      `/td>`+
      `<td>`+
        `<input iscolorscheme class="headbtn" style="padding:0;margin:0;height:48px;margin:-9px 0 -9px 0" class=colorscheme type=range min=8 max=48 step=1 id="color-${name}" name="${name}" value=${value}>`+
      `</td>`+
      `<td>`+
        ` <span id="${name}">${value}px</span>`+
      `</td>`+
    `</tr>`;
    sizeListeners += `,color-${name}`;
  });

  let html = 
  `<div id=helperContentProfile>`+
    `<h1${getTrans('settings001')}/h1>`+
    `<div style=position:relative id=newNick></div>`+
    `<h2${getTrans('settings005')}/h2>`+
    `<table>`+
      MenuC+
    `</table>`+
    `<h2${getTrans('settings006')}/h2>`+
    `<table>`+
      MenuS+
    `</table>`+
    `<button class=loginbtn onclick=setToColorScheme()${getTrans('settings002')}/button><br>`+
    `<button class=loginbtn onclick=dropColorScheme()${getTrans('settings003')}/button><br><br><br>`+
    `<div style=display:flex>`+
      `<textarea name=scheme class=framelabel style="width:calc(100% - 170px)" id=scheme>${Slocal.get('ColorScheme')}</textarea>`+
      `<button class=loginbtn onclick=setColorScheme()${getTrans('settings004')}/button><br><br>`+
    `</div>`+
  `</div>`;

  innerProfile(html);
  colorListeners.slice(1).split(',').forEach(function(id) {
    getElement(id).addEventListener('input', function(el) {
      setColor('--color-'+el.target.name.toLowerCase(), el.target.value, 'Color'+el.target.name);
    })
  });
  sizeListeners.slice(1).split(',').forEach(function(id) {
    let el = getElement(id)
    el.addEventListener('change', function(el) {
      setColor('--def-'+el.target.name.toLowerCase(), el.target.value+'px', 'Size'+el.target.name);
    });
    el.addEventListener('input', function(el) {
      getElement(el.target.name).innerHTML = el.target.value+'px';
    });
  });
};

if (Slocal.get('ColorVer') < 3 || !Slocal.get('ColorVer')) {
  Slocal.set('ColorVer',   3);

  let rgb = {
    r: 97,
    g: 42,
    b: 157,
  },
  bgColor =  `${parseInt(rgb.r*0.09)},${parseInt(rgb.g*0.08)},${parseInt(rgb.b*0.08)}`,
  mainColor =  `${parseInt(rgb.r   )},${parseInt(rgb.g   )},${parseInt(rgb.b   )}`,
  windowColor =`${parseInt(rgb.r*0.65)},${parseInt(rgb.g*0.75)},${parseInt(rgb.b*0.6 )}`,
  lightColor = `${parseInt(rgb.r*1.35)},${parseInt(rgb.g*0   )},${parseInt(rgb.b*1.65)}`,
  profColor =  `${parseInt(rgb.r*0.3 )},${parseInt(rgb.g*0.5 )},${parseInt(rgb.b*0.2 )}`,

  colorScheme =
    `Bg|${RGBtoHEX(bgColor)},`+
    `Bg-alpha|${RGBtoHEX(bgColor)},`+
    `Main|${RGBtoHEX(mainColor)},`+
    `Light|${RGBtoHEX(lightColor)},`+
    `Window|${RGBtoHEX(windowColor)},`+
    `Profile|${RGBtoHEX(profColor)},`+
    `Profile-alpha|${RGBtoHEX(profColor)},`+
    `Black|#13120f,`+
    `White|#ffffff/`+

    `Font|16,`+
    `Btn-size|16,`+
    `Text-indent|16,`+
    `Border-small|8,`+
    `Border|12,`+
    `Border-large|30`;
  Slocal.set('ColorScheme', colorScheme);
}

let [Colors, Sizes] = Slocal.get('ColorScheme').split('/');

Colors.split(',').forEach(function(col) {
  let [name,hex] = col.split('|');

  if (name.includes('-alpha'))
    hex += '99';
  SlocalName = 'Color' + name,
  CSSname = '--color-'+name.toLowerCase();
  setColor(CSSname, hex, SlocalName)
  Slocal.set(name, hex);
});
Sizes.split(',').forEach(function(siz) {
  let [name,val] = siz.split('|');

  val += 'px'

  SlocalName = 'Size' + name,
  CSSname = '--def-'+name.toLowerCase();
  setColor(CSSname, val, SlocalName)
  Slocal.set(name, val);
});
// #endregion
// #region предстартовые проверки

if (!Slocal.get('Lang') || Slocal.get('Lang') == 'Ru') {
  mainLang = 'RU';
  Slocal.set('Lang', 'RU');
};

window.addEventListener('popstate', function() {
  ignore = true;
  getLink();
});

window.addEventListener('input', function() {
  Console.log(location.search);
  if (location.search !== '?list' && location.search !== '?shows')
    return;
  let INPUT = getElement('gdpsNameInput').value;
  clearTimeout(TimeOut[0]);
  if (INPUT != '') {
    TimeOut[0] = setTimeout(function() {
      sendFinder();
    }, 300);
  }
});

//window.addEventListener('resize', setImgSize);

window.onerror = function(a, b, c, d, e) {
  Console.log(`message: ${a}`);
  Console.log(`source: ${b}`);
  Console.log(`lineno: ${c}`);
  Console.log(`colno: ${d}`);
  Console.log(`error: ${e}`);

  returnError(
    `${a}`+
    `\nON LINE ${c} IN COLUMN ${d}`
  );

  return true;
};
// #endregion

reStart();
