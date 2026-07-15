/**
 * Header Menu Module
 * Handles mobile menu toggle, dropdown interactions, and responsive behavior
 */
export default class HeaderMenu {
  constructor() {
    this.cacheDOM();
    this.bindEvents();
  }

  cacheDOM() {
    this.toggle = document.querySelector('.mobile-menu-toggle');
    this.menu = document.querySelector('.main-menu');
    this.submenuLinks = document.querySelectorAll('.main-menu .menu-item-has-children > a');
    this.welcomeUser = document.querySelector('.awps-welcome');
    this.loginDropdown = document.querySelector('.awps-login-dropdown');
  }

  bindEvents() {
    // Mobile menu toggle
    if (this.toggle && this.menu) {
      this.toggle.addEventListener('click', (e) => {
        e.preventDefault();
        this.toggle.classList.toggle('active');
        this.menu.classList.toggle('show');
      });
    }

    // Submenu toggle on mobile
    if (this.submenuLinks.length > 0) {
      this.submenuLinks.forEach(link => {
        link.addEventListener('click', (e) => {
          if (window.innerWidth <= 767) {
            e.preventDefault();
            const submenu = link.nextElementSibling;
            if (submenu && submenu.classList.contains('dropdown-menu')) {
              submenu.classList.toggle('show');
              
              // Close other open submenus
              this.submenuLinks.forEach(otherLink => {
                const otherSubmenu = otherLink.nextElementSibling;
                if (otherSubmenu && otherSubmenu !== submenu) {
                  otherSubmenu.classList.remove('show');
                }
              });
            }
          }
        });
      });
    }

    // User dropdown toggle on mobile
    if (this.welcomeUser && this.loginDropdown) {
      this.welcomeUser.addEventListener('click', (e) => {
        if (window.innerWidth <= 767) {
          e.preventDefault();
          this.loginDropdown.classList.toggle('show');
        }
      });
    }

    // Close all menus when clicking outside
    document.addEventListener('click', (e) => {
      this.handleClickOutside(e);
    });

    // Handle window resize
    window.addEventListener('resize', () => {
      if (window.innerWidth > 767) {
        // Reset mobile states on desktop
        if (this.menu) this.menu.classList.remove('show');
        if (this.toggle) this.toggle.classList.remove('active');
        document.querySelectorAll('.dropdown-menu').forEach(submenu => {
          submenu.classList.remove('show');
        });
        if (this.loginDropdown) this.loginDropdown.classList.remove('show');
      }
    });
  }

  handleClickOutside(e) {
    // Close mobile menu
    if (this.toggle && this.menu) {
      if (
        !this.toggle.contains(e.target) && 
        !this.menu.contains(e.target) && 
        this.menu.classList.contains('show')
      ) {
        this.menu.classList.remove('show');
        this.toggle.classList.remove('active');
      }
    }

    // Close user dropdown
    if (this.loginDropdown) {
      if (
        !this.loginDropdown.contains(e.target) && 
        this.loginDropdown.classList.contains('show')
      ) {
        this.loginDropdown.classList.remove('show');
      }
    }

    // Close submenus when clicking a link (mobile only)
    if (window.innerWidth <= 767) {
      const clickedLink = e.target.closest('.dropdown-menu a');
      if (clickedLink) {
        document.querySelectorAll('.dropdown-menu').forEach(submenu => {
          submenu.classList.remove('show');
        });
        if (this.menu) this.menu.classList.remove('show');
        if (this.toggle) this.toggle.classList.remove('active');
      }
    }
  }
}