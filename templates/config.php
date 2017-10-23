<?php
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
            
            <h2>PLN Status</h2>
            <p>
                <?php if ($_['pln_accepting']): ?>Accepting <?php else: ?>Not accepting <?php endif; ?>
                deposits from user <?php echo $_['user']->getUid(); ?>. <br>
                <?php echo $_['pln_message']; ?>
            </p>

            <?php print_unescaped($this->inc('config.content')); ?>
        </div>
    </div>
</div>
