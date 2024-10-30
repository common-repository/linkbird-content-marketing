<div class="notice notice-error is-dismissible">
    <?php if ( !empty( $setup_notice ) ) : ?>
        <p>
            <strong><?php echo __( 'Vielen Dank für die Installation des "contentbird Content Marketing" Plugins!', 'linkbird-content-marketing' ); ?></strong>
        </p>
        <p>
            <?php echo sprintf(
                __( 'Bitte öffnen Sie jetzt die <a href="%1$s">Plugin-Einstellungen</a> um die Installation abzuschließen.', 'linkbird-content-marketing' ),
                admin_url( 'options-general.php?page=linkbird-content-marketing' )
            ); ?>
        </p>
    <?php else: ?>
        <p>
            <strong><?php echo __( 'Das Plugin "contentbird Content Marketing" hat Probleme festgestellt:', 'linkbird-content-marketing' ); ?></strong>
        </p>
        <?php foreach( $messages as $message ) { ?>
            <p>
                &nbsp;&nbsp;- <?php echo $message; ?>
            </p>
        <?php } ?>
        <p>
            <?php echo sprintf(
                __( 'Öffnen Sie jetzt die <a href="%1$s">Plugin-Einstellungen</a> um die Probleme zu beheben.', 'linkbird-content-marketing' ),
                admin_url( 'options-general.php?page=linkbird-wordpress-plugin%2Flinkbird-content-marketing.php' )
            ); ?>
        </p>
    <?php endif; ?>
</div>