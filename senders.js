/**
 * Created by cmanalan on 3/8/2016.
 */

(function($) {
    $(document).ready(function() {
        // send out events
        $('.logout-button').click(function() {
            conn.close();
            $('.output').append('<p>Logged out.</p>');
            $('.hide-after-login').show();
            $('.show-after-login').hide();
        });
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
                        "op": "join",
                        "team": $('select.team').val()
                    }
                }
            ));
        });
        $('.game').on('click', '.start-game', function(ev) {
            ev.preventDefault();
            conn.send(JSON.stringify(
                {
                    "game": { "op" : "start" }
                }
            ));
        });
        $('.game').on('click', '.gain-gold', function() {
            conn.send(JSON.stringify(
                {
                    "game": {
                        "op" : "transaction",
                        "actions": [{
                            'action': 'gain_gold',
                            'amount': 1
                        }]
                    }
                }
            ));
        });
        $('.game').on('click', '.gain-upkeep-gold', function() {
            conn.send(JSON.stringify(
                {
                    "game": {
                        "op" : "transaction",
                        "actions": [{
                            'action': 'gain_gold',
                            'amount': parseInt($('.total-workers').html())
                        }]
                    }
                }
            ));
        });
        $('.game').on('click', '.spend-gold', function() {
            conn.send(JSON.stringify(
                {
                    "game": {
                        "op" : "transaction",
                        "actions": [{
                            'action': 'spend_gold',
                            'amount': 1
                        }]
                    }
                }
            ));
        });
        $('.game').on('click', '.recruit-worker', function() {
            if ($('.selected.deck').html() == 'hand') {
                conn.send(JSON.stringify(
                    {
                        "game": {
                            "op" : "transaction",
                            "actions": [{
                                'action': 'recruit_worker',
                                'card_index': $('.selected.index').html()
                            }],
                        }
                    }
                ));
            }
        });
        $('.game').on('click', '.discard-redraw', function() {
            conn.send(JSON.stringify(
                {
                    "game": {
                        "op" : "transaction",
                        "actions": [{
                            'action': 'discard_redraw'
                        }],
                    }
                }
            ));
        });
        $('.game').on('click', '.deploy', function() {
            if ($('.selected.deck').html() == 'hand' || $('.selected.deck').html() == 'heroes') {
                conn.send(JSON.stringify(
                    {
                        "game": {
                            "op" : "transaction",
                            "actions": [{
                                'action': 'deploy',
                                'selected_deck': $('.selected.deck').html(),
                                'card_index': $('.selected.index').html()
                            }]
                        }
                    }
                ));
            }
        });
        $('.game').on('click', '.tech', function() {
            conn.send(JSON.stringify(
                {
                    "game": {
                        "op" : "transaction",
                        "actions": [{
                            'action': 'tech',
                            'card_index': $('select.codex').val()
                        }],
                    }
                }
            ));
        });
        $('.game').on('click', '.end-turn', function() {
            conn.send(JSON.stringify(
                {
                    "game": {
                        "op" : "transaction",
                        "actions": [{ 'action': 'end_turn' }]
                    }
                }
            ));
        });
        $('.game').on('click', 'input.patrol', function() {
            if ($('.selected.deck').html() == 'battlefield') {
                conn.send(JSON.stringify(
                    {
                        "game": {
                            "op" : "transaction",
                            "actions": [{
                                'action': 'patrol',
                                'selected_player': $('.selected.player').html(),
                                'card_index': $('.selected.index').html(),
                                'patrol': $('select.patrol').val().toLowerCase()
                            }]
                        }
                    }
                ));
            }
        });
        $('.game').on('click', 'input.discard', function() {
            if ($('.selected.deck').html() == 'battlefield' || $('.selected.deck').html() == 'hand') {
                conn.send(JSON.stringify(
                    {
                        "game": {
                            "op" : "transaction",
                            "actions": [{
                                'action': 'discard',
                                'card_index': $('.selected.index').html(),
                                'selected_deck': $('.selected.deck').html()
                            }]
                        }
                    }
                ));
            }
        });
    });
})(jQuery);