<div class="wrap">
    <?php if ( $form_send === true ) : ?>
        <?php if ( empty( $errors['input'] ) && empty( $errors['save'] ) ) : ?>
            <div class="updated fade"><p><strong><?php _e( 'Das Plugin wurde erfolgreich installiert und kann nun verwendet werden!', 'linkbird-content-marketing' ); ?></strong></p></div>
        <?php elseif ( !empty( $errors['save'] ) ) : ?>
            <div class="notice notice-error is-dismissible">
                <p><?php echo __('Es gab einen Fehler beim Aktivieren des Plugin, bitte versuchen Sie es später erneut.', 'linkbird-content-marketing'); ?></p>
                <ul>
                <?php foreach ( $errors['save'] as $error_message ) : ?>
                    <li><?php echo $error_message; ?></li>
                <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <h2><?php echo esc_html( get_admin_page_title() ); ?></h2>

    <form method="post" id="lbcm_admin">
        <p class="lbcm_help">
            <?php _e( 'Tragen Sie hier bitte Ihren contentbird Installations Token ein:', 'linkbird-content-marketing' ); ?>
        </p>

        <textarea type="text" placeholder="<?php _e( 'contentbird Installations Token', 'linkbird-content-marketing' ); ?>"
                  class="full <?php if ( array_key_exists( 'lbcm_api_token', $errors['input'] ) ) : ?>lbcm_error<?php endif; ?>"
                  cols="80"
                  rows="10"
                  name="lbcm_options[lbcm_api_token]"
                  id="lbcm_api_token"><?php echo $options['lbcm_api_token']; ?></textarea>

        <?php if ( array_key_exists( 'lbcm_api_token', $errors['input'] ) ) : ?>
            <p class="lbcm_error_message"><?php echo $errors['input']['lbcm_api_token']; ?></p>
        <?php endif; ?>

        <?php submit_button( __( 'Speichern', 'linkbird-content-marketing' ) ); ?>
    </form>
</div>