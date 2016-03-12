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

    /**
     * Game actions
     */
    function action_gain_gold($from, $settings) {
        $amt = $settings['amount'];
        foreach ($this->players as $value) {
            if ($value->id == $from) {
                $value->gold += $amt;
                $this->message_buffer[] = $value->alias . ' gained 1 gold.';
                break;
            }
        }
    }
    function action_spend_gold($from, $settings) {
        $amt = $settings['amount'];
        foreach ($this->players as $value) {
            if ($value->id == $from) {
                $value->gold -= $amt;
                $this->message_buffer[] = $value->alias . ' spent 1 gold.';
                break;
            }
        }
    }
    function action_recruit_worker($from, $settings) {
        $card_idx = intval($settings['card_index']);
        foreach ($this->players as $value) { /* @var $value Player */
            if ($value->id == $from) {
                $value->gold--;
                $value->workers++;
                $value->move_card($value->private['hand'], $card_idx, $value->private['workers']);
                $this->message_buffer[] = $value->alias . ' recruited a worker.';
                break;
            }
        }
    }
    function action_discard_redraw($from, $settings = array()) {
        foreach ($this->players as $value) { /* @var $value Player */
            if ($value->id == $from) {
                $old_hand_count = count($value->private['hand']);
                $cards_to_draw = min($old_hand_count + 2, 5);

                // discard your entire hand
                $value->private['discards'] = array_merge($value->private['discards'], $value->private['hand']);
                $value->private['hand'] = array();

                // redraw
                for ($i = 0; $i < $cards_to_draw; $i++) {
                    $value->draw_card();
                }

                $this->message_buffer[] = $value->alias . ' discarded ' . $old_hand_count .  ' cards and drew ' . $cards_to_draw . ' cards.';
                break;
            }
        }
    }
    function action_deploy($from, $settings) {
        $card_idx = intval($settings['card_index']);
        foreach ($this->players as $value) { /* @var $value Player */
            if ($value->id == $from) {
                if ($settings['selected_deck'] == 'hand') {
                    $value->move_card($value->private['hand'], $card_idx, $this->table);
                } elseif ($settings['selected_deck'] == 'heroes') {
                    $value->heroes[$card_idx]->activate();
                    $value->move_card($value->heroes, $card_idx, $this->table);
                } else {
                    return;
                }
                $this->message_buffer[] = $value->alias . ' deployed to the table.';
                break;
            }
        }
    }
    function action_tech($from, $settings) {
        $card_idx = intval($settings['card_index']);
        foreach ($this->players as $value) { /* @var $value Player */
            if ($value->id == $from) {
                $value->move_card($value->private['codex'], $card_idx, $value->private['discards']);
                $this->message_buffer[] = $value->alias . ' took a card out of their codex.';
                break;
            }
        }
    }
}
