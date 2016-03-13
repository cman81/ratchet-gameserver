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
    public $deck_count = 0;
    public $starter = '';
    public $specs = array();
    public $heroes = array();
    public $buildings = array(
        'base' => array(
            'status' => 'built',
            'hp' => 20,
        ),
        'tech1' => array(
            'required_gold' => 1,
            'required_workers' => 6,
            'status' => 'inactive',
            'hp' => 5
        ),
        'tech2' => array(
            'required_gold' => 4,
            'required_workers' => 8,
            'status' => 'inactive',
            'hp' => 5
        ),
        'tech3' => array(
            'required_gold' => 5,
            'required_workers' => 10,
            'status' => 'inactive',
            'hp' => 5
        ),
        'addon' => array()
    );
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
        $teams = array(
            'mono_red' => array(
                'starter' => 'red',
                'specs' => array('anarchy', 'fire', 'blood'),
            ),
            'mono_white' => array(
                'starter' => 'white',
                'specs' => array('discipline', 'ninjutsu', 'strength'),
            ),
            'mono_black' => array(
                'starter' => 'black',
                'specs' => array('demonology', 'disease', 'necromancy'),
            ),
            'mono_green' => array(
                'starter' => 'green',
                'specs' => array('balance', 'feral', 'growth'),
            ),
            'mono_purple' => array(
                'starter' => 'purple',
                'specs' => array('past', 'present', 'future'),
            ),
            'mono_blue' => array(
                'starter' => 'blue',
                'specs' => array('law', 'peace', 'truth'),
            ),
        );
        if (!$starter) { // random pre-built team
            $pick = array_rand($teams);
            $team = $teams[$pick];
            $this->starter = $team['starter'];
            $this->specs = $team['specs'];
        } elseif (!$specs) { // pre-built-team
            $this->starter = $teams[$starter]['starter'];
            $this->specs = $teams[$starter]['specs'];
        } else { // custom team
            $this->starter = $starter;
            $this->specs = $specs;
        }
    }

    // place in discards so that they will get shuffled on the draw
    public function build_starter_deck() {
        $cards = file(SERVERROOT . '/cards.csv');
        $pattern = '/^(' . $this->starter . '-\d{1}).*/';

        $this->private['discards'] = array();
        foreach ($cards as $value) {
            if (preg_match($pattern, $value)) {
                $this->private['discards'][] = new Card($value);
            }
        }
    }

    /**
     * Find matches and place 2 of each matching card in the codex.
     */
    public function build_codex() {
        $cards = file(SERVERROOT . '/cards.csv');
        $patterns = array();
        $hero_begins_with = array();
        foreach ($this->specs as $value) {
            $patterns[] = '/^(' . $value . '-\d{1,2}).*/';
            $hero_begins_with[] = $value . '-hero';
        }

        $this->private['codex'] = array();
        foreach ($cards as $value) {
            $hero_found = FALSE;
            // add to our heroes?
            foreach ($hero_begins_with as $v2) {
                if (substr($value, 0, strlen($v2)) == $v2) {
                    $hero_found = TRUE;
                    $this->heroes[] = new HeroCard($value);
                    break; // proceed to next card
                }
            }

            if (!$hero_found) {
                // add to our codex?
                foreach ($patterns as $pattern) {
                    if (preg_match($pattern, $value)) {
                        for ($i = 0; $i < 2; $i++) {
                            $this->private['codex'][] = new Card($value);
                        }
                        break; // proceed to next card
                    }
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
