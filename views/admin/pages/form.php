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
    ? 'Edit Page'
    : 'Add New Page';
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
Page Title
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
Page Time Limit (seconds)
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
Enter the maximum time allowed for this page in seconds.
<br>
For example: 5 = 5 seconds, 30 = 30 seconds, 60 = 1 minute.
<br>
Leave as 0 for an unlimited page.
</p>

</td>

</tr>

<tr>

<th>
<label for="content">
Content
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
Save Page
</button>

<a class="button"
   href="<?php echo esc_url(
       admin_url(
           'admin.php?page=eas-pages&quiz_id=' .
           $quiz_id
       )
   ); ?>">
Cancel
</a>

</p>

</form>

</div>