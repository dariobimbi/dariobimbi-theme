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

/* Abuse limits. A WordPress nonce is valid for ~24h and reusable by anonymous
   visitors, so a bot that scrapes one nonce could otherwise POST in a loop.
   wp_mail() can only reach the fixed recipient above (no relay), so this caps
   inbox-flood volume, not spam relaying. */
const DARIOBIMBI_CONTACT_RATE_MAX    = 3;    // successful sends per window, per IP
const DARIOBIMBI_CONTACT_RATE_WINDOW = 600;  // window length, in seconds (10 minutes)
const DARIOBIMBI_CONTACT_MAX_NAME    = 100;  // characters
const DARIOBIMBI_CONTACT_MAX_EMAIL   = 254;  // RFC 5321 practical maximum
const DARIOBIMBI_CONTACT_MAX_MESSAGE = 5000; // characters (~800 words)

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
    <?php elseif ( $status === 'throttled' ) : ?>
        <p class="contact-form__notice contact-form__notice--err" role="alert">
            You have sent a few messages in a row. Please give it a few minutes before sending another.
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

    // Length caps: bound the payload so one submission can't carry a huge body.
    if ( mb_strlen( $name ) > DARIOBIMBI_CONTACT_MAX_NAME
         || mb_strlen( $email ) > DARIOBIMBI_CONTACT_MAX_EMAIL
         || mb_strlen( $message ) > DARIOBIMBI_CONTACT_MAX_MESSAGE ) {
        $redirect( 'error' );
    }

    // Per-IP rate limit. Checked only now that the message is well-formed, so a
    // fumbled submission (typo'd email) doesn't burn the quota. REMOTE_ADDR is
    // used deliberately over forwarded-for headers, which a client can spoof.
    $ip      = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
    $rl_key  = 'dariobimbi_contact_rl_' . md5( $ip );
    $tries   = (int) get_transient( $rl_key );
    if ( $tries >= DARIOBIMBI_CONTACT_RATE_MAX ) {
        $redirect( 'throttled' );
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

    // Only a delivered message counts toward the limit — a failed send (e.g. no
    // SMTP locally) shouldn't lock anyone out. set_transient refreshes the TTL,
    // so a persistent sender stays blocked for the full window from their last
    // successful send, while a legitimate one ages out naturally.
    if ( $sent ) {
        set_transient( $rl_key, $tries + 1, DARIOBIMBI_CONTACT_RATE_WINDOW );
    }

    $redirect( $sent ? 'sent' : 'error' );
}

add_action( 'admin_post_' . DARIOBIMBI_CONTACT_ACTION,        'dariobimbi_contact_handle' );
add_action( 'admin_post_nopriv_' . DARIOBIMBI_CONTACT_ACTION, 'dariobimbi_contact_handle' );
