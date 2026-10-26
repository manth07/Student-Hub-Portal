/**
 * ==========================================================
 * StudentHub Portal - Data Fetching & Dynamic Rendering
 * Practical 6: Asynchronous Data Loading with Fetch API
 * ==========================================================
 */

document.addEventListener('DOMContentLoaded', () => {
  // Check which page is currently loaded
  if (document.getElementById('events-container')) {
    initEventsPage();
  }

  if (document.getElementById('students-container')) {
    initStudentsPage();
  }

  if (document.getElementById('faqs-container')) {
    initFaqsPage();
  }
});

/* ----------------------------------------------------------
   1. Events Page (events.html)
   ---------------------------------------------------------- */
let allEvents = [];
let filteredEvents = [];
let eventCurrentPage = 1;
const eventsPerPage = 5;

function initEventsPage() {
  const container = document.getElementById('events-container');
  const searchInput = document.getElementById('event-search');
  const categoryFilter = document.getElementById('event-category');
  const sortSelect = document.getElementById('event-sort');
  const prevBtn = document.getElementById('event-prev-btn');
  const nextBtn = document.getElementById('event-next-btn');

  // Fetch events.json using Fetch API
  fetch('/studenthub/data/events.json')
    .then(response => {
      if (!response.ok) throw new Error('Network error loading events.json');
      return response.json();
    })
    .then(data => {
      allEvents = data;
      filteredEvents = [...allEvents];
      applyEventFilters();
    })
    .catch(error => {
      console.error('Error fetching events:', error);
      container.innerHTML = `<div class="alert alert-danger">Failed to load events: ${error.message}</div>`;
    });

  // Filter Listeners
  if (searchInput) searchInput.addEventListener('input', applyEventFilters);
  if (categoryFilter) categoryFilter.addEventListener('change', applyEventFilters);
  if (sortSelect) sortSelect.addEventListener('change', applyEventFilters);

  if (prevBtn) {
    prevBtn.addEventListener('click', () => {
      if (eventCurrentPage > 1) {
        eventCurrentPage--;
        renderEventCards();
      }
    });
  }

  if (nextBtn) {
    nextBtn.addEventListener('click', () => {
      const maxPages = Math.ceil(filteredEvents.length / eventsPerPage);
      if (eventCurrentPage < maxPages) {
        eventCurrentPage++;
        renderEventCards();
      }
    });
  }
}

function applyEventFilters() {
  const searchVal = (document.getElementById('event-search')?.value || '').toLowerCase().trim();
  const categoryVal = document.getElementById('event-category')?.value || 'All';
  const sortVal = document.getElementById('event-sort')?.value || 'date-asc';

  filteredEvents = allEvents.filter(ev => {
    const matchesSearch = ev.title.toLowerCase().includes(searchVal) ||
                          ev.description.toLowerCase().includes(searchVal) ||
                          ev.venue.toLowerCase().includes(searchVal);
    const matchesCategory = (categoryVal === 'All') || (ev.category === categoryVal);
    return matchesSearch && matchesCategory;
  });

  // Sorting
  filteredEvents.sort((a, b) => {
    if (sortVal === 'date-asc') {
      return new Date(a.date) - new Date(b.date);
    } else {
      return new Date(b.date) - new Date(a.date);
    }
  });

  eventCurrentPage = 1;
  renderEventCards();
}

