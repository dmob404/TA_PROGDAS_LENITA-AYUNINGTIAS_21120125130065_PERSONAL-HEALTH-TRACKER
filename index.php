<?php
session_start();

if (!isset($_SESSION["user"])) {
    header("Location: login.php");
    exit;
}

$username = $_SESSION["user"];

// theme
if (!isset($_SESSION['theme'])) {
    $_SESSION['theme'] = 'neon';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_theme'])) {
    $_SESSION['theme'] = ($_SESSION['theme'] === 'soft') ? 'neon' : 'soft';
    header("Location: index.php");
    exit;
}

$theme = $_SESSION['theme'];
$bodyClass = $theme === 'soft' ? 'dark theme-soft' : 'dark theme-neon';

// load classes
require_once __DIR__ . "/classes/HabitClasses.php";
require_once __DIR__ . "/classes/Tracker.php";

// helper mood text
function moodDescription($m) {
    switch ($m) {
        case "😊": return "Bahagia & positif";
        case "😐": return "Netral, biasa saja";
        case "😔": return "Sedikit lelah / sedih";
        default:   return "-";
    }
}

// init tracker
$tracker = new Tracker($username);

// init queue
$userDir = "users/$username";
if (!is_dir($userDir)) {
    mkdir($userDir, 0777, true);
}
$queueFile = "$userDir/queue.json";
$queueArr = file_exists($queueFile) ? json_decode(file_get_contents($queueFile), true) : [];
if (!is_array($queueArr)) $queueArr = [];
$queueObj = new Queue($queueArr);

function saveQueue($username, Queue $q) {
    $file = "users/$username/queue.json";
    file_put_contents($file, json_encode($q->toArray(), JSON_PRETTY_PRINT));
}

$processedTask = null;
$queueError    = '';

// handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // tracker actions
    if (isset($_POST['undo'])) {
        $tracker->undo();
    }

    if (isset($_POST['tambah'])) {
        $nilai_air   = (int) ($_POST['nilai_air'] ?? 0);
        $nilai_steps = (int) ($_POST['nilai_steps'] ?? 0);
        $nilai_mood  = isset($_POST['nilai_mood']) ? trim($_POST['nilai_mood']) : null;
        $tracker->add($nilai_air, $nilai_steps, $nilai_mood);
    }

    if (isset($_POST['reset'])) {
        $tracker->resetToday();
    }

    if (isset($_POST['clear_history'])) {
        $tracker->clearHistory();
    }

    // queue actions
    if (isset($_POST['clear_queue'])) {
        $queueObj->clear();
        saveQueue($username, $queueObj);
    }

    if (isset($_POST['add_task'])) {
        $text = trim($_POST['task_text'] ?? '');
        if ($text !== '') {
            if (strlen($text) > 120) {
                $queueError = 'Task terlalu panjang, maks 120 karakter.';
            } else {
                $taskClean = preg_replace('/\s+/', ' ', $text);
                $queueObj->enqueue(trim($taskClean));
                saveQueue($username, $queueObj);
            }
        }
    }

    if (isset($_POST['process_next'])) {
        if (!$queueObj->isEmpty()) {
            $next = $queueObj->dequeue();
            saveQueue($username, $queueObj);

            $summary = "Status sekarang — Air: {$tracker->getAir()} ml/2000, Langkah: {$tracker->getSteps()}/6500, Mood: " . moodDescription($tracker->getMood()) . ".";
            $processedTask = [
                'task'   => $next,
                'result' => $summary
            ];
        }
    }
}

// data view
$air    = $tracker->getAir();
$steps  = $tracker->getSteps();
$mood   = $tracker->getMood();
$airPct = $tracker->getAirPct();
$stepPct= $tracker->getStepPct();
$log    = $tracker->getLog();
$stackSize = $tracker->getStackSize();

$badges = $tracker->getBadges();
$unlockHydration = $badges['unlockHydration'];
$unlockWalker    = $badges['unlockWalker'];
$unlockPerfect   = $badges['unlockPerfect'];
$unlockWeekly    = $badges['unlockWeekly'];

