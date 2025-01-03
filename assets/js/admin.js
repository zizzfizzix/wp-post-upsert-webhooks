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
