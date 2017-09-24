<h2>WestVault Settings</h2>

<h3>Terms of Service</h3>
<form id="westvault_terms">
    <?php if ($_['pln_user_terms_checked']): ?>
        <ol>
            <?php foreach ($_['pln_user_terms'] as $term): ?>
                <li><?php p($term['text']); ?><br>
                    <em>updated <?php p($term['updated']); ?></em>
                </li>
            <?php endforeach; ?>
        </ol>
        <p>
            <?php if ($_['pln_user_agreed']): ?>
                Agreement date: <?php p($_['pln_user_agreed']->format('c')); ?>
            <?php else: ?>
                <label for="pln_user_agreed">I agree to abide by the terms of use.</label>
                <input type="checkbox" name="pln_user_agreed" id="pln_user_agreed" value="agree"/><br>
                <button id="terms_agree">Save Agreement</button>
            <?php endif; ?>
        </p>
    <?php else: ?>
        <p>The terms of service have not been updated.</p>
    <?php endif ?>
    <button id="pln_terms_refresh">Refresh Terms</button>
</form>

<h3>Global Settings</h3>
<?php if (isset($_['isAdmin']) && $_['isAdmin']): ?>
    <form id="westvault_site">
        <p>
            <label for="pln_site_ignore">Ignored file names (one per line)</label><br>
            <textarea name="pln_site_ignore" id="pln_ignore" rows="6" cols="72"><?php echo $_['pln_site_ignore']; ?></textarea><br>
            <em>Examples: .* to ignore files that start with a dot, *.log to ignore logging files.</em>
        </p>
        <h3>Checksum Type</h3>
        <p>
            <input <?php if ($_['pln_site_checksum_type'] === 'md5') echo "checked='checked'" ?> type="radio" name="pln_site_checksum_type" id="pln_site_checksum_type_md5" value="md5">
            <label for="pln_site_checksum_type_md5">MD5</label><br>
            <em>Use message digest algorithm for calculating checksums.</em>
        </p>
        <p>
            <input <?php if ($_['pln_site_checksum_type'] === 'sha1') echo "checked='checked'" ?> type="radio" name="pln_site_checksum_type" id="pln_site_checksum_type_sha1" value="sha1">
            <label for="pln_site_checksum_type_sha1">SHA-1</label><br>
            <em>Use secure hash algorithm 1 for calculating checksums.</em>
        </p>						
        <p>
            <label>Staging server endpoint</label><br>
            <input value="<?php echo $_['pln_site_url'] ?>" type="url" name="pln_site_url" id="pln_site_url" /><br>
            <em>The PLN staging server is where deposits are sent for processing. It probably
                ends in `api/sword/2.0/sd-iri`.</em>
        </p>
        <button id="site_save">Save</button>
    </form>
<?php else: ?>
    <div>
        <h4>Ignored File Names</h4>
        <p>
            Examples: .* to ignore files that start with a dot, *.log to ignore logging files.
        </p>
        <blockquote>
            <?php echo nl2br($_['pln_site_ignore']); ?>
        </blockquote>

        <h4>Checksum Type</h4>
        <p>The checksum method to use when validating deposits.</p>
        <blockquote>
            <?php if ($_['pln_site_checksum_type'] === 'md5'): ?>
                <b>MD5</b>: Use message digest algorithm for calculating checksums.
            <?php elseif ($_['pln_site_checksum_type'] === 'sha1'): ?>
                <b>SHA-1</b>: Use secure hash algorithm 1 for calculating checksums.
            <?php endif ?>
        </blockquote>

        <h4>Staging server endpoint</h4>
        <p>The PLN staging server is where deposits are sent for processing.</p>
        <blockquote>
            <?php echo $_['pln_site_url'] ?>
        </blockquote>

    </div>
<?php endif ?>

<form id="westvault_user">
    <h3>User Settings</h3>    
    <p>
        <label for="pln_user_email">Notification address</label><br>
        <input value="<?php echo $_['pln_user_email']; ?>" type="email" name="pln_user_email" id="pln_user_email"/><br>
        <em>Notification emails will be sent to this address.</em>
    </p>
    <p>
        <label for="pln_user_ignore">Ignored file names (one per line)</label><br>
        <textarea name="pln_user_ignore" id="pln_ignore" rows="6" cols="72"><?php echo $_['pln_user_ignore']; ?></textarea><br>
        <em>Examples: .* to ignore files that start with a dot, *.log to ignore logging files.</em>
    </p>

    <p>
        <label>Preservation folder</label><br>
        <input value="<?php echo $_['pln_user_preserved_folder'] ?>" type="text" name="pln_user_preserved_folder" id="pln_user_preserved_folder" /><br>
        <em>Contents of this folder will be preserved.</em>
    </p>
    <p>
        <label>Restoration folder</label><br>
        <input value="<?php echo $_['pln_user_restored_folder'] ?>" type="text" name="pln_user_restored_folder" id="pln_user_restored_folder" /><br>
        <em>Preserved content will be restored to this folder as requested.</em>
    </p>    
    <p>
        <label for="pln_user_cleanup">Remove completed deposits</label>
        <input <?php if ($_['pln_user_cleanup'] === 'cleanup') echo 'checked="checked"' ?> type="checkbox" name="pln_user_cleanup" id="pln_user_cleanup" value="cleanup"/><br>
        <em>Remove files once they've been deposited to LOCKSS. Leave this unchecked if you would like to clean up the folder manually.</em>
    </p>
    <button id="user_save">Save Settings</button>
</form>


<h4>User UUID</h4>
<p>
    The PLN uses this string to associate deposits with your account.
</p>
<blockquote>
    <?php if ($_['pln_user_uuid']): ?>
        <?php echo $_['pln_user_uuid']; ?>
    <?php else: ?>
        <i>You do not have a UUID. All users other than OwnCloud admininstrators should have a UUID.</i>
    <?php endif ?>
</blockquote>
