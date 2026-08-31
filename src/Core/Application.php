<?php
declare(strict_types=1);
namespace EAS\Core;
use EAS\Admin\AdminController;
use EAS\Frontend\QuizShortcode;
use EAS\Frontend\AttemptController;
use EAS\Frontend\ResultController;
use EAS\Frontend\FrontendAssets;
if (!defined('ABSPATH')) {
    exit;
}
class Application
{
    public function run(): void
    {
        new AdminController();
        $shortcode = new QuizShortcode();
        $shortcode->register();
        $attemptController = new AttemptController();
        $attemptController->register();
        $resultController = new ResultController();
        $resultController->register();
        $myResultsController = new \EAS\Frontend\MyResultsController();
        $myResultsController->register();
new FrontendAssets();
    }
}