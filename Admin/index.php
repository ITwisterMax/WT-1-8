<?

session_start();
// Проверка входа
if (!isset($_SESSION["curr_account"]))
    die("Ошибка доступа!");
echo '<title>Панель администратора</title><link rel="stylesheet" href="style.css">';
include "db.php";

// начальные параметры
function initialize()
{
    // устанавливаем начальную директорию
    $_GET['start'] = '../Site';

    // устанавливаем текущую директорию
    if (!isset($_GET['dir']))
        $_GET['dir'] = $_GET['start'];
}

// добавление файла
function add_file()
{
    $file = $_GET['dir'] . '/' . $_FILES['userfile']['name'];
    
    if (move_uploaded_file($_FILES['userfile']['tmp_name'], $file)) 
        echo '<div class="successmessage">Файл был успешно загружен!</div><br />';
    else 
        echo '<div class="errormessage">Ошибка. Файл не был загружен!</div><br />';
}

// добавление директории
function add_dir()
{
    // проверка на корректность имени директории
    if (strpos($_POST['itemname'], '/') or trim($_POST['itemname']) == ''
    or file_exists($_GET['dir'] . '/' . trim($_POST['itemname'])) or !mkdir($_GET['dir'] . '/' . trim($_POST['itemname']))) 
        echo '<div class="errormessage">Ошибка. Некорректное имя директории!</div><br />';    
    else
        echo '<div class="successmessage">Директория была успешно добавлена</div><br />';
}

// рекурсивное удаление содержимого директории
function delete_dir($dir)
{
    $curr = glob($dir . '/*');
    if (count($curr) > 0)
        foreach ($curr as $item) 
            if (is_dir($item))
            {
                delete_dir($item);
                rmdir($item);
            }
            else
                unlink($item);
}

// удаление директории или файла
function delete_item()
{
    // проверка на корректность имени директории или файла
    if (strpos($_POST['itemname'], '/') or trim($_POST['itemname']) == ''
    or !file_exists($_GET['dir'] . '/' . trim($_POST['itemname'])))
        echo '<div class="errormessage">Ошибка. Файл или директория не были найдены!</div><br />';
    elseif (is_dir($_GET['dir'] . '/' . trim($_POST['itemname'])))
    {
        // удаление директории
        $dir = $_GET['dir'] . '/' . trim($_POST['itemname']);
        delete_dir($dir);
        rmdir($dir);
        echo '<div class="successmessage">Директория была успешно удалена!</div><br />';
    }
    else
    {
        // удаление файла
        $file = $_GET['dir'] . '/' . trim($_POST['itemname']);
        unlink($file);
        echo '<div class="successmessage">Файл был успешно удален!</div><br />';
    }
}

// загрузка в базу данных
function load_in()
{
	// открываем директорию
    $od = opendir($_GET['start']);
    $files = array();
    // читаем директорию
    while (false !== ($file = readdir($od)))
    {
        if ((is_file($_GET['start'] . '/' . $file)) && (preg_match("/.\.php/", $_GET['start'] . '/' . $file) == 0))
            $files[] = $file;
    }
    // закрываем директорию
    closedir($od);

    $link = get_connection() or die("Connection error!");
    // записываем в базу данных
    foreach ($files as $item)
    {
        $info = file_get_contents($_GET['start'] . '/' . $item);
        $query = mysqli_query($link, "INSERT INTO `pages_list`(`page_name`, `page_content`) VALUES (`$item`, `$info`)");
    }
    mysqli_close($link);
    echo '<div class="successmessage">Данные были успешно загружены в базу данных!</div><br />';
}

// получение названий файлов страниц
function get_pages($link)
{
    $query = mysqli_query($link, "SELECT page_name FROM `pages_list` WHERE 1");
    while ($file = mysqli_fetch_assoc($query))
        $result[] = $file["page_name"];
    mysqli_free_result($query);
    return $result;
}

// получение контента файлов страниц
function load_pages($link, $pages)
{
    for ($i = 0; $i < count($pages); $i++)
    {
        $query = mysqli_query($link, "SELECT page_content FROM `pages_list` WHERE page_name LIKE '%$pages[$i]%'");
        $result = mysqli_fetch_assoc($query);
        $file = fopen($_GET['start'] . '/' . $pages[$i], "w");
        fwrite($file, $result["page_content"]);
        fclose($file);
        mysqli_free_result($query);
    }
}

// загрузка из базы данных
function load_from()
{
    $link = get_connection() or die("Connection error!");
    $pages = get_pages($link);
    load_pages($link, $pages);
    mysqli_close($link);
    echo '<div class="successmessage">Данные были успешно выгружены из базы данных!</div><br />';
}

// отправка статистики на почту админа
function to_mail()
{
    $sub = "Статистика по сайту 'Волшебный мир Гарри Поттера'";
    $msg = "---------------------------------------------------------------------------\n";
    $msg .= "Статистика посещения сайта (день / количество человек):\n\n";
    $rec = $_SESSION["curr_email"];
    $link = get_connection() or die("Connection error!");
    $query = mysqli_query($link, "SELECT * FROM `stat_list`");
    while ($data = mysqli_fetch_assoc($query))
        $msg .= "{$data["day_count"]} : {$data["accounts_count"]} чел.\n";
    $msg .= "---------------------------------------------------------------------------\n";
    $msg .= "Статистика голосования (результат / количество человек):\n\n";
    $query = mysqli_query($link, "SELECT * FROM `vote_stat`");
    while ($data = mysqli_fetch_assoc($query))
        $msg .= "{$data["vote"]} : {$data["vote_count"]} чел.\n";
    $msg .= "---------------------------------------------------------------------------\n";
    mysqli_close($link);
    mail($rec, $sub, $msg);
    echo '<div class="successmessage">Статистика была успешно выслана на почту!</div><br />';
}

