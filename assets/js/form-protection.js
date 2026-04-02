/**
 * Global Form Resubmission Prevention Script
 * This script prevents duplicate form submissions across the entire HRIS system
 */

document.addEventListener('DOMContentLoaded', function() {
    // Find all forms on the page
    const forms = document.querySelectorAll('form');
    
    forms.forEach(function(form) {
        let isSubmitting = false;
        
        form.addEventListener('submit', function(e) {
            // Check if form is already submitting
            if (isSubmitting) {
                e.preventDefault();
                return false;
            }
            
            // Find submit buttons
            const submitButtons = form.querySelectorAll('button[type="submit"], input[type="submit"]');
            
            // Set submitting state
            isSubmitting = true;
            
            // Disable submit buttons and show loading state
            submitButtons.forEach(function(button) {
                button.disabled = true;
                const originalText = button.textContent || button.value;
                
                if (button.tagName === 'BUTTON') {
                    button.textContent = 'Processing...';
                    button.dataset.originalText = originalText;
                } else if (button.tagName === 'INPUT') {
                    button.value = 'Processing...';
                    button.dataset.originalText = originalText;
                }
            });
            
            // Re-enable buttons after 10 seconds (fallback)
            setTimeout(function() {
                if (isSubmitting) {
                    isSubmitting = false;
                    submitButtons.forEach(function(button) {
                        button.disabled = false;
                        const originalText = button.dataset.originalText;
                        if (originalText) {
                            if (button.tagName === 'BUTTON') {
                                button.textContent = originalText;
                            } else if (button.tagName === 'INPUT') {
                                button.value = originalText;
                            }
                            delete button.dataset.originalText;
                        }
                    });
                }
            }, 10000);
        });
        
        // Reset submitting state when form is reset
        form.addEventListener('reset', function() {
            isSubmitting = false;
            const submitButtons = form.querySelectorAll('button[type="submit"], input[type="submit"]');
            submitButtons.forEach(function(button) {
                button.disabled = false;
                const originalText = button.dataset.originalText;
                if (originalText) {
                    if (button.tagName === 'BUTTON') {
                        button.textContent = originalText;
                    } else if (button.tagName === 'INPUT') {
                        button.value = originalText;
                    }
                    delete button.dataset.originalText;
                }
            });
        });
    });
    
    // Handle browser back/forward navigation
    window.addEventListener('pageshow', function(event) {
        // If page is loaded from cache (back button), reset form states
        if (event.persisted) {
            const forms = document.querySelectorAll('form');
            forms.forEach(function(form) {
                const submitButtons = form.querySelectorAll('button[type="submit"], input[type="submit"]');
                submitButtons.forEach(function(button) {
                    button.disabled = false;
                    const originalText = button.dataset.originalText;
                    if (originalText) {
                        if (button.tagName === 'BUTTON') {
                            button.textContent = originalText;
                        } else if (button.tagName === 'INPUT') {
                            button.value = originalText;
                        }
                        delete button.dataset.originalText;
                    }
                });
            });
        }
    });
    
    // Warn users before leaving if form has unsaved changes
    let formChanged = false;
    const formsWithChanges = new Set();
    
    forms.forEach(function(form) {
        const inputs = form.querySelectorAll('input, textarea, select');
        
        inputs.forEach(function(input) {
            input.addEventListener('change', function() {
                formsWithChanges.add(form);
                formChanged = true;
            });
        });
        
        form.addEventListener('submit', function() {
            formsWithChanges.delete(form);
            if (formsWithChanges.size === 0) {
                formChanged = false;
            }
        });
    });
    
    window.addEventListener('beforeunload', function(e) {
        if (formChanged) {
            const message = 'You have unsaved changes. Are you sure you want to leave?';
            e.returnValue = message;
            return message;
        }
    });
});
