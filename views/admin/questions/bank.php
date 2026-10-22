<?php

if (!defined('ABSPATH')) {
    exit;
}

$type_labels = array(
    'multiple_choice' => __('Multiple Choice', 'eas-quiz'),
    'drag_drop_text' => __('Drag and Drop into Text', 'eas-quiz'),
    'fill_blank' => __('Fill in the Blanks (C-Test)', 'eas-quiz'),
    'description' => __('Description', 'eas-quiz'),
    'essay' => __('Essay', 'eas-quiz'),
    'audio_response' => __('Audio Response', 'eas-quiz'),
);

?>

<?php
// phpcs:disable WordPress.Security.NonceVerification.Recommended -- This GET parameter only controls a read-only notice and does not perform an action.
$eas_warning = isset($_GET['eas_warning'])
    ? sanitize_key(wp_unslash($_GET['eas_warning']))
    : '';
// phpcs:enable WordPress.Security.NonceVerification.Recommended
?>

<div class="wrap">
    <?php if ($eas_warning === 'missing_correct_answer') : ?>
        <div class="notice notice-warning is-dismissible">
            <p>
                <?php esc_html_e('Warning: This question has no correct answer selected. It has been saved, but it cannot be graded correctly.', 'eas-quiz'); ?>
            </p>
        </div>
    <?php endif; ?>

    <h1><?php esc_html_e('Question Bank', 'eas-quiz'); ?></h1>

    <p>
        <?php esc_html_e('Quiz:', 'eas-quiz'); ?>
        <strong><?php echo esc_html($quiz_title); ?></strong>
    </p>

    <p>
        <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=eas-quizzes')); ?>">
            ← <?php esc_html_e('Back to Quizzes', 'eas-quiz'); ?>
        </a>

        <a class="button button-primary" href="<?php echo esc_url(admin_url(
            'admin.php?page=eas-questions&quiz_id=' . (int) $quiz['id'] . '&action=bank_add'
        )); ?>">
            ➕ <?php esc_html_e('Create New Question', 'eas-quiz'); ?>
        </a>

        <a class="button" href="<?php echo esc_url(admin_url(
            'admin.php?page=eas-questions&quiz_id=' . $quiz_id . '&action=import'
        )); ?>">
            <?php esc_html_e('Import Questions (GIFT)', 'eas-quiz'); ?>
        </a>

        <a class="button" href="<?php echo esc_url(wp_nonce_url(
            admin_url(
                'admin-post.php?action=eas_export_questions&quiz_id=' . (int) $quiz['id']
            ),
            'eas_export_questions_' . (int) $quiz['id']
        )); ?>">
            <?php esc_html_e('Export Questions (GIFT)', 'eas-quiz'); ?>
        </a>
    </p>

    <hr>

    <?php if (empty($questions)): ?>
        <p>
            <?php esc_html_e('This quiz does not have any questions in its question bank yet.', 'eas-quiz'); ?>
        </p>
    <?php else: ?>
        <table class="widefat fixed striped">
            <thead>
                <tr>
                    <th style="width:60px;">ID</th>
                    <th><?php esc_html_e('Question', 'eas-quiz'); ?></th>
                    <th style="width:150px;"><?php esc_html_e('Type', 'eas-quiz'); ?></th>
                    <th style="width:80px;"><?php esc_html_e('Points', 'eas-quiz'); ?></th>
                    <th><?php esc_html_e('Pages', 'eas-quiz'); ?></th>
                    <th style="width:220px;"><?php esc_html_e('Actions', 'eas-quiz'); ?></th>
                </tr>
            </thead>

            <tbody>
            <?php foreach ($questions as $question): ?>
                <tr>
                    <td><?php echo (int) $question['id']; ?></td>

                    <td>
                        <?php
                        $text = wp_strip_all_tags($question['question_text']);
                        echo esc_html(wp_trim_words($text, 25, '...'));
                        ?>
                    </td>

                    <td>
                        <?php
                        $question_type = $question['type'] ?? '';
                        echo esc_html($type_labels[$question_type] ?? $question_type);
                        ?>
                    </td>

                    <td><?php echo esc_html($question['points'] ?? 0); ?></td>

                    <td>
                        <?php if (empty($question['pages'])): ?>
                            <em><?php esc_html_e('Not assigned to a page', 'eas-quiz'); ?></em>
                        <?php else: ?>
                            <?php foreach ($question['pages'] as $page): ?>
                                <div><?php echo esc_html($page['title']); ?></div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </td>

                    <td>
                        <a href="<?php echo esc_url(admin_url(
                            'admin.php?page=eas-questions&quiz_id=' . (int) $quiz['id'] . '&action=bank_edit&id=' . (int) $question['id']
                        )); ?>">
                            <?php esc_html_e('Edit', 'eas-quiz'); ?>
                        </a>
                        |
                        <a href="<?php echo esc_url(admin_url(
                            'admin-post.php?action=eas_preview_question&id=' . (int) $question['id']
                        )); ?>" target="_blank">
                            <?php esc_html_e('Preview', 'eas-quiz'); ?>
                        </a>
                        |
                        <a href="<?php echo esc_url(wp_nonce_url(
                            admin_url(
                                'admin-post.php?action=eas_delete_question&id=' . (int) $question['id']
                            ),
                            'eas_delete_question_' . (int) $question['id']
                        )); ?>"
                           onclick="return confirm('<?php echo esc_js(__('Delete this question permanently from the question bank?', 'eas-quiz')); ?>');">
                            <?php esc_html_e('Delete', 'eas-quiz'); ?>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>