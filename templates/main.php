<?php
    script('westvault', 'script');
    style('westvault', 'style');
?>

<div id="app">
    <div id="app-navigation">
        <?php print_unescaped($this->inc('part.navigation')); ?>
    </div>

    <div id="app-content">
        <div id="app-content-wrapper">
            <?php print_unescaped($this->inc('part.content')); ?>
        </div>
    </div>
</div>
