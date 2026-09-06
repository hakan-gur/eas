<?php

if (!defined('ABSPATH')) {
    exit;
}

$settings = json_decode(
    $question['settings'] ?? '',
    true
);
$min_lines = 10;

if (is_array($settings)) {

    $min_lines =
        (int) ($settings['essay_min_lines'] ?? 10);

}
?>


<div class="eas-question">


<h3>

<?php echo wp_kses_post($question['question_text']); ?>

</h3>



<div class="eas-essay-response">


<div class="eas-essay-toolbar">

    <button type="button" class="eas-cut">
        <?php esc_html_e('Cut', 'eas-quiz'); ?>
    </button>

    <button type="button" class="eas-copy">
        <?php esc_html_e('Copy', 'eas-quiz'); ?>
    </button>

    <button type="button" class="eas-paste">
        <?php esc_html_e('Paste', 'eas-quiz'); ?>
    </button>

</div>


<textarea
    name="question_<?php echo esc_attr($question['id']); ?>"
    class="large-text eas-essay-box"
    style="height: <?php echo (int)$min_lines * 24; ?>px;"
    placeholder="<?php echo esc_attr__('Type your answer here;', 'eas-quiz'); ?>"><?php
echo esc_textarea(
    $response['answer'] ?? ''
);
?></textarea>
<div class="eas-word-count"
     style="text-align:center; margin-top:5px; color:#666;">

    <?php esc_html_e('Words:', 'eas-quiz'); ?> <span>0</span>
<script>

(function(){

    const container =
        document.currentScript.closest('.eas-question');


    const box =
        container.querySelector('.eas-essay-box');


    const cut =
        container.querySelector('.eas-cut');


    const copy =
        container.querySelector('.eas-copy');


    const paste =
        container.querySelector('.eas-paste');



    cut.addEventListener(
        'click',
        function(){

            document.execCommand('cut');

        }
    );



    copy.addEventListener(
        'click',
        function(){

            document.execCommand('copy');

        }
    );



    paste.addEventListener(
        'click',
        async function(){


            try {

                const text =
                    await navigator.clipboard.readText();


                const start =
                    box.selectionStart;


                const end =
                    box.selectionEnd;


                box.value =
                    box.value.substring(0,start)
                    +
                    text
                    +
                    box.value.substring(end);


                box.focus();


            }
            catch(error){

                alert(
                    '<?php echo esc_js(__('Paste is not allowed by your browser.', 'eas-quiz')); ?>'
                );

            }


        }
    );


})();

</script>
</div>

</div>

<script>

(function(){

    const box =
        document.currentScript
        .closest('.eas-question')
        .querySelector('.eas-essay-box');


    const counter =
        document.currentScript
        .closest('.eas-question')
        .querySelector('.eas-word-count span');


    function updateWordCount(){

        const text =
            box.value.trim();


        if (text === '') {

            counter.textContent = '0';

            return;

        }


        const words =
            text.split(/\s+/).length;


        counter.textContent =
            words;

    }


    box.addEventListener(
        'input',
        updateWordCount
    );


})();

</script>
</div>