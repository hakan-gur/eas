<?php

if (!defined('ABSPATH')) {
    exit;
}

?>
<?php if (isset($_GET['graded'])) : ?>

<div class="notice notice-success">

<p>

<?php esc_html_e('Grades saved successfully.', 'eas-quiz'); ?>

</p>

</div>

<?php endif; ?>

<div class="wrap">
<p>

<a class="button"
href="<?php echo esc_url(admin_url(
    'admin.php?page=eas-attempts'
)); ?>">

← <?php esc_html_e('Back to Attempts', 'eas-quiz'); ?>

</a>

</p><form method="post"
      action="<?php echo esc_url(admin_url('admin-post.php')); ?>">

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
<?php esc_html_e('Grade Attempt', 'eas-quiz'); ?>
</h1>



<h2>
<?php echo esc_html($attempt['display_name']); ?>
</h2>


<p>
<strong><?php esc_html_e('Quiz:', 'eas-quiz'); ?></strong>

<?php echo esc_html($attempt['quiz_title']); ?>

</p>


<p>
<strong><?php esc_html_e('Status:', 'eas-quiz'); ?></strong>

<?php echo esc_html($attempt['status']); ?>

</p>



<hr>




<?php if (empty($responses)) : ?>


<p>
<?php esc_html_e('No responses found.', 'eas-quiz'); ?>
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
<?php esc_html_e('Type:', 'eas-quiz'); ?>
</strong>

<?php echo esc_html(
    $response['type']
); ?>


</p>




<?php if ($response['type'] === 'audio_response') : ?>


<p>
<strong>
<?php esc_html_e('Student recording:', 'eas-quiz'); ?>
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
<?php esc_html_e('Student answer:', 'eas-quiz'); ?>
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
<?php esc_html_e('Points:', 'eas-quiz'); ?>
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
<?php esc_html_e('Feedback:', 'eas-quiz'); ?>
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
    __('Save Grades', 'eas-quiz')
); ?>


</form>

</div>