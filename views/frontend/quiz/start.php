<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="eas-quiz-start">
    <h2>
        <?php echo esc_html($quiz['title']); ?>
    </h2>
    <?php if (!empty($quiz['description'])): ?>
        <div class="eas-description">
            <?php echo wp_kses_post($quiz['description']); ?>
        </div>
    <?php endif; ?>
<?php if ((int) $quiz['time_limit'] > 0): ?>
<p>
<?php if ((int) $quiz['time_limit'] > 0): ?>
<p>
    <strong>
        Time limit:
    </strong>
    <?php echo esc_html($quiz['time_limit']); ?>
    minute<?php echo ((int) $quiz['time_limit'] > 1) ? 's' : ''; ?>
</p>
<?php endif; ?>
</p>
<?php endif; ?>
    <div class="eas-attempt-info">
        <p>
            <strong>
                Attempts allowed:
            </strong>
            <?php
            echo (int) $quiz['attempts_allowed'] === 0
                ? 'Unlimited'
                : esc_html($quiz['attempts_allowed']);
            ?>
        <br>
        <?php if (is_user_logged_in()): ?>
                <strong>
                    Completed attempts:
                </strong>
                <?php echo esc_html($completed_attempts); ?>
            <br>
            <?php if ($remaining_attempts !== null): ?>
                    <strong>
                        Remaining attempts:
                    </strong>
                    <?php echo esc_html($remaining_attempts); ?>
                </p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <?php if (
        $remaining_attempts === null
        ||
        $remaining_attempts > 0
    ): ?>
        <form method="post"
              action="<?php echo admin_url('admin-post.php'); ?>">
            <input type="hidden"
                   name="action"
                   value="eas_start_quiz">
            <input type="hidden"
                   name="quiz_id"
                   value="<?php echo esc_attr($quiz['id']); ?>">
            <?php
            wp_nonce_field(
                'eas_start_quiz'
            );
            ?>
            <button type="submit"
                    class="button button-primary">
                Start Attempt
            </button>
        </form>
    <?php else: ?>
        <p class="eas-no-attempts">
            You have reached the maximum number of attempts allowed for this quiz.
        </p>
    <?php endif; ?>
</div>