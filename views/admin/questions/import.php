<?php

if (!defined('ABSPATH')) {
    exit;
}

?>

<div class="wrap">

    <h1><?php esc_html_e('Import Questions', 'eas-quiz'); ?></h1>

    <p>
        <?php esc_html_e('Quiz:', 'eas-quiz'); ?>
        <strong><?php echo esc_html($quiz_title); ?></strong>
    </p>

    <hr>

    <h2><?php esc_html_e('Moodle GIFT Import', 'eas-quiz'); ?></h2>

    <p>
        <?php
        printf(
            esc_html__('Paste questions exported from Moodle in %s below.', 'eas-quiz'),
            '<strong>' . esc_html__('GIFT format', 'eas-quiz') . '</strong>'
        );
        ?>
    </p>

    <p><?php esc_html_e('EAS currently supports:', 'eas-quiz'); ?></p>

    <ul style="list-style: disc; margin-left: 25px;">
        <li><strong><?php esc_html_e('Description', 'eas-quiz'); ?></strong> <?php esc_html_e('questions', 'eas-quiz'); ?></li>
        <li><strong><?php esc_html_e('Multiple Choice', 'eas-quiz'); ?></strong> <?php esc_html_e('questions', 'eas-quiz'); ?></li>
        <li><?php esc_html_e('Question numbers in the', 'eas-quiz'); ?> <code>::000::</code>, <code>::001::</code> <?php esc_html_e('format', 'eas-quiz'); ?></li>
        <li><?php esc_html_e('Correct answers beginning with', 'eas-quiz'); ?> <code>=</code></li>
        <li><?php esc_html_e('Incorrect answers beginning with', 'eas-quiz'); ?> <code>~</code></li>
        <li><?php esc_html_e('General Feedback beginning with', 'eas-quiz'); ?> <code>####</code></li>
        <li><?php esc_html_e('HTML in question text and General Feedback', 'eas-quiz'); ?></li>
    </ul>

    <h3><?php esc_html_e('Examples', 'eas-quiz'); ?></h3>

    <p><strong><?php esc_html_e('Description:', 'eas-quiz'); ?></strong></p>

    <pre style="background:#f6f7f7;padding:15px;border:1px solid #ccd0d4;max-width:800px;overflow:auto;">::000::<strong>Questions 41 through 43 refer to the following article.</strong></pre>

    <p><strong><?php esc_html_e('Multiple Choice:', 'eas-quiz'); ?></strong></p>

    <pre style="background:#f6f7f7;padding:15px;border:1px solid #ccd0d4;max-width:800px;overflow:auto;">::001::The house Mr. Eno is thinking about buying is located ----- Heritage Street.{
~to
=on
~from
~of
####on + <strong>street name</strong>}</pre>

    <p>
        <?php esc_html_e('The number', 'eas-quiz'); ?> <code>000</code> <?php esc_html_e('is not displayed.', 'eas-quiz'); ?>
        <?php esc_html_e('Other question numbers are retained at the beginning of the question text.', 'eas-quiz'); ?>
    </p>

    <p>
        <?php esc_html_e('For example,', 'eas-quiz'); ?> <code>::001::</code>
        <?php esc_html_e('becomes', 'eas-quiz'); ?> <strong>1.</strong>
        <?php esc_html_e('at the beginning of the question.', 'eas-quiz'); ?>
    </p>

    <hr>

    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">

        <input type="hidden" name="action" value="eas_import_questions">
        <input type="hidden" name="quiz_id" value="<?php echo esc_attr($quiz_id); ?>">

        <?php wp_nonce_field('eas_import_questions'); ?>

        <table class="form-table">
            <tr>
                <th>
                    <label for="eas_import_text">
                        <?php esc_html_e('GIFT Questions', 'eas-quiz'); ?>
                    </label>
                </th>
                <td>
                    <textarea
                        id="eas_import_text"
                        name="import_text"
                        rows="25"
                        class="large-text code"
                        placeholder="<?php echo esc_attr__('Paste your Moodle GIFT questions here...', 'eas-quiz'); ?>"
                    ></textarea>
                    <p class="description">
                        <?php esc_html_e('Paste the GIFT text exactly as exported from Moodle. Each question should begin with', 'eas-quiz'); ?>
                        <code>::number::</code>.
                    </p>
                </td>
            </tr>
        </table>

        <?php submit_button(__('Import GIFT Questions', 'eas-quiz')); ?>

    </form>

    <p>
        <a class="button" href="<?php echo esc_url(admin_url(
            'admin.php?page=eas-questions&quiz_id=' . (int) $quiz_id . '&action=bank'
        )); ?>">
            ← <?php esc_html_e('Back to Question Bank', 'eas-quiz'); ?>
        </a>
    </p>

</div>