<?php
if(!defined('ABSPATH')){
    exit;
}

?>
<body>
    <section class="ils_setup_section">
        <div class="container">
            <div class="main_ils_setup">
                <div class="left_ils_setup">
                    <div class="ils_logo">
                        <a href="<?php echo esc_url(WPILS_DOMAIN);?>" target="_blank">
                            <img src="<?php echo esc_url(plugin_dir_url(__FILE__).'assets/image/ils-logo.png?v=1'); ?>" alt="ils">
                        </a>
                    </div>
                    <div class="left_setup_content">
                        <h2>We Are More Than Just a Shipping Partner</h2>
                    </div>
                </div>
                <div class="right_ils_setup">
                    <div class="ils_setup_contnet">
                        <h2>Connect or setup your account with <?php echo esc_html(WPILS_APP_NAME);?></h2>
                        
                        <form action="<?php echo esc_url( WPILS_Admin::WPILS_get_page_url() ); ?>" method="post">
                            <div class="setup_input">
                                <input type="hidden" name="action" value="verify-key">
                                <input type="hidden" name="_wpnonce" value="<?php echo esc_html(wp_create_nonce()); ?>">
                                <input id="key" name="key" type="text" placeholder="Enter API Token ">
                                <?php
                                    $result = get_transient('wpils_key_validation_result');
                                    echo ("<p class='alert-error'>".esc_html($result)."</p>");
                                    delete_transient('wpils_key_validation_result');
                                ?>
                                <div class="setup_connect_btn">
                                    <button type="submit" name="submit" id="submit" class="ils-btn" >Connect with Chanel</button>
                                </div>
                            </div>
                        </form>
                        <p class="or">Follow below steps for integration</p>
                        
                        <ul class="integration-step">
                            <li>
                                <p>Sign up/in in <a href="<?php echo esc_url(WPILS_DOMAIN);?>" target="_blank">our panel.</a></p>
                            </li>
                            <li>
                                <p>Open channel from sidebar and click on "view" on wordpress box.</p>
                            </li>
                            <li>
                                <p>Click Add channel button and add required details and verify.</p>
                            </li>
                            <li>
                                <p>You will get API token on edit the same channel.</p>
                            </li>
                            <li>
                                <p>Copy same token and add here.</p>
                            </li>
                        </ul>
                        <a href="<?php echo esc_url(WPILS_DOMAIN);?>" target="_blank" class="ils-btn">Setup Account</a>
                    </div>
                </div>
            </div>
        </div>
    </section>
</body>
