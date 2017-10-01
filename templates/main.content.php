<h2>WestVault Deposits</h2>
<p>Showing deposits for <?php echo $_['user']->getUid(); ?>.</p>

<table id="deposits">
    <thead>
        <tr>
            <th>Path</th>
            <th>Staging Status</th>
            <th>LOCKSS Status</th>
            <th>Agreement</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach($_['deposits'] as $deposit): ?>
        <tr>
            <td class="path"><?php echo basename($deposit->getPath()); ?></td>
            <td class="status"><?php echo $deposit->getPlnStatus(); ?></td>
            <td class="status"><?php echo $deposit->getLockssStatus(); ?></td>
            <td class="status"><?php echo $deposit->getAgreement(); ?></td>
            <td class="status"><button class="restore" data-id="<?php p($deposit->getId()) ?>">Restore</button></td>
        </tr>
        <?php endforeach ?>
    </tbody>
</table>

<p>
    <button id="status-check">Check Status</button>
</p>