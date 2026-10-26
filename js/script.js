/**
 * ==========================================================
 * StudentHub Portal - Core Interactivity & Auth Guard (script.js)
 * Practical 4: UI Interactivity & Practical 10: Mandatory RBAC Guard
 * ==========================================================
 */

// Step 0: Automatic Localhost Redirect Guard (Prevent file:// and Live Server :5500 Bugs)
(function() {
  if (window.location.protocol === 'file:' || (window.location.port && window.location.port !== '80' && window.location.port !== '443')) {
    var currentFile = window.location.pathname.split('/').pop() || 'login.html';
    window.location.replace('http://localhost/studenthub/' + currentFile);
  }
})();

// Step 1: Immediately hide document visibility until session status is verified
document.documentElement.style.visibility = 'hidden';

// Execute Authentication & RBAC Guard
(function enforceAuthGuard() {
  const path = window.location.pathname;
  let page = path.substring(path.lastIndexOf('/') + 1) || 'index.html';
  if (!page || page.trim() === '') page = 'index.html';

  const isPublicPage = (page === 'login.html' || page === 'register.html');
  const isAdminOnlyPage = (page === 'ajax_crud.html' || page === 'admin.html' || path.includes('/php/'));

  // Always use root-relative API endpoint
  fetch('/studenthub/php/session_status.php', { cache: 'no-store' })
    .then(response => {
      if (!response.ok) throw new Error('HTTP ' + response.status);
      return response.json();
    })
    .then(data => {
      // ----------------------------------------------------
      // Rule A: User is NOT logged in
      // ----------------------------------------------------
      if (!data || data.logged_in !== true) {
        if (!isPublicPage) {
          window.location.replace('/studenthub/login.html');
          return;
        } else {
          // Public page (login.html or register.html) - allow display
          document.documentElement.style.visibility = 'visible';
          return;
        }
      }

      // ----------------------------------------------------
      // Rule B: User IS already logged in on login or register page
      // ----------------------------------------------------
      if (isPublicPage) {
        if (data.role === 'admin') {
          window.location.replace('/studenthub/php/admin_dashboard.php');
        } else {
          window.location.replace('/studenthub/index.html');
        }
        return;
      }

      // ----------------------------------------------------
      // Rule C: Logged-in non-admin attempting to access Admin-Only pages
      // ----------------------------------------------------
      if (data.role !== 'admin' && isAdminOnlyPage && !path.includes('logout.php') && !path.includes('contact_process.php') && !path.includes('feedback_process.php')) {
        alert('Access Denied! Only Administrators can access this module.');
        window.location.replace('/studenthub/dashboard.html');
        return;
      }

      // User is authenticated and permitted: reveal page
      document.documentElement.style.visibility = 'visible';

      // Enforce RBAC in Navigation Bar
      updateNavForRole(data);
    })
    .catch(err => {
      console.warn('Auth guard check failed:', err);
      if (!isPublicPage) {
        window.location.replace('/studenthub/login.html');
      } else {
        document.documentElement.style.visibility = 'visible';
      }
    });
})();

/**
 * Updates navbar elements dynamically based on user role (Admin vs. Student)
 */
