// â”€â”€ Scroll reveal â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
const revealEls = document.querySelectorAll('.reveal');
const revealObserver = new IntersectionObserver((entries) => {
  entries.forEach(e => { if (e.isIntersecting) e.target.classList.add('visible'); });
}, { threshold: 0.1 });
revealEls.forEach(el => revealObserver.observe(el));


// â”€â”€ Hero chat bubbles â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
const heroMessages = [
  { type: 'in',  text: '\uD83D\uDC4B Hi! I\'m interested in your services.' },
  { type: 'out', text: 'Hello! Thanks for reaching out \uD83D\uDE0A What can we help you with today?' },
  { type: 'in',  text: 'I need info about your pricing.' },
  { type: 'out', text: '\uD83D\uDE04 Sure! May I know your business type first?' },
  { type: 'in',  text: 'I run an online clothing store.' },
  { type: 'out', text: '\u2705 Perfect! I\'ve sent your custom plan. Check it out \uD83D\uDC86' },
];

function renderHeroChat() {
  const container = document.getElementById('hero-chat');
  if (!container) return;
  container.innerHTML = '';
  heroMessages.forEach((msg, i) => {
    const div = document.createElement('div');
    div.className = `chat-bubble flex ${msg.type === 'out' ? 'justify-end' : 'justify-start'}`;
    const times = ['10:01','10:01','10:02','10:02','10:03','10:03'];
    div.innerHTML = `<div class="${msg.type === 'out' ? 'msg-out' : 'msg-in'} px-3 py-2 text-xs max-w-[80%] shadow-sm" style="font-family:Inter,sans-serif;">${msg.text}<span class="block text-right text-gray-400 text-[10px] mt-1">${msg.type === 'out' ? '\u2714\u2714' : ''} ${times[i]}</span></div>`;
    container.appendChild(div);
    setTimeout(() => div.classList.add('visible'), i * 600 + 400);
  });
}
renderHeroChat();


// â”€â”€ Flow chat (automation / phone mockup section) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

const flowMessages = [
  {
    type: 'out',
    text: '\uD83D\uDC4B Hello! Welcome to AdverDigify.\n\nI\'m your WhatsApp Assistant. How can I help you today?',
    time: '09:00'
  },
  {
    type: 'in',
    text: 'Hi! I saw your ad. I need help automating my real estate leads.',
    time: '09:00'
  },
  {
    type: 'out',
    text: '\uD83C\uDFE1 Great! We specialise in real estate automation.\n\nHow many leads do you get per day?',
    time: '09:01'
  },
  {
    type: 'in',
    text: 'Around 30-50 leads a day but we miss most of them after hours.',
    time: '09:01'
  },
  {
    type: 'out',
    text: '\u2705 That\'s exactly what we solve!\n\nOur bot responds instantly 24/7, qualifies each lead, and books site visits \u2014 fully automated.',
    time: '09:02'
  },
  {
    type: 'in',
    text: 'Sounds good! How soon can you set it up?',
    time: '09:02'
  },
  {
    type: 'out',
    text: '\u26A1 We go live in just 7 days!\n\nWould you like to book a FREE demo call?\n\n\uD83D\uDCC5 Mon \u2022 Tue \u2022 Wed \u2022 Thu available',
    time: '09:03'
  },
  {
    type: 'in',
    text: 'Yes, please book me for Monday.',
    time: '09:03'
  },
  {
    type: 'out',
    text: '\uD83C\uDF89 Done! Your demo is confirmed for Monday 11:00 AM.\n\nYou\'ll get a reminder 1 hour before. See you! \uD83D\uDE0A',
    time: '09:04'
  },
];

// Typing indicator bubble
function showTyping(container) {
  const typing = document.createElement('div');
  typing.id = 'typing-indicator';
  typing.className = 'chat-bubble flex justify-start visible';
  typing.innerHTML = `
    <div class="msg-in px-3 py-2 text-xs shadow-sm" style="font-family:Inter,sans-serif;">
      <span class="typing-dots">
        <span></span><span></span><span></span>
      </span>
    </div>`;
  container.appendChild(typing);
  container.scrollTop = container.scrollHeight;
  return typing;
}

function removeTyping() {
  const el = document.getElementById('typing-indicator');
  if (el) el.remove();
}

