<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    <form action="options.php" method="post">
        <?php
        settings_fields($this->option_name);
        do_settings_sections('wp-post-upsert-webhooks');
        submit_button();
        ?>
    </form>
</div>
