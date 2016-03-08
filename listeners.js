/**
 * Created by cmanalan on 3/8/2016.
 */

(function($) {
    $(document).ready(function () {
        $('.connect-button').click(function () {
            // build websocket URL
            var ws_url = 'wss://' + ws_host;
            if (window.location.protocol == "http:") {
                ws_url = 'ws://' + ws_host;
            }
            if (ws_port != '80' && ws_port.length > 0) {
                ws_url += ':' + ws_port;
            }
            ws_url += ws_folder + ws_path;

            // establish connection
            $('.output').append('<p>Connecting...</p>');
            conn = new WebSocket(ws_url);
            conn.onopen = function (e) {
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

            conn.onerror = function (e) {
                console.log('Websockets error:');
                console.log(e);
                $('.output').append('<p>We got an error... (did you do `php chat-server.php` ?)</p>');
            }

            // listen for events
            conn.onmessage = function (e) {
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
                    $('.game').html('<p>Players:</p>');
                    if (!response.game.is_started) {
                        $('.game').append('<a href="#" class="start-game">Start Game.</a>');
                    }

                }
            };

            $('.hide-after-login').hide();
            $('.logout-button').show();
        });
    });
})(jQuery);