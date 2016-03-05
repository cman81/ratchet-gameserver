<?php
    $is_settings_loaded = FALSE;
    include('settings.php');
    if (!$is_settings_loaded) {
        include('default.settings.php');
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Instant Chat!</title>
    <style>
        .clearfix:after {
            visibility: hidden;
            display: block;
            font-size: 0;
            content: " ";
            clear: both;
            height: 0;
        }
        * html .clearfix             { zoom: 1; } /* IE6 */
        *:first-child+html .clearfix { zoom: 1; } /* IE7 */
        .float-left { float: left; }
        .clear {
            float: none;
            clear: both;
        }
    </style>
    <style>
        body { height: 100%; }
        .operations, .input { height: 5vh; }
        .output {
            border: 1px black solid;
            height: 80vh;
            margin-bottom: 2vh;
            padding: 5px;
        }
        .message { width: 90%; }
        .game { width: 80%; }
        .chat { width: 20%; }
        .input, .logout-button { display: none; }
    </style>
    <script src="//code.jquery.com/jquery-2.2.1.min.js"></script>
    <script>
        var conn;
        
        <?= $ws_connection_params ?>

        (function($) {
            $(document).ready(function() {
                $('.connect-button').click(function() {
                    // establish connection
                    $('.output').append('<p>Connecting...</p>');
                    
                    // We are using wss:// as the protocol because Cloud9 is using
                    // HTTPS. In case you try to run this, using HTTP, make sure
                    // to change this to ws:// .
                    var ws_url = 'wss://' + ws_host;
                    if (ws_port != '80' && ws_port.length > 0) {
                    ws_url += ':' + ws_port;
                    }
                    ws_url += ws_folder + ws_path;
                    conn = new WebSocket(ws_url);
                    conn.onopen = function(e) {
                        console.log("Connection established!");
                        $('.output').append('<p>Connection established!</p>');
                        clearAlert();
                        $('.input').show();
                        conn.send(JSON.stringify({"login": $('.alias').val()})); // send login
                    };

                    // handle connection errors / slow connections
                    var timeoutID = window.setTimeout(slowAlert, 5000);
                    function slowAlert() {
                        $('.output').append('<p>Slow connection... (did you do `php chat-server.php` ?)</p>');
                    }
                    function clearAlert() {
                        window.clearTimeout(timeoutID);
                    }
                    conn.onerror = function(e) {
                        console.log('Websockets error:');
                        console.log(e);
                        $('.output').append('<p>We got an error... (did you do `php chat-server.php` ?)</p>');
                    }

                    // listen for events
                    conn.onmessage = function(e) {
                        var response = JSON.parse(e.data);
                        console.log(response);
                        if (typeof(response.loginError) != "undefined" && response.loginError !== null) {
                            $('.output').append('<p>Invalid login.</p>'); // receive message
                            $('.hide-after-login').show();
                            $('.logout-button').hide();
                        }
                        if (typeof(response.login) != "undefined" && response.login !== null) {
                            $('.output').append('<p>' + response.login + ' has logged in.</p>');
                        }
                        if (typeof(response.chat) != "undefined" && response.chat !== null) {
                            $('.output').append('<p>' + response.chat.from + ': ' + response.chat.message + '</p>'); // receive message
                        }
                        if (typeof(response.logout) != "undefined" && response.logout !== null) {
                            $('.output').append('<p>' + response.logout + ' has logged out.</p>');
                        }
                        if (typeof(response.gameError) != "undefined" && response.gameError !== null) {
                            $('.output').append('<p>There was a problem with your request: ' + response.gameError + '</p>'); // receive message
                        }
                        if (typeof(response.game) != "undefined" && response.game !== null) {
                            if (!response.game.players[0] || !response.game.players[1]) {
                                $('.game').html('Waiting for another player to join.');
                            } else {
                                $('.game').html('<p>Score:</p>');
                                for (var key in response.game.players) {
                                    var value = response.game.players[key];
                                    $('.game').append('<p>' + value.alias + ': ' + value.score + '</p>');
                                }
                            }
                        }
                    };

                    $('.hide-after-login').hide();
                    $('.logout-button').show();
                });

                $('.logout-button').click(function() {
                    conn.close();
                    $('.output').append('<p>Logged out.</p>');
                    $('.hide-after-login').show();
                    $(this).hide();
                });

                // send out events
                $('.send-button').click(function(ev) {
                    ev.preventDefault();
                    $('.output').append('<p>Me: ' + $('.message').val() + '</p>'); // output message on our screen
                    // broadcast message
                    conn.send(JSON.stringify(
                            {
                                "chat": {
                                    "from": $('.alias').val(),
                                    "message" : $('.message').val()
                                }
                            }
                    ));
                    $('.message').val(''); // clear buffer
                });
                $('.play-button').click(function() {
                    conn.send(JSON.stringify(
                            {
                                "game": {
                                    "op": "join"
                                }
                            }
                    ));
                });
            });
        })(jQuery);

    </script>
</head>
<body>
<form>
    <div class="operations">
        <span class="hide-after-login">Alias:</span>
        <input type="text" name="alias" class="hide-after-login alias" />
        <input type="button" value="Login" class="hide-after-login connect-button" />
        <input type="button" value="Play Game" class="play-button" />
        <input type="button" value="Logout" class="logout-button" />
    </div>
    <div class="game float-left">
        Welcome to RPS! Rock, Paper, Scissors
    </div>
    <div class="chat float-left">
        <div class="output"></div>
    </div>
    <div class="clear"></div>
    <div class="input">
        <input type="text" name="message" class="float-left message" />
        <input type="submit" class="float-left send-button" value="Send" />
    </div>
</form>
</body>
</html>