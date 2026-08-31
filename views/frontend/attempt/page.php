<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="eas-attempt">
<?php
/*
 * =========================================================
 * PAGE NAVIGATION
 * =========================================================
 */
$next_page = $pages->nextByQuiz(
    (int) $page['quiz_id'],
    (int) $page['page_number']
);
$quiz_allows_backward =
    !empty($quiz['backward_navigation']);
$previous_page = null;
if ($quiz_allows_backward) {
    $previous_page = $pages->previousByQuiz(
        (int) $page['quiz_id'],
        (int) $page['page_number']
    );
}
/*
 * Timed pages deliberately hide manual Previous/Next
 * navigation. They advance automatically when the
 * page timer reaches zero.
 */
$show_header_navigation =
    empty($page_time_limit)
    &&
    ($previous_page || $next_page);
/*
 * Separate nonces for navigation.
 *
 * Next Page and Previous Page deliberately use
 * different nonce actions.
 */
$next_page_nonce =
    wp_create_nonce('eas_next_page');

$previous_page_nonce =
    wp_create_nonce('eas_previous_page');
?>
<form id="eas-attempt-form"
      method="post"
      action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
    <input type="hidden"
           name="attempt_id"
           value="<?php echo esc_attr($attempt['id']); ?>">
    <!-- =========================================================
         EAS QUIZ HEADER TOOLBAR
         ========================================================= -->
    <div class="eas-quiz-header">
        <!-- Quiz Name -->
        <div class="eas-quiz-header-name">
            <?php
            echo esc_html(
                $quiz['title'] ?? 'Quiz'
            );
            ?>
        </div>
        <!-- Timer -->
        <div class="eas-quiz-header-time">
            <?php if (!empty($page_time_limit)) : ?>
                <span class="eas-page-timer">
                    <strong>Remaining Time:</strong>
                    <strong id="eas-page-countdown">
                        <?php
                        echo esc_html(
                            gmdate(
                                'i:s',
                                max(
                                    0,
                                    (int) $page_time_remaining
                                )
                            )
                        );
                        ?>
                    </strong>
                </span>
            <?php elseif (
                isset($remaining_seconds)
                &&
                $remaining_seconds !== null
            ) : ?>
                <span class="eas-timer">
                    Remaining Time:
                    <strong id="eas-countdown">
                        <?php
                        echo esc_html(
                            gmdate(
                                'i:s',
                                max(
                                    0,
                                    (int) $remaining_seconds
                                )
                            )
                        );
                        ?>
                    </strong>
                </span>
            <?php endif; ?>
        </div>
        <!-- Previous / Next -->
        <div class="eas-quiz-header-navigation">
            <?php if ($show_header_navigation) : ?>
                <?php if ($previous_page) : ?>
                    <button
    type="button"
    class="button"
    onclick="
        this.form.querySelector('input[name=action]').value='eas_previous_page';
        this.form.querySelector('input[name=_wpnonce]').value='<?php echo esc_attr($previous_page_nonce); ?>';
        this.form.submit();
    "
>
    Previous Page
</button>
                <?php endif; ?>
                <?php if ($next_page) : ?>
                    <button
    type="button"
    class="button button-primary"
    onclick="
        this.form.querySelector('input[name=action]').value='eas_next_page';
        this.form.querySelector('input[name=_wpnonce]').value='<?php echo esc_attr($next_page_nonce); ?>';
        this.form.submit();
    "
>
    Next Page
</button>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
<!-- =========================================================
     PAGE TIMER
     ========================================================= -->

