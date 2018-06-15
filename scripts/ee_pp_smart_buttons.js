// Render the PayPal button
/**
 * @var ee_paypal_smart_button_args array of localized variables
 */
jQuery(document).ready(function() {
	//add SPCO object
	ee_paypal_smart_buttons_args.spco = SPCO;
	ee_paypal_smart_buttons = new EegPayPalSmartButtons(ee_paypal_smart_buttons_args,{});
});

/**
 *
 * @param instance_vars {
 * 	spco : SPCO,
 *	currency : string (eg USD)
 *	transaction_total: float|string
 *	payment_div_selector : string	eg '#paypal-button-container'
 *	sandbox_mode: boolean
 *	client_id: string
 *	slug: string (payment method slug)
 *	button_shape: string
 *	button-size: string
 *	button_layout: string
 * }
 * @param translations
 * @constructor
 */
function EegPayPalSmartButtons (instance_vars, translations) {
	this.spco = instance_vars.spco;
	this.currency = instance_vars.currency;
	this.transaction_total = instance_vars.transaction_total;
	this.payment_div_selector = instance_vars.payment_div_selector;
	this.sandbox_mode = instance_vars.sandbox_mode;
	this.client_id = instance_vars.client_id;
	this.slug = instance_vars.slug;
	this.button_shape = instance_vars.button_shape;
	this.button_layout = instance_vars.button_layout;

	this.set_listener_for_payment_amount_change = function() {
		//console.log( JSON.stringify( '**EE_STRIPE.set_listener_for_payment_amount_change**', null, 4 ) );
		this.spco.main_container.on( 'spco_payment_amount', ( event, payment_amount ) =>{
			if ( typeof payment_amount !== 'undefined' && parseInt(payment_amount) !== 0 ) {
				this.transaction_total = payment_amount;
			}
		});
	};
	this.set_listener_for_spco_display_step = function() {
		this.spco.main_container.on( 'spco_display_step', () => {
			this.initialize_smart_button();
		} );
	};

	this.initialize_smart_button = function()
	{
		var payment_div = jQuery(this.payment_div_selector);
		if( payment_div.length == 0 ){
			//if there payment div doesn't exist, don't do anything more
			return;
		}
		//move the paypal button to the right spot
		payment_div.insertBefore('.hidden-paypal-smart-button-regular-button');
		paypal.Button.render({

			// Set your environment

			env: this.sandbox_mode ? 'sandbox' : 'production', // sandbox | production

			// Specify the style of the button
			// locale: 'en_BR',
			style: {
				layout: this.button_layout,  // horizontal | vertical
				size:   'responsive',    // small, medium | large | responsive
				shape:  this.button_shape,      // pill | rect
			},

			// Specify allowed and disallowed funding sources
			//
			// Options:
			// - paypal.FUNDING.CARD
			// - paypal.FUNDING.CREDIT
			// - paypal.FUNDING.ELV

			funding: {
				allowed: [ paypal.FUNDING.CARD, paypal.FUNDING.CREDIT ],
				disallowed: [ ]
			},

			// PayPal Client IDs - replace with your own
			// Create a PayPal app: https://developer.paypal.com/developer/applications/create

			client: {
				sandbox:    this.client_id,
				production: this.client_id
			},

			payment: (data, actions) => {
				return actions.payment.create({
					payment: {
						//documentation on what format transactions can take: https://developer.paypal.com/docs/api/payments/#definition-transaction
						transactions: [
							{
								amount: { total: this.transaction_total, currency: this.currency}
							}
						]
					}
				});
			},

			// onClick: function() {
			// 	SPCO.display_payment_method(
			// 		'paypal_smart_buttons'
			// 	);
			// 	//if we did this, we just need to call jQuery( '#spco-go-to-step-finalize_registration-submit').trigger( 'click' );
			// 	//from onAuthorize
			// },

			onAuthorize: (data, actions) => {
				// don't execute the payment here. Let's do it server-side where it's more secure
				// return actions.payment.execute().then(function() {
				//window.alert('Payment Complete!');
				this.spco.display_payment_method(this.slug);
				// wait for the payment method to be fully displayed, then submit it
				jQuery(document).on('spco_switch_payment_methods', function(){
					jQuery('#paypal-payer-id').val(data.payerID);
					jQuery('#paypal-payment-id').val(data.paymentID);
					jQuery('#paypal-payment-token').val(data.paymentToken);
					jQuery('#paypal-order-id').val(data.orderID);
					jQuery('#spco-go-to-step-finalize_registration-submit').trigger( 'click' );
				});

				// });
			},
			onError: ( data, actions) => {
				if( typeof(data) !== 'undefined' && typeof(data.message) !== 'undefined') {
					var error = data.message;
				} else {
					var error = 'An error occurred while processing payment with PayPal';
				}
				var messages = {
					errors : error
				};
				this.spco.display_messages(messages,false);
			}

		}, this.payment_div_selector);

	}

	//get this show on the road
	this.set_listener_for_payment_amount_change();
	this.set_listener_for_spco_display_step();
	this.initialize_smart_button();
}

