<?

// Проверка входа
if (!isset($_SESSION["curr_account"]))
    die("Ошибка доступа!");

include "index.html";