function toggleFaq(el){el.classList.toggle('open')}

function loadVideo(){
  document.getElementById('vfInner').innerHTML='<iframe src="https://www.youtube.com/embed/dQw4w9WgXcQ?autoplay=1" allow="autoplay;encrypted-media" allowfullscreen></iframe>';
}

const observer = new IntersectionObserver((entries)=>{
  entries.forEach(e=>{
    if(e.isIntersecting){e.target.classList.add('visible')}
  });
},{threshold:0.1});
document.querySelectorAll('.reveal').forEach(el=>observer.observe(el));

// Keep track of original modal card content to restore on close if needed
let originalModalContent = null;

function openModal(e) {
  if (e) e.preventDefault();
  const modal = document.getElementById('projectModal');
  if (modal) {
    // Save original content before any replacements
    const modalCard = modal.querySelector('.modal-card');
    if (modalCard && !originalModalContent) {
      originalModalContent = modalCard.innerHTML;
    }
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
  }
}

function closeModal() {
  const modal = document.getElementById('projectModal');
  if (modal) {
    modal.classList.remove('active');
    document.body.style.overflow = '';
    // Restore original content so the form can be filled again
    const modalCard = modal.querySelector('.modal-card');
    if (modalCard && originalModalContent) {
      setTimeout(() => {
        modalCard.innerHTML = originalModalContent;
        // Re-attach form listener to the newly restored form
        const form = modalCard.querySelector('.faq-inquiry-form');
        if (form) attachFormListener(form);
      }, 300);
    }
  }
}

window.addEventListener('click', function(e) {
  const modal = document.getElementById('projectModal');
  if (e.target === modal) {
    closeModal();
  }
});

// Validations helpers
function validatePhone(input) {
  if (!input) return true;
  const val = input.value.trim();
  const digits = val.replace(/\D/g, '');
  if (digits.length < 10 || digits.length > 15) {
    input.setCustomValidity('Phone number must have between 10 and 15 digits.');
    return false;
  }
  input.setCustomValidity('');
  return true;
}

function validateName(input) {
  if (!input) return true;
  const val = input.value.trim();
  if (val.length < 3 || val.length > 50 || !/^[a-zA-Z\s]+$/.test(val)) {
    input.setCustomValidity('Name must be between 3 and 50 characters, containing only letters and spaces.');
    return false;
  }
  input.setCustomValidity('');
  return true;
}

function validateEmail(input) {
  if (!input) return true;
  const val = input.value.trim();
  const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
  if (!emailRegex.test(val)) {
    input.setCustomValidity('Please enter a valid email address with a domain (e.g. name@domain.com).');
    return false;
  }
  input.setCustomValidity('');
  return true;
}

// Success Modal Helpers
const successModal = document.getElementById('success-modal');
const successClose = document.getElementById('success-close');
const successDone = document.getElementById('success-done');

function openSuccessModal() {
  if (successModal) {
    successModal.style.display = 'flex';
    successModal.offsetHeight; // force reflow
    successModal.classList.add('active');
    document.body.style.overflow = 'hidden';
  }
}

function closeSuccessModal() {
  if (successModal) {
    successModal.classList.remove('active');
    document.body.style.overflow = '';
    setTimeout(() => {
      successModal.style.display = 'none';
    }, 400);
  }
}

if (successClose) successClose.addEventListener('click', closeSuccessModal);
if (successDone) successDone.addEventListener('click', closeSuccessModal);
if (successModal) {
  successModal.addEventListener('click', function(e) {
    if (e.target === successModal) closeSuccessModal();
  });
}

// Form AJAX Submission Helper
function attachFormListener(form) {
  const nameInput = form.querySelector('input[name="name"]');
  const emailInput = form.querySelector('input[name="email"]');
  const phoneInput = form.querySelector('input[name="phone"]');

  if (nameInput) nameInput.addEventListener('input', () => validateName(nameInput));
  if (emailInput) emailInput.addEventListener('input', () => validateEmail(emailInput));
  if (phoneInput) phoneInput.addEventListener('input', () => validatePhone(phoneInput));

  form.addEventListener('submit', function(e) {
    e.preventDefault();

    // Trigger validation
    const nameOk = validateName(nameInput);
    const emailOk = validateEmail(emailInput);
    const phoneOk = validatePhone(phoneInput);

    if (!form.checkValidity() || !nameOk || !emailOk || !phoneOk) {
      form.reportValidity();
      return;
    }

    const submitBtn = form.querySelector('.btn-faq-submit');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = 'Sending... <i class="fa-solid fa-spinner fa-spin" style="margin-left: 4px;"></i>';

    fetch('submit.php', {
      method: 'POST',
      headers: {
        'Accept': 'application/json'
      },
      body: new FormData(form)
    })
    .then(res => {
      if (!res.ok) {
        throw new Error('Server returned status ' + res.status);
      }
      return res.text();
    })
    .then(text => {
      try {
        const data = JSON.parse(text);
        if (data.success) {
          form.reset();
          submitBtn.disabled = false;
          submitBtn.innerHTML = originalText;
          closeModal();
          openSuccessModal();
        } else {
          alert('Error: ' + data.error);
          submitBtn.disabled = false;
          submitBtn.innerHTML = originalText;
        }
      } catch (parseErr) {
        console.error('Failed to parse JSON response:', text);
        alert('Server Error: Response is not valid JSON. Detail: ' + text.substring(0, 150));
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
      }
    })
    .catch(err => {
      console.error('Submission error:', err);
      alert('Failed to send inquiry: ' + err.message);
      submitBtn.disabled = false;
      submitBtn.innerHTML = originalText;
    });
  });
}

// Attach submission listeners to all inquiry forms on load
document.querySelectorAll('.faq-inquiry-form').forEach(form => attachFormListener(form));

