<?php

// Queue untuk Productive Task Queue
class Queue {
    private $items = [];

    public function __construct(array $initial = []) {
        $this->items = $initial;
    }

    public function enqueue($item) {
        array_push($this->items, $item);
    }

    public function dequeue() {
        if ($this->isEmpty()) return null;
        return array_shift($this->items);
    }

    public function peek() {
        return $this->isEmpty() ? null : $this->items[0];
    }

    public function isEmpty() {
        return count($this->items) === 0;
    }

    public function toArray() {
        return $this->items;
    }

    public function clear() {
        $this->items = [];
    }
}

// Tracker utama
class Tracker
{
    private string $username;
    private int $air;
    private int $steps;
    private string $mood;
    private array $stack;
    private array $log;

    private string $userDir;
    private string $logFile;

    public function __construct(string $username)
    {
        $this->username = $username;

        // pastikan session sudah dimulai di index.php
        if (!isset($_SESSION['air']))   $_SESSION['air'] = 0;
        if (!isset($_SESSION['steps'])) $_SESSION['steps'] = 0;
        if (!isset($_SESSION['mood']))  $_SESSION['mood'] = "-";
        if (!isset($_SESSION['stack'])) $_SESSION['stack'] = [];

        $this->air   = (int) $_SESSION['air'];
        $this->steps = (int) $_SESSION['steps'];
        $this->mood  = (string) $_SESSION['mood'];
        $this->stack = $_SESSION['stack'];

        // user folder & log file
        $this->userDir = "users/{$this->username}";
        if (!is_dir($this->userDir)) {
            mkdir($this->userDir, 0777, true);
        }

        $this->logFile = "{$this->userDir}/log.json";
        if (!file_exists($this->logFile)) {
            file_put_contents($this->logFile, json_encode([], JSON_PRETTY_PRINT));
        }

        $log = json_decode(file_get_contents($this->logFile), true);
        $this->log = is_array($log) ? $log : [];
    }

    // ---------- PUBLIC API ----------

    public function add(int $addAir, int $addSteps, ?string $newMood): void
    {
        $this->pushSnapshot();

        $drink = new DrinkHabit();
        $drink->add($this->air);
        $drink->add($addAir);
        $this->air = max(0, $drink->get());

        $steps = new StepHabit();
        $steps->add($this->steps);
        $steps->add($addSteps);
        $this->steps = max(0, $steps->get());

        if ($newMood !== null && $newMood !== "-") {
            $moodHabit = new MoodHabit();
            $moodHabit->add($newMood);
            $this->mood = $moodHabit->get();
        } elseif ($this->mood === "") {
            $this->mood = "-";
        }

        $this->logToday();
        $this->persistSession();
    }

    public function resetToday(): void
    {
        $this->pushSnapshot();

        $this->air   = 0;
        $this->steps = 0;
        $this->mood  = "-";

        $this->logToday();
        $this->persistSession();
    }

    public function clearHistory(): void
    {
        $this->pushSnapshot();
        $this->log = [];
        $this->saveLog();
    }

    public function undo(): void
    {
        if (empty($this->stack)) return;

        $last = array_pop($this->stack);

        $this->air   = (int) ($last['air'] ?? 0);
        $this->steps = (int) ($last['steps'] ?? 0);
        $this->mood  = (string) ($last['mood'] ?? "-");

        $_SESSION['stack'] = $this->stack;

        $this->logToday();
        $this->persistSession();
    }

    // getters
    public function getAir(): int     { return $this->air; }
    public function getSteps(): int   { return $this->steps; }
    public function getMood(): string { return $this->mood; }

    public function getAirPct(): float {
        return min(100, ($this->air / 2000) * 100);
    }

    public function getStepPct(): float {
        return min(100, ($this->steps / 6500) * 100);
    }

    public function getLog(): array {
        return $this->log;
    }

    public function getStackSize(): int {
        return count($this->stack);
    }

    public function getBadges(): array {
        $unlockHydration = ($this->air >= 2000);
        $unlockWalker    = ($this->steps >= 6500);
        $unlockPerfect   = ($this->air >= 2000 && $this->steps >= 6500 && $this->mood !== "-");
        $unlockWeekly    = $this->checkWeekly();

        return [
            'unlockHydration' => $unlockHydration,
            'unlockWalker'    => $unlockWalker,
            'unlockPerfect'   => $unlockPerfect,
            'unlockWeekly'    => $unlockWeekly,
        ];
    }

    // ---------- INTERNAL ----------

    private function pushSnapshot(): void
    {
        $snap = [
            'air'   => $this->air,
            'steps' => $this->steps,
            'mood'  => $this->mood,
        ];

        $this->stack[] = $snap;
        if (count($this->stack) > 50) {
            array_shift($this->stack);
        }

        $_SESSION['stack'] = $this->stack;
    }

    private function persistSession(): void
    {
        $_SESSION['air']   = $this->air;
        $_SESSION['steps'] = $this->steps;
        $_SESSION['mood']  = $this->mood;
    }

    private function saveLog(): void
    {
        file_put_contents($this->logFile, json_encode($this->log, JSON_PRETTY_PRINT));
    }

    private function logToday(): void
    {
        $today = date('Y-m-d');
        $idxFound = null;

        foreach ($this->log as $idx => $entry) {
            if ($entry['date'] === $today) {
                $idxFound = $idx;
                break;
            }
        }

        $newEntry = [
            'date'  => $today,
            'air'   => $this->air,
            'steps' => $this->steps,
            'mood'  => $this->mood,
        ];

        if ($idxFound !== null) {
            $this->log[$idxFound] = $newEntry;
        } else {
            $this->log[] = $newEntry;
        }

        $this->saveLog();
    }

    private function checkWeekly(): bool
    {
        if (empty($this->log)) return false;

        $dates = [];
        foreach ($this->log as $entry) {
            $dates[$entry['date']] = $entry;
        }

        $today = new DateTime();
        for ($i = 0; $i < 7; $i++) {
            $d = clone $today;
            $d->modify("-{$i} day");
            $k = $d->format('Y-m-d');

            if (!isset($dates[$k])) return false;
            if ($dates[$k]['air'] < 2000 || $dates[$k]['steps'] < 6500) return false;
        }

        return true;
    }
}
