<?php declare(strict_types=1);
script('westvault', 'script');
style('westvault', 'style');
?>

<div id="app">
    <div id="app-navigation">
        <?php print_unescaped($this->inc('part.navigation')); ?>
        <?php print_unescaped($this->inc('part.settings')); ?>
    </div>

    <div id="app-content">
        <div id="app-content-wrapper">
            <?php if (isset($_['message'])) {
                echo $_['message'];
            } ?>
            <?php print_unescaped($this->inc('main.content')); ?>
        </div>
    </div>
</div>
