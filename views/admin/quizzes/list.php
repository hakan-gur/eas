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


<h1>
Quizzes
</h1>



<p>

<a class="button button-primary"

href="<?php echo admin_url(

    'admin.php?page=eas-quizzes&action=add'

); ?>">

➕ Add New Quiz

</a>

</p>



<?php if (empty($quizzes)) : ?>


<div class="notice notice-info">

<p>
No quizzes found.
</p>

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

<th>
Quiz Title
</th>

<th>
Pages
</th>

<th>
Questions
</th>

<th>
Points
</th>

<th>
Shortcode
</th>

<th>
Actions
</th>


</tr>

</thead>




<tbody>


<?php foreach ($quizzes as $quiz) : ?>


<tr>


<td>

<strong>

<?php echo esc_html(

    $quiz['title']

); ?>

</strong>

</td>

<td>

<?php echo esc_html(

    $quiz['page_count']

); ?>

</td>


<td>

<?php echo esc_html(

    $quiz['question_count']

); ?>

</td>


<td>

<?php echo esc_html(

    number_format(

        (float) $quiz['total_points'],

        2

    )

); ?>

</td>


<td>

<code>

<?php echo esc_html(

    '[eas_quiz id="' . $quiz['id'] . '"]'

); ?>

</code>

</td>




<td style="white-space:nowrap;">



<a class="button"

href="<?php echo admin_url(

    'admin.php?page=eas-pages&quiz_id=' .

    $quiz['id']

); ?>">

📄 Pages

</a>

<a class="button"
   href="<?php echo admin_url(
       'admin.php?page=eas-questions&quiz_id=' .
       $quiz['id'] .
       '&action=bank'
   ); ?>">

    ❓ Questions

</a>


<a class="button"

href="<?php echo admin_url(

    'admin.php?page=eas-quizzes&action=edit&id=' .

    $quiz['id']

); ?>">

✏ Edit

</a>

<a class="button button-link-delete" href="<?php echo wp_nonce_url(

    admin_url(

        'admin-post.php?action=eas_duplicate_quiz&id=' .

        $quiz['id']

    ),

    'eas_duplicate_quiz_' . $quiz['id']

); ?>">

📄 Duplicate

</a>


<a class="button button-link-delete"
href="<?php echo wp_nonce_url(

    admin_url(

        'admin-post.php?action=eas_delete_quiz&id=' .

        $quiz['id']

    ),

    'eas_delete_quiz_' . $quiz['id']

); ?>"
onclick="return confirm('Delete this quiz and ALL of its pages, questions, answers, attempts, and responses permanently?');">

🗑 Delete

</a>



</td>


</tr>



<?php endforeach; ?>


</tbody>


</table>



<?php endif; ?>


</div>