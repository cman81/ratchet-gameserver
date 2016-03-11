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
    public $gold = 0;
    public $starter = '';
    public $specs = array();
    public $private = array( // other players cannot see this information
        'hand' => array(),
        'discards' => array(),
        'codex' => array(),
        'workers' => array(),
    );
    public $hidden = array( // hidden from other players including ourselves
        'deck' => array(),
    );

    public function __construct($id, $alias, $starter = FALSE, $specs = FALSE) {
        $this->id = $id;
        $this->alias = $alias;
        if (!$this->starter = $starter && $this->specs = $specs) {
            $team = array(
                array(
                    'starter' => 'red',
                    'specs' => array('anarchy', 'fire', 'blood'),
                ),
                array(
                    'starter' => 'white',
                    'specs' => array('discipline', 'ninjutsu', 'strength'),
                ),
                array(
                    'starter' => 'black',
                    'specs' => array('demonology', 'disease', 'necromancy'),
                ),
            );
            $pick = array_rand($team);
            $team = $team[$pick];
            $this->starter = $team['starter'];
            $this->specs = $team['specs'];
        }
    }

    // place in discards so that they will get shuffled on the draw
    public function build_starter_deck() {
        $cards = file(SERVERROOT . '/cards.csv');
        $pattern = '/^(' . $this->starter . '-\d{1}).*/';

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
    public function build_codex() {
        $cards = file(SERVERROOT . '/cards.csv');
        $patterns = array();
        foreach ($this->specs as $value) {
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

    /**
     * Draw a card from the draw deck. If the draw deck is empty, shuffle the discard deck. This becomes your new draw deck
     */
    function draw_card() {
        if (!count($this->hidden['deck'])) {
            shuffle($this->private['discards']);
            $this->hidden['deck'] = $this->private['discards']; // makes a copy
            $this->private['discards'] = array();
        }
        $this->private['hand'][] = array_pop($this->hidden['deck']);
    }

    /**
     * Generic card mover (from one zone to another)
     */
    function move_card(&$from_deck, $from_idx, &$to_deck) {
        $to_deck = array_merge($to_deck, array_splice($from_deck, $from_idx, 1));
    }
}
