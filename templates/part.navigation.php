<div id="app-navigation">
    <ul>
        <?php foreach ($_['navigation'] as $n): ?>
            <li><a href="<?php p($n['url']); ?>"><?php p($n['name']); ?></a></li>
        <?php endforeach; ?>
    </ul>
</div>