<?php if (!empty($page_time_limit)) : ?>

    <script>

    (function () {

        let remaining =
            <?php echo (int) $page_time_remaining; ?>;

        let submitted = false;


        function submitPageWhenExpired() {

            if (submitted) {
                return;
            }

            submitted = true;


            const form =
                document.getElementById(
                    'eas-attempt-form'
                );


            if (!form) {
                return;
            }


            /*
             * =================================================
             * TIMED PAGE WITH ANOTHER PAGE
             * =================================================
             *
             * Behave exactly like clicking "Next Page".
             */
            <?php if ($next_page) : ?>

                const action =
                    form.querySelector(
                        'input[name="action"]'
                    );

                const nonce =
                    form.querySelector(
                        'input[name="_wpnonce"]'
                    );


                if (action) {

                    action.value =
                        'eas_next_page';

                }


                if (nonce) {

                    nonce.value =
    '<?php echo esc_js(
        $next_page_nonce
    ); ?>';

                }


            <?php else : ?>

                /*
                 * =================================================
                 * LAST PAGE
                 * =================================================
                 *
                 * Time expiration submits the entire quiz.
                 */
                const action =
                    form.querySelector(
                        'input[name="action"]'
                    );

                const nonce =
                    form.querySelector(
                        'input[name="_wpnonce"]'
                    );


                if (action) {

                    action.value =
                        'eas_submit_attempt';

                }


                if (nonce) {

                    nonce.value =
                        '<?php echo esc_js(
                            wp_create_nonce(
                                'eas_submit_attempt'
                            )
                        ); ?>';

                }

            <?php endif; ?>


            /*
             * Submit the form.
             */
            form.submit();

        }


        function updatePageTimer() {

            const countdown =
                document.getElementById(
                    'eas-page-countdown'
                );


            if (!countdown) {
                return;
            }


            /*
             * Prevent negative values.
             */
            if (remaining < 0) {
                remaining = 0;
            }


            const minutes =
                Math.floor(
                    remaining / 60
                );


            const seconds =
                remaining % 60;


            countdown.textContent =
                minutes +
                ':' +
                (
                    seconds < 10
                        ? '0'
                        : ''
                ) +
                seconds;


            /*
             * Time has expired.
             */
            if (remaining <= 0) {

                submitPageWhenExpired();

                return;

            }


            remaining--;

        }


        /*
         * Display the initial value immediately.
         */
        updatePageTimer();


        /*
         * Update once every second.
         */
        setInterval(
            updatePageTimer,
            1000
        );

    })();

    </script>

