<?php

declare(strict_types=1);

namespace EAS\Admin\Question;

use EAS\Question\QuestionRenderer;
use EAS\Question\QuestionRepository;

if (!defined('ABSPATH')) {
    exit;
}

class QuestionPreviewController
{
    private QuestionRepository $repository;

    public function __construct()
    {
        $this->repository = new QuestionRepository();

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
        $question = $id > 0 ? $this->repository->find($id) : null;

        if (!$question) {
            wp_die(esc_html__('Question not found.', 'eas-quiz'));
        }

        $renderer = new QuestionRenderer();
        $question_html = $renderer->render($question, 1);
        $title = __('Question Preview', 'eas-quiz');
        $frontend_css = EAS_PLUGIN_URL . 'assets/css/frontend.css';
        $preview_css = EAS_PLUGIN_URL . 'assets/css/question-preview.css';
        $preview_js = EAS_PLUGIN_URL . 'assets/js/question-preview.js';
        ?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo esc_html($title); ?></title>
    <link rel="stylesheet" href="<?php echo esc_url($frontend_css . '?ver=' . EAS_VERSION); ?>">
    <link rel="stylesheet" href="<?php echo esc_url($preview_css . '?ver=' . EAS_VERSION); ?>">
</head>
<body class="eas-question-preview-page">
    <main class="eas-question-preview">
        <div class="eas-question-preview__shell">
            <header class="eas-question-preview__header">
                <div>
                    <p class="eas-question-preview__eyebrow"><?php esc_html_e('EAS', 'eas-quiz'); ?></p>
                    <h1 class="eas-question-preview__title"><?php esc_html_e('Question Preview', 'eas-quiz'); ?></h1>
                </div>
                <a class="eas-question-preview__close" href="javascript:window.close();">
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
    <script src="<?php echo esc_url($preview_js . '?ver=' . EAS_VERSION); ?>"></script>
</body>
</html>
<?php
        exit;
    }
}