function appendMessage(container, msg) {
  const div = document.createElement('div');
  div.className = `chat-bubble flex ${msg.type === 'out' ? 'justify-end' : 'justify-start'}`;
  div.innerHTML = `<div class="${msg.type === 'out' ? 'msg-out' : 'msg-in'} px-3 py-2 text-xs max-w-[85%] shadow-sm whitespace-pre-line" style="font-family:Inter,sans-serif;">${msg.text}<span class="block text-right text-gray-400 text-[10px] mt-1">${msg.type === 'out' ? '\u2714\u2714' : ''} ${msg.time}</span></div>`;
  container.appendChild(div);
  setTimeout(() => div.classList.add('visible'), 40);
  container.scrollTop = container.scrollHeight;
}

function playFlowChat() {
  const container = document.getElementById('flow-chat');
  if (!container) return;
  container.innerHTML = '';

  let delay = 400;

  flowMessages.forEach((msg, i) => {
    if (msg.type === 'out') {
      // Show typing first, then reveal message
      setTimeout(() => {
        showTyping(container);
        container.scrollTop = container.scrollHeight;
      }, delay);
      delay += 900; // typing duration
      setTimeout(() => {
        removeTyping();
        appendMessage(container, msg);
      }, delay);
      delay += 600;
    } else {
      // User message: just appears
      setTimeout(() => {
        appendMessage(container, msg);
      }, delay);
      delay += 700;
    }
  });

  // Loop after all messages + 4s pause
  setTimeout(() => {
    playFlowChat();
  }, delay + 4000);
}

// Start as soon as the DOM is ready â€” no observer needed
document.addEventListener('DOMContentLoaded', () => {
  setTimeout(playFlowChat, 1200);
});


// -- Mobile card carousel dot indicators -----------------------
function setupCarouselDots(scrollEl, dotsEl) {
  if (!scrollEl || !dotsEl) return;
  const cards = scrollEl.querySelectorAll('.snap-start');
  if (!cards.length) return;

  // Create dots
  dotsEl.innerHTML = '';
  cards.forEach((_, i) => {
    const dot = document.createElement('div');
    dot.className = 'carousel-dot' + (i === 0 ? ' active' : '');
    dot.addEventListener('click', () => {
      cards[i].scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'start' });
    });
    dotsEl.appendChild(dot);
  });

  const dots = dotsEl.querySelectorAll('.carousel-dot');

  // Update active dot on scroll
  scrollEl.addEventListener('scroll', () => {
    const scrollLeft = scrollEl.scrollLeft;
    const cardWidth = cards[0].offsetWidth + 16; // +gap
    const activeIndex = Math.round(scrollLeft / cardWidth);
    dots.forEach((d, i) => d.classList.toggle('active', i === activeIndex));
  }, { passive: true });
}

// Init after DOM ready
document.addEventListener('DOMContentLoaded', () => {
  // Benefits carousel
  const bScroll = document.querySelector('#benefits-carousel .mobile-card-scroll');
  const bDots = document.getElementById('benefits-dots');
  setupCarouselDots(bScroll, bDots);

  // Testimonials carousel
  const tScroll = document.querySelector('#testi-carousel .mobile-card-scroll');
  const tDots = document.getElementById('testi-dots');
  setupCarouselDots(tScroll, tDots);
});


// â”€â”€ Offer popup: show after 10 seconds â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
document.addEventListener('DOMContentLoaded', function() {
  const popup = document.getElementById('offer-popup');
  if (!popup) return;

  // Close on backdrop click
  popup.addEventListener('click', function(e) {
    if (e.target === popup) closePopup();
  });

  // Close on X button click
  const closeBtn = document.getElementById('offer-close');
  if (closeBtn) {
    closeBtn.addEventListener('click', closePopup);
  }

  // Close on CTA click
  const ctaBtn = popup.querySelector('a[href="#contact"]');
  if (ctaBtn) {
    ctaBtn.addEventListener('click', closePopup);
  }

  // Show after 10 seconds
  setTimeout(openPopup, 10000);
});


// -- Popup close/open helpers (class-based, avoids !important conflict) -
function closePopup() {
  const p = document.getElementById('offer-popup');
  if (p) p.classList.remove('visible');
}
function openPopup() {
  const p = document.getElementById('offer-popup');
  if (p) p.classList.add('visible');
}

