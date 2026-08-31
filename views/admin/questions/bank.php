<?php

if (!defined('ABSPATH')) {
    exit;
}

?>

<div class="wrap">

    <h1>
        Question Bank
    </h1>

    <p>
        Quiz:
        <strong>
            <?php echo esc_html($quiz_title); ?>
        </strong>
    </p>

    <p>

        <a class="button"
           href="<?php echo esc_url(
               admin_url(
                   'admin.php?page=eas-quizzes'
               )
           ); ?>">

            ← Back to Quizzes

        </a>

        <a class="button button-primary"
           href="<?php echo esc_url(
               admin_url(
                   'admin.php?page=eas-questions' .
                   '&quiz_id=' .
                   (int) $quiz['id'] .
                   '&action=bank_add'
               )
           ); ?>">

            ➕ Create New Question

        </a>

        <a
            class="button"
            href="<?php echo esc_url(
                admin_url(
                    'admin.php?page=eas-questions' .
                    '&quiz_id=' .
                    $quiz_id .
                    '&action=import'
                )
            ); ?>"
        >

            Import Questions (GIFT)

        </a>

        <a
            class="button"
            href="<?php echo esc_url(
                wp_nonce_url(
                    admin_url(
                        'admin-post.php?action=' .
                        'eas_export_questions' .
                        '&quiz_id=' .
                        (int) $quiz['id']
                    ),
                    'eas_export_questions_' .
                    (int) $quiz['id']
                )
            ); ?>"
        >

            Export Questions (GIFT)

        </a>

    </p>

    <hr>

    <?php if (empty($questions)): ?>

        <p>
            This quiz does not have any questions in its question bank yet.
        </p>

    <?php else: ?>

        <table class="widefat fixed striped">

            <thead>

                <tr>

                    <th style="width:60px;">
                        ID
                    </th>

                    <th>
                        Question
                    </th>

                    <th style="width:150px;">
                        Type
                    </th>

                    <th style="width:80px;">
                        Points
                    </th>

                    <th>
                        Pages
                    </th>

                    <th style="width:220px;">
                        Actions
                    </th>

                </tr>

            </thead>

            <tbody>

            <?php foreach ($questions as $question): ?>

                <tr>

                    <td>
                        <?php echo (int) $question['id']; ?>
                    </td>

                    <td>

                        <?php

                        $text = wp_strip_all_tags(
                            $question['question_text']
                        );

                        echo esc_html(
                            wp_trim_words(
                                $text,
                                25,
                                '...'
                            )
                        );

                        ?>

                    </td>

                    <td>
                        <?php echo esc_html(
                            $question['type'] ?? ''
                        ); ?>
                    </td>

                    <td>
                        <?php echo esc_html(
                            $question['points'] ?? 0
                        ); ?>
                    </td>

                    <td>

                        <?php if (
                            empty($question['pages'])
                        ): ?>

                            <em>
                                Not assigned to a page
                            </em>

                        <?php else: ?>

                            <?php foreach (
                                $question['pages']
                                as $page
                            ): ?>

                                <div>
                                    <?php echo esc_html(
                                        $page['title']
                                    ); ?>
                                </div>

                            <?php endforeach; ?>

                        <?php endif; ?>

                    </td>

                    <td>

                        <a href="<?php echo esc_url(
                            admin_url(
                                'admin.php?page=eas-questions' .
                                '&quiz_id=' .
                                (int) $quiz['id'] .
                                '&action=bank_edit&id=' .
                                (int) $question['id']
                            )
                        ); ?>">

                            Edit

                        </a>

                        |

                        <a href="<?php echo esc_url(
                            admin_url(
                                'admin-post.php?action=' .
                                'eas_preview_question&id=' .
                                (int) $question['id']
                            )
                        ); ?>"
                           target="_blank">

                            Preview

                        </a>

                        |

                        <a href="<?php echo esc_url(
                            wp_nonce_url(

                                admin_url(
                                    'admin-post.php?action=' .
                                    'eas_delete_question&id=' .
                                    (int) $question['id']
                                ),

                                'eas_delete_question_' .
                                (int) $question['id']

                            )
                        ); ?>"
                           onclick="return confirm(
                               'Delete this question permanently from the question bank?'
                           );">

                            Delete

                        </a>

                    </td>

                </tr>

            <?php endforeach; ?>

            </tbody>

        </table>

    <?php endif; ?>

</div>