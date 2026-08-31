<?php

if (!defined('ABSPATH')) {
    exit;
}

?>


<div class="wrap">


<?php if (

    isset($_GET['eas_warning'])

    &&

    $_GET['eas_warning'] === 'missing_correct_answer'

) : ?>


<div class="notice notice-warning is-dismissible">

    <p>
        Warning: This question has no correct answer selected. 
        It has been saved, but it cannot be graded correctly.
    </p>

</div>


<?php endif; ?>


<?php

settings_errors(
    'eas_question'
);

?>


<h1>
Questions
</h1>


<p>
Quiz: <?php echo esc_html($quiz_title ?? ''); ?>
</p>


<p>
Page: <?php echo esc_html($page_title ?? ''); ?>
</p>
<p>

<a class="button"
href="<?php echo admin_url(

    'admin.php?page=eas-pages&quiz_id='
    .
    $page['quiz_id']

); ?>">

← Back to Pages

</a>

</p>


<a class="button button-primary"
href="<?php echo admin_url(

'admin.php?page=eas-questions&page_id=' .
$page_id .
'&action=add'

); ?>">

Add New Question

</a>
<a class="button"
   href="<?php echo admin_url(
       'admin.php?page=eas-questions&page_id=' .
       $page_id .
       '&action=add_existing'
   ); ?>">

    Add Existing Question

</a>


<br><br>




<?php if (empty($questions)): ?>


<p>
No questions found.
</p>


<?php else: ?>


<table class="widefat">


<thead>

<tr>
<th>#</th>
<th>Order</th>
<th>Question</th>
<th>Type</th>
<th>Points</th>
<th>Status</th>
<th>Actions</th>

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
            href="<?php echo wp_nonce_url(
                admin_url(
                    'admin-post.php?action=eas_move_question_up'
                    . '&id='
                    . $question['id']
                    . '&page_id='
                    . $page_id
                ),
                'eas_move_question_up_' . $question['id']
            ); ?>"
            class="button"
            title="Move question up"
        >↑</a>

    <?php endif; ?>

    <a
        href="<?php echo wp_nonce_url(
            admin_url(
                'admin-post.php?action=eas_move_question_down'
                . '&id='
                . $question['id']
                . '&page_id='
                . $page_id
            ),
            'eas_move_question_down_' . $question['id']
        ); ?>"
        class="button"
        title="Move question down"
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

✓ Auto graded

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

🟡 Manual grading

</span>



<?php else : ?>


<span style="
color:#555;
font-weight:600;
">

Review

</span>



<?php endif; ?>


</td>





<td>



<a href="<?php echo admin_url(

'admin.php?page=eas-questions&page_id=' .
$page_id .
'&action=edit&id=' .
$question['id']

); ?>">

Edit

</a>

|
<a href="<?php echo admin_url(

'admin-post.php?action=eas_preview_question&id=' .
$question['id']

); ?>"
target="_blank">

Preview

</a>

|


<a href="<?php echo wp_nonce_url(

admin_url(

'admin-post.php?action=eas_delete_question&id=' .
$question['id'] .
'&page_id=' .
$page_id

),

'eas_delete_question_' . $question['id']

); ?>"
onclick="return confirm('Delete this question permanently?');">

Delete

</a>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>
</div>