function updateNavForRole(data) {
  const role = data.role || 'student';
  const email = data.email || 'user';

  // 1. Admin-Only navigation links
  if (role === 'admin') {
    document.querySelectorAll('.admin-only-link').forEach(el => {
      el.style.setProperty('display', 'inline-block', 'important');
    });
  } else {
    // Completely remove Admin-Only links for Students
    document.querySelectorAll('.admin-only-link').forEach(el => {
      el.remove();
    });
  }

  // 2. Clean up static login/register anchor links if present
  const navActions = document.querySelector('.nav-actions');
  if (navActions) {
    navActions.querySelectorAll('a').forEach(a => {
      const href = a.getAttribute('href') || '';
      if (href.includes('login.html') || href.includes('register.html')) {
        a.remove();
      }
    });

    // 3. Populate or replace #nav-user-controls cleanly without duplicate badge
    let userControls = document.getElementById('nav-user-controls');
    if (!userControls) {
      userControls = document.createElement('div');
      userControls.id = 'nav-user-controls';
      userControls.style.display = 'inline-flex';
      userControls.style.alignItems = 'center';
      userControls.style.gap = '6px';

      const themeBtn = document.getElementById('theme-toggle-btn');
      if (themeBtn) {
        navActions.insertBefore(userControls, themeBtn);
      } else {
        navActions.prepend(userControls);
      }
    }

    const badgeRoleClass = (role === 'admin') ? 'badge-success' : 'badge-tech';
    const roleDisplay = (role === 'admin')
      ? 'Admin: ' + email
      : 'Student: ' + (data.enrollment || email.split('@')[0]);
    const dashboardHref = (role === 'admin')
      ? '/studenthub/php/admin_dashboard.php'
      : '/studenthub/dashboard.html';
    const logoutHref = '/studenthub/php/logout.php';

    userControls.innerHTML = `
      <span class="badge ${badgeRoleClass}" title="${email}" style="max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; padding: 4px 8px; font-size: 0.8rem; font-weight: 600;">
        ${roleDisplay}
      </span>
      <a href="${dashboardHref}" class="btn btn-outline btn-sm" style="white-space: nowrap; padding: 4px 10px; font-size: 0.85rem;">Dashboard</a>
      <a href="${logoutHref}" class="btn btn-danger btn-sm" style="white-space: nowrap; padding: 4px 10px; font-size: 0.85rem;">Logout</a>
    `;
  }
}

// ----------------------------------------------------------
// Standard UI Initialization on DOMContentLoaded
// ----------------------------------------------------------
document.addEventListener('DOMContentLoaded', () => {
  initThemeToggle();
  initMobileMenu();
  initNoticeBanner();
  initImageSlider();
  initAccordion();
  initModal();
});

/* ----------------------------------------------------------
   1. Theme Toggle (Dark / Light Mode with localStorage)
   ---------------------------------------------------------- */
function initThemeToggle() {
  const toggleBtns = document.querySelectorAll('#theme-toggle-btn, #theme-toggle, .theme-toggle-btn');
  const currentTheme = localStorage.getItem('studenthub_theme') || 'light';

  document.documentElement.setAttribute('data-theme', currentTheme);
  toggleBtns.forEach(btn => updateThemeButton(btn, currentTheme));

  toggleBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      const activeTheme = document.documentElement.getAttribute('data-theme');
      const newTheme = activeTheme === 'dark' ? 'light' : 'dark';

      document.documentElement.setAttribute('data-theme', newTheme);
      localStorage.setItem('studenthub_theme', newTheme);
      toggleBtns.forEach(b => updateThemeButton(b, newTheme));
    });
  });
}

function updateThemeButton(btn, theme) {
  if (!btn) return;
  if (theme === 'dark') {
    btn.innerHTML = '<span class="theme-text">Light Mode</span>';
    btn.setAttribute('aria-label', 'Switch to Light Mode');
  } else {
    btn.innerHTML = '<span class="theme-text">Dark Mode</span>';
    btn.setAttribute('aria-label', 'Switch to Dark Mode');
  }
}

/* ----------------------------------------------------------
   2. Mobile Hamburger Menu Toggle
   ---------------------------------------------------------- */
function initMobileMenu() {
  const hamburgerBtn = document.getElementById('hamburger-btn');
  const navMenu = document.getElementById('nav-menu');

  if (hamburgerBtn && navMenu) {
    hamburgerBtn.addEventListener('click', () => {
      navMenu.classList.toggle('show');
      const isOpen = navMenu.classList.contains('show');
      hamburgerBtn.setAttribute('aria-expanded', isOpen);
    });
  }
}

/* ----------------------------------------------------------
   3. Dismissible Notification Banner (index.html)
   ---------------------------------------------------------- */
