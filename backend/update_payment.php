<?php
session_start();
require_once("functions.php");
$id_user = $_SESSION['id_user'];

$user_info = get_user_info($id_user);
$settings = get_settings();
$stripe_enabled = $settings['stripe_enabled'];
$stripe_secret_key = $settings['stripe_secret_key'];
$stripe_public_key = $settings['stripe_public_key'];
if((!$stripe_enabled) && (empty($stripe_public_key)) || (empty($stripe_secret_key))) {
    exit;
}
$id_subscription_stripe = $user_info['id_subscription_stripe'];
?>

<script src="https://js.stripe.com/v3/"></script>

<div style="cursor: pointer" onclick="redirect_to_setup();" class="card bg-warning text-white shadow mb-4">
    <div class="card-body">
        <?php echo _("There's a problem with your payment details. Please update your card by clicking here."); ?><br>
        <?php echo _("Actual payment method: "); ?> <span id="card_num">--</span>
    </div>
</div>

<div id="modal_redirect_setup" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-body">
                <p><?php echo _("Redirecting to payment setup page ..."); ?></p>
            </div>
        </div>
    </div>
</div>

<script>
    (function($) {
        "use strict"; // Start of use strict
        $(document).ready(function () {
            window.stripe = Stripe('<?php echo $stripe_public_key; ?>');
            get_payment_method();
        });

    })(jQuery); // End of use strict
</script>