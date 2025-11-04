/* ============================================================================
 * FlintmanCMS Hello World Plugin - JavaScript
 * ============================================================================
 *
 * PURPOSE:
 * Custom JavaScript functionality for the Hello World plugin.
 * This file is loaded via $scriptAdd in helloworld.php
 *
 * FEATURES:
 * - Form validation
 * - Interactive UI enhancements
 * - AJAX operations (optional)
 *
 * ============================================================================ */

/**
 * Main plugin initialization
 * Runs when DOM is fully loaded
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log('Hello World Plugin JavaScript Loaded');

    // Initialize all plugin features
    initFormValidation();
    initMessageCards();
    initDeleteConfirmation();
});

/**
 * Form Validation
 * Validates message and author fields before submission
 */
function initFormValidation() {
    const forms = document.querySelectorAll('.hello-form form');

    forms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            const messageField = form.querySelector('input[name="message"]');
            const authorField = form.querySelector('input[name="author"]');

            // Validate message field
            if (messageField && messageField.value.trim() === '') {
                e.preventDefault();
                alert('Please enter a message');
                messageField.focus();
                return false;
            }

            // Validate author field
            if (authorField && authorField.value.trim() === '') {
                e.preventDefault();
                alert('Please enter your name');
                authorField.focus();
                return false;
            }

            // Additional validation: message length
            if (messageField && messageField.value.length < 3) {
                e.preventDefault();
                alert('Message must be at least 3 characters long');
                messageField.focus();
                return false;
            }

            return true;
        });
    });
}

/**
 * Message Card Interactions
 * Adds interactive hover effects and click animations
 */
function initMessageCards() {
    const cards = document.querySelectorAll('.hello-message-card');

    cards.forEach(function(card) {
        // Add click animation
        card.addEventListener('click', function(e) {
            // Don't trigger if clicking on a link/button
            if (e.target.tagName === 'A' || e.target.tagName === 'BUTTON') {
                return;
            }

            // Find the view details link and trigger it
            const link = card.querySelector('a[href*="action=view"]');
            if (link) {
                window.location.href = link.href;
            }
        });

        // Change cursor to pointer on hover
        card.style.cursor = 'pointer';
    });
}

/**
 * Delete Confirmation
 * Enhanced confirmation dialog for delete actions
 */
function initDeleteConfirmation() {
    const deleteLinks = document.querySelectorAll('a[href*="action=delete"]');

    deleteLinks.forEach(function(link) {
        link.addEventListener('click', function(e) {
            // Only show confirmation on the list page, not the confirmation page itself
            if (!window.location.href.includes('action=delete')) {
                const confirmed = confirm(
                    'Are you sure you want to delete this message?\n\n' +
                    'This action cannot be undone.'
                );

                if (!confirmed) {
                    e.preventDefault();
                    return false;
                }
            }
        });
    });
}

/**
 * OPTIONAL: AJAX Example
 * Load messages without page refresh
 * Uncomment and modify as needed for your use case
 */
/*
function loadMessagesAjax() {
    fetch('ajax_endpoint.php?action=get_messages')
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById('messages-container');
            container.innerHTML = '';

            data.messages.forEach(function(message) {
                const card = document.createElement('div');
                card.className = 'hello-message-card';
                card.innerHTML = `
                    <h3>${escapeHtml(message.message)}</h3>
                    <p class="author">By: ${escapeHtml(message.author)}</p>
                    <p class="date">${message.date}</p>
                    <a href="index.php?n=plugins&p=helloworld&action=view&id=${message.id}" class="button">View Details</a>
                `;
                container.appendChild(card);
            });
        })
        .catch(error => {
            console.error('Error loading messages:', error);
        });
}
*/

/**
 * Utility: Escape HTML
 * Prevents XSS when inserting user content into DOM
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * Utility: Show Status Message
 * Display temporary success/error messages
 */
function showStatusMessage(message, type) {
    type = type || 'success'; // default to success

    const messageDiv = document.createElement('div');
    messageDiv.className = type + '-message';
    messageDiv.textContent = message;
    messageDiv.style.position = 'fixed';
    messageDiv.style.top = '20px';
    messageDiv.style.right = '20px';
    messageDiv.style.padding = '1rem 1.5rem';
    messageDiv.style.borderRadius = '0.5rem';
    messageDiv.style.boxShadow = '0 4px 6px rgba(0, 0, 0, 0.1)';
    messageDiv.style.zIndex = '9999';
    messageDiv.style.animation = 'slideIn 0.3s ease';

    if (type === 'success') {
        messageDiv.style.background = 'rgba(16, 185, 129, 0.9)';
        messageDiv.style.color = 'white';
    } else if (type === 'error') {
        messageDiv.style.background = 'rgba(239, 68, 68, 0.9)';
        messageDiv.style.color = 'white';
    }

    document.body.appendChild(messageDiv);

    // Auto-remove after 3 seconds
    setTimeout(function() {
        messageDiv.style.animation = 'slideOut 0.3s ease';
        setTimeout(function() {
            document.body.removeChild(messageDiv);
        }, 300);
    }, 3000);
}

// Add CSS animations for status messages
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);

/* ============================================================================
 * NOTES FOR JAVASCRIPT DEVELOPERS
 * ============================================================================
 *
 * BEST PRACTICES:
 *
 * 1. DOM READY:
 *    - Always wait for DOMContentLoaded before manipulating DOM
 *    - Ensures all elements are available
 *
 * 2. EVENT DELEGATION:
 *    - For dynamic content, use event delegation on parent elements
 *    - More efficient than attaching listeners to many elements
 *
 * 3. SECURITY:
 *    - Always escape user input before inserting into DOM
 *    - Use escapeHtml() function or similar
 *    - Prevent XSS attacks
 *
 * 4. AJAX:
 *    - Use fetch() API for modern browsers
 *    - Always handle errors gracefully
 *    - Show loading indicators for better UX
 *
 * 5. PERFORMANCE:
 *    - Cache DOM queries in variables
 *    - Minimize reflows and repaints
 *    - Debounce/throttle frequent events
 *
 * 6. COMPATIBILITY:
 *    - Test in multiple browsers
 *    - Use polyfills if needed for older browsers
 *    - Graceful degradation for no-JS scenarios
 *
 * 7. DEBUGGING:
 *    - Use console.log() for development
 *    - Remove or comment out logs in production
 *    - Use browser developer tools
 *
 * INTEGRATION WITH CMS:
 *
 * - This file is loaded via $scriptAdd in plugin PHP file
 * - Access CMS variables by passing them to JavaScript
 * - Use data attributes for configuration
 * - Respect theme styles and behaviors
 *
 * COMMON TASKS:
 *
 * - Form validation: Check required fields, formats
 * - AJAX requests: Load/save data without page refresh
 * - UI enhancements: Animations, interactive elements
 * - User feedback: Success/error messages, loading states
 *
 * ============================================================================ */
