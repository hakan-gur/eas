<p>

<a class="button"
href="<?php echo admin_url(
    'admin.php?page=eas-quizzes'
); ?>">

← Back to Quizzes

</a>

</p>
<?php if (empty($attempts)) : ?>

<p>
No completed attempts found.
</p>


<?php else : ?>
<div class="wrap">

<h1>
Attempts
</h1>
<form method="get">

<input type="hidden"
       name="page"
       value="eas-attempts">


<label>
Quiz:
</label>


<select name="quiz_id">

<option value="0">
All quizzes
</option>


<?php foreach ($quizzes as $quiz) : ?>

<option value="<?php echo esc_attr($quiz['id']); ?>"
<?php selected(
    $quiz_id,
    $quiz['id']
); ?>>

<?php echo esc_html($quiz['title']); ?>

</option>


<?php endforeach; ?>

</select>



<label style="margin-left:20px;">

Status:

</label>


<select name="status">

<option value="">
All
</option>


<option value="completed"
<?php selected(
    $status,
    'completed'
); ?>>

Completed

</option>


</select>



<?php submit_button(
    'Filter',
    'secondary',
    '',
    false
); ?>


</form>


<br>
<table class="widefat">


<thead>

<tr>

<th>Student</th>
<th>Quiz</th>
<th>Status</th>
<th>Score</th>
<th>Started</th>
<th>Completed</th>
<th>Duration</th>
<th>Action</th>
</tr>

</thead>



<tbody>


<?php foreach ($attempts as $attempt) : ?>


<tr>


<td>
<?php echo esc_html($attempt['display_name']); ?>
</td>


<td>
<?php echo esc_html($attempt['quiz_title']); ?>
</td>


<td>

<?php if ($attempt['grading_status'] === 'Needs grading') : ?>

<span style="
display:inline-block;
padding:4px 10px;
background:#fff3cd;
color:#856404;
border:1px solid #ffeeba;
border-radius:12px;
font-weight:600;
font-size:12px;
">
🟡 Needs grading
</span>

<?php else : ?>

<span style="
display:inline-block;
padding:4px 10px;
background:#d4edda;
color:#155724;
border:1px solid #c3e6cb;
border-radius:12px;
font-weight:600;
font-size:12px;
">
🟢 Graded
</span>

<?php endif; ?>

</td>


<td>

<?php echo esc_html(

number_format(
    (float) $attempt['score'],
    1
)

); ?>

/

<?php echo esc_html(

number_format(
    (float) $attempt['max_points'],
    1
)

); ?>


<br>


<?php

if ((float) $attempt['max_points'] > 0) {

    echo esc_html(

number_format(
    (
        (float) $attempt['score']
        /
        (float) $attempt['max_points']
    ) * 100,
    0
)

    );

    echo '%';

}

?>

</td>
<td>

<?php echo esc_html(
    $attempt['started_at']
); ?>

</td>


<td>

<?php echo esc_html(
    $attempt['completed_at']
); ?>

</td>


<td>

<?php echo esc_html(
    $attempt['duration']
); ?>

</td>

<td>
<?php if ($attempt['grading_status'] === 'Needs grading') : ?>
<a class="button button-primary"
   href="<?php echo admin_url(
'admin.php?page=eas-attempt-grade&attempt_id=' . $attempt['id']
); ?>">
📝 Grade
</a>
<?php else : ?>
<a class="button"
   href="<?php echo admin_url(
'admin.php?page=eas-attempt-grade&attempt_id=' . $attempt['id']
); ?>">
👁 Review
</a>
<?php endif; ?>

<?php
$delete_url = wp_nonce_url(
    admin_url(
        'admin-post.php?action=eas_delete_attempt&id='
        . (int) $attempt['id']
    ),
    'eas_delete_attempt_' . (int) $attempt['id']
);
?>

<a class="button"
   style="margin-left:5px;"
   href="<?php echo esc_url($delete_url); ?>"
   onclick="return confirm(
       'Are you sure you want to permanently delete this student attempt? All answers and associated audio recordings will also be deleted.'
   );">
    🗑 Delete
</a>

</td>

</tr>


<?php endforeach; ?>


</tbody>


</table>

</div>
<?php endif; ?>