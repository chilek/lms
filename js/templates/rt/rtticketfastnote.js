const $input = $('#quick-note-input');
const $button = $('#quick-message-send');
const $overlay = $('#quick-message-overlay');

function updateButtonState(disabled = null) {
    if (disabled === null) {
        disabled = !$input.val().trim();
    }
    $button.prop('disabled', disabled);
}

function showStatus(message, color) {
    $overlay
        .html(`<span style="color: ${color};">${message}</span>`)
        .css('display', 'flex');

    setTimeout(() => {
        $overlay.fadeOut();
    }, 4000);
}

function sendQuickMessage(ticketId) {
    const messageText = $input.val().trim();
    updateButtonState(true);

    showStatus($t('Sending message'), 'orange');

    $.ajax({
        url: "?m=rtticketedit&id=" + ticketId + "&action=fastnote",
        type: 'POST',
        data: { fastnote: messageText },
        success: () => {
            showStatus($t('Note added'), 'green');
            $input.val('');
            setTimeout(() => {
                const cleanUrl = window.location.origin + window.location.pathname + window.location.search;
                window.location.href = cleanUrl;
                }, 1500
            );
        },
        error: (xhr, status, error) => {
            showStatus(`$t('Adding note Error'): ${error}"`, 'red');
            console.error(trans('AJAX Error:'), xhr.responseText);
        },
        complete: () => {
            updateButtonState();
        }
    });
}

$input.on('input', () => updateButtonState());
