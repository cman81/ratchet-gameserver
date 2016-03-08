/**
 * Created by cmanalan on 3/8/2016.
 */

(function($) {
    $(document).ready(function () {

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