<h2>WestVault Settings</h2>

<p>Hello <?php echo $_['user']->getDisplayName(); ?> from $group.</p>

<form>
    <input type="hidden" value="settings_type" value="global"/>
    <h3>Global Settings</h3>
    <p>
        <?php if (isset($_['isAdmin']) && $_['isAdmin']): ?>
            You are an admin.
        <?php else: ?>
            You are not an admin.
        <?php endif ?>
    </p>
</form>

<form>
    <input type="hidden" value="settings_type" value="group"/>
    <h3>Group Settings</h3>    
</form>

<form>
    <input type="hidden" value="settings_type" value="user"/>
    <h3>User Settings</h3>    
</form>
