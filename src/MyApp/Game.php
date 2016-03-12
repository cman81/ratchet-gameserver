<?php
/**
 * Created by PhpStorm.
 * User: cmanalan
 * Date: 3/12/2016
 * Time: 2:17 AM
 */

namespace MyApp;


class Game {
    public $players = array();
    public $is_started = FALSE;
    public $min_players = 2;
    public $max_players = 2;
    public $lastupdated;
    public $whos_turn = 0;
    public $table = array();

    public function __construct() {
        $this->lastupdated = time();
    }
}