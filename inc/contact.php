<?php
/**
 * Contact form module — Dario Bimbi theme.
 *
 * Hand-rolled, no plugin. The form posts to admin-post.php; the handler
 * verifies a nonce, checks a honeypot, sanitizes inputs, sends with
 * wp_mail(), and redirects back to the homepage with a status flag so
 * the pattern can render a success or error notice above the form.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

const DARIOBIMBI_CONTACT_ACTION    = 'dariobimbi_contact';
const DARIOBIMBI_CONTACT_RECIPIENT = 'dariobimbi@gmail.com';

/**
 * Render the contact form. Called from patterns/contact.php inside a wp:html block.
 */
function dariobimbi_contact_form(): string {
    $status = isset( $_GET['contact'] ) ? sanitize_key( $_GET['contact'] ) : '';

    ob_start();
    ?>
    <?php if ( $status === 'sent' ) : ?>
        <p class="contact-form__notice contact-form__notice--ok" role="status">
            Thanks, your message is on its way. I usually reply within a day.
        </p>
    <?php elseif ( $status === 'error' ) : ?>
        <p class="contact-form__notice contact-form__notice--err" role="alert">
            That did not go through. Please double-check your email address and message, then try again.
        </p>
    <?php endif; ?>

    <form class="contact-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" novalidate>
        <input type="hidden" name="action" value="<?php echo esc_attr( DARIOBIMBI_CONTACT_ACTION ); ?>">
        <?php wp_nonce_field( DARIOBIMBI_CONTACT_ACTION, 'dariobimbi_contact_nonce' ); ?>

        <p class="contact-form__hp" aria-hidden="true">
            <label>Leave this empty
                <input type="text" name="website" tabindex="-1" autocomplete="off">
            </label>
        </p>

        <p class="contact-form__field">
            <label for="contact-name">Name</label>
            <input id="contact-name" type="text" name="name" required>
        </p>

        <p class="contact-form__field">
            <label for="contact-email">Email</label>
            <input id="contact-email" type="email" name="email" required>
        </p>

        <p class="contact-form__field">
            <label for="contact-message">Message</label>
            <textarea id="contact-message" name="message" rows="5" required></textarea>
        </p>

        <p class="contact-form__submit">
            <button type="submit" class="wp-block-button__link wp-element-button">Send message →</button>
        </p>
    </form>
    <?php
    return (string) ob_get_clean();
}

/**
 * Handle the form submission. Always redirects, never renders.
 */
function dariobimbi_contact_handle(): void {
    $back = wp_get_referer();
    if ( ! $back ) {
        $back = home_url( '/' );
    }
    $back = strtok( $back, '#' ); // drop any incoming fragment so we can re-add #contact
    $back = remove_query_arg( 'contact', $back );

    $redirect = function ( string $status ) use ( $back ) {
        $url = add_query_arg( 'contact', $status, $back ) . '#contact';
        wp_safe_redirect( $url );
        exit;
    };

    if ( ! isset( $_POST['dariobimbi_contact_nonce'] )
         || ! wp_verify_nonce( wp_unslash( $_POST['dariobimbi_contact_nonce'] ), DARIOBIMBI_CONTACT_ACTION ) ) {
        $redirect( 'error' );
    }

    // Honeypot. Bots fill hidden fields; humans don't. Pretend success so the bot stops trying.
    if ( ! empty( $_POST['website'] ) ) {
        $redirect( 'sent' );
    }

    $name    = isset( $_POST['name'] )    ? sanitize_text_field( wp_unslash( $_POST['name'] ) )       : '';
    $email   = isset( $_POST['email'] )   ? sanitize_email( wp_unslash( $_POST['email'] ) )           : '';
    $message = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '';

    if ( $name === '' || $message === '' || ! is_email( $email ) ) {
        $redirect( 'error' );
    }

    $subject = sprintf( '[dariobimbi.com] New message from %s', $name );
    $body    = sprintf(
        "Name: %s\nEmail: %s\n\nMessage:\n%s\n",
        $name,
        $email,
        $message
    );
    $headers = [ 'Reply-To: ' . sprintf( '%s <%s>', $name, $email ) ];

    $sent = wp_mail( DARIOBIMBI_CONTACT_RECIPIENT, $subject, $body, $headers );

    $redirect( $sent ? 'sent' : 'error' );
}

add_action( 'admin_post_' . DARIOBIMBI_CONTACT_ACTION,        'dariobimbi_contact_handle' );
add_action( 'admin_post_nopriv_' . DARIOBIMBI_CONTACT_ACTION, 'dariobimbi_contact_handle' );
