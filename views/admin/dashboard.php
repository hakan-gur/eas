<?php

if (!defined('ABSPATH')) {
    exit;
}

?>

<div class="wrap eas-dashboard">

<h1>
<?php esc_html_e('English Assessment Suite', 'eas-quiz'); ?>
</h1>

<div style="
display:grid;
grid-template-columns:repeat(4, 1fr);
gap:20px;
margin-top:25px;
max-width:1000px;
">

<div style="background:#fff;border:1px solid #ddd;padding:20px;border-radius:8px;">
<h2>📚</h2>
<p><strong><?php esc_html_e('Quizzes', 'eas-quiz'); ?></strong></p>
<p style="font-size:28px;">
<?php echo esc_html($quiz_count ?? 0); ?>
</p>
</div>

<div style="background:#fff;border:1px solid #ddd;padding:20px;border-radius:8px;">
<h2>📝</h2>
<p><strong><?php esc_html_e('Questions', 'eas-quiz'); ?></strong></p>
<p style="font-size:28px;">
<?php echo esc_html($question_count ?? 0); ?>
</p>
</div>

<div style="background:#fff;border:1px solid #ddd;padding:20px;border-radius:8px;">
<h2>👥</h2>
<p><strong><?php esc_html_e('Students', 'eas-quiz'); ?></strong></p>
<p style="font-size:28px;">
<?php echo esc_html($student_count ?? 0); ?>
</p>
</div>

<div style="background:#fff;border:1px solid #ddd;padding:20px;border-radius:8px;">
<h2>⏳</h2>
<p><strong><?php esc_html_e('Pending grading', 'eas-quiz'); ?></strong></p>
<p style="font-size:28px;">
<?php echo esc_html($pending_grading ?? 0); ?>
</p>
</div>

</div>

<hr style="margin:35px 0;">

<h2><?php esc_html_e('Quick Actions', 'eas-quiz'); ?></h2>

<p>
<a class="button button-primary"
href="<?php echo esc_url(admin_url('admin.php?page=eas-quizzes')); ?>">
📚 <?php esc_html_e('Manage Quizzes', 'eas-quiz'); ?>
</a>

<a class="button"
href="<?php echo esc_url(admin_url('admin.php?page=eas-attempts')); ?>">
📝 <?php esc_html_e('View Attempts', 'eas-quiz'); ?>
</a>
</p>

<hr style="margin:35px 0;">

<h2><?php esc_html_e('System Information', 'eas-quiz'); ?></h2>

<table class="widefat" style="max-width:700px;">
<tbody>
<tr>
<th><?php esc_html_e('Version', 'eas-quiz'); ?></th>
<td><?php echo esc_html('EAS ' . EAS_VERSION); ?></td>
</tr>

<tr>
<th><?php esc_html_e('Status', 'eas-quiz'); ?></th>
<td>
<span style="color:#15803d;font-weight:bold;">
✓ <?php esc_html_e('System operational', 'eas-quiz'); ?>
</span>
</td>
</tr>
</tbody>
</table>

<hr style="margin:35px 0;">

<h2><?php esc_html_e('Recent Activity', 'eas-quiz'); ?></h2>

<?php if (empty($recent_attempts)) : ?>

<p><?php esc_html_e('No completed attempts yet.', 'eas-quiz'); ?></p>

<?php else : ?>

<table class="widefat" style="max-width:900px;">
<thead>
<tr>
<th><?php esc_html_e('Student', 'eas-quiz'); ?></th>
<th><?php esc_html_e('Quiz', 'eas-quiz'); ?></th>
<th><?php esc_html_e('Score', 'eas-quiz'); ?></th>
<th><?php esc_html_e('Completed', 'eas-quiz'); ?></th>
</tr>
</thead>
<tbody>

<?php foreach ($recent_attempts as $attempt) : ?>
<tr>
<td><?php echo esc_html($attempt['display_name']); ?></td>
<td><?php echo esc_html($attempt['quiz_title']); ?></td>
<td><?php echo esc_html($attempt['score']); ?></td>
<td><?php echo esc_html($attempt['completed_at']); ?></td>
</tr>
<?php endforeach; ?>

</tbody>
</table>

<?php endif; ?>

</div>