// tips
$airIcon  = "💧";
$stepIcon = "👣";
$moodIcon = "😊";

$baseTips = [
    "Minum air sedikit-sedikit tapi sering, jangan nunggu haus dulu baru minum.",
    "Coba jalan keliling rumah atau kos 5–10 menit untuk menyegarkan badan.",
    "Tarik napas dalam 3 detik, tahan 3 detik, hembuskan 3 detik. Ulangi beberapa kali.",
    "Kalau lagi banyak duduk, selingi dengan berdiri dan stretching 1–2 menit.",
    "Tidur cukup penting banget buat recovery tubuh dan mood kamu besok.",
    "Kurangi scroll HP sambil rebahan lama, ganti dengan jalan ringan atau peregangan.",
    "Jangan lupa makan teratur, tubuh butuh energi biar bisa bergerak lebih aktif.",
    "Gerakan kecil tapi konsisten tiap hari lebih baik dari niat besar tapi tidak jalan."
];

$contextTips = [];

if ($air < 1000) {
    $contextTips[] = "Asupan air kamu hari ini masih kurang, coba tambah 1–2 gelas lagi ya.";
}
if ($steps < 3000) {
    $contextTips[] = "Langkah kamu masih sedikit, coba jalan kecil setelah ini minimal 5–10 menit.";
}
if ($mood === "😔") {
    $contextTips[] = "Mood lagi kurang oke, coba tarik napas dalam dan lakukan hal kecil yang kamu suka.";
}
if ($air >= 2000 && $steps >= 6500) {
    $contextTips[] = "Keren! Target air dan langkah sudah tercapai, pertahankan konsistensinya ya. 🎉";
}

$tipPool = !empty($contextTips) ? $contextTips : $baseTips;
$tipOfTheDay = $tipPool[array_rand($tipPool)];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Personal Health Tracker</title>
    <link rel="stylesheet" href="style.css" />
</head>
<body class="<?php echo $bodyClass; ?>">

