<h2>WestVault Deposits</h2>
<p>Showing deposits for <?php echo $_['user']->getUid(); ?>.</p>

<table id="deposits">
    <thead>
        <tr>
            <th>Path</th>
            <th>Status</th>
            <th>LOCKSS Status</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($_['deposits'] as $depositFile) { ?>
            <tr>
                <td class="path"><?php echo basename($depositFile->getPath()); ?></td>
                <td class="status pln-status"><?php echo $depositFile->getPlnStatus(); ?></td>
                <td class="status lockss-status"><?php echo $depositFile->getLockssStatus(); ?></td>
                <td class="status button-status">
                    <?php if ('agreement' === $depositFile->getLockssStatus()) { ?>
                        <button class="restore" data-uuid="<?php p($depositFile->getUuid()); ?>">Restore</button>
                    <?php } ?>
                </td>
            </tr>
        <?php } ?>
    </tbody>
</table>
