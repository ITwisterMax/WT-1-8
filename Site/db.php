<?

// Вход в базу данных
function get_connection()
{
    $IP = "";
    $login = "";
    $password = "";
    $database = "";
    return mysqli_connect($IP, $login, $password, $database);
}