function initNoticeBanner() {
  const banner = document.getElementById('notice-banner');
  const closeBtn = document.getElementById('notice-close-btn');

  if (banner && closeBtn) {
    if (sessionStorage.getItem('notice_banner_closed') === 'true') {
      banner.style.display = 'none';
    }

    closeBtn.addEventListener('click', () => {
      banner.style.display = 'none';
      sessionStorage.setItem('notice_banner_closed', 'true');
    });
  }
}

/* ----------------------------------------------------------
   4. Image & Content Slider (index.html)
   ---------------------------------------------------------- */
function initImageSlider() {
  const sliderWrapper = document.getElementById('slider-wrapper');
  const slides = document.querySelectorAll('.slide');
  const prevBtn = document.getElementById('slider-prev');
  const nextBtn = document.getElementById('slider-next');
  const dotsContainer = document.getElementById('slider-dots');

  if (!sliderWrapper || slides.length === 0) return;

  let currentSlide = 0;
  const totalSlides = slides.length;
  let autoSlideTimer = null;

  if (dotsContainer) {
    dotsContainer.innerHTML = '';
    slides.forEach((_, index) => {
      const dot = document.createElement('span');
      dot.classList.add('slider-dot');
      if (index === 0) dot.classList.add('active');
      dot.addEventListener('click', () => {
        goToSlide(index);
        restartAutoSlide();
      });
      dotsContainer.appendChild(dot);
    });
  }

  function goToSlide(index) {
    if (index < 0) {
      currentSlide = totalSlides - 1;
    } else if (index >= totalSlides) {
      currentSlide = 0;
    } else {
      currentSlide = index;
    }

    sliderWrapper.style.transform = `translateX(-${currentSlide * 100}%)`;

    const dots = document.querySelectorAll('.slider-dot');
    dots.forEach((d, i) => {
      d.classList.toggle('active', i === currentSlide);
    });
  }

  if (prevBtn) {
    prevBtn.addEventListener('click', () => {
      goToSlide(currentSlide - 1);
      restartAutoSlide();
    });
  }

  if (nextBtn) {
    nextBtn.addEventListener('click', () => {
      goToSlide(currentSlide + 1);
      restartAutoSlide();
    });
  }

  function startAutoSlide() {
    autoSlideTimer = setInterval(() => {
      goToSlide(currentSlide + 1);
    }, 4500);
  }

  function restartAutoSlide() {
    if (autoSlideTimer) clearInterval(autoSlideTimer);
    startAutoSlide();
  }

  startAutoSlide();
}

/* ----------------------------------------------------------
   5. Collapsible Accordion (faq.html)
   ---------------------------------------------------------- */
function initAccordion() {
  const accordionHeaders = document.querySelectorAll('.accordion-header');

  accordionHeaders.forEach(header => {
    header.addEventListener('click', () => {
      const item = header.parentElement;
      const isOpen = item.classList.contains('active');

      document.querySelectorAll('.accordion-item').forEach(i => {
        i.classList.remove('active');
      });

      if (!isOpen) {
        item.classList.add('active');
      }
    });
  });
}

/* ----------------------------------------------------------
   6. Modal Popup (events.html)
   ---------------------------------------------------------- */
function initModal() {
  const modal = document.getElementById('event-modal');
  const closeBtn = document.getElementById('modal-close-btn');

  if (modal && closeBtn) {
    closeBtn.addEventListener('click', () => {
      closeModal();
    });

    modal.addEventListener('click', (e) => {
      if (e.target === modal) {
        closeModal();
      }
    });

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && modal.classList.contains('active')) {
        closeModal();
      }
    });
  }
}

function openModal(title, contentHtml) {
  const modal = document.getElementById('event-modal');
  const modalTitle = document.getElementById('modal-title');
  const modalBody = document.getElementById('modal-body');

  if (modal && modalTitle && modalBody) {
    modalTitle.textContent = title;
    modalBody.innerHTML = contentHtml;
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
  }
}

function closeModal() {
  const modal = document.getElementById('event-modal');
  if (modal) {
    modal.classList.remove('active');
    document.body.style.overflow = 'auto';
  }
}
