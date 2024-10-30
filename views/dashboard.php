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
                        <img src="<?php echo esc_url(plugin_dir_url(__FILE__).'assets/image/Ok-amico.svg'); ?>" />
                    </div>
                </div>
                <div class="right_ils_setup">
                    <div class="ils_setup_contnet connect_account_txt">
                        
                        <h2>Congratulations! <p> Your account setup done with <?php echo esc_html(WPILS_APP_NAME);?>.</p>
                        </h2>
                        <ul>
                            <li>
                                <p>Make sure your orders are getting sync in a panel.</p>
                            </li>
                            <li>
                                <p>Your tracking details will be sync here once your order will get shipped.</p>
                            </li>
                            <li>
                                <p>If you face any issue you can reach us on <a href="mailto:<?php echo esc_html(WPILS_SUPPORT_EMAIL); ?>"><?php echo esc_html( WPILS_SUPPORT_EMAIL ); ?></a></p>
                            </li>
                        </ul>
                        <div class="ils_review">
                            <p>Like our service and support? Drop a nice review for us.</p>
                            <a href="<?php echo esc_url(WPILS_REVIEW_LINK);?>" target="_blank" class="ils-btn">Review Here</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</body>
</html>