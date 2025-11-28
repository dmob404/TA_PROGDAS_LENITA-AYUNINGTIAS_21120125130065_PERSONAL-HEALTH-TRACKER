<?php
session_start();

if (!isset($_SESSION["user"])) {
    header("Location: login.php");
    exit;
}

$username = $_SESSION["user"];

if (!isset($_SESSION['air']))   $_SESSION['air'] = 0;
if (!isset($_SESSION['steps'])) $_SESSION['steps'] = 0;
if (!isset($_SESSION['mood']))  $_SESSION['mood'] = "-";

require_once "classes/TrackerProcessor.php";

$air   = $_SESSION['air'];
$steps = $_SESSION['steps'];
$mood  = $_SESSION['mood'];

if (isset($_POST['tambah'])) {

    $nilai_air   = (int) ($_POST['nilai_air'] ?? 0);
    $nilai_steps = (int) ($_POST['nilai_steps'] ?? 0);
    $mood        = $_POST['mood'];

   
    $processor = new TrackerProcessor($air, $steps, $mood);
    $result = $processor->process($nilai_air, $nilai_steps, $mood);

    $_SESSION['air']   = $result["drink"];
    $_SESSION['steps'] = $result["steps"];
    $_SESSION['mood']  = $result["mood"];
}



if (isset($_POST['reset_hari'])) {
    $_SESSION['air'] = 0;
    $_SESSION['steps'] = 0;
    $_SESSION['mood'] = "-";
}

$today = date("Y-m-d");
$logFile = "users/$username/data.json";
$log = file_exists($logFile) ? json_decode(file_get_contents($logFile), true) : [];
if (!is_array($log)) $log = [];

if (isset($_POST['tambah'])) {
    $log[] = [
        "date" => $today,
        "air" => $_SESSION['air'],
        "steps" => $_SESSION['steps'],
        "mood" => $_SESSION['mood']
    ];
    file_put_contents($logFile, json_encode($log, JSON_PRETTY_PRINT));
}


if (isset($_POST['clear_history'])) {
    $log = [];
    file_put_contents($logFile, json_encode([], JSON_PRETTY_PRINT));
}


$air   = $_SESSION['air'];
$steps = $_SESSION['steps'];
$mood  = $_SESSION['mood'];


$airPct  = min(100, ($air / 2000) * 100);
$stepPct = min(100, ($steps / 6500) * 100);


$unlockHydration = ($air >= 2000);
$unlockWalker    = ($steps >= 6500);
$unlockPerfect   = ($air >= 2000 && $steps >= 6500 && $mood != "-");


$unlockWeekly = false;
if (count($log) >= 7) {
    $last7 = array_slice($log, -7);
    $valid = true;
    foreach ($last7 as $entry) {
        if ($entry["air"] == 0 && $entry["steps"] == 0) $valid = false;
    }
    if ($valid) $unlockWeekly = true;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Personal Health Tracker</title>
<link rel="stylesheet" href="style.css" />
</head>
<body class="dark">

<div class="container">
    <h1>Personal Health Tracker</h1>
<form method="POST" style="text-align:right;">
    <button formaction="logout.php" class="btn-reset" style="width:auto;">Logout</button>
</form>

   
    <form method="POST" class="form-row">
       <input type="number" name="nilai_air" placeholder="Air Minum (<?= $air ?>/2000 ml)" />
<input type="number" name="nilai_steps" placeholder="Langkah (<?= $steps ?>/6500)" />



        <select name="mood">
            <option <?= $mood=="Neutral 😐"?"selected":"" ?>>Neutral 😐</option>
            <option <?= $mood=="Happy 😄"?"selected":"" ?>>Happy 😄</option>
            <option <?= $mood=="Good 😊"?"selected":"" ?>>Good 😊</option>
            <option <?= $mood=="Stressed 😫"?"selected":"" ?>>Stressed 😫</option>
            <option <?= $mood=="Tired 😪"?"selected":"" ?>>Tired 😪</option>
        </select>

        <button type="submit" name="tambah" class="btn-primary">Tambah</button>

        <div class="row-small">
            <button type="submit" name="reset_hari" class="btn-reset">Reset Hari Ini</button>
            <button type="submit" name="clear_history" class="btn-reset">Clear Riwayat</button>
        </div>
    </form>

    
    <div class="data-box">
        <p>Air Minum: <b><?= $air ?></b> / 2000 ml</p>
        <p>Langkah: <b><?= $steps ?></b> / 6500 langkah</p>
        <p>Mood: <?= $mood ?></p>
    </div>

   
    <div class="progress">
        <p>Progress Air: <span><?= round($airPct) ?>%</span></p>
        <div class="bar"><div class="fill fill-air" style="--val: <?= $airPct ?>%;"></div></div>

        <p>Progress Langkah: <span><?= round($stepPct) ?>%</span></p>
        <div class="bar"><div class="fill fill-step" style="--val: <?= $stepPct ?>%;"></div></div>
    </div>

 
    <div class="badges">
        <h3>Badges</h3>
        <div class="badge-grid">
            <div class="badge <?= $unlockHydration ? "unlocked" : "locked" ?>">
                <div class="badge-icon">💧</div>Hydration King
            </div>
            <div class="badge <?= $unlockWalker ? "unlocked" : "locked" ?>">
                <div class="badge-icon">🚶</div>Fit Walker
            </div>
            <div class="badge <?= $unlockPerfect ? "unlocked" : "locked" ?>">
                <div class="badge-icon">🏆</div>Perfect Day
            </div>
            <div class="badge <?= $unlockWeekly ? "unlocked" : "locked" ?>">
                <div class="badge-icon">📅</div>Weekly Warrior
            </div>
        </div>
    </div>

    <h3 style="margin-top:28px;">Riwayat Hari</h3>
    <?php if (!empty($log)): ?>
    <table border="1" cellpadding="8" cellspacing="0" style="width:100%; border-collapse:collapse; margin-top:10px; font-size:14px;">
        <tr style="font-weight:bold; background:rgba(255,255,255,0.08);">
            <td>Tanggal</td>
            <td>Air (ml)</td>
            <td>Langkah</td>
            <td>Mood</td>
        </tr>
        <?php foreach (array_reverse($log) as $r): ?>
        <tr>
            <td><?= $r['date'] ?></td>
            <td><?= $r['air'] ?></td>
            <td><?= $r['steps'] ?></td>
            <td><?= $r['mood'] ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php else: ?>
        <p style="margin-top:20px; opacity:.55;">Belum ada riwayat</p>
    <?php endif; ?>

    <div class="chart-wrapper">
        <div class="css-chart">
            <div class="bar-group">
                <div class="bar bar-air" style="--h: <?= $airPct ?>%"><span>Air</span></div>
                <div class="bar bar-step" style="--h: <?= $stepPct ?>%"><span>Langkah</span></div>
            </div>
        </div>
    </div>

</div>
</body>
</html>
