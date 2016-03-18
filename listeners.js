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
                    if (!response.game.is_started) {
                        $('.lobby').html('<p>Players:' + getPlayers(response.game) + '</p>');
                        if (response.game.players.length >= response.game.min_players) {
                            $('.lobby').append('<a href="#" class="start-game">Start Game</a>');
                        } else {
                            $('.lobby').append('<p>Waiting for other players...</p>');
                        }
                        $('.lobby').show();
                        $('.game').hide();
                    } else {
                        drawGameState(response.game);
                        $('.lobby').hide();
                        $('.game').show();
                    }
                    for (var key in response.messages) {
                        var value = response.messages[key];
                        $('.output').append('<p>' + value + '</p>');
                    }
                }
            };

            $('.hide-after-login').hide();
            $('.show-after-login').show();

            // TODO: how do I put the below functions in a separate file?
            function drawGameState(game) {
                var me = game.players[game.me];

                // stats
                $('.game h2.stats .whos-turn').empty();
                if (game.me == game.whos_turn) {
                    $('.game h2.stats .whos-turn').html('(My Turn)');
                }
                $('.game h2.stats .gold').html(me.gold + ' gold');
                $('.game div.stats').empty();
                $('.game div.stats').append('<div>Base HP: ' + me.buildings.base.hp + '</div>');
                $('.game div.stats').append('<div>Workers: <span class="total-workers">' + me.workers + '</span></div>');
                $('.game div.stats').append('<div>Gold: <span class="total-gold">' + me.gold + '</span></div>');
                $('.game div.stats').append('<div>Deck: ' + me.deck_count + ' Card' + ((me.deck_count == 1) ? '' : 's') + '</div>');

                // heroes
                $('.game div.heroes').empty();
                for (var key in me.heroes) {
                    var value = me.heroes[key];
                    $('.game div.heroes').append('<img src="cards/' + value.img + '" class="float-left card-thumb ' + value.status + '" />');
                }

                // table
                $('.game div.table').empty();
                for (var key in game.players) {
                    var value = game.players[key];
                    $('.game div.table').append('<div class="battlefield' + ((game.me == key) ? ' me' : '') + ' clearfix"><h2>' + value.alias + '</h2></div>');
                    for (var k in value.battlefield) {
                        var v = value.battlefield[k];
                        $('.game .battlefield').eq(key).append(
                            '<div class="float-left card-container">' +
                            '<img src="cards/' + v.img + '" class="card-thumb" />' +
                            '<div class="info">' +
                            '<div>Patrol: ' + v.patrol + '</div>' +
                            '<div>Damage: ' + v.damage + '</div>' +
                            '</div>' +
                            '</div>');
                    }
                }

                // hand
                $('.game h2.hand .size').html(me.private.hand.length);
                $('.game div.hand').empty();
                for (var key in me.private.hand) {
                    var value = me.private.hand[key];
                    $('.game div.hand').append('<img src="cards/' + value.img + '" class="float-left card-thumb" />');
                }

                // misc
                $('.game h2.workers .size').html(me.private.workers.length);
                $('.game select.workers').empty();
                for (var key in me.private.workers) {
                    var value = me.private.workers[key];
                    $('.game select.workers').append('<option value="' + key + '">' + value.id + '</option>');
                }
                $('.game h2.codex .size').html(me.private.codex.length);
                $('.game select.codex').empty();
                for (var key in me.private.codex) {
                    var value = me.private.codex[key];
                    $('.game select.codex').append('<option value="' + key + '">' + value.id + '</option>');
                }
                $('.game h2.discards').append(' (' + me.private.discards.length + ')');
                $('.game select.discards').empty();
                for (var key in me.private.discards) {
                    var value = me.private.discards[key];
                    $('.game select.discards').append('<option value="' + key + '">' + value.id + '</option>');
                }
                console.log(game);
            }
        });
    });
})(jQuery);