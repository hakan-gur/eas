<?php

if (!defined('ABSPATH')) {
    exit;
}

?>

<style>

.eas-pages-table th:first-child,
.eas-pages-table td:first-child {
    width: 5%;
}

.eas-pages-table th:nth-child(2),
.eas-pages-table td:nth-child(2) {
    width: 30%;
}

.eas-pages-table th:nth-child(3),
.eas-pages-table td:nth-child(3),
.eas-pages-table th:nth-child(4),
.eas-pages-table td:nth-child(4) {
    width: 10%;
}

.eas-pages-table th:last-child,
.eas-pages-table td:last-child {
    width: 30%;
    white-space: nowrap;
}

.eas-page-row {
    cursor: grab;
}

.eas-page-row:active {
    cursor: grabbing;
}

.eas-page-row.dragging {
    opacity: 0.5;
}

.eas-page-row.drag-over {
    border-top: 3px solid #2271b1;
}

.eas-page-drag {
    width: 5%;
    text-align: center;
    color: #777;
    font-size: 18px;
}

</style>

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
        esc_html__('%d sec', 'eas-quiz'),
        $page_time_limit
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
href="<?php echo esc_url(
    wp_nonce_url(
        admin_url(
            'admin-post.php?action=eas_delete_page&id=' .
            $page_id .
            '&quiz_id=' .
            $quiz_id
        ),
        'eas_delete_page_' . $page_id
    )
); ?>"
onclick="return confirm('<?php echo esc_js(__('Delete this page and all of its questions permanently?', 'eas-quiz')); ?>');"
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
<script>

document.addEventListener('DOMContentLoaded', function () {

    const tableBody = document.querySelector(
        '.eas-pages-table tbody'
    );

    if (!tableBody) {
        return;
    }

    let draggedRow = null;

    tableBody.addEventListener(
        'dragstart',
        function (event) {

            const row = event.target.closest(
                '.eas-page-row'
            );

            if (!row) {
                return;
            }

            draggedRow = row;

            row.classList.add(
                'dragging'
            );

            event.dataTransfer.effectAllowed =
                'move';

            event.dataTransfer.setData(
                'text/plain',
                row.dataset.pageId
            );
        }
    );

    tableBody.addEventListener(
        'dragend',
        function () {

            if (draggedRow) {

                draggedRow.classList.remove(
                    'dragging'
                );
            }

            document
                .querySelectorAll(
                    '.eas-page-row.drag-over'
                )
                .forEach(function (row) {

                    row.classList.remove(
                        'drag-over'
                    );

                });

            draggedRow = null;
        }
    );

    tableBody.addEventListener(
        'dragover',
        function (event) {

            event.preventDefault();

            const row = event.target.closest(
                '.eas-page-row'
            );

            if (
                !row ||
                row === draggedRow
            ) {
                return;
            }

            document
                .querySelectorAll(
                    '.eas-page-row.drag-over'
                )
                .forEach(function (item) {

                    item.classList.remove(
                        'drag-over'
                    );

                });

            row.classList.add(
                'drag-over'
            );

            const rect =
                row.getBoundingClientRect();

            const middle =
                rect.top +
                (rect.height / 2);

            if (event.clientY < middle) {

                tableBody.insertBefore(
                    draggedRow,
                    row
                );

            } else {

                tableBody.insertBefore(
                    draggedRow,
                    row.nextSibling
                );
            }

            row.classList.remove(
                'drag-over'
            );
        }
    );

    tableBody.addEventListener(
        'drop',
        function (event) {

            event.preventDefault();

            if (!draggedRow) {
                return;
            }

            const pageIds = Array.from(
                tableBody.querySelectorAll(
                    '.eas-page-row'
                )
            ).map(function (row) {

                return row.dataset.pageId;

            });

            const form =
                document.createElement('form');

            form.method = 'POST';

            form.action =
                '<?php echo esc_url(
                    admin_url('admin-post.php')
                ); ?>';

            const actionInput =
                document.createElement('input');

            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value =
                'eas_reorder_pages';

            form.appendChild(
                actionInput
            );

            const quizInput =
                document.createElement('input');

            quizInput.type = 'hidden';
            quizInput.name = 'quiz_id';
            quizInput.value =
                '<?php echo esc_attr($quiz_id); ?>';

            form.appendChild(
                quizInput
            );

            const nonceInput =
                document.createElement('input');

            nonceInput.type = 'hidden';
            nonceInput.name = '_wpnonce';
            nonceInput.value =
                '<?php echo esc_attr(
                    wp_create_nonce(
                        'eas_reorder_pages_' . $quiz_id
                    )
                ); ?>';

            form.appendChild(
                nonceInput
            );

            pageIds.forEach(function (pageId) {

                const input =
                    document.createElement('input');

                input.type = 'hidden';
                input.name = 'page_ids[]';
                input.value = pageId;

                form.appendChild(
                    input
                );

            });

            document.body.appendChild(
                form
            );

            form.submit();
        }
    );

});

</script>