<?

include "db.php";

// Перезапись статистики
function refresh_stat($link, $new_vote)
{
    $query = mysqli_query($link, "UPDATE `vote_stat` SET vote_count=vote_count+1 WHERE id={$new_vote}");
}

// Перезапись статистики
$link = get_connection() or die("Connection error!");
$new_vote = ($_POST["vote"]);
refresh_stat($link, $new_vote);
mysqli_close($link);
include "index.html";
