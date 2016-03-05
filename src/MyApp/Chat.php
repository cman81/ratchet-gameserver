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
            'players' => array(
                FALSE,
                FALSE,
            ),
            'lastupdated' => time(),
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
            // are we already playing?
            foreach ($this->game['players'] as $key => $value) {
                if (isset($value['id']) && ($value['id'] == $from->resourceId)) {
                    $from->send(json_encode(array(
                        'gameError' => 'You are already playing!'
                    )));
                    echo $this->aliases[$from->resourceId] . " tried joining, but was already playing\n";
                    return;
                }
            }
            $is_playing = FALSE;

            // take a seat if possible
            foreach ($this->game['players'] as $key => $value) {
                if (!$value) {
                    $this->game['players'][$key] = array(
                        'id' => $from->resourceId,
                        'alias' => $this->aliases[$from->resourceId],
                        'score' => 0,
                        'choice' => FALSE,
                        'is_ready' => TRUE,
                    );
                    $this->game['lastupdated'] = time();
                    echo $this->aliases[$from->resourceId] . " joined the game as Player {$key}\n";
                    $is_playing = TRUE;
                    break;
                }
            }
            if (!$is_playing) {
                $from->send(json_encode(array(
                    'gameError' => 'No seats available'
                )));
                echo $this->aliases[$from->resourceId] . " tried joining, but was unable to\n";
                return;
            }
        }
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
    $gamestate['is_you_ready'] = FALSE;
    $gamestate['is_opponents_ready'] = TRUE;
    foreach($gamestate['players'] as $key => $player) {
        if ($player['id'] == $this_player) {
            $gamestate['is_you_ready'] = $player['is_ready'];
            continue;
        }
        if (!$player['is_ready']) {
            $gamestate['is_opponents_ready'] = FALSE;
        }
        if (isset($player['choice']) && $player['choice'] != FALSE) {
            $gamestate[$key]['choice'] = 'HIDDEN';
        }
    }
    return $gamestate;
}
