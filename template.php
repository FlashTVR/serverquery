<?php

/*
 * The MIT License
 *
 * Copyright 2015 Steve Guidetti.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

?>
<link rel="stylesheet" href="serverquery.css" type="text/css">
<table class="serverquery">
<?php foreach($data['servers'] as $key => $s): ?>
<?php if($s->online): ?>
    <tr class="server<?php echo $key; ?> online">
        <td class="game"><img src="<?php echo $s->gameIcon; ?>" title="<?php echo $s->gameName; ?>" alt="<?php echo $s->gameName; ?>"></td>
        <td class="server"<?php if(!$s->map): ?> colspan="2"<?php endif; ?>>
            <?php echo $s->name; ?><br>
<?php if($s->link): ?>
            <small><a href="<?php echo $s->link; ?>"><?php echo $s->addr; ?></a></small>
<?php else: ?>
            <small><?php echo $s->addr; ?></small>
<?php endif; ?>
        </td>
<?php if($s->map): ?>
        <td class="map"><?php echo $s->map; ?></td>
<?php endif; ?>
        <td class="playercount"><?php echo $s->playerCount; ?>/<?php echo $s->maxPlayers; ?></td>
        <td class="playerlist">
            <div style="width: <?php echo $s->maxPlayers * 6; ?>px;">
<?php if($s->playerCount > 0): ?>
                <div class="active"></div>
                <div class="active" style="width: <?php echo $s->playerCount * 4 - 4; ?>px;"></div>
                <div style="width: <?php echo ($s->maxPlayers - $s->playerCount) * 4; ?>px;"></div>
<?php if($s->players): ?>
                <ul>
<?php foreach($s->players as $player): ?>
                    <li><?php echo $player; ?></li>
<?php endforeach; ?>
                </ul>
<?php endif; ?>
<?php else: ?>
                <div></div>
                <div style="width: <?php echo $s->maxPlayers * 4 - 4; ?>px;"></div>
<?php endif; ?>
            </div>
        </td>
<?php else: ?>
    <tr class="server<?php echo $key; ?> offline">
        <td class="game"><img src="<?php echo $s->gameIcon; ?>" title="<?php echo $s->gameName; ?>" alt="<?php echo $s->gameName; ?>"></td>
        <td class="server"><i><?php echo $s->name; ?></i><br><small><?php echo $s->addr; ?></small></td>
        <td class="status" colspan="3">Offline</td>
<?php endif; ?>
    </tr>
<?php endforeach; ?>
</table>
