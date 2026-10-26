/**
 * ==========================================================
 * StudentHub Portal - Form Validation & Strength Meter
 * Practical 5: Client-Side Regex Form Validation
 * ==========================================================
 */

document.addEventListener('DOMContentLoaded', () => {
  const registerForm = document.getElementById('register-form');
  const passwordInput = document.getElementById('password');

  if (passwordInput) {
    passwordInput.addEventListener('input', () => {
      checkPasswordStrength(passwordInput.value);
    });
  }

  if (registerForm) {
    // Add real-time input listeners to clear errors as user types
    const fields = ['full_name', 'email', 'mobile', 'password', 'confirm_password', 'course', 'year'];
    fields.forEach(fieldId => {
      const input = document.getElementById(fieldId);
      if (input) {
        input.addEventListener('input', () => validateSingleField(fieldId));
        input.addEventListener('blur', () => validateSingleField(fieldId));
      }
    });

    // Form submit listener
    registerForm.addEventListener('submit', (e) => {
      const isValid = validateRegistrationForm();
      if (!isValid) {
        e.preventDefault(); // Stop form submission if invalid
      }
    });
  }
});

/* ----------------------------------------------------------
   1. Live Password Strength Meter
   ---------------------------------------------------------- */
function checkPasswordStrength(password) {
  const fill = document.getElementById('password-meter-fill');
  const text = document.getElementById('password-meter-text');

  if (!fill || !text) return;

  if (!password) {
    fill.style.width = '0%';
    fill.style.backgroundColor = 'transparent';
    text.textContent = '';
    return;
  }

  let score = 0;

  // Criteria checks
  if (password.length >= 8) score += 25;
  if (/[A-Z]/.test(password)) score += 25;
  if (/[0-9]/.test(password)) score += 25;
  if (/[@$!%*?&#^()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password)) score += 25;

  fill.style.width = score + '%';

  if (score <= 25) {
    fill.style.backgroundColor = '#ef4444'; // Red
    text.textContent = 'Weak (Needs 8+ chars, uppercase, number & symbol)';
    text.style.color = '#ef4444';
  } else if (score <= 75) {
    fill.style.backgroundColor = '#f59e0b'; // Amber
    text.textContent = 'Medium (Add special symbols or numbers)';
    text.style.color = '#f59e0b';
  } else {
    fill.style.backgroundColor = '#10b981'; // Green
    text.textContent = 'Strong password!';
    text.style.color = '#10b981';
  }
}

/* ----------------------------------------------------------
   2. Single Field Real-Time Validation
   ---------------------------------------------------------- */
function validateSingleField(fieldId) {
  const nameRegex = /^[A-Za-z\s]{3,50}$/;
  const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
  const mobileRegex = /^[6-9]\d{9}$/;
  const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&#^()_+\-=\[\]{};':"\\|,.<>\/?]).{8,}$/;

  const input = document.getElementById(fieldId);
  const errorSpan = document.getElementById(`err-${fieldId}`);
  if (!input || !errorSpan) return true;

  const value = input.value.trim();
  let isValid = true;
  let message = '';

  switch (fieldId) {
    case 'full_name':
      if (!nameRegex.test(value)) {
        isValid = false;
        message = 'Name must be 3-50 letters only (no numbers or special characters).';
      }
      break;

    case 'email':
      if (!emailRegex.test(value)) {
        isValid = false;
        message = 'Please enter a valid email address (e.g. name@domain.com).';
      }
      break;

    case 'mobile':
      if (!mobileRegex.test(value)) {
        isValid = false;
        message = 'Enter a valid 10-digit Indian mobile number starting with 6, 7, 8, or 9.';
      }
      break;

    case 'password':
      if (!passwordRegex.test(value)) {
        isValid = false;
        message = 'Password must be at least 8 characters with 1 uppercase, 1 lowercase, 1 number, and 1 special symbol.';
      }
      break;

    case 'confirm_password':
      const originalPass = document.getElementById('password').value;
      if (value !== originalPass || value === '') {
        isValid = false;
        message = 'Passwords do not match.';
      }
      break;

    case 'course':
      if (!value) {
        isValid = false;
        message = 'Please select your academic course.';
      }
      break;

    case 'year':
      if (!value) {
        isValid = false;
        message = 'Please select your current year of study.';
      }
      break;
  }

  if (!isValid) {
    input.classList.add('is-invalid');
    input.classList.remove('is-valid');
    errorSpan.textContent = message;
    errorSpan.classList.add('visible');
    return false;
  } else {
    input.classList.remove('is-invalid');
    input.classList.add('is-valid');
    errorSpan.textContent = '';
    errorSpan.classList.remove('visible');
    return true;
  }
}

/* ----------------------------------------------------------
   3. Complete Form Validation on Submit
   ---------------------------------------------------------- */
function validateRegistrationForm() {
  const fields = ['full_name', 'email', 'mobile', 'password', 'confirm_password', 'course', 'year'];
  let allValid = true;

  // Validate text & select fields
  fields.forEach(fieldId => {
    const isFieldValid = validateSingleField(fieldId);
    if (!isFieldValid) allValid = false;
  });

  // Validate Gender Radio
  const genderChecked = document.querySelector('input[name="gender"]:checked');
  const errGender = document.getElementById('err-gender');
  if (!genderChecked) {
    if (errGender) {
      errGender.textContent = 'Please select your gender.';
      errGender.classList.add('visible');
    }
    allValid = false;
  } else {
    if (errGender) {
      errGender.textContent = '';
      errGender.classList.remove('visible');
    }
  }

  // Validate Terms Checkbox
  const terms = document.getElementById('terms');
  const errTerms = document.getElementById('err-terms');
  if (terms && !terms.checked) {
    if (errTerms) {
      errTerms.textContent = 'You must agree to the university honor code & terms.';
      errTerms.classList.add('visible');
    }
    allValid = false;
  } else {
    if (errTerms) {
      errTerms.textContent = '';
      errTerms.classList.remove('visible');
    }
  }

  return allValid;
}
