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
    <link rel="stylesheet" type="text/css" href="style.css" />
    <link rel="stylesheet" type="text/css" href="//code.jquery.com/ui/1.11.4/themes/hot-sneaks/jquery-ui.css" />
    <script src="//code.jquery.com/jquery-2.2.1.min.js"></script>
    <script src="//code.jquery.com/ui/1.11.4/jquery-ui.min.js"></script>
    <script src="helpers.js"></script>
    <script src="listeners.js"></script>
    <script src="senders.js"></script>
    <script>
        var conn;
        
        <?= $ws_connection_params ?>
    </script>
</head>
<body>
<form>
    <div class="operations">
        <span class="hide-after-login">Alias:</span>
        <input type="text" name="alias" class="hide-after-login alias" />
        <input type="button" value="Login" class="hide-after-login connect-button" />
        <input type="button" value="Play Game" class="show-after-login play-button" />
        <input type="button" value="Logout" class="show-after-login logout-button" />
        <span class="show-after-login">Team:</span>
        <select class="team show-after-login" name="team">
            <option value="">Random Team</option>
            <option value="mono_white">Mono White: Discipline, Ninjutsu, Strength</option>
            <option value="mono_red">Mono Red: Anarchy, Fire, Blood</option>
            <option value="mono_black">Mono Black: Demonology, Disease, Necromancy</option>
            <option value="mono_green">Mono Green: Balance, Feral, Growth</option>
            <option value="mono_purple">Mono Purple: Past, Present, Future</option>
            <option value="mono_blue">Mono Blue: Law, Peace, Truth</option>
        </select>
    </div>
    <div class="lobby float-left">
        Welcome to Codex!
    </div>
    <div class="game float-left">
        <h2 class="stats">Stats <span class="whos-turn"></span> <span class="gold"></span></h2>
        <div class="stats"></div>
        <h2 class="heroes">Heroes</h2>
        <div class="heroes clearfix"></div>
        <h2 class="table">Table</h2>
        <div class="table clearfix"></div>
        <h2 class="hand">Hand (<span class="size"></span>)</h2>
        <div class="hand clearfix"></div>
        <h2 class="actions">Actions</h2>
        <div class="actions">
            <p class="main">
                Game actions:
                <input type="button" class="gain-upkeep-gold" value="Gain Upkeep Gold" />
                <input type="button" class="discard-redraw" value="Discard/Draw Hand" />
                <input type="button" class="end-turn" value="End Turn" />
            </p>
            <p class="card">
                Card: Player <span class="selected player">0</span> <span class="selected deck">hand</span>[<span class="selected index">0</span>] actions:
                <input type="button" class="recruit-worker" value="Recruit Worker" />
                <input type="button" class="deploy" value="Deploy to Table" />
                <select class="patrol">
                    <option>None</option>
                    <option>Squad Leader</option>
                    <option>Elite</option>
                    <option>Scavenger</option>
                    <option>Technician</option>
                    <option>Lookout</option>
                    </select>
                <input type="button" class="patrol" value="Patrol" />
                <input type="button" class="discard" value="Discard" />
                <input type="button" class="add-damage" value="Add 1 Damage" />
                <input type="button" class="remove-damage" value="Remove 1 Damage" />
            </p>
            <p class="misc">
                Misc actions
                <input type="button" class="gain-gold" value="Gain 1 Gold" />
                <input type="button" class="spend-gold hide-if-broke" value="Spend 1 Gold" />
                <input type="button" class="draw" value="Draw a Card" />
            </p>
            <input type="button" class="tech" value="Tech from Codex" />
        </div>
        <h2 class="misc">Misc</h2>
        <div class="misc">
            <h2 class="discards">Discards (<span class="size"></span>)</h2>
            <div class="discards"><select class="discards" name="discards"></select></div>
            <h2 class="workers">Workers (<span class="size"></span>)</h2>
            <div class="workers"><select class="workers" name="workers"></select></div>
            <h2 class="codex">Codex (<span class="size"></span>)</h2>
            <div class="codex"><select class="codex" name="codex"></select></div>
        </div>
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