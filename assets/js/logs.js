function showWebhookDetails(log) {
    const modal = document.getElementById('webhook-details-modal');
    const payload = document.getElementById('webhook-payload');
    const response = document.getElementById('webhook-response');
    const requestHeaders = document.getElementById('webhook-request-headers');
    const responseHeaders = document.getElementById('webhook-response-headers');
    const responseStatus = document.getElementById('webhook-response-status');
    const webhookUrl = document.getElementById('webhook-url');

    // Format request data
    if (typeof log.payload === 'string') {
        try {
            payload.textContent = JSON.stringify(JSON.parse(log.payload), null, 2);
        } catch (e) {
            payload.textContent = log.payload;
        }
    } else {
        payload.textContent = JSON.stringify(log.payload, null, 2);
    }

    if (typeof log.request_headers === 'string') {
        try {
            requestHeaders.textContent = JSON.stringify(JSON.parse(log.request_headers), null, 2);
        } catch (e) {
            requestHeaders.textContent = log.request_headers || 'No headers available';
        }
    } else {
        requestHeaders.textContent = JSON.stringify(log.request_headers, null, 2);
    }

    // Format response data
    responseStatus.textContent = log.response_code || 'N/A';

    if (typeof log.response_headers === 'string') {
        try {
            responseHeaders.textContent = JSON.stringify(JSON.parse(log.response_headers), null, 2);
        } catch (e) {
            responseHeaders.textContent = log.response_headers || 'No headers available';
        }
    } else {
        responseHeaders.textContent = JSON.stringify(log.response_headers, null, 2);
    }

    response.textContent = log.response_body || 'No response available';
    webhookUrl.textContent = `${log.http_method || 'POST'} ${log.webhook_url}`;
    modal.style.display = 'block';
}

document.addEventListener('DOMContentLoaded', function() {
    // Event binding for detail buttons
    document.querySelectorAll('.show-webhook-details').forEach(button => {
        button.addEventListener('click', function() {
            const logData = JSON.parse(this.dataset.log);
            showWebhookDetails(logData);
        });
    });

    // Modal close handlers
    document.querySelector('.webhook-modal-close').addEventListener('click', function() {
        document.getElementById('webhook-details-modal').style.display = 'none';
    });

    window.addEventListener('click', function(event) {
        const modal = document.getElementById('webhook-details-modal');
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });
});
