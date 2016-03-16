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
    public $message_buffer = array();

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
                $this->message_buffer[] = $value->alias . ' gained ' . $amt . ' gold.';
                break;
            }
        }
    }
    function action_spend_gold($from, $settings) {
        $amt = $settings['amount'];
        foreach ($this->players as $value) {
            if ($value->id == $from) {
                $value->gold -= $amt;
                $this->message_buffer[] = $value->alias . ' spent ' . $amt . ' gold.';
                break;
            }
        }
    }
    function action_recruit_worker($from, $settings) {
        $card_idx = intval($settings['card_index']);
        foreach ($this->players as $value) { /* @var $value Player */
            if ($value->id == $from) {
                if (locate_card('green-3-tech0-rich-earth', $value->battlefield)) {
                    $this->message_buffer[] = $value->alias . ' has Rich Earth, so workers are free!';
                } else {
                    $value->gold--;
                }
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
                    $cost = $value->private['hand'][$card_idx]->cost;
                    $id = $value->private['hand'][$card_idx]->id;
                    $value->move_card($value->private['hand'], $card_idx, $value->battlefield);
                    if ($id == 'green-2-tech0-young-treant') {
                        $value->draw_card();
                        $this->message_buffer[] = $value->alias . ' draws a card since ' . $id . ' arrived!';
                    }
                } elseif ($settings['selected_deck'] == 'heroes') {
                    $cost = $value->heroes[$card_idx]->cost;
                    $id = $value->heroes[$card_idx]->id;
                    $value->heroes[$card_idx]->activate();
                    $value->move_card($value->heroes, $card_idx, $value->battlefield);
                } else {
                    return;
                }
                $value->gold -= $cost;
                $this->message_buffer[] = $value->alias . ' spent ' . $cost . ' gold and deployed ' . $id . ' to the battlefield.';
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
    function action_end_turn($from, $settings) {
        foreach ($this->players as $key => $value) { /* @var $value Player */
            if ($value->id == $from) {
                $this->whos_turn = ++$key % count($this->players);
                $this->message_buffer[] = $value->alias . ' ended their turn.';
            }
        }
    }
    function action_patrol($from, $settings) {
        $card_idx = intval($settings['card_index']);
        foreach ($this->players as $key => $value) { /* @var $value Player */
            if ($value->id == $from) {
                $value->battlefield[$card_idx]->patrol = $settings['patrol'];
                $this->message_buffer[] = $value->alias . ' ended their turn.';
            }
        }
    }
    function action_discard($from, $settings) {
        $card_idx = intval($settings['card_index']);
        foreach ($this->players as $key => $value) { /* @var $value Player */
            if ($settings['selected_deck'] == 'hand') {
                $id = $value->private['hand'][$card_idx]->id;
                $value->move_card($value->private['hand'], $card_idx, $value->private['discards']);
            } elseif ($settings['selected_deck'] == 'battlefield') {
                $id = $value->battlefield[$card_idx]->id;
                $value->battlefield[$card_idx]->damage = 0;
                $value->move_card($value->battlefield, $card_idx, $value->private['discards']);
            } else {
                return;
            }
            $this->message_buffer[] = $value->alias . ' discarded ' . $id . '.';
            break;
        }
    }
    function action_draw($from, $settings = array()) {
        foreach ($this->players as $value) { /* @var $value Player */
            if ($value->id == $from) {
                $value->draw_card();

                $this->message_buffer[] = $value->alias . ' drew a card.';
                break;
            }
        }
    }
    function action_add_damage($from, $settings) {
        $amt = $settings['amount'];
        $selected_player = $settings['selected_player'];
        $card_idx = intval($settings['card_index']);
        $this->players[$selected_player]->battlefield[$card_idx]->damage += $amt;
        $this->message_buffer[] = $this->players[$selected_player]->battlefield[$card_idx]->id . ' took ' . $amt . ' damage.';
    }
    function action_remove_damage($from, $settings) {
        $amt = $settings['amount'];
        $selected_player = $settings['selected_player'];
        $card_idx = intval($settings['card_index']);
        $this->players[$selected_player]->battlefield[$card_idx]->damage -= $amt;
        $this->message_buffer[] = $this->players[$selected_player]->battlefield[$card_idx]->id . ' healed from ' . $amt . ' damage.';
    }
}