// массовая рассылка на почту
function mass_msg()
{
    if ($_POST['itemname'] === '')
        echo '<div class="errormessage">Ошибка. Сообщение пустое!</div><br />';
    else
    {
        $sub = "Важная информация по сайту 'Волшебный мир Гарри Поттера'";
        $msg = $_POST['itemname'];
        $link = get_connection() or die("Connection error!");
        $query = mysqli_query($link, "SELECT * FROM `accounts_list`");
        while ($data = mysqli_fetch_assoc($query))
            if ($data['account_email'] != $_SESSION['curr_email'])    
                mail($data['account_email'], $sub, $msg);
        echo '<div class="successmessage">Сообщения были успешно высланы на почты!</div><br />';
    }
}

// отображение содержимого директории
function show_list()
{
    // открываем директорию
    $od = opendir($_GET['dir']);
        // читаем директорию
        while (false !== ($file = readdir($od)))
        {
            // проверяем директория или файл
            if (is_dir($_GET['dir'] . '/' . $file) && $file!='.' && $file!='..')
                // создаем массив
                $dirs[] = $file;

            if (is_file($_GET['dir'] . '/' . $file))
                // создаем массив
                $files[] = $file;
        }
    // закрываем директорию
    closedir($od);

    // вывод названия директории и пути к нему
    echo '<div class="data"><h3>С возвращением, ' . $_SESSION['curr_account'] . '</h3><div class="left">';

    // возврат в предыдущую директорию
    if ($_GET['dir'] != $_GET['start'])
    {
        $tmp = strrpos($_GET['dir'], '/');
        $newdir = substr($_GET['dir'], 0, $tmp);
        echo '<img src="Images/Back.png" class="data"> <a href="?dir='.$newdir.'">Back</a>';
    }

    $total = '<form action="" method="post">';

    // вывод массива директорий
    if (isset($dirs))
    {
        sort($dirs, SORT_FLAG_CASE | SORT_NATURAL);
        foreach ($dirs as $k => $v)
            $total .= '<img src="Images/Folder.png" class="data"> <a href="?dir=' . $_GET['dir']
            . '/' . $dirs[$k] . '">' . $dirs[$k] . '</a><br />';
    }

    // вывод массива файлов
    if(isset($files))
    {
        sort($files, SORT_FLAG_CASE | SORT_NATURAL);
        foreach ($files as $k => $v)
            $total .= '<img src="Images/File.png" class="data"><a href="' . $_GET['dir']
            . '/' . $files[$k] . '" download>' . $files[$k] . '</a><br />';
    }

    // поле для ввода имени файла или директории
    $total .= '<br /><Label>Поле для ввода: </Label><input class="text" type="text" name="itemname"><br />';
    // кнопка для добавления директории
    $total .= '<input class="buttons" type="submit" name="adddir" value="Создать директорию">';
    // кнопка для удаления файла или директории 
    $total .= '<input class="buttons" type="submit" name="delete" value="Удалить файл/директорию">';
	// кнопка для загрузки в базу данных
    $total .= '<input class="buttons" type="submit" name="load_in_db" value="Загрузить в базу данных"><br />';
	// кнопка для выгрузки из базы данных
    $total .= '<input class="buttons" type="submit" name="load_from_db" value="Выгрузить из базы данных">';
    // кнопка для отправки статистики на почту админа
    $total .= '<input class="buttons" type="submit" name="to_mail" value="Отправить статистику">';
    // кнопка для рассылки
    $total .= '<input class="buttons" type="submit" name="mass_msg" value="Рассылка сообщения"></form>';
    // загрузка файла
    $total .= '<form enctype="multipart/form-data" method="POST">
    <input class="file" name="userfile" type="file"><input class="buttons" type="submit" name="addfile" value="Загрузить файл" /></form>';
    // выход из аккаунта
    $total .= '<form action="../Login/index.php"><input class="buttons" type="submit" value="Выйти из аккаунта"></form></div>';

    // количество посещений
    $total .= '<div class="right"><table><tr><th>Дата посещения</th><th>Количество человек</th>';
    $link = get_connection() or die("Connection error!");
    $query = mysqli_query($link, "SELECT * FROM `stat_list`");
    
    while ($data = mysqli_fetch_assoc($query))
        $total .= "<tr><td>{$data["day_count"]}</td><td>{$data["accounts_count"]} чел.</td></tr>";
    $total .= '</table>';

    // количество голосования
    $total .= '<table><tr><th>Результат</th><th>Количество человек</th>';
    $query = mysqli_query($link, "SELECT * FROM `vote_stat`");
    
    while ($data = mysqli_fetch_assoc($query))
        $total .= "<tr><td>{$data["vote"]}</td><td>{$data["vote_count"]} чел.</td></tr>";
    mysqli_close($link);
    $total .= '</table></div>';

    // вывод содержимого директории
    echo $total;
}

//------------------------------------

initialize();

if (isset($_POST['addfile']))
    add_file();
elseif (isset($_POST['adddir']))
    add_dir();
elseif (isset($_POST['delete']))
    delete_item();
elseif (isset($_POST['load_in_db']))
    load_in();
elseif (isset($_POST['load_from_db']))
    load_from();
elseif (isset($_POST['to_mail']))
    to_mail();
elseif (isset($_POST['mass_msg']))
    mass_msg();

show_list();

//------------------------------------
