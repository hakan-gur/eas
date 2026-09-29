<?php

declare(strict_types=1);

namespace EAS\Admin\Page;

use EAS\Page\PageRepository;
use EAS\Quiz\QuizRepository;

if (!defined('ABSPATH')) {
    exit;
}

class PageController
{
    private PageRepository $repository;
    private QuizRepository $quizzes;

    public function __construct()
    {
        $this->repository = new PageRepository();
        $this->quizzes = new QuizRepository();

        add_action('admin_menu', array($this, 'register'), 99);
        add_action('admin_post_eas_save_page', array($this, 'save'));
        add_action('admin_post_eas_delete_page', array($this, 'delete'));
        add_action('admin_post_eas_move_page', array($this, 'move'));
        add_action('admin_post_eas_reorder_pages', array($this, 'reorder'));
    }

    public function register(): void
    {
        add_submenu_page(
            'eas-dashboard',
            __('EAS Pages', 'eas-quiz'),
            __('Pages', 'eas-quiz'),
            'manage_options',
            'eas-pages',
            array($this, 'index')
        );
    }

    public function index(): void
    {
        $quiz_id = isset($_GET['quiz_id']) ? (int) $_GET['quiz_id'] : 0;

        if (isset($_GET['action']) && $_GET['action'] === 'add') {
            $this->form($quiz_id);
            return;
        }

        if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
            $this->form($quiz_id, (int) $_GET['id']);
            return;
        }

        $pages = $this->repository->all_by_quiz($quiz_id);
        $quiz = $this->quizzes->find($quiz_id);

        require EAS_PLUGIN_DIR . 'views/admin/pages/list.php';
    }

    private function form(int $quiz_id, ?int $id = null): void
    {
        $page = null;

        if ($id !== null) {
            $page = $this->repository->find($id);
        }

        require EAS_PLUGIN_DIR . 'views/admin/pages/form.php';
    }

    public function save(): void
    {
        check_admin_referer('eas_save_page');

        $quiz_id = (int) ($_POST['quiz_id'] ?? 0);

        $data = array(
            'title' => sanitize_text_field($_POST['title'] ?? ''),
            'content' => wp_kses_post(
                wp_unslash(
                    $_POST['content'] ?? ''
                    )
                ),
            'page_time_limit' => (int) ($_POST['page_time_limit'] ?? 0),
            'updated_at' => current_time('mysql')
        );

        $id = (int) ($_POST['page_id'] ?? 0);

        if ($id > 0) {
            $this->repository->update($id, $data);
        } else {
            $data['created_at'] = current_time('mysql');
            $this->repository->create($quiz_id, $data);
        }

        wp_redirect(admin_url('admin.php?page=eas-pages&quiz_id=' . $quiz_id));
        exit;
    }

    public function delete(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        $quiz_id = (int) ($_GET['quiz_id'] ?? 0);

        check_admin_referer('eas_delete_page_' . $id);

        $this->repository->delete($id);

        wp_redirect(admin_url('admin.php?page=eas-pages&quiz_id=' . $quiz_id));
        exit;
    }

    public function move(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        $quiz_id = (int) ($_GET['quiz_id'] ?? 0);
        $direction = sanitize_key($_GET['direction'] ?? '');

        check_admin_referer('eas_move_page_' . $id);

        if ($id > 0 && $quiz_id > 0) {
            $this->repository->move($id, $direction);
        }

        wp_redirect(admin_url('admin.php?page=eas-pages&quiz_id=' . $quiz_id));
        exit;
    }

    public function reorder(): void
    {
        $quiz_id = (int) ($_POST['quiz_id'] ?? 0);

        check_admin_referer('eas_reorder_pages_' . $quiz_id);

        $page_ids = isset($_POST['page_ids']) ? (array) $_POST['page_ids'] : array();
        $page_ids = array_map('absint', $page_ids);
        $page_ids = array_values(array_filter($page_ids));

        if ($quiz_id > 0 && !empty($page_ids)) {
            $this->repository->reorder($quiz_id, $page_ids);
        }

        wp_redirect(admin_url('admin.php?page=eas-pages&quiz_id=' . $quiz_id));
        exit;
    }
}
