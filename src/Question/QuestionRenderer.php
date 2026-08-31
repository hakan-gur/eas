<?php
declare(strict_types=1);
namespace EAS\Question;
use EAS\Answer\AnswerRepository;
use EAS\Core\View;
if (!defined('ABSPATH')) {
    exit;
}
class QuestionRenderer
{
    private AnswerRepository $answers;
    public function __construct()
    {
        $this->answers = new AnswerRepository();
    }
    public function render(
    array $question,
    int $number,
    ?array $quiz = null,
    ?array $response = null
): string
    {
        $type = $question['type'] ?? 'multiple_choice';
        switch ($type) {
            case 'essay':
                return View::render(
                    'frontend/question/essay',
                    [
                        'question' => $question,
                        'number' => $number,
                        'response' => $response
                    ]
                );
            case 'audio_response':
                return View::render(
                    'frontend/question/audio',
                    [
                        'question' => $question,
                        'number' => $number,
                        'response' => $response
                    ]
                );
case 'drag_drop_text':
    $options = $this->answers->all_by_question(
        (int) $question['id']
    );
    shuffle($options);
    return View::render(
        'frontend/question/drag_drop_text',
        [
            'question' => $question,
            'number' => $number,
            'options' => $options,
            'response' => $response
        ]
    );
case 'fill_blank':
    return View::render(
        'frontend/question/fill_blank',
        [
            'question' => $question,
            'number' => $number,
            'response' => $response
        ]
    );
case 'description':
    return View::render(
        'frontend/question/description',
        [
            'question' => $question,
            'number' => $number
        ]
    );
            case 'multiple_choice':
            default:
                $options = $this->answers->all_by_question(
                    (int) $question['id']
                );
                /*
                 * Shuffle options only if enabled
                 */
                if (
                    $quiz !== null
                    &&
                    !empty($quiz['shuffle_options'])
                ) {
                    shuffle($options);
                }
                return View::render(
                    'frontend/question/item',
                    [
                        'question' => $question,
                        'number' => $number,
                        'options' => $options,
                        'response' => $response
                    ]
                );
        }
    }
}