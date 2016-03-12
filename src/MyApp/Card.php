<?php
/**
 * Created by PhpStorm.
 * User: cmanalan
 * Date: 3/12/2016
 * Time: 1:48 AM
 */

namespace MyApp;


class Card {
    public $id;
    public $img;
    public $tags = array();

    public function __construct($img) {
        $this->img = trim($img);
        $this->id = substr($this->img, 0, -4);
    }
}

class HeroCard extends Card {
    public $status = 'waiting'; // waiting, active, or recovering
    public $level = 0;

    public function activate() {
        if ($this->status == 'waiting') {
            $this->status = 'active';
            $this->level = 1;
        }
    }
}
