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
      action="<?php echo esc_url(admin_url('admin-post.php')); ?>"
      data-submit-confirm="<?php echo esc_attr__('Are you sure you want to submit this quiz?', 'eas-quiz'); ?>"
      data-navigation-storage-key="eas-navigation-hidden-quiz-<?php echo esc_attr((int) $page['quiz_id']); ?>"
      data-show-navigation-label="<?php echo esc_attr__('Show navigation', 'eas-quiz'); ?>"
      data-hide-navigation-label="<?php echo esc_attr__('Hide navigation', 'eas-quiz'); ?>"
      data-go-to-page-nonce="<?php echo esc_attr($go_to_page_nonce); ?>"
      <?php if (!empty($page_time_limit)) : ?>
      data-page-time-remaining="<?php echo esc_attr((int) $page_time_remaining); ?>"
      data-page-expiry-action="<?php echo esc_attr($next_page ? 'eas_next_page' : 'eas_submit_attempt'); ?>"
      data-page-expiry-nonce="<?php echo esc_attr($next_page ? $next_page_nonce : wp_create_nonce('eas_submit_attempt')); ?>"
      <?php endif; ?>
      <?php if (isset($remaining_seconds) && $remaining_seconds !== null && empty($page_time_limit)) : ?>
      data-quiz-time-remaining="<?php echo esc_attr((int) $remaining_seconds); ?>"
      data-quiz-expiry-nonce="<?php echo esc_attr(wp_create_nonce('eas_submit_attempt')); ?>"
      <?php endif; ?>>
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
                        class="button eas-attempt-navigation-submit"
                        data-eas-action="eas_previous_page"
                        data-eas-nonce="<?php echo esc_attr($previous_page_nonce); ?>"
                    >
                        <?php esc_html_e('Previous Page', 'eas-quiz'); ?>
                    </button>
                <?php endif; ?>
                <?php if ($next_page) : ?>
                    <button
                        type="button"
                        class="button button-primary eas-attempt-navigation-submit"
                        data-eas-action="eas_next_page"
                        data-eas-nonce="<?php echo esc_attr($next_page_nonce); ?>"
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
                    <?php
                    // phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- The question renderer returns quiz-interface HTML; escaping it would break the markup.
                    echo $renderer->render_question($description, 0);
                    // phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
                    ?>
                </div>
                <div class="eas-right-panel">
                    <?php
                    $normal_question_id = (int) $normalquestions[0]['id'];
                    // phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- The question renderer returns quiz-interface HTML; escaping it would break the markup.
                    echo $renderer->render_question(
                        $normalquestions[0],
                        $question_offset + 1,
                        $attempt_responses[$normal_question_id] ?? null
                    );
                    // phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
                    ?>
                </div>
            </div>
        <?php else : ?>
            <?php foreach ($questions as $index => $question) : ?>
                <?php
                $question_id = (int) $question['id'];
                // phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- The question renderer returns quiz-interface HTML; escaping it would break the markup.
                echo $renderer->render_question(
                    $question,
                    $question_offset + $index + 1,
                    $attempt_responses[$question_id] ?? null
                );
                // phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
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
        >&gt;</button>

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

