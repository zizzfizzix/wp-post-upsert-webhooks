function addWebhookEndpoint() {
    const container = document.getElementById('webhook-endpoints');
    const template = document.getElementById('webhook-template');
    const index = container.children.length;
    const newEndpoint = template.content.cloneNode(true);

    // Replace placeholder index
    newEndpoint.querySelectorAll('[name*="{{INDEX}}"]').forEach(el => {
        el.name = el.name.replace('{{INDEX}}', index);
    });

    container.appendChild(newEndpoint);
}

function removeWebhookEndpoint(button) {
    const endpoint = button.closest('.webhook-endpoint');
    if (document.querySelectorAll('.webhook-endpoint').length > 1) {
        endpoint.remove();
    } else {
        alert('At least one webhook configuration must remain.');
    }
}

function toggleTokenVisibility(button) {
    const input = button.previousElementSibling;
    if (input.type === 'password') {
        input.type = 'text';
        button.textContent = 'Hide';
    } else {
        input.type = 'password';
        button.textContent = 'Show';
    }
}

function toggleWebhook(header) {
    const endpoint = header.closest('.webhook-endpoint');
    const content = endpoint.querySelector('.webhook-content');
    const indicator = header.querySelector('.collapse-indicator');

    content.classList.toggle('collapsed');
    indicator.classList.toggle('rotated');
}

function updateWebhookTitle(input) {
    const title = input.value || 'Unnamed Webhook';
    const header = input.closest('.webhook-endpoint').querySelector('.webhook-title');
    header.textContent = title;
}

function updateRetryDelays(input) {
    const endpoint = input.closest('.webhook-endpoint');
    const maxRetries = parseInt(input.value) || 0;
    const delaysContainer = endpoint.querySelector('.retry-delays');
    const currentDelays = delaysContainer.querySelectorAll('.retry-delay-field');
    const template = document.getElementById('retry-delay-template');

    // Remove excess delay fields
    for (let i = currentDelays.length - 1; i >= maxRetries; i--) {
        currentDelays[i].remove();
    }

    // Add missing delay fields
    for (let i = currentDelays.length; i < maxRetries; i++) {
        const newDelay = template.content.cloneNode(true);
        const index = input.name.match(/\[(\d+)\]/)[1];

        newDelay.querySelectorAll('[name*="{{INDEX}}"]').forEach(el => {
            el.name = el.name.replace('{{INDEX}}', index);
            el.name = el.name.replace('{{DELAY_INDEX}}', i);
        });

        const label = newDelay.querySelector('label');
        label.textContent = `Retry #${i + 1} Delay (seconds):`;

        delaysContainer.appendChild(newDelay);
    }
}

function updateRetryMode(input) {
    const retrySettings = input.closest('.retry-settings');
    const mode = input.value;
    retrySettings.dataset.mode = mode;

    // Reset values when switching modes
    if (mode === 'disabled') {
        const maxRetries = retrySettings.querySelector('.max-retries-input');
        if (maxRetries) {
            maxRetries.value = 3; // Reset to default value
        }
    }

    updateRetryPreview(retrySettings);
}

function calculateRetryDelays(retrySettings) {
    const mode = retrySettings.dataset.mode;
    const maxRetries = parseInt(retrySettings.querySelector('.max-retries-input').value) || 0;
    const delays = [];

    if (mode === 'constant') {
        const value = parseInt(retrySettings.querySelector('[name*="[constant_delay][value]"]').value) || 0;
        const unit = retrySettings.querySelector('[name*="[constant_delay][unit]"]').value;

        let totalSeconds;
        switch (unit) {
            case 'ms':
                totalSeconds = value / 1000;
                break;
            case 'sec':
                totalSeconds = value;
                break;
            case 'min':
                totalSeconds = value * 60;
                break;
            case 'hour':
                totalSeconds = value * 3600;
                break;
            case 'day':
                totalSeconds = value * 86400;
                break;
            default:
                totalSeconds = value;
        }

        for (let i = 0; i < maxRetries; i++) {
            delays.push(totalSeconds);
        }
    } else if (mode === 'exponential') {
        const multiplier = parseFloat(retrySettings.querySelector('[name*="[exponential][multiplier]"]').value) || 2;
        const base = parseInt(retrySettings.querySelector('[name*="[exponential][base]"]').value) || 5;
        const jitter = parseInt(retrySettings.querySelector('[name*="[exponential][jitter]"]').value) || 0;

        for (let i = 0; i < maxRetries; i++) {
            const delay = multiplier * Math.pow(base, i + 1);
            delays.push(delay);
        }

        if (jitter > 0) {
            return delays.map(delay => {
                const jitterSeconds = (delay * jitter) / 100;
                return `${Math.round(delay)} seconds (+/- ${jitterSeconds.toFixed(1)} seconds)`;
            });
        }
    }

    return delays.map(delay => formatDelay(delay));
}

