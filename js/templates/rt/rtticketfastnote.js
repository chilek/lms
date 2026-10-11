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

function addQuickNote(ticketId) {
    const messageText = $input.val().trim();
    updateButtonState(true);

    showStatus($t('Adding note'), 'orange');

    $.ajax({
        url: "?m=rtticketedit&id=" + ticketId + "&action=fastnote",
        type: 'POST',
        data: { fastnote: messageText },
        success: () => {
            showStatus($t('Note added'), 'green');
            $input.val('');
            setTimeout(() => { window.location.reload(); }, 1500);
        },
        error: (xhr, status, error) => {
            showStatus($t('Error adding note'), 'red');
            console.error(trans('AJAX Error:'), xhr.responseText);
        },
        complete: () => {
            updateButtonState();
        }
    });
}

$input.on('input', () => updateButtonState());
