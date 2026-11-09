<?php
/**
 * Campos invisibles contra robots (App\Libraries\BotTrap). Va dentro del
 * <form>, junto a csrf_field(); la ruta POST debe tener el filtro "bottrap".
 * El campo trampa queda fuera de la vista y del orden de tabulación; los
 * lectores de pantalla lo ignoran.
 */
use App\Libraries\BotTrap;
?>
<div class="bot-trap" aria-hidden="true">
    <label for="bt-<?= BotTrap::FIELD ?>">No llenes este campo</label>
    <input type="text" id="bt-<?= BotTrap::FIELD ?>" name="<?= BotTrap::FIELD ?>" value="" tabindex="-1" autocomplete="off">
</div>
<input type="hidden" name="<?= BotTrap::TIME_FIELD ?>" value="<?= esc(BotTrap::token(), 'attr') ?>">
