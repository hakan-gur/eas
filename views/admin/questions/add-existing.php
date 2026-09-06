<?php

if (!defined('ABSPATH')) {
    exit;
}

?>

<div class="wrap">

    <h1>
        <?php esc_html_e('Add Existing Question', 'eas-quiz'); ?>
    </h1>

    <p>
        <strong><?php esc_html_e('Quiz:', 'eas-quiz'); ?></strong>
        <?php echo esc_html($quiz_title); ?>
    </p>

    <p>
        <strong><?php esc_html_e('Page:', 'eas-quiz'); ?></strong>
        <?php echo esc_html($page_title); ?>
    </p>

    <p>
        <a class="button"
           href="<?php echo esc_url(admin_url(
               'admin.php?page=eas-questions&page_id=' .
               $page_id
           )); ?>">

            ← <?php esc_html_e('Back to Questions', 'eas-quiz'); ?>

        </a>
        <a class="button button-primary"
   href="<?php echo esc_url(admin_url(
       'admin.php?page=eas-questions&page_id=' .
       $page_id .
       '&action=add'
   )); ?>">

    <?php esc_html_e('Create New Question', 'eas-quiz'); ?>

</a>
    </p>

    <hr>

    <?php if (empty($question_bank)): ?>

        <p>
            <?php esc_html_e('There are no existing questions in this quiz.', 'eas-quiz'); ?>
        </p>

        <p>
            <a class="button button-primary"
               href="<?php echo esc_url(admin_url(
                   'admin.php?page=eas-questions&page_id=' .
                   $page_id .
                   '&action=add'
               )); ?>">

                <?php esc_html_e('Add New Question', 'eas-quiz'); ?>

            </a>
        </p>

    <?php else: ?>

        <table class="widefat striped">

            <thead>

                <tr>

                    <th>
                        <?php esc_html_e('Question', 'eas-quiz'); ?>
                    </th>

                    <th style="width:120px;">
                        <?php esc_html_e('Type', 'eas-quiz'); ?>
                    </th>

                    <th style="width:80px;">
                        <?php esc_html_e('Points', 'eas-quiz'); ?>
                    </th>

                    <th style="width:180px;">
                        <?php esc_html_e('Action', 'eas-quiz'); ?>
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
                            $type_labels = array(
                                'multiple_choice' => __('Multiple Choice', 'eas-quiz'),
                                'drag_drop_text' => __('Drag and Drop into Text', 'eas-quiz'),
                                'fill_blank' => __('Fill in the Blanks (C-Test)', 'eas-quiz'),
                                'description' => __('Description', 'eas-quiz'),
                                'essay' => __('Essay', 'eas-quiz'),
                                'audio_response' => __('Audio Response', 'eas-quiz'),
                            );

                            echo esc_html(
                                $type_labels[$question['type']] ?? $question['type']
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
                                    ✓ <?php esc_html_e('Already on this page', 'eas-quiz'); ?>
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
                                        <?php esc_html_e('Add to Page', 'eas-quiz'); ?>
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