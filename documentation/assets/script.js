/**
 * Alfa Payment Gateway Documentation Script
 * Enhanced interactive functionality: smooth scroll-spy, responsive navigation, image lightbox
 */
document.addEventListener('DOMContentLoaded', () => {
  // Navigation Scroll Spy
  const mainNavLinks = document.querySelectorAll('nav ul li a');
  const sections = document.querySelectorAll('main section');
  const mobileToggle = document.getElementById('mobileMenuToggle');
  const navElement = document.querySelector('nav');

  // Mobile menu toggle
  if (mobileToggle && navElement) {
    mobileToggle.addEventListener('click', () => {
      navElement.classList.toggle('open');
      mobileToggle.classList.toggle('active');
    });

    // Close menu on link click on mobile
    mainNavLinks.forEach(link => {
      link.addEventListener('click', () => {
        if (window.innerWidth <= 860) {
          navElement.classList.remove('open');
          mobileToggle.classList.remove('active');
        }
      });
    });
  }

  // ScrollSpy with requestAnimationFrame for 60fps performance
  let ticking = false;
  window.addEventListener('scroll', () => {
    if (!ticking) {
      window.requestAnimationFrame(() => {
        const fromTop = window.scrollY + 120;

        sections.forEach(section => {
          const id = section.getAttribute('id');
          if (!id) return;
          const link = document.querySelector(`nav ul li a[href="#${id}"]`);
          if (!link) return;

          const top = section.offsetTop;
          const height = section.offsetHeight;

          if (fromTop >= top && fromTop < top + height) {
            mainNavLinks.forEach(l => l.classList.remove('current'));
            link.classList.add('current');
          }
        });

        ticking = false;
      });
      ticking = true;
    }
  });

  // Lightbox functionality for screenshots
  const images = document.querySelectorAll('section img.doc-screenshot');
  if (images.length > 0) {
    // Create modal elements
    const modal = document.createElement('div');
    modal.className = 'lightbox-modal';
    modal.innerHTML = `
      <div class="lightbox-overlay"></div>
      <div class="lightbox-content">
        <button class="lightbox-close" aria-label="Close image preview">&times;</button>
        <img src="" alt="Enlarged preview" class="lightbox-image" />
        <p class="lightbox-caption"></p>
      </div>
    `;
    document.body.appendChild(modal);

    const modalImg = modal.querySelector('.lightbox-image');
    const modalCaption = modal.querySelector('.lightbox-caption');
    const overlay = modal.querySelector('.lightbox-overlay');
    const closeBtn = modal.querySelector('.lightbox-close');

    const closeModal = () => {
      modal.classList.remove('active');
      document.body.style.overflow = '';
    };

    images.forEach(img => {
      img.style.cursor = 'zoom-in';
      img.addEventListener('click', () => {
        modalImg.src = img.src;
        modalCaption.textContent = img.getAttribute('alt') || img.nextElementSibling?.textContent || '';
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
      });
    });

    closeBtn.addEventListener('click', closeModal);
    overlay.addEventListener('click', closeModal);
    document.addEventListener('keydown', e => {
      if (e.key === 'Escape' && modal.classList.contains('active')) {
        closeModal();
      }
    });
  }

  // Copy code blocks
  document.querySelectorAll('.copy-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const targetId = btn.getAttribute('data-target');
      const codeBlock = document.getElementById(targetId);
      if (codeBlock) {
        navigator.clipboard.writeText(codeBlock.innerText.trim()).then(() => {
          const originalText = btn.innerHTML;
          btn.innerHTML = '<span>✓ Copied!</span>';
          setTimeout(() => {
            btn.innerHTML = originalText;
          }, 2000);
        });
      }
    });
  });
});
