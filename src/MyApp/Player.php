<?php
/**
 * Created by PhpStorm.
 * User: cmanalan
 * Date: 3/9/2016
 * Time: 11:58 PM
 */

namespace MyApp;


class Player {
    public $id;
    public $alias;
    public $score = 0;
    public $workers = 0;
    public $private = array();
    public $hidden = array();

    public function __construct($id, $alias) {
        $this->id = $id;
        $this->alias = $alias;
    }

    // place in discards so that they will get shuffled on the draw
    public function build_starter_deck($color) {
        $cards = file(SERVERROOT . '/cards.csv');
        $pattern = '/^(' . $color . '-\d{1}).*/';

        $this->private['discards'] = array();
        foreach ($cards as $value) {
            if (preg_match($pattern, $value)) {
                $this->private['discards'][] = trim($value);
            }
        }
    }

    /**
     * Find matches and place 2 of each matching card in the codex.
     */
    public function build_codex($specs) {
        $cards = file(SERVERROOT . '/cards.csv');
        $patterns = array();
        foreach ($specs as $value) {
            $patterns[] = '/^(' . $value . '-\d{1,2}).*/';
        }

        $this->private['codex'] = array();
        foreach ($cards as $value) {
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $value)) {
                    for ($i = 0; $i < 2; $i++) {
                        $this->private['codex'][] = trim($value);
                    }
                    break; // proceed to next card
                }
            }
        }
    }
}