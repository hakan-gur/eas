<?php

declare(strict_types=1);

namespace EAS\Admin;


use EAS\Quiz\QuizRepository;


if (!defined('ABSPATH')) {
    exit;
}


class QuizMenu
{


    private QuizRepository $repository;

    private QuizForm $form;



    public function __construct()
    {

        $this->repository = new QuizRepository();

        $this->form = new QuizForm();


        add_action(
            'admin_menu',
            array(
                $this,
                'register'
            )
        );

    }



    public function register(): void
    {

        add_menu_page(

            __('EAS Quizzes', 'eas-quiz'),

            __('EAS Quiz', 'eas-quiz'),

            'eas_manage_quizzes',

            'eas-quizzes',

            array(
                $this,
                'page'
            ),

            'dashicons-welcome-learn-more',

            30

        );


        add_submenu_page(

            'eas-quizzes',

            __('Quizzes', 'eas-quiz'),

            __('Quizzes', 'eas-quiz'),

            'eas_manage_quizzes',

            'eas-quizzes',

            array(
                $this,
                'page'
            )

        );

    }



    public function page(): void
    {


        // These query parameters select the screen; deletion is separately nonce-protected below.
        // phpcs:disable WordPress.Security.NonceVerification.Recommended
        $action = isset($_GET['action']) ? sanitize_key(wp_unslash($_GET['action'])) : '';
        $quiz_id = isset($_GET['id']) ? absint(wp_unslash($_GET['id'])) : 0;
        // phpcs:enable WordPress.Security.NonceVerification.Recommended

        if ($action === 'add') {

            $this->form->display();

            return;

        }



        if (
            $action === 'edit'
            &&
            $quiz_id > 0
        ) {

            $this->form->display(
                $quiz_id
            );

            return;

        }



        if (
            $action === 'delete'
            &&
            $quiz_id > 0
        ) {


            check_admin_referer('eas_delete_quiz_' . $quiz_id);

            $this->repository->delete(
                $quiz_id
            );


            wp_safe_redirect(
                admin_url(
                    'admin.php?page=eas-quizzes'
                )
            );


            exit;

        }



        $quizzes = $this->repository->all();



        echo '<div class="wrap">';


        echo '<h1>' . esc_html__('EAS Quizzes', 'eas-quiz') . '</h1>';



        echo '<a href="?page=eas-quizzes&action=add" class="button button-primary">';
        echo esc_html__('Add New Quiz', 'eas-quiz');
        echo '</a>';



        echo '<br><br>';



        if (empty($quizzes)) {


            echo '<p>' . esc_html__('No quizzes found.', 'eas-quiz') . '</p>';


        } else {


            echo '<table class="widefat striped">';



            echo '<thead>';
            echo '<tr>';

            echo '<th>' . esc_html__('ID', 'eas-quiz') . '</th>';
            echo '<th>' . esc_html__('Title', 'eas-quiz') . '</th>';
            echo '<th>' . esc_html__('Attempts', 'eas-quiz') . '</th>';
            echo '<th>' . esc_html__('Time Limit', 'eas-quiz') . '</th>';
            echo '<th>' . esc_html__('Actions', 'eas-quiz') . '</th>';

            echo '</tr>';
            echo '</thead>';



            echo '<tbody>';



            foreach ($quizzes as $quiz) {


                echo '<tr>';


                echo '<td>';
                echo esc_html($quiz['id']);
                echo '</td>';



                echo '<td>';
                echo esc_html($quiz['title']);
                echo '</td>';



                echo '<td>';

                echo ($quiz['attempts_allowed'] == 0)
                    ? esc_html__('Unlimited', 'eas-quiz')
                    : esc_html($quiz['attempts_allowed']);

                echo '</td>';



                echo '<td>';

                echo ($quiz['time_limit'] == 0)
                    ? esc_html__('No limit', 'eas-quiz')
                    : esc_html($quiz['time_limit']) . ' ' . esc_html__('minutes', 'eas-quiz');

                echo '</td>';



                echo '<td>';

                echo '<a href="?page=eas-quizzes&action=edit&id='
                    . esc_attr($quiz['id'])
                    . '">' . esc_html__('Edit', 'eas-quiz') . '</a>';

                echo ' | ';


                echo '<a href="' . esc_url(wp_nonce_url(
                    add_query_arg(
                        array(
                            'page' => 'eas-quizzes',
                            'action' => 'delete',
                            'id' => absint($quiz['id']),
                        ),
                        admin_url('admin.php')
                    ),
                    'eas_delete_quiz_' . absint($quiz['id'])
                )) . '" onclick="return confirm(\''
                    . esc_js(__('Delete this quiz?', 'eas-quiz'))
                    . '\')">'
                    . esc_html__('Delete', 'eas-quiz')
                    . '</a>';


                echo '</td>';



                echo '</tr>';


            }



            echo '</tbody>';

            echo '</table>';


        }



        echo '</div>';


    }


}