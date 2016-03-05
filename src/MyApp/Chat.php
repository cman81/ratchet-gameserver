<?php
namespace MyApp;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class Chat implements MessageComponentInterface {
    protected $clients;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->aliases = array();
    }

    public function onOpen(ConnectionInterface $conn) {
        // Store the new connection to send messages to later
        $this->clients->attach($conn);

        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $json = json_decode($msg, TRUE);
        if (isset($json['login'])) {
            handle_login($json['login'], $from, $this->aliases);
        } else {
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
        unset($this->aliases[$conn->resourceId]);
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";

        $conn->close();
    }
}

function handle_login($login, &$from, &$aliases) {
    if (is_invalid_login($login, $aliases)) {
        $from->send(json_encode(array(
            'login' => 'Disconnecting: invalid login',
        )));
        echo "Invalid login.\n";
        $from->close();
    } else {
        $aliases[$from->resourceId] = $login;
        echo "The following users are logged in: " . implode(', ', $aliases) . "\n";
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
