// Form Validation using Regular Expressions

// Validation regex patterns
const validators = {
    email: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
    password: /^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d@$!%*#?&]{8,}$/,
    name: /^[a-zA-Z\s]{2,50}$/,
    phone: /^\+?[\d\s\-()]{10,}$/
};

// Validate email address
function validateEmail(email) {
    return validators.email.test(email);
}

// Validate password strength (min 8 chars, 1 letter, 1 number)
function validatePassword(password) {
    return validators.password.test(password);
}

// Validate name format (letters/spaces, 2-50 chars)
function validateName(name) {
    return validators.name.test(name);
}

// Validate phone number
function validatePhone(phone) {
    return validators.phone.test(phone);
}

// Validate file type
function validateFileType(file, allowedTypes) {
    return allowedTypes.includes(file.type);
}

// Validate file size
function validateFileSize(file, maxSize) {
    return file.size <= maxSize;
}

// Show validation error
function showValidationError(elementId, message) {
    const errorElement = document.getElementById(elementId);
    if (errorElement) {
        errorElement.textContent = message;
        errorElement.style.display = 'block';
        errorElement.classList.add('show');
    }
}

// Clear validation error
function clearValidationError(elementId) {
    const errorElement = document.getElementById(elementId);
    if (errorElement) {
        errorElement.textContent = '';
        errorElement.style.display = 'none';
        errorElement.classList.remove('show');
    }
}

// Clear all validation errors in a form
function clearAllValidationErrors(form) {
    const errors = form.querySelectorAll('.error-text');
    errors.forEach(error => {
        error.textContent = '';
        error.style.display = 'none';
        error.classList.remove('show');
    });
}

// Validate form field on input
function validateFieldOnInput(field, validationType, errorElementId) {
    field.addEventListener('input', function () {
        const value = this.value.trim();
        let isValid = false;
        let errorMessage = '';

        switch (validationType) {
            case 'email':
                isValid = validateEmail(value);
                errorMessage = 'Please enter a valid email address';
                break;
            case 'password':
                isValid = validatePassword(value);
                errorMessage = 'Password must be at least 8 characters with 1 letter and 1 number';
                break;
            case 'name':
                isValid = validateName(value);
                errorMessage = 'Name must be 2-50 characters, letters only';
                break;
            case 'phone':
                isValid = validatePhone(value);
                errorMessage = 'Please enter a valid phone number';
                break;
        }

        if (value.length > 0) {
            if (isValid) {
                clearValidationError(errorElementId);
                field.classList.remove('invalid');
                field.classList.add('valid');
            } else {
                showValidationError(errorElementId, errorMessage);
                field.classList.remove('valid');
                field.classList.add('invalid');
            }
        } else {
            clearValidationError(errorElementId);
            field.classList.remove('valid', 'invalid');
        }
    });
}

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        validateEmail,
        validatePassword,
        validateName,
        validatePhone,
        validateFileType,
        validateFileSize,
        showValidationError,
        clearValidationError,
        clearAllValidationErrors,
        validateFieldOnInput
    };
}