function renderEventCards() {
  const container = document.getElementById('events-container');
  const pageInfo = document.getElementById('event-page-info');
  const prevBtn = document.getElementById('event-prev-btn');
  const nextBtn = document.getElementById('event-next-btn');

  if (!container) return;

  if (filteredEvents.length === 0) {
    container.innerHTML = `
      <div style="grid-column: 1 / -1; text-align: center; padding: 40px;" class="card">
        <h3>No Events Found</h3>
        <p class="text-muted">Try adjusting your search query or category filter.</p>
      </div>`;
    if (pageInfo) pageInfo.textContent = 'Page 0 of 0';
    if (prevBtn) prevBtn.disabled = true;
    if (nextBtn) nextBtn.disabled = true;
    return;
  }

  const totalPages = Math.ceil(filteredEvents.length / eventsPerPage);
  const startIndex = (eventCurrentPage - 1) * eventsPerPage;
  const pageItems = filteredEvents.slice(startIndex, startIndex + eventsPerPage);

  container.innerHTML = pageItems.map(ev => {
    const categoryBadgeClass = 'badge-' + ev.category.toLowerCase();
    return `
      <div class="card event-card" style="display:flex; flex-direction:column; justify-content:space-between;">
        <div>
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
            <span class="badge ${categoryBadgeClass}">${ev.category}</span>
            <span style="font-size:0.85rem; color:var(--text-muted);">Date: ${ev.date}</span>
          </div>
          <h3 class="card-title">${ev.title}</h3>
          <p style="font-size:0.9rem; color:var(--text-muted); margin-bottom:8px;"><strong>Venue:</strong> ${ev.venue}</p>
          <p style="font-size:0.95rem; margin-bottom:18px;">${ev.description}</p>
        </div>
        <div style="margin-top:auto; padding-top:12px; border-top:1px solid var(--border-color);">
          <button class="btn btn-outline btn-sm btn-block" onclick="showEventDetailsModal(${ev.id})">
            View Details & Register
          </button>
        </div>
      </div>
    `;
  }).join('');

  if (pageInfo) {
    pageInfo.textContent = `Page ${eventCurrentPage} of ${totalPages} (${filteredEvents.length} events)`;
  }
  if (prevBtn) prevBtn.disabled = (eventCurrentPage === 1);
  if (nextBtn) nextBtn.disabled = (eventCurrentPage >= totalPages);
}

// Modal Trigger for Event Details
window.showEventDetailsModal = function(eventId) {
  const ev = allEvents.find(e => e.id === eventId);
  if (!ev) return;

  const html = `
    <div style="text-align:left;">
      <p><strong>Category:</strong> <span class="badge badge-${ev.category.toLowerCase()}">${ev.category}</span></p>
      <p><strong>Date:</strong> ${ev.date}</p>
      <p><strong>Venue:</strong> ${ev.venue}</p>
      <p><strong>Description:</strong></p>
      <p class="text-muted" style="background:var(--bg-color); padding:10px; border-radius:6px;">${ev.description}</p>
      <div class="alert alert-info" style="margin-top:15px; margin-bottom:0;">
        <strong>Quick Registration:</strong> To register, make sure you are logged in, then confirm below.
      </div>
    </div>
  `;

  if (typeof openModal === 'function') {
    openModal(ev.title, html);
  } else {
    alert(`${ev.title}\nDate: ${ev.date}\nVenue: ${ev.venue}\n${ev.description}`);
  }
};

/* ----------------------------------------------------------
   2. Student Directory Page (profile.html)
   ---------------------------------------------------------- */
let allStudents = [];

function initStudentsPage() {
  const container = document.getElementById('students-container');
  const searchInput = document.getElementById('student-search');
  const courseFilter = document.getElementById('student-course');
  const yearFilter = document.getElementById('student-year');

  fetch('/studenthub/data/students.json')
    .then(res => res.json())
    .then(data => {
      allStudents = data;
      renderStudentsList(allStudents);
    })
    .catch(err => {
      console.error('Error fetching students:', err);
      container.innerHTML = `<div class="alert alert-danger">Error loading students: ${err.message}</div>`;
    });

  function filterStudents() {
    const q = (searchInput?.value || '').toLowerCase().trim();
    const course = courseFilter?.value || 'All';
    const year = yearFilter?.value || 'All';

    const results = allStudents.filter(s => {
      const matchQuery = s.name.toLowerCase().includes(q) ||
                         s.enrollment.toLowerCase().includes(q) ||
                         s.email.toLowerCase().includes(q);
      const matchCourse = (course === 'All') || (s.course === course);
      const matchYear = (year === 'All') || (s.year.toString() === year);

      return matchQuery && matchCourse && matchYear;
    });

    renderStudentsList(results);
  }

  if (searchInput) searchInput.addEventListener('input', filterStudents);
  if (courseFilter) courseFilter.addEventListener('change', filterStudents);
  if (yearFilter) yearFilter.addEventListener('change', filterStudents);
}

