<?php

abstract class HabitBase {
    protected $value = 0;
    abstract public function add($v);
    public function get() { return $this->value; }
}

class DrinkHabit extends HabitBase {
    public function add($v) { $this->value += $v; }
}

class StepHabit extends HabitBase {
    public function add($v) { $this->value += $v; }
}

class MoodHabit extends HabitBase {
    public function add($v) {
        $this->value = $v;
    }
}
