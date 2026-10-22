<?php

declare(strict_types=1);

namespace EAS\Admin;


use EAS\Quiz\QuizRepository;


if (!defined('ABSPATH')) {
    exit;
}


class QuizForm
{


    private QuizRepository $repository;



    public function __construct()
    {

        $this->repository = new QuizRepository();

    }



    public function display(
        ?int $id = null
    ): void
    {


        $quiz = array(

            'title' => '',
            'description' => '',
            'attempts_allowed' => 1,
            'time_limit' => 0,
            'backward_navigation' => 1

        );



        if ($id) {


            $existing = $this->repository->find(
                $id
            );


            if ($existing) {

                $quiz = $existing;

            }

        }



        if (
            isset($_SERVER['REQUEST_METHOD'])
            && 'POST' === $_SERVER['REQUEST_METHOD']
        ) {

            check_admin_referer(
                'eas_save_quiz',
                'eas_nonce'
            );

            $post = wp_unslash($_POST);

            if (
                isset($post['eas_save_quiz'])
            ) {

                $this->save(
                    $id,
                    $post
                );

                return;

            }

        }



        echo '<div class="wrap">';



        echo '<h1>';

        echo $id
            ? esc_html__('Edit Quiz', 'eas-quiz')
            : esc_html__('Add New Quiz', 'eas-quiz');

        echo '</h1>';



        echo '<form method="post">';



        wp_nonce_field(
            'eas_save_quiz',
            'eas_nonce'
        );



        echo '<table class="form-table">';



        echo '<tr>';

        echo '<th>' . esc_html__('Quiz Name', 'eas-quiz') . '</th>';

        echo '<td>';

        echo '<input type="text" name="title" class="regular-text" value="';

        echo esc_attr(
            $quiz['title']
        );

        echo '" required>';

        echo '</td>';

        echo '</tr>';



        echo '<tr>';

        echo '<th>' . esc_html__('Description', 'eas-quiz') . '</th>';

        echo '<td>';

        wp_editor(

            $quiz['description'],

            'description',

            array(

                'textarea_name' => 'description',

                'textarea_rows' => 8,

                'media_buttons' => true

            )

        );

        echo '</td>';

        echo '</tr>';



        echo '<tr>';

        echo '<th>' . esc_html__('Attempts Allowed', 'eas-quiz') . '</th>';

        echo '<td>';

        echo '<select name="attempts_allowed">';



        echo '<option value="1" ';

        selected(
            $quiz['attempts_allowed'],
            1
        );

        echo '>' . esc_html__('1 attempt', 'eas-quiz') . '</option>';



        echo '<option value="0" ';

        selected(
            $quiz['attempts_allowed'],
            0
        );

        echo '>' . esc_html__('Unlimited', 'eas-quiz') . '</option>';



        echo '</select>';

        echo '</td>';

        echo '</tr>';



        echo '<tr>';

        echo '<th>' . esc_html__('Time Limit', 'eas-quiz') . '</th>';

        echo '<td>';

        echo '<input type="number" name="time_limit" value="';

        echo esc_attr(
            $quiz['time_limit']
        );

        echo '"> ' . esc_html__('minutes', 'eas-quiz');

        echo '<p class="description">';

        echo esc_html__('Enter 0 for no time limit.', 'eas-quiz');

        echo '</p>';

        echo '</td>';

        echo '</tr>';



        echo '<tr>';

        echo '<th>' . esc_html__('Backward Navigation', 'eas-quiz') . '</th>';

        echo '<td>';



        echo '<label>';

        echo '<input type="radio" name="backward_navigation" value="1" ';

        checked(
            $quiz['backward_navigation'],
            1
        );

        echo '> ' . esc_html__('Permitted', 'eas-quiz');

        echo '</label>';



        echo '<br>';



        echo '<label>';

        echo '<input type="radio" name="backward_navigation" value="0" ';

        checked(
            $quiz['backward_navigation'],
            0
        );

        echo '> ' . esc_html__('Not permitted', 'eas-quiz');

        echo '</label>';



        echo '</td>';

        echo '</tr>';



        echo '</table>';



        submit_button(
            __('Save Quiz', 'eas-quiz'),
            'primary',
            'eas_save_quiz'
        );



        echo '</form>';



        echo '</div>';

    }



    private function save(
        ?int $id,
        array $post
    ): void
    {


        $nonce = isset($post['eas_nonce']) && is_string($post['eas_nonce'])
            ? sanitize_text_field($post['eas_nonce'])
            : '';

        if (
            '' === $nonce
            || !wp_verify_nonce($nonce, 'eas_save_quiz')
        ) {

            return;

        }



        $title = isset($post['title']) && is_string($post['title'])
            ? sanitize_text_field($post['title'])
            : '';

        $description = isset($post['description']) && is_string($post['description'])
            ? wp_kses_post($post['description'])
            : '';

        $attempts_allowed = isset($post['attempts_allowed']) && is_scalar($post['attempts_allowed'])
            ? (int) $post['attempts_allowed']
            : 1;
        $attempts_allowed = in_array($attempts_allowed, array(0, 1), true)
            ? $attempts_allowed
            : 1;

        $time_limit = isset($post['time_limit']) && is_scalar($post['time_limit'])
            ? absint($post['time_limit'])
            : 0;

        $backward_navigation = isset($post['backward_navigation']) && is_scalar($post['backward_navigation'])
            ? (int) $post['backward_navigation']
            : 1;
        $backward_navigation = in_array($backward_navigation, array(0, 1), true)
            ? $backward_navigation
            : 1;

        $data = array(
            'title' => $title,
            'description' => $description,
            'attempts_allowed' => $attempts_allowed,
            'time_limit' => $time_limit,
            'backward_navigation' => $backward_navigation,



            'updated_at' => current_time(
                'mysql'
            )


        );



        if ($id) {


            $this->repository->update(

                $id,

                $data

            );


        } else {



            $data['created_by'] =

                get_current_user_id();



            $data['created_at'] =

                current_time(
                    'mysql'
                );



            $this->repository->create(

                $data

            );


        }



        wp_safe_redirect(

            admin_url(

                'admin.php?page=eas-quizzes'

            )

        );


        exit;

    }


}