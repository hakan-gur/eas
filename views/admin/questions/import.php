<?php

if (!defined('ABSPATH')) {
    exit;
}

?>

<div class="wrap">

    <h1>
        Import Questions
    </h1>

    <p>
        Quiz:
        <strong>
            <?php echo esc_html($quiz_title); ?>
        </strong>
    </p>

    <hr>

    <h2>
        Moodle GIFT Import
    </h2>

    <p>
        Paste questions exported from Moodle in
        <strong>GIFT format</strong> below.
    </p>

    <p>
        EAS currently supports:
    </p>

    <ul style="list-style: disc; margin-left: 25px;">

        <li>
            <strong>Description</strong> questions
        </li>

        <li>
            <strong>Multiple Choice</strong> questions
        </li>

        <li>
            Question numbers in the
            <code>::000::</code>,
            <code>::001::</code> format
        </li>

        <li>
            Correct answers beginning with
            <code>=</code>
        </li>

        <li>
            Incorrect answers beginning with
            <code>~</code>
        </li>

        <li>
            General Feedback beginning with
            <code>####</code>
        </li>

        <li>
            HTML in question text and General Feedback
        </li>

    </ul>

    <h3>
        Examples
    </h3>

    <p>
        <strong>Description:</strong>
    </p>

    <pre style="
        background:#f6f7f7;
        padding:15px;
        border:1px solid #ccd0d4;
        max-width:800px;
        overflow:auto;
    ">::000::<strong>Questions 41 through 43 refer to the following article.</strong></pre>

    <p>
        <strong>Multiple Choice:</strong>
    </p>

    <pre style="
        background:#f6f7f7;
        padding:15px;
        border:1px solid #ccd0d4;
        max-width:800px;
        overflow:auto;
    ">::001::The house Mr. Eno is thinking about buying is located ----- Heritage Street.{
~to
=on
~from
~of
####on + <strong>street name</strong>}</pre>

    <p>
        The number <code>000</code> is not displayed.
        Other question numbers are retained at the beginning
        of the question text.
    </p>

    <p>
        For example,
        <code>::001::</code> becomes
        <strong>1.</strong> at the beginning of the question.
    </p>

    <hr>

    <form
        method="post"
        action="<?php echo esc_url(
            admin_url('admin-post.php')
        ); ?>"
    >

        <input
            type="hidden"
            name="action"
            value="eas_import_questions"
        >

        <input
            type="hidden"
            name="quiz_id"
            value="<?php echo esc_attr(
                $quiz_id
            ); ?>"
        >

        <?php
        wp_nonce_field(
            'eas_import_questions'
        );
        ?>

        <table class="form-table">

            <tr>

                <th>
                    <label for="eas_import_text">
                        GIFT Questions
                    </label>
                </th>

                <td>

                    <textarea
                        id="eas_import_text"
                        name="import_text"
                        rows="25"
                        class="large-text code"
                        placeholder="Paste your Moodle GIFT questions here..."
                    ></textarea>

                    <p class="description">
                        Paste the GIFT text exactly as exported
                        from Moodle. Each question should begin
                        with <code>::number::</code>.
                    </p>

                </td>

            </tr>

        </table>

        <?php
        submit_button(
            'Import GIFT Questions'
        );
        ?>

    </form>

    <p>

        <a
            class="button"
            href="<?php echo esc_url(
                admin_url(
                    'admin.php?page=eas-questions'
                    . '&quiz_id='
                    . (int) $quiz_id
                    . '&action=bank'
                )
            ); ?>"
        >
            ← Back to Question Bank
        </a>

    </p>

</div>