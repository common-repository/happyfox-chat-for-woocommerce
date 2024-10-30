<?php require_once('happyfox-chat-for-woocommerce-config.php') ?>
<div class='hfc-settings'>
    <div class='header'>
      <div class='hfc-cf'>
        <img class='logo hfc-left' src="<?php echo WP_PLUGIN_URL . HFC_ASSET_LOGO_URL ?>" alt="HappyFox Chat" />
        <img class='logo hfc-left' src="<?php echo WP_PLUGIN_URL . HFC_ASSET_WOO_LOGO_URL ?>" alt="HappyFox Chat" />
      </div>
      <div class='content'>
          <h1>HappyFox Chat + WooCommerce</h1>
          <p>Congratulations on successfully installing the HappyFox Chat plugin.</p>
          <p>HappyFox Chat is a live chat software. You need to get your API key to set up this plugin.</p>
          <p>Sign in to your HappyFox Chat account, copy your API key from the <a href="<?php echo HFC_APP_MANAGE_PAGE ?>" target="_blank">WooCommerce App</a> page and paste it here</p>
      </div>
    </div>
    <hr class='line-break'/>

    <form class="hfc-form" name="hfc-form" method="POST" action="">
        <label class="hfc-label" for="hfc_api_key">WooCommerce API key: </label>
        <input type="text" id="hfc_api_key" class="hfc_api_key" name="hfc_api_key" placeholder="Paste your API key here" value="<?php echo get_option('hfc_api_key')  ?>"/>
        <span id="status">
        </span>
        <input type="hidden" name="hfc_api_key_submission" value="1" />

        <?php if(!(get_option('hfc_api_key'))) { ?>
          <button class='btn-submit' type="submit" name="Submit" value="Connect with HappyFox Chat" >
            Connect with HappyFox Chat
          </button>
        <?php } else { ?>
          <button class='btn-submit' type="submit" name="Submit" value="Connect with HappyFox Chat" >
            Change API Key
          </button>
        <?php } ?>
    </form>
    <hr class='line-break'/>

    <div class='footer-text'>
      <p id="homepage_helper" class="<?php if (!get_option('hfc_api_key')) echo 'hide'; ?>">Visit your <a href="<?php echo home_url() ?>" target="_blank">homepage</a> to see HappyFox Chat in action.</p>
      <div class='footer-background'>
        <div class="hfc-cf">
          <img class="hfc-footer-logo hfc-left" src="<?php echo WP_PLUGIN_URL . HFC_ASSET_LOGO_URL ?>" alt="HappyFox Chat" />
          <p class="hfc-footer-text hfc-left"> Have questions about HappyFox Chat?</p>
        </div>
        <ul class="hfc-cf">
          <li class="hfc-footer-item"><a href="<?php echo HFC_APP_REF_PAGE ?>" target="_blank">chat with us</a></li>
          <li class="hfc-footer-item"><a href="mailto:support@happyfoxchat.com">send us an email</a></li>
          <li class="hfc-footer-item hfc-footer-item-last"> New user? <a href="<?php echo HFC_APP_SIGNUP_PAGE ?>" target="_blank">Sign up</a> for HappyFox Chat</li>
        </ul>
      </div>
    </div>

    <hr class='line-break'/>
</div>

<script type="text/javascript">
    jQuery(document).ready(function() {
        jQuery('form').submit(function(e) {
            jQuery('#status').hide();
            e.preventDefault();
            form = jQuery(this);
            form_data = form.serialize();
            url = form.attr('action');
            submitButton = form.find('button[type="submit"]')
            submitText = submitButton.text();
            submitButton.text('Connecting..');
            jQuery.ajax({
                type: 'POST',
                url: url,
                data: form_data,
                success: function() {
                    debugger;
                    jQuery('#status').attr('class', 'success').text('You\'re all set to chat!');
                    jQuery('#homepage_helper').removeClass('hide');
                    jQuery('#status').addClass('success');
                },
                error: function() {
                    jQuery('#status').attr('class', 'error').text('Your API key seems to be invalid');
                    jQuery('#status').addClass('error');
                },
                complete: function(xhr, status){
                  jQuery('#status').show().delay(4000).fadeOut();
                  if(status === "success"){
                    submitButton.html('Connected Successfully' + '<span class="dashicons dashicons-yes"></span>');
                    setTimeout(function(){
                      submitButton.text('Change API Key');
                    },4000);
                    return;
                  } else {
                    submitButton.text(submitText);
                  }
                }
            });
        });
    });
</script>
