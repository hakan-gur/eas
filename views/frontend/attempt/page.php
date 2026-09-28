<?php
if (!defined('ABSPATH')) {
    exit;
}

$next_page = $pages->nextByQuiz(
    (int) $page['quiz_id'],
    (int) $page['page_number']
);
$quiz_allows_backward = !empty($quiz['backward_navigation']);
$previous_page = null;
if ($quiz_allows_backward) {
    $previous_page = $pages->previousByQuiz(
        (int) $page['quiz_id'],
        (int) $page['page_number']
    );
}
$show_header_navigation = empty($page_time_limit) && ($previous_page || $next_page);
$next_page_nonce = wp_create_nonce('eas_next_page');
$previous_page_nonce = wp_create_nonce('eas_previous_page');
$go_to_page_nonce = wp_create_nonce('eas_go_to_page');
$all_pages = $pages->all_by_quiz((int) $page['quiz_id']);
$show_navigation_panel = $quiz_allows_backward && count($all_pages) > 1;
?>
<div class="eas-attempt<?php echo $show_navigation_panel ? ' eas-attempt-with-navigation' : ''; ?>">
<div class="eas-attempt-layout<?php echo $show_navigation_panel ? ' has-navigation-panel' : ''; ?>" id="eas-attempt-layout">
<main class="eas-attempt-content" id="eas-attempt-content">
<form id="eas-attempt-form"
      method="post"
      action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
    <input type="hidden" name="attempt_id" value="<?php echo esc_attr($attempt['id']); ?>">

    <div class="eas-quiz-header">
        <div class="eas-quiz-header-name">
            <?php echo esc_html($quiz['title'] ?? __('Quiz', 'eas-quiz')); ?>
        </div>

        <div class="eas-quiz-header-time">
            <?php if (!empty($page_time_limit)) : ?>
                <span class="eas-page-timer">
                    <strong><?php esc_html_e('Remaining Time:', 'eas-quiz'); ?></strong>
                    <strong id="eas-page-countdown">
                        <?php
                        echo esc_html(
                            gmdate('i:s', max(0, (int) $page_time_remaining))
                        );
                        ?>
                    </strong>
                </span>
            <?php elseif (isset($remaining_seconds) && $remaining_seconds !== null) : ?>
                <span class="eas-timer">
                    <?php esc_html_e('Remaining Time:', 'eas-quiz'); ?>
                    <strong id="eas-countdown">
                        <?php
                        echo esc_html(
                            gmdate('i:s', max(0, (int) $remaining_seconds))
                        );
                        ?>
                    </strong>
                </span>
            <?php endif; ?>
        </div>

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
                        <?php esc_html_e('Previous Page', 'eas-quiz'); ?>
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
                        <?php esc_html_e('Next Page', 'eas-quiz'); ?>
                    </button>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($show_navigation_panel) : ?>
        <input type="hidden" name="go_to_page_id" id="eas-go-to-page-id" value="">
    <?php endif; ?>

    <?php if (!empty($page_time_limit)) : ?>
        <script>
        (function () {
            let remaining = <?php echo (int) $page_time_remaining; ?>;
            let submitted = false;

            function submitPageWhenExpired() {
                if (submitted) {
                    return;
                }
                submitted = true;

                const form = document.getElementById('eas-attempt-form');
                if (!form) {
                    return;
                }

                const action = form.querySelector('input[name="action"]');
                const nonce = form.querySelector('input[name="_wpnonce"]');

                <?php if ($next_page) : ?>
                    if (action) {
                        action.value = 'eas_next_page';
                    }
                    if (nonce) {
                        nonce.value = '<?php echo esc_js($next_page_nonce); ?>';
                    }
                <?php else : ?>
                    if (action) {
                        action.value = 'eas_submit_attempt';
                    }
                    if (nonce) {
                        nonce.value = '<?php echo esc_js(wp_create_nonce('eas_submit_attempt')); ?>';
                    }
                <?php endif; ?>

                form.submit();
            }

            function updatePageTimer() {
                const countdown = document.getElementById('eas-page-countdown');
                if (!countdown) {
                    return;
                }

                if (remaining < 0) {
                    remaining = 0;
                }

                const minutes = Math.floor(remaining / 60);
                const seconds = remaining % 60;
                countdown.textContent = minutes + ':' + (seconds < 10 ? '0' : '') + seconds;

                if (remaining <= 0) {
                    submitPageWhenExpired();
                    return;
                }

                remaining--;
            }

            updatePageTimer();
            setInterval(updatePageTimer, 1000);
        })();
        </script>
    <?php endif; ?>

    <?php if (
        isset($remaining_seconds)
        && $remaining_seconds !== null
        && empty($page_time_limit)
    ) : ?>
        <script>
        (function () {
            let remaining = <?php echo (int) $remaining_seconds; ?>;
            let submitted = false;

            function submitQuizWhenExpired() {
                if (submitted) {
                    return;
                }
                submitted = true;

                const form = document.getElementById('eas-attempt-form');
                if (!form) {
                    return;
                }

                const action = form.querySelector('input[name="action"]');
                if (action) {
                    action.value = 'eas_submit_attempt';
                }

                form.submit();
            }

            function updateQuizTimer() {
                const countdown = document.getElementById('eas-countdown');
                if (!countdown) {
                    return;
                }

                if (remaining < 0) {
                    remaining = 0;
                }

                const minutes = Math.floor(remaining / 60);
                const seconds = remaining % 60;
                countdown.textContent = minutes + ':' + (seconds < 10 ? '0' : '') + seconds;

                if (remaining <= 0) {
                    submitQuizWhenExpired();
                    return;
                }

                remaining--;
            }

            updateQuizTimer();
            setInterval(updateQuizTimer, 1000);
        })();
        </script>
    <?php endif; ?>

    <?php if (!empty($page['content'])) : ?>
        <div class="eas-page-content">
            <?php echo do_shortcode($page['content']); ?>
        </div>
    <?php endif; ?>

    <?php if (empty($questions)) : ?>
        <p></p>
    <?php else : ?>
        <?php
        $description = null;
        $normalquestions = array();
        foreach ($questions as $question) {
            if ($question['type'] === 'description') {
                $description = $question;
            } else {
                $normalquestions[] = $question;
            }
        }
        ?>

        <?php if ($description && count($normalquestions) === 1) : ?>
            <div class="eas-split-layout">
                <div class="eas-left-panel">
                    <?php echo $renderer->render_question($description, 0); ?>
                </div>
                <div class="eas-right-panel">
                    <?php
                    $normal_question_id = (int) $normalquestions[0]['id'];
                    echo $renderer->render_question(
                        $normalquestions[0],
                        $question_offset + 1,
                        $attempt_responses[$normal_question_id] ?? null
                    );
                    ?>
                </div>
            </div>
        <?php else : ?>
            <?php foreach ($questions as $index => $question) : ?>
                <?php
                $question_id = (int) $question['id'];
                echo $renderer->render_question(
                    $question,
                    $question_offset + $index + 1,
                    $attempt_responses[$question_id] ?? null
                );
                ?>
                <hr>
            <?php endforeach; ?>
        <?php endif; ?>
    <?php endif; ?>

    <?php if (!empty($page_time_limit)) : ?>
        <?php if ($next_page) : ?>
            <input type="hidden" name="action" value="eas_next_page">
            <?php wp_nonce_field('eas_next_page'); ?>
        <?php else : ?>
            <input type="hidden" name="action" value="eas_submit_attempt">
            <?php wp_nonce_field('eas_submit_attempt'); ?>
            <p>
                <button type="submit" class="button button-primary">
                    <?php esc_html_e('Submit Quiz', 'eas-quiz'); ?>
                </button>
            </p>
        <?php endif; ?>
    <?php elseif ($next_page) : ?>
        <input type="hidden" name="action" value="eas_next_page">
        <?php wp_nonce_field('eas_next_page'); ?>
    <?php else : ?>
        <input type="hidden" name="action" value="eas_submit_attempt">
        <?php wp_nonce_field('eas_submit_attempt'); ?>
        <p>
            <button type="submit" class="button button-primary">
                <?php esc_html_e('Submit Quiz', 'eas-quiz'); ?>
            </button>
        </p>
    <?php endif; ?>

    <script>