<?php endif; ?>

    <!-- =========================================================
         QUIZ-WIDE TIMER
         ========================================================= -->
    <?php if (
        isset($remaining_seconds)
        &&
        $remaining_seconds !== null
        &&
        empty($page_time_limit)
    ) : ?>
        <script>
        (function () {
            let remaining =
                <?php echo (int) $remaining_seconds; ?>;
            let submitted = false;
            function submitQuizWhenExpired() {
                if (submitted) {
                    return;
                }
                submitted = true;
                const form =
                    document.getElementById(
                        'eas-attempt-form'
                    );
                if (!form) {
                    return;
                }
                const action =
                    form.querySelector(
                        'input[name="action"]'
                    );
                if (action) {
                    action.value =
                        'eas_submit_attempt';
                }
                form.submit();
            }
            function updateQuizTimer() {
                const countdown =
                    document.getElementById(
                        'eas-countdown'
                    );
                if (!countdown) {
                    return;
                }
                if (remaining < 0) {
                    remaining = 0;
                }
                const minutes =
                    Math.floor(
                        remaining / 60
                    );
                const seconds =
                    remaining % 60;
                countdown.textContent =
                    minutes +
                    ':' +
                    (
                        seconds < 10
                            ? '0'
                            : ''
                    ) +
                    seconds;
                if (remaining <= 0) {
                    submitQuizWhenExpired();
                    return;
                }
                remaining--;
            }
            updateQuizTimer();
            setInterval(
                updateQuizTimer,
                1000
            );
        })();
        </script>
    <?php endif; ?>
    <!-- =========================================================
         PAGE CONTENT
         ========================================================= -->
    <?php if (!empty($page['content'])) : ?>
        <div class="eas-page-content">
            <?php
            echo do_shortcode(
                $page['content']
            );
            ?>
        </div>
    <?php endif; ?>
    <!-- =========================================================
         QUESTIONS
         ========================================================= -->
    <?php if (empty($questions)) : ?>
        <p>
            <!-- No questions on this page. -->
        </p>
    <?php else : ?>
        <?php
        /*
         * Separate a description question from normal
         * questions so that a description + one question
         * can use the split-screen layout.
         */
        $description = null;
        $normalquestions = array();
        foreach ($questions as $question) {
            if (
                $question['type'] === 'description'
            ) {
                $description = $question;
            } else {
                $normalquestions[] = $question;
            }
        }
        ?>
        <!-- =====================================================
             SPLIT LAYOUT
             ===================================================== -->
        <?php if (
            $description
            &&
            count($normalquestions) === 1
        ) : ?>
            <div class="eas-split-layout">
                <!-- LEFT: DESCRIPTION -->
                <div class="eas-left-panel">
                    <?php
                    echo $renderer->render_question(
                        $description,
                        0
                    );
                    ?>
                </div>
                <!-- RIGHT: QUESTION -->
                <div class="eas-right-panel">
                    <?php
                    $normal_question_id =
                        (int) $normalquestions[0]['id'];
                    echo $renderer->render_question(
                        $normalquestions[0],
                        $question_offset + 1,
                        $attempt_responses[
                            $normal_question_id
                        ] ?? null
                    );
                    ?>
                </div>
            </div>
        <?php else : ?>
            <!-- =================================================
                 NORMAL QUESTION LIST
                 ================================================= -->
            <?php foreach (
                $questions
                as $index => $question
            ) : ?>
                <?php
                $question_id =
                    (int) $question['id'];
                echo $renderer->render_question(
                    $question,
                    $question_offset + $index + 1,
                    $attempt_responses[
                        $question_id
                    ] ?? null
                );
                ?>
                <hr>
            <?php endforeach; ?>
        <?php endif; ?>
    <?php endif; ?>
    <!-- =========================================================
         FORM ACTION / NONCE
         ========================================================= -->
    <?php if (!empty($page_time_limit)) : ?>
        <?php if ($next_page) : ?>
            <!-- Timed page with another page after it -->
            <input type="hidden"
                   name="action"
                   value="eas_next_page">
            <?php
            wp_nonce_field(
                'eas_next_page'
            );
            ?>
        <?php else : ?>
            <!-- Last timed page -->
            <input type="hidden"
                   name="action"
                   value="eas_submit_attempt">
            <?php
            wp_nonce_field(
                'eas_submit_attempt'
            );
            ?>
            <p>
                <button
                    type="submit"
                    class="button button-primary"
                >
                    Submit Quiz
                </button>
            </p>
        <?php endif; ?>
    <?php elseif ($next_page) : ?>
        <!-- =====================================================
             NORMAL PAGE WITH A NEXT PAGE
             ===================================================== -->
        <input type="hidden"
               name="action"
               value="eas_next_page">
        <?php
        wp_nonce_field(
            'eas_next_page'
        );
        ?>
    <?php else : ?>
        <!-- =====================================================
             LAST PAGE
             ===================================================== -->
        <input type="hidden"
               name="action"
               value="eas_submit_attempt">
        <?php
        wp_nonce_field(
            'eas_submit_attempt'
        );
        ?>
        <p>
            <button
                type="submit"
                class="button button-primary"
            >
                Submit Quiz
            </button>
        </p>
    <?php endif; ?>
    <script>
document.addEventListener(
    'DOMContentLoaded',
    function () {

        const form =
            document.getElementById(
                'eas-attempt-form'
            );

        if (!form) {
            return;
        }

        form.addEventListener(
            'submit',
            function (event) {

                /*
                 * Only ask for confirmation when
                 * the student manually submits the quiz.
                 */
                const action =
                    form.querySelector(
                        'input[name="action"]'
                    );

                if (
                    action
                    &&
                    action.value === 'eas_submit_attempt'
                ) {

                    if (
                        !window.confirm(
                            'Are you sure you want to submit this quiz?'
                        )
                    ) {
                        event.preventDefault();
                    }

                }

            }
        );

    }
);
</script>
</form>
</div>