<div class="container">

    <div class="main-panel">

        <div style="display:flex; justify-content:space-between; align-items:center; gap:16px; flex-wrap:wrap;">
            <div>
                <h1>Personal Health Tracker</h1>
                <p style="margin:0; font-size:13px; opacity:.8;">
                    Halo, <?= htmlspecialchars($username) ?>! Pantau air, langkah, dan mood kamu setiap hari.
                </p>
            </div>
            <div style="display:flex; gap:8px; align-items:center;">
                <form method="POST">
                    <button type="submit" name="toggle_theme" class="btn-secondary">
                        <?= ($theme === 'soft') ? 'Switch to Dark Mode' : 'Switch to Light Mode' ?>
                    </button>
                </form>

                <form method="POST" action="logout.php">
                    <button class="btn-reset" style="width:auto;">Logout</button>
                </form>
            </div>
        </div>

        <details class="tools-box">
            <summary class="tools-header">⚙ Undo & Info Snapshot</summary>
            <div class="tools-content">
                <form method="POST" style="margin-bottom:10px;">
                    <button type="submit" name="undo" class="btn-reset" style="width:160px;">Undo Last</button>
                    <span style="margin-left:10px; opacity:.85;">Stack size: <?= $stackSize ?></span>
                </form>
                <p style="margin-top:0; font-size:13px;">
                    Fitur <b>Undo Last</b> menyimpan snapshot tiap kali kamu update / reset data.
                    Kalau salah input, kamu bisa kembali ke kondisi sebelumnya.
                </p>
            </div>
        </details>

        <div class="main-layout">

            <div>
                <form method="POST">
                    <div class="form-row">
                        <div style="flex:2;">
                            <label>Tambah Air Minum (ml):</label><br />
                            <input type="number" name="nilai_air" placeholder="contoh: 250" />
                        </div>
                        <div style="flex:2;">
                            <label>Tambah Langkah:</label><br />
                            <input type="number" name="nilai_steps" placeholder="contoh: 1000" />
                        </div>
                        <div style="flex:1;">
                            <label>Mood Hari Ini:</label><br />
                            <select name="nilai_mood">
                                <option value="-">-</option>
                                <option value="😊" <?= ($mood === "😊") ? 'selected' : '' ?>>😊 Senang</option>
                                <option value="😐" <?= ($mood === "😐") ? 'selected' : '' ?>>😐 Netral</option>
                                <option value="😔" <?= ($mood === "😔") ? 'selected' : '' ?>>😔 Kurang Oke</option>
                            </select>
                        </div>
                    </div>

                    <div class="row-small">
                        <button type="submit" name="tambah" class="btn-primary">Update Hari Ini</button>
                        <button type="submit" name="reset" class="btn-reset">Reset Hari Ini</button>
                        <button type="submit" name="clear_history" class="btn-reset">Clear Riwayat</button>
                    </div>
                </form>

                <div class="data-box">
                    <p>Air Minum: <b><?= $airIcon ?> <?= $air ?> ml</b> (Target 2000 ml)</p>
                    <p>Langkah: <b><?= $stepIcon ?> <?= $steps ?> langkah</b> (Target 6500 langkah)</p>
                    <p>Mood: <b><?= $moodIcon ?> <?= htmlspecialchars($mood) ?> (<?= moodDescription($mood) ?>)</b></p>
                </div>

                <div class="data-box" style="margin-top:10px;">
                    <p><b>Ringkasan:</b></p>
                    <ul class="daily-goals">
                        <li>Target air: <b><?= ($air >= 2000) ? "Tercapai 🎉" : "Belum tercapai" ?></b> (<?= $air ?>/2000 ml)</li>
                        <li>Target langkah: <b><?= ($steps >= 6500) ? "Tercapai 💪" : "Belum tercapai" ?></b> (<?= $steps ?>/6500 langkah)</li>
                        <li>Mood hari ini: <b><?= moodDescription($mood) ?></b></li>
                    </ul>
                </div>
            </div>

            <div>
                <div class="progress">
                    <p>
                        <span><?= $airIcon ?> Progress Air</span>
                        <span><?= round($airPct, 1) ?>%</span>
                    </p>
                    <div class="bar">
                        <div class="fill fill-air" style="--val:<?= $airPct ?>%;"></div>
                    </div>

                    <p>
                        <span><?= $stepIcon ?> Progress Langkah</span>
                        <span><?= round($stepPct, 1) ?>%</span>
                    </p>
                    <div class="bar">
                        <div class="fill fill-step" style="--val:<?= $stepPct ?>%;"></div>
                    </div>
                </div>

                <div style="margin-top:14px;">
                    <h3>Catatan / Refleksi</h3>
                    <textarea class="note-box" placeholder="Tulis insight singkat untuk hari ini (tidak tersimpan)."></textarea>
                </div>

                <details class="tools-box" open style="margin-top:14px;">
                    <summary class="tools-header">⚙ Productive Task Queue</summary>
                    <div class="tools-content">

                        <?php if ($queueError): ?>
                            <div style="margin-bottom:10px; padding:8px; background:rgba(255,0,0,0.16); border-radius:8px;">
                                <?= htmlspecialchars($queueError) ?>
                            </div>
                        <?php endif; ?>

                        <strong style="font-size:15px;">Task Queue</strong>

                        <form method="POST" style="display:flex; gap:8px; margin-top:10px;">
                            <input type="text" name="task_text" placeholder="Tambah tugas..." style="flex:1; padding:8px 10px; border-radius:10px; border:1px solid rgba(255,255,255,0.14);" />
                            <button type="submit" name="add_task" class="btn-primary" style="padding:10px 14px;">Add</button>
                        </form>

                        <form method="POST" style="display:flex; gap:8px; margin-top:12px;">
                            <button type="submit" name="process_next" class="btn-primary" style="padding:8px 12px;">Process Next</button>
                            <button type="submit" name="clear_queue" class="btn-reset" style="padding:8px 12px;">Clear Queue</button>
                        </form>

                        <ul style="margin-top:14px; line-height:1.45; padding-left:18px;">
                            <?php foreach ($queueObj->toArray() as $i => $qit): ?>
                                <li style="margin-bottom:4px; font-size:13px;">
                                    <?= ($i+1) ?>. <?= htmlspecialchars($qit) ?>
                                </li>
                            <?php endforeach; ?>
                            <?php if ($queueObj->isEmpty()): ?>
                                <li style="font-size:13px; opacity:.75;">Belum ada task. Tambah 1 task kecil yang bisa kamu selesaikan hari ini.</li>
                            <?php endif; ?>
                        </ul>

                        <?php if (!empty($processedTask)): ?>
                            <div style="margin-top:14px; padding:10px; background:rgba(255,255,255,0.06); border-radius:10px; font-size:13px;">
                                <strong>Processed:</strong> <?= htmlspecialchars($processedTask['task']) ?><br />
                                <span style="opacity:.85;"><?= htmlspecialchars($processedTask['result']) ?></span>
                            </div>
                        <?php endif; ?>

                    </div>
                </details>
            </div>

        </div>

        <div class="bottom-layout">
            <h3>Riwayat Hari</h3>
            <?php if (!empty($log)): ?>
                <div class="history-wrapper">
                    <div class="history-scroll">
                        <table class="history-table">
                            <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Air</th>
                                <th>Langkah</th>
                                <th>Mood</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach (array_reverse($log) as $r): ?>
                                <tr>
                                    <td><?= $r['date'] ?></td>
                                    <td><?= $r['air'] ?></td>
                                    <td><?= $r['steps'] ?></td>
                                    <td><?= htmlspecialchars($r['mood']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php else: ?>
                <p style="font-size:13px; opacity:.8;">Belum ada riwayat. Update datamu hari ini ya!</p>
            <?php endif; ?>
        </div>

    </div>

    <div class="side-panel">
        <h3>Status & Badges</h3>

        <ul class="quick-stats">
            <li><span>Hydration</span> <?= $unlockHydration ? "✅ Cukup minum" : "❌ Kurang" ?></li>
            <li><span>Walker</span> <?= $unlockWalker ? "✅ Rajin jalan" : "❌ Gak cukup" ?></li>
            <li><span>Perfect Day</span> <?= $unlockPerfect ? "🌟 Yes" : "Belum" ?></li>
            <li><span>Weekly Streak</span> <?= $unlockWeekly ? "🔥 7 hari konsisten" : "Belum 7 hari" ?></li>
        </ul>

        <div class="badge-grid">
            <div class="badge <?= $unlockHydration ? 'unlocked' : 'locked' ?>">
                <div class="badge-icon">💧</div>
                <div>Hydration Master</div>
            </div>
            <div class="badge <?= $unlockWalker ? 'unlocked' : 'locked' ?>">
                <div class="badge-icon">👟</div>
                <div>Daily Walker</div>
            </div>
            <div class="badge <?= $unlockPerfect ? 'unlocked' : 'locked' ?>">
                <div class="badge-icon">🌟</div>
                <div>Perfect Day</div>
            </div>
            <div class="badge <?= $unlockWeekly ? 'unlocked' : 'locked' ?>">
                <div class="badge-icon">🔥</div>
                <div>Weekly Warrior</div>
            </div>
        </div>

        <div class="motivation-card">
            <h4>Motivasi Hari Ini</h4>
            <p><?= htmlspecialchars($tipOfTheDay) ?></p>
        </div>

        <div class="side-box">
            <h4>Tips</h4>
         <ul style="line-height:1.5; margin:0; padding-left:18px;">
                <li>Minum air sedikit-sedikit tapi sering.</li>
                <li>Jalan 10 menit setelah duduk lama.</li>
                <li>Catat target kecil tiap pagi.</li>
            </ul>
        </div>

    </div>

</div>

</body>
</html>
