<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Included view variables are local rendering data, not plugin globals.
if (!defined('ABSPATH')) {
    exit;
}

?>

<div class="eas-question eas-description-question">

    <div class="eas-description-content">

        <?php echo do_shortcode(
            wp_kses_post(
                $question['question_text']
            )
        ); ?>

    </div>

</div>