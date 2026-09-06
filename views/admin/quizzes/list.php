<?php

if (!defined('ABSPATH')) {
    exit;
}

?>
<style>
.eas-quizzes-table th:first-child,
.eas-quizzes-table td:first-child {
    width: 30%;
}

.eas-quizzes-table th:last-child,
.eas-quizzes-table td:last-child {
    width: 40%;
    white-space: nowrap;
}

.eas-quizzes-table td:first-child {
    word-break: break-word;
}
</style>

<div class="wrap eas-quizzes">

<h1><?php esc_html_e('Quizzes', 'eas-quiz'); ?></h1>

<p>
<a class="button button-primary"
href="<?php echo esc_url(admin_url('admin.php?page=eas-quizzes&action=add')); ?>">
➕ <?php esc_html_e('Add New Quiz', 'eas-quiz'); ?>
</a>
</p>

<?php if (empty($quizzes)) : ?>

<div class="notice notice-info">
<p><?php esc_html_e('No quizzes found.', 'eas-quiz'); ?></p>
</div>

<?php else : ?>

<table class="widefat eas-quizzes-table">
<colgroup>
<col style="width:30%;">
<col style="width:8%;">
<col style="width:10%;">
<col style="width:10%;">
<col style="width:17%;">
<col style="width:25%;">
</colgroup>

<thead>
<tr>
<th><?php esc_html_e('Quiz Title', 'eas-quiz'); ?></th>
<th><?php esc_html_e('Pages', 'eas-quiz'); ?></th>
<th><?php esc_html_e('Questions', 'eas-quiz'); ?></th>
<th><?php esc_html_e('Points', 'eas-quiz'); ?></th>
<th><?php esc_html_e('Shortcode', 'eas-quiz'); ?></th>
<th><?php esc_html_e('Actions', 'eas-quiz'); ?></th>
</tr>
</thead>

<tbody>

<?php foreach ($quizzes as $quiz) : ?>
<tr>
<td>
<strong><?php echo esc_html($quiz['title']); ?></strong>
</td>
<td><?php echo esc_html($quiz['page_count']); ?></td>
<td><?php echo esc_html($quiz['question_count']); ?></td>
<td>
<?php echo esc_html(
    number_format_i18n(
        (float) $quiz['total_points'],
        2
    )
); ?>
</td>
<td>
<code><?php echo esc_html('[eas_quiz id="' . $quiz['id'] . '"]'); ?></code>
</td>
<td style="white-space:nowrap;">

<a class="button"
href="<?php echo esc_url(admin_url('admin.php?page=eas-pages&quiz_id=' . $quiz['id'])); ?>">
📄 <?php esc_html_e('Pages', 'eas-quiz'); ?>
</a>

<a class="button"
href="<?php echo esc_url(admin_url('admin.php?page=eas-questions&quiz_id=' . $quiz['id'] . '&action=bank')); ?>">
❓ <?php esc_html_e('Questions', 'eas-quiz'); ?>
</a>

<a class="button"
href="<?php echo esc_url(admin_url('admin.php?page=eas-quizzes&action=edit&id=' . $quiz['id'])); ?>">
✏ <?php esc_html_e('Edit', 'eas-quiz'); ?>
</a>

<a class="button button-link-delete"
href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=eas_duplicate_quiz&id=' . $quiz['id']), 'eas_duplicate_quiz_' . $quiz['id'])); ?>">
📄 <?php esc_html_e('Duplicate', 'eas-quiz'); ?>
</a>

<a class="button button-link-delete"
href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=eas_delete_quiz&id=' . $quiz['id']), 'eas_delete_quiz_' . $quiz['id'])); ?>"
onclick="return confirm('<?php echo esc_js(__('Delete this quiz and ALL of its pages, questions, answers, attempts, and responses permanently?', 'eas-quiz')); ?>');">
🗑 <?php esc_html_e('Delete', 'eas-quiz'); ?>
</a>

</td>
</tr>
<?php endforeach; ?>

</tbody>
</table>

<?php endif; ?>

</div>