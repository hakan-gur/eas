<?php

declare(strict_types=1);

namespace EAS\Audio;


if (!defined('ABSPATH')) {
    exit;
}


class AudioRepository
{


    private \wpdb $wpdb;



    public function __construct()
    {

        global $wpdb;

        $this->wpdb = $wpdb;

    }




    private function table(): string
    {

        return $this->wpdb->prefix . 'eas_audio';

    }





    public function create(

        int $response_id,

        string $file_path,

        int $duration = 0

    ): int
    {


        $this->wpdb->insert(

            $this->table(),

            array(

                'response_id' => $response_id,

                'file_path' => $file_path,

                'duration' => $duration,

                'created_at' => current_time('mysql')

            ),

            array(

                '%d',

                '%s',

                '%d',

                '%s'

            )

        );


        return (int) $this->wpdb->insert_id;


    }





    public function find_by_response(

        int $response_id

    ): ?array
    {


        // phpcs:disable WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter -- The table name uses the trusted WordPress prefix and fixed EAS suffix; the response ID is prepared.
        $audio = $this->wpdb->get_row(

            $this->wpdb->prepare(

                "SELECT *
                 FROM {$this->table()}
                 WHERE response_id = %d
                 LIMIT 1",

                $response_id

            ),

            ARRAY_A

        );
        // phpcs:enable WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter


        return $audio ?: null;


    }
    
    public function delete_by_response(
    int $response_id
): bool {

    $audio = $this->find_by_response(
        $response_id
    );

    if ($audio) {

        /*
         * Delete the physical audio file.
         */
        $file_path = $audio['file_path'] ?? '';

        if (!empty($file_path)) {

            $upload = wp_upload_dir();

            $full_path =
                trailingslashit(
                    $upload['basedir']
                )
                . 'eas-audio/'
                . basename($file_path);

            if (file_exists($full_path)) {

                wp_delete_file(
                    $full_path
                );

            }

        }

    }

    /*
     * Delete the audio database record.
     */
    return (bool) $this->wpdb->delete(

        $this->table(),

        array(
            'response_id' => $response_id
        ),

        array(
            '%d'
        )

    );
}


}