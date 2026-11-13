(function () {
    'use strict';

    function initializeAudioQuestion(container) {
        const status = container.querySelector('.eas-speaking-status');
        const hidden = container.querySelector('.eas-audio-file');
        const delay = Math.max(0, parseInt(container.dataset.delay, 10) || 0);
        const prepare = Math.max(0, parseInt(container.dataset.prepare, 10) || 0);
        const duration = Math.max(0, parseInt(container.dataset.duration, 10) || 0);
        const uploadUrl = container.dataset.uploadUrl || '';
        const nonce = container.dataset.uploadNonce || '';
        const questionId = container.dataset.questionId || '';
        let mediaRecorder = null;
        let chunks = [];

        if (!status || !hidden) {
            return;
        }

        const strings = {
            secondRemaining: container.dataset.secondRemaining || 'second remaining',
            secondsRemaining: container.dataset.secondsRemaining || 'seconds remaining',
            recording: container.dataset.recordingLabel || 'Recording',
            recordingStatus: container.dataset.recordingStatus || 'Recording...',
            uploading: container.dataset.uploadingStatus || 'Uploading...',
            microphoneDenied: container.dataset.microphoneDenied || 'Microphone permission denied.',
            recordingSaved: container.dataset.recordingSaved || 'Recording saved.',
            uploadFailed: container.dataset.uploadFailed || 'Upload failed.',
            uploadTryAgain: container.dataset.uploadTryAgain || 'Upload failed. Please try again.',
            getReady: container.dataset.getReady || 'Get ready'
        };

        function startCountdown(seconds, callback, label) {
            const box = container.querySelector('.eas-speaking-countdown');
            const fill = container.querySelector('.eas-progress-fill');
            const time = container.querySelector('.eas-progress-time');
            const title = container.querySelector('.eas-progress-label');

            if (!box || !fill || !time || !title) {
                callback();
                return;
            }

            const total = Math.max(0, Number(seconds) || 0);

            if (total === 0) {
                box.style.display = 'none';
                callback();
                return;
            }

            box.style.display = 'block';
            title.textContent = label;

            let remaining = total;

            function updateBar() {
                fill.style.width = Math.max(0, (remaining / total) * 100) + '%';
                time.textContent = remaining + ' ' + (remaining === 1 ? strings.secondRemaining : strings.secondsRemaining);
            }

            updateBar();

            const timer = window.setInterval(function () {
                remaining--;
                updateBar();

                if (remaining <= 0) {
                    window.clearInterval(timer);
                    fill.style.width = '0%';
                    time.textContent = '0 ' + strings.secondsRemaining;

                    window.setTimeout(function () {
                        box.style.display = 'none';
                        callback();
                    }, 500);
                }
            }, 1000);
        }

        function uploadAudio() {
            const blob = new Blob(chunks, { type: 'audio/webm' });
            const formData = new FormData();
            const attemptIdInput = document.querySelector(
                '#eas-attempt-form input[name="attempt_id"]'
            );

            formData.append('action', 'examenanto_upload_audio');
            formData.append('nonce', nonce);
            formData.append('attempt_id', attemptIdInput ? attemptIdInput.value : '');
            formData.append('question_id', questionId);
            formData.append('audio', blob, 'response.webm');

            status.textContent = strings.uploading;

            fetch(uploadUrl, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
                .then(async function (response) {
                    const responseText = await response.text();
                    let data;

                    try {
                        data = JSON.parse(responseText);
                    } catch (error) {
                        throw new Error('Server returned invalid JSON: ' + responseText);
                    }

                    if (!response.ok) {
                        throw new Error('HTTP error ' + response.status);
                    }

                    return data;
                })
                .then(function (data) {
                    if (data.success) {
                        hidden.value = data.data.file;
                        status.textContent = strings.recordingSaved;
                        chunks = [];
                    } else {
                        console.error('EAS audio upload failed:', data);
                        status.textContent = strings.uploadFailed;
                    }
                })
                .catch(function (error) {
                    console.error('EAS audio upload error:', error);
                    status.textContent = strings.uploadTryAgain;
                });
        }

        function startRecording() {
            startCountdown(duration, function () {}, strings.recording);
            status.textContent = strings.recordingStatus;

            if (
                !navigator.mediaDevices
                || typeof navigator.mediaDevices.getUserMedia !== 'function'
                || typeof window.MediaRecorder === 'undefined'
            ) {
                status.textContent = strings.microphoneDenied;
                return;
            }

            chunks = [];

            navigator.mediaDevices.getUserMedia({ audio: true })
                .then(function (stream) {
                    mediaRecorder = new MediaRecorder(stream);
                    mediaRecorder.ondataavailable = function (event) {
                        chunks.push(event.data);
                    };
                    mediaRecorder.onstop = uploadAudio;
                    mediaRecorder.start();

                    window.setTimeout(function () {
                        if (mediaRecorder && mediaRecorder.state !== 'inactive') {
                            mediaRecorder.stop();
                        }

                        stream.getTracks().forEach(function (track) {
                            track.stop();
                        });

                        status.textContent = strings.uploading;
                    }, duration * 1000);
                })
                .catch(function (error) {
                    console.error(error);
                    status.textContent = strings.microphoneDenied;
                });
        }

        window.setTimeout(function () {
            startCountdown(prepare, startRecording, strings.getReady);
        }, delay * 1000);
    }

    document.querySelectorAll('.eas-audio-question').forEach(initializeAudioQuestion);
})();
