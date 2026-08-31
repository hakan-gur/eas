<?php

if (!defined('ABSPATH')) {
    exit;
}

?>

<div class="wrap">

    <h1>
        Add Existing Question
    </h1>

    <p>
        <strong>Quiz:</strong>
        <?php echo esc_html($quiz_title); ?>
    </p>

    <p>
        <strong>Page:</strong>
        <?php echo esc_html($page_title); ?>
    </p>

    <p>
        <a class="button"
           href="<?php echo admin_url(
               'admin.php?page=eas-questions&page_id=' .
               $page_id
           ); ?>">

            ← Back to Questions

        </a>
        <a class="button button-primary"
   href="<?php echo admin_url(
       'admin.php?page=eas-questions&page_id=' .
       $page_id .
       '&action=add'
   ); ?>">

    Create New Question

</a>
    </p>

    <hr>

    <?php if (empty($question_bank)): ?>

        <p>
            There are no existing questions in this quiz.
        </p>

        <p>
            <a class="button button-primary"
               href="<?php echo admin_url(
                   'admin.php?page=eas-questions&page_id=' .
                   $page_id .
                   '&action=add'
               ); ?>">

                Add New Question

            </a>
        </p>

    <?php else: ?>

        <table class="widefat striped">

            <thead>

                <tr>

                    <th>
                        Question
                    </th>

                    <th style="width:120px;">
                        Type
                    </th>

                    <th style="width:80px;">
                        Points
                    </th>

                    <th style="width:180px;">
                        Action
                    </th>

                </tr>

            </thead>

            <tbody>

                <?php foreach ($question_bank as $question): ?>

                    <?php
                    $question_id = (int) $question['id'];
                    $already_assigned =
                        isset($assigned[$question_id]);
                    ?>

                    <tr>

                        <td>

                            <?php
                            echo wp_kses_post(
                                $question['question_text']
                            );
                            ?>

                        </td>

                        <td>
                            <?php
                            echo esc_html(
                                $question['type']
                            );
                            ?>
                        </td>

                        <td>
                            <?php
                            echo esc_html(
                                $question['points']
                            );
                            ?>
                        </td>

                        <td>

                            <?php if ($already_assigned): ?>

                                <span>
                                    ✓ Already on this page
                                </span>

                            <?php else: ?>

                                <form method="post"
                                      action="<?php echo esc_url(
                                          admin_url(
                                              'admin-post.php'
                                          )
                                      ); ?>">

                                    <input
                                        type="hidden"
                                        name="action"
                                        value="eas_add_existing_question"
                                    >

                                    <input
                                        type="hidden"
                                        name="page_id"
                                        value="<?php echo esc_attr(
                                            $page_id
                                        ); ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="question_id"
                                        value="<?php echo esc_attr(
                                            $question_id
                                        ); ?>"
                                    >

                                    <?php
                                    wp_nonce_field(
                                        'eas_add_existing_question'
                                    );
                                    ?>

                                    <button
                                        type="submit"
                                        class="button button-primary"
                                    >
                                        Add to Page
                                    </button>

                                </form>

                            <?php endif; ?>

                        </td>

                    </tr>

                <?php endforeach; ?>

            </tbody>

        </table>

    <?php endif; ?>

</div>