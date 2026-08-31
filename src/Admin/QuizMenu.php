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

            'EAS Quizzes',

            'EAS Quiz',

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

            'Quizzes',

            'Quizzes',

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


        if (
            isset($_GET['action'])
            &&
            $_GET['action'] === 'add'
        ) {

            $this->form->display();

            return;

        }



        if (
            isset($_GET['action'])
            &&
            $_GET['action'] === 'edit'
            &&
            isset($_GET['id'])
        ) {

            $this->form->display(
                (int) $_GET['id']
            );

            return;

        }



        if (
            isset($_GET['action'])
            &&
            $_GET['action'] === 'delete'
            &&
            isset($_GET['id'])
        ) {


            $this->repository->delete(
                (int) $_GET['id']
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


        echo '<h1>EAS Quizzes</h1>';



        echo '<a href="?page=eas-quizzes&action=add" class="button button-primary">';
        echo 'Add New Quiz';
        echo '</a>';



        echo '<br><br>';



        if (empty($quizzes)) {


            echo '<p>No quizzes found.</p>';


        } else {


            echo '<table class="widefat striped">';



            echo '<thead>';
            echo '<tr>';

            echo '<th>ID</th>';
            echo '<th>Title</th>';
            echo '<th>Attempts</th>';
            echo '<th>Time Limit</th>';
            echo '<th>Actions</th>';

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
                    ? 'Unlimited'
                    : esc_html($quiz['attempts_allowed']);

                echo '</td>';



                echo '<td>';

                echo ($quiz['time_limit'] == 0)
                    ? 'No limit'
                    : esc_html($quiz['time_limit']) . ' minutes';

                echo '</td>';



                echo '<td>';

                echo '<a href="?page=eas-quizzes&action=edit&id='
                    . esc_attr($quiz['id'])
                    . '">Edit</a>';

                echo ' | ';


                echo '<a href="?page=eas-quizzes&action=delete&id='
                    . esc_attr($quiz['id'])
                    . '" onclick="return confirm(\'Delete this quiz?\')">Delete</a>';


                echo '</td>';



                echo '</tr>';


            }



            echo '</tbody>';

            echo '</table>';


        }



        echo '</div>';


    }


}