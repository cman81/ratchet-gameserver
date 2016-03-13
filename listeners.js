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
                        $('.game').html('<p>Players:' + getPlayers(response.game) + '</p>');
                        if (response.game.players.length >= response.game.min_players) {
                            $('.game').append('<a href="#" class="start-game">Start Game</a>');
                        } else {
                            $('.game').append('<p>Waiting for other players...</p>');
                        }
                    } else {
                        drawGameState(response.game);
                        presentActions(response.game);
                        $('.game').accordion('refresh');
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
                $('.game').html('' +
                    '<h2 class="stats">Stats</h2><div class="stats"></div>' +
                    '<h2 class="heroes">Heroes</h2><div class="heroes clearfix"></div>' +
                    '<h2 class="table">Table</h2><div class="table clearfix"></div>' +
                    '<h2 class="hand">Hand</h2><div class="hand clearfix"></div>' +
                    '<h2 class="actions">Actions</h2><div class="actions"></div>' +
                    '<h2 class="misc">Misc</h2><div class="misc">' +
                        '<h2 class="discards">Discards</h2><div class="discards"><select class="discards" name="discards"></select></div>' +
                        '<h2 class="workers">Workers</h2><div class="workers"><select class="workers" name="workers"></select></div>' +
                        '<h2 class="codex">Codex</h2><div class="codex"><select class="codex" name="codex"></select></div>' +
                    '</div>');
                if (game.me == game.whos_turn) {
                    $('.game h2.stats').append(' (My Turn)');
                }
                $('.game div.stats').append('<div>Base HP: ' + me.buildings.base.hp + '</div>');
                $('.game div.stats').append('<div>Workers: <span class="total-workers">' + me.workers + '</span></div>');
                $('.game div.stats').append('<div>Gold: <span class="total-gold">' + me.gold + '</span></div>');
                $('.game div.stats').append('<div>Deck: ' + me.deck_count + ' Card' + ((me.deck_count == 1) ? '' : 's') + '</div>');
                for (var key in me.heroes) {
                    var value = me.heroes[key];
                    $('.game div.heroes').append('<img src="cards/' + value.img + '" class="float-left card-thumb ' + value.status + '" />');
                }
                $('.game h2.hand').append(' (' + me.private.hand.length + ')');
                for (var key in me.private.hand) {
                    var value = me.private.hand[key];
                    $('.game div.hand').append('<img src="cards/' + value.img + '" class="float-left card-thumb" />');
                }
                $('.game h2.workers').append(' (' + me.private.workers.length + ')');
                for (var key in me.private.workers) {
                    var value = me.private.workers[key];
                    $('.game select.workers').append('<option value="' + key + '">' + value.id + '</option>');
                }
                for (var key in game.players) {
                    var value = game.players[key];
                    $('.game div.table').append('<div class="battlefield' + ((game.me == key) ? ' me' : '') + ' clearfix"><h2>' + value.alias + '</h2></div>');
                    for (var k in value.battlefield) {
                        var v = value.battlefield[k];
                        $('.game .battlefield').eq(key).append('<img src="cards/' + v.img + '" class="float-left card-thumb" />');
                    }
                }
                $('.game h2.codex').append(' (' + me.private.codex.length + ')');
                for (var key in me.private.codex) {
                    var value = me.private.codex[key];
                    $('.game select.codex').append('<option value="' + key + '">' + value.id + '</option>');
                }
                $('.game h2.discards').append(' (' + me.private.discards.length + ')');
                for (var key in me.private.discards) {
                    var value = me.private.discards[key];
                    $('.game select.discards').append('<option value="' + key + '">' + value.id + '</option>');
                }
                console.log(game);
            }

            function presentActions(game) {
                $('.game div.actions').append('<p class="main">Game actions:' +
                    '<input type="button" class="gain-upkeep-gold" value="Gain Upkeep Gold" />' +
                    '<input type="button" class="discard-redraw" value="Discard/Draw Hand" />' +
                    '<input type="button" class="end-turn" value="End Turn" />' +
                    '</p>');
                $('.game div.actions').append('<p class="card">Card: Player <span class="selected player">0</span> <span class="selected deck">hand</span>[<span class="selected index">0</span>] actions:' +
                    '<input type="button" class="recruit-worker" value="Recruit Worker" />' +
                    '<input type="button" class="deploy" value="Deploy to Table" />' +
                    '</p>');
                $('.game div.actions').append('<p class="misc">Misc actions' +
                    '<input type="button" class="gain-gold" value="Gain 1 Gold" />' +
                    '<input type="button" class="spend-gold hide-if-broke" value="Spend 1 Gold" />' +
                    '</p>');
                $('.game div.codex').append('<input type="button" class="tech" value="Tech from Codex" />');
            }
        });
    });
})(jQuery);