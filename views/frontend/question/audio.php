<?php

if (!defined('ABSPATH')) {
    exit;
}

?>

<div class="eas-question eas-audio-question">

    <h3>
        <?php echo wp_kses_post($question['question_text']); ?>
    </h3>


    <div class="eas-audio-response">
<?php

$audio_settings = json_decode(
    $question['settings'] ?? '',
    true
);

$delay = (int) ($audio_settings['audio_delay_seconds'] ?? 10);
$prepare = (int) ($audio_settings['audio_prepare_seconds'] ?? 5);
$duration = (int) ($audio_settings['audio_duration_seconds'] ?? 45);

?>

        <p class="eas-speaking-message">
            <?php esc_html_e('Listen carefully. Your response will start automatically.', 'eas-quiz'); ?>
        </p>

            <div class="eas-speaking-countdown"
     style="display:none;">

    <div class="eas-progress-label">
        <?php esc_html_e('Preparing...', 'eas-quiz'); ?>
    </div>

    <div class="eas-progress-bar">

        <div class="eas-progress-fill"></div>

    </div>

    <div class="eas-progress-time">
        <?php esc_html_e('0 seconds remaining', 'eas-quiz'); ?>
    </div>

</div>



        <p class="eas-speaking-status">

            <!-- Status is populated by JavaScript. -->

        </p>



        <input type="hidden"

               class="eas-audio-file"

               name="question_<?php echo esc_attr($question['id']); ?>"

               value="">



    </div>

<script>

(function(){
    const container =
        document.currentScript.closest('.eas-audio-question');


    const status =
        container.querySelector('.eas-speaking-status');


    const hidden =
        container.querySelector('.eas-audio-file');


    let mediaRecorder;

    let chunks = [];

function startCountdown(seconds, callback, label){

    const box =
        container.querySelector(
            '.eas-speaking-countdown'
        );

    const fill =
        container.querySelector(
            '.eas-progress-fill'
        );

    const time =
        container.querySelector(
            '.eas-progress-time'
        );

    const title =
        container.querySelector(
            '.eas-progress-label'
        );


    box.style.display = 'block';


    title.textContent = label;


    let remaining = seconds;


    function updateBar(){

        const percentage =
            (remaining / seconds) * 100;


        fill.style.width =
            percentage + '%';


        time.textContent =
            remaining +
            ' ' +
            (remaining === 1
                ? '<?php echo esc_js(__('second remaining', 'eas-quiz')); ?>'
                : '<?php echo esc_js(__('seconds remaining', 'eas-quiz')); ?>');

    }


    updateBar();


    const timer = setInterval(function(){


        remaining--;


        updateBar();



        if (remaining <= 0) {

            clearInterval(timer);


            fill.style.width = '0%';


            time.textContent =
                '0 <?php echo esc_js(__('seconds remaining', 'eas-quiz')); ?>';


            setTimeout(function(){

                box.style.display = 'none';

                callback();

            },500);

        }


    },1000);

}
    function startRecording(){

    startCountdown(
        <?php echo $duration; ?>,
        function(){

        },
        "<?php echo esc_js(__('Recording', 'eas-quiz')); ?>"
    );


    status.textContent =
        "<?php echo esc_js(__('Recording...', 'eas-quiz')); ?>";


        navigator.mediaDevices.getUserMedia({
            audio:true
        })

        .then(stream => {


            mediaRecorder =
                new MediaRecorder(stream);



            mediaRecorder.start();



            mediaRecorder.ondataavailable =
                e => {

                    chunks.push(e.data);

                };



            mediaRecorder.onstop =
                uploadAudio;



            setTimeout(function(){


                mediaRecorder.stop();


                stream.getTracks().forEach(
                    track => track.stop()
                );



                status.textContent =
                    "<?php echo esc_js(__('Uploading...', 'eas-quiz')); ?>";


            }, <?php echo $duration * 1000; ?>);



        })


        .catch(error => {


            console.error(error);


            status.textContent =
                "<?php echo esc_js(__('Microphone permission denied.', 'eas-quiz')); ?>";

        });


    }






    function uploadAudio(){

    const blob = new Blob(
        chunks,
        {
            type: 'audio/webm'
        }
    );

    const formData = new FormData();

    formData.append(
        'action',
        'eas_upload_audio'
    );

    formData.append(
        'nonce',
        '<?php echo esc_js(wp_create_nonce("eas_upload_audio")); ?>'
    );

    const attemptIdInput = document.querySelector(
        '#eas-attempt-form input[name="attempt_id"]'
    );

    formData.append('attempt_id', attemptIdInput ? attemptIdInput.value : '');
    formData.append('question_id', '<?php echo (int) $question['id']; ?>');

    formData.append(
        'audio',
        blob,
        'response.webm'
    );

    status.textContent = "<?php echo esc_js(__('Uploading...', 'eas-quiz')); ?>";

    fetch(
        '<?php echo esc_url(admin_url('admin-post.php')); ?>',
        {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        }
    )
    .then(async response => {

        const text = await response.text();

        console.log(
            'EAS audio upload HTTP status:',
            response.status
        );

        console.log(
            'EAS audio upload response:',
            text
        );

        let data;

        try {
            data = JSON.parse(text);
        }
        catch (error) {

            throw new Error(
                'Server returned invalid JSON: ' + text
            );

        }

        if (!response.ok) {

            throw new Error(
                'HTTP error ' + response.status
            );

        }

        return data;

    })
    .then(data => {

        if (data.success) {

            hidden.value =
                data.data.file;

            status.textContent =
                "<?php echo esc_js(__('Recording saved.', 'eas-quiz')); ?>";

            /*
             * Clear the chunks after successful upload.
             */
            chunks = [];

        }
        else {

            console.error(
                'EAS audio upload failed:',
                data
            );

            status.textContent =
                "<?php echo esc_js(__('Upload failed.', 'eas-quiz')); ?>";

        }

    })
    .catch(error => {

        console.error(
            'EAS audio upload error:',
            error
        );

        status.textContent =
            "<?php echo esc_js(__('Upload failed. Please try again.', 'eas-quiz')); ?>";

    });

}






    setTimeout(function(){

    startCountdown(
        <?php echo $prepare; ?>,
        function(){

            startRecording();

        },
        "<?php echo esc_js(__('Get ready', 'eas-quiz')); ?>"

    );

}, <?php echo $delay * 1000; ?>);


})();

</script>
</div>