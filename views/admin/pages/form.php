<?php
if (!defined('ABSPATH')) {
    exit;
}

$title = '';
$content = '';
$page_id = 0;
$page_time_limit = 0;

if ($page) {
    $title = $page['title'];
    $content = $page['content'];
    $page_id = (int) $page['id'];
    $page_time_limit = (int) (
        $page['page_time_limit'] ?? 0
    );
}
?>

<div class="wrap">

<h1>
<?php echo $page_id > 0
    ? esc_html__('Edit Page', 'eas-quiz')
    : esc_html__('Add New Page', 'eas-quiz');
?>
</h1>

<form method="post"
      action="<?php echo esc_url(
          admin_url('admin-post.php')
      ); ?>">

<input type="hidden"
       name="action"
       value="eas_save_page">

<input type="hidden"
       name="quiz_id"
       value="<?php echo esc_attr(
           $quiz_id
       ); ?>">

<input type="hidden"
       name="page_id"
       value="<?php echo esc_attr(
           $page_id
       ); ?>">

<?php
wp_nonce_field(
    'eas_save_page'
);
?>

<table class="form-table">

<tr>

<th>
<label for="title">
<?php esc_html_e('Page Title', 'eas-quiz'); ?>
</label>
</th>

<td>

<input type="text"
       name="title"
       id="title"
       class="regular-text"
       value="<?php echo esc_attr(
           $title
       ); ?>">

</td>

</tr>

<tr>

<th>
<label for="page_time_limit">
<?php esc_html_e('Page Time Limit (seconds)', 'eas-quiz'); ?>
</label>
</th>

<td>

<input type="number"
       name="page_time_limit"
       id="page_time_limit"
       value="<?php echo esc_attr(
           $page_time_limit
       ); ?>"
       min="0"
       step="1">

<p class="description">
<?php esc_html_e('Enter the maximum time allowed for this page in seconds.', 'eas-quiz'); ?>
<br>
<?php esc_html_e('For example: 5 = 5 seconds, 30 = 30 seconds, 60 = 1 minute.', 'eas-quiz'); ?>
<br>
<?php esc_html_e('Leave as 0 for an unlimited page.', 'eas-quiz'); ?>
</p>

</td>

</tr>

<tr>

<th>
<label for="content">
<?php esc_html_e('Content', 'eas-quiz'); ?>
</label>
</th>

<td>

<?php
wp_editor(
    $content,
    'content',
    array(
        'textarea_name' => 'content',
        'media_buttons' => true,
        'textarea_rows' => 12
    )
);
?>

</td>

</tr>

</table>

<p>

<button type="submit"
        class="button button-primary">
<?php esc_html_e('Save Page', 'eas-quiz'); ?>
</button>

<a class="button"
   href="<?php echo esc_url(
       admin_url(
           'admin.php?page=eas-pages&quiz_id=' .
           $quiz_id
       )
   ); ?>">
<?php esc_html_e('Cancel', 'eas-quiz'); ?>
</a>

</p>

</form>

</div>