<p><strong><?php esc_html_e('PayPal Express Checkout with Smart Buttons', 'event_espresso'); ?></strong></p>
<p>
    <?php esc_html_e('Adjust the settings for the PayPal Express Checkout with Smart Buttons payment gateway.', 'event_espresso'); ?>
</p>
<p>
    <?php
    printf(
        esc_html__('See %1$shere%2$s for list of currencies supported by PayPal Smart Buttons (which uses PayPal\'s REST API).', 'event_espresso'),
        "<a href='https://developer.paypal.com/docs/integration/direct/rest/currency-codes/'>",
        "</a>"
    ); ?>
</p>
<p><strong><?php esc_html_e('PayPal Express Checkout with Smart Buttons Settings', 'event_espresso'); ?></strong></p>
<p><?php esc_html_e('This uses different PayPal credentials than PayPal Express, Pro, and Standard. Because this uses PayPal\'s newer REST API, you need to create what they call "An App". Creating one is easy:','event_espresso' );?></p>
<ol>
    <li><?php
        printf(
            esc_html(
                _x(
                    'Create an account on %1$s (opens in new tab)',
                    'Create an account on {link-to-developer.paypa.com} (opens in a new tab)',
                    'event_espresso'
                )
            ),
            '<a href="https://developer.paypal.com" target="_blank">developer.paypal.com</a>'
        );
        ?></li>
    <li><?php esc_html_e('Go to "My Apps & Credentials" (you may already be on it)','event_espresso' );?></li>
    <li><?php esc_html_e('Scroll down to "REST API apps"','event_espresso' );?></li>
    <li><?php esc_html_e('Just under that, click "Create App"','event_espresso' );?></li>
    <li><?php esc_html_e('Select the "App Name" you\'d like registrants to see. The name of your website is a good choice. Pick any sandbox developer account you like.','event_espresso' );?></li>
    <li><?php esc_html_e('Note that your App\'s "Client ID" and "Secret" are on the following page. These can be accessed later from the "My Apps & Credentials" page.','event_espresso' );?></li>
</ol>
<p><?php
    printf(
        esc_html(
                _x(
                    '%1$sHere is a video showing the above %2$s (opens in a new tab).',
                    'Here is a video showing the above (opens in a new tab).',
                    'event_espresso'
                )
        ),
        '<a href="https://drive.google.com/file/d/1QW5btK7UP8FU4JehgENkzApA-TZkANuI/view">',
        '</a>'
        );
    ?></p>
<ul>
    <li>
        <strong><?php esc_html_e('App Client ID', 'event_espresso'); ?></strong><br/>
        <?php esc_html_e('Retrieved from PayPal\'s "My Apps & Credentials" page, see above.', 'event_espresso'); ?>
    </li>
    <li>
        <strong><?php esc_html_e('App Secret', 'event_espresso'); ?></strong><br/>
        <?php esc_html_e('Retrieved from PayPal\'s "My Apps & Credentials" page, see above.', 'event_espresso'); ?>
    </li>
    <li>
        <strong><?php esc_html_e('Button Shape', 'event_espresso'); ?></strong><br/>
        <?php esc_html_e('Controls the shape of PayPal\'s Smart Buttons. Choose whichever shape you prefer with your website\'s design.', 'event_espresso'); ?>
    </li>
    <li>
        <strong><?php esc_html_e('Button Size', 'event_espresso'); ?></strong><br/>
        <?php esc_html_e(
            'Controls how much space allotted to the PayPal Smart Buttons. Standard button size only has room to show the pay with PayPal or PayPal Credit options. Full Width can show other options, like credit cards and Venmo, when applicable.',
            'event_espresso'
        ); ?>
    </li>
</ul>