function formatDelay(seconds) {
    if (seconds < 60) {
        return `${seconds} seconds`;
    } else if (seconds < 3600) {
        const minutes = Math.floor(seconds / 60);
        return `${minutes} minute${minutes > 1 ? 's' : ''}`;
    } else if (seconds < 86400) {
        const hours = Math.floor(seconds / 3600);
        return `${hours} hour${hours > 1 ? 's' : ''}`;
    } else {
        const days = Math.floor(seconds / 86400);
        return `${days} day${days > 1 ? 's' : ''}`;
    }
}

function updateRetryPreview(retrySettings) {
    const mode = retrySettings.dataset.mode;
    const previewContent = retrySettings.querySelector('.retry-preview-content');

    if (mode === 'disabled') {
        previewContent.innerHTML = 'Retries are disabled.';
        return;
    }

    const delays = calculateRetryDelays(retrySettings);
    const content = delays.map((delay, index) => {
        if (mode === 'exponential') {
            const formula = retrySettings.querySelector('.formula-hint').textContent;
            return `${index + 1}: ${delay} (${formula})`;
        } else {
            return `${index + 1}: After ${delay}`;
        }
    }).join('<br>');

    previewContent.innerHTML = content;
}

function handleRetryInputChange(event) {
    const retrySettings = event.target.closest('.retry-settings');
    if (retrySettings) {
        updateRetryPreview(retrySettings);
    }
}

function updateJitterValue(range) {
    const number = range.closest('.range-input-group').querySelector('.retry-jitter-number');
    number.value = range.value;
    handleRetryInputChange({ target: range });
}

// Initialize event listeners when the DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Add event listeners for existing max retries inputs
    document.querySelectorAll('.max-retries-input').forEach(input => {
        input.addEventListener('change', function() {
            updateRetryDelays(this);
        });
    });

    // Add event listeners for retry mode selection
    document.querySelectorAll('.retry-mode-option input[type="radio"]').forEach(input => {
        input.addEventListener('change', function() {
            updateRetryMode(this);
        });

        // Initialize mode for existing webhooks
        if (input.checked) {
            updateRetryMode(input);
        }
    });

    // Add event listeners for retry settings changes
    document.querySelectorAll('.retry-settings input, .retry-settings select').forEach(input => {
        input.addEventListener('change', handleRetryInputChange);
        input.addEventListener('input', function() {
            if (this.type === 'range') {
                updateJitterValue(this);
            }
        });
    });

    // Initialize retry previews
    document.querySelectorAll('.retry-settings').forEach(settings => {
        const checkedMode = settings.querySelector('input[type="radio"]:checked');
        if (checkedMode) {
            updateRetryMode(checkedMode);
        }
    });

    // Observe for new webhook endpoints being added
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            mutation.addedNodes.forEach(function(node) {
                if (node.nodeType === 1 && node.classList.contains('webhook-endpoint')) {
                    // Add event listeners for retry mode radio buttons
                    const retryModeInputs = node.querySelectorAll('.retry-mode-option input[type="radio"]');
                    retryModeInputs.forEach(input => {
                        input.addEventListener('change', function() {
                            updateRetryMode(this);
                        });
                    });

                    const maxRetriesInput = node.querySelector('.max-retries-input');
                    if (maxRetriesInput) {
                        maxRetriesInput.addEventListener('change', function() {
                            updateRetryDelays(this);
                        });
                    }

                    const retryInputs = node.querySelectorAll('.retry-settings input, .retry-settings select');
                    retryInputs.forEach(input => {
                        input.addEventListener('change', handleRetryInputChange);
                        input.addEventListener('input', function() {
                            if (this.type === 'range') {
                                updateJitterValue(this);
                            }
                        });
                    });

                    // Initialize the retry mode for the new webhook
                    const checkedMode = node.querySelector('.retry-mode-option input[type="radio"]:checked');
                    if (checkedMode) {
                        updateRetryMode(checkedMode);
                    }
                }
            });
        });
    });

    observer.observe(document.getElementById('webhook-endpoints'), {
        childList: true,
        subtree: true,
        attributes: true,
        attributeFilter: ['checked']
    });
});
