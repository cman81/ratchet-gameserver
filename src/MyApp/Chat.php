<?php
namespace MyApp;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class Chat implements MessageComponentInterface {
    protected $clients;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->aliases = array();
        $this->game = array(
            'players' => array(),
            'is_started' => FALSE,
            'min_players' => 2,
            'max_players' => 2,
            'lastupdated' => time(),
            'whos_turn' => 0,
        );
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
            if ($this->game['is_started']) {
                $from->send(json_encode(array(
                    'gameError' => 'The game has already started!'
                )));
                echo $this->aliases[$from->resourceId] . " tried joining, but the game has already started\n";
                return;
            }
            // are we already playing?
            $alias = $this->aliases[$from->resourceId];
            foreach ($this->game['players'] as $key => $value) {
                if ($value['alias'] == $alias) {
                    $this->game['players'][$key]['id'] = $from->resourceId;
                    $from->send(json_encode(array(
                        'gameError' => 'You are already playing!'
                    )));
                    echo $this->aliases[$from->resourceId] . " tried joining, but was already playing\n";
                    $this->send_gamestate();
                    return;
                }
            }

            // take a seat if possible
            if (count($this->game['players']) == $this->game['max_players']) {
                $from->send(json_encode(array(
                    'gameError' => 'No seats available'
                )));
                echo $this->aliases[$from->resourceId] . " tried joining, but was unable to\n";
                return;
            } else {
                $this->game['players'][] = array(
                    'id' => $from->resourceId,
                    'alias' => $this->aliases[$from->resourceId],
                    'score' => 0,
                    'workers' => 0,
                    'private' => array(),
                    'hidden' => array(),
                );
                $this->game['lastupdated'] = time();
                echo $this->aliases[$from->resourceId] . " joined the game as Player " . count($this->game['players']) . "\n";
            }
        }
        if ($json['op'] == 'start') {
            $this->game['is_started'] = TRUE;

            // determine turn order
            shuffle($this->game['players']);

            // setup initial workers
            $this->game['players'][0]['workers'] = 4;
            $this->game['players'][1]['workers'] = 5;

            // generate a starter deck and codex for this player
            foreach ($this->game['players'] as $key => $value) {
                $team = array_rand(
                    array(
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
                    )
                );

                $this->game['players'][$key]['discards'] = build_starter_deck($team['starter']); // place in discards so that they will get shuffled on the draw
                $this->game['players'][$key]['codex'] = build_codex($team['pecs']);

                // deal out 5 cards to each player
            }

        }
        $this->send_gamestate();
    }

    /**
     * Send the current game state
     */
    function send_gamestate() {
        foreach ($this->clients as $client) {
            $msg = json_encode(array('game' => apply_mask($this->game, $client->resourceId)));
            echo "Passing the following information to " . $this->aliases[$client->resourceId] . ": " . $msg . "\n";
            $client->send($msg);
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
    return $gamestate;
}

function build_starter_deck($color) {
    $cards = file(SERVERROOT . '/cards.csv');
    $deck = array();
    $pattern = '/^(' . $color . '-\d{1}).*/';
    foreach ($cards as $value) {
        if (preg_match($pattern, $value)) {
            $deck[] = trim($value);
        }
    }
    return $deck;
}

function build_codex($specs) {
    $cards = file(SERVERROOT . '/cards.csv');
    $patterns = array();
    foreach ($specs as $value) {
        $patterns[] = '/^(' . $value . '-\d{1,2}).*/';
    }
    foreach ($cards as $value) {
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $value)) {
                for ($i = 0; $i < 2; $i++) {
                    $deck[] = trim($value);
                }
                break; // proceed to next card
            }
        }
    }
    return $cards;
}
