<?php

/**
 * paypal_smart_buttons_debug_info
 *
 * @package               Event Espresso
 * @subpackage
 * @author                Mike Nelson
 *
 * ------------------------------------------------------------------------
 */
/**
 * @var $debug_mode
 */
if ($debug_mode) {
?>
<div class="sandbox-panel">
    <h2 class="section-title"><?php esc_html_e('PayPal Sandbox Mode', 'event_espresso'); ?></h2>
    <h3 class="important-notice"><?php
        esc_html_e(
            'Debug Mode Is Turned On. Payments will not be processed',
            'event_espresso'
        ); ?></h3>

    <h4 class="test-credit-cards-info-pg">
        <?php esc_html_e('Testing Guidelines', 'event_espresso'); ?>
    </h4>
    <ul style="margin:1em 2em 1.5em;">
        <li><?php
            esc_html_e(
                'While testing, use the credit card number & email address associated with your sandbox account.',
                'event_espresso'
            ); ?></li>
        <li><?php
            printf(
                esc_html__(
                    'To find the sandbox account\'s credit card, go to %1$s, then "Dashboard", then under Sandbox click "Accounts", then click your account and click "Profile", then in the popup that appears click on the "Funding" tab. Your testing card is listed there.',
                    'event_espresso'
                ),
                '<a href="http://developer.paypal.com">developer.paypal.com</a>'
            ); ?></li>
    </ul>
</div><?php
}
// End of file paypal_smart_buttons_debug_info.template.php