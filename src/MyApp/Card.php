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
    public $cost = 0;
    public $tags = array();

    public function __construct($img) {
        $this->img = trim($img);
        $this->id = substr($this->img, 0, -4);
        $pattern = '/\w*-(\d{1,2})-.*/';
        preg_match($pattern, $this->img, $matches);
        $this->cost = intval($matches[1]);
    }
}

class HeroCard extends Card {
    public $status = 'waiting'; // waiting, active, or recovering
    public $level = 0;

    public function __construct($img) {
        parent::__construct($img);
        $this->cost = 2;
    }
    public function activate() {
        if ($this->status == 'waiting') {
            $this->status = 'active';
            $this->level = 1;
        }
    }
}
