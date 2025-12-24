jQuery(document).ready(function ($) {
    'use strict';

    // Email validation regex pattern
    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    // Find the registration form
    const $registrationForm = $('.woocommerce-form-register, #account_registration-form form');

    if ($registrationForm.length === 0) {
        return; // Exit if no registration form found
    }

    const $emailField = $registrationForm.find('#reg_email');
    const $phoneField = $registrationForm.find('#reg_phone');
    const $companyField = $registrationForm.find('#reg_company_name');

    // Create error message container helper
    function showError($field, message) {
        // Remove any existing error for this field
        $field.next('.validation-error').remove();
        $field.removeClass('error-field');

        // Add error message
        $field.addClass('error-field');
        $field.after('<span class="validation-error" style="color: #e2401c; font-size: 0.875em; display: block; margin-top: 5px;">' + message + '</span>');
    }

    function clearError($field) {
        $field.next('.validation-error').remove();
        $field.removeClass('error-field');
    }

    // Email format validation (client-side)
    function validateEmailFormat(email) {
        if (!email) {
            return { valid: false, message: 'Email is required' };
        }

        if (email.indexOf(' ') !== -1) {
            return { valid: false, message: 'Email address cannot contain spaces' };
        }

        if (!emailPattern.test(email)) {
            return { valid: false, message: 'Please enter a valid email address' };
        }

        const parts = email.split('@');
        if (parts.length !== 2) {
            return { valid: false, message: 'Email format is invalid' };
        }

        const domain = parts[1];
        if (domain.indexOf('.') === -1) {
            return { valid: false, message: 'Email domain is invalid' };
        }

        if (/^[.-]|[.-]$/.test(domain)) {
            return { valid: false, message: 'Email domain format is invalid' };
        }

        return { valid: true };
    }

    // Check if email exists (AJAX)
    function checkEmailExists(email, callback) {
        $.ajax({
            url: registrationValidation.ajaxurl,
            type: 'POST',
            data: {
                action: 'check_email_exists',
                nonce: registrationValidation.nonce,
                email: email
            },
            success: function (response) {
                callback(response);
            },
            error: function () {
                callback({ success: false, data: { message: 'Error checking email' } });
            }
        });
    }

    // Check if phone exists (AJAX)
    function checkPhoneExists(phone, callback) {
        $.ajax({
            url: registrationValidation.ajaxurl,
            type: 'POST',
            data: {
                action: 'check_phone_exists',
                nonce: registrationValidation.nonce,
                phone: phone
            },
            success: function (response) {
                callback(response);
            },
            error: function () {
                callback({ success: false, data: { message: 'Error checking phone' } });
            }
        });
    }

    // Check if company name exists (AJAX)
    function checkCompanyExists(companyName, callback) {
        $.ajax({
            url: registrationValidation.ajaxurl,
            type: 'POST',
            data: {
                action: 'check_company_exists',
                nonce: registrationValidation.nonce,
                company_name: companyName
            },
            success: function (response) {
                callback(response);
            },
            error: function () {
                callback({ success: false, data: { message: 'Error checking company name' } });
            }
        });
    }

    // Email field validation
    let emailTimeout;
    $emailField.on('blur input', function () {
        clearTimeout(emailTimeout);
        const email = $(this).val().trim();

        if (!email) {
            clearError($emailField);
            return;
        }

        // First validate format
        const formatCheck = validateEmailFormat(email);
        if (!formatCheck.valid) {
            showError($emailField, formatCheck.message);
            return;
        }

        // Clear format errors
        clearError($emailField);

        // Then check if exists (with debounce)
        emailTimeout = setTimeout(function () {
            checkEmailExists(email, function (response) {
                if (!response.success) {
                    showError($emailField, response.data.message);
                } else {
                    clearError($emailField);
                }
            });
        }, 500);
    });

    // Phone field validation
    let phoneTimeout;
    $phoneField.on('blur input', function () {
        clearTimeout(phoneTimeout);
        const phone = $(this).val().trim();

        if (!phone) {
            clearError($phoneField);
            return;
        }

        phoneTimeout = setTimeout(function () {
            checkPhoneExists(phone, function (response) {
                if (!response.success) {
                    showError($phoneField, response.data.message);
                } else {
                    clearError($phoneField);
                }
            });
        }, 500);
    });

    // Company field validation
    let companyTimeout;
    $companyField.on('blur input', function () {
        clearTimeout(companyTimeout);
        const companyName = $(this).val().trim();

        if (!companyName) {
            clearError($companyField);
            return;
        }

        companyTimeout = setTimeout(function () {
            checkCompanyExists(companyName, function (response) {
                if (!response.success) {
                    showError($companyField, response.data.message);
                } else {
                    clearError($companyField);
                }
            });
        }, 500);
    });

    // Form submission validation
    $registrationForm.on('submit', function (e) {
        let hasErrors = false;

        // Check for any existing validation errors
        if ($registrationForm.find('.validation-error').length > 0) {
            e.preventDefault();
            hasErrors = true;

            // Scroll to first error
            const $firstError = $registrationForm.find('.validation-error').first();
            if ($firstError.length) {
                $('html, body').animate({
                    scrollTop: $firstError.offset().top - 100
                }, 300);
            }
        }

        // Validate email format before submission
        const email = $emailField.val().trim();
        const emailFormatCheck = validateEmailFormat(email);
        if (!emailFormatCheck.valid) {
            e.preventDefault();
            showError($emailField, emailFormatCheck.message);
            hasErrors = true;
        }

        // Validate required phone
        const phone = $phoneField.val().trim();
        if (!phone) {
            e.preventDefault();
            showError($phoneField, 'Phone number is required');
            hasErrors = true;
        }

        if (hasErrors) {
            // Show general error message
            const $generalError = $registrationForm.find('.woocommerce-error');
            if ($generalError.length === 0) {
                $registrationForm.prepend(
                    '<ul class="woocommerce-error" role="alert" style="margin-bottom: 20px;">' +
                    '<li>Please fix the validation errors before submitting.</li>' +
                    '</ul>'
                );
            }
        }
    });
});
