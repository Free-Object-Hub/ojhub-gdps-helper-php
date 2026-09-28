<?php
session_start();
require_once 'build.php';
require_once './server/'.$build.'/api/api.php';

$er = '';
if (isset($_GET['auth']) || isset($_POST['auth'])) {
    $auth = $_GET['auth'] ?? $_POST['auth'];
    $tokenPass = User::getDropPassToken($auth);
    if ($tokenPass == false)
        exit('Ссылка истекла');
    if (($tokenPass['date'] + 86400) < time())
        exit('Ссылка истекла');
    if ($tokenPass['isUsed'] != 0)
        exit('Ссылка истекла');

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if (isset($_POST['password']) && $_POST['password'] === $_POST['password2']) {
            if ($tokenPass['username'] == $_POST['username']) {
                $user = Users::fetchByUsername($tokenPass['username']);
                $user->refreshPassword($_POST['password'], $auth);
                echo "<script>localStorage.setItem('oschubUser', '$user->token');location = './?profile'</script>";
                //header('Location: https://objecthub.xyz/?profile');
                exit();
            } else $er = 'Непредвиденная ошибка!';
        } else $er = 'Пароли не совпадают!';
    }
} else
    exit('Ссылка истекла');
?>
<head>
    <title>ojhub - Подтверждение аккаунта</title>
    <link href="./main.css" rel="stylesheet">
    <meta name=viewport content="width=device-width,initial-scale=1.0">
    <meta charset="UTF-8">
    <script>
        let getElement = (i)=>document.getElementById(i),
            TimeOut = null,
            sendForm = false;
        window.addEventListener('input', e=>{
            clearTimeout(TimeOut);
            TimeOut = setTimeout(()=>{
                let pass1 = getElement('password1').value,
                    pass2 = getElement('password2').value,
                    pass3 = getElement('password3'),
                    pass4 = getElement('password4');
                if (pass1 !== '' && pass2 !== '')
                    if (pass1 === pass2) {
                        pass3.innerHTML = 'Пароли совпадают';
                        pass3.style.color = '#00FF00';
                        pass4.style.opacity = '100%';
                        sendForm = true;
                    } else {
                        pass3.innerHTML = 'Пароли не совпадают';
                        pass3.style.color = '#FF0000';
                        pass4.style.opacity = '40%';
                        sendForm = false;
                    }
            }, 50);
        });
    </script>
</head>
<body style="background-color: var(--color-bg); --color-bg: #08030c; --color-bg-alpha: #08030c99; --color-main: #612a9d; --color-light: #8200ff; --color-window: #3f1f5e; --color-profile: #1d151f; --color-profile-alpha: #1d151f99; --color-black: #13120f; --color-white: #ffffff; --def-font: 16px; --def-btn-size: 16px; --def-text-indent: 16px; --def-border-small: 8px; --def-border: 12px; --def-border-large: 30px; --rr-tags: 100%; --rr-text: 0%;">
    <br><br><br><br>
    <div class="frameprofile">
        <h1>Сброс пароля</h1>
        <h2><?php echo $er?></h2>
        <form method="post" action="./refresh.php" onsubmit="return sendForm">
            <input name="auth" type="hidden" value="<?php echo $_GET['auth']?>">
            <input type="text" name="username" placeholder="Логин" class="framelabel"><br><br>
            <input type="password" id="password1" name="password" placeholder="Пароль" class="framelabel"><br><br>
            <input type="password" id="password2" name="password2" placeholder="Подтвердите пароль" class="framelabel"><br><br><br><br>
            <p id="password3"></p>
            <input id="password4" style="opacity:40%" type="submit" value="Подтвердить" class="loginbtn" align="center">
        </form>
    </div>
</body>