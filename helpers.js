/**
 * Created by cmanalan on 3/8/2016.
 */

(function($) {
    $(document).ready(function () {
        $('.game').on('click', '.hand .card-thumb', function() {
            console.log($(this));
            $('.selected.deck').html('hand');
            $('.selected.index').html($(this).index('.hand .card-thumb'));
            $('.game').accordion("option", "active", 4);
        });
        $('.game').on('click', '.heroes .card-thumb', function() {
            console.log($(this));
            $('.selected.deck').html('heroes');
            $('.selected.index').html($(this).index('.heroes .card-thumb'));
            $('.game').accordion("option", "active", 4);
        });
        $('.game').on('click', '.battlefield .card-thumb', function() {
            console.log($(this));
            $('.selected.player').html($(this).parents('.battlefield').index('.battlefield'));
            $('.selected.deck').html('battlefield');
            $('.selected.index').html($(this).index('.battlefield:eq(' + $('.selected.player').html() + ') .card-thumb'));
            $('.game').accordion("option", "active", 4);
        });
        $('.game').accordion({ heightStyle: "content" });
    });
})(jQuery);

function getPlayers(game) {
    var out = [];
    for (var key in game.players) {
        var value = game.players[key];
        out.push(value.alias);
    }

    return out.join(', ');
}