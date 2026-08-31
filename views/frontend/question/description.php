<?php

if (!defined('ABSPATH')) {
    exit;
}

?>

<div class="eas-question eas-description-question">

    <div class="eas-description-content">

        <?php echo wp_kses_post(
            $question['question_text']
        ); ?>

    </div>

</div>