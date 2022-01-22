<?

session_start();
unset($_SESSION["curr_account"]);
include "db.php";

// Применение шаблона и отображение страницы
function view_page($page)
{
    $template = file_get_contents($page);
    $template = preg_replace("/({FILE ?= ?\")([a-zA-z.0-9_]*)(\"})/",
        "<? include \"$2\" ?>", $template);
    file_put_contents("temp", $template);
    include "temp";
    unlink("temp");
}

// Вход в аккаунт
function log_in_account()
{
    $link = get_connection() or die("Connection error!");
    $query = mysqli_query($link, "SELECT * FROM `accounts_list` WHERE account_login='{$_POST["login"]}'");
    $data = mysqli_fetch_assoc($query);
    if ($data && ($data["account_password"] == $_POST["password"]))
    {
        session_unset();
        $_SESSION["curr_account"] = $data["account_name"];
        $_SESSION["curr_email"] = $data["account_email"];
        $sub = "Вход на сайт 'Волшебный мир Гарри Поттера'";
        $msg = "На ваш аккаунт с логином {$data["account_login"]} был выполнен вход.";
        $rec = $data["account_email"];
        mail($rec, $sub, $msg);
        $date = date("d.m.y");
        $query = mysqli_query($link, "SELECT * FROM `stat_list` WHERE day_count='$date'");
        $info = mysqli_fetch_assoc($query);
        if ($info)
            $query = mysqli_query($link, "UPDATE `stat_list` SET accounts_count=accounts_count+1 WHERE day_count='$date'");
        else
            $query = mysqli_query($link, "INSERT INTO `stat_list`(`day_count`, `accounts_count`) VALUES ('$date','1')");
        if ($data["account_login"] == "admin")
            echo "<script>document.location.href=\"../Admin/index.php\"</script>";
        else
            echo "<script>document.location.href=\"../Site/index.html\"</script>";
    }
}

// Регистрация аккаунта
function reg_new_account()
{
    if (($_POST["create_name"] != "") && ($_POST["create_email"] != "") &&
    ($_POST["create_login"] != "") && ($_POST["create_password"] != "") && ($_POST["captcha"] == $_SESSION["rand_number"])) 
    {
        $link = get_connection() or die("Connection error!");
        $query = mysqli_query($link, "SELECT * FROM `accounts_list` WHERE account_login='{$_POST["create_login"]}'");
        $data = mysqli_fetch_assoc($query);
        if (!$data)
        {
            $query = mysqli_query($link, "INSERT INTO `accounts_list`(`account_name`, `account_email`, `account_login`, `account_password`)
                VALUES ('{$_POST["create_name"]}','{$_POST["create_email"]}','{$_POST["create_login"]}','{$_POST["create_password"]}')");
            $sub = "Регистрация на сайте 'Волшебный мир Гарри Поттера'";
            $msg = "Ваш аккаунт был успешно зарегистрирован.\n\nИмя пользователя: {$_POST["create_name"]}\nE-mail: {$_POST["create_email"]}\nЛогин: {$_POST["create_login"]}\nПароль: {$_POST["create_password"]}";
            $rec = $_POST["create_email"];
            mail($rec,$sub,$msg);
            echo "<script>document.location.href=\"index.php\"</script>";
        }
		mysqli_free_result($query);
    }
}

// Регистрация или вход в аккаунт
if (isset($_POST["let_log_in"]))
    log_in_account();
elseif (isset($_POST["let_reg"]))
    reg_new_account();

// Загрузка нужной стартовой страницы
if (isset($_POST["reg"]) || isset($_POST["let_reg"]))
    view_page("reg.html");
else
    view_page("log_in.html");
