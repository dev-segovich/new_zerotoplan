/**
* Template Name: QuickStart
* Template URL: https://bootstrapmade.com/quickstart-bootstrap-startup-website-template/
* Updated: May 10 2024 with Bootstrap v5.3.3
* Author: BootstrapMade.com
* License: https://bootstrapmade.com/license/
*/

(function() {
  "use strict";

  /**
   * Apply .scrolled class to the body as the page is scrolled down
   */
  function toggleScrolled() {
    const selectBody = document.querySelector('body');
    const selectHeader = document.querySelector('#header');
    if (!selectHeader.classList.contains('scroll-up-sticky') && !selectHeader.classList.contains('sticky-top') && !selectHeader.classList.contains('fixed-top')) return;
    window.scrollY > 100 ? selectBody.classList.add('scrolled') : selectBody.classList.remove('scrolled');
  }

  document.addEventListener('scroll', toggleScrolled);
  window.addEventListener('load', toggleScrolled);

  /**
   * Mobile nav toggle
   */
  const mobileNavToggleBtn = document.querySelector('.mobile-nav-toggle');

  function mobileNavToogle() {
    document.querySelector('body').classList.toggle('mobile-nav-active');
    mobileNavToggleBtn.classList.toggle('bi-list');
    mobileNavToggleBtn.classList.toggle('bi-x');
  }
  mobileNavToggleBtn.addEventListener('click', mobileNavToogle);

  /**
   * Hide mobile nav on same-page/hash links
   */
  document.querySelectorAll('#navmenu a').forEach(navmenu => {
    navmenu.addEventListener('click', () => {
      if (document.querySelector('.mobile-nav-active')) {
        mobileNavToogle();
      }
    });

  });

  /**
   * Toggle mobile nav dropdowns
   */
  document.querySelectorAll('.navmenu .toggle-dropdown').forEach(navmenu => {
    navmenu.addEventListener('click', function(e) {
      if (document.querySelector('.mobile-nav-active')) {
        e.preventDefault();
        this.parentNode.classList.toggle('active');
        this.parentNode.nextElementSibling.classList.toggle('dropdown-active');
        e.stopImmediatePropagation();
      }
    });
  });

  /**
   * Preloader
   */
  const preloader = document.querySelector('#preloader');
  if (preloader) {
    window.addEventListener('load', () => {
      preloader.remove();
    });
  }

  /**
   * Scroll top button
   */
  let scrollTop = document.querySelector('.scroll-top');

  function toggleScrollTop() {
    if (scrollTop) {
      window.scrollY > 100 ? scrollTop.classList.add('active') : scrollTop.classList.remove('active');
    }
  }
  scrollTop.addEventListener('click', (e) => {
    e.preventDefault();
    window.scrollTo({
      top: 0,
      behavior: 'smooth'
    });
  });

  window.addEventListener('load', toggleScrollTop);
  document.addEventListener('scroll', toggleScrollTop);

  /**
   * Animation on scroll function and init
   */
  function aosInit() {
    AOS.init({
      duration: 600,
      easing: 'ease-in-out',
      once: true,
      mirror: false
    });
  }
  window.addEventListener('load', aosInit);

  /**
   * Initiate glightbox
   */
  const glightbox = GLightbox({
    selector: '.glightbox'
  });

  /**
   * Frequently Asked Questions Toggle
   */
  document.querySelectorAll('.faq-item h3, .faq-item .faq-toggle').forEach((faqItem) => {
    faqItem.addEventListener('click', () => {
      faqItem.parentNode.classList.toggle('faq-active');
    });
  });

  /**
   * Init swiper sliders
   */
  function initSwiper() {
    document.querySelectorAll('.swiper').forEach(function(swiper) {
      let config = JSON.parse(swiper.querySelector('.swiper-config').innerHTML.trim());
      new Swiper(swiper, config);
    });
  }
  window.addEventListener('load', initSwiper);

  /**
   * Correct scrolling position upon page load for URLs containing hash links.
   */
  window.addEventListener('load', function(e) {
    if (window.location.hash) {
      if (document.querySelector(window.location.hash)) {
        setTimeout(() => {
          let section = document.querySelector(window.location.hash);
          let scrollMarginTop = getComputedStyle(section).scrollMarginTop;
          window.scrollTo({
            top: section.offsetTop - parseInt(scrollMarginTop),
            behavior: 'smooth'
          });
        }, 100);
      }
    }
  });

  /**
   * Navmenu Scrollspy
   */
  let navmenulinks = document.querySelectorAll('.navmenu a');

  function navmenuScrollspy() {
    navmenulinks.forEach(navmenulink => {
      if (!navmenulink.hash) return;
      let section = document.querySelector(navmenulink.hash);
      if (!section) return;
      let position = window.scrollY + 200;
      if (position >= section.offsetTop && position <= (section.offsetTop + section.offsetHeight)) {
        document.querySelectorAll('.navmenu a.active').forEach(link => link.classList.remove('active'));
        navmenulink.classList.add('active');
      } else {
        navmenulink.classList.remove('active');
      }
    })
  }
  window.addEventListener('load', navmenuScrollspy);
  document.addEventListener('scroll', navmenuScrollspy);

})();



  (function () {
    const steps = document.querySelectorAll('#process .step');
    if (!('IntersectionObserver' in window) || steps.length === 0) {
      steps.forEach(s => s.classList.add('in-view'));
      return;
    }
    const io = new IntersectionObserver((entries) => {
      entries.forEach((e, i) => {
        if (e.isIntersecting) {
          // retraso escalonado para efecto "cascada"
          setTimeout(() => e.target.classList.add('in-view'), (i % 5) * 90);
          io.unobserve(e.target);
        }
      });
    }, { threshold: 0.2 });
    steps.forEach(s => io.observe(s));
  })();

  (function () {
    const modalEl = document.getElementById('videoModal');
    const iframe = document.getElementById('heroVideo');
    const src = iframe.getAttribute('data-src');

    modalEl.addEventListener('show.bs.modal', () => {
      // setea el src al abrir para que haga autoplay dentro del modal
      iframe.setAttribute('src', src);
    });

    modalEl.addEventListener('hidden.bs.modal', () => {
      // limpia el src para pausar el video al cerrar
      iframe.setAttribute('src', '');
    });
  })();

  document.addEventListener("DOMContentLoaded", () => {
  const messagesEl = document.getElementById("chat-messages");
  const fakeText   = document.getElementById("fakeText");
  const fakeCaret  = document.getElementById("fakeCaret");
  const i18nBox    = document.getElementById("aichat-i18n");

  function readQuestions(){
    const items = i18nBox ? i18nBox.querySelectorAll('[data-section="aichat"]') : [];
    const list = [];
    items.forEach(el => {
      const txt = (el.textContent || "").trim();
      if (txt) list.push(txt);
    });
    return list.length ? list : [
      "Are there incentives or funding programs for this site?",
      "What are the zoning height and density limits?",
      "How does my unit mix compare with competitors?",
      "What is the projected exit cap rate over five years?",
      "How fast did comparable projects lease and at what rent?"
    ];
  }

  let questions = readQuestions();
  let idx = 0;
  const TYPE_MS = 50;
  const THINK_MS = 3000;
  const GAP_AFTER_SEND = 400;

  function wait(ms){ return new Promise(res => setTimeout(res, ms)); }
  function jitter(n){ return Math.round((Math.random()-0.5)*n); }

  // 🔑 Control de ciclo único
  let currentRunId = 0; // identificador único de cada loop

  async function typeIntoFake(text, runId){
    fakeText.textContent = "";
    fakeCaret.style.display = "inline-block";
    for (let i = 0; i < text.length; i++){
      // si cambió el idioma/ciclo, abortamos y limpiamos
      if (runId !== currentRunId) { 
        fakeText.textContent = "";
        return;
      }
      fakeText.textContent += text[i];
      await wait(TYPE_MS + jitter(8));
    }
    await wait(150);
  }

  function createUserBubble(text){
    const b = document.createElement("div");
    b.className = "message user enter";
    b.textContent = text;
    return b;
  }

  function createAIThinking(){
    const a = document.createElement("div");
    a.className = "message ai";
    a.innerHTML = `<span class="thinking">
      <span class="dot"></span><span class="dot"></span><span class="dot"></span>
    </span>`;
    return a;
  }

  function clearHistory(){ messagesEl.innerHTML = ""; }

  async function cycle(runId){
    while (runId === currentRunId) {
      clearHistory();

      const q = questions[idx];
      await typeIntoFake(q, runId);
      if (runId !== currentRunId) return;

      const userBubble = createUserBubble(q);
      messagesEl.appendChild(userBubble);
      fakeText.textContent = "";
      fakeCaret.style.display = "none";
      await wait(GAP_AFTER_SEND);
      if (runId !== currentRunId) return;

      const aiBubble = createAIThinking();
      messagesEl.appendChild(aiBubble);
      await wait(THINK_MS);

      idx = (idx + 1) % questions.length;
      await wait(350);
    }
  }

  function startCycle(){
    currentRunId++;
    cycle(currentRunId);
  }

  // Arranque inicial (pequeño delay por si tu i18n setea EN en load)
  setTimeout(startCycle, 20);

  // 🛑 Debounce del observer para no reiniciar varias veces
  if (i18nBox && "MutationObserver" in window){
    let debounceTimer = null;
    const obs = new MutationObserver(() => {
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(() => {
        questions = readQuestions();
        idx = 0;
        startCycle();
      }, 120); // espera a que terminen todos los cambios del i18n
    });
    obs.observe(i18nBox, { childList: true, subtree: true, characterData: true });
  }
});
