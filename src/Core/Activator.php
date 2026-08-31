<?php

declare(strict_types=1);

namespace EAS\Core;


use EAS\Database\Upgrade;


if (!defined('ABSPATH')) {
    exit;
}


class Activator
{


    public static function activate(): void
    {


        Installer::install();


        Upgrade::run();


        self::create_required_pages();



        flush_rewrite_rules();


    }





    private static function create_required_pages(): void
    {


        $pages = array(


            array(

                'title'   => 'Attempt',

                'slug'    => 'attempt',

                'content' => '[eas_attempt]'

            ),


            array(

                'title'   => 'Quiz Results',

                'slug'    => 'quiz-result',

                'content' => '[eas_results]'

            )


        );





        foreach ($pages as $page) {


            $existing = get_page_by_path(

                $page['slug']

            );



            if ($existing) {


                continue;


            }





            $page_id = wp_insert_post(


                array(

                    'post_title'   => $page['title'],

                    'post_name'    => $page['slug'],

                    'post_content' => $page['content'],

                    'post_status'  => 'publish',

                    'post_type'    => 'page'


                )


            );



            if ($page_id && !is_wp_error($page_id)) {


                if ($page['slug'] === 'attempt') {


                    update_option(

                        'eas_attempt_page_id',

                        $page_id

                    );


                }



                if ($page['slug'] === 'quiz-result') {


                    update_option(

                        'eas_results_page_id',

                        $page_id

                    );


                }


            }


        }


    }


}