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
            $(this).hide();
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
                    "game": { "op": "join" }
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
                        "actions": ['gain_gold']
                    }
                }
            ));
        });
        $('.game').on('click', '.spend-gold', function() {
            conn.send(JSON.stringify(
                {
                    "game": {
                        "op" : "transaction",
                        "actions": ['spend_gold']
                    }
                }
            ));
        });
    });
})(jQuery);