function renderStudentsList(list) {
  const container = document.getElementById('students-container');
  const countEl = document.getElementById('student-count');

  if (!container) return;
  if (countEl) countEl.textContent = `Showing ${list.length} Students`;

  if (list.length === 0) {
    container.innerHTML = `
      <div style="grid-column: 1 / -1; text-align: center; padding: 40px;" class="card">
        <h3>No Student Records Found</h3>
        <p class="text-muted">No student matched the search criteria.</p>
      </div>`;
    return;
  }

  container.innerHTML = list.map(s => {
    const skillsHtml = s.skills.map(sk => `<span class="badge" style="background:#e0e7ff; color:#3730a3; margin:2px;">${sk}</span>`).join(' ');
    return `
      <div class="card" style="display:flex; flex-direction:column; justify-content:space-between;">
        <div>
          <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:10px;">
            <div>
              <h3 style="margin-bottom:2px; font-size:1.15rem;">${s.name}</h3>
              <span class="badge" style="background:#e2e8f0; color:#334155;">${s.enrollment}</span>
            </div>
            <span class="badge badge-tech">${s.course}</span>
          </div>
          <p style="font-size:0.88rem; color:var(--text-muted); margin-bottom:4px;"><strong>Year:</strong> Semester ${s.year * 2 - 1} / Year ${s.year}</p>
          <p style="font-size:0.88rem; color:var(--text-muted); margin-bottom:12px;"><strong>Email:</strong> <a href="mailto:${s.email}" style="color:var(--primary-color);">${s.email}</a></p>
          <div style="margin-top:10px;">
            <p style="font-size:0.82rem; font-weight:600; margin-bottom:4px; color:var(--text-muted);">Skills / Interests:</p>
            <div>${skillsHtml}</div>
          </div>
        </div>
      </div>
    `;
  }).join('');
}

/* ----------------------------------------------------------
   3. FAQ Page (faq.html)
   ---------------------------------------------------------- */
let allFaqs = [];

function initFaqsPage() {
  const container = document.getElementById('faqs-container');
  const searchInput = document.getElementById('faq-search');
  const categoryFilter = document.getElementById('faq-category');

  fetch('/studenthub/data/faqs.json')
    .then(res => res.json())
    .then(data => {
      allFaqs = data;
      renderFaqsList(allFaqs);
    })
    .catch(err => {
      console.error('Error fetching FAQs:', err);
      container.innerHTML = `<div class="alert alert-danger">Error loading FAQs: ${err.message}</div>`;
    });

  function filterFaqs() {
    const q = (searchInput?.value || '').toLowerCase().trim();
    const category = categoryFilter?.value || 'All';

    const results = allFaqs.filter(f => {
      const matchQ = f.question.toLowerCase().includes(q) || f.answer.toLowerCase().includes(q);
      const matchCat = (category === 'All') || (f.category === category);
      return matchQ && matchCat;
    });

    renderFaqsList(results);
  }

  if (searchInput) searchInput.addEventListener('input', filterFaqs);
  if (categoryFilter) categoryFilter.addEventListener('change', filterFaqs);
}

function renderFaqsList(faqs) {
  const container = document.getElementById('faqs-container');
  if (!container) return;

  if (faqs.length === 0) {
    container.innerHTML = `
      <div class="card" style="text-align:center; padding:30px;">
        <h3>No FAQ questions found</h3>
        <p class="text-muted">Try searching with another keyword.</p>
      </div>`;
    return;
  }

  container.innerHTML = faqs.map((f, idx) => `
    <div class="accordion-item ${idx === 0 ? 'active' : ''}">
      <button class="accordion-header" type="button" aria-expanded="${idx === 0 ? 'true' : 'false'}">
        <span><span class="badge" style="background:#e0e7ff; color:#3730a3; margin-right:8px;">${f.category}</span> ${f.question}</span>
        <span class="accordion-icon">▼</span>
      </button>
      <div class="accordion-content">
        <p>${f.answer}</p>
      </div>
    </div>
  `).join('');

  // Re-attach accordion listeners for newly rendered elements
  if (typeof initAccordion === 'function') {
    initAccordion();
  }
}
