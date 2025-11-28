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
        $this->value = $v; // mood overwrite (polymorphism)
    }
}

class TrackerProcessor {
    private $drink;
    private $steps;
    private $mood;

    public function __construct($drink, $steps, $mood) {
        $this->drink = $drink;
        $this->steps = $steps;
        $this->mood  = $mood;
    }

    public function process($addDrink, $addSteps, $newMood) {
        $drinkObj = new DrinkHabit();
        $stepsObj = new StepHabit();
        $moodObj  = new MoodHabit();

        $drinkObj->add($this->drink);
        $drinkObj->add($addDrink);

        $stepsObj->add($this->steps);
        $stepsObj->add($addSteps);

        $moodObj->add($newMood);

        return [
            "drink" => $drinkObj->get(),
            "steps" => $stepsObj->get(),
            "mood"  => $moodObj->get()
        ];
    }
}
