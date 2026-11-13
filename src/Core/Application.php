<?php
declare(strict_types=1);
namespace Examenanto\Core;
use Examenanto\Admin\AdminController;
use Examenanto\Frontend\QuizShortcode;
use Examenanto\Frontend\AttemptController;
use Examenanto\Frontend\ResultController;
use Examenanto\Frontend\FrontendAssets;
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
        $myResultsController = new \Examenanto\Frontend\MyResultsController();
        $myResultsController->register();
new FrontendAssets();
    }
}