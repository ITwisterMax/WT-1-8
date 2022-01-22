<?
// Начало сессии
session_start();
include "db.php";

// Получение списка результатов поиска
function get_content($link, $search)
{
    $result = "";
    if (empty($search)) return "<ul><div class=\"error\">По вашему запросу ничего не найдено. Проверьте введенный запрос.</div></ul>";
    $query = mysqli_query($link, "SELECT page_name, page_content FROM `pages_list` WHERE page_content LIKE '%$search%'");
    $i = 1;
    while ($file = mysqli_fetch_assoc($query))
    {
        if (($file["page_name"] == "search.html") or (preg_match("/.\.css/", $file["page_name"]) === 1)) continue;
        $info = strip_tags($file["page_content"]);
        $position = stripos($info, $search);
        $content = mb_substr($info, $position - 5, $position + strlen($search), 'UTF-8');
        if (empty(trim($content))) continue;
        $result .= "<li><a href=\"" . $file["page_name"] . "\">" . $i . ") " . $file["page_name"] . ": ..." . $content . "...</a></li>";
        $i++;
    }
    mysqli_free_result($query);
    if (empty(trim($result))) return "<ul><div class=\"error\">По вашему запросу ничего не найдено. Проверьте введенный запрос.</div></ul>";
    $_SESSION["search"][] = $search;
    return "<ul>" . $result . "</ul>";
}

// отображение списка результатов поиска
$link = get_connection() or die("Connection error!");
$search = strip_tags(trim($_POST["search_info"]));
$file = file_get_contents("search.html");
$file = str_replace("{LIST}", get_content($link, $search), $file);
mysqli_close($link);
echo $file;
