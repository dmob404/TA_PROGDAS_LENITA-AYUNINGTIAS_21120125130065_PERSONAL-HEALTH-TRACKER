<?php
// load data
$data = json_decode(file_get_contents("data.json"), true);

$drink = $_POST["drink"];
$steps = $_POST["steps"];
$mood = $_POST["mood"];

// simpan input
$data[] = [
    "drink" => $drink,
    "steps" => $steps,
    "mood"  => $mood
];

file_put_contents("data.json", json_encode($data, JSON_PRETTY_PRINT));

// cek apakah user mencapai target
$notif = "";
if ($drink >= 2000 && $steps >= 6500) {
    $notif = "?notif=ok";
}

header("Location: index.php$notif");
exit;
