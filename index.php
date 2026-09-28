<?php $err = true;
$a = "<!DOCTYPE html>
<html>
    <head>\n";
if (isset($_GET['camp'])) {
    $id = $_GET['camp'];
    require_once __DIR__.'/build.php';
    require_once __DIR__.'/server/'.$build.'/api/api.php';
    $gdps = content::fetchById($id,0);
    if ($gdps != null) {
        $err = false;
        $a.= "        <meta property=\"og:title\" content=\"$gdps->title\">\n".
             "        <meta property=\"og:description\" content=\"$gdps->description\">\n".
             "        <meta property=\"og:image\" content=\"$gdps->img\">\n";
    }
}
if (isset($_GET['show'])) {
    $id = $_GET['show'];
    require_once __DIR__.'/build.php';
    require_once __DIR__.'/server/'.$build.'/api/api.php';
    $gdps = content::fetchById($id,1);
    if ($gdps != null) {
        $err = false;
        $a.= "        <meta property=\"og:title\" content=\"$gdps->title\">\n".
             "        <meta property=\"og:description\" content=\"$gdps->description\">\n".
             "        <meta property=\"og:image\" content=\"$gdps->img\">\n";
    }
}
if (isset($_GET['news'])) {
    $id = stristr($_GET['news'],'.',true);
    if ($id !== false) {
        require_once __DIR__.'/build.php';
        require_once __DIR__.'/server/'.$build.'/api/api.php';
        $news = content::fetchById($id,2);
        $gdps = content::fetchById($id,0);
        if ($news != null) {
            $id2 = trim(stristr($_GET['news'],'.'),'.');
            $gdps = content::fetchById($id2,0);
            $err = false;
            $text = base64_decode($news->text);
            $a.= "        <meta property=\"og:title\" content=\"Новость $news->title с $gdps->title\">\n".
                 "        <meta property=\"og:description\" content=\"$text\">\n".
                 "        <meta property=\"og:image\" content=\"$gdps->img\">\n";
        }
    }
}
if (isset($_GET['wikis']) || isset($_GET['Wikis'])) {
    $err = false;
    $a.= "        <meta property=\"og:title\" content=\"Object Hub Wiki\">\n".
         "        <meta property=\"og:description\" content=\"Добро пожаловать на наш редактор пользовательских вики!\">\n".
         "        <meta property=\"og:image\" content=\"https://objecthub.xyz/imgs/hubbig.png\">\n";
}
if (isset($_GET['wiki'])) {
    $id = $_GET['wiki'];
    require_once __DIR__.'/build.php';
    require_once __DIR__.'/server/'.$build.'/api/api.php';
    $wiki = Guides::fetchWikiById($id);
    if ($wiki != null) {
        $err = false;
        $a.= "        <meta property=\"og:title\" content=\"$wiki->title\">\n".
             "        <meta property=\"og:description\" content=\"$wiki->text\">\n".
             "        <meta property=\"og:image\" content=\"$wiki->img\">\n";
    }
}
if ($err === true) {
    $a.= "        <meta property=\"og:title\" content=\"Object hub\">\n".
         "        <meta property=\"og:description\" content=\"Удобный сервис для поиска и размещения своих обджект шоу и кемпов!\">\n".
         "        <meta property=\"og:image\" content=\"https://objecthub.xyz/imgs/hubbig.png\">\n";
}
echo$a?>
        <meta name=viewport content="width=device-width,initial-scale=1.0">
        <meta charset=UTF-8>
        <title>Object Hub</title>
        <link rel=icon>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Comfortaa:wght@300..700&family=Unbounded:wght@200..900&display=swap" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Comfortaa:wght@300..700&family=Huninn&family=Manrope:wght@200..800&family=News+Cycle:wght@400;700&family=Unbounded:wght@200..900&display=swap" rel="stylesheet">
        <link href="./static/main.css?ver=13" rel=stylesheet>
        <link href="./static/window.css?ver=13" rel=stylesheet>
        <script src="./static/newHelper.js?ver=14&helper"></script>
        <script defer src="./static/nhConfig.js?ver=20&helper"></script>
        <style id=wikiStyle></style>
    </head>
    <body style="background-color:var(--color-bg)">
        <div id=1st></div>
        <div id=windowsXP>
            <div id=Professional class=hider></div>
        </div>
    </body>
</html>
