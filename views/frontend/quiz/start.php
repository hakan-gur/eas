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
    <strong>
        <?php esc_html_e('Time limit:', 'eas-quiz'); ?>
    </strong>
    <?php echo esc_html($quiz['time_limit']); ?>
    <?php
    printf(
        esc_html(
            _n(
                'minute',
                'minutes',
                (int) $quiz['time_limit'],
                'eas-quiz'
            )
        )
    );
    ?>
</p>
<?php endif; ?>
    <div class="eas-attempt-info">
        <p>
            <strong>
                <?php esc_html_e('Attempts allowed:', 'eas-quiz'); ?>
            </strong>
            <?php
            echo (int) $quiz['attempts_allowed'] === 0
                ? esc_html__('Unlimited', 'eas-quiz')
                : esc_html($quiz['attempts_allowed']);
            ?>
        <br>
        <?php if (is_user_logged_in()): ?>
                <strong>
                    <?php esc_html_e('Completed attempts:', 'eas-quiz'); ?>
                </strong>
                <?php echo esc_html($completed_attempts); ?>
            <br>
            <?php if ($remaining_attempts !== null): ?>
                    <strong>
                        <?php esc_html_e('Remaining attempts:', 'eas-quiz'); ?>
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
              action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
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
                <?php esc_html_e('Start Attempt', 'eas-quiz'); ?>
            </button>
        </form>
    <?php else: ?>
        <p class="eas-no-attempts">
            <?php esc_html_e('You have reached the maximum number of attempts allowed for this quiz.', 'eas-quiz'); ?>
        </p>
    <?php endif; ?>
</div>