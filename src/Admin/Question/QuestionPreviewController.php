<?php

declare(strict_types=1);

namespace EAS\Admin\Question;

use EAS\Question\QuestionRenderer;
use EAS\Question\QuestionRepository;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Provides a standalone, frontend-styled question preview for administrators.
 *
 * The existing question renderer is deliberately reused so that preview output
 * stays synchronized with the real student question presentation. This class
 * changes presentation only; it does not create, save, grade, or alter answers.
 */
class QuestionPreviewController
{
    private QuestionRepository $repository;

    public function __construct()
    {
        $this->repository = new QuestionRepository();

        /*
         * QuestionController already registers this action. Remove that
         * presentation-only callback and replace it with the dedicated preview.
         */
        remove_all_actions('admin_post_eas_preview_question');

        add_action(
            'admin_post_eas_preview_question',
            array($this, 'preview')
        );
    }

    public function preview(): void
    {
        if (!is_user_logged_in() || !current_user_can('manage_options')) {
            wp_die(
                esc_html__('You are not allowed to preview questions.', 'eas-quiz'),
                '',
                array('response' => 403)
            );
        }

        $id = (int) ($_GET['id'] ?? 0);

        if ($id <= 0) {
            wp_die(
                esc_html__('Invalid question.', 'eas-quiz')
            );
        }

        $question = $this->repository->find($id);

        if (!$question) {
            wp_die(
                esc_html__('Question not found.', 'eas-quiz')
            );
        }

        $renderer = new QuestionRenderer();
        $question_html = $renderer->render($question, 1);

        $title = sprintf(
            __('Question Preview: %s', 'eas-quiz'),
            wp_strip_all_tags((string) ($question['type'] ?? ''))
        );

        $frontend_css = EAS_PLUGIN_URL . 'assets/css/frontend.css';
        $preview_css = EAS_PLUGIN_URL . 'assets/css/question-preview.css';
        $frontend_js = EAS_PLUGIN_URL . 'assets/js/frontend.js';

        ?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo esc_html($title); ?></title>
    <link rel="stylesheet" href="<?php echo esc_url($frontend_css . '?ver=' . EAS_VERSION); ?>">
    <link rel="stylesheet" href="<?php echo esc_url($preview_css . '?ver=' . EAS_VERSION); ?>">
</head>
<body>
    <main class="eas-question-preview">
        <div class="eas-question-preview__shell">
            <header class="eas-question-preview__header">
                <div>
                    <p class="eas-question-preview__eyebrow">
                        <?php esc_html_e('EAS', 'eas-quiz'); ?>
                    </p>
                    <h1 class="eas-question-preview__title">
                        <?php esc_html_e('Question Preview', 'eas-quiz'); ?>
                    </h1>
                </div>

                <a
                    class="eas-question-preview__close"
                    href="javascript:window.close();"
                >
                    <?php esc_html_e('Close Preview', 'eas-quiz'); ?>
                </a>
            </header>

            <section class="eas-question-preview__card">
                <?php echo $question_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

                <p class="eas-preview-note">
                    <?php esc_html_e('Preview only. No answer or result is saved from this page.', 'eas-quiz'); ?>
                </p>
            </section>
        </div>
    </main>

    <script src="<?php echo esc_url($frontend_js . '?ver=' . EAS_VERSION); ?>"></script>
</body>
</html>
<?php

        exit;
    }
}