document.addEventListener(
    'DOMContentLoaded',
    function () {
        const form = document.getElementById('eas-attempt-form');

        if (!form) {
            return;
        }

        form.addEventListener(
            'submit',
            function (event) {
                const action = form.querySelector('input[name="action"]');

                if (
                    action
                    && action.value === 'eas_submit_attempt'
                ) {
                    if (
                        !window.confirm(
                            '<?php echo esc_js(__('Are you sure you want to submit this quiz?', 'eas-quiz')); ?>'
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
</main>
<?php if ($show_navigation_panel) : ?>
    <aside class="eas-navigation-panel" id="eas-navigation-panel" aria-label="<?php echo esc_attr__('Quiz Navigation', 'eas-quiz'); ?>">
        <button
            type="button"
            class="eas-navigation-toggle"
            id="eas-navigation-toggle"
            aria-expanded="true"
            aria-controls="eas-navigation-pages"
            title="<?php echo esc_attr__('Hide navigation', 'eas-quiz'); ?>"
        >&lt;</button>

        <div class="eas-navigation-panel-content">
            <div class="eas-navigation-pages" id="eas-navigation-pages">
                <?php foreach ($all_pages as $navigation_page) : ?>
                    <?php
                    $navigation_page_id = (int) $navigation_page['id'];
                    $navigation_is_current =
                        $navigation_page_id === (int) $page['id'];
                    ?>
                    <button
                        type="button"
                        class="eas-navigation-page-number<?php echo $navigation_is_current ? ' is-current' : ''; ?>"
                        data-page-id="<?php echo esc_attr($navigation_page_id); ?>"
                        <?php echo $navigation_is_current ? 'aria-current="page"' : ''; ?>
                    >
                        <?php echo esc_html((int) $navigation_page['page_number']); ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>
    </aside>
<?php endif; ?>
</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const layout = document.getElementById('eas-attempt-layout');
    const toggle = document.getElementById('eas-navigation-toggle');
    const form = document.getElementById('eas-attempt-form');

    if (!layout || !toggle || !form) {
        return;
    }

    toggle.addEventListener('click', function () {
        const hidden = layout.classList.toggle('navigation-hidden');

        toggle.setAttribute('aria-expanded', hidden ? 'false' : 'true');
        toggle.innerHTML = hidden ? '&gt;' : '&lt;';
        toggle.title = hidden
            ? '<?php echo esc_js(__('Show navigation', 'eas-quiz')); ?>'
            : '<?php echo esc_js(__('Hide navigation', 'eas-quiz')); ?>';
    });

    document.querySelectorAll('.eas-navigation-page-number').forEach(function (button) {
        button.addEventListener('click', function () {
            const pageId = this.getAttribute('data-page-id');
            const action = form.querySelector('input[name="action"]');
            const nonce = form.querySelector('input[name="_wpnonce"]');
            const target = document.getElementById('eas-go-to-page-id');

            if (!pageId || !action || !nonce || !target) {
                return;
            }

            action.value = 'eas_go_to_page';
            nonce.value = '<?php echo esc_js($go_to_page_nonce); ?>';
            target.value = pageId;
            form.submit();
        });
    });
});
</script>
