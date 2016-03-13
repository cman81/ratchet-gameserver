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
    <script src="//code.jquery.com/jquery-2.2.1.min.js"></script>
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