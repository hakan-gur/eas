<?php

if (!defined('ABSPATH')) {
    exit;
}

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This only selects a read-only notice and does not perform an action or change data.
$eas_warning = (
    isset($_GET['eas_warning'])
    && is_string($_GET['eas_warning'])
)
    ? sanitize_key(wp_unslash($_GET['eas_warning']))
    : '';

?>


<div class="wrap">


<?php if ($eas_warning === 'missing_correct_answer') : ?>


<div class="notice notice-warning is-dismissible">

    <p>
        <?php esc_html_e('Warning: This question has no correct answer selected. It has been saved, but it cannot be graded correctly.', 'eas-quiz'); ?>
    </p>

</div>


<?php endif; ?>


<?php

settings_errors(
    'eas_question'
);

?>


<h1>
<?php esc_html_e('Questions', 'eas-quiz'); ?>
</h1>


<p>
<?php esc_html_e('Quiz:', 'eas-quiz'); ?> <?php echo esc_html($quiz_title ?? ''); ?>
</p>


<p>
<?php esc_html_e('Page:', 'eas-quiz'); ?> <?php echo esc_html($page_title ?? ''); ?>
</p>
<p>

<a class="button"
href="<?php echo esc_url(admin_url(

    'admin.php?page=eas-pages&quiz_id='
    .
    $page['quiz_id']

)); ?>">

← <?php esc_html_e('Back to Pages', 'eas-quiz'); ?>

</a>

</p>


<a class="button button-primary"
href="<?php echo esc_url(admin_url(

'admin.php?page=eas-questions&page_id=' .
$page_id .
'&action=add'

)); ?>">

<?php esc_html_e('Add New Question', 'eas-quiz'); ?>

</a>
<a class="button"
   href="<?php echo esc_url(admin_url(
       'admin.php?page=eas-questions&page_id=' .
       $page_id .
       '&action=add_existing'
   )); ?>">

    <?php esc_html_e('Add Existing Question', 'eas-quiz'); ?>

</a>


<br><br>




<?php if (empty($questions)): ?>


<p>
<?php esc_html_e('No questions found.', 'eas-quiz'); ?>
</p>


<?php else: ?>


<table class="widefat">


<thead>

<tr>
<th>#</th>
<th><?php esc_html_e('Order', 'eas-quiz'); ?></th>
<th><?php esc_html_e('Question', 'eas-quiz'); ?></th>
<th><?php esc_html_e('Type', 'eas-quiz'); ?></th>
<th><?php esc_html_e('Points', 'eas-quiz'); ?></th>
<th><?php esc_html_e('Status', 'eas-quiz'); ?></th>
<th><?php esc_html_e('Actions', 'eas-quiz'); ?></th>

</tr>

</thead>



<tbody>



<?php foreach ($questions as $index => $question): ?>


<tr>


<td>

<?php echo esc_html(

    $index + 1

); ?>

</td>


<td style="white-space: nowrap; width: 90px;">
    <?php
    $current_order = (int) (
        $question['page_sort_order'] ?? 0
    );
    ?>

    <?php if ($current_order > 1): ?>

        <a
            href="<?php echo esc_url(wp_nonce_url(
                admin_url(
                    'admin-post.php?action=eas_move_question_up'
                    . '&id='
                    . $question['id']
                    . '&page_id='
                    . $page_id
                ),
                'eas_move_question_up_' . $question['id']
            )); ?>"
            class="button"
            title="<?php esc_attr_e('Move question up', 'eas-quiz'); ?>"
        >↑</a>

    <?php endif; ?>

    <a
        href="<?php echo esc_url(wp_nonce_url(
            admin_url(
                'admin-post.php?action=eas_move_question_down'
                . '&id='
                . $question['id']
                . '&page_id='
                . $page_id
            ),
            'eas_move_question_down_' . $question['id']
        )); ?>"
        class="button"
        title="<?php esc_attr_e('Move question down', 'eas-quiz'); ?>"
    >↓</a>
</td>

<td>

<?php echo esc_html(

wp_trim_words(

    $question['question_text'],

    15

)

); ?>

</td>





<td>

<?php echo esc_html(

    $question['type']

); ?>

</td>





<td>

<?php echo esc_html(

number_format(

    (float) $question['points'],

    2

)

); ?>

</td>





<td>


<?php if (

    $question['type'] === 'multiple_choice'

) : ?>


<span style="
color:#155724;
font-weight:600;
">

✓ <?php esc_html_e('Auto graded', 'eas-quiz'); ?>

</span>



<?php elseif (

    $question['type'] === 'essay'

    ||

    $question['type'] === 'audio_response'

) : ?>


<span style="
color:#856404;
font-weight:600;
">

🟡 <?php esc_html_e('Manual grading', 'eas-quiz'); ?>

</span>



<?php else : ?>


<span style="
color:#555;
font-weight:600;
">

<?php esc_html_e('Review', 'eas-quiz'); ?>

</span>



<?php endif; ?>


</td>





<td>




<a href="<?php echo esc_url(admin_url(

'admin.php?page=eas-questions&page_id=' .
$page_id .
'&action=edit&id=' .
$question['id']

)); ?>">

<?php esc_html_e('Edit', 'eas-quiz'); ?>

</a>

|
<a href="<?php echo esc_url(admin_url(

'admin-post.php?action=eas_preview_question&id=' .
$question['id']

)); ?>"
target="_blank">

<?php esc_html_e('Preview', 'eas-quiz'); ?>

</a>

|


<a href="<?php echo esc_url(wp_nonce_url(

admin_url(

'admin-post.php?action=eas_delete_question&id=' .
$question['id'] .
'&page_id=' .
$page_id

),

'eas_delete_question_' . $question['id']

)); ?>"
onclick="return confirm('<?php echo esc_js(__('Remove this question from the page?', 'eas-quiz')); ?>');">

<?php esc_html_e('Remove', 'eas-quiz'); ?>

</a>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>
</div>
