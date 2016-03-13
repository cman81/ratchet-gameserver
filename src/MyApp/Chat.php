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
                if (method_exists($this->game, 'action_' . $value['action'])) {
                    $fnname = 'action_' . $value['action'];
                    $this->game->{$fnname}($from->resourceId, $value);
                    $this->game->lastupdated = time();
                }
            }
        }
        $this->send_gamestate();
    }

    /**
     * Send the current game state
     */
    function send_gamestate() {
        foreach ($this->clients as $client) {
            $msg = json_encode(array(
                'game' => apply_mask(clone $this->game, $client->resourceId),
                'messages' => $this->game->message_buffer,
            ));
            echo "Passing the following information to " . $this->aliases[$client->resourceId] . ":\n\n" . $msg . "\n\n";
            $client->send($msg);
        }
        $this->game->message_buffer = array();
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

    // Player 1: recruit a worker and bring out a card (assume it costs 2 cold)
    $game->action_gain_gold($game->players[0]->id, array('amount' => 4));
    $game->action_spend_gold($game->players[0]->id, array('amount' => 1));
    $game->action_recruit_worker($game->players[0]->id, array(
        'selected_deck' => 'hand',
        'card_index' => 0
    ));
    $game->action_spend_gold($game->players[0]->id, array('amount' => 2));
    $game->action_deploy($game->players[0]->id, array(
        'selected_deck' => 'hand',
        'card_index' => 0
    ));
    $game->action_discard_redraw($game->players[0]->id);


    // Player 2: recruit your hero
    $game->action_gain_gold($game->players[1]->id, array('amount' => 5));
    $game->action_spend_gold($game->players[1]->id, array('amount' => 2));
    $game->action_deploy($game->players[1]->id, array(
        'selected_deck' => 'heroes',
        'card_index' => 0
    ));
    $game->action_discard_redraw($game->players[1]->id);

    echo 'test complete';
}
