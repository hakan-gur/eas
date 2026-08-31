<?php

if (!defined('ABSPATH')) {
    exit;
}

?>
<?php if (isset($_GET['graded'])) : ?>

<div class="notice notice-success">

<p>

Grades saved successfully.

</p>

</div>

<?php endif; ?>

<div class="wrap">
<p>

<a class="button"
href="<?php echo admin_url(
    'admin.php?page=eas-attempts'
); ?>">

← Back to Attempts

</a>

</p><form method="post"
      action="<?php echo admin_url('admin-post.php'); ?>">

<input type="hidden"
       name="action"
       value="eas_save_grades">


<input type="hidden"
       name="attempt_id"
       value="<?php echo esc_attr($attempt['id']); ?>">


<?php wp_nonce_field(
    'eas_save_grades'
); ?>

<h1>
Grade Attempt
</h1>



<h2>
<?php echo esc_html($attempt['display_name']); ?>
</h2>


<p>
<strong>Quiz:</strong>

<?php echo esc_html($attempt['quiz_title']); ?>

</p>


<p>
<strong>Status:</strong>

<?php echo esc_html($attempt['status']); ?>

</p>



<hr>




<?php if (empty($responses)) : ?>


<p>
No responses found.
</p>



<?php else : ?>



<?php foreach ($responses as $response) : ?>



<div style="margin-bottom:30px;">



<h2>

<?php echo esc_html(
    $response['question_text']
); ?>

</h2>



<p>

<strong>
Type:
</strong>

<?php echo esc_html(
    $response['type']
); ?>


</p>




<?php if ($response['type'] === 'audio_response') : ?>


<p>
<strong>
Student recording:
</strong>
</p>


<audio controls>

<source
src="<?php echo esc_url(
    wp_upload_dir()['baseurl']
    . '/eas-audio/'
    . $response['answer']
); ?>"
type="audio/webm">


</audio>



<?php else : ?>



<p>

<strong>
Student answer:
</strong>

</p>


<div style="padding:10px;background:#f5f5f5;">

<?php echo esc_html(
    $response['answer']
); ?>

</div>



<?php endif; ?>




<p>

<strong>
Points:
</strong>

<input type="number"

       name="points_<?php echo esc_attr($response['item_id']); ?>"

       value="<?php echo esc_attr(
           $response['points_awarded']
       ); ?>"

       min="0"

       max="<?php echo esc_attr(
           $response['max_points']
       ); ?>"

       step="0.01">


/

<?php echo esc_html(
    $response['max_points']
); ?>


</p>



<p>

<strong>
Feedback:
</strong>

</p>


<textarea

name="feedback_<?php echo esc_attr($response['item_id']); ?>"

rows="4"

style="width:100%;"

><?php echo esc_textarea(

    $response['teacher_feedback'] ?? ''

); ?></textarea>



</div>


<hr>



<?php endforeach; ?>


<?php endif; ?>
<?php submit_button(
    'Save Grades'
); ?>


</form>

</div>