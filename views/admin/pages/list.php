<?php

if (!defined('ABSPATH')) {
    exit;
}

?>

<div class="wrap">

<h1>
<?php esc_html_e('Pages', 'eas-quiz'); ?>
</h1>

<p>

<strong>
<?php esc_html_e('Quiz:', 'eas-quiz'); ?>
</strong>

<?php echo esc_html(
    $quiz['title'] ?? __('Unknown quiz', 'eas-quiz')
); ?>

</p>

<p>

<a class="button"
href="<?php echo esc_url(
    admin_url('admin.php?page=eas-quizzes')
); ?>">

← <?php esc_html_e('Back to Quizzes', 'eas-quiz'); ?>

</a>

</p>

<a class="button button-primary"
href="<?php echo esc_url(
    admin_url(
        'admin.php?page=eas-pages&quiz_id=' .
        $quiz_id .
        '&action=add'
    )
); ?>">

<?php esc_html_e('Add New Page', 'eas-quiz'); ?>

</a>

<br><br>

<?php if (empty($pages)): ?>

<p>
<?php esc_html_e('No pages found.', 'eas-quiz'); ?>
</p>

<?php else: ?>

<table class="widefat eas-pages-table">

<thead>

<tr>

<th>
#
</th>

<th>
<?php esc_html_e('Page', 'eas-quiz'); ?>
</th>

<th>
<?php esc_html_e('Page Time', 'eas-quiz'); ?>
</th>

<th>
<?php esc_html_e('Questions', 'eas-quiz'); ?>
</th>

<th>
<?php esc_html_e('Points', 'eas-quiz'); ?>
</th>

<th>
<?php esc_html_e('Actions', 'eas-quiz'); ?>
</th>

</tr>

</thead>

<tbody>

<?php

$total_pages = count($pages);

foreach ($pages as $index => $page):

    $page_id = (int) $page['id'];
    $page_number = (int) $page['page_number'];

    $is_first = ($index === 0);
    $is_last = ($index === $total_pages - 1);

?>

<tr
    class="eas-page-row"
    draggable="true"
    data-page-id="<?php echo esc_attr($page_id); ?>"
>

<td>

<?php echo esc_html(
    $page_number
); ?>

</td>

<td>

<?php echo esc_html(
    $page['title']
); ?>

</td>
<td>

<?php

$page_time_limit = (int) ($page['page_time_limit'] ?? 0);

if ($page_time_limit > 0) {

    printf(
        /* translators: %d: page time limit in seconds. */
        esc_html__('%d sec', 'eas-quiz'),
        esc_html($page_time_limit)
    );

} else {

    echo '—';

}

?>

</td>
<td>

<?php echo esc_html(
    $page['question_count'] ?? 0
); ?>

</td>

<td>

<?php echo esc_html(
    number_format_i18n(
        (float) ($page['page_points'] ?? 0),
        2
    )
); ?>

</td>

<td>
&nbsp;
<a href="<?php echo esc_url(
    admin_url(
        'admin.php?page=eas-pages&quiz_id=' .
        $quiz_id .
        '&action=edit&id=' .
        $page_id
    )
); ?>">

<?php esc_html_e('Edit', 'eas-quiz'); ?>

</a>


|


<a href="<?php echo esc_url(
    admin_url(
        'admin.php?page=eas-questions&page_id=' .
        $page_id
    )
); ?>">

<?php esc_html_e('Questions', 'eas-quiz'); ?>

</a>


|


<a
class="eas-confirm-delete"
href="<?php echo esc_url(
    wp_nonce_url(
        admin_url(
            'admin-post.php?action=examenanto_delete_page&id=' .
            $page_id .
            '&quiz_id=' .
            $quiz_id
        ),
        'eas_delete_page_' . $page_id
    )
); ?>"
data-confirm="<?php echo esc_attr__('Delete this page and all of its questions permanently?', 'eas-quiz'); ?>"
>

<?php esc_html_e('Delete', 'eas-quiz'); ?>

</a>

</td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

<?php endif; ?>

</div>
