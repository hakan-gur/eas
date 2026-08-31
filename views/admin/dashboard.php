<?php

if (!defined('ABSPATH')) {
    exit;
}

?>


<div class="wrap eas-dashboard">


<h1>
English Assessment Suite
</h1>



<div style="
display:grid;
grid-template-columns:repeat(4, 1fr);
gap:20px;
margin-top:25px;
max-width:1000px;
">


<div style="
background:#fff;
border:1px solid #ddd;
padding:20px;
border-radius:8px;
">

<h2>
📚
</h2>

<p>
<strong>
Quizzes
</strong>
</p>

<p style="font-size:28px;">
<?php echo esc_html($quiz_count ?? 0); ?>
</p>

</div>



<div style="
background:#fff;
border:1px solid #ddd;
padding:20px;
border-radius:8px;
">

<h2>
📝
</h2>

<p>
<strong>
Questions
</strong>
</p>

<p style="font-size:28px;">
<?php echo esc_html($question_count ?? 0); ?>
</p>

</div>



<div style="
background:#fff;
border:1px solid #ddd;
padding:20px;
border-radius:8px;
">

<h2>
👥
</h2>

<p>
<strong>
Students
</strong>
</p>

<p style="font-size:28px;">
<?php echo esc_html($student_count ?? 0); ?>
</p>

</div>



<div style="
background:#fff;
border:1px solid #ddd;
padding:20px;
border-radius:8px;
">

<h2>
⏳
</h2>

<p>
<strong>
Pending grading
</strong>
</p>

<p style="font-size:28px;">
<?php echo esc_html($pending_grading ?? 0); ?>
</p>

</div>



</div>




<hr style="margin:35px 0;">



<h2>
Quick Actions
</h2>



<p>

<a class="button button-primary"
href="<?php echo admin_url(
    'admin.php?page=eas-quizzes'
); ?>">

📚 Manage Quizzes

</a>



<a class="button"
href="<?php echo admin_url(
    'admin.php?page=eas-attempts'
); ?>">

📝 View Attempts

</a>


</p>




<hr style="margin:35px 0;">



<h2>
System Information
</h2>


<table class="widefat"
style="max-width:700px;">


<tbody>


<tr>

<th>
Version
</th>

<td>
EAS 0.5.0
</td>

</tr>



<tr>

<th>
Status
</th>

<td>

<span style="
color:#15803d;
font-weight:bold;
">

✓ System operational

</span>

</td>

</tr>



</tbody>


</table>
<hr style="margin:35px 0;">


<h2>
Recent Activity
</h2>



<?php if (empty($recent_attempts)) : ?>


<p>
No completed attempts yet.
</p>



<?php else : ?>


<table class="widefat"
style="max-width:900px;">


<thead>

<tr>

<th>
Student
</th>

<th>
Quiz
</th>

<th>
Score
</th>

<th>
Completed
</th>

</tr>

</thead>


<tbody>


<?php foreach ($recent_attempts as $attempt) : ?>


<tr>


<td>

<?php echo esc_html(

    $attempt['display_name']

); ?>

</td>



<td>

<?php echo esc_html(

    $attempt['quiz_title']

); ?>

</td>



<td>

<?php echo esc_html(

    $attempt['score']

); ?>

</td>



<td>

<?php echo esc_html(

    $attempt['completed_at']

); ?>

</td>



</tr>


<?php endforeach; ?>


</tbody>


</table>


<?php endif; ?>


</div>