document.addEventListener(
    'DOMContentLoaded',
    function () {


        const startButtons =
            document.querySelectorAll(
                '.eas-start-recording'
            );


        startButtons.forEach(
            function (startButton) {


                const container =
                    startButton.closest(
                        '.eas-audio-response'
                    );


                const stopButton =
                    container.querySelector(
                        '.eas-stop-recording'
                    );


                const status =
                    container.querySelector(
                        '.eas-recording-status'
                    );


                const preview =
                    container.querySelector(
                        '.eas-audio-preview'
                    );


                const hiddenInput =
                    container.querySelector(
                        'input[type="hidden"]'
                    );



                let recorder;

                let chunks = [];



                startButton.addEventListener(
                    'click',
                    async function () {


                        const stream =
                            await navigator.mediaDevices.getUserMedia(
                                {
                                    audio: true
                                }
                            );


                        chunks = [];


                        recorder =
                            new MediaRecorder(stream);



                        recorder.ondataavailable =
                            function (event) {

                                chunks.push(
                                    event.data
                                );

                            };



                        recorder.onstop =
                            function () {


                                const blob =
                                    new Blob(
                                        chunks,
                                        {
                                            type:
                                            'audio/webm'
                                        }
                                    );


                                const url =
                                    URL.createObjectURL(
                                        blob
                                    );


                                preview.src = url;

                                preview.style.display =
                                    'block';


                                status.textContent =
                                    'Recording ready.';



const formData = new FormData();


formData.append(
    'action',
    'eas_upload_audio'
);


formData.append(
    'nonce',
    easAudio.nonce
);


formData.append(
    'audio',
    blob,
    'recording.webm'
);


fetch(
    easAudio.uploadUrl,
    {
        method: 'POST',
        body: formData
    }
)
.then(response => response.json())
.then(data => {

    if (data.success) {

        hiddenInput.value = data.data.file;

        status.textContent =
            'Recording uploaded successfully.';

    } else {

        status.textContent =
            'Upload failed.';

        console.log(data);

    }

})
.catch(error => {

    status.textContent =
        'Upload error.';

    console.error(error);

});

                            };



                        recorder.start();



                        status.textContent =
                            'Recording...';



                        startButton.disabled =
                            true;


                        stopButton.disabled =
                            false;


                    }
                );





                stopButton.addEventListener(
                    'click',
                    function () {


                        if (recorder) {


                            recorder.stop();



                            startButton.disabled =
                                false;


                            stopButton.disabled =
                                true;


                        }


                    }
                );


            }
        );


    }
);