<?php

if (!defined('ABSPATH')) {
    exit;
}

?>


<div class="wrap">


<h1>
Question Preview
</h1>



<div class="card"
     style="max-width:800px;padding:25px;">



<h2>
Question
</h2>



<div style="font-size:16px;">

<?php

echo wp_kses_post(

    $question['question_text']

);

?>

</div>



<hr>



<h2>
Answers
</h2>



<?php if (empty($answers)): ?>


<p>
No answers have been added.
</p>


<?php else: ?>



<ol>


<?php foreach ($answers as $answer): ?>


<li style="margin-bottom:10px;">


<?php if ((int) $answer['is_correct'] === 1): ?>


<strong>

✓

<?php echo esc_html(

    $answer['answer_text']

); ?>

</strong>


<span style="color:green;">

(correct)

</span>



<?php else: ?>


<?php echo esc_html(

    $answer['answer_text']

); ?>


<?php endif; ?>


</li>


<?php endforeach; ?>


</ol>



<?php endif; ?>



</div>



<p>

<a href="<?php echo admin_url(

    'admin.php?page=eas-questions&page_id=' .

    $question['page_id']

); ?>"

class="button">

Back to Questions

</a>


</p>



</div>