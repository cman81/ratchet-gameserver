<?php
namespace MyApp;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class Chat implements MessageComponentInterface {
    protected $clients;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->aliases = array();
        $this->game = new Game();
        $this->message_buffer = array();
    }

    public function onOpen(ConnectionInterface $conn) {
        // Store the new connection to send messages to later
        $this->clients->attach($conn);

        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $json = json_decode($msg, TRUE);
        $is_handled = FALSE;
        if (isset($json['login'])) {
            $this->handle_login($json['login'], $from);
            $is_handled = TRUE;
        }
        if (isset($json['chat'])) {
            $this->handle_chat($json['chat'], $from, $msg);
            $is_handled = TRUE;
        }
        if (isset($json['game'])) {
            $this->handle_game($json['game'], $from);
            $is_handled = TRUE;
        }
        // default handler
        if (!$is_handled) {
            $numRecv = count($this->clients) - 1;
            echo sprintf('Connection %d sending message "%s" to %d other connection%s' . "\n"
                , $from->resourceId, $msg, $numRecv, $numRecv == 1 ? '' : 's');

            foreach ($this->clients as $client) {
                if ($from !== $client) {
                    // The sender is not the receiver, send to each client connected
                    $client->send($msg);
                }
            }
        }
    }

    public function onClose(ConnectionInterface $conn) {
        // The connection is closed, remove it, as we can no longer send it messages
        $this->clients->detach($conn);
        foreach ($this->clients as $client) {
            $client->send(json_encode(array(
                'logout' => $this->aliases[$conn->resourceId]
            )));
        }
        unset($this->aliases[$conn->resourceId]);
        echo "Connection {$conn->resourceId} has disconnected\n";

        // also, just quit the game for now
        $this->game = new Game();
        $this->send_gamestate();
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";

        $conn->close();
    }

    public function handle_login($login, $from) {
        if (is_invalid_login($login, $this->aliases)) {
            $from->send(json_encode(array(
                'loginError' => 'Disconnecting: invalid login',
            )));
            echo "Invalid login.\n";
            $from->close();
        } else {
            $this->aliases[$from->resourceId] = $login;
            echo "The following users are logged in: " . implode(', ', $this->aliases) . "\n";
            foreach ($this->clients as $client) {
                if ($from !== $client) {
                    // The sender is not the receiver, send to each client connected
                    $client->send(json_encode(array(
                        'login' => $login
                    )));
                }
            }
        }
    }

    public function handle_chat($json, $from, $msg) {
        $numRecv = count($this->clients) - 1;
        echo sprintf('Connection %d sending message "%s" to %d other connection%s' . "\n"
            , $from->resourceId, $msg, $numRecv, $numRecv == 1 ? '' : 's');

        foreach ($this->clients as $client) {
            if ($from !== $client) {
                // The sender is not the receiver, send to each client connected
                $client->send($msg);
            }
        }
    }

    public function handle_game($json, $from) {
        if ($json['op'] == 'join') {
            // has the game already started?
            if ($this->game->is_started) {
                $from->send(json_encode(array(
                    'gameError' => 'The game has already started!'
                )));
                echo $this->aliases[$from->resourceId] . " tried joining, but the game has already started\n";
                return;
            }
            // are we already playing?
            $alias = $this->aliases[$from->resourceId];
            foreach ($this->game->players as $key => $value) {
                if ($value->alias == $alias) {
                    $this->game->players[$key]->id = $from->resourceId;
                    $from->send(json_encode(array(
                        'gameError' => 'You are already playing!'
                    )));
                    echo $this->aliases[$from->resourceId] . " tried joining, but was already playing\n";
                    $this->send_gamestate();
                    return;
                }
            }

            // take a seat if possible
            if (count($this->game->players) == $this->game->max_players) {
                $from->send(json_encode(array(
                    'gameError' => 'No seats available'
                )));
                echo $this->aliases[$from->resourceId] . " tried joining, but was unable to\n";
                return;
            } else {
                $this->game->players[] = new Player(
                    $from->resourceId,
                    $this->aliases[$from->resourceId]
                );
                $this->game->lastupdated = time();
                echo $this->aliases[$from->resourceId] . " joined the game as Player " . count($this->game->players) . "\n";
            }
        }
        if ($json['op'] == 'start') {
            $this->game->is_started = TRUE;
            $this->game->table = array();

            // determine turn order
            shuffle($this->game->players);

            // setup initial workers
            $this->game->players[0]->workers = 4;
            $this->game->players[1]->workers = 5;

            // generate a starter deck and codex for this player
            foreach ($this->game->players as $key => $value) {
                $value->build_starter_deck();
                $value->build_codex();

                // deal out 5 cards to each player
                for ($i = 0; $i < 5; $i++) {
                    $value->draw_card();
                }
            }

            $this->message_buffer[] = $this->aliases[$from->resourceId] . ' started the game.';
        }
        if ($json['op'] == 'transaction') {
            echo "Received from " . $this->aliases[$from->resourceId] . ":\n\n" . json_encode($json) . "\n\n";
            foreach ($json['actions'] as $value) {
                if (method_exists($this, 'action_' . $value['action'])) {
                    $fnname = 'action_' . $value['action'];
                    $this->{$fnname}($from, $value);
                    $this->game->lastupdated = time();
                }
            }
        }
        $this->send_gamestate();
        $this->message_buffer = array();
    }

    /**
     * Send the current game state
     */
    function send_gamestate() {
        foreach ($this->clients as $client) {
            $msg = json_encode(array(
                'game' => apply_mask(clone $this->game, $client->resourceId),
                'messages' => $this->message_buffer,
            ));
            echo "Passing the following information to " . $this->aliases[$client->resourceId] . ":\n\n" . $msg . "\n\n";
            $client->send($msg);
        }
    }

    /**
     * Game actions
     */
    function action_gain_gold($from, $settings) {
        $amt = $settings['amount'];
        foreach ($this->game->players as $value) {
            if ($value->id == $from->resourceId) {
                $value->gold += $amt;
                $this->message_buffer[] = $value->alias . ' gained 1 gold.';
                break;
            }
        }
    }
    function action_spend_gold($from, $settings) {
        $amt = $settings['amount'];
        foreach ($this->game->players as $value) {
            if ($value->id == $from->resourceId) {
                $value->gold -= $amt;
                $this->message_buffer[] = $value->alias . ' spent 1 gold.';
                break;
            }
        }
    }
    function action_recruit_worker($from, $settings) {
        $card_idx = intval($settings['card_index']);
        foreach ($this->game->players as $value) { /* @var $value Player */
            if ($value->id == $from->resourceId) {
                $value->gold--;
                $value->workers++;
                $value->move_card($value->private['hand'], $card_idx, $value->private['workers']);
                $this->message_buffer[] = $value->alias . ' recruited a worker.';
                break;
            }
        }
    }
    function action_discard_redraw($from, $settings) {
        foreach ($this->game->players as $value) { /* @var $value Player */
            if ($value->id == $from->resourceId) {
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
        foreach ($this->game->players as $value) { /* @var $value Player */
            if ($value->id == $from->resourceId) {
                $value->move_card($value->private['hand'], $card_idx, $this->game->table);
                $this->message_buffer[] = $value->alias . ' deployed to the table.';
                break;
            }
        }
    }
    function action_tech($from, $settings) {
        $card_idx = intval($settings['card_index']);
        foreach ($this->game->players as $value) { /* @var $value Player */
            if ($value->id == $from->resourceId) {
                $value->move_card($value->private['codex'], $card_idx, $value->private['discards']);
                $this->message_buffer[] = $value->alias . ' took a card out of their codex.';
                break;
            }
        }
    }
}

function is_invalid_login($username, $aliases) {
    if (empty($username)) {
        return TRUE;
    }
    if (in_array($username, $aliases)) {
        return TRUE;
    }
    return FALSE;
}

/**
 * In certain games, information must be hidden from the player
 */
function apply_mask($gamestate, $this_player) {
    foreach ($gamestate->players as $key => $value) {
        $clone = clone $value; /* @var $clone \MyApp\Player */
        $clone->deck_count = count($clone->hidden['deck']);
        unset($clone->hidden);
        if ($this_player != $clone->id) {
            unset($clone->private);
        } else {
            $gamestate->me = $key;
        }
        $gamestate->players[$key] = $clone;
    }

    return $gamestate;
}

function unit_test() {
    $game = new Game();

    $game->players[] = new Player(12, 'Christian');
    $game->players[] = new Player(13, 'haha');
    $game->is_started = TRUE;

// determine turn order
    shuffle($game->players);

// setup initial workers
    $game->players[0]->workers = 4;
    $game->players[1]->workers = 5;

// generate a starter deck and codex for this player
    foreach ($game->players as $key => $value) { /* @var $value \MyApp\Player */
        $value->build_starter_deck();
        $value->build_codex();

        // deal out 5 cards to each player
        for ($i = 0; $i < 5; $i++) {
            $value->draw_card();
        }
    }
    $game->players[0]->move_card($game->players[0]->private['hand'], 0, $game->players[0]->private['workers']);
    $game->players[0]->move_card($game->players[0]->private['hand'], 0, $game->players[0]->private['workers']);
    $game->players[0]->move_card($game->players[0]->private['hand'], 0, $game->players[0]->private['workers']);

    $game->players[1]->move_card($game->players[1]->heroes, 0, $game->table);
    $game->players[1]->move_card($game->players[1]->private['hand'], 0, $game->table);
    $game->players[1]->move_card($game->players[1]->private['hand'], 0, $game->table);
    $game->players[1]->move_card($game->players[1]->private['hand'], 0, $game->table);

    echo 'test complete';
}