// ── Form validation ───────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('demo-form');
  if (!form) return;

  const nameInput = form.querySelector('input[name="name"]');
  const emailInput = form.querySelector('input[name="email"]');
  const phoneInput = form.querySelector('input[name="phone"]');

  function validatePhone(input) {
    if (!input) return;
    const val = input.value.trim();
    if (val === '') {
      if (input.required) {
        input.setCustomValidity('Phone number is required.');
      } else {
        input.setCustomValidity('');
      }
      return;
    }
    // Extract only digits
    const digits = val.replace(/\D/g, '');
    if (digits.length < 10) {
      input.setCustomValidity('Phone number must have at least 10 digits.');
    } else if (digits.length > 15) {
      input.setCustomValidity('Phone number cannot exceed 15 digits.');
    } else {
      input.setCustomValidity('');
    }
  }

  function validateName(input) {
    if (!input) return;
    const val = input.value.trim();
    if (val.length < 3) {
      input.setCustomValidity('Name must be at least 3 characters.');
    } else if (val.length > 50) {
      input.setCustomValidity('Name cannot exceed 50 characters.');
    } else if (!/^[a-zA-Z\s]+$/.test(val)) {
      input.setCustomValidity('Name can only contain letters and spaces.');
    } else {
      input.setCustomValidity('');
    }
  }

  function validateEmail(input) {
    if (!input) return;
    const val = input.value.trim();
    const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
    if (!emailRegex.test(val)) {
      input.setCustomValidity('Please enter a valid email address with a domain (e.g. name@domain.com).');
    } else {
      input.setCustomValidity('');
    }
  }

  // Bind input events for live validation feedback
  if (nameInput) {
    nameInput.addEventListener('input', () => validateName(nameInput));
  }
  if (emailInput) {
    emailInput.addEventListener('input', () => validateEmail(emailInput));
  }
  if (phoneInput) {
    phoneInput.addEventListener('input', () => validatePhone(phoneInput));
  }

  const successModal = document.getElementById('success-modal');
  const successClose = document.getElementById('success-close');
  const successDone = document.getElementById('success-done');

  function openSuccessModal() {
    if (successModal) {
      successModal.style.display = 'flex';
      document.body.style.overflow = 'hidden';
    }
  }

  function closeSuccessModal() {
    if (successModal) {
      successModal.style.display = 'none';
      document.body.style.overflow = '';
    }
  }

  if (successClose) successClose.addEventListener('click', closeSuccessModal);
  if (successDone) successDone.addEventListener('click', closeSuccessModal);
  if (successModal) {
    successModal.addEventListener('click', (e) => {
      if (e.target === successModal) closeSuccessModal();
    });
  }

  // Form AJAX Submission Handler
  form.addEventListener('submit', function(e) {
    e.preventDefault();

    // Trigger validation logic manually before submitting
    validateName(nameInput);
    validateEmail(emailInput);
    validatePhone(phoneInput);

    if (!form.checkValidity()) {
      form.reportValidity();
      return;
    }

    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = 'Sending Request... <i class="fa-solid fa-spinner fa-spin" style="margin-left: 4px;"></i>';

    fetch('submit.php', {
      method: 'POST',
      body: new FormData(form)
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        // Reset the form so it remains at its place but is cleared
        form.reset();
        
        // Restore submit button state
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;

        // Show the beautiful success modal popup
        openSuccessModal();
      } else {
        alert('Error: ' + data.error);
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
      }
    })
    .catch(err => {
      alert('Failed to send request. Please check your connection and try again.');
      submitBtn.disabled = false;
      submitBtn.innerHTML = originalText;
    });
  });
  // Mobile menu toggle logic
  const menuToggle = document.getElementById('menu-toggle');
  const mobileMenu = document.getElementById('mobile-menu');
  if (menuToggle && mobileMenu) {
    menuToggle.addEventListener('click', () => {
      mobileMenu.classList.toggle('hidden');
      mobileMenu.classList.toggle('flex');
    });
    // Hide menu drawer when clicking a link
    mobileMenu.querySelectorAll('a').forEach(link => {
      link.addEventListener('click', () => {
        mobileMenu.classList.add('hidden');
        mobileMenu.classList.remove('flex');
      });
    });